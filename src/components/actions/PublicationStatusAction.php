<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\models\behaviors\PublicationStatusBehavior;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\web\Response;
use yii2tech\admin\actions\Action;
use yii2tech\admin\actions\ModelFormTrait;

class PublicationStatusAction extends Action
{
    use ModelFormTrait;

    public $value = PublicationStatusBehavior::STATUS_PUBLISHED;

    public function run($id)
    {
        $model = $this->findModel($id);
        $this->flash = Yiit::t('The item #{id} was successfully moved to {value}.');

        $model->setAttribute('publication_status', $this->value);
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->performAjaxValidation($model);
        }
        if ($model->save(true, ['publication_status'])) {
            $this->setFlash($this->flash, ['id' => $id, 'model' => $model, 'value', $this->value]);
            return $this->controller->redirect($this->createReturnUrl('index', $model));
        }

        $this->setFlash(['error' => implode("<br/>", $model->getErrorSummary(true))]);

        return $this->controller->redirect($this->createReturnUrl('index', $model));
    }
}
