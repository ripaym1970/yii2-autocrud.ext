<?php

namespace ripaym1970\autocrud\models\behaviors;

use yii\db\Exception;

class NestedSetsBehavior extends \creocoder\nestedsets\NestedSetsBehavior
{
    protected function beforeInsertRootNode()
    {
        if ($this->treeAttribute === false && $this->owner->find()->roots()->exists()) {
            throw new Exception('Can not create more than one root when "treeAttribute" is false.');
        }

        $this->owner->setAttribute($this->leftAttribute, 1);
        $this->owner->setAttribute($this->rightAttribute, 2);
        $this->owner->setAttribute($this->depthAttribute, 1);
    }
}
