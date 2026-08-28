<?php

return [

/**
 * Таблиця "Пользователи админки" `admin`
 */

'admin' => [
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
],


/**
 * Таблиця "Міста" `city`
 */

'city' => [
    'title' => 'Міста',
    'columns' => [
        'id' => ['type' => 'integer', 'comment' => 'ID',],

        'slug'       => ['type' => 'string', 'unique' => true, 'comment' => 'ЧПУ',],

        'country_id' => ['type' => 'integer', 'comment' => 'Країна',],

        'active'     => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно',],
    ],
    'index' => [
        'country_id',
        'active',
    ],

    'gridColumns' => [
        'id',
        [
            'attribute' => 'country_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelCountry = $model->country ?? null;
                if ($modelCountry) {
                    $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                    $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                    foreach ($modelCountry->translations as $translation) {
                        $translationLanguageId = $translation->language_id;
                        if (in_array($translationLanguageId, $languageIds)) {
                            $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                        }
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'country',
        ],
        [
            'attribute' => 'name',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
        'slug',
        'active:boolean',
        //'center_latitude',
        //'center_longitude',
    ],
    'viewAttributes' => [
        'gridColumns',
        //'center_latitude',
        //'center_longitude',
    ],
    'formFields' => [
        [
            [
                'country_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'country',
                'table' => 'country',
            ],
        ],
        [['name', 'type' => 'text',],],
        [['genitive1', 'type' => 'text',],],
        [['genitive2', 'type' => 'text',],],
        [['slug', 'type' => 'text',],],
        [['active', 'type' => 'checkbox', 'width' => 3,],],
        //[['center_latitude', 'type' => 'text',],],
        //[['center_longitude', 'type' => 'text',],],
    ],
    'relations' => [
        'country' => [
            'table'     => 'country',
            'attribute' => 'country_id',
        ],
        'translations' => [
            'multiple'  => true,
            'table'     => 'city_translation',
            'attribute' => 'city_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'name'      => null, // TODO: Уточнить поля
                'genitive1' => null, // TODO: Уточнить поля
                'genitive2' => null, // TODO: Уточнить поля
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name); // TODO: Уточнить поля
            },
        ],
    ],
    'fill'        => [
        [
            'id'         => 1,
            'country_id' => 1,
            'slug'       => 'kyiv',
            'active'     => 1,
        ],
    ],
],


/**
 * Таблиця "Переводы для "Города" `city_translation`
 */

'city_translation' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Міста',
    'columns'        => [
        'city_id'     => ['type' => 'integer', 'comment' => 'Місто',],
        'language_id' => ['type' => 'string', 'size' => 2, 'default' => 'uk', 'comment' => 'Мова',],

        'name' => [
            'type'        => 'string',
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Назва',
        ],
        'genitive1' => [
            'type'        => 'string',
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Назва = Кого?',
        ],
        'genitive2' => [
            'type'        => 'string',
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Назва = Де?',
        ],
    ],
    'PRIMARY' => 'city_id, language_id', // Строка, только одна
    'relations' => [
        'city' => [
            'table'     => 'city',
            'attribute' => 'city_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    //'behaviors'      => [
    //    ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
    //],
    'fill' => [
        [
            'city_id'     => 1,
            'language_id' => 'en',
            'name'        => 'Kyiv',
        ],
        [
            'city_id'     => 1,
            'language_id' => 'ru',
            'name'        => 'Киев',
        ],
        [
            'city_id'     => 1,
            'language_id' => 'uk',
            'name'        => 'Київ',
        ],
    ],
],


/**
 * Таблиця "Страны" `country`
 */

'country' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Країни',
    'columns'        => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'code'   => ['type' => 'string', 'size' => 2, 'unique' => true, 'comment' => 'Код ISO'],
        'slug'   => ['type' => 'string', 'unique' => true, 'comment' => 'ЧПУ'],
        'active' => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],
    ],
    'index' => [
        'active',
    ],

    'gridColumns'    => [
        'id',
        [
            'attribute' => 'name',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
        'code',
        'slug',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields'     => [
        [['name',   'type' => 'text',],],
        [['code',   'type' => 'text',],],
        [['slug',   'type' => 'text',],],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations'      => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'country_translation',
            'attribute' => 'country_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'name' => null, // TODO: Уточнить поля
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name); // TODO: Уточнить поля
            },
        ],
    ],
    'fill'        => [
        [
            'id'      => 1,
            'code'    => 'UK',
            'slug'    => 'ukraine',
            'active'  => 1,
        ],
    ],
],


/**
 * Таблиця "Переводы для "Країни" `country_translation`
 */

'country_translation' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Країни',
    'columns'        => [
        'country_id'  => ['type' => 'integer', 'comment' => 'Країна',],
        'language_id' => ['type' => 'string', 'size' => 2, 'default' => 'uk', 'comment' => 'Мова',],

        'name' => [
            'type'        => 'string',
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Назва',
        ],
    ],
    'PRIMARY' => 'country_id, language_id', // Строка, только одна
    'relations' => [
        'country'     => [
            'table'     => 'country',
            'attribute' => 'country_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    'fill' => [
        [
            'country_id'  => 1,
            'language_id' => 'uk',
            'name'        => 'Україна',
        ],
        [
            'country_id'  => 1,
            'language_id' => 'en',
            'name'        => 'Ukraine',
        ],
    ],
],


