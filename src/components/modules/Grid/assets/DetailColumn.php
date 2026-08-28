<?php

namespace ripaym1970\autocrud\components\modules\Grid\assets;

class DetailColumn extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__;

    public $js = [
        'js/detailColumn.js',
    ];

    public $depends = [
        \ripaym1970\autocrud\components\Marionette\Asset::class,
        \ripaym1970\autocrud\components\assets\vendor\Telerik::class,
    ];
}
