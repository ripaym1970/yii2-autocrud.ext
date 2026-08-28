<?php

/**
 * @var \yii\web\View $this
 * @var \ripaym1970\autocrud\components\modules\Grid\models\ProfilesManagementForm $model
 */

$data = [];
foreach ($model->shares as $share) {
    $element = [
        'id' => -$share['id'] // negative to avoid duplication with IDs items
            // replace id = 0 ("All users") with a constant, because if 0, the item cannot be selected
            ?: \ripaym1970\autocrud\components\modules\Grid\models\ProfilesManagementForm::SELECT_ALL,
        'label' => $share['name'],
        'expanded' => true,
        'selectable' => true,
        'items' => [],
    ];
    foreach ($share['identities'] as $identity) {
        $element['items'][] = [
            'id' => $identity['id'],
            'label' => $identity['name'],
            'checked' => in_array(
                $identity['id'],
                $model->assigneeIds
            ),
        ];
    }
    $data[] = $element;
}

if (!$data) {
    \Yii::$app->session->addFlash(
        'info',
        \Yii::t(
            "app",
            "Sorry, but selected Profile is not shared with any object"
        )
    );

    echo \ripaym1970\autocrud\components\widgets\Alert::widget();
    return;
}


$form = \ripaym1970\autocrud\components\Util::beginTypicalForm();

echo $form->field($model, 'assigneeIds')->widget(
    \ripaym1970\autocrud\components\Dropdown\Widget::class,
    [
        'name' => 'assigneeIds[]',
        'data' => $data,
        'multiSelect' => true,
        'checkChildren' => true,
    ]
);

echo \yii\helpers\Html::beginTag('div', ['class'=> 'row']);
echo \yii\helpers\Html::beginTag('div', ['class'=> 'col text-center']);

echo \yii\bootstrap4\Button::widget([
    'label' => \Yii::t('app', "Set Profile as Default for Selected Entities"),
    'options' => [
        'class' => [
            'btn',
            'btn-success',
            'btn-md',
        ]
    ]
]);

echo \yii\helpers\Html::endTag('div'); //col
echo \yii\helpers\Html::endTag('div'); //row

$form::end();
