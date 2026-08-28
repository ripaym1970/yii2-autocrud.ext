<?php

use yii\helpers\Html;

/** @var $entity \yii\db\ActiveRecord|null */
/** @var $model \ripaym1970\autocrud\components\modules\HistoricalRecords\models\HistoricalRecord */

$attributes = [];

foreach ($model->details as $fieldName => $fieldValue) {
    if ($entity) {
        $value = is_null($fieldValue)
            ? null
            : $entity->getRepresentation($fieldName, $fieldValue);
    } else {
        $value = $fieldValue;
    }

    $name = $entity
        ? $entity->getAttributeLabel($fieldName)
        : $fieldName;

    if (is_array($fieldValue)) {
        $attributes[] = [
            'label' => $name,
            'format' => 'raw',
            'value' => Html::tag(
                'pre',
                print_r($value, true)
            ),
        ];
        continue;
    }
    $attributes[] = [
        'label' => $name,
        'value' => $value,
    ];
}

usort($attributes, function ($lhs, $rhs) {
    return $lhs['label'] <=> $rhs['label'];
});

echo \yii\widgets\DetailView::widget([
    'model' => $model,
    'options' => \ripaym1970\autocrud\components\Util::defaultDetailViewOptions(),
    'attributes' => $attributes,
]);
