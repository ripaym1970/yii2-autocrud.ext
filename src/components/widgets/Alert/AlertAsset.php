<?php
/**
 * Alert без использования bootstrap.css
 *
 * Использовать
 * ripaym1970\autocrud\components\widgets\AlertAsset::register($this);
 *
 * echo ripaym1970\autocrud\components\widgets\Alert::widget();
 */

namespace ripaym1970\autocrud\components\widgets\Alert;

use yii\web\AssetBundle;
use yii\web\View;

class AlertAsset extends AssetBundle
{
    public $sourcePath = __DIR__;

    public $css = [
        'css/alert.css',
    ];

    //public $js = [
    //    'js/alert.js',
    //];

    // все скрипты будут перенесены в конец
    public $jsOptions = [
        'position' => View::POS_END
    ];

    public $depends = [
        //'yii\web\YiiAsset',             // Проект без JQuery
        //'yii\bootstrap5\BootstrapAsset', // Проект без Bootstrap
    ];
}
