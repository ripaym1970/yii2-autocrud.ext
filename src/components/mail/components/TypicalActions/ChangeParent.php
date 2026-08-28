<?php

namespace ripaym1970\autocrud\components\mail\components\TypicalActions;

/**
 * @property \ripaym1970\autocrud\components\behaviors\Finder|\ripaym1970\autocrud\components\TypicalController $controller
 */
class ChangeParent extends \yii\base\Action
{
    public $parentIdField = 'parent_id';
    /** @var callable|null */
    public $beforeSave;

    public function run()
    {
        /** @var \yii\db\ActiveRecord $name */
        $name = ($this->controller)::MODEL_CLASS;
        $shortName = \ripaym1970\autocrud\components\Util::getShortClassName($name);

        $model = $this->controller->findModel(
            $_REQUEST[$shortName . '__' . $name::primaryKey()[0]]
        );

        $newParentId = $_REQUEST[$shortName . '__' . $this->parentIdField];
        if (in_array($newParentId, [-1, 0])) {
            $newParentId = null;
        }
        $model->{$this->parentIdField} = $newParentId;

        $modelSaved = false;
        $onSave = function ($x) use (&$modelSaved) {
            $modelSaved = true;
        };

        $model->on($model::EVENT_AFTER_UPDATE, $onSave);
        $model->on($model::EVENT_AFTER_INSERT, $onSave);

        $transaction = \ripaym1970\autocrud\components\Util::makeTransaction();

        // the syntax for calling method is used intentionally, because
        // sometimes method has to replace $model
        if (is_callable($this->beforeSave) && !($this->beforeSave)($model)) {
            $transaction && $transaction->rollBack();
            throw new \yii\base\UserException("Could not move model");
        }
        if (!$modelSaved) {
            \ripaym1970\autocrud\components\Util::saveModel($model);
        }
        $transaction && $transaction->commit();
        \ripaym1970\autocrud\components\Util::noContent();
    }
}
