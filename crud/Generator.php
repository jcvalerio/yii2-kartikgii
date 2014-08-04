<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace jcvalerio\kartikgii\crud;

use Yii;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\Schema;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\web\Controller;
use apptitude\helpers\UtilHelper;

/**
 * Generates CRUD
 *
 * @property array $columnNames Model column names. This property is read-only.
 * @property string $controllerID The controller ID (without the module ID prefix). This property is
 * read-only.
 * @property array $searchAttributes Searchable attributes. This property is read-only.
 * @property boolean|\yii\db\TableSchema $tableSchema This property is read-only.
 * @property string $viewPath The action view file path. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Generator extends \yii\gii\generators\crud\Generator
{

    /**
     * @const TEXT_FIELD_DEFAULT_SIZE Default text control length.
     */
    const TEXT_FIELD_DEFAULT_SIZE = 60;

    /**
     * @const TEXT_FIELD_MAX_SIZE Column max length to use a text control.
     */
    const TEXT_FIELD_MAX_SIZE = 255;

    public $modelClass;
    public $moduleID;
    public $controllerClass;
    public $baseControllerClass = 'yii\web\Controller';
    public $customBaseControllerClass;
    public $indexWidgetType = 'grid';
    public $searchModelClass = '';
    public $columns = 2;
    public $commonModelNamespace = 'common\models';
    public $createTime = array('AddedDate', 'create_time', 'createtime', 'created_at', 'createdat', 'created_time', 'createdtime');
    public $updateTime = array('EditedDate', 'changed', 'changed_at', 'updatetime', 'modified_at', 'updated_at', 'update_time', 'timestamp', 'updatedat');
    public $addedBy = array('AddedById');
    public $editedBy = array('EditedById');
    public $timestampAuditTrailFields = [];
    public $userAuditTrailFields = [];
    public $auditTrailFields = [];
    public $generateAllViews = false;
    public $generateController = false;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Kartik CRUD Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates a controller and views that implement CRUD (Create, Read, Update, Delete)
            operations for the specified data model.';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->timestampAuditTrailFields = array_merge($this->createTime, $this->updateTime);
        $this->userAuditTrailFields = array_merge($this->addedBy, $this->editedBy);
        $this->auditTrailFields = array_merge($this->timestampAuditTrailFields, $this->userAuditTrailFields);
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['moduleID', 'controllerClass', 'modelClass', 'searchModelClass', 'baseControllerClass', 'commonModelNamespace', 'customBaseControllerClass'], 'filter', 'filter' => 'trim'],
            [['modelClass', 'controllerClass', 'baseControllerClass', 'indexWidgetType', 'commonModelNamespace', 'customBaseControllerClass'], 'required'],
            [['searchModelClass'], 'compare', 'compareAttribute' => 'modelClass', 'operator' => '!==', 'message' => 'Search Model Class must not be equal to Model Class.'],
            [['modelClass', 'controllerClass', 'baseControllerClass', 'searchModelClass', 'customBaseControllerClass'], 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['modelClass'], 'validateClass', 'params' => ['extends' => BaseActiveRecord::className()]],
            [['baseControllerClass'], 'validateClass', 'params' => ['extends' => Controller::className()]],
            [['controllerClass'], 'match', 'pattern' => '/Controller$/', 'message' => 'Controller class name must be suffixed with "Controller".'],
            [['controllerClass'], 'match', 'pattern' => '/(^|\\\\)[A-Z][^\\\\]+Controller$/', 'message' => 'Controller class name must start with an uppercase letter.'],
            [['controllerClass', 'searchModelClass'], 'validateNewClass'],
            [['indexWidgetType'], 'in', 'range' => ['grid', 'list']],
            [['modelClass'], 'validateModelClass'],
            [['moduleID'], 'validateModuleID'],
            [['enableI18N', 'generateAllViews', 'generateController'], 'boolean'],
            [['columns'], 'integer'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'modelClass' => 'Model Class',
            'moduleID' => 'Module ID',
            'controllerClass' => 'Controller Class',
            'baseControllerClass' => 'Base Controller Class',
            'indexWidgetType' => 'Widget Used in Index Page',
            'searchModelClass' => 'Search Model Class',
            'columns' => 'Form Columns',
            'customBaseControllerClass' => 'Base Controller Class Custom',
            'generateAllViews' => 'Generate all views?',
            'generateController' => 'Generate controller?'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'modelClass' => 'This is the ActiveRecord class associated with the table that CRUD will be built upon.
                You should provide a fully qualified class name, e.g., <code>app\models\Post</code>.',
            'controllerClass' => 'This is the name of the controller class to be generated. You should
                provide a fully qualified namespaced class, .e.g, <code>app\controllers\PostController</code>.
                The controller class name should follow the CamelCase scheme with an uppercase first letter',
            'baseControllerClass' => 'This is the class that the new CRUD controller class will extend from.
                You should provide a fully qualified class name, e.g., <code>yii\web\Controller</code>.',
            'moduleID' => 'This is the ID of the module that the generated controller will belong to.
                If not set, it means the controller will belong to the application.',
            'indexWidgetType' => 'This is the widget type to be used in the index page to display list of the models.
                You may choose either <code>GridView</code> or <code>ListView</code>',
            'searchModelClass' => 'This is the name of the search model class to be generated. You should provide a fully
                qualified namespaced class name, e.g., <code>app\models\PostSearch</code>.',
            'commonModelNamespace' => 'This is the namespace where the common model class are located. This is used to
                generated qualified namespaced class name, e.g., <code>common\models\UserAccount::getKeyValuePairs()</code>.',
            'customBaseControllerClass' => 'This is the class that the new CRUD controller class will extend from.
                You should provide a fully qualified class name, e.g., <code>yii\web\Controller</code>.',
            'generateAllViews' => 'Generates all models using default values <code>commom\models</code>',
            'generateController' => 'Generate the controller class, be careful, because you can override custom implementations'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return [
            'controller.php',
            'base/baseController.php'
            ];
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['baseControllerClass', 'moduleID', 'indexWidgetType']);
    }

    /**
     * Checks if model class is valid
     */
    public function validateModelClass()
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pk = $class::primaryKey();
        if (empty($pk)) {
            $this->addError('modelClass', "The table associated with $class must have primary key(s).");
        }
    }

    /**
     * Checks if model ID is valid
     */
    public function validateModuleID()
    {
        if (!empty($this->moduleID)) {
            $module = Yii::$app->getModule($this->moduleID);
            if ($module === null) {
                $this->addError('moduleID', "Module '{$this->moduleID}' does not exist.");
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        if($this->generateAllViews) {
            $files = $this->generateAll();
        } else {
            $files = $this->generateFiles();
        }

        return $files;
    }
    
    private function generateAll()
    {
        $modelClass = $this->modelClass;
        $searchModelClass = $this->searchModelClass;
        $controllerClass = $this->controllerClass;
        $customBaseControllerClass = $this->customBaseControllerClass;

        $files = [];        
        $models = Yii::$app->db->schema->tableNames;
        foreach ($models as $tableName) {
            $this->modelClass = 'common\\models\\' . $tableName;
            $this->searchModelClass = 'common\\models\\' . $tableName . 'Search';
            $this->controllerClass = 'backend\\controllers\\' . $tableName . 'Controller';
            $this->customBaseControllerClass = 'backend\\controllers\\base\\' . $tableName . 'BaseController';

            $filename = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->modelClass, '\\')) . '.php');
            if(file_exists($filename)) {
                $files = $this->generateFiles($files);
            }
        }
        
        $this->modelClass = $modelClass;
        $this->searchModelClass = $searchModelClass;
        $this->controllerClass = $controllerClass;
        $this->customBaseControllerClass = $customBaseControllerClass;
        
        return $files;
    }
    
    private function generateFiles($files = [])
    {
        $baseControllerFile = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->customBaseControllerClass, '\\')) . '.php');
        $files[] = new CodeFile($baseControllerFile, $this->render('base/baseController.php'));
        if($this->generateController) {
            $controllerFile = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->controllerClass, '\\')) . '.php');
            $files[] = new CodeFile($controllerFile, $this->render('controller.php'));
        }

        if (!empty($this->searchModelClass)) {
            $searchModel = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->searchModelClass, '\\') . '.php'));
            $files[] = new CodeFile($searchModel, $this->render('search.php'));
        }

        $viewPath = $this->getViewPath();
        $templatePath = $this->getTemplatePath() . '/views';
        foreach (scandir($templatePath) as $file) {
            if (empty($this->searchModelClass) && $file === '_search.php') {
                continue;
            }
            if (is_file($templatePath . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $files[] = new CodeFile("$viewPath/$file", $this->render("views/$file"));
            }
        }

        return $files;
    }

    /**
     * @return string the controller ID (without the module ID prefix)
     */
    public function getControllerID()
    {
        $pos = strrpos($this->controllerClass, '\\');
        $class = substr(substr($this->controllerClass, $pos + 1), 0, -10);

        return Inflector::camel2id($class);
    }

    /**
     * @return string the action view file path
     */
    public function getViewPath()
    {
        $module = empty($this->moduleID) ? Yii::$app : Yii::$app->getModule($this->moduleID);

        return $module->getViewPath() . '/' . $this->getControllerID();
    }

    public function getNameAttribute()
    {
        foreach ($this->getColumnNames() as $name) {
            if (!strcasecmp($name, 'name') || !strcasecmp($name, 'title')) {
                return $name;
            }
        }
        /** @var \yii\db\ActiveRecord $class */
        $class = $this->modelClass;
        $pk = $class::primaryKey();

        return $pk[0];
    }

    /**
     * Generates code for active field
     * @param \yii\db\ColumnSchema $column describes the metadata of a column in a database table.
     * @return string
     */
    public function isValidField($column)
    {
        $isValidField = true;
        if ($column->isPrimaryKey) {
            $isValidField = false;
        } else {
            $isAuditTrailField = in_array($column->name, $this->auditTrailFields);
            if ($isAuditTrailField) {
                $isValidField = false;
            }
        }
        return $isValidField;
    }

    /**
     * Generates code for active field
     * @param string $attribute
     * @return string
     */
    public function generateActiveField($attribute)
    {
        $tableSchema = $this->getTableSchema();
        $column = $tableSchema->columns[$attribute];
        return $this->generateActiveFieldControl($tableSchema, $column);
    }

    /**
     * Generates code for valid active fields
     * @return string representation of all valid active fields
     */
    public function generateActiveFields()
    {
        $activeFields = "";
        $indentation = 4;
        $tableSchema = $this->getTableSchema();
        foreach ($tableSchema->columns as $column) {
            if ($this->isValidField($column)) {
                $activeFields .= UtilHelper::indentCode($indentation) . $this->generateActiveFieldControl($tableSchema, $column) . "\n";
            }
        }
        return $activeFields;
    }

    /**
     * Generates code for active field
     * @param \yii\db\TableSchema $tableSchema represents the metadata of a database table.
     * @param \yii\db\ColumnSchema $column describes the metadata of a column in a database table.
     * @return string
     */
    public function generateActiveFieldControl($tableSchema, $column)
    {
        $attribute = $column->name;
        $model = new $this->modelClass();
        if ($tableSchema === false || !isset($column)) {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $attribute)) {
                return "'$attribute' => ['type'=> TabularForm::INPUT_PASSWORD,'options' => ['placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "'))]],";
                //return "\$form->field(\$model, '$attribute')->passwordInput()";
            } else {
                return "'$attribute' => ['type'=> TabularForm::INPUT_TEXT, 'options' => ['placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "'))]],";
                //return "\$form->field(\$model, '$attribute')";
            }
        }
        $isAuditTrailField = in_array($column->name, $this->auditTrailFields);
        if ($isAuditTrailField) {
            return '';
        }
        $foreignKey = $this->getForeignKey($tableSchema, $column);
        if (!empty($foreignKey)) {
            return $this->generateForeignKeyField($column, $foreignKey, $attribute);
        } elseif ($column->phpType === 'boolean') {
            //return "\$form->field(\$model, '$attribute')->checkbox()";
            return "'$attribute' => ['type' => Form::INPUT_CHECKBOX, 'options' => ['placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "'))]],";
        } elseif ($column->type === 'text') {
            //return "\$form->field(\$model, '$attribute')->textarea(['rows' => 6])";
            return "'$attribute' => ['type' => Form::INPUT_TEXTAREA, 'options' => ['placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "')),'rows'=> 6]],";
        } elseif ($column->type === 'date') {
            return "'$attribute' => ['type' => Form::INPUT_WIDGET, 'widgetClass' => DateControl::classname(),'options' => ['type' => DateControl::FORMAT_DATE]],";
        } elseif ($column->type === 'time') {
            return "'$attribute' => ['type' => Form::INPUT_WIDGET, 'widgetClass' => DateControl::classname(),'options' => ['type' => DateControl::FORMAT_TIME]],";
        } elseif ($column->type === 'datetime' || $column->type === 'timestamp') {
            return "'$attribute' => ['type' => Form::INPUT_WIDGET, 'widgetClass' => DateControl::classname(),'options' => ['type' => DateControl::FORMAT_DATETIME]],";
        } else {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                $input = 'INPUT_PASSWORD';
            } else {
                $input = 'INPUT_TEXT';
            }
            if ($column->phpType !== 'string' || $column->size === null) {
                //return "\$form->field(\$model, '$attribute')->$input()";
                return "'$attribute' => ['type' => Form::" . $input . ", 'options' => ['placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "'))]],";
            } elseif ($column->size > self::TEXT_FIELD_MAX_SIZE) {
                return "'$attribute' => ['type' => Form::INPUT_TEXTAREA, 'options' => ['placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "')),'rows' => 6]],";
            } else {
                if (($size = $maxLength = $column->size) > self::TEXT_FIELD_DEFAULT_SIZE) {
                    $size = self::TEXT_FIELD_DEFAULT_SIZE;
                }

                //return "\$form->field(\$model, '$attribute')->$input(['maxlength' => $column->size])";
                return "'$attribute' => ['type' => Form::" . $input . ", 'options' => ['placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "')), 'maxlength' => " . $column->size . "]],";
            }
        }
    }

    /**
     * Generates code for active search field
     * @param string $attribute
     * @return string
     */
    public function generateActiveSearchField($attribute)
    {
        $tableSchema = $this->getTableSchema();
        if ($tableSchema === false) {
            return "\$form->field(\$model, '$attribute')";
        }
        $column = $tableSchema->columns[$attribute];
        if ($column->phpType === 'boolean') {
            return "\$form->field(\$model, '$attribute')->checkbox()";
        } else {
            return "\$form->field(\$model, '$attribute')";
        }
    }

    /**
     * Generates column format
     * @param \yii\db\ColumnSchema $column
     * @return string
     */
    public function generateColumnFormat($column)
    {
        if ($column->phpType === 'boolean') {
            return 'boolean';
        } elseif ($column->type === 'text') {
            return 'ntext';
        } elseif (stripos($column->name, 'time') !== false && $column->phpType === 'integer') {
            return 'datetime';
        } elseif (stripos($column->name, 'email') !== false) {
            return 'email';
        } elseif (stripos($column->name, 'url') !== false) {
            return 'url';
        } else {
            return 'text';
        }
    }

    /**
     * Generates validation rules for the search model.
     * @return array the generated validation rules
     */
    public function generateSearchRules()
    {
        if (($table = $this->getTableSchema()) === false) {
            return ["[['" . implode("', '", $this->getColumnNames()) . "'], 'safe']"];
        }
        $types = [];
        foreach ($table->columns as $column) {
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                default:
                    $types['safe'][] = $column->name;
                    break;
            }
        }

        $rules = [];
        foreach ($types as $type => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
        }

        return $rules;
    }

    /**
     * @return array searchable attributes
     */
    public function getSearchAttributes()
    {
        return $this->getColumnNames();
    }

    /**
     * Generates the attribute labels for the search model.
     * @return array the generated attribute labels (name => label)
     */
    public function generateSearchLabels()
    {
        /** @var \yii\base\Model $model */
        $model = new $this->modelClass();
        $attributeLabels = $model->attributeLabels();
        $labels = [];
        foreach ($this->getColumnNames() as $name) {
            if (isset($attributeLabels[$name])) {
                $labels[$name] = $attributeLabels[$name];
            } else {
                if (!strcasecmp($name, 'id')) {
                    $labels[$name] = 'ID';
                } else {
                    $label = Inflector::camel2words($name);
                    if (strcasecmp(substr($label, -3), ' id') === 0) {
                        $label = substr($label, 0, -3) . ' ID';
                    }
                    $labels[$name] = $label;
                }
            }
        }

        return $labels;
    }

    /**
     * Generates search conditions
     * @return array
     */
    public function generateSearchConditions()
    {
        $columns = [];
        if (($table = $this->getTableSchema()) === false) {
            $class = $this->modelClass;
            /** @var \yii\base\Model $model */
            $model = new $class();
            foreach ($model->attributes() as $attribute) {
                $columns[$attribute] = 'unknown';
            }
        } else {
            foreach ($table->columns as $column) {
                $columns[$column->name] = $column->type;
            }
        }

        $likeConditions = [];
        $hashConditions = [];
        foreach ($columns as $column => $type) {
            switch ($type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_BOOLEAN:
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    $hashConditions[] = "'{$column}' => \$this->{$column},";
                    break;
                default:
                    $likeConditions[] = "->andFilterWhere(['like', '{$column}', \$this->{$column}])";
                    break;
            }
        }

        $conditions = [];
        if (!empty($hashConditions)) {
            $conditions[] = "\$query->andFilterWhere([\n"
                . str_repeat(' ', 12) . implode("\n" . str_repeat(' ', 12), $hashConditions)
                . "\n" . str_repeat(' ', 8) . "]);\n";
        }
        if (!empty($likeConditions)) {
            $conditions[] = "\$query" . implode("\n" . str_repeat(' ', 12), $likeConditions) . ";\n";
        }

        return $conditions;
    }

    /**
     * Generates URL parameters
     * @return string
     */
    public function generateUrlParams()
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pks = $class::primaryKey();
        if (count($pks) === 1) {
            if (is_subclass_of($class, 'yii\mongodb\ActiveRecord')) {
                return "'id' => (string)\$model->{$pks[0]}";
            } else {
                return "'id' => \$model->{$pks[0]}";
            }
        } else {
            $params = [];
            foreach ($pks as $pk) {
                if (is_subclass_of($class, 'yii\mongodb\ActiveRecord')) {
                    $params[] = "'$pk' => (string)\$model->$pk";
                } else {
                    $params[] = "'$pk' => \$model->$pk";
                }
            }

            return implode(', ', $params);
        }
    }

    /**
     * Generates action parameters
     * @return string
     */
    public function generateActionParams()
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pks = $class::primaryKey();
        if (count($pks) === 1) {
            return '$id';
        } else {
            return '$' . implode(', $', $pks);
        }
    }

    /**
     * Generates parameter tags for phpdoc
     * @return array parameter tags for phpdoc
     */
    public function generateActionParamComments()
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pks = $class::primaryKey();
        if (($table = $this->getTableSchema()) === false) {
            $params = [];
            foreach ($pks as $pk) {
                $params[] = '@param ' . (substr(strtolower($pk), -2) == 'id' ? 'integer' : 'string') . ' $' . $pk;
            }

            return $params;
        }
        if (count($pks) === 1) {
            return ['@param ' . $table->columns[$pks[0]]->phpType . ' $id'];
        } else {
            $params = [];
            foreach ($pks as $pk) {
                $params[] = '@param ' . $table->columns[$pk]->phpType . ' $' . $pk;
            }

            return $params;
        }
    }

    /**
     * Returns table schema for current model class or false if it is not an active record
     * @return boolean|\yii\db\TableSchema
     */
    public function getTableSchema()
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
            return $class::getTableSchema();
        } else {
            return false;
        }
    }

    /**
     * @return array model column names
     */
    public function getColumnNames()
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
            return $class::getTableSchema()->getColumnNames();
        } else {
            /** @var \yii\base\Model $model */
            $model = new $class();

            return $model->attributes();
        }
    }

    /**
     * If the especified column is a foreign key return the referencial information.
     * 
     * @param \yii\db\TableSchema $tableSchema represents the metadata of a database table.
     * @return array foreign key information related with especified column.
     * Each array element is of the following structure:
     *
     * ~~~
     * [
     *  'ForeignTableName',
     *  'fk1' => 'pk1',  // pk1 is in foreign table
     *  'fk2' => 'pk2',  // if composite foreign key
     * ]
     * ~~~
     */
    public function getForeignKey($tableSchema, $column)
    {
        $isForeignKey = false;
        $foreignKey = [];
        foreach ($tableSchema->foreignKeys as $refs) {
            $foreignKey = $refs;
            $refTable = $refs[0];
            unset($refs[0]);
            $fks = array_keys($refs);
            if (in_array($column->name, $fks)) {
                $isForeignKey = true;
                break;
            }
        }
        if (!$isForeignKey) {
            $foreignKey = [];
        }
        return $foreignKey;
    }

    /**
     * Generates code for active field
     * 
     * @param yii\db\ColumnSchema $column class describes the metadata of a column in a database table.
     * @param array $foreignKey array foreign key information related with especified column.
     * @param string $attribute column name
     * @return string that represents the dropdown active field.
     */
    public function generateForeignKeyField($column, $foreignKey, $attribute)
    {
        $prompt = '';
        if ($column->allowNull && $column->defaultValue == NULL) {
            $prompt = "'prompt' => 'None', ";
        }
        return "'$attribute' => ['type' => Form::INPUT_DROPDOWN_LIST, 'items' => " . $this->commonModelNamespace . "\\" .
            $foreignKey[0] . "::getKeyValuePairs(), 'options' => [" .
            $prompt . "'placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "'))]],";
    }
    
    public function generateForeignKeyColumn($column, $foreignKey, $attribute)
    {
        $prompt = '';
        if ($column->allowNull && $column->defaultValue == NULL) {
            $prompt = "'prompt' => 'None', ";
        }
        $displayOnly = '';
        if(in_array($column->name, $this->auditTrailFields)) {
            $displayOnly = "'displayOnly' => true,";
        }
        return "[ 'attribute' => '$attribute'," . $displayOnly ." 'type' => DetailView::INPUT_DROPDOWN_LIST, 'items' => " . $this->commonModelNamespace . "\\" .
            $foreignKey[0] . "::getKeyValuePairs(), 'options' => [" .
            $prompt . "'placeholder' => Yii::t('app', 'Enter {0}...', Yii::t('app', '" . $attribute . "'))]],";
    }

    /**
     * Generate a relation name for the specified table and a base name.
     * @param array $relations the relations being generated currently.
     * @param string $className the class name that will contain the relation declarations
     * @param \yii\db\TableSchema $table the table schema
     * @param string $key a base name that the relation name may be generated from
     * @param boolean $multiple whether this is a has-many relation
     * @return string the relation name
     */
    public function generateRelationName($relations, $className, $table, $key, $multiple)
    {
        if (strcasecmp(substr($key, -2), 'id') === 0 && strcasecmp($key, 'id')) {
            $key = rtrim(substr($key, 0, -2), '_');
        }
        if ($multiple) {
            $key = Inflector::pluralize($key);
        }
        $name = $rawName = Inflector::id2camel($key, '_');
        $i = 0;
        while (isset($table->columns[lcfirst($name)])) {
            $name = $rawName . ($i++);
        }
        while (isset($relations[$className][lcfirst($name)])) {
            $name = $rawName . ($i++);
        }

        return $name;
    }

}
