<?php

/**
 * Таблиця "Міста" `city`
 */

return [
    'title' => 'Міста',
    'columns' => [
        'id' => ['type' => 'integer', 'comment' => 'ID',],

        'slug'       => ['type' => 'string', 'unique' => true, 'comment' => 'ЧПУ',],

        'country_id' => ['type' => 'integer', 'comment' => 'Країна',],

        'active'     => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно',],
    ],
    'index' => [
        'country_id',
        'active',
    ],

    'gridColumns' => [
        'id',
        [
            'attribute' => 'country_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelCountry = $model->country ?? null;
                if ($modelCountry) {
                    $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                    $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                    foreach ($modelCountry->translations as $translation) {
                        $translationLanguageId = $translation->language_id;
                        if (in_array($translationLanguageId, $languageIds)) {
                            $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                        }
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'country',
        ],
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
        'slug',
        'active:boolean',
        //'center_latitude',
        //'center_longitude',
    ],
    'viewAttributes' => [
        'gridColumns',
        //'center_latitude',
        //'center_longitude',
    ],
    'formFields' => [
        [
            [
                'country_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'country',
                'table' => 'country',
            ],
        ],
        [['name', 'type' => 'text',],],
        [['genitive1', 'type' => 'text',],],
        [['genitive2', 'type' => 'text',],],
        [['slug', 'type' => 'text',],],
        [['active', 'type' => 'checkbox', 'width' => 3,],],
        //[['center_latitude', 'type' => 'text',],],
        //[['center_longitude', 'type' => 'text',],],
    ],
    'relations' => [
        'country' => [
            'table'     => 'country',
            'attribute' => 'country_id',
        ],
        'translations' => [
            'multiple'  => true,
            'table'     => 'city_translation',
            'attribute' => 'city_id',
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
                'name'      => null, // TODO: Уточнить поля
                'genitive1' => null, // TODO: Уточнить поля
                'genitive2' => null, // TODO: Уточнить поля
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name); // TODO: Уточнить поля
            },
        ],
    ],
    'fill'        => [
        [
            'id'         => 1,
            'country_id' => 1,
            'slug'       => 'kyiv',
            'active'     => 1,
        ],
    ],
];
