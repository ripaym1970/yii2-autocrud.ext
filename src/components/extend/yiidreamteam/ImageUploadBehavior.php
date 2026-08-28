<?php

namespace ripaym1970\autocrud\components\extend\yiidreamteam;

use ripaym1970\autocrud\components\extend\phpthumb\GD;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * @property string $attribute
 * @property bool   $createThumbsOnSave
 * @property bool   $createThumbsOnRequest
 */
class ImageUploadBehavior extends FileUploadBehavior
{
    public $attribute = 'image';

    public $createThumbsOnSave = true;
    public $createThumbsOnRequest = false;

    /** @var array Thumbnail profiles, array of [width, height, ... PHPThumb options] */
    public $thumbs = [];

    /** @var string Path template for thumbnails. Please use the [[profile]] placeholder. */
    public $thumbPath = '@webroot/uploads/[[profile]]_[[pk]].[[extension]]';
    /** @var string Url template for thumbnails. */
    public $thumbUrl = '/uploads/[[profile]]_[[pk]].[[extension]]';

    public $filePath = '@webroot/uploads/[[pk]].[[extension]]';
    public $fileUrl = '/uploads/[[pk]].[[extension]]';

    /**
     * @return array|string[]
     */
    public function events()
    {
        return ArrayHelper::merge(parent::events(), [
            static::EVENT_AFTER_FILE_SAVE => 'afterFileSave',
        ]);
    }

    /**
     * @return void
     */
    public function cleanFiles()
    {
        parent::cleanFiles();
        foreach (array_keys($this->thumbs) as $profile) {
            @unlink($this->getThumbFilePath($this->attribute, $profile));
        }
    }

    /**
     * @param string $attribute
     * @param string $profile
     *
     * @return string
     */
    public function getThumbFilePath($attribute, $profile = 'thumb')
    {
        $behavior = static::getInstance($this->owner, $attribute);
        return $behavior->resolveProfilePath($behavior->thumbPath, $profile);
    }

    /**
     * Разрешает путь к [[profile]] для эскиза [[profile]].
     *
     * @param string $path
     * @param string $profile
     *
     * @return string
     */
    public function resolveProfilePath($path, $profile)
    {
        $path = $this->resolvePath($path);
        return preg_replace_callback('|\[\[([\w\_/]+)\]\]|', function ($matches) use ($profile) {
            $name = $matches[1];
            switch ($name) {
                case 'profile':
                    return $profile;
            }
            return '[[' . $name . ']]';
        }, $path);
    }

    /**
     * Получаем ссылку на картинку сайта
     *
     * @param string      $attribute
     * @param string|null $emptyUrl
     *
     * @return string|null
     */
    public function getImageFileUrl($attribute, $emptyUrl = null)
    {
        // Если нет значения
        if (!$this->owner->{$attribute}) {
            return $emptyUrl;
        }

        return $this->getUploadedFileUrl($attribute);
    }

    /**
     * @param string      $attribute
     * @param string      $profile
     * @param string|null $emptyUrl
     *
     * @return string|null
     */
    public function getThumbFileUrl($attribute, $profile = 'thumb', $emptyUrl = null)
    {
        if (!$this->owner->{$attribute}) {
            return $emptyUrl;
        }

        $behavior = static::getInstance($this->owner, $attribute);

        if ($behavior->createThumbsOnRequest) {
            $behavior->createThumbs();
        }

        return $behavior->resolveProfilePath($behavior->thumbUrl, $profile);
    }

    /**
     * Создает миниатюры изображений
     */
    public function createThumbs()
    {
        $path = $this->getUploadedFilePath($this->attribute);
        if (!file_exists($path)) {
            //dd("Не найден файл-источник $path");
            return;
        }

        foreach ($this->thumbs as $profile => $config) {
            $thumbPath = static::getThumbFilePath($this->attribute, $profile);
            //d($thumbPath);
            if (!file_exists($thumbPath)) {
                // setup image processor function
                if (isset($config['processor']) && is_callable($config['processor'])) {
                    $processor = $config['processor'];
                    unset($config['processor']);
                } else {
                    $processor = function (GD $thumb) use ($config) {
                        $thumb->adaptiveResize($config['width'], $config['height']);
                    };
                }

                $thumb = new GD($path, $config);
                call_user_func($processor, $thumb, $this->attribute);
                FileHelper::createDirectory(pathinfo($thumbPath, PATHINFO_DIRNAME), 0777, true);
                $saved = $thumb->save($thumbPath);
                if (!$saved) {
                    dd("Не сохранили в $thumbPath");
                }
            }
        }
    }

    /**
     * Обработчик событий после сохранения файла
     */
    public function afterFileSave()
    {
        if ($this->createThumbsOnSave == true) {
            $this->createThumbs();
        }
    }
}
