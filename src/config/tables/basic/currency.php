<?php

/**
 * Таблиця "Валюти" `currency`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'     => 'Валюти',
    'icon_menu' => 'currency',
    'columns'   => [
        'id' => ['type' => 'string',  'size' => 3, 'comment' => 'ID'],

        'code'    => ['type' => 'string', 'size' => 1, 'unique' => true, 'comment' => 'Символ'],
        'slug'    => ['type' => 'string', 'size' => 3, 'unique' => true, 'comment' => 'ЧПУ'],
        'default' => ['type' => 'boolean', 'default' => 0, 'comment' => 'Дефолтна',],
        'active'  => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],
    ],

    'defaultOrder'  => ['id' => SORT_ASC],
    'gridColumns'    => [
        'id',
        'name',
        'code',
        'slug',
        'default:boolean',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields'     => [
        [['name', 'type' => 'text',],],
        [['code', 'type' => 'text',],],
        [['slug', 'type' => 'text',],],
        [['default', 'type' => 'checkbox',],],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations'      => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'currency_translation',
            'attribute' => 'currency_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
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
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name) ;
            },
        ],
    ],
    'fill'        => [
        [
            'id'      => 'USD',
            'code'    => '$',
            'slug'    => 'usd',
            'default' => 0,
            'active'  => 1,
        ],
        [
            'id'      => 'EUR',
            'code'    => '€',
            'slug'    => 'eur',
            'default' => 0,
            'active'  => 0,
        ],
        [
            'id'      => 'RUB',
            'code'    => '₽',
            'slug'    => 'rub',
            'default' => 0,
            'active'  => 0,
        ],
        [
            'id'      => 'UAH',
            'code'    => '₴',
            'slug'    => 'uah',
            'default' => 1,
            'active'  => 1,
        ],
    ],
];
