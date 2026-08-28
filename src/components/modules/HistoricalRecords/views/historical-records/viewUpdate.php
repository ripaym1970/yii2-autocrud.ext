<?php

use ripaym1970\autocrud\components\Yiit;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var \yii\db\ActiveRecord|null $entity */
/** @var \ripaym1970\autocrud\components\modules\HistoricalRecords\models\HistoricalRecord $model */

$keys = array_unique(
    ArrayHelper::merge(
        array_keys(
            ArrayHelper::getValue(
                $model->details,
                'oldAttributes',
                []
            )
        ),
        array_keys(
            ArrayHelper::getValue(
                $model->details,
                'newAttributes',
                []
            )
        )
    )
);

$formatAttributes = function ($attributes) {
    return is_array($attributes)
        ? Html::tag(
            'pre',
            print_r(
                $attributes,
                true
            )
        )
        : $attributes;
};

$rawData = [];
$diffs = [];


foreach ($keys as $index => $key) {
    $attributes = [];
    foreach (['oldAttributes', 'newAttributes'] as $attribute) {
        $value = ArrayHelper::getValue(
            $model->details,
            $attribute . '.' . $key
        );

        if ($entity) {
            $attributes[] = is_null($value)
                ? null
                : $entity->getRepresentation($key, $value);
        } else {
            $attributes[] = $value;
        }
    }


    $oldAttributes = $formatAttributes($attributes[0]);
    $newAttributes = $formatAttributes($attributes[1]);

    $rawData[] = [
        'id' => $index,
        'key' => $entity ? $entity->getAttributeLabel($key) : $key,
        'oldAttributes' => $oldAttributes,
        'newAttributes' => $newAttributes,
    ];

    $builder = new \SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder([
        'collapseRanges' => true,
        'commonLineThreshold' => 6,
        'contextLines' => 3,
        'fromFile' => $key,
        'fromFileDate' => Yii::$app->formatter->asDatetime($model->created_at),
        'toFile' => $key,
        'toFileDate' => Yii::$app->formatter->asDatetime($model->created_at),
    ]);

    $differ = new \SebastianBergmann\Diff\Differ($builder);

    $diffs[] = $differ->diff($oldAttributes, $newAttributes);
}

$dataProvider = new yii\data\ArrayDataProvider([
    'allModels' => $rawData,
]);

$table = \yii\grid\GridView::widget([
    'dataProvider' => $dataProvider,
    'headerRowOptions' => \ripaym1970\autocrud\components\Util::defaultHeaderOptions(),
    'tableOptions' => \ripaym1970\autocrud\components\Util::defaultDetailViewOptions(),
    'layout' => '{items}',
    'columns' => [
        [
            'attribute' => 'key',
            'format' => 'text',
            'label' => Yiit::t('Property name'),
        ],
        [
            'format' => 'raw',
            'label' => Yiit::class::t('Old Value'),
            'attribute' => 'oldAttributes',
        ],
        [
            'format' => 'raw',
            'label' => Yiit::t('New Value'),
            'attribute' => 'newAttributes',
        ],
    ]
]);

echo \yii\bootstrap5\Tabs::widget([
    'items' => [
        [
            'label' => Yiit::t('Grid'),
            'content' => $table,
        ],
        [
            'label' => Yiit::t('Diff'),
            'content' => Html::tag(
                'div',
                implode('', $diffs),
                ['id' => 'js-diff']
            )
        ]
    ],
]);


$js = '
    var diffHtml = Diff2Html.getPrettyHtml(
        $("#js-diff").html(),
        {
            inputFormat: "diff",
            showFiles: false,
            matching: "lines",
            outputFormat: "side-by-side"
        }
    );
    $("#js-diff").html(diffHtml);
';
$this->registerJs($js);
