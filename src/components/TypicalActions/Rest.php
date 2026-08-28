<?php

namespace ripaym1970\autocrud\components\TypicalActions;

/** @inheritdoc
 * @property string $modelName
 */
class Rest extends \yii\base\Action
{
    /** @var  $fetchFields array|callable */
    public $fetchFields = [];
    /** @var  $fetchFields array */
    public $saveFields = [];
    /** @var  $collectionQuery callable */
    public $collectionQuery;
    public $deleteEnabled = false;

    public function run($id = null)
    {
        $request = \Yii::$app->request;
        if ($request->isGet) {
            return $this->fetch($id);
        }

        if ($request->isPost || $request->isPut) {
            if (!$this->saveFields) {
                throw new \yii\base\InvalidConfigException(
                    "Fields list is empty"
                );
            }
            return $this->save($id);
        }

        if ($this->deleteEnabled && $id && $request->isDelete) {
            return $this->delete($id);
        }

        throw new \yii\web\BadRequestHttpException(
            "Unsupported method"
        );
    }

    protected function getModelName()
    {
        return ($this->controller)::MODEL_CLASS;
    }

    private function toArray($models)
    {
        return \yii\helpers\ArrayHelper::toArray(
            $models,
            [
                $this->modelName => is_callable($this->fetchFields)
                    ? call_user_func($this->fetchFields)
                    : $this->fetchFields,
            ]
        );
    }

    private function fetch($id)
    {
        if (!$id) {
            $query = ($this->modelName)::find();

            if (is_callable($this->collectionQuery)) {
                $query = call_user_func($this->collectionQuery, $query);
            }

            $models = $query->all();
        } else {
            $models = $this->controller->findModel($id);
        }

        return $this->controller->asJson($this->toArray($models));
    }

    private function save($id)
    {
        /** @var $model \yii\db\ActiveRecord */
        $model = $id
            ? $this->controller->findModel($id)
            : new $this->modelName;

        $attributes = \yii\helpers\Json::decode(\yii::$app->request->rawBody);

        foreach ($this->saveFields as $field) {
            $model->$field = $attributes[$field] ?? null;
        }

        $result = $model->save();
        $model->refresh();
        $attributes = $this->toArray($model);
        if (!$result) {
            $attributes['error'] = \yii\helpers\Html::errorSummary(
                [$model],
                ['class' => 'error-summary']
            );
        }

        return $this->controller->asJson($attributes);
    }

    private function delete($id)
    {
        \ripaym1970\autocrud\components\Util::deleteModel(
            $this->controller->findModel($id)
        );
        \ripaym1970\autocrud\components\Util::noContent();
    }
}
