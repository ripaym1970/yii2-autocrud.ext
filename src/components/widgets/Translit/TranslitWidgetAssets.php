<?php

namespace ripaym1970\autocrud\components\widgets\Translit;

use yii\web\AssetBundle;

class TranslitWidgetAssets extends AssetBundle
{
    public $sourcePath = '@common/components/widgets/Translit/assets';

    public $js = [
        'jquery.liTranslit.js',
    ];
}
