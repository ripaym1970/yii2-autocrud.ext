<?php

namespace ripaym1970\autocrud\models\forms;

use ripaym1970\autocrud\components\Yiit;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * @property array $files
 */
class ImagesForm extends Model
{
    /** @var UploadedFile[] */
    public array $files = [];

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            ['files', 'each', 'rule' => ['image']],
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels()
    {
        return [
            'files' => Yiit::t('Файли з фото'),
        ];
    }

    public function beforeValidate(): bool
    {
        if (parent::beforeValidate()) {
            $this->files = UploadedFile::getInstances($this, 'files');
            //$notSaved = '';
            //foreach ($this->files as $file) {
            //    $fileName = $file->name;
            //    // Перемещаем файл с кеша на диск
            //    if (!$file->saveAs(\yii\helpers\Url::to("@uploadsPath/original/$tableName/$id/$fileName"))) {
            //        $notSaved .= $fileName . ', ' ;
            //    }
            //}
            //return $notSaved == '';
            return true;
        }
        return false;
    }
}
