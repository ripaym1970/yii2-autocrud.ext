<?php

/**
 * @var \ripaym1970\autocrud\components\modules\Grid\models\GridProfile $model
 * @var \yii\web\View $this
 */

echo \ripaym1970\autocrud\components\widgets\Alert::widget();

$form = \ripaym1970\autocrud\components\Util::beginTypicalHorizontalForm();
$model->auto_refresh_time = $model->convertAutoRefreshTimeToString();
$general = [
    $form->errorSummary($model),
    $form->field($model, 'name')->textInput(),
    $form->field($model, 'notes')->textarea(),
    $form->field($model, 'auto_refresh_time')
        ->widget(
            \ripaym1970\autocrud\components\widgets\TimePicker::class,
            [
                'serverSideFormat' => "mm:ss",
                'clientSideFormat' => 'mm:ss'
            ]
        ),
    $form->field($model, 'saveFilters')->checkbox([
        'disabled' => 'disabled',
    ]),
    $form->field($model, 'has_custom_filter')->checkbox(),
    \yii\helpers\Html::tag(
        'div',
        '',
        [
            'class' => ['form-group', 'row', 'js-expand-all-groups-placeholder'],
        ]
    ),
    \yii\helpers\Html::tag(
        'div',
        '',
        [
            'class' => ['form-group', 'row', 'js-filter-row-placeholder'],
        ]
    ),
    \yii\helpers\Html::tag(
        'ul',
        '',
        ['class' => 'js-fields']
    ),
];

$general = implode('', $general);

$data = \ripaym1970\autocrud\components\modules\Grid\models\GridProfile::getSharesData(
    \yii\helpers\ArrayHelper::getValue(
        $_REQUEST,
        'shareIds',
        []
    )
        ?: \yii\helpers\ArrayHelper::getColumn(
        $model->shares,
        'parent_id'
    )
);

if (!$data) {
    echo $general;
    $form::end();
    return;
}

$tabs = [
    [
        'label' => Yii::t('app', 'General'),
        'content' => $general,
    ],
    [
        'label' => Yii::t('app', 'Sharing'),
        'content' => \ripaym1970\autocrud\components\widgets\FormGroup::widget([
            'labelOptions'   => [
                'class' => ['col-sm-3'],
            ],
            'label'          => Yii::t('app', "Sharing"),
            'content'        => \ripaym1970\autocrud\components\Dropdown\Widget::widget([
                'multiSelect'   => true,
                'isDropDown'    => false,
                'checkChildren' => true,
                'name'          => 'shareIds[]',
                'data'          => $data,
            ]),
            'contentOptions' => [
                'class' => ['col-sm-9'],
            ],
        ]),
    ]
];

echo \yii\bootstrap4\Tabs::widget([
    'items' => $tabs,
]);

$form::end();

$this->registerJs('
(function() {
    var checkbox = $("#gridprofile-has_custom_filter");
    checkbox.on("change", function() {
        var checked = $(this).is(":checked");
        $(".js-filter-row-placeholder").toggle(!checked);
    });
    checkbox.trigger("change");
})()');
