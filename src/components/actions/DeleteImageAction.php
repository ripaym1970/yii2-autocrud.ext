<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\models\CrudModel;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii2tech\admin\actions\Action;

class DeleteImageAction extends Action
{
    /**
     * @param int $id
     * @param int $image_id
     *
     * @return Response
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function run(int $id, int $image_id)
    {
        $this->setReturnAction('view');

        /** @var ActiveRecord|CrudModel $model */
        $model = $this->findModel($id);
        try {
            if ($model->removeImage($image_id)) {
                $this->setFlash(Yiit::t('Зображення успішно видалено'));
            } else {
                $this->setFlash(['error' => Yiit::t('Зображення не вдалося видалити')]);
            }
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        $params = Yii::$app->request->getQueryParams();
        unset($params['image_id']);
        Yii::$app->request->setQueryParams($params);
        return $this->controller->redirect($this->createReturnUrl('view', $model));
    }
}
