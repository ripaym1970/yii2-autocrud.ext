<?php

namespace ripaym1970\autocrud\models\interfaces\basic;

use yii\web\UploadedFile;

/**
 * This is the interface class for table "*_image".
 *
 * @property int    id
 * @property int    sort             Порядок
 * @property string file             Файл
 */
class ImageModelInterface extends \yii\db\ActiveRecord
    implements \yii\db\ActiveRecordInterface
{
    public static function create(UploadedFile $file): self
    {
        return new self();
    }
}
