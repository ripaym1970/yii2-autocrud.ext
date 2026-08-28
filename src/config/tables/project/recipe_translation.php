<?php

/**
 * Таблиця "" `recipe_translation`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Рецепти',
    'columns'        => [
        'recipe_id'     => ['type' => 'integer', 'comment' => 'Рецепт',],
        'language_id' => ['type' => 'string', 'size' => 2, 'default' => 'uk', 'comment' => 'Мова',],

        'name'        => [
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
            'comment'     => 'Приготування',
        ],
    ],
    'PRIMARY' => 'recipe_id, language_id', // Строка, только одна
    'relations' => [
        'recipe'     => [
            'table'     => 'recipe',
            'attribute' => 'recipe_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    //'fill' => [
    //    [
    //        'recipe_id'     => 1,
    //        'language_id' => 'uk',
    //        'name'        => 'Яйце куряче варене',
    //        'description' => '',
    //    ],
    //    [
    //        'recipe_id'     => 1,
    //        'language_id' => 'en',
    //        'name'        => 'Boiled chicken egg',
    //        'description' => '',
    //    ],
    //],
];
