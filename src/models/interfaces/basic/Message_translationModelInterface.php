<?php

namespace ripaym1970\autocrud\models\interfaces\basic;

use ripaym1970\autocrud\models\interfaces\basic\VariationModelInterface;

/**
 * This is the interface class for table "message_translation".
 *
 * @property int    message_id  [int(11)]       Повідомлення
 * @property string translation [varchar(255)]  Переклад
 */
class Message_translationModelInterface extends VariationModelInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message_translation';
    }
}
