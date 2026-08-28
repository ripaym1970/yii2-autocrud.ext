<?php

use ripaym1970\autocrud\components\modules\HistoricalRecords\models\HistoricalRecord;
use ripaym1970\autocrud\components\Yiit;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\view;

/**
 * @var HistoricalRecord $model
 * @var view             $this
 */

//\ripaym1970\autocrud\components\assets\vendor\Diff2html::register($this);

echo Html::tag('h5', Yiit::t('General data'));

echo \yii\widgets\DetailView::widget([
        'model' => $model,
        'options' => \ripaym1970\autocrud\components\Util::defaultDetailViewOptions(),
        'attributes' => [
            'id',
            'created_at:dateTime',
            'owner_class',
            'owner_id',
            'author_class',
            'author_id',
            [
                'label' => $model->getAttributeLabel('action_id'),
                'value' => ArrayHelper::getValue($model->actions, $model->action_id),
            ],
        ],
    ]
);

echo Html::tag('h5', Yiit::t('Details'));

if ($model->isMessage) {
    echo Html::tag(
        'h5',
        ArrayHelper::getValue($model->details, 'message'),
        [
            'class' => 'card card-body',
        ]
    );
    return;
}

$entity = class_exists($model->owner_class) ?
    new $model->owner_class
    : null;

$params = [
    'model' => $model,
    'entity' => $entity,
];

if ($model->isUpdate) {
    echo $this->render('viewUpdate', $params);
} elseif ($model->isCreate || $model->isRemove) {
    echo $this->render('viewCreateRemove', $params);
} else {
    throw new \yii\base\Exception('Can not render details');
}