/**
 * Таблиця "Валюти" `currency`
 */

'currency' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'     => 'Валюти',
    'icon_menu' => 'currency',
    'columns'   => [
        'id' => ['type' => 'string',  'size' => 3, 'comment' => 'ID'],

        'code'    => ['type' => 'string', 'size' => 1, 'unique' => true, 'comment' => 'Символ'],
        'slug'    => ['type' => 'string', 'size' => 3, 'unique' => true, 'comment' => 'ЧПУ'],
        'default' => ['type' => 'boolean', 'default' => 0, 'comment' => 'Дефолтна',],
        'active'  => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],
    ],

    'defaultOrder'  => ['id' => SORT_ASC],
    'gridColumns'    => [
        'id',
        'name',
        'code',
        'slug',
        'default:boolean',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields'     => [
        [['name', 'type' => 'text',],],
        [['code', 'type' => 'text',],],
        [['slug', 'type' => 'text',],],
        [['default', 'type' => 'checkbox',],],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations'      => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'currency_translation',
            'attribute' => 'currency_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'name'        => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name) ;
            },
        ],
    ],
    'fill'        => [
        [
            'id'      => 'USD',
            'code'    => '$',
            'slug'    => 'usd',
            'default' => 0,
            'active'  => 1,
        ],
        [
            'id'      => 'EUR',
            'code'    => '€',
            'slug'    => 'eur',
            'default' => 0,
            'active'  => 0,
        ],
        [
            'id'      => 'RUB',
            'code'    => '₽',
            'slug'    => 'rub',
            'default' => 0,
            'active'  => 0,
        ],
        [
            'id'      => 'UAH',
            'code'    => '₴',
            'slug'    => 'uah',
            'default' => 1,
            'active'  => 1,
        ],
    ],
],


/**
 * Таблиця "Переводы для "Валюти" `currency_translation`
 */

'currency_translation' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'     => 'Валюти',
    'columns'   => [
        'currency_id' => ['type' => 'string', 'size' => 3, 'comment' => 'Валюта',],
        'language_id' => ['type' => 'string', 'size' => 2, 'comment' => 'Мова',],

        'name' => [
            'type'        => 'string',
            'null'        => true,
            'required'    => true,
            'required_if' => function ($model) {
                return $model->language->id == Yii::$app->language;
            },
            'comment'     => 'Назва',
        ],
    ],
    'PRIMARY' => 'currency_id, language_id', // Строка, только одна
    'relations' => [
        'currency' => [
            'table'     => 'currency',
            'attribute' => 'currency_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    'fill' => [
        [
            'currency_id' => 'USD',
            'language_id' => 'uk',
            'name'        => 'Долар США',
        ],
        [
            'currency_id' => 'EUR',
            'language_id' => 'uk',
            'name'        => 'Євро',
        ],
        [
            'currency_id' => 'RUB',
            'language_id' => 'uk',
            'name'        => 'Рубль Росії',
        ],
        [
            'currency_id' => 'UAH',
            'language_id' => 'uk',
            'name'        => 'Гривня України',
        ],
        [
            'currency_id' => 'USD',
            'language_id' => 'ru',
            'name'        => 'Доллар США',
        ],
        [
            'currency_id' => 'EUR',
            'language_id' => 'ru',
            'name'        => 'Евро',
        ],
        [
            'currency_id' => 'RUB',
            'language_id' => 'ru',
            'name'        => 'Рубль России',
        ],
        [
            'currency_id' => 'UAH',
            'language_id' => 'ru',
            'name'        => 'Гривня Украины',
        ],
    ],
],


/**
 * Таблиця "Логування змін" `historical_records`
 * Как добавлять?
 * alter table historical_records add check (json_valid(`details`));
 */

'historical_records' => [
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
],


/**
 * Таблиця "Мови" `language`
 */

