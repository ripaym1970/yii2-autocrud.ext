<?php

namespace ripaym1970\autocrud\components\TypicalActions;

/** @inheritdoc */
class Create extends AbstractEditCreate
{
    /** @var callable|null $afterCreate */
    public $afterCreate;

    public function run()
    {
        $name = ($this->controller)::MODEL_CLASS;
        $model = new $name;

        // the syntax for calling method is used intentionally, because
        // sometimes method has to replace $model
        if (is_callable($this->afterCreate) && !($this->afterCreate)($model)) {
            throw new \yii\base\Exception("Creation failed");
        }

        return $this->process($model);
    }
}
