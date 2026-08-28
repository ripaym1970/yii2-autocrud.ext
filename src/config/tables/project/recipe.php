<?php

/**
 * Таблиця "Страны" `recipe`
 */

return [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Рецепти',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'user_id'   => ['type' => 'integer', 'comment' => 'Користувач'],
        'serves'    => ['type' => 'integer', 'comment' => 'Кількість порцій',],
        'prepare_at'   => ['type' => 'integer', 'comment' => 'Час підготовки, хв.',],
        'cooking_at'   => ['type' => 'integer', 'comment' => 'Час приготування, хв.',],
        'calorific' => ['type' => 'integer', 'comment' => 'Калорійність, ккал/100г',],
        'glycemic'  => ['type' => 'integer', 'comment' => 'Глікемічний індекс, ХО',],
        'active'    => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],

        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'user_id',
        'active',
    ],

    'gridColumns'    => [
        'id',
        'user_id',
        [
            'attribute' => 'user_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->user ?? null;
                if ($modelProperty) {
                    $out .=  $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'user',
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
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
        'prepare_at',
        'cooking_at',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
        'description:raw',
    ],
    'formFields'     => [
        [
            [
                'user_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'user',
                'table' => 'user',
                'name'  => 'name',
            ],
        ],
        [['name', 'type' => 'text',],],
        [
            [
                'description',
                'label' => 'Опис',
                'type'  => 'editor',
            ],
        ],
        [['prepare_at', 'type' => 'text',],],
        [['cooking_at', 'type' => 'text',],],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations' => [
        'user' => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
        'translations'       => [
            'multiple'  => true,
            'table'     => 'recipe_translation',
            'attribute' => 'recipe_id',
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
                'name' => null,
                'description' => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name);
            },
        ],
    ],
    // 'fill'        => [
    //     [
    //         'id'         => 1,
    //         'prepare_at' => 5,
    //         'cooking_at' => 10,
    //         'active'     => 1,
    //     ],
    // ],
];