'language' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title' => 'Мови',
    'icon_menu' => 'language',
    'columns' => [
        'id' => ['type' => 'string', 'size' => 2, 'pk' => true, 'comment' => 'Код',],

        'name'    => ['type' => 'string', 'size' => 16, 'comment' => 'Назва', 'unique' => true,],
        'default' => ['type' => 'boolean', 'default' => 0, 'comment' => 'Дефолтна',],
        'active'  => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно',],
        'flag'    => ['type' => 'text', 'comment' => 'SVG-код картинки флага',],
    ],
    'defaultOrder'   => ['name' => SORT_ASC],
    'gridColumns'    => [
        'id',
        'name',
        'default:boolean',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields'     => [
        [['name', 'type' => 'text',],],
        [['flag', 'type' => 'text',],],
        [['default', 'type' => 'checkbox',],],
        [['active', 'type' => 'checkbox',],],
    ],
    // TODO: Без прапорів
    //'fill' => [
    //    ['id' => 'en', 'name' => 'English',    'default' => false,  'active' => true,],
    //    ['id' => 'de', 'name' => 'German',     'default' => false,  'active' => false,],
    //    ['id' => 'fr', 'name' => 'French',     'default' => false,  'active' => false,],
    //    ['id' => 'ru', 'name' => 'Русский',    'default' => false,  'active' => false,],
    //    ['id' => 'uk', 'name' => 'Українська', 'default' => true,   'active' => true,],
    //],
    // TODO: З прапорами
    'fill' => [
        ['id' => 'en', 'name' => 'English', 'default' => false, 'active' => true, 'flag' => '<svg width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" enable-background="new 0 0 512 512"><circle cx="256" cy="256" r="256" fill="#F0F0F0"/><g fill="#0052B4"><path d="M52.92 100.142c-20.109 26.163-35.272 56.318-44.101 89.077h133.178l-89.077-89.077zM503.181 189.219c-8.829-32.758-23.993-62.913-44.101-89.076l-89.075 89.076h133.176zM8.819 322.784c8.83 32.758 23.993 62.913 44.101 89.075l89.074-89.075h-133.175zM411.858 52.921c-26.163-20.109-56.317-35.272-89.076-44.102v133.177l89.076-89.075zM100.142 459.079c26.163 20.109 56.318 35.272 89.076 44.102v-133.176l-89.076 89.074zM189.217 8.819c-32.758 8.83-62.913 23.993-89.075 44.101l89.075 89.075v-133.176zM322.783 503.181c32.758-8.83 62.913-23.993 89.075-44.101l-89.075-89.075v133.176zM370.005 322.784l89.075 89.076c20.108-26.162 35.272-56.318 44.101-89.076h-133.176z"/></g><g fill="#D80027"><path d="M509.833 222.609h-220.441v-220.442c-10.931-1.423-22.075-2.167-33.392-2.167-11.319 0-22.461.744-33.391 2.167v220.441h-220.442c-1.423 10.931-2.167 22.075-2.167 33.392 0 11.319.744 22.461 2.167 33.391h220.441v220.442c10.931 1.423 22.073 2.167 33.392 2.167 11.317 0 22.461-.743 33.391-2.167v-220.441h220.442c1.423-10.931 2.167-22.073 2.167-33.392 0-11.317-.744-22.461-2.167-33.391zM322.783 322.784l114.236 114.236c5.254-5.252 10.266-10.743 15.048-16.435l-97.802-97.802h-31.482v.001zM189.217 322.784h-.002l-114.235 114.235c5.252 5.254 10.743 10.266 16.435 15.048l97.802-97.804v-31.479zM189.217 189.219v-.002l-114.236-114.237c-5.254 5.252-10.266 10.743-15.048 16.435l97.803 97.803h31.481zM322.783 189.219l114.237-114.238c-5.252-5.254-10.743-10.266-16.435-15.047l-97.802 97.803v31.482z"/></g></svg>'],
        ['id' => 'de', 'name' => 'German', 'default' => false,  'active' => false, 'flag' => '<svg width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" enable-background="new 0 0 512 512"><path d="M15.923 345.043c36.171 97.484 130.006 166.957 240.077 166.957s203.906-69.473 240.077-166.957l-240.077-22.26-240.077 22.26z" fill="#FFDA44"/><path d="M256 0c-110.071 0-203.906 69.472-240.077 166.957l240.077 22.26 240.077-22.261c-36.171-97.484-130.006-166.956-240.077-166.956z"/><path d="M15.923 166.957c-10.29 27.733-15.923 57.729-15.923 89.043s5.633 61.31 15.923 89.043h480.155c10.29-27.733 15.922-57.729 15.922-89.043s-5.632-61.31-15.923-89.043h-480.154z" fill="#D80027"/></svg>'],
        ['id' => 'fr', 'name' => 'French', 'default' => false,  'active' => false, 'flag' => '<svg width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" enable-background="new 0 0 512 512"><circle cx="256" cy="256" r="256" fill="#F0F0F0"/><path d="M512 256c0-110.071-69.472-203.906-166.957-240.077v480.155c97.485-36.172 166.957-130.007 166.957-240.078z" fill="#D80027"/><path d="M0 256c0 110.071 69.473 203.906 166.957 240.077v-480.154c-97.484 36.171-166.957 130.006-166.957 240.077z" fill="#0052B4"/></svg>'],
        ['id' => 'ru', 'name' => 'Русский', 'default' => false, 'active' => false, 'flag' => '<svg width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" enable-background="new 0 0 512 512"><circle cx="256" cy="256" r="256" fill="#F0F0F0"/><path d="M496.077 345.043c10.291-27.733 15.923-57.729 15.923-89.043s-5.632-61.31-15.923-89.043h-480.154c-10.29 27.733-15.923 57.729-15.923 89.043s5.633 61.31 15.923 89.043l240.077 22.261 240.077-22.261z" fill="#0052B4"/><path d="M256 512c110.071 0 203.906-69.472 240.077-166.957h-480.154c36.171 97.485 130.006 166.957 240.077 166.957z" fill="#D80027"/></svg>'],
        ['id' => 'uk', 'name' => 'Українська', 'default' => true, 'active' => true, 'flag' => '<svg width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" enable-background="new 0 0 512 512"><circle style="fill:#FFDA44;" cx="256" cy="256" r="256"/><path style="fill:#338AF3;" d="M0,256C0,114.616,114.616,0,256,0s256,114.616,256,256"/></svg>'],
    ],
],


'log_backend' => [
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
],


'log_frontend' => [
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
],


/**
 * Таблиця "Ключи сообщений для Yiit::t($message, $category)" `message`
 *
 * Якщо декілька однакових повідомлень відрізняються відмінком (падежом) в другій мові,
 * то змінивши $category зможете конкретизувати місце.
 * Не забудьте додати category в код!
 */

