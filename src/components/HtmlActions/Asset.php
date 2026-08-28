<?php

namespace ripaym1970\autocrud\components\HtmlActions;

class Asset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__;
    public $css = [
        'css/HtmlActions.css',
    ];

    public $js = [
        'js/HtmlActions.js',
    ];

    public $depends = [
        //\ripaym1970\autocrud\components\assets\vendor\JqueryUi::class,
        //\ripaym1970\autocrud\components\Marionette\Asset::class,
        //\ripaym1970\autocrud\components\assets\vendor\JqueryForm::class,
        //\ripaym1970\autocrud\components\modules\Translations\asset\TranslationAsset::class,
        //\unclead\multipleinput\assets\MultipleInputAsset::class,
    ];

    public $publishOptions = [
        'except' => [
            "*.coffee",
            "*.php",
        ],
    ];
}
