<?php

namespace ripaym1970\autocrud\components;

abstract class AbstractModule extends \yii\base\Module
{
    public function init()
    {
        if (\ripaym1970\autocrud\components\Util::isInConsoleMode()) {
            $namespace = (new \ReflectionClass($this))->getNamespaceName();
            $this->controllerNamespace = $namespace . '\commands';
        }
        return parent::init();
    }
}
