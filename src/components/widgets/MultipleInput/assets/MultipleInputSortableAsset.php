<?php

namespace ripaym1970\autocrud\components\widgets\MultipleInput\assets;

use yii\web\AssetBundle;

class MultipleInputSortableAsset extends AssetBundle
{
    public $depends = [
        'ripaym1970\autocrud\components\widgets\MultipleInput\assets\MultipleInputAsset',
    ];

    public function init()
    {
        $this->sourcePath = __DIR__ . '/src/';

        $this->js = [
            YII_DEBUG ? 'js/jquery-sortable.js' : 'js/jquery-sortable.min.js',
        ];

        $this->css = [
            YII_DEBUG ? 'css/sorting.css' : 'css/sorting.min.css',
        ];

        parent::init();
    }
}
