<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\models\CrudModel;
use ripaym1970\autocrud\models\forms\ImagesForm;
use ripaym1970\autocrud\models\interfaces\basic\ImageModelInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii2tech\admin\actions\View;

/**
 * View action displays an existing model.
 */
class ViewAction extends View
{
    public $form = null;

    /**
     * Displays a model.
     *
     * @param string $id the primary key of the model.
     *
     * @return mixed|string|Response
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function run($id)
    {
        $this->setReturnAction('view');

        /** @var ActiveRecord|CrudModel $model */
        $model = $this->findModel($id);
        $post = Yii::$app->request->post();
        //dd($post);

        $imagesForm = new ImagesForm();
        if ($post && $imagesForm->load($post) && $imagesForm->validate()) {
            try {
                //$this->modelService->edit($model, $imagesForm);
                if ($imagesForm->files) {
                    /** @var ImageModelInterface $classNameImage */
                    $classNameImage = str_replace('Model', '_imageModel', get_class($model));
                    $modelImages = $model->images ?? [];
                    foreach ($imagesForm->files as $file) {
                        $modelImages[] = new $classNameImage([
                            //'location_id' => $model->id, // автоматом присвоит $model->id в нужное поле
                            'file' => $file,
                        ]);
                    }
                    /** @var ActiveRecord $image */
                    foreach ($modelImages as $i => $image) {
                        $image->sort = $i;
                    }

                    $model->images = $modelImages;

                    if ($model->save(false)) {
                        $tableName = Yii::$app->request->get('table');
                        $notSavedFiles = '';
                        //https://youtu.be/TZi5qa8diV8?t=858
                        $saveUrl = URL::to("@uploadsPath/original/$tableName/$id/");
                        // Проверяем путь
                        if (!file_exists($saveUrl)) {
                            //dd($saveUrl);
                            mkdir($saveUrl, 0777, true);
                        }
                        // Сохраняем все фото
                        foreach ($imagesForm->files as $file) {
                            $fileName = $file->name;
                            // Перемещаем файл с кеша на диск
                            if (!$file->saveAs($saveUrl . $fileName)) {
                                $notSavedFiles .= $fileName . ', ' ;
                            }
                        }
                        $this->setFlash('Зображення успішно додані');
                        if ($notSavedFiles) {
                            $this->setFlash(['error' => 'Не додано' . ': ' . $notSavedFiles]);
                        }
                    } else {
                        dd($model->errors);
                    }
                }

                return $this->controller->redirect($this->createReturnUrl('view', $model));
            } catch (\DomainException $e) {
                Yii::$app->errorHandler->logException($e);
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->controller->render($this->view, [
            'model'      => $model,
            'imagesForm' => $imagesForm,
        ]);
    }
}
