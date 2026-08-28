<?php

/**
 * Таблиця "Страны" `country`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Країни',
    'columns'        => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'code'   => ['type' => 'string', 'size' => 2, 'unique' => true, 'comment' => 'Код ISO'],
        'slug'   => ['type' => 'string', 'unique' => true, 'comment' => 'ЧПУ'],
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
        'code',
        'slug',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields'     => [
        [['name',   'type' => 'text',],],
        [['code',   'type' => 'text',],],
        [['slug',   'type' => 'text',],],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations'      => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'country_translation',
            'attribute' => 'country_id',
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
                'name' => null, // TODO: Уточнить поля
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
            'id'      => 1,
            'code'    => 'UK',
            'slug'    => 'ukraine',
            'active'  => 1,
        ],
    ],
];
