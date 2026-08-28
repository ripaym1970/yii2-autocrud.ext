<?php

/**
 * Таблиця "Категорії продуктів" `product_category_translation`
 */

return [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Категорії продуктів',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'parent_id' => ['type' => 'integer', 'comment' => 'Parent'],
        'active'    => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],

        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'parent_id',
        'active',
    ],
    'gridColumns'    => [
        'id',
        'parent_id',
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
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
    ],
    'viewAttributes' => [
        'gridColumns',
        'description:raw',
    ],
    'formFields'     => [
        [['name', 'type' => 'text',],],
        [
            [
                'description',
                'label' => 'Опис',
                'type'  => 'editor',
            ],
        ],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations' => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'product_category_translation',
            'attribute' => 'product_category_id',
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
    'fill' => [
        [
            'id'     => 1,
            'active' => 1,
        ],
        [
            'id'     => 2,
            'active' => 1,
        ],
        [
            'id'     => 3,
            'active' => 1,
        ],
        [
            'id'     => 4,
            'active' => 1,
        ],
    ],
];
