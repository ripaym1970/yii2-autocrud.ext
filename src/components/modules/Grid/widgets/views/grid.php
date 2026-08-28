<?php

/**
 * @var \yii\web\View $this
 * @var \ripaym1970\autocrud\components\modules\Grid\widgets\Grid $widget
 */

\ripaym1970\autocrud\components\modules\Grid\assets\Asset::register($this);

echo \yii\helpers\Html::tag(
    $widget->element,
    '',
    \yii\helpers\ArrayHelper::merge(
        $widget->htmlAttributes,
        [
            'data-params' => $widget->settings,
            'data-profiles' => $widget->profiles,
        ]
    )
);

$class = $widget->representation == \ripaym1970\autocrud\components\modules\Grid\Helper::GRID_REPRESENTATION
    ? 'Grid'
    : "Tree";

$js = "new application.Views." . $class . "({el: '#" . $widget->htmlAttributes['id'] . "'});";
$this->registerJs($js);