'message' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Переклади повідомлень', // Тут такое надо название
    'columns'        => [
        'id' => ['type' => 'integer', 'comment' => 'ID',],

        'category' => ['type' => 'string', 'default' => 'app', 'comment' => 'Категорія',],
        'message'  => ['type' => 'string', 'comment' => 'Повідомлення для перекладу',],
    ],
    'defaultOrder'   => ['message' => SORT_ASC],
    'gridToolbar' => ['{flush}',], // Список дополнительных кнопок
    'gridColumns'    => [
        'id',
        'category',
        'message',
        [
            'attribute' => 'translation',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                foreach ($model->translations as $translation) {
                    $out .= $translation->language_id . ', ' . $translation->translation . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields' => [
        [['category', 'type' => 'text',],],
        [['message', 'type' => 'text',],],
        [['translation', 'type' => 'text',],],
    ],
    'relations'      => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'message_translation',
            'attribute' => 'message_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'translation' => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->translation) ;
            },
        ],
    ],
],


/**
 * Таблиця "Переводы сообщений для Yiit::t()" `message_translation`
 *
 * alter table message_translation
 * add constraint message_translation_message_id_fk
 * foreign key (message_id) references message (id);
 *
 * alter table message_translation
 * add constraint message_translation_language_id_fk
 * foreign key (language_id) references language (id);
 */

'message_translation' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title' => 'Переклади повідомлень на інші активні мови',
    'columns' => [
        'message_id' => ['type' => 'integer', 'comment' => 'Повідомлення'],
        'language_id' => ['type' => 'string', 'size' => 2, 'comment' => 'Мова'],

        'translation' => [
            'type' => 'string',
            'required' => true,
            'comment' => 'Переклад',
        ],
    ],
    'PRIMARY'   => 'message_id, language_id',
    'relations' => [
        'message' => [
            'table'     => 'message',
            'attribute' => 'message_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
],


/**
 * Таблиця "Пользователи сайта" `user`
 */

'user' => [
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
],


/**
 * Таблиця "Авторизация через соц.сети" `user_auth`
 * https://github.com/yiisoft/yii2-authclient/blob/master/docs/guide-ru/installation.md
 * $this->addForeignKey('fk-auth-user_id-user-id', 'auth', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE');
 */



'user_auth' => [
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
],


/**
 * Таблиця "СМС-коды для авторизации пользователя" `user_auth_code`
 */

'user_auth_code' => [
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
],


/**
 * Таблиця "Логирование входов пользователей" `user_auth_log.php`
 */

'user_auth_log' => [
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
],


/**
 * Таблиця "Продукти- список" `product`
 */

'product' => [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Продукти - список',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'product_category_id' => ['type' => 'integer', 'comment' => 'Категорія'],
        'unit_id'             => ['type' => 'integer', 'comment' => 'Розмірність'],
        'active'              => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],

        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'product_category_id',
        'active',
    ],

    'gridColumns'    => [
        'id',
        [
            'attribute' => 'name',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
        [
            'attribute' => 'product_category_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->product_category ?? null;
                if ($modelProperty) {
                    $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                    $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                    foreach ($modelProperty->translations as $translation) {
                        $translationLanguageId = $translation->language_id;
                        if (in_array($translationLanguageId, $languageIds)) {
                            $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                        }
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'unit',
        ],
        [
            'attribute' => 'unit_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->unit ?? null;
                if ($modelProperty) {
                    $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                    $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                    foreach ($modelProperty->translations as $translation) {
                        $translationLanguageId = $translation->language_id;
                        if (in_array($translationLanguageId, $languageIds)) {
                            $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                        }
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'unit',
        ],
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
        [
            'attribute' => 'description',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->description . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
    ],
    'formFields'     => [
        [['name', 'type' => 'text',],],
        [['description', 'type' => 'editor',],],
        [
            [
                'unit_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'unit',
                'table' => 'unit',
            ],
        ],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations'      => [
        'product_category'     => [
            'table'     => 'product_category',
            'attribute' => 'product_category_id',
        ],
        'unit' => [
            'table'     => 'unit',
            'attribute' => 'unit_id',
        ],
        'translations'       => [
            'multiple'  => true,
            'table'     => 'product_translation',
            'attribute' => 'product_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'name'        => null,
                'description' => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name);
            },
        ],
    ],
    //'fill'        => [
    //    [
    //        'id'       => 1,
    //        'unit_id'  => 1,
    //        'active'   => 1,
    //    ],
    //    [
    //        'id'       => 2,
    //        'unit_id'  => 1,
    //        'active'   => 1,
    //    ],
    //],
],


/**
 * Таблиця "Закуплені продукти" `product_add`
 */
//adding products

'product_add' => [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Закуплені продукти',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'user_id'    => ['type' => 'integer', 'comment' => 'Користувач'],
        'product_id' => ['type' => 'integer', 'comment' => 'Продукт',],
        'quantity'   => ['type' => 'integer', 'required' => true, 'comment' => 'Кількість',],

        'created_at'    => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at'    => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'user_id',
        'product_id',
        'created_at',
    ],

    'gridColumns'    => [
        'id',
        [
            'attribute' => 'user_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                return $model->user->name;
            },
            'filter' => 'distinct',
            'filter_class_name' => 'user',
        ],
        [
            'attribute' => 'product_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->product->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }

                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'product',
        ],
        [
            'attribute' => 'quantity',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = $model->quantity;
                $modelProperty = $model->product->unit ?? null;
                if ($modelProperty) {
                    $out .= ' ' . $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields'     => [
        [
            [
                'user_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'user',
                'table' => 'user',
            ],
        ],
        [
            [
                'product_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'product',
                'table' => 'product',
            ],
        ],
        [['quantity', 'type' => 'text',],],
        [
            [
                'unit_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'unit',
                'table' => 'unit',
            ],
        ],
    ],
    'relations'      => [
        'user' => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
        'product' => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
        'unit' => [
            'table'     => 'unit',
            'attribute' => 'unit_id',
        ],
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
    ],
    'fill' => [
        [
            'id'         => 1,
            'user_id'    => 1,
            'product_id' => 1,
            'quantity'   => 10,
        ],
    ],
],


/**
 * Таблиця "Категорії продуктів" `product_category_translation`
 */

'product_category' => [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Категорії продуктів',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'parent_id' => ['type' => 'integer', 'comment' => 'Parent'],
        'active'    => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],

        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'parent_id',
        'active',
    ],
    'gridColumns'    => [
        'id',
        'parent_id',
        [
            'attribute' => 'name',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
    ],
    'viewAttributes' => [
        'gridColumns',
        'description:raw',
    ],
    'formFields'     => [
        [['name', 'type' => 'text',],],
        [
            [
                'description',
                'label' => 'Опис',
                'type'  => 'editor',
            ],
        ],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations' => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'product_category_translation',
            'attribute' => 'product_category_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'name'        => null,
                'description' => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name);
            },
        ],
    ],
    'fill' => [
        [
            'id'     => 1,
            'active' => 1,
        ],
        [
            'id'     => 2,
            'active' => 1,
        ],
        [
            'id'     => 3,
            'active' => 1,
        ],
        [
            'id'     => 4,
            'active' => 1,
        ],
    ],
],


