<?php

/**
 * UUID Behavior will set your ID with UUID
 *
 * https://github.com/mootensai/yii2-uuid-behavior/tree/master
 *
 * Простіше
 *  class MyModel extends ActiveRecord{
 *      public function rules() {
 *          return [
 *              ...,
 *              ['id', 'default', 'value' => $this->getDb()->createCommand("REPLACE(UUID(),'-','')")->queryScalar()],
 *              ...,
 *          ]
 *      }
 *  }
 */

namespace ripaym1970\autocrud\components\behaviors\UUIDBehavior;

use yii\base\Behavior;
use yii\base\Event;
use yii\db\BaseActiveRecord;

class UUIDBehavior extends Behavior
{
    /**
     * Field/Column yang akan diisi UUID
     * Default -> id
     *
     * @var string
     */
    public $column = 'id';

    /**
     * Override event()
     * memasukkan method beforeSave() kedalam komponen ActiveRecord::EVENT_BEFORE_INSERT
     *
     * @return array<string, string>
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
        ];
    }

    /**
     * set beforeSave() -> UUID data
     *
     * @param Event $event
     */
    public function beforeSave(Event $event): void
    {
        if ($this->owner->getAttribute($this->column) !== null
            && $this->owner->getAttribute($this->column) !== ''
        ) {
            return;
        }

        $this->owner->setAttribute($this->column, bin2hex(random_bytes(16)));
    }
}
