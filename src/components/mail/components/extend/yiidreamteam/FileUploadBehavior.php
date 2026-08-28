<?php

namespace ripaym1970\autocrud\components\mail\components\extend\yiidreamteam;

use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;
use yii\web\UploadedFile;

/**
 * @property ActiveRecord $owner
 */
class FileUploadBehavior extends \yii\base\Behavior
{
    public const EVENT_AFTER_FILE_SAVE = 'afterFileSave';

    /** @var string Имя атрибута, который содержит вложение */
    public $attribute = 'upload';

    /** @var string Шаблон пути для хранения файлов */
    public $filePath = '@webroot/uploads/[[pk]].[[extension]]';

    /** @var string Где хранить изображения */
    public $fileUrl = '/uploads/[[pk]].[[extension]]';

    /**
     * @var string Атрибут, используемый для связи модели владельца с ее родителем
     * @deprecated Use attribute_xxx placeholder instead
     */
    public $parentRelationAttribute;

    /** @var \yii\web\UploadedFile */
    protected $file;

    /**
     * @return string[]
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * Событие перед проверкой
     */
    public function beforeValidate()
    {
        if ($this->owner->{$this->attribute} instanceof UploadedFile) {
            $this->file = $this->owner->{$this->attribute};
            return;
        }

        $this->file = UploadedFile::getInstance($this->owner, $this->attribute);

        if (empty($this->file)) {
            $this->file = UploadedFile::getInstanceByName($this->attribute);
        }

        if ($this->file instanceof UploadedFile) {
            $this->owner->{$this->attribute} = $this->file;
        }
    }

    /**
     * Событие перед сохранением
     *
     * @throws \Exception
     */
    public function beforeSave()
    {
        if ($this->file instanceof UploadedFile) {
            if (true !== $this->owner->isNewRecord) {
                /** @var ActiveRecord $oldModel */
                $oldModel = $this->owner->findOne($this->owner->primaryKey);
                $behavior = static::getInstance($oldModel, $this->attribute);
                $behavior->cleanFiles();
            }

            $this->owner->{$this->attribute} = implode(
                '.',
                array_filter([$this->file->baseName, $this->file->extension])
            );
        } else {
            if (true !== $this->owner->isNewRecord && empty($this->owner->{$this->attribute})) {
                $this->owner->{$this->attribute} = ArrayHelper::getValue(
                    $this->owner->oldAttributes,
                    $this->attribute,
                    null
                );
            }
        }
    }

    /**
     * Возвращает экземпляр поведения для указанного объекта и атрибута
     *
     * @param Model $model
     * @param string $attribute
     *
     * @return static
     */
    public static function getInstance(Model $model, $attribute)
    {
        foreach ($model->behaviors as $behavior) {
            if ($behavior instanceof self && $behavior->attribute == $attribute) {
                return $behavior;
            }
        }
        //echo '<pre>';var_dump('$model=',$model);echo '</pre>';exit();
        throw new InvalidCallException('Missing behavior for attribute ' . VarDumper::dumpAsString($attribute));
    }

    /**
     * Удаляет файлы, связанные с атрибутом
     */
    public function cleanFiles()
    {
        $path = $this->resolvePath($this->filePath);
        @unlink($path);
    }

    /**
     * Заменяет все заполнители в переменной пути соответствующими значениями
     *
     * @param string $path
     *
     * @return string
     * @throws \ReflectionException
     */
    public function resolvePath($path)
    {
        $path = Yii::getAlias($path);

        //dd($path, $this->owner, $this->attribute);

        $pi = pathinfo($this->owner->{$this->attribute});
        $fileName = ArrayHelper::getValue($pi, 'filename');
        //echo '<pre>';var_dump($fileName);echo '</pre>';exit();
        $extension = strtolower(ArrayHelper::getValue($pi, 'extension'));

        return preg_replace_callback('|\[\[([\w\_/]+)\]\]|', function ($matches) use ($fileName, $extension) {
            $name = $matches[1];
            switch ($name) {
                case 'extension':
                    return $extension;
                case 'filename':
                    return $fileName;
                case 'basename':
                    return implode('.', array_filter([$fileName, $extension]));
                case 'app_root':
                    return Yii::getAlias('@app');
                case 'web_root':
                    return Yii::getAlias('@webroot');
                case 'base_url':
                    return Yii::getAlias('@web');
                case 'model':
                    $r = new \ReflectionClass($this->owner->class);
                    return lcfirst($r->getShortName());
                case 'attribute':
                    return lcfirst($this->attribute);
                case 'id':
                case 'pk':
                    $pk = implode('_', $this->owner->getPrimaryKey(true));
                    return lcfirst($pk);
                case 'id_path':
                    return static::makeIdPath($this->owner->getPrimaryKey());
                case 'parent_id':
                    return $this->owner->{$this->parentRelationAttribute};
            }
            if (preg_match('|^attribute_(\w+)$|', $name, $am)) {
                $attribute = $am[1];
                return $this->owner->{$attribute};
            }
            if (preg_match('|^md5_attribute_(\w+)$|', $name, $am)) {
                $attribute = $am[1];
                return md5($this->owner->{$attribute});
            }
            return '[[' . $name . ']]';
        }, $path);
    }

    /**
     * @param int $id
     *
     * @return string
     */
    protected static function makeIdPath($id)
    {
        $id = is_array($id) ? implode('', $id) : $id;
        $length = 10;
        $id = str_pad($id, $length, '0', STR_PAD_RIGHT);

        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[] = substr($id, $i, 1);
        }

        return implode('/', $result);
    }

    /**
     * Событие после сохранения
     */
    public function afterSave()
    {
        if ($this->file instanceof UploadedFile !== true) {
            return;
        }

        $path = $this->getUploadedFilePath($this->attribute);

        FileHelper::createDirectory(pathinfo($path, PATHINFO_DIRNAME), 0777, true);

        if (!$this->file->saveAs($path)) {
            throw new FileUploadException($this->file->error, 'File saving error.');
        }

        $this->owner->trigger(static::EVENT_AFTER_FILE_SAVE);
    }

    /**
     * Возвращает путь к файлу для атрибута
     *
     * @param string $attribute
     *
     * @return string
     */
    public function getUploadedFilePath($attribute)
    {
        $behavior = static::getInstance($this->owner, $attribute);

        if (!$this->owner->{$attribute}) {
            return '';
        }

        return $behavior->resolvePath($behavior->filePath);
    }

    /**
     * Событие перед удалением
     */
    public function beforeDelete()
    {
        $this->cleanFiles();
    }

    /**
     * Возвращает URL-адрес файла для атрибута
     *
     * @param string $attribute
     *
     * @return string|null
     */
    public function getUploadedFileUrl($attribute)
    {
        if (!$this->owner->{$attribute}) {
            return null;
        }

        // Запускаем поведение
        $behavior = static::getInstance($this->owner, $attribute);

        // Получаем путь к файлу по шаблону поведения
        // '/images/original/location/[[attribute_location_id]]/[[filename]].[[extension]]'
        return $behavior->resolvePath($behavior->fileUrl);
    }
}
