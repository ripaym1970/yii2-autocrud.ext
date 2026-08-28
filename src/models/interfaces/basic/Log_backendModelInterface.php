<?php

namespace ripaym1970\autocrud\models\interfaces\basic;

/**
 * This is the interface class for table "log_backend".
 *
 * @property int    level    [int(11)]       Рівень
 * @property string category [varchar(255)]  Категорія
 * @property float  log_time [double]        Створено
 * @property string prefix   [text]          Префікс
 * @property string message  [text]          Повідомлення
 */
class Log_backendModelInterface extends ModelInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'log_backend';
    }
}
