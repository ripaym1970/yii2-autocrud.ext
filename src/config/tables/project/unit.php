<?php

/**
 * Таблиця "Oдиниці виміру продуктів" `unit`
 */

return [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Oдиниці виміру',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'active' => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],
    ],
    'index' => [
        'active',
    ],

    'gridColumns'    => [
        'id',
        [
            'attribute' => 'name',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
        'name',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
        [
            'attribute' => 'description',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->description . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
    ],
    'formFields'     => [
        [['name', 'type' => 'text',],],
        [['description', 'type' => 'editor',],],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations'      => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'unit_translation',
            'attribute' => 'unit_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'name'        => null,
                'description' => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name);
            },
        ],
    ],
    //'fill'        => [
    //    [
    //        'id'      => 1,
    //        'active'  => 1,
    //    ],
    //    [
    //        'id'      => 2,
    //        'active'  => 1,
    //    ],
    //],
];
