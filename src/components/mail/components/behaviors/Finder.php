<?php

namespace ripaym1970\autocrud\components\mail\components\behaviors;

/** @inheritdoc */
class Finder extends \yii\base\Behavior
{
    /**
     * @var $checkAccess callable|null
     * callback, which accepts model as parameter
     * should return true if access is granted, otherwise false
     */
    public $checkAccess;

    /**
     * @var array|callable additional search conditions if needed. in case with callable should return array with
     * search conditions
     */
    public $conditions = [];

    public function findModel($id): \yii\db\ActiveRecord
    {
        $modelClass = ($this->owner)::MODEL_CLASS;

        $model = $modelClass::findOne(
            \yii\helpers\ArrayHelper::merge(
                [
                    'id' => $id,
                ],
                is_array($this->conditions)
                    ? $this->conditions
                    : call_user_func($this->conditions)
            )
        );

        if (!$model) {
            throw new \yii\web\NotFoundHttpException(
                \yii::t('app', "Model was not found")
            );
        }

        if (
            is_callable($this->checkAccess)
            && !call_user_func($this->checkAccess, $model)
        ) {
            throw new \yii\web\ForbiddenHttpException(
                \yii::t('app', "Access denied")
            );
        }

        return $model;
    }

}
