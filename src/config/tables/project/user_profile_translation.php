<?php

/**
 * Таблиця "Варіації для user_profile" `user_profile_translation`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title' => 'Варіації для user_profile',
    'columns' => [
        'user_profile_id' => ['type' => 'integer', 'comment' => 'Користувач - Профіль',],
        'language_id' => ['type' => 'string', 'size' => '2', 'comment' => 'Мова',],

        'first_name' => [
            'type'        => 'string',
            'null'        => true,
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Ім’я',
        ],
        'middle_name' => [
            'type'        => 'string',
            'null'        => true,
            'required'    => false,
            'comment'     => 'По батькові',
        ],
        'last_name' => [
            'type'        => 'string',
            'null'        => true,
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Прізвище',
        ],
        //'birth_place' => ['type' => 'string', 'required' => true, 'comment' => 'Місце нарождення',],
        'address'     => ['type' => 'string', 'required' => false, 'null' => true, 'comment' => 'Адреса'],
    ],
    'PRIMARY' => 'user_profile_id, language_id', // Строка, только одна
    'relations' => [
        'user_profile' => [
            'table'     => 'user_profile',
            'attribute' => 'user_profile_id',
        ],
        'language'     => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
];
