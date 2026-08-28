<?php

/**
 * Таблиця "Переводы для "Города" `city_translation`
 */

return [
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
];
