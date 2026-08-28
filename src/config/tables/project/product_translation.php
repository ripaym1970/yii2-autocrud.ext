<?php

/**
 * Таблиця "" `product_translation`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Продукти',
    'columns'        => [
        'product_id'  => ['type' => 'integer', 'comment' => 'Назва',],
        'language_id' => ['type' => 'string', 'size' => 2, 'default' => 'uk', 'comment' => 'Мова',],

        'name' => [
            'type'        => 'string',
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Назва',
        ],
        'description' => [
            'type'        => 'text',
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Опис',
        ],
    ],
    'PRIMARY' => 'product_id, language_id', // Строка, только одна
    'relations' => [
        'product'     => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    //'fill' => [
    //    [
    //        'product_id'  => 1,
    //        'language_id' => 'uk',
    //        'name'        => 'Куряче яйце',
    //    ],
    //    [
    //        'product_id'  => 1,
    //        'language_id' => 'en',
    //        'name'        => 'Chicken egg',
    //    ],
    //    [
    //        'product_id'  => 2,
    //        'language_id' => 'uk',
    //        'name'        => 'Страусове яйце',
    //    ],
    //    [
    //        'product_id'  => 2,
    //        'language_id' => 'en',
    //        'name'        => 'Ostrich egg',
    //    ],
    //],
];
