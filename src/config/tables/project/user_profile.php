<?php

/**
 * Таблиця "Користувач - Профіль" `user_profile`
 */

return [
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
];
