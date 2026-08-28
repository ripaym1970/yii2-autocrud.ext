<?php

namespace ripaym1970\autocrud\components\actions;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii2tech\admin\actions\Action;

class MoveImageUpAction extends Action
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

        /** @var $model */
        $model = $this->findModel($id);
        $model->moveImageUp($image_id);

        $params = Yii::$app->request->getQueryParams();
        unset($params['image_id']);
        Yii::$app->request->setQueryParams($params);
        return $this->controller->redirect($this->createReturnUrl('view', $model));
    }
}