/**
 * Таблиця "Категорії продуктів" `product_category_translation`
 */

'product_category_translation' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Категорії продуктів',
    'columns'        => [
        'product_category_id'  => ['type' => 'integer', 'comment' => 'Назва',],
        'language_id' => ['type' => 'string', 'size' => 2, 'default' => 'uk', 'comment' => 'Мова',],

        'name' => [
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
            'comment'     => 'Опис',
        ],
    ],
    'PRIMARY' => 'product_category_id, language_id', // Строка, только одна
    'relations' => [
        'product_category'     => [
            'table'     => 'product_category',
            'attribute' => 'product_category_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    'fill' => [
        [
            'product_category_id' => 1,
            'language_id'         => 'uk',
            'name'                => 'Сахар',
        ],
        [
            'product_category_id' => 1,
            'language_id'         => 'en',
            'name'                => 'Sugar',
        ],
        [
            'product_category_id' => 2,
            'language_id'         => 'uk',
            'name'                => 'Сіль',
        ],
        [
            'product_category_id' => 2,
            'language_id'         => 'en',
            'name'                => 'Salt',
        ],
        [
            'product_category_id' => 3,
            'language_id'         => 'uk',
            'name'                => 'Крупи',
        ],
        [
            'product_category_id' => 3,
            'language_id'         => 'en',
            'name'                => 'Groats',
        ],
        [
            'product_category_id' => 4,
            'language_id'         => 'uk',
            'name'                => 'Мука',
        ],
        [
            'product_category_id' => 4,
            'language_id'         => 'en',
            'name'                => 'Flour',
        ],
    ],
],

/**
 * Можна виділити кілька основних груп харчових продуктів:
 * М'ясо та м'ясопродукти;
 * Риба та рибопродукти;
 * Яйця;
 * Молоко та молочні продукти;
 * Хліб та хлібобулочні вироби;
 * Крупи, макаронні вироби;
 * Бобові;
 * Овочі;
 * Фрукти та ягоди;
 * Горіхи;
 * Гриби;
 * Кондитерські вироби;
 * Харчові жири;
 * Напої, чай, кава.
 */


/**
 * Таблиця "Витрачені продукти" `product_iss`
 */
//issuance of products

