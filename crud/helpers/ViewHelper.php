<?php

namespace appttitude\helpers\views;

use kartik\grid\GridView;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Helpers to generate common views or controls.
 *
 * @author jcvalerio
 */
class ViewHelper
{

    public static function generateSelect2Column($columnName, $relationName, $relatedClassName)
    {
        $relatedModel = '\common\models\\' . $relatedClassName;
        return [
            'attribute' => $columnName,
            'value' => $relationName . '.' . $relatedModel::getDisplayProperty(),
            'filterType' => GridView::FILTER_SELECT2,
            'filter' => ArrayHelper::map($relatedModel::find()->orderBy($relatedModel::getDisplayProperty())->asArray()->all(), $relatedModel::getPrimaryKeyProperty(), $relatedModel::getDisplayProperty()),
            'filterWidgetOptions' => [
                'pluginOptions' => ['allowClear' => true],
            ],
            'filterInputOptions' => ['placeholder' => Yii::t('app', 'Any {0}...', Yii::t('app', $relatedClassName))],
            'format' => 'raw'
        ];
    }

}
