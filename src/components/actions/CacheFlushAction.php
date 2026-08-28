<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\components\Yiit;
use yii2tech\admin\actions\Action;

class CacheFlushAction extends Action
{
    public function run()
    {
        $this->setFlash(Yiit::t('Кеш був очищений'));

        return $this->controller->redirect($this->createReturnUrl('index'));
    }
}
