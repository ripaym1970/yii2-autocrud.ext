<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\components\Yiit;
use Yii;use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii2tech\admin\actions\Delete;

class DeleteAction extends Delete
{
    public function run($id = null)
    {
        $this->flash = Yiit::t('The item #{id} was successfully deleted.');

        $id = Yii::$app->request->get('id', Yii::$app->request->post('id'));
        $model = $this->findModel($id);

        $transaction = $model->getDb()->beginTransaction();

        $success = true;
        if (isset($model->behaviors()['translations'])) {
            foreach ($model->getVariationModels() as $variationModel) {
                if (!$variationModel->getIsNewRecord()) {
                    $success = $success && $variationModel->delete();
                }
            }
        }

        if ($model->getBehavior('nestedSetsBehavior')) {
            $success = $success && $model->deleteWithChildren();
        } else {
            /** @var $modelClass ActiveRecord */
            $modelClass = get_class($model);
            $tableName = $modelClass::tableName();
            // Получим связи
            $tableRelations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.relations', []);
            $relations = prepareRelations($tableRelations);
            //echo '<pre>';var_dump('get_class=',$relations);echo '</pre>';exit();
            // Удалим привязанные записи через relations
            foreach ($relations as $name => $item) {
                $relation = $model->getRelation($name);
                // Если связь multiple
                if ($relation->multiple === true) {
                    $model->unlinkAll($name, true);
                }
            }

            $success = $success && $model->delete();
        }

        if ($success) {
            $transaction->commit();
            $this->setFlash(Yiit::t($this->flash), ['id' => $id, 'model' => $model]);
        } else {
            $transaction->rollBack();
            $this->setFlash(['error' => 'Error deleting: ' . implode("<br/>", $model->getErrorSummary(true))]);
        }

        return $this->controller->redirect($this->createReturnUrl('index'));
    }
}
