<?php
/**
 * Использовать на странице где нужен пагинаотр
 * use frontend\assets\LSPagerAsset; // Надо для PHP-пагинатора
 * LSPagerAsset::register($this); // Надо для PHP-пагинатора
 */

namespace ripaym1970\autocrud\components\mail\components\widgets\Pager;

use yii\web\AssetBundle;
use yii\web\View;

class PagerAsset extends AssetBundle
{
    public $sourcePath = __DIR__;

    public $css = [
        'css/pager.css', // надо для PHP/JS-пагинатора
    ];

    public $js = [
        'js/pager.js', // Надо для JS-части пагинатора
    ];

    // все скрипты будут перенесены в конец
    public $jsOptions = [
        'position' => View::POS_END
    ];

    public $depends = [
        //'yii\web\YiiAsset',             // Проект без JQuery
        //'yii\bootstrap5\BootstrapAsset', // Проект без Bootstrap
    ];
}
