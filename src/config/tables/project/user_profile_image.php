<?php

/**
 * Таблиця "Користувач - Зображення" `user_profile_image`
 */

return [
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
];
