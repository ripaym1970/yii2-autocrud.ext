<?php

/**
 * $element = $options->owner->hasManyRelations[$options->relationName];
 * $shortClass = \ripaym1970\autocrud\components\Util::getShortClassName($element->model);
 *
 * // nothing to save, skip
 * if (!isset($_REQUEST[$shortClass])) {
 *     continue;
 * }
 *
 * if ($element->rule->isHasMany) {
 *     $result = $this->saveHasMany($shortClass, $element, $relationName);
 * }
 */

namespace ripaym1970\autocrud\components;

use yii\base\Behavior;

class Saver extends \yii\base\Component
{
    /** @var \yii\db\ActiveRecord|Behavior */
    public $model;

    /** @var array */
    public $relations = [];

    public function init()
    {
        if (!$this->model) {
            throw new \yii\base\InvalidConfigException("Model is required");
        }

        return parent::init();
    }

    //public function save(): bool
    //{
    //    $transaction = \ripaym1970\autocrud\components\Util::makeTransaction();
    //    $result = true;
    //    /** @var DynamicModels\models\BaseModel[] $objectsToRemove */
    //    $objectsToRemove = [];
    //    /**
    //     * @var  string $relationName
    //     * @var  CacheElement $element
    //     */
    //    foreach ($this->relations as $relationName => $element) {
    //        $shortClass = \ripaym1970\autocrud\components\Util::getShortClassName($element->model);
    //
    //
    //        // nothing to save, skip
    //        if (!isset($_REQUEST[$shortClass])) {
    //            continue;
    //        }
    //
    //
    //        if ($element->rule->isHasMany) {
    //            $result = $this->saveHasMany($shortClass, $element, $relationName) && $result;
    //            continue;
    //        }
    //
    //        $existingObject = $element->model;
    //        /** @var DynamicModels\models\BaseModel $className */
    //        $className = get_class($existingObject);
    //
    //        /** @var DynamicModels\models\BaseModel $newObject */
    //        $newObject = \ripaym1970\autocrud\components\Util::fillModelFields(
    //            new $className(),
    //            ['id'],
    //            true
    //        );
    //
    //        if ($newObject->id) {
    //            $newObject = $className::findOne($newObject->id)
    //                ?: $newObject; // maybe not in db already (transaction rolled back etc.)
    //        }
    //
    //        // reset, fillModelFields could put empty line there
    //        if (!$newObject->id) {
    //            unset($newObject->id);
    //        }
    //
    //        $shouldUnlink = $newObject->id != $existingObject->id
    //            && !$existingObject->isNewRecord;
    //
    //        if ($shouldUnlink) {
    //            $this->model->unlink($relationName, $existingObject, true);
    //            $objectsToRemove[] = $existingObject;
    //        }
    //
    //        // replace old model with new one anyway
    //        $element->model = $newObject;
    //
    //        $shouldUpdateDetails = $element->rule->interfaceForm
    //            && $newObject instanceof DynamicModels\models\BaseModel;
    //
    //        if ($shouldUpdateDetails) {
    //            \ripaym1970\autocrud\components\Util::fillModelFields(
    //                $newObject,
    //                \yii\helpers\ArrayHelper::getColumn(
    //                    $newObject->modelFields,
    //                    'name'
    //                ),
    //                true
    //            );
    //        }
    //
    //        $detailsEmpty = !count(
    //            array_filter(
    //                array_values(
    //                    $newObject->attributes
    //                )
    //            )
    //        );
    //
    //        $hasEav = $this->checkEavRequest($newObject);
    //
    //        $compatible = $element->rule->service->checkObject(
    //            $this->model,
    //            false
    //        );
    //
    //        $failed = !$compatible
    //            && (!$detailsEmpty || $hasEav);
    //
    //        if ($failed) {
    //            $result = false;
    //            continue;
    //        }
    //
    //        $failed = !$shouldUpdateDetails
    //            && $newObject->isNewRecord
    //            && $element->rule->is_target_required;
    //
    //        if ($failed) {
    //            $result = false;
    //            continue;
    //        }
    //
    //        // rule is not compatible, but model has no details, and no related eav,
    //        // so we just do nothing
    //        if (!$compatible) {
    //            continue;
    //        }
    //
    //        $canSkip = $shouldUpdateDetails
    //            && $detailsEmpty
    //            && !$hasEav
    //            && !$element->rule->is_target_required;
    //
    //        if ($canSkip) {
    //            continue;
    //        }
    //        $shouldLink = $newObject->isNewRecord
    //            || $newObject->id != $existingObject->id;
    //
    //        $failed = $shouldUpdateDetails && !(
    //                (
    //                    \ripaym1970\autocrud\components\Util::hasBehavior(
    //                        $newObject,
    //                        \ripaym1970\autocrud\components\Color\behaviors\HasColor::class,
    //                        false
    //                    )
    //                    && $newObject->saveColorAndOwner()
    //                )
    //                || $newObject->save()
    //            )
    //            || !$newObject->saveEavData();
    //
    //        if ($failed) {
    //            $result = false;
    //            continue;
    //        }
    //
    //        $hasFilesBehaviour = \ripaym1970\autocrud\components\Util::hasBehavior(
    //                $newObject,
    //                \ripaym1970\autocrud\components\modules\File\behaviors\File::class,
    //                false
    //            ) && $shouldUpdateDetails;
    //
    //        if ($hasFilesBehaviour) {
    //            $failed = !$newObject->saveFiles();
    //        }
    //
    //        if ($failed) {
    //            $result = false;
    //            continue;
    //        }
    //
    //        if ($shouldLink && !$newObject->isNewRecord) {
    //            $this->model->link($relationName, $newObject);
    //        }
    //    }
    //
    //    if (!$result) {
    //        $transaction && $transaction->rollBack();
    //        return false;
    //    }
    //
    //    // auto-drop if needed
    //    foreach ($objectsToRemove as $toRemove) {
    //        $shouldRemove = $toRemove instanceof DynamicModels\models\BaseModel
    //            && $toRemove::getMetaModel()->automated_removal;
    //        $shouldRemove && $toRemove->removeIfNeeded();
    //    }
    //    $transaction && $transaction->commit();
    //    return true;
    //}

    protected function saveHasMany($shortClass, CacheElement $element, $relationName)
    {
        $ids = array_values(array_unique($_REQUEST[$shortClass]));
        $existingIds = \yii\helpers\ArrayHelper::getColumn(
            $this->model->$relationName,
            'id'
        );
        $idsToAdd = array_diff($ids, $existingIds);
        $idsToRemove = array_diff($existingIds, $ids);

        $invalid = !$element->validate()
            && (
                count($existingIds) != count($idsToRemove)
                && count($existingIds) != 0
                || count($idsToAdd)
            );

        foreach ($idsToAdd as $id) {
            $model = $element->model::findOne($id);
            // can be already removed, it's ok
            if ($model) {
                $this->model->link($relationName, $model);
            }
        }
        foreach ($idsToRemove as $id) {
            $model = $element->model::findOne($id);
            // can be already removed, it's ok
            if ($model) {
                $this->model->unlink($relationName, $model, true);
            }
        }
        // clear cache
        unset($this->model[$relationName]);
        return !$invalid;
    }
}
