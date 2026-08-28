<?php

/**
 * Таблиця "Витрачені продукти" `product_iss`
 */
//issuance of products

return [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Витрачені продукти',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'user_id'    => ['type' => 'integer', 'comment' => 'Користувач'],
        'product_id' => ['type' => 'integer', 'comment' => 'Продукт',],
        'quantity'   => ['type' => 'integer', 'required' => true, 'comment' => 'Кількість',],

        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'user_id',
        'product_id',
        'created_at',
    ],

    'gridColumns'    => [
        'id',
        [
            'attribute' => 'user_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                return $model->user->name;
            },
            'filter' => 'distinct',
            'filter_class_name' => 'user',
        ],
        [
            'attribute' => 'product_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->product->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }

                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'product',
        ],
        [
            'attribute' => 'quantity',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = $model->quantity;
                $modelProperty = $model->product->unit ?? null;
                if ($modelProperty) {
                    $out .= ' ' . $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields'     => [
        [
            [
                'user_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'user',
                'table' => 'user',
            ],
        ],
        [
            [
                'product_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'product',
                'table' => 'product',
            ],
        ],
        [['quantity', 'type' => 'text',],],
        [
            [
                'unit_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'unit',
                'table' => 'unit',
            ],
        ],
    ],
    'relations'      => [
        'user' => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
        'product' => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
        'unit' => [
            'table'     => 'unit',
            'attribute' => 'unit_id',
        ],
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
    ],
    'fill' => [
        [
            'id'         => 1,
            'user_id'    => 1,
            'product_id' => 1,
            'quantity'   => 5,
        ],
    ],
];
