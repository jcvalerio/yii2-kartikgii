<?php
/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var yii\gii\generators\crud\Generator $generator
 */

echo $form->field($generator, 'generateAllViews')->checkbox();
echo $form->field($generator, 'generateController')->checkbox();
echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'searchModelClass');
echo $form->field($generator, 'controllerClass');
echo $form->field($generator, 'customBaseControllerClass');
echo $form->field($generator, 'baseControllerClass');
echo $form->field($generator, 'moduleID');
echo $form->field($generator, 'indexWidgetType')->dropDownList([
    'grid' => 'GridView',
    'list' => 'ListView',
]);
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');
echo $form->field($generator, 'columns');
echo $form->field($generator, 'commonModelNamespace');
