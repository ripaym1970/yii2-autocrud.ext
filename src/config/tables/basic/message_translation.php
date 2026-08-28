<?php

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

return [
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
];
