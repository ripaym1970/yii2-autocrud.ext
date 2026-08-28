<?php

namespace ripaym1970\autocrud\models\behaviors;

use Closure;
use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\web\UploadedFile;

class UploadBehavior extends Behavior
{
    public $unlinkOnSave   = true;
    public $unlinkOnDelete = true;
    public $path           = 'uploads/{model_class_name}/{id}/{file_name}.{extension}';
    public $patterns       = [];
    public $properties     = [];
    public $afterUpload;
    /**
     * @var UploadedFile the uploaded file instance.
     */
    private $_file;

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            BaseActiveRecord::EVENT_BEFORE_INSERT   => 'beforeSave',
            BaseActiveRecord::EVENT_BEFORE_UPDATE   => 'beforeSave',
            BaseActiveRecord::EVENT_AFTER_INSERT    => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_UPDATE    => 'afterSave',
            BaseActiveRecord::EVENT_BEFORE_DELETE   => 'beforeDelete',
        ];
    }

    /**
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file): void
    {
        $this->_file = $file;
    }

    /**
     * This method is invoked before validation starts.
     */
    public function beforeValidate()
    {
        if ($this->_file instanceof UploadedFile) {
            if ($this->owner->hasAttribute('original_name')) {
                $this->owner->original_name = $this->_file->name;
            }
            $pathInfo = pathinfo($this->_file->name);
            $pathInfo['filename'] = Inflector::slug($pathInfo['filename'], '-');
            $this->_file->name = $pathInfo['filename'] . '.' . $pathInfo['extension'];
            $this->owner->file_name = $pathInfo['filename'];
            $this->owner->extension = $pathInfo['extension'];
        }
    }

    /**
     * This method is invoked before deleting a record.
     */
    public function beforeDelete()
    {
        if ($this->unlinkOnDelete) {
            $this->delete();
        }
    }

    protected function delete($old = false)
    {
        $files = glob($this->getUploadPath('*', $old));
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function getUploadPath($old = false, $params = [])
    {
        return Yii::getAlias('@' . $this->getPath($params));
    }

    public function getPath($old = false, $params = [])
    {
        /**
         * @var ActiveRecord $model
         */
        $model = $this->owner;
        $patterns = $this->patterns;
        if (is_string($params) && $patterns) {
            $key = current(array_keys($patterns));
            $params = [
                $key => $params,
            ];
        }
        return preg_replace_callback('/{([^}]+)}/', function ($matches) use ($model, $old, $patterns, $params) {
            $name = $matches[1];
            if (isset($params[$name])) {
                $attribute = $params[$name];
            } elseif (isset($patterns[$name])) {
                $attribute = $patterns[$name];
            } else {
                if ($old) {
                    $attribute = $model->getOldAttribute($name);
                } else {
                    $attribute = $model->getAttribute($name);
                }
            }
            if (is_string($attribute) || is_numeric($attribute)) {
                return strtolower($attribute);
            }
            return $matches[0];
        }, $this->path);
    }

    public function beforeSave()
    {
        if ($this->_file instanceof UploadedFile) {
            if (!$this->owner->getIsNewRecord() && $this->unlinkOnSave === true) {
                $this->delete(true);
            }
        }
    }

    /**
     * This method is called at the end of inserting or updating a record.
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function afterSave()
    {
        if ($this->_file instanceof UploadedFile) {
            $path = $this->getUploadPath();
            if (!FileHelper::createDirectory(dirname($path))) {
                throw new InvalidArgumentException("Directory specified in 'path' attribute doesn't exist or cannot be created.");
            }
            $this->_file->saveAs($path);
            $this->owner->updateAttributes([
                'source_file_md5' => md5_file($path),
            ]);
            if ($this->afterUpload instanceof Closure) {
                call_user_func($this->afterUpload, $this->owner);
            }
        }
    }

    public function getUrl($params = [])
    {
        return '/' . $this->getPath(false, $params);
    }

    public function canGetProperty($name, $checkVars = true)
    {
        foreach ($this->properties as $key => $call) {
            if (str_starts_with($name, $key)) {
                return true;
            }
        }
        return parent::canGetProperty($name, $checkVars); // TODO: Change the autogenerated stub
    }

    public function __get($name)
    {
        foreach ($this->properties as $key => $call) {
            foreach ($call[1] as $param => $value) {
                $replace = substr($name, strlen($key)) ?: $this->patterns[$param] ?? '';
                $replace = Inflector::camel2id($replace, '_');
                if (str_starts_with($replace, 'admin')) {
                    $replace = '_' . $replace;
                }
                $call[1][$param] = str_replace('{name}', $replace, $value);
            }
            return $this->{$call[0]}($call[1]);
        }
        return parent::__get($name); // TODO: Change the autogenerated stub
    }
}
