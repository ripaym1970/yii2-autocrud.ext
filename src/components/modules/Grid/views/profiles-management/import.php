<?php


/**
 * @var \ripaym1970\autocrud\components\modules\Grid\models\ImportForm $model
 * @var \yii\web\view $this
 */

$form = \ripaym1970\autocrud\components\Util::beginTypicalHorizontalForm([
    'encodeErrorSummary' => false,
]);

echo $form->errorSummary($model);

echo $form->field($model, 'file')->fileInput();

echo $form->field($model, 'shareIds')->widget(
    \ripaym1970\autocrud\components\Dropdown\Widget::class,
    [
        'multiSelect' => true,
        'isDropDown' => false,
        'checkChildren' => true,
        'name' => 'shareIds[]',
        'data' => \ripaym1970\autocrud\components\modules\Grid\models\GridProfile::getSharesData(
            $model->shareIds
        )
    ]
);

$form::end();
