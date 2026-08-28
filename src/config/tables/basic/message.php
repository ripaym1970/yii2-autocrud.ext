<?php

/**
 * Таблиця "Ключи сообщений для Yiit::t($message, $category)" `message`
 *
 * Якщо декілька однакових повідомлень відрізняються відмінком (падежом) в другій мові,
 * то змінивши $category зможете конкретизувати місце.
 * Не забудьте додати category в код!
 */

return [
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
];
