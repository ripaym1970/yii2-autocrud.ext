<?php

namespace ripaym1970\autocrud\components\modules\Grid\assets;

class Asset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__;

    public $css = [
        'css/grid.css',
    ];

    public $js = [
        'js/kendoGrid.js',
        'js/gridProfiles.js',
        'js/customFilter.js',
    ];

    public $depends = [
        \ripaym1970\autocrud\components\assets\vendor\JqueryUi::class,
        \ripaym1970\autocrud\components\assets\Filter::class,
        \ripaym1970\autocrud\components\assets\vendor\Telerik::class,

        \ripaym1970\autocrud\components\Marionette\Asset::class,
        \ripaym1970\autocrud\components\HtmlActions\Asset::class,

        \ripaym1970\autocrud\components\assets\vendor\EasyTimerAsset::class,

        DetailColumn::class,
    ];
}
