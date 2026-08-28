<?php

/**
 * Таблиця "Логування змін" `historical_records`
 * Как добавлять?
 * alter table historical_records add check (json_valid(`details`));
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Логування змін',
    'columns'        => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'created_at' => ['type' => 'integer', 'required' => true, 'comment' => 'Cтворено',],
        'action_id'  => ['type' => 'integer', 'comment' => 'Подія',],
        'details'    => ['type' => 'text', 'comment' => 'Деталі',],

        'owner_class' => ['type' => 'string', 'comment' => 'Клас змін',],
        'owner_id'    => ['type' => 'integer', 'comment' => 'Запис змін',],

        'author_class' => ['type' => 'string', 'comment' => 'Клас автор',],
        'author_id'    => ['type' => 'integer', 'comment' => 'Запис автор',],
    ],
    'index'        => [
        'created_at',
        'action_id',
        'owner_class, owner_id',
        'author_class, author_id',
    ],

    // 'beforeValidate' => function ($model) {
    //     $model->created_at = time();
    //     return true;
    // },
];
