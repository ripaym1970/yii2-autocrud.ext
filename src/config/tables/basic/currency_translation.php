<?php

/**
 * Таблиця "Переводы для "Валюти" `currency_translation`
 */

return [
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
];
