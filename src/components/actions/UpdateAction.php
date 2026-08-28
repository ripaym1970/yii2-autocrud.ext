<?php

/**
 * https://unclead.github.io/yii2-multiple-input/usage/
 */

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\models\interfaces\ClubModelInterface;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\web\Response;
use yii\web\UploadedFile;
use yii2tech\admin\actions\Update;

class UpdateAction extends Update
{
    //public $ajaxValidation = true;
    // Задает форму отличную от formFields. Например: formFieldsPassword
    public $form = '';

    public function run($id = null)
    {
        $id = Yii::$app->request->get('id', Yii::$app->request->post('id'));
        $modelClass = Yii::$app->request->post('modelClass');
        if (empty($id) && $modelClass) {
            $modelClass = StringHelper::basename($modelClass);
            $params = Yii::$app->request->post($modelClass);
            if (isset($params['id'])) {
                $id = $params['id'];
            }
        }
        /** @var ClubModelInterface $model */
        $model = $this->findModel($id);
        $model->scenario = $this->scenario;

        $post = Yii::$app->request->post();
        // Если что-то есть в $post
        if ($post) {
            //d($model->attributes, $post, $_FILES);

            // Грузим основную модель
            // Для load() в rules() для каждого поля должно быть правило или safe
            // Затирает значение если у поля тип file
            $loaded = $this->load($model, $post);
            //d($model->attributes, $post, $_FILES);
            //return;

            // Если основную модель загрузили
            if ($loaded) {
                //$tableName = lcfirst(str_replace('Model', '', StringHelper::basename(get_class($model))));
                $tableName = Yii::$app->request->get('table');

                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return $this->performAjaxValidation($model);
                }

                $transaction = $model->getDb()->beginTransaction();

                // Если есть вариации
                if (isset($model->behaviors()['translations'])) {
                    Model::loadMultiple($model->getVariationModels(), $post);
                }
                //dd($model->attributes, $post, $_FILES);

                $validated = $model->validate();
                if (!$validated) {
                    $this->setFlash(['error' => 'Validate errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
                }
                // Сохраняем всё
                $saved = $model->save(false);

                // Если сохранили
                if ($saved) {
                    // Получим для модели поля для названия картинок
                    $typeImage = [];
                    $columns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.columns', []);
                    foreach ($columns as $key => $column) {
                        if ($column['type'] == 'image') {
                            $typeImage[] = $key;
                        }
                    }
                    if ($typeImage) {
                        $saveUrl = URL::to("@uploadsPath/original/$tableName/$id/");
                        // Проверяем путь
                        if (!file_exists($saveUrl) && !mkdir($saveUrl, 0777, true)) {
                            dd('Не вдалося створити папку ' . $saveUrl, $_FILES);
                        }

                        // Для каждого поля
                        foreach ($typeImage as $fieldName) {
                            // Получим картинку
                            $image = UploadedFile::getInstance($model, $fieldName);
                            // Если есть картинка
                            if (!empty($image)) {
                                //d($fieldName, $image);
                                // Создадим старое название
                                $oldImageName = $model->$fieldName;
                                // Удалим старую картинку с сервера
                                $path = URL::to("@uploadsPath/original/$tableName/$id/$oldImageName");
                                if ($oldImageName && file_exists($path)) {
                                    unlink($path);
                                }

                                // Создадим новое название
                                $newImageName = $fieldName . '-' . $image->name;
                                // Сохраним новую картинку на сервер
                                $path = URL::to("@uploadsPath/original/$tableName/$id/$newImageName");
                                //if (file_exists($path)) {
                                //    unlink($path);
                                //}
                                if ($image->saveAs($path)) {
                                    // Заменим название
                                    $model->$fieldName = $newImageName;
                                }
                            }
                        }

                        // Сохраняем все изменения
                        $saved = $model->save(false);
                    }
//dd('ttttttt');
                    $transaction->commit();
                    $this->flash = Yiit::t('Запис № <a href="{url}">{id}</a> успішно оновлено');
                    $this->setFlash(Yiit::t($this->flash), ['id' => $id, 'url' => '/admin/'.$tableName . '/' . $id]);
                    return $this->controller->redirect($this->createReturnUrl('view', $model));
                }
                $transaction->rollBack();
                $this->setFlash(['error' => 'Save errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
            } else {
                $this->setFlash(['error' => 'Errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
            }
        }

        if (!$this->view) {
            return $this->controller->redirect($this->createReturnUrl('index', $model));
        }
        return $this->controller->render($this->view, [
            'model' => $model,
            'form'  => $this->form,
        ]);
    }
}
