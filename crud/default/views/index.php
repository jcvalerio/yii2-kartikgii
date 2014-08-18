<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;


/**
 * @var yii\web\View $this
 * @var yii\gii\generators\crud\Generator $generator
 */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";


?>


use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use <?= $generator->indexWidgetType === 'grid' ? "kartik\\grid\\GridView" : "yii\\widgets\\ListView" ?>;
use yii\widgets\Pjax;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
<?= !empty($generator->searchModelClass) ? " * @var " . ltrim($generator->searchModelClass, '\\') . " \$searchModel\n" : '' ?>
 */

$this->title = <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">
    <div class="page-header">
            <h1><?= "<?= " ?>Html::encode($this->title) ?></h1>
    </div>
<?php if(!empty($generator->searchModelClass)): ?>
<?= "    <?php " . ($generator->indexWidgetType === 'grid' ? "// " : "") ?>echo $this->render('_search', ['model' => $searchModel]); ?>
<?php endif; ?>

    <p>
        <?= "<?php /* echo " ?>Html::a(<?= $generator->generateString('Create {modelClass}', ['modelClass' => Inflector::camel2words(StringHelper::basename($generator->modelClass))]) ?>, ['create'], ['class' => 'btn btn-success'])<?= "*/ " ?> ?>
    </p>

<?php if ($generator->indexWidgetType === 'grid'): ?>
    <?= "<?php Pjax::begin(); echo " ?>GridView::widget([
        'dataProvider' => $dataProvider,
        <?= !empty($generator->searchModelClass) ? "'filterModel' => \$searchModel,\n        'columns' => [\n" : "'columns' => [\n"; ?>
            ['class' => 'yii\grid\SerialColumn'],

<?php
$count = 0;
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        if (++$count < 6) {
            echo "            '" . $name . "',\n";
        } else {
            echo "            // '" . $name . "',\n";
        }
    }
} else {
    foreach ($tableSchema->columns as $column) {
        if(!$generator->isValidField($column)) {
            continue;
        }
        $foreignKey = $generator->getForeignKey($tableSchema, $column);
        if (!empty($foreignKey)) {
            $relations = [];
            $modelClass = StringHelper::basename($generator->modelClass);
            $className = Inflector::camel2words($modelClass);
            $relatedClassName = $foreignKey[0];
            unset($foreignKey[0]);
            $fks = array_keys($foreignKey);
            $relationName = $generator->generateRelationName($relations, $className, $tableSchema, $fks[0], false);
            //$columnDisplay = "            ['attribute' => '" . lcfirst($relationName) . ".Name', 'label' => Yii::t('app', '" . $column->name . "') ],";
            $columnDisplay = "            \\appttitude\\helpers\\views\\ViewHelper::generateSelect2Column('$column->name', '" . lcfirst($relationName) . "', '$relatedClassName'),";
            /*
            $columnDisplay = "
            [
                'attribute' => '$column->name',
                'value' => function (\$model, \$key, \$index, \$widget) {
                    return !isset(\$model->" . lcfirst($relationName) . ") ? '' : Html::a(\$model->" . lcfirst($relationName) . "->Name, Url::toRoute(['" . lcfirst($relatedClassName) . "/update/', 'id' => \$model->" . $column->name . "]), [
                            'title' => Yii::t('app', 'Navigate to {0}...', Yii::t('app', '$relatedClassName')),
                    ]);
                },
                'filterType' => GridView::FILTER_SELECT2,
                'filter' => ArrayHelper::map(\common\models\\$relatedClassName::find()->orderBy('Name')->asArray()->all(), '" . $relatedClassName . "Id', 'Name'),
                'filterWidgetOptions' => [
                    'pluginOptions' => ['allowClear' => true],
                ],
                'filterInputOptions' => ['placeholder' => Yii::t('app', 'Any {0}...', Yii::t('app', '$relatedClassName'))],
                'format' => 'raw'
            ],";
             * 
             */
        } else {
            $format = $generator->generateColumnFormat($column);
            if($column->type === 'date'){
                $columnDisplay = "            ['attribute' => '$column->name','format' => ['date', (isset(Yii::\$app->modules['datecontrol']['displaySettings']['date'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['date'] : 'd-m-Y']],";

            }elseif($column->type === 'time'){
                $columnDisplay = "            ['attribute' => '$column->name','format' => ['time', (isset(Yii::\$app->modules['datecontrol']['displaySettings']['time'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['time'] : 'H:i:s A']],";
            }elseif($column->type === 'datetime' || $column->type === 'timestamp'){
                $columnDisplay = "            ['attribute' => '$column->name','format' => ['datetime', (isset(Yii::\$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A']],";
            }else{
                $columnDisplay = "            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',";
            }
        }
        if (++$count < 6) {
            echo $columnDisplay ."\n";
        } else {
            echo "/*" . $columnDisplay . " */\n";
        }
    }
}
?>

            [
                'class' => 'yii\grid\ActionColumn',
            ],
        ],
        'responsive' => true,
        'hover' => true,
        'condensed' => true,
        'floatHeader' => true,
        'panel' => [
            'heading' => '<h3 class="panel-title"><i class="glyphicon glyphicon-th-list"></i> ' . Html::encode($this->title) . ' </h3>',
            'type' => 'info',
            'before' => Html::a('<i class="glyphicon glyphicon-plus"></i> Add', ['create'], ['class' => 'btn btn-success']),
            'after' => Html::a('<i class="glyphicon glyphicon-repeat"></i> Reset List', ['index'], ['class' => 'btn btn-info']),
            'showFooter' => false
        ],
    ]); Pjax::end(); ?>
<?php else: ?>
    <?= "<?= " ?>ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['class' => 'item'],
        'itemView' => function ($model, $key, $index, $widget) {
            return Html::a(Html::encode($model-><?= $nameAttribute ?>), ['view', <?= $urlParams ?>]);
        },
    ]) ?>
<?php endif; ?>

</div>
