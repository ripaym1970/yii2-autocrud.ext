<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\components\ActiveForm;
use ripaym1970\autocrud\components\WithRelationsBehavior;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\Action;
use yii\web\Response;
use yii2tech\admin\ActionEvent;
use yii2tech\admin\actions\Update;

class WithRelationsUpdate extends Update
{
    /**
     * @return \string[][]
     */
    public function behaviors()
    {
        return [
            [
                'class' => WithRelationsBehavior::class,
            ],
        ];
    }

    public function run($id)
    {
        $model = $this->findModel($id);
        $model->scenario = $this->scenario;

        if ($this->load($model, Yii::$app->request->post())) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->performAjaxValidation($model);
            }

            $this->flash = Yiit::t('Елемент № <a href="{url}">{id}</a> успішно оновлено');

            $transaction = $model->getDb()->beginTransaction();
            if ($model->save() && $this->performAfterSave($model)) {
                $transaction->commit();
                $this->setFlash($this->flash, ['id' => $id, 'model' => $model]);
                return $this->controller->redirect($this->createReturnUrl('view', $model));
            }

            $transaction->rollBack();
            $this->setFlash(['error' => implode("<br/>", $model->getErrorSummary(true))]);
        }
        return $this->controller->render($this->view, [
            'model' => $model,
        ]);
    }

    protected function performAfterSave($model)
    {
        /** @var $this Action */
        $event = new ActionEvent($this, [
            'model'  => $model,
            'result' => ActiveForm::validate($model),
        ]);
        $this->trigger('afterSave', $event);
        return $event->result;
    }
}
