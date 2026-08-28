<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\Model;
use yii\web\Response;
use yii\widgets\ActiveForm;
use yii2tech\admin\actions\Create;

class CreateAction extends Create
{
    //public $ajaxValidation = true;
    //
    //public $onSuccess;
    //
    //public $onFail;
    //
    //public $onEmpty;
    // Задает форму отличную от formFields. Например: formFieldsPassword
    public $form = '';

    public function run()
    {
        $model = $this->newModel();
        $model->scenario = $this->scenario;

        $post = Yii::$app->request->post();

        if (Yii::$app->request->isAjax && $model->load($post)) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($post) {
            // Получим название класса модели
            $modelName = ucfirst(get_class($model)::tableName()) . 'Model';
            // Возьмем только поля модели
            $post2[$modelName] = $post[$modelName] ?? [];

            // Удалим из них поля relatedManyAjax (которые массивы)
            foreach ($post2[$modelName] as $key => $item) {
                if (is_array($item)) {
                    unset($post2[$modelName][$key]);
                }
            }

            // Грузим основные поля модели
            $loaded = $this->load($model, $post2);
            // Если основные поля загрузили, провалидировали и сохранили
            if (!$loaded || !$model->validate() || !$model->save()) {
                $this->setFlash(['error' => 'Помилки: ' . "<br/> " . implode("<br/>", $model->getErrorSummary(true))]);

                // Грузим поля модели, чтобы обратно в форму передать
                // TODO: Не грузятся translations!!!
                $loaded = $this->load($model, $post);

                return $this->controller->render($this->view, [
                    'model' => $model,
                    'form'  => $this->form,
                ]);

                //// Грузим поля relatedManyAjax
                ////dd($post, $post2);
                //unset($post[$modelName]);
                //$loaded = $this->load($model, $post); // Тут 2й раз грузим модель!!!
            }
            //dd($model);

            $this->flash = Yiit::t('Елемент № <a href="{url}">{id}</a> успішно створено');

            // Если основную модель загрузили
            if ($loaded) {
                // Если есть вариации
                if (isset($model->behaviors()['translations'])) {
                    Model::loadMultiple($model->getVariationModels(), $post);
                }
                // Если сохранили без валидации - только связи
                if ($model->save(false)) {
                    return $this->controller->redirect($this->createReturnUrl('view', $model));
                }

                $this->setFlash(['error' => 'Save errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
            } else {
                $this->setFlash(['error' => 'Load errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
            }
        }

        return $this->controller->render($this->view, [
            'model' => $model,
            'form'  => $this->form,
        ]);
    }
}
