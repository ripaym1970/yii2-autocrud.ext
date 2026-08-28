<?php

/**
 * Таблиця "Пользователи сайта" `user`
 */

return [
    'title'             => 'Користувачі сайта',
    'columns'           => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        //'login'         => [
        //    'type'     => 'string',
        //    'required' => true,
        //    'unique'   => true,
        //    'null'     => false,
        //    'comment'  => 'Логін',
        //],

        // для регистрации и авторизации
        'email'                => ['type' => 'email',   'unique' => true, 'comment' => 'E-mail (Логін)',],
        'email_confirmed'      => ['type' => 'boolean', 'default' => 0, 'comment' => 'Підтвердження E-mail',],
        //'email_confirmation_code' => ['type' => 'string', 'null' => true],
        'password_hash' => ['type' => 'string', 'required' => true, 'comment' => 'Хеш пароля',],
        'password_reset_token' => ['type' => 'string', 'null' => true, 'comment' => 'Токен сброса пароля',],
        'auth_key'             => ['type' => 'string', 'comment' => 'Ключ повторної авторизації',],
        'status' => [
            'type'    => 'integer',
            'null'    => false,
            'items'   => function () {
                return ripaym1970\autocrud\models\UserModel::statusList(); // Щоб були варіації
            },
            'default' => ripaym1970\autocrud\models\UserModel::STATUS_ACTIVE,
            'comment' => 'Статус',
        ],

        // Данные пользователя
        'name'         => ['type' => 'string', 'required' => true, 'comment' => 'Нікнейм - для списку',],
        'username'     => ['type' => 'string', 'unique' => true, 'null' => true, 'comment' => 'Нікнейм',],
        'language_id'  => ['type' => 'string', 'size' => 2, 'default' => 'uk', 'comment' => 'Мова користувача',],
        'is_test'      => ['type' => 'boolean', 'default' => 0, 'comment' => 'Тестовий період',],
        'is_receive'   => ['type' => 'boolean', 'default' => 0, 'comment' => 'Відправлять повідомлея',],
        'lastlogin_at' => ['type' => 'integer', 'comment' => 'Останній візит',],
        'created_at'   => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at'   => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index'        => [
        'status',
        'created_at',
    ],

    'gridColumns'        => [
        'id',
        'created_at:datetime',
        //'language_id:string[table=language][type=text]',
        //'language.name',
        [
            'attribute' => 'language_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                if ($model->language_id) {
                    return yii\helpers\Html::a(
                        $model->language->name,
                        ['crud/view', 'table' => 'language', 'id' => $model->language_id]
                    );
                }
                return ripaym1970\autocrud\components\Yiit::t('Не задано');
            },
            //'filter' => ['' => 'Все', 'ru' => 'Русский', 'en' => 'English'],
            'filter' => 'distinct',
            'filter_class_name' => 'language',
        ],
        'email',
        'name:string',
        'status:dropdown',
        'lastlogin_at:datetime',
    ],

    'gridButtons'        => [
        'password' => [
            'icon'    => 'lock text-warning',
            'options' => [
                'data-pjax' => 0,
            ],
        ],
        //'delete'   => [],
    ],
    'viewAttributes'     => [
        'id:integer',
        'email',
        'name',
        'status:dropdown',
        //'language_id:string[table=language][type=text]',
        'lastlogin_at:datetime',
        'created_at:datetime',
        'updated_at:datetime',
    ],

    'formFields'         => [
        [['email',      'type' => 'string', 'width' => 6,]],
        [['name',   'type' => 'string', 'width' => 6,]],
        [['username',   'type' => 'string', 'width' => 6,]],
        [['password',   'type' => 'string', 'width' => 6, 'label' => 'Пароль',]],
        //[['password_repeat', 'type' => 'password', 'default' => '', 'width' => 12]],
        [['language_id', 'type' => 'relatedOneAjax', 'rel' => 'language', 'table' => 'language', 'where' => 'active=true', 'width' => 6,]],
        [['status',      'type' => 'dropdown', 'width' => 6, 'items' => [
            ripaym1970\autocrud\models\UserModel::STATUS_INACTIVE  => 'Неактивно',
            ripaym1970\autocrud\models\UserModel::STATUS_ACTIVE    => 'Активно',
            ripaym1970\autocrud\models\UserModel::STATUS_PENDING   => 'В очікуванні', // Ожидает активации?
            ripaym1970\autocrud\models\UserModel::STATUS_SUSPENDED => 'Припинено',  // Приостановлено
            ripaym1970\autocrud\models\UserModel::STATUS_DELETED   => 'Видалено',   // Удален сам или?
        ], 'default' => 1,]],
    ],
    'fields'             => [
        'password',
        'password_repeat',
    ],
    'formFieldsPassword' => [
        [['password',        'type' => 'password', 'width' => 12, 'required' => true, 'label' => 'Пароль',]],
        [['password_repeat', 'type' => 'password', 'width' => 12, 'required' => true, 'label' => 'Пароль',]],
    ],
    'relations'      => [
        'language'    => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
        //'profile'    => [
        //    'table'     => 'user_profile',
        //    'attribute' => 'user_id',
        //],
        //'company'    => [
        //    'table'     => 'user_company',
        //    'attribute' => 'company_id',
        //],
    ],
    'beforeValidate'     => function ($user) {
        //$user->auth_key = $user->auth_key ?: Yii::$app->security->generateRandomString();
        //$user->password_reset_token = $user->password_reset_token ?: Yii::$app->security->generateRandomString();
        //$user->password_hash = $user->password ?: Yii::$app->security->generateRandomString();

        if (empty($user->email)) {
            unset($user->email);
        }
        if (!empty($user->password)) {
            if ($user->password !== $user->password_repeat) {
                $user->addError('password', ripaym1970\autocrud\components\Yiit::t('Паролі не збігаються'));
                return false;
            }
            $user->password_hash = Yii::$app->security->generatePasswordHash($user->password);
        }

        return true;
    },
    //'beforeValidate' => function ($model) {
    //    if (!is_numeric($model->number)) {
    //        $model->addError('number', ripaym1970\autocrud\components\Yiit::t('Должны быть только цифры. Должно быть 16 цифр.'));
    //        return false;
    //    }
    //    if (mb_strlen($model->number) < 16) {
    //        $model->addError('number', ripaym1970\autocrud\components\Yiit::t('Короткий номер. Должно быть 16 цифр.'));
    //        return false;
    //    }
    //    if (mb_strlen($model->number) > 16) {
    //        $model->addError('number', ripaym1970\autocrud\components\Yiit::t('Длинный номер. Должно быть 16 цифр.'));
    //        return false;
    //    }
    //    return true;
    //},
    'fill' => [
        [
            'id'            => 1,
            'email'         => 'ripaym@ukr.net',
            'password_hash' => function () {
                return Yii::$app->getSecurity()->generatePasswordHash('123456');
            },
            'auth_key'      => 'j9IFbefjvus_10dFJghlwrjATrYtPjPI',
            'status'        => 1,
            'name'          => 'ripaym',
            'username'      => 'ripaym',
            'language_id'   => 'uk',
            'created_at'    => 1714302548,
            'updated_at'    => 1714302548,
        ],
    ],
];
