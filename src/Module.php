<?php

namespace ripaym1970\autocrud;

use Yii;

class Module extends \yii\base\Module
{
    public function init()
    {
        if (Yii::$app instanceof \yii\console\Application
            && $this->controllerNamespace === __NAMESPACE__ . '\\controllers'
        ) {
            $this->controllerNamespace = __NAMESPACE__ . '\\console\\controllers';
        }

        parent::init();
    }
}
