<?php

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title' => 'Помилки - Фронтенд',
    'columns' => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'level'    => ['type' => 'integer', 'comment' => 'Рівень'],
        'category' => ['type' => 'string',  'comment' => 'Категорія'],
        'log_time' => ['type' => 'double',  'comment' => 'Створено'],
        'prefix'   => ['type' => 'text',    'comment' => 'Префікс'],
        'message'  => ['type' => 'text',    'comment' => 'Повідомлення'],
    ],

    'defaultOrder'   => ['log_time' => SORT_DESC],
    'gridToolbar' => ['{flush}',], // Список дополнительных кнопок
    'gridColumns'    => [
        'id',
        'level',
        'category',
        'log_time:datetime',
        'prefix',
    ],
    'viewAttributes' => [
        'id',
        'level',
        'category',
        'log_time:datetime',
        'prefix',
        'message',
    ],
    'formFields'     => [
        [['level', 'type' => 'text',],],
        [['category', 'type' => 'text',],],
        [['log_time', 'type' => 'text',],],
        [['prefix', 'type' => 'text',],],
        [['message', 'type' => 'textarea', 'rows' => 12,],],
    ],
];
