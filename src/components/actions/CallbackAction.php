<?php

namespace ripaym1970\autocrud\components\actions;

use yii\base\InvalidConfigException;
use yii2tech\admin\actions\Callback;

class CallbackAction extends Callback
{
    public $newModel;

    public function run($id = false)
    {
        if ($id) {
            $model = $this->findModel($id);
        } else {
            $model = $this->newModel();
        }
        if ($this->callback === null) {
            throw new InvalidConfigException('"' . get_class($this) . '::$callback" must be set.');
        }
        if (is_string($this->callback)) {
            call_user_func([$model, $this->callback]);
        } else {
            call_user_func($this->callback, $model);
        }
        $this->setFlash($this->flash, ['id' => $id, 'model' => $model]);
        return $this->controller->redirect($this->createReturnUrl('view', $model));
    }

    public function newModel()
    {
        if ($this->newModel !== null) {
            return call_user_func($this->newModel, $this);
        }
        if ($this->controller->hasMethod('newModel')) {
            return call_user_func([$this->controller, 'newModel'], $this);
        }
        throw new InvalidConfigException('Either "' . get_class($this) . '::$newModel" must be set or controller must declare method "newModel()".');
    }
}
