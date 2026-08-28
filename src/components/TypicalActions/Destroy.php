<?php

namespace ripaym1970\autocrud\components\TypicalActions;

class Destroy extends AbstractAction
{
    /**  @var callable|null $beforeDelete */
    public $beforeDelete;
    /**  @var callable|null $afterDelete */
    public $afterDelete;

    public function run($id)
    {
        $model = $this->controller->findModel($id);

        $transaction = \ripaym1970\autocrud\components\Util::makeTransaction();

        // the syntax for calling method is used intentionally, because
        // sometimes method can even replace $model
        if (
            is_callable($this->beforeDelete)
            && !($this->beforeDelete)($model)
        ) {
            throw new \yii\base\Exception(
                "Before delete callback failed"
            );
        }

        \ripaym1970\autocrud\components\Util::deleteModel($model);

        // the syntax for calling method is used intentionally, because
        // sometimes method can even replace $model
        if (
            is_callable($this->afterDelete)
            && !($this->afterDelete)($model)
        ) {
            throw new \yii\base\Exception(
                "After delete callback failed"
            );
        }

        $transaction && $transaction->commit();
        \ripaym1970\autocrud\components\Util::noContent();
    }
}
