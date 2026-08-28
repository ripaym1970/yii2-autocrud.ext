<?php

/**
 * Таблиця "Авторизация через соц.сети" `user_auth`
 * https://github.com/yiisoft/yii2-authclient/blob/master/docs/guide-ru/installation.md
 * $this->addForeignKey('fk-auth-user_id-user-id', 'auth', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE');
 */



return [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'   => 'Користувачі - Соц.мережі',
    'columns' => [
        'id' => ['type' => 'integer'],

        'user_id'   => ['type' => 'integer', 'comment' => 'ID пользователя',],
        'source'    => ['type' => 'string', 'fk' => false, 'comment' => 'Назва соц.сети',],
        'source_id' => ['type' => 'string', 'fk' => false, 'comment' => 'ID в соц.сети',],
    ],
    'index'        => [
        'user_id',
        'source_id',
    ],
    'relations'      => [
        'user'    => [
            'table'    => 'user',
        ],
    ],
];
