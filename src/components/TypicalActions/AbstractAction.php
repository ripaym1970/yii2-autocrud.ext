<?php

namespace ripaym1970\autocrud\components\TypicalActions;

abstract class AbstractAction extends \yii\base\Action
{
    public $view;
    /**
     * @var array|callable $additionalViewParams
     */
    public $additionalViewParams = [];

    protected function renderView($model = null, array $additionalParams = [])
    {
        $params = \yii\helpers\ArrayHelper::merge(
            $additionalParams,
            is_callable($this->additionalViewParams)
                ? call_user_func($this->additionalViewParams, $model)
                : $this->additionalViewParams,
            $model
                ? ['model' => $model]
                : []
        );
        $method = \yii::$app->request->isAjax
            ? 'renderAjax'
            : 'render';

        return $this->controller->$method($this->view, $params);
    }

    protected function invalidMethod()
    {
        throw new \yii\web\HttpException(405, "Invalid method");
    }
}
