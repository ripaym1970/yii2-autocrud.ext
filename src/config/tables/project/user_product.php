<?php

/**
 * Таблиця "Продукти користувача" `user_product`
 */

return [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Продукти користувача',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'user_id'    => ['type' => 'integer', 'required' => true, 'comment' => 'Користувач',],
        'product_id' => ['type' => 'integer', 'required' => true, 'comment' => 'Продукт',],
        'quantity'   => ['type' => 'integer', 'required' => true, 'comment' => 'Кількість',],
    ],
    'index' => [
        'user_id',
        'product_id',
    ],
    'gridColumns'    => [
        'id',
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
            'attribute' => 'product_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->product ?? null;
                if ($modelProperty) {
                    $out .=  $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
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
    'relations' => [
        'user' => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
        'product' => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
    ],
    //'fill'        => [
    //    [
    //        'user_id'    => 1,
    //        'product_id' => 1,
    //        'quantity'   => 5,
    //    ],
    //],
];
