<?php

/**
 * Таблиця "Логирование входов пользователей" `user_auth_log.php`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'   => 'Користувачі - Лог авторизації',
    'columns' => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'user_id'      => ['type' => 'integer', 'comment' => 'Користувач'],
        'date'         => ['type' => 'integer', 'comment' => 'Дата авторизації'],
        'cookie_based' => ['type' => 'boolean', 'default' => 1, 'comment' => 'cookie'],
        'duration'     => ['type' => 'integer', 'comment' => 'На сайті'],
        'error'        => ['type' => 'string',  'comment' => 'Помилка'],
        'ip'           => ['type' => 'string',  'comment' => 'IP користувача'],
        'host'         => ['type' => 'string',  'comment' => 'HOST користувача'],
        'url'          => ['type' => 'string',  'comment' => 'URL користувача'],
        'user_agent'   => ['type' => 'string',  'comment' => 'USER АGENT'],
    ],
    'index'        => [
        'date',
        'user_id',
    ],
    'relations'      => [
        'user'    => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
    ],
];
