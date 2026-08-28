<?php

namespace ripaym1970\autocrud\components\modules\Grid\assets;

class ProfilesManagement extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . '/profiles-management/';

    public $js = [
        'index.js',
    ];

    public $depends = [
        \ripaym1970\autocrud\components\Marionette\Asset::class,
        \ripaym1970\autocrud\components\HtmlActions\Asset::class,
        \ripaym1970\autocrud\components\assets\vendor\UriJs::class,
    ];
}
