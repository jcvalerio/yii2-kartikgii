<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * @var yii\web\View $this
 * @var yii\gii\generators\crud\Generator $generator
 */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->modelClass, '\\') ?> $model
 */

$this->title = $model-><?= $generator->getNameAttribute() ?>;
$this->params['breadcrumbs'][] = ['label' => <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-view">
    <div class="page-header">
        <h1><?= "<?= " ?>Html::encode($this->title) ?></h1>
    </div>


    <?= "<?= " ?>DetailView::widget([
            'model' => $model,
            'condensed'=>false,
            'hover'=>true,
            'mode'=>Yii::$app->request->get('edit')=='t' ? DetailView::MODE_EDIT : DetailView::MODE_VIEW,
            'panel'=>[
            'heading'=>$this->title,
            'type'=>DetailView::TYPE_INFO,
        ],
        'attributes' => [
<?php
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        echo "            '" . $name . "',\n";
    }
} else {
    foreach ($generator->getTableSchema()->columns as $column) {
        if($column->isPrimaryKey) {
            continue;
        }
        $displayOnly = '';
        if(in_array($column->name, $generator->auditTrailFields)) {
            $displayOnly = "'displayOnly' => true,";
        }
        $foreignKey = $generator->getForeignKey($tableSchema, $column);
        if (!empty($foreignKey)) {
            echo "            " . $generator->generateForeignKeyColumn($column, $foreignKey, $column->name) . "\n";
            
        } else {
            $format = $generator->generateColumnFormat($column);

            if($column->type === 'date'){
                echo "            [
                    'attribute' => '$column->name',
                    $displayOnly
                    'format' => ['date',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['date'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['date'] : 'd-m-Y'],
                    'type' => DetailView::INPUT_WIDGET,
                    'widgetOptions' => [
                        'class' => DateControl::classname(),
                        'type' => DateControl::FORMAT_DATE
                    ]
                ],\n";

            }elseif($column->type === 'time'){
                echo "            [
                    'attribute' => '$column->name',
                    $displayOnly
                    'format' => ['time',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['time'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['time'] : 'H:i:s A'],
                    'type' => DetailView::INPUT_WIDGET,
                    'widgetOptions' => [
                        'class' => DateControl::classname(),
                        'type' => DateControl::FORMAT_TIME
                    ]
                ],\n";

            }elseif($column->type === 'datetime' || $column->type === 'timestamp'){
                echo "            [
                    'attribute' => '$column->name',
                    $displayOnly
                    'format' => ['datetime',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A'],
                    'type' => DetailView::INPUT_WIDGET,
                    'widgetOptions' => [
                        'class' => DateControl::classname(),
                        'type' => DateControl::FORMAT_DATETIME
                    ]
                ],\n";

            }elseif($column->phpType === 'boolean'){
                echo "            [
                    'attribute' => '$column->name',
                    $displayOnly
                    'format' => 'raw',
                    'value' => \$model->$column->name ? 
                        '<span class=\"label label-success\">' . Yii::t('app', 'Yes') . '</span>' : 
                        '<span class=\"label label-danger\">' . Yii::t('app', 'No') . '</span>',
                    'type'=>DetailView::INPUT_SWITCH
                ],\n";

            } else{
                echo "            ['attribute' => '" . $column->name ."'". ($format === 'text' ? "" : ", 'format' => '" . $format . "'") . ", " . $displayOnly . "],\n";
            }
        }
    }
}
?>
        ],
        'deleteOptions'=>[
        'url'=>['delete', 'id' => $model-><?=$generator->getTableSchema()->primaryKey[0]?>],
        'data'=>[
        'confirm'=>Yii::t('app', 'Are you sure you want to delete this item?'),
        'method'=>'post',
        ],
        ],
        'enableEditMode'=>true,
    ]) ?>

</div>
