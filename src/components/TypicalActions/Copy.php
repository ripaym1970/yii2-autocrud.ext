<?php

namespace ripaym1970\autocrud\components\TypicalActions;

class Copy extends \yii\base\Action
{
    public function run($id)
    {
        $this->controller->findModel($id)->cloneRecord();
        \ripaym1970\autocrud\components\Util::noContent();
    }
}
