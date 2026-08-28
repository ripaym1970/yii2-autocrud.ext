<?php

namespace ripaym1970\autocrud\components\widgets\MultipleInput\assets;

use yii\web\AssetBundle;

class MaskedInputAsset extends AssetBundle
{
    public $depends = [
        'yii\web\JqueryAsset',
    ];

    public function __construct($config = [])
    {
        $config = array_merge(
            [
                'sourcePath' => __DIR__ . '/src/',
                'js'         => [
                    YII_DEBUG ? 'js/jquery.inputmask.js' : 'js/jquery.inputmask.min.js',
                ],
            ],
            $config
        );

        parent::__construct($config);
    }
}
