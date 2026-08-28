<?php

namespace ripaym1970\autocrud\models\interfaces\basic;

use ripaym1970\autocrud\models\interfaces\basic\ModelInterface;

/**
 * This is the interface class for table "user".
 *
 * @property string email
 */
class UserModelInterface extends ModelInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }
}
