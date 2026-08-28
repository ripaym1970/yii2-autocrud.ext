<?php

namespace ripaym1970\autocrud\models\interfaces\basic;

/**
 * This is the interface class for table "message".
 *
 * @property string category    [varchar(255)]  Категорія
 * @property string message     [varchar(255)]  Повідомлення для перекладу
 *
 * @property string translation [varchar(16)] Варіація
 * @property string language    [varchar(16)]
 */
class MessageModelInterface extends ModelInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message';
    }
}