'product_iss' => [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Витрачені продукти',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'user_id'    => ['type' => 'integer', 'comment' => 'Користувач'],
        'product_id' => ['type' => 'integer', 'comment' => 'Продукт',],
        'quantity'   => ['type' => 'integer', 'required' => true, 'comment' => 'Кількість',],

        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'user_id',
        'product_id',
        'created_at',
    ],

    'gridColumns'    => [
        'id',
        [
            'attribute' => 'user_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                return $model->user->name;
            },
            'filter' => 'distinct',
            'filter_class_name' => 'user',
        ],
        [
            'attribute' => 'product_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->product->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }

                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'product',
        ],
        [
            'attribute' => 'quantity',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = $model->quantity;
                $modelProperty = $model->product->unit ?? null;
                if ($modelProperty) {
                    $out .= ' ' . $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'formFields'     => [
        [
            [
                'user_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'user',
                'table' => 'user',
            ],
        ],
        [
            [
                'product_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'product',
                'table' => 'product',
            ],
        ],
        [['quantity', 'type' => 'text',],],
        [
            [
                'unit_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'unit',
                'table' => 'unit',
            ],
        ],
    ],
    'relations'      => [
        'user' => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
        'product' => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
        'unit' => [
            'table'     => 'unit',
            'attribute' => 'unit_id',
        ],
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
    ],
    'fill' => [
        [
            'id'         => 1,
            'user_id'    => 1,
            'product_id' => 1,
            'quantity'   => 5,
        ],
    ],
],


/**
 * Таблиця "" `product_translation`
 */

'product_translation' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Продукти',
    'columns'        => [
        'product_id'  => ['type' => 'integer', 'comment' => 'Назва',],
        'language_id' => ['type' => 'string', 'size' => 2, 'default' => 'uk', 'comment' => 'Мова',],

        'name' => [
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
            'comment'     => 'Опис',
        ],
    ],
    'PRIMARY' => 'product_id, language_id', // Строка, только одна
    'relations' => [
        'product'     => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    //'fill' => [
    //    [
    //        'product_id'  => 1,
    //        'language_id' => 'uk',
    //        'name'        => 'Куряче яйце',
    //    ],
    //    [
    //        'product_id'  => 1,
    //        'language_id' => 'en',
    //        'name'        => 'Chicken egg',
    //    ],
    //    [
    //        'product_id'  => 2,
    //        'language_id' => 'uk',
    //        'name'        => 'Страусове яйце',
    //    ],
    //    [
    //        'product_id'  => 2,
    //        'language_id' => 'en',
    //        'name'        => 'Ostrich egg',
    //    ],
    //],
],


/**
 * Таблиця "Страны" `recipe`
 */

'recipe' => [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Рецепти',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'user_id'   => ['type' => 'integer', 'comment' => 'Користувач'],
        'serves'    => ['type' => 'integer', 'comment' => 'Кількість порцій',],
        'prepare_at'   => ['type' => 'integer', 'comment' => 'Час підготовки, хв.',],
        'cooking_at'   => ['type' => 'integer', 'comment' => 'Час приготування, хв.',],
        'calorific' => ['type' => 'integer', 'comment' => 'Калорійність, ккал/100г',],
        'glycemic'  => ['type' => 'integer', 'comment' => 'Глікемічний індекс, ХО',],
        'active'    => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],

        'created_at' => ['type' => 'integer', 'comment' => 'Cтворено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'user_id',
        'active',
    ],

    'gridColumns'    => [
        'id',
        'user_id',
        [
            'attribute' => 'user_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->user ?? null;
                if ($modelProperty) {
                    $out .=  $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'user',
        ],
        [
            'attribute' => 'name',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
        'prepare_at',
        'cooking_at',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
        'description:raw',
    ],
    'formFields'     => [
        [
            [
                'user_id',
                'type'  => 'relatedOneAjax',
                'rel'   => 'user',
                'table' => 'user',
                'name'  => 'name',
            ],
        ],
        [['name', 'type' => 'text',],],
        [
            [
                'description',
                'label' => 'Опис',
                'type'  => 'editor',
            ],
        ],
        [['prepare_at', 'type' => 'text',],],
        [['cooking_at', 'type' => 'text',],],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations' => [
        'user' => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
        'translations'       => [
            'multiple'  => true,
            'table'     => 'recipe_translation',
            'attribute' => 'recipe_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'name' => null,
                'description' => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name);
            },
        ],
    ],
    // 'fill'        => [
    //     [
    //         'id'         => 1,
    //         'prepare_at' => 5,
    //         'cooking_at' => 10,
    //         'active'     => 1,
    //     ],
    // ],
],


/**
 * Таблиця "" `recipe_product_assignment`
 */

'recipe_product_assignment' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'     => '',
    'columns'   => [
        'recipe_id'  => ['type' => 'integer', 'required' => true, 'comment' => 'Рецепт',],
        'product_id' => ['type' => 'integer', 'required' => true, 'comment' => 'Продукт',],
        'quantity'   => ['type' => 'integer', 'required' => true, 'comment' => 'Кількість',],
    ],
    'PRIMARY'   => 'recipe_id, product_id',
    'index' => [
        'quantity',
    ],
    'relations' => [
        'recipe' => [
            'table'     => 'recipe',
            'attribute' => 'recipe_id',
        ],
        'product' => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
    ],
    'fill'        => [
        [
            'recipe_id'    => 1,
            'product_id' => 1,
            'quantity'   => 1,
        ],
    ],
],


/**
 * Таблиця "" `recipe_translation`
 */

'recipe_translation' => [
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
],


/**
 * Таблиця "Oдиниці виміру продуктів" `unit`
 */

'unit' => [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Oдиниці виміру',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'active' => ['type' => 'boolean', 'default' => 1, 'comment' => 'Активно'],
    ],
    'index' => [
        'active',
    ],

    'gridColumns'    => [
        'id',
        [
            'attribute' => 'name',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->name . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
        'name',
        'active:boolean',
    ],
    'viewAttributes' => [
        'gridColumns',
        [
            'attribute' => 'description',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $languageIds = \ripaym1970\autocrud\models\crud\LanguageModel::activeIds();
                $show = !(count($languageIds) == 1 && $languageIds[0] == 'uk');
                foreach ($model->translations as $translation) {
                    $translationLanguageId = $translation->language_id;
                    if (in_array($translationLanguageId, $languageIds)) {
                        $out .= ($show ? $translationLanguageId . ', ' : '') . $translation->description . '<br>';
                    }
                }
                return $out ?: '<span class="not-set">(' . ripaym1970\autocrud\components\Yiit::t('не задано') . ')</span>';
            },
        ],
    ],
    'formFields'     => [
        [['name', 'type' => 'text',],],
        [['description', 'type' => 'editor',],],
        [['active', 'type' => 'checkbox',],],
    ],
    'relations'      => [
        'translations'       => [
            'multiple'  => true,
            'table'     => 'unit_translation',
            'attribute' => 'unit_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'name'        => null,
                'description' => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->name);
            },
        ],
    ],
    //'fill'        => [
    //    [
    //        'id'      => 1,
    //        'active'  => 1,
    //    ],
    //    [
    //        'id'      => 2,
    //        'active'  => 1,
    //    ],
    //],
],


