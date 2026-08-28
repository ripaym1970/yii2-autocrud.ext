<?php

/**
 * Таблиця "Категорії продуктів" `product_category_translation`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Категорії продуктів',
    'columns'        => [
        'product_category_id'  => ['type' => 'integer', 'comment' => 'Назва',],
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
    'PRIMARY' => 'product_category_id, language_id', // Строка, только одна
    'relations' => [
        'product_category'     => [
            'table'     => 'product_category',
            'attribute' => 'product_category_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    'fill' => [
        [
            'product_category_id' => 1,
            'language_id'         => 'uk',
            'name'                => 'Сахар',
        ],
        [
            'product_category_id' => 1,
            'language_id'         => 'en',
            'name'                => 'Sugar',
        ],
        [
            'product_category_id' => 2,
            'language_id'         => 'uk',
            'name'                => 'Сіль',
        ],
        [
            'product_category_id' => 2,
            'language_id'         => 'en',
            'name'                => 'Salt',
        ],
        [
            'product_category_id' => 3,
            'language_id'         => 'uk',
            'name'                => 'Крупи',
        ],
        [
            'product_category_id' => 3,
            'language_id'         => 'en',
            'name'                => 'Groats',
        ],
        [
            'product_category_id' => 4,
            'language_id'         => 'uk',
            'name'                => 'Мука',
        ],
        [
            'product_category_id' => 4,
            'language_id'         => 'en',
            'name'                => 'Flour',
        ],
    ],
];

/**
 * Можна виділити кілька основних груп харчових продуктів:
 * М'ясо та м'ясопродукти;
 * Риба та рибопродукти;
 * Яйця;
 * Молоко та молочні продукти;
 * Хліб та хлібобулочні вироби;
 * Крупи, макаронні вироби;
 * Бобові;
 * Овочі;
 * Фрукти та ягоди;
 * Горіхи;
 * Гриби;
 * Кондитерські вироби;
 * Харчові жири;
 * Напої, чай, кава.
 */
