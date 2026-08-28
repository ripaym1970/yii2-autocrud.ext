<?php

/**
 * Таблиця "СМС-коды для авторизации пользователя" `user_auth_code`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'roles' => [
        ripaym1970\autocrud\models\AdminModel::ROLE_ADMINISTRATOR,
    ],
    'title'   => 'СМС-коди',
    'columns' => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'phone'      => ['type' => 'string', 'size' => '13', 'required' => true, 'comment' => 'Телефон авторизации',],
        'code'       => ['type' => 'string', 'size' => '6', 'required' => true, 'comment' => 'СМС-код',],
        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index'   => [
        'phone',
        'updated_at',
    ],
];