/**
 * Таблиця "" `unit_translation`
 */

'unit_translation' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Oдиниці виміру',
    'columns'        => [
        'unit_id'     => ['type' => 'integer', 'comment' => 'Oдиниця виміру',],
        'language_id' => ['type' => 'string', 'size' => 2, 'default' => 'uk', 'comment' => 'Мова',],

        'name' => [
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
            'comment'     => 'Опис',
        ],
    ],
    'PRIMARY' => 'unit_id, language_id', // Строка, только одна
    'relations' => [
        'unit'     => [
            'table'     => 'unit',
            'attribute' => 'unit_id',
        ],
        'language' => [
            'table'     => 'language',
            'attribute' => 'language_id',
            'type'      => 'string',
        ],
    ],
    //'fill' => [
    //    [
    //        'unit_id'  => 1,
    //        'language_id' => 'uk',
    //        'name'        => 'шт',
    //    ],
    //    [
    //        'unit_id'  => 1,
    //        'language_id' => 'en',
    //        'name'        => 'piece',
    //    ],
    //    [
    //        'unit_id'  => 2,
    //        'language_id' => 'uk',
    //        'name'        => 'кг',
    //    ],
    //    [
    //        'unit_id'  => 2,
    //        'language_id' => 'en',
    //        'name'        => 'kg',
    //    ],
    //],
],


/**
 * Таблиця "Продукти користувача" `user_product`
 */

