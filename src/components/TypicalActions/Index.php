<?php

namespace ripaym1970\autocrud\components\TypicalActions;

class Index extends AbstractAction
{
    public $view = 'index';
    /**
     * @var \ripaym1970\autocrud\components\modules\Grid\Helper|callable $gridHelper
     */
    public $gridHelper;

    public function run()
    {
        /** @var \ripaym1970\autocrud\components\modules\Grid\Helper|callable $helper */
        $helper = is_callable($this->gridHelper)
            ? call_user_func($this->gridHelper)
            : $this->gridHelper;

        if (!\yii::$app->request->isGet) {
            $this->invalidMethod();
        }

        if (!\yii::$app->request->isAjax) {
            $viewParams = [];
            if ($helper) {
                $viewParams['gridHelper'] = $helper;
            }
            return $this->renderView(null, $viewParams);
        }

        if ($helper) {
            return $this->controller->asJson($helper->processRequest());
        }

        $this->invalidMethod();
    }
}
