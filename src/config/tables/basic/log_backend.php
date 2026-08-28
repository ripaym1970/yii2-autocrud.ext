<?php

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title' => 'Помилки - Бекенд',
    'columns' => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'level'    => ['type' => 'integer', 'comment' => 'Рівень'],
        'category' => ['type' => 'string',  'comment' => 'Категорія'],
        'log_time' => ['type' => 'double',  'comment' => 'Створено'],
        'prefix'   => ['type' => 'text',    'comment' => 'Префікс'],
        'message'  => ['type' => 'text',    'comment' => 'Повідомлення'],
    ],

    'defaultOrder'   => ['log_time' => SORT_DESC],
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
];
