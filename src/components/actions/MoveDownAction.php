<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\models\behaviors\NestedSetsBehavior;
use DomainException;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii2tech\admin\actions\Delete;

class MoveDownAction extends Delete
{
    /**
     * @param string $id the primary key of the model
     *
     * @return Response
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function run($id = null)
    {
        $id = Yii::$app->request->get('id', Yii::$app->request->post('id'));
        if (!$id || $id < 0) {
            throw new DomainException('Не получили ID записи.');
        }

        /** @var NestedSetsBehavior $model */
        $model = $this->findModel($id);

        if ($model->isRoot()) {
            throw new DomainException('Нельзя перемещать root-запись.');
            //throw new \DomainException('Unable to manage the root category.');
        }
        $next = $model->next()->limit(1)->one();
        if ($next) {
            if ($next->depth == $model->depth) {
                $transaction = $model->getDb()->beginTransaction();
                $model->insertBefore($next);
                $transaction->commit();
                $this->flash = Yiit::t('The item #{id} was successfully MoveDown.');
            } else {
                $this->flash = Yiit::t('Можно перемещать только одного уровня.');
                throw new DomainException('Можно перемещать только одного уровня.');
            }
        }

        return $this->controller->redirect($this->createReturnUrl('index', $model));
    }
}
