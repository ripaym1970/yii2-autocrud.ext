<?php

/**
 * Таблиця "Пользователи админки" `admin`
 */

return [
    'crud_menu' => false,
    'crud_edit' => false,
    'roles'              => [
        ripaym1970\autocrud\models\AdminModel::ROLE_ADMINISTRATOR,
    ],
    'title'          => 'Адміни',
    'columns'           => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'role'          => [
            'type'    => 'enum',
            'items'   => function () {
                return ripaym1970\autocrud\models\AdminModel::roleList(); // Щоб були варіації треба callback
            },
            'default' => ripaym1970\autocrud\models\AdminModel::ROLE_USER,
            'comment' => 'Роль',
        ],
        'login'         => [
            'type'     => 'email',
            'required' => true,
            'unique'   => true,
            'comment'  => 'Логін (E-mail)',
        ],
        'status'        => [
            'type'    => 'boolean',
            'items'   => function () {
                return ripaym1970\autocrud\models\AdminModel::roleList(); // Щоб були варіації треба callback
            },
            'default' => ripaym1970\autocrud\models\AdminModel::STATUS_ACTIVE,
            'comment' => 'Активно',
        ],

        //'company_id' => ['type' => 'integer', 'null' => false, 'fk' => false, 'comment' => 'Компанія',],
        'name'       => ['type' => 'string', 'required' => true, 'comment' => 'Нікнейм - для списку',],
        'username'   => ['type' => 'string', 'required' => true, 'comment' => 'Нікнейм',],
        'first_name' => ['type' => 'string', 'required' => true, 'comment' => "Ім'я",],
        'last_name'  => ['type' => 'string', 'required' => true, 'comment' => 'Прізвище',],
        'phone'      => ['type' => 'string', 'comment' => 'Телефон',],

        'lastlogin_at'  => ['type' => 'integer', 'comment' => 'Останній визит',],
        'password_hash' => ['type' => 'string', 'required' => true, 'comment' => 'Хеш пароля',],
        'created_at'    => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at'    => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index'              => [
        'role',
        'login',
        //'company_id',
        'status',
    ],
    'relations' => [
        'companies' => [
            'multiple'  => true,
            'table'     => 'company',
            'attribute' => 'admin_id',
            'via'       => 'admin_company_assignment',
        ],
    ],

    'defaultOrder'       => ['updated_at' => SORT_ASC],
    'gridColumns'        => [
        'id',
        'login',
        //'name',
        //'email',
        //'company.name',
        'role:dropdown',
        'status:boolean',
        'lastlogin_at:datetime',
    ],
    'gridButtons'        => [
        // Кнопка смены пароля
        'password' => [
            'icon'    => 'lock text-warning',
            'options' => [
                'data-pjax' => 0,
                'title'     => 'Змінити пароль',
            ],
        ],
        //'delete'   => [], // Добавить стандартную кнопку "Удалить"
    ],
    'viewAttributes'     => [
        'gridColumns',
        'lastlogin_at:datetime',
        'created_at:datetime',
        'updated_at:datetime',
    ],
    'formFields'         => [
        [['login', 'type' => 'text',],],
        //[['name', 'type' => 'text',],],
        //[['email', 'type' => 'text',],],
        [['role', 'type' => 'dropdown',],],
        [['status', 'type' => 'checkbox',],],
    ],
    'fields'             => [
        'password',
        'password_repeat',
    ],
    'formFieldsPassword' => [
        [['password', 'type' => 'password', 'required' => true,],],
        [['password_repeat', 'type' => 'password', 'required' => true,],],
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
    ],
    'beforeValidate'     => function ($user) {
        $user->password_hash = $user->password ?: Yii::$app->security->generateRandomString();
        //if (empty($user->name)) {
        //    unset($user->name);
        //}
        //if (empty($user->email)) {
        //    unset($user->email);
        //}
        if (!empty($user->password)) {
            //if ($user->password !== $user->password_repeat) {
            //    $user->addError('password_repeat', ripaym1970\autocrud\components\Yiit::t('Passwords does not match'));
            //    return false;
            //}
            $user->password_hash = Yii::$app->security->generatePasswordHash($user->password);
        }
        return true;
    },
    'beforeSave'         => function ($user) {
        if ($user->id === Yii::$app->user->id && $user->status === ripaym1970\autocrud\models\AdminModel::STATUS_INACTIVE) {
            $user->addError('id', ripaym1970\autocrud\components\Yiit::t('Неможливо деактивувати себе'));
            return false;
        }
        if ($user->id === Yii::$app->user->id && $user->isAttributeChanged('role')) {
            $user->addError('id', ripaym1970\autocrud\components\Yiit::t('Ви не можете змінити собі роль'));
            return false;
        }

        return true;
    },
    //'afterSave'          => function ($user) {
    //    $role = Yii::$app->authManager->getRole($user->role);
    //    Yii::$app->authManager->revokeAll($user->id);
    //    Yii::$app->authManager->assign($role, $user->id);
    //},
    'beforeDelete'       => function ($user) {
        if ($user->id === Yii::$app->user->id) {
            $user->addError('id', ripaym1970\autocrud\components\Yiit::t('Неможливо видалити себе'));
            return false;
        }
        return true;
    },
    //'afterDelete'        => function ($user) {
    //    Yii::$app->authManager->revokeAll($user->id);
    //},
    'fill' => [
        [
            'id'            => 1,
            'name'          => 'admin_test',
            'username'      => 'admin_test',
            'role'          => 'admin',
            'login'         => 'admin@test.com',
            'password_hash' => '$2y$13$uApvNye4ljKft1qNKeBjyuwX3QAFADAyebZv354BSu42e3VLHp/Su',
            'status'        => 1,
        ],
    ],
];
