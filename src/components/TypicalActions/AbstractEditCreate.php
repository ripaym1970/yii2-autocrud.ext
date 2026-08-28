<?php

namespace ripaym1970\autocrud\components\TypicalActions;

/**
 * @property \yii\db\ActiveRecord $model
 */
class AbstractEditCreate extends AbstractAction
{
    public const AFTER_SAVE = 'onAfterSave';
    /** @var callable|null */
    public $beforeSave;
    /** array or callback (with model as param) */
    /** @var  $fields array */
    public $fields;
    public $view = 'form';
    public $saveEavData = true;
    public $saveFiles = true;
    /**
     * @var callable|string|array|bool|null $successRedirectUrl
     * may contain/return url, where Yii should redirect if model
     * is saved successfully. If === false, it won't do redirect and
     * just render view again
     */
    public $successRedirectUrl;
    /**
     * @var bool|callable
     * in some cases we need to render the form always, even if it saved the data successfully
     */
    public $renderAlways = false;

    public function init()
    {
        if (!$this->fields) {
            throw new \yii\base\InvalidConfigException(
                "Fields list should be not empty"
            );
        }
        return parent::init();
    }

    protected function process(\yii\db\ActiveRecord $model)
    {
        if (\Yii::$app->request->isGet) {
            return $this->renderView($model);
        }

        if (!\Yii::$app->request->isPost) {
            $this->invalidMethod();
        }

        $modelSaved = false;
        $onSave = function (\yii\base\Event $e) use (&$modelSaved) {
            $modelSaved = true;
        };

        $model->on($model::EVENT_AFTER_UPDATE, $onSave);
        $model->on($model::EVENT_AFTER_INSERT, $onSave);

        $fieldsToFill = is_callable($this->fields)
            ? call_user_func($this->fields, $model)
            : $this->fields;
        \ripaym1970\autocrud\components\Util::fillModelFields($model, $fieldsToFill);

        $transaction = \ripaym1970\autocrud\components\Util::makeTransaction();

        // the syntax for calling method is used intentionally, because
        // sometimes method has to replace $model
        if (is_callable($this->beforeSave) && !($this->beforeSave)($model)) {
            $transaction && $transaction->rollBack();
            return $this->renderView($model);
        }

        if (!$modelSaved && !\ripaym1970\autocrud\components\ActiveField::isReloadable()) {
            $modelSaved = $model->save();
        }

        if ($modelSaved) {
            $event = new \yii\base\ModelEvent([
                'sender' => $model,
            ]);
            $this->trigger(self::AFTER_SAVE, $event);
            $modelSaved = $event->isValid;
        }

        $modelSaved = $modelSaved
            && $this->processDynamicModels($model)
            && $this->processFiles($model);

        if (!$modelSaved || \ripaym1970\autocrud\components\ActiveField::isReloadable()) {
            $transaction && $transaction->rollBack();
            return $this->renderView($model);
        }

        $transaction && $transaction->commit();

        if ($model->id) {
            \Yii::$app->response->headers->add(
                'X-Model-ID',
                $model->id
            );
        }

        if (\Yii::$app->request->isAjax) {
            $shouldContinue = is_callable($this->renderAlways)
                ? call_user_func($this->renderAlways, $model)
                : $this->renderAlways;
            if (!$shouldContinue) {
                \ripaym1970\autocrud\components\Util::noContent();
                return '';
            }
        } else {
            \Yii::$app->session->addFlash(
                'success',
                \Yii::t('app', "Saved Successfully")
            );
        }

        if ($this->successRedirectUrl) {
            $url = is_callable($this->successRedirectUrl)
                ? call_user_func($this->successRedirectUrl, $model)
                : $this->successRedirectUrl;
            if ($url !== false) {
                return $this->controller->redirect($url);
            }
        }

        return $this->renderView($model);
    }

    protected function processFiles($model)
    {
        if (!$this->saveFiles) {
            return true;
        }

        $hasFiles = \ripaym1970\autocrud\components\Util::hasBehavior(
            $model,
            \ripaym1970\autocrud\components\modules\File\behaviors\File::class,
            false
        );

        /** @var \ripaym1970\autocrud\components\modules\File\behaviors\File $model */
        return !$hasFiles || $model->saveFiles();
    }

    protected function processDynamicModels($model)
    {
        if (!$this->saveEavData) {
            return true;
        }

        $hasExtendedProperties = \ripaym1970\autocrud\components\Util::hasBehavior(
            $model,
            \ripaym1970\autocrud\components\modules\DynamicModels\components\Behavior::class,
            false
        );
        /** @var \ripaym1970\autocrud\components\modules\DynamicModels\components\Behavior $model */
        return !$hasExtendedProperties || $model->saveEavData();
    }
}
