<?php

namespace ripaym1970\autocrud\models\forms;

use ripaym1970\autocrud\components\Yiit;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * @property array files
 */
class ImageForm extends Model
{
    /** @var UploadedFile[] */
    public $files;

    public function rules(): array
    {
        return [
            ['files', 'each', 'rule' => ['image']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'files' => Yiit::t('Файл з фото'),
        ];
    }

    public function beforeValidate(): bool
    {
        if (parent::beforeValidate()) {
            $this->files = UploadedFile::getInstances($this, 'files');

            return true;
        }

        return false;
    }
}
