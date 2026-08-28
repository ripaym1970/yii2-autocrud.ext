<?php

/**
 * Таблиця "Продукти- список" `product`
 */

return [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Продукти - список',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'product_category_id' => ['type' => 'integer', 'comment' => 'Категорія'],
        'unit_id'             => ['type' => 'integer', 'comment' => 'Розмірність'],
        'active'              => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],

        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'product_category_id',
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
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
        [
            'attribute' => 'product_category_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->product_category ?? null;
                if ($modelProperty) {
                    $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                    $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                    foreach ($modelProperty->translations as $translation) {
                        $translationLanguageId = $translation->language_id;
                        if (in_array($translationLanguageId, $languageIds)) {
                            $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                        }
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'unit',
        ],
        [
            'attribute' => 'unit_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->unit ?? null;
                if ($modelProperty) {
                    $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                    $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                    foreach ($modelProperty->translations as $translation) {
                        $translationLanguageId = $translation->language_id;
                        if (in_array($translationLanguageId, $languageIds)) {
                            $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                        }
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'unit',
        ],
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
        [
            [
                'unit_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'unit',
                'table' => 'unit',
            ],
        ],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations'      => [
        'product_category'     => [
            'table'     => 'product_category',
            'attribute' => 'product_category_id',
        ],
        'unit' => [
            'table'     => 'unit',
            'attribute' => 'unit_id',
        ],
        'translations'       => [
            'multiple'  => true,
            'table'     => 'product_translation',
            'attribute' => 'product_id',
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
    //        'id'       => 1,
    //        'unit_id'  => 1,
    //        'active'   => 1,
    //    ],
    //    [
    //        'id'       => 2,
    //        'unit_id'  => 1,
    //        'active'   => 1,
    //    ],
    //],
];
