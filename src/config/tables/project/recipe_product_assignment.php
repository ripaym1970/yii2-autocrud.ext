<?php

/**
 * Таблиця "" `recipe_product_assignment`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'     => '',
    'columns'   => [
        'recipe_id'  => ['type' => 'integer', 'required' => true, 'comment' => 'Рецепт',],
        'product_id' => ['type' => 'integer', 'required' => true, 'comment' => 'Продукт',],
        'quantity'   => ['type' => 'integer', 'required' => true, 'comment' => 'Кількість',],
    ],
    'PRIMARY'   => 'recipe_id, product_id',
    'index' => [
        'quantity',
    ],
    'relations' => [
        'recipe' => [
            'table'     => 'recipe',
            'attribute' => 'recipe_id',
        ],
        'product' => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
    ],
    'fill'        => [
        [
            'recipe_id'    => 1,
            'product_id' => 1,
            'quantity'   => 1,
        ],
    ],
];
