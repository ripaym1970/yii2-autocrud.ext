<?php

/**
 * Таблиця "" `unit_translation`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Oдиниці виміру',
    'columns'        => [
        'unit_id'     => ['type' => 'integer', 'comment' => 'Oдиниця виміру',],
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
    'PRIMARY' => 'unit_id, language_id', // Строка, только одна
    'relations' => [
        'unit'     => [
            'table'     => 'unit',
            'attribute' => 'unit_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    //'fill' => [
    //    [
    //        'unit_id'  => 1,
    //        'language_id' => 'uk',
    //        'name'        => 'шт',
    //    ],
    //    [
    //        'unit_id'  => 1,
    //        'language_id' => 'en',
    //        'name'        => 'piece',
    //    ],
    //    [
    //        'unit_id'  => 2,
    //        'language_id' => 'uk',
    //        'name'        => 'кг',
    //    ],
    //    [
    //        'unit_id'  => 2,
    //        'language_id' => 'en',
    //        'name'        => 'kg',
    //    ],
    //],
];
