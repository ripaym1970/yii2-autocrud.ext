<?php

/**
 * https://github.com/thephpleague/flysystem
 */

namespace ripaym1970\autocrud\components\extend;

//use ripaym1970\autocrud\models\crud\ServiceModel;
use League\Flysystem\Exception;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use ripaym1970\autocrud\components\extend\yiidreamteam\ImageUploadBehavior;
use yii\web\UploadedFile;

class FlySystemImageUploadBehavior extends ImageUploadBehavior
{
    private $filesystem;

    public function __construct(Filesystem $filesystem, $config = [])
    {
        parent::__construct($config);

        $this->filesystem = $filesystem;
    }

    /**
     * @throws FileNotFoundException
     */
    public function cleanFiles(): void
    {
        $this->filesystem->delete($this->resolvePath($this->filePath));

        foreach (array_keys($this->thumbs) as $profile) {
            $this->filesystem->delete($this->getThumbFilePath($this->attribute, $profile));
        }
    }

    /**
     * TODO: переопределяем afterSave() из vendor/yii-dream-team/yii2-upload-behavior/src/FileUploadBehavior.php
     *
     * Сохраняем фото на диск
     *
     * @throws Exception
     */
    public function afterSave(): void
    {
        if (!$this->file) {
            throw new Exception('Потерялось или не задано имя файла');
            // При перемещении файлов нет имени, поэтому просто завершаем функцию
            return;
        }

        if (!($this->file instanceof UploadedFile)) {
            throw new Exception('Файл "' . $this->file . '" не типа UploadedFile');
        }

        // Получим имя файла-оригинала
        $originFile = $this->getUploadedFilePath($this->attribute);
        // Получим путь куда сохранять ВСЕ файлы-оригинал
        $originPath = pathinfo($originFile, PATHINFO_DIRNAME);
        // Создадим папку куда сохранять ВСЕ файлы-оригинал
        $result = $this->filesystem->createDir($originPath);
        if (!$result) {
            if (!is_dir($originPath)) {
                mkdir($originPath, 0777, true);
            }
            dd(['1 Не удалось создать папку ' . $originPath . '']);
        }

        //d([$originFile, $originPath]);
        //dd([$originFile, $this->file]);

        // Имя tmp-файла
        $tmpFile = $this->file->tempName;
        if (!is_file($tmpFile)) {
            dd('Файл не загрузился или его размер слишкой большой. Имя файла "' . $tmpFile . '"');
        }
        //// Копируем tmp-файл в $originFile (/static/origin/projects/1/...)
        //$result = $this->filesystem->put($originFile, file_get_contents($tmpFile));
        //if (!$result) {
        //    dd(['Не удалось скопировать tmp-файл "' . $tmpFile . '" в "' . $originFile . '"']);
        //}
        // Перемещаем файл в cache-папку
        rename($tmpFile, $originFile);

        // Запускаем срабатывание тригера 'afterFileSave' для выполнения afterFileSave() из vendor/yii-dream-team/yii2-upload-behavior/src/ImageUploadBehavior.php
        //$this->owner->trigger(static::EVENT_AFTER_FILE_SAVE);
        // Далее запускается createThumbs() из vendor/yii-dream-team/yii2-upload-behavior/src/ImageUploadBehavior.php
        // Поступим проще
        $this->afterFileSave();
    }

    /**
     * TODO: переопределяем afterFileSave() из vendor/yii-dream-team/yii2-upload-behavior/src/ImageUploadBehavior.php
     *
     * After file save event handler.
     */
    public function afterFileSave()
    {
        //dd($this->createThumbsOnSave);
        if ($this->createThumbsOnSave == true) {
            $this->createThumbs();
        }
    }

