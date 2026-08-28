<?php

namespace ripaym1970\autocrud\components\TypicalActions;

/** @inheritdoc */
class Edit extends AbstractEditCreate
{
    public function run($id)
    {
        return $this->process($this->controller->findModel($id));
    }
}
