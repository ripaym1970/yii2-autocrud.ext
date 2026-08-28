<?php

/**
 * Таблиця "Переводы для "Країни" `country_translation`
 */

return [
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
];
