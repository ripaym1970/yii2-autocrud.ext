<?php

namespace ripaym1970\autocrud\components\actions;

use yii\helpers\ArrayHelper;

class ErrorAction extends \yii\web\ErrorAction
{
    protected function getViewRenderParams()
    {
        return ArrayHelper::merge(parent::getViewRenderParams(), [
            'code'  => $this->exception ? $this->exception->statusCode : null,
            'file'  => $this->exception ? $this->exception->getFile() : null,
            'line'  => $this->exception ? $this->exception->getLine() : null,
            'trace' => $this->exception ? $this->exception->getTraceAsString() : null,
        ]);
    }
}
