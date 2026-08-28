<?php

namespace ripaym1970\autocrud\models\behaviors;

//use ripaym1970\autocrud\components\BaseActiveRecord;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

class SynonymsBehavior extends Behavior
{
    public $attribute = 'synonyms';
    public $separator = '|||';

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
        ];
    }

    public function beforeValidate()
    {
        if (is_array($this->owner->{$this->attribute})) {
            $this->owner->{$this->attribute} = implode($this->separator, $this->owner->{$this->attribute});
        }
    }
}