'user_product' => [
    //'crud_menu' => false,
    //'crud_edit' => false,
    'title'     => 'Продукти користувача',
    'columns'   => [
        'id' => ['type' => 'integer', 'comment' => 'ID'],

        'user_id'    => ['type' => 'integer', 'required' => true, 'comment' => 'Користувач',],
        'product_id' => ['type' => 'integer', 'required' => true, 'comment' => 'Продукт',],
        'quantity'   => ['type' => 'integer', 'required' => true, 'comment' => 'Кількість',],
    ],
    'index' => [
        'user_id',
        'product_id',
    ],
    'gridColumns'    => [
        'id',
        [
            'attribute' => 'user_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->user ?? null;
                if ($modelProperty) {
                    $out .=  $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'user',
        ],
        [
            'attribute' => 'product_id',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = '';
                $modelProperty = $model->product ?? null;
                if ($modelProperty) {
                    $out .=  $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
            'filter' => 'distinct',
            'filter_class_name' => 'product',
        ],
        [
            'attribute' => 'quantity',
            'format'    => 'raw',
            'value'     => function ($model) {
                $out = $model->quantity;
                $modelProperty = $model->product->unit ?? null;
                if ($modelProperty) {
                    $out .= ' ' . $modelProperty->name . '<br>';
                }
                return $out ?: '<span class="not-set">(не задано)</span>';
            },
        ],
    ],
    'viewAttributes' => [
        'gridColumns',
    ],
    'relations' => [
        'user' => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
        'product' => [
            'table'     => 'product',
            'attribute' => 'product_id',
        ],
    ],
    //'fill'        => [
    //    [
    //        'user_id'    => 1,
    //        'product_id' => 1,
    //        'quantity'   => 5,
    //    ],
    //],
],


/**
 * Таблиця "Користувач - Профіль" `user_profile`
 */

'user_profile' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title' => 'Користувач - Профіль',
    'columns' => [
        'id' => ['type' => 'integer', 'comment' => 'ID',],

        'user_id' => ['type' => 'integer', 'comment' => 'Користувач',],

        //'gender'           => ['type' => 'integer', 'comment' => 'Стать',],
        //'birth_date'       => ['type' => 'integer', 'comment' => 'Дата народження',],
        //'birth_country_id' => ['type' => 'integer', 'comment' => 'Страна народження',],

        'email'     => ['type' => 'string', 'comment' => 'E-mail',],
        'phone'     => ['type' => 'string', 'comment' => 'Телефон',],
        'facebook'  => ['type' => 'string', 'comment' => 'Facebook',],
        'instagram' => ['type' => 'string', 'comment' => 'Instagram',],
        'telegram'  => ['type' => 'string', 'comment' => 'Telegram',],
        'linkedin'  => ['type' => 'string', 'comment' => 'LinkedIn',],
        'skype'     => ['type' => 'string', 'comment' => 'Skype',],

        'created_at' => ['type' => 'integer', 'comment' => 'Створено',],
        'updated_at' => ['type' => 'integer', 'comment' => 'Змінено',],
    ],
    'index' => [
        'user_id',
    ],
    'defaultOrder' => ['created_at' => SORT_DESC],
    'gridColumns' => [
        'id',
        'user_id',
        'email',
        'phone',

        'gender',
        //'birth_date',
        //'birth_country_id',
        //'birth_place',

        'facebook',
        'instagram',
        'telegram',
        'linkedin',
        'skype',

        'trailer_uri',

        'created_at:datetime',
    ],
    'viewAttributes' => [
        'id',
        'user_id',

        'email',
        'phone',

        'gender',
        //'birth_date',
        //'birth_country_id',
        //'birth_place',

        'facebook',
        'instagram',
        'telegram',
        'linkedin',
        'skype',

        'trailer_uri',

        'created_at:datetime',
        'updated_at:datetime',
    ],
    'formFields' => [
        [['id', 'type' => 'text', 'width' => 12,]],
        [['user_id', 'type' => 'text', 'width' => 12,]],

        [['email', 'type' => 'text', 'width' => 12,]],
        [['phone', 'type' => 'text', 'width' => 12,]],

        [['gender', 'type' => 'text', 'width' => 12,]],
        //[['birth_date', 'type' => 'text', 'width' => 12,]],
        //[['birth_country_id', 'type' => 'text', 'width' => 12,]],
        //[['birth_place', 'type' => 'text', 'width' => 12,]],

        [['facebook', 'type' => 'text', 'width' => 12,]],
        [['instagram', 'type' => 'text', 'width' => 12,]],
        [['telegram', 'type' => 'text', 'width' => 12,]],
        [['linkedin', 'type' => 'text', 'width' => 12,]],
        [['skype', 'type' => 'text', 'width' => 12,]],

        [['trailer_uri', 'type' => 'text', 'width' => 12,]],
     ],
    'relations' => [
        'user'               => [
            'table'     => 'user',
            'attribute' => 'user_id',
        ],
        'images'             => [
            'multiple'  => true,
            'table'     => 'user_profile_image',
            'attribute' => 'user_profile_id',
        ],
        'translations'       => [
            'multiple'  => true,
            'table'     => 'user_profile_translation',
            'attribute' => 'user_profile_id',
        ],
        'defaultTranslation' => function ($model) {
            return $model->hasDefaultVariationRelation();
        },
    ],
    'behaviors'      => [
        'translations'  => [
            'class'                             => yii2tech\ar\variation\VariationBehavior::class,
            'variationsRelation'                => 'translations',
            'defaultVariationRelation'          => 'defaultTranslation',
            'variationOptionReferenceAttribute' => 'language_id',
            'optionModelClass'                  => ripaym1970\autocrud\models\crud\LanguageModel::class,
            'defaultVariationOptionReference'   => function () {
                return Yii::$app->language;
            },
            'variationAttributeDefaultValueMap' => [
                'first_name' => null,
                'middle_name' => null,
                'last_name' => null,
                //'birth_place' => null,
                'address' => null,
            ],
            // Следует ли сохранять конкретную вариационную модель
            'variationSaveFilter' => function ($model) {
                // Если есть хоть одно свойство
                return !empty($model->first_name) ||  !empty($model->last_name);
            },
        ],
    ],
],


/**
 * Таблиця "Користувач - Зображення" `user_profile_image`
 */

'user_profile_image' => [
    'crud_menu' => false,
    'crud_edit' => false,
    'title'          => 'Користувач - Зображення',
    'columns'        => [
        'id' => ['type' => 'integer', 'comment' => 'ID',],

        'user_profile_id' => ['type' => 'integer', 'comment' => 'Користувач'],
        'sort'            => ['type' => 'integer', 'comment' => 'Позиція',],
        'file'            => ['type' => 'string',  'comment' => 'Зображення'],
    ],
    'composite' => [
        [
            'user_profile_id',
            'sort',
        ],
    ],
    'relations' => [
        'user_profile'   => [
            'table'     => 'user_profile',
            'attribute' => 'user_profile_id',
        ],
    ],
    'behaviors'      => [
        'imageUpload'  => [
            // common/components/extend/yiidreamteam/README.md
            'class' => ripaym1970\autocrud\components\extend\yiidreamteam\ImageUploadBehavior::class,
            'createThumbsOnRequest' => true,
            //
            'attribute' => 'file',
            // Заменяет в имени картинки значение [[profile]]. profile - не менять!
            'thumbs' => [
                'thumb'   => ['width' => 100, 'height' => 70],
                //'39x39'   => ['width' => 39, 'height' => 39], // header avatar
                //'113x94'  => ['width' => 113, 'height' => 94],
                //'138x156' => ['width' => 138, 'height' => 156],
                //'170x202' => ['width' => 170, 'height' => 202], // form load
            ],
            // Путь где будут сохранены преобразованные фото
            'thumbPath' => '@uploadsPath/cache/user_profile/[[attribute_user_profile_id]]/[[filename]].[[extension]]',
            // Ссылка на сохрененные преобразованные фото
            'thumbUrl'  => '@uploadsUrl/cache/user_profile/[[attribute_user_profile_id]]/[[filename]].[[extension]]',

            // Путь где будет сохранен оригинал фото
            'filePath'  => '@uploadsPath/original/user_profile/[[attribute_user_profile_id]]/[[filename]].[[extension]]',
            // Ссылка на сохрененный оригинал фото
            'fileUrl'   => '@uploadsUrl/original/user_profile/[[attribute_user_profile_id]]/[[filename]].[[extension]]',
        ],
    ],
],


/**
 * Таблиця "Варіації для user_profile" `user_profile_translation`
 */

'user_profile_translation' => [
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
],

];