    /**
     * TODO: переопределяем createThumbs() из vendor/yii-dream-team/yii2-upload-behavior/src/ImageUploadBehavior.php
     *
     * Для вьюшки /views/project/project/view.php преобразует картинки
     *
     * Алгоритм:
     * - получаем файл-оригинал
     * - копируем файл-оригинал в tmp-файл
     * - преобразовываем tmp-файл в нужный типоразмер
     * - сохраняем в tmp-файл
     * - копируем tmp-файл в cache-файл-типоразмер
     */
    //public function createThumbs(): void {
    //    // Получим имя файла-оригинала (/static/origin/projects/1/...)
    //
    //    //echo '<pre>';var_dump($this->attribute);echo '</pre>';exit();
    //    $originFile = $this->getUploadedFilePath($this->attribute);
    //    //dd(['$pathFile=', $originFile]);
    //
    //    if (!is_file($originFile)) {
    //        //dd($this->attribute);
    //        //dd('1 Файла-оригинала ' . $originFile . ' нет - удалем ссылку на него из проекта');
    //
    //        if (!empty($this->owner->getAttribute('project_id'))) {
    //            $model = ServiceModel::findOne(['id' => $this->owner->getAttribute('service_id')]);
    //            $model->main_image_id = null;
    //            $model->save();
    //        }
    //        //if (!empty($this->owner->getAttribute('person_profile_id'))) {
    //        //    $profile = PersonProfile::findOne(['id' => $this->owner->getAttribute('person_profile_id')]);
    //        //    $profile->main_image_id = null;
    //        //    $profile->save();
    //        //}
    //
    //        return;
    //    }
    //
    //    $filesize = filesize($originFile);
    //    if (!$filesize) {
    //        dd('Файл-оригинал пустой. Размер файла-оригинала "' . $originFile . '": ' . $filesize . ' байтов');
    //    }
    //
    //    // Для всех типоразмеров
    //    foreach ($this->thumbs as $profile => $config) {
    //        // Получим имя файла-типоразмера
    //        $thumbFile = static::getThumbFilePath($this->attribute, $profile);
    //        //dd(['load' => $originFile, 'save' => $thumbFile]);
    //
    //        // Получим путь куда сохранять ВСЕ файлы-типоразмер
    //        $thumbPath = pathinfo($thumbFile, PATHINFO_DIRNAME);
    //
    //        // Создадим папку куда сохранять ВСЕ файлы-типоразмер
    //        $result = $this->filesystem->createDir($thumbPath); // Для Win7 не работает
    //        if (!$result) {
    //            //dd(['2 Не удалось создать папку ' . $thumbPath . '']);
    //            if (!is_dir($thumbPath)) {
    //                mkdir($thumbPath, 0777, true);
    //            }
    //        }
    //
    //
    //        // Если есть файл-оригинал и нет файла-типоразмер
    //        if (file_exists($originFile) && !file_exists($thumbFile)) {
    //            // Если есть функция-обработчик фото
    //            if (isset($config['processor']) && is_callable($config['processor'])) {
    //                $processor = $config['processor'];
    //                unset($config['processor']);
    //            } else {
    //                // Иначе задаём свою с параметрами
    //                $processor = function (GD $thumb) use ($config) {
    //                    $thumb->adaptiveResize($config['width'], $config['height']);
    //                };
    //            }
    //
    //            // Создаем объект преобразования файла-оригинала
    //            // TODO: И тут даёт ошибку
    //            // В vendor/masterexploder/phpthumb/src/PHPThumb/GD.php в 59 сторке чтобы было protected $options = [];
    //            // Решение - создаем класс от GD
    //            $thumb = new GD($originFile, $config);
    //
    //            // Преобразовываем файл-оригинал
    //            call_user_func($processor, $thumb, $this->attribute);
    //
    //            // Сохраняем обработанный файл-оригинал в файл-типоразмер
    //            $saved = $thumb->save($thumbPath);
    //            if (!$saved) {
    //                dd('Не сохранили ' . $thumbPath);
    //            }
    //        }
    //    }
    //}

    ///**
    // * @param $path
    // *
    // * @return string
    // */
    //private function getTempPath($path): string {
    //    return sys_get_temp_dir() . $path;
    //}
}
