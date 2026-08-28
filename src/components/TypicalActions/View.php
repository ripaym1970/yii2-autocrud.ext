<?php

namespace ripaym1970\autocrud\components\TypicalActions;

/** @inheritdoc */
class View extends AbstractAction
{
    public $view = 'view';

    public function run($id)
    {
        if (!\yii::$app->request->isGet) {
            $this->invalidMethod();
        }

        return $this->renderView($this->controller->findModel($id));
    }
}
