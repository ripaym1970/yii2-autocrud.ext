<?php

/**
 * @author albanjubert
 */

namespace ripaym1970\autocrud\components\widgets\SaveRelations;

use RuntimeException;
use Yii;
use yii\base\Behavior;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQuery;
use yii\db\BaseActiveRecord;
use yii\db\Exception as DbException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;

/**
 * Это поведение активной записи позволяет проверять и сохранять отношения модели при вызове метода save().
 * Список обрабатываемых отношений должен быть объявлен с использованием параметра $relations через массив имен отношений.
 * 'behaviors'      => [
 *      ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
 *      'saveRelations' => [
 *          'class' => ripaym1970\autocrud\components\widgets\SaveRelations\SaveRelationsBehavior::class,
 *          'relations' => [
 *              //'tags',   // сохраняются в CrudModel
 *              'phones',   // сохраняются тут
 *              //'images', // сохраняются в ViewAction
 *          ],
 *      ],
 *  ],
 */
class SaveRelationsBehavior extends Behavior
{
    const RELATION_KEY_FORM_NAME = 'formName';
    const RELATION_KEY_RELATION_NAME = 'relationName';

    public $relations = [];
    public $relationKeyName = self::RELATION_KEY_FORM_NAME;

    private $_relations = [];
    private $_oldRelationValue = []; // Сохраните начальное значение отношений
    private $_newRelationValue = []; // Значение отношений обновления магазина
    private $_relationsToDelete = [];
    private $_relationsSaveStarted = false;

    /** @var BaseActiveRecord[] $_savedHasOneModels */
    private $_savedHasOneModels = [];

    private $_relationsScenario = [];
    private $_relationsExtraColumns = [];
    private $_relationsCascadeDelete = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $allowedProperties = ['scenario', 'extraColumns', 'cascadeDelete'];
        // Для всех связей для сохранения
        foreach ($this->relations as $key => $value) {
            // если просто название связи
            if (is_int($key)) {
                // добавим связь
                $this->_relations[] = $value;
            } else {
                $this->_relations[] = $key;
                if (is_array($value)) {
                    foreach ($value as $propertyKey => $propertyValue) {
                        if (in_array($propertyKey, $allowedProperties)) {
                            $this->{'_relations' . ucfirst($propertyKey)}[$key] = $propertyValue;
                        } else {
                            throw new UnknownPropertyException('The relation property named ' . $propertyKey . ' is not supported');
                        }
                    }
                }
            }
        }
        //dd($this);
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            BaseActiveRecord::EVENT_AFTER_VALIDATE  => 'afterValidate',
            BaseActiveRecord::EVENT_AFTER_INSERT    => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_UPDATE    => 'afterSave',
            BaseActiveRecord::EVENT_BEFORE_DELETE   => 'beforeDelete',
            BaseActiveRecord::EVENT_AFTER_DELETE    => 'afterDelete'
        ];
    }

    /**
     * Проверьте, привязано ли поведение к активной записи.
     *
     * @param Component $owner
     *
     * @throws RuntimeException
     */
    public function attach($owner)
    {
        if (!($owner instanceof BaseActiveRecord)) {
            throw new RuntimeException('Owner must be instance of yii\db\BaseActiveRecord');
        }
        parent::attach($owner);
    }

    /**
     * Переопределить метод canSetProperty, чтобы иметь возможность определять, разрешен ли установщик отношений.
     * Сеттер разрешен, если отношение объявлено в параметре Relations.
     *
     * @param string $name
     * @param bool   $checkVars
     *
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        $relation = $owner->getRelation($name, false);
        if (in_array($name, $this->_relations) && !is_null($relation)) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars);
    }

    /**
     * Переопределить метод __set, чтобы иметь возможность устанавливать значения отношений,
     * предоставляя экземпляр модели, значение первичного ключа или ассоциативный массив
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws InvalidArgumentException
     */
    public function __set($name, $value)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        if (in_array($name, $this->_relations)) {
            Yii::debug("Setting {$name} relation value", __METHOD__);
            /** @var ActiveQuery $relation */
            $relation = $owner->getRelation($name);
            if (!isset($this->_oldRelationValue[$name])) {
                if ($owner->isNewRecord) {
                    if ($relation->multiple === true) {
                        $this->_oldRelationValue[$name] = [];
                    } else {
                        $this->_oldRelationValue[$name] = null;
                    }
                } else {
                    $this->_oldRelationValue[$name] = $owner->{$name};
                }
            }
            if ($relation->multiple === true) {
                $this->setMultipleRelation($name, $value);
            } else {
                $this->setSingleRelation($name, $value);
            }
        }
    }

    /**
     * Установить именованное единственное отношение с заданным значением
     *
     * @param string $relationName
     * @param        $value
     *
     * @throws InvalidArgumentException
     */
    protected function setSingleRelation($relationName, $value)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        /** @var ActiveQuery $relation */
        $relation = $owner->getRelation($relationName);
        if (!($value instanceof $relation->modelClass)) {
            $value = $this->processModelAsArray($value, $relation, $relationName);
        }
        $this->_newRelationValue[$relationName] = $value;
        $owner->populateRelation($relationName, $value);
    }

    /**
     * Установить именованное множественное отношение с заданным значением
     *
     * @param string $relationName
     * @param        $value
     *
     * @throws InvalidArgumentException
     */
    protected function setMultipleRelation($relationName, $value)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        /** @var ActiveQuery $relation */
        $relation = $owner->getRelation($relationName);
        $newRelations = [];
        if (!is_array($value)) {
            if (!empty($value)) {
                $value = [$value];
            } else {
                $value = [];
            }
        }
        foreach ($value as $entry) {
            if ($entry instanceof $relation->modelClass) {
                $newRelations[] = $entry;
            } else {
                // TODO handle this with one DB request to retrieve all models
                $newRelations[] = $this->processModelAsArray($entry, $relation, $relationName);
            }
        }
        $this->_newRelationValue[$relationName] = $newRelations;
        $owner->populateRelation($relationName, $newRelations);
    }

    /**
     * Получите модель BaseActiveRecord, используя заданный параметр $data.
     * $data может быть либо идентификатором модели, либо ассоциативным массивом, представляющим атрибуты модели => значения.
     *
     * @param mixed       $data
     * @param ActiveQuery $relation
     *
     * @return BaseActiveRecord
     */
    protected function processModelAsArray($data, $relation, $name)
    {
        /** @var BaseActiveRecord $modelClass */
        $modelClass = $relation->modelClass;
        $fks = $this->_getRelatedFks($data, $relation, $modelClass);
        return $this->_loadOrCreateRelationModel($data, $fks, $modelClass, $name);
    }

    /**
     * Получите внешние ключи соответствующей модели.
     *
     * @param $data
     * @param $relation
     * @param BaseActiveRecord $modelClass
     *
     * @return array
     */
    private function _getRelatedFks($data, $relation, $modelClass)
    {
        $fks = [];
        if (is_array($data)) {
            // Получите правильное определение ссылки
            if ($relation->via instanceof BaseActiveRecord) {
                $link = $relation->via->link;
            } elseif (is_array($relation->via)) {
                [$viaName, $viaQuery] = $relation->via;
                $link = $viaQuery->link;
            } else {
                $link = $relation->link;
            }
            // поиск PK
            foreach ($modelClass::primaryKey() as $modelAttribute) {
                if (isset($data[$modelAttribute])) {
                    $fks[$modelAttribute] = $data[$modelAttribute];
                } elseif ($relation->multiple && !$relation->via) {
                    foreach ($link as $relatedAttribute => $relatedModelAttribute) {
                        if (!isset($data[$relatedAttribute]) && in_array($relatedAttribute, $modelClass::primaryKey())) {
                            $fks[$relatedAttribute] = $this->owner->{$relatedModelAttribute};
                        }
                    }
                } else {
                    $fks = [];
                    break;
                }
            }
            if (empty($fks)) {
                foreach ($link as $relatedAttribute => $modelAttribute) {
                    if (isset($data[$modelAttribute])) {
                        $fks[$modelAttribute] = $data[$modelAttribute];
                    }
                }
            }
        } else {
            $fks = $data;
        }
        return $fks;
    }

    /**
     * Загрузите существующую модель или создайте ее, если ключ не был предоставлен и данные не пусты.
     *
     * @param $data
     * @param $fks
     * @param $modelClass
     * @param string $relationName
     *
     * @return BaseActiveRecord
     */
    private function _loadOrCreateRelationModel($data, $fks, $modelClass, $relationName)
    {
        /** @var BaseActiveRecord $relationModel */
        $relationModel = null;
        if (!empty($fks)) {
            $relationModel = $modelClass::findOne($fks);
        }
        if (!($relationModel instanceof BaseActiveRecord) && !empty($data)) {
            $relationModel = new $modelClass;
        }
        // Если установлен пользовательский сценарий, примените его здесь, чтобы правильно установить атрибуты модели
        if (array_key_exists($relationName, $this->_relationsScenario)) {
            $relationModel->setScenario($this->_relationsScenario[$relationName]);
        }
        if (($relationModel instanceof BaseActiveRecord) && is_array($data)) {
            $relationModel->setAttributes($data);
            if ($relationModel->hasMethod('loadRelations')) {
                $relationModel->loadRelations($data);
            }
        }
        return $relationModel;
    }

    /**
     * Перед проверкой модели владельца сохраните связанные модели.
     * Для отношений hasOne() установите соответствующие внешние ключи модели владельца, чтобы иметь возможность проверить ее.
     *
     * @param ModelEvent $event
     *
     * @throws DbException
     * @throws InvalidConfigException
     */
    public function beforeValidate(ModelEvent $event)
    {
        if ($this->_relationsSaveStarted === false && !empty($this->_oldRelationValue)) {
            /* @var $model BaseActiveRecord */
            $model = $this->owner;
            if ($this->saveRelatedRecords($model, $event)) {
                // Если отношение имеет значение has_one, попробуйте установить связанные атрибуты модели
                foreach ($this->_relations as $relationName) {
                    // Связь не установлена, ничего не делайте...
                    if (array_key_exists($relationName, $this->_oldRelationValue)) {
                        $this->_setRelationForeignKeys($relationName);
                    }
                }
            }
        }
    }

    /**
     * После проверки модели владельца откатите вновь сохраненные отношения hasOne, если это не удалось.
     *
     * @throws DbException
     */
    public function afterValidate()
    {
        if ($this->owner->hasErrors() && !empty($this->_savedHasOneModels)) {
            $this->_rollbackSavedHasOneModels();
        }
    }

    /**
     * Подготовьте каждую связанную модель (подтвердите или сохраните при необходимости).
     * Это делается во время процесса предварительной проверки, чтобы иметь возможность
     * для установки связанных внешних ключей для вновь созданных записей.
     *
     * @param BaseActiveRecord $model
     * @param ModelEvent       $event
     *
     * @return bool
     * @throws DbException
     * @throws InvalidConfigException
     */
    protected function saveRelatedRecords(BaseActiveRecord $model, ModelEvent $event)
    {
        try {
            foreach ($this->_relations as $relationName) {
                if (array_key_exists($relationName, $this->_oldRelationValue)) { // Relation was not set, do nothing...
                    /** @var ActiveQuery $relation */
                    $relation = $model->getRelation($relationName);
                    if (!empty($model->{$relationName})) {
                        if ($relation->multiple === false) {
                            $this->_prepareHasOneRelation($model, $relationName, $event);
                        } else {
                            $this->_prepareHasManyRelation($model, $relationName);
                        }
                    }
                }
            }
            if (!$event->isValid) {
                throw new Exception('One of the related model could not be validated');
            }
        } catch (Exception $e) {
            Yii::warning(get_class($e) . ' was thrown while saving related records during beforeValidate event: ' . $e->getMessage(), __METHOD__);
            $this->_rollbackSavedHasOneModels(); // Откат сохраненных записей в процессе проверки, если таковые имеются.
            $model->addError($model->formName(), $e->getMessage());
            $event->isValid = false; // Прекратить сохранение, что-то пошло не так
            return false;
        }
        return true;
    }

    /**
     * @param BaseActiveRecord $model
     * @param ModelEvent       $event
     * @param string           $relationName
     */
    private function _prepareHasOneRelation(BaseActiveRecord $model, $relationName, ModelEvent $event)
    {
        Yii::debug("_prepareHasOneRelation for {$relationName}", __METHOD__);
        $relationModel = $model->{$relationName};
        $this->validateRelationModel(self::prettyRelationName($relationName), $relationName, $model->{$relationName});
        $relation = $model->getRelation($relationName);
        $p1 = $model->isPrimaryKey(array_keys($relation->link));
        $p2 = $relationModel::isPrimaryKey(array_values($relation->link));
        if ($relationModel->getIsNewRecord() && $p1 && !$p2) {
            // Сохранить Имеет одну связь новую запись
            if ($event->isValid && (count($model->dirtyAttributes) || $model->{$relationName}->isNewRecord)) {
                Yii::debug('Saving ' . self::prettyRelationName($relationName) . ' relation model', __METHOD__);
                if ($model->{$relationName}->save()) {
                    $this->_savedHasOneModels[] = $model->{$relationName};
                }
            }
        }
    }

    /**
     * Проверьте модель отношений и при необходимости добавьте сообщение об ошибке в атрибут модели владельца.
     *
     * @param string           $prettyRelationName
     * @param string           $relationName
     * @param BaseActiveRecord $relationModel
     */
    protected function validateRelationModel($prettyRelationName, $relationName, BaseActiveRecord $relationModel)
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        if (!is_null($relationModel) && ($relationModel->isNewRecord || count($relationModel->getDirtyAttributes()))) {
            Yii::debug("Validating {$prettyRelationName} relation model using " . $relationModel->scenario . ' scenario', __METHOD__);
            if (!$relationModel->validate()) {
                $this->_addError($relationModel, $model, $relationName, $prettyRelationName);
            }
        }
    }

    /**
     * Прикрепляйте ошибки к реляционным атрибутам владельца.
     *
     * @param BaseActiveRecord $relationModel
     * @param BaseActiveRecord $owner
     * @param string           $relationName
     * @param string           $prettyRelationName
     */
    private function _addError($relationModel, $owner, $relationName, $prettyRelationName)
    {
        foreach ($relationModel->errors as $attribute => $attributeErrors) {
            foreach ($attributeErrors as $error) {
                $owner->addError($relationName, "{$prettyRelationName}: {$error}");
            }
        }
    }

    /**
     * @param string   $relationName
     * @param int|null $i
     *
     * @return string
     */
    protected static function prettyRelationName($relationName, $i = null)
    {
        return Inflector::camel2words($relationName, true) . (is_null($i) ? '' : " #{$i}");
    }

    /**
     * @param BaseActiveRecord $model
     * @param string           $relationName
     */
    private function _prepareHasManyRelation(BaseActiveRecord $model, $relationName)
    {
        /** @var BaseActiveRecord $relationModel */
        foreach ($model->{$relationName} as $i => $relationModel) {
            $this->validateRelationModel(self::prettyRelationName($relationName, $i), $relationName, $relationModel);
        }
    }

    /**
     * Удалить вновь созданные. Имеет одну модель, если таковая имеется.
     *
     * @throws DbException
     */
    private function _rollbackSavedHasOneModels()
    {
        foreach ($this->_savedHasOneModels as $savedHasOneModel) {
            $savedHasOneModel->delete();
        }
        $this->_savedHasOneModels = [];
    }

    /**
     * Установите внешние ключи связи, указывающие на первичный ключ владельца.
     *
     * @param string $relationName
     */
    protected function _setRelationForeignKeys($relationName)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        /** @var ActiveQuery $relation */
        $relation = $owner->getRelation($relationName);
        if ($relation->multiple === false && !empty($owner->{$relationName})) {
            Yii::debug("Setting foreign keys for {$relationName}", __METHOD__);
            foreach ($relation->link as $relatedAttribute => $modelAttribute) {
                if ($owner->{$modelAttribute} !== $owner->{$relationName}->{$relatedAttribute}) {
                    if ($owner->{$relationName}->isNewRecord) {
                        $owner->{$relationName}->save();
                    }
                    $owner->{$modelAttribute} = $owner->{$relationName}->{$relatedAttribute};
                }
            }
        }
    }

    /**
     * Ссылка на соответствующие модели.
     * Если модели не менялись, ничего не будет сделано.
     * Связанные записи будут связаны с моделью владельца с помощью метода BaseActiveRecord `link()`.
     *
     * @throws Exception
     */
    public function afterSave()
    {
        if ($this->_relationsSaveStarted === false) {
            /** @var BaseActiveRecord $owner */
            $owner = $this->owner;
            $this->_relationsSaveStarted = true;
            // Populate relations with updated values
            foreach ($this->_newRelationValue as $name => $value) {
                $owner->populateRelation($name, $value);
            }
            try {
                foreach ($this->_relations as $relationName) {
                    if (array_key_exists($relationName, $this->_oldRelationValue)) { // Relation was not set, do nothing...
                        Yii::debug("Linking {$relationName} relation", __METHOD__);
                        /** @var ActiveQuery $relation */
                        $relation = $owner->getRelation($relationName);
                        if ($relation->multiple === true) { // Has many relation
                            $this->_afterSaveHasManyRelation($relationName);
                        } else { // Has one relation
                            $this->_afterSaveHasOneRelation($relationName);
                        }
                        unset($this->_oldRelationValue[$relationName]);
                    }
                }
            } catch (Exception $e) {
                Yii::warning(get_class($e) . ' was thrown while saving related records during afterSave event: ' . $e->getMessage(), __METHOD__);
                $this->_rollbackSavedHasOneModels();
                /**
                 * К сожалению, это обязательно, поскольку ошибка произошла во время события afterSave.
                 * и мы не хотим, чтобы пользователь/разработчик не знал об этой проблеме.
                 **/
                throw $e;
            }
            $owner->refresh();
            $this->_relationsSaveStarted = false;
        }
    }

    /**
     * @param string $relationName
     *
     * @throws DbException
     */
    public function _afterSaveHasManyRelation($relationName)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        /** @var ActiveQuery $relation */
        $relation = $owner->getRelation($relationName);

        // Обрабатывать новые отношения
        $existingRecords = [];
        /** @var ActiveQuery $relationModel */
        foreach ($owner->{$relationName} as $i => $relationModel) {
            /** @var BaseActiveRecord $relationModel */
            if ($relationModel->isNewRecord) {
                if (!empty($relation->via)) {
                    if ($relationModel->validate()) {
                        $relationModel->save();
                    } else {
                        $this->_addError($relationModel, $owner, $relationName, self::prettyRelationName($relationName, $i));
                        throw new DbException('Related record ' . self::prettyRelationName($relationName, $i) . ' could not be saved.');
                    }
                }
                $junctionTableColumns = $this->_getJunctionTableColumns($relationName, $relationModel);
                $owner->link($relationName, $relationModel, $junctionTableColumns);
            } else {
                $existingRecords[] = $relationModel;
            }
            if (count($relationModel->dirtyAttributes)) {
                if ($relationModel->validate()) {
                    $relationModel->save();
                } else {
                    $this->_addError($relationModel, $owner, $relationName, self::prettyRelationName($relationName));
                    throw new DbException('Related record ' . self::prettyRelationName($relationName) . ' could not be saved.');
                }
            }
        }
        $junctionTablePropertiesUsed = array_key_exists($relationName, $this->_relationsExtraColumns);

        // Обрабатывать существующие добавленные и удаленные связи
        [$addedPks, $deletedPks] = $this->_computePkDiff(
            $this->_oldRelationValue[$relationName],
            $existingRecords,
            $junctionTablePropertiesUsed
        );

        // Удаленные отношения
        $initialModels = ArrayHelper::index($this->_oldRelationValue[$relationName], function (BaseActiveRecord $model) {
            return implode('-', $model->getPrimaryKey(true));
        });
        $initialRelations = $owner->{$relationName};
        foreach ($deletedPks as $key) {
            $owner->unlink($relationName, $initialModels[$key], true);
        }

        // Добавлены отношения
        $actualModels = ArrayHelper::index(
            $junctionTablePropertiesUsed ? $initialRelations : $owner->{$relationName},
            function (BaseActiveRecord $model) {
                return implode('-', $model->getPrimaryKey(true));
            }
        );
        foreach ($addedPks as $key) {
            $junctionTableColumns = $this->_getJunctionTableColumns($relationName, $actualModels[$key]);
            $owner->link($relationName, $actualModels[$key], $junctionTableColumns);
        }
    }

    /**
     * Возвращает массив столбцов для сохранения в соединительной таблице для связанной модели, имеющей отношение «многие ко многим».
     *
     * @param string           $relationName
     * @param BaseActiveRecord $model
     *
     * @return array
     * @throws \RuntimeException
     */
    private function _getJunctionTableColumns($relationName, $model)
    {
        $junctionTableColumns = [];
        if (array_key_exists($relationName, $this->_relationsExtraColumns)) {
            if (is_callable($this->_relationsExtraColumns[$relationName])) {
                $junctionTableColumns = $this->_relationsExtraColumns[$relationName]($model);
            } elseif (is_array($this->_relationsExtraColumns[$relationName])) {
                $junctionTableColumns = $this->_relationsExtraColumns[$relationName];
            }
            if (!is_array($junctionTableColumns)) {
                throw new RuntimeException(
                    'Junction table columns definition must return an array, got ' . gettype($junctionTableColumns)
                );
            }
        }
        return $junctionTableColumns;
    }

    /**
     * Вычислить разницу между двумя наборами записей, используя «токены» первичных ключей.
     * Если для третьего параметра установлено значение true, все первоначальные связанные записи будут помечены для удаления,
     * даже если их свойства не изменились.
     * Это может быть удобно в отношениях «многие ко многим», включающих соединительную таблицу.
     *
     * @param BaseActiveRecord[] $initialRelations
     * @param BaseActiveRecord[] $updatedRelations
     * @param bool               $forceSave
     *
     * @return array
     */
    private function _computePkDiff($initialRelations, $updatedRelations, $forceSave = false)
    {
        // Вычисление различий между первоначальными отношениями и текущими
        $oldPks = ArrayHelper::getColumn($initialRelations, function (BaseActiveRecord $model) {
            return implode('-', $model->getPrimaryKey(true));
        });
        $newPks = ArrayHelper::getColumn($updatedRelations, function (BaseActiveRecord $model) {
            return implode('-', $model->getPrimaryKey(true));
        });
        if ($forceSave) {
            $addedPks = $newPks;
            $deletedPks = $oldPks;
        } else {
            $identicalPks = array_intersect($oldPks, $newPks);
            $addedPks = array_values(array_diff($newPks, $identicalPks));
            $deletedPks = array_values(array_diff($oldPks, $identicalPks));
        }
        return [$addedPks, $deletedPks];
    }

    /**
     * @param string $relationName
     *
     * @throws InvalidCallException
     */
    private function _afterSaveHasOneRelation($relationName)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        if ($this->_oldRelationValue[$relationName] !== $owner->{$relationName}) {
            if ($owner->{$relationName} instanceof BaseActiveRecord) {
                $owner->link($relationName, $owner->{$relationName});
            } else {
                if ($this->_oldRelationValue[$relationName] instanceof BaseActiveRecord) {
                    $owner->unlink($relationName, $this->_oldRelationValue[$relationName]);
                }
            }
        }
        if ($owner->{$relationName} instanceof BaseActiveRecord) {
            $owner->{$relationName}->save();
        }
    }

    /**
     * Получить список отношений модели владельца, чтобы иметь возможность удалить их после удаления.
     */
    public function beforeDelete()
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        foreach ($this->_relationsCascadeDelete as $relationName => $params) {
            if ($params === true) {
                /** @var ActiveQuery $relation */
                $relation = $owner->getRelation($relationName);
                if (!empty($owner->{$relationName})) {
                    if ($relation->multiple === true) { // Has many relation
                        $this->_relationsToDelete = ArrayHelper::merge($this->_relationsToDelete, $owner->{$relationName});
                    } else {
                        $this->_relationsToDelete[] = $owner->{$relationName};
                    }
                }
            }
        }
    }

    /**
     * Удаление связанных моделей, помеченных как подлежащие удалению.
     *
     * @throws Exception
     */
    public function afterDelete()
    {
        /** @var BaseActiveRecord $modelToDelete */
        foreach ($this->_relationsToDelete as $modelToDelete) {
            try {
                if (!$modelToDelete->delete()) {
                    throw new DbException('Could not delete the related record: ' . $modelToDelete::className() . '(' . VarDumper::dumpAsString($modelToDelete->primaryKey) . ')');
                }
            } catch (Exception $e) {
                Yii::warning(get_class($e) . ' was thrown while deleting related records during afterDelete event: ' . $e->getMessage(), __METHOD__);
                $this->_rollbackSavedHasOneModels();
                throw $e;
            }
        }
    }

    /**
     * Заполняет отношения входными данными.
     *
     * @param array $data
     *
     * @throws InvalidConfigException
     */
    public function loadRelations($data)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        //dd($this->_relations);
        foreach ($this->_relations as $relationName) {
            $keyName = $this->_getRelationKeyName($relationName);
            if (array_key_exists($keyName, $data)) {
                $owner->{$relationName} = $data[$keyName];
            }
        }
    }

    /**
     * Установить сценарий для данного отношения
     *
     * @param string $relationName
     * @param string $scenario
     *
     * @throws InvalidArgumentException
     */
    public function setRelationScenario($relationName, $scenario)
    {
        /** @var BaseActiveRecord $owner */
        $owner = $this->owner;
        $relation = $owner->getRelation($relationName, false);
        if (in_array($relationName, $this->_relations) && !is_null($relation)) {
            $this->_relationsScenario[$relationName] = $scenario;
        } else {
            throw new InvalidArgumentException('Unknown ' . $relationName . ' relation');
        }
    }

    /**
     * @param string $relationName
     *
     * @return mixed
     * @throws InvalidConfigException
     */
    private function _getRelationKeyName($relationName)
    {
        switch ($this->relationKeyName) {
            case self::RELATION_KEY_RELATION_NAME:
                $keyName = $relationName;
                break;
            case self::RELATION_KEY_FORM_NAME:
                /** @var BaseActiveRecord $owner */
                $owner = $this->owner;
                /** @var ActiveQuery $relation */
                $relation = $owner->getRelation($relationName);
                $modelClass = $relation->modelClass;
                /** @var ActiveQuery $relationalModel */
                $relationalModel = new $modelClass;
                $keyName = $relationalModel->formName();
                break;
            default:
                throw new InvalidConfigException('Unknown relation key name');
        }
        return $keyName;
    }

    /**
     * Вернуть старые значения отношений.
     *
     * @return array Старые отношения (пары имя-значение)
     */
    public function getOldRelations()
    {
        $oldRelations = [];
        foreach ($this->_relations as $relationName) {
            $oldRelations[$relationName] = $this->getOldRelation($relationName);
        }
        return $oldRelations;
    }

    /**
     * Возвращает старое значение именованного отношения.
     *
     * @param string $relationName Имя отношения, определенное в параметре поведения `relations`.
     *
     * @return mixed
     */
    public function getOldRelation($relationName)
    {
        return array_key_exists($relationName, $this->_oldRelationValue)
            ? $this->_oldRelationValue[$relationName]
            : $this->owner->{$relationName};
    }

    /**
     * Возвращает отношения, которые были изменены с момента их загрузки.
     *
     * @return array Измененные отношения (пары имя-значение)
     */
    public function getDirtyRelations()
    {
        $dirtyRelations = [];
        foreach ($this->_relations as $relationName) {
            if (array_key_exists($relationName, $this->_oldRelationValue)) {
                $dirtyRelations[$relationName] = $this->owner->{$relationName};
            }
        }
        return $dirtyRelations;
    }

    /**
     * Пометить отношение как грязное
     *
     * @param string $relationName
     *
     * @return bool Удалась ли операция.
     */
    public function markRelationDirty($relationName)
    {
        if (in_array($relationName, $this->_relations) && !array_key_exists($relationName, $this->_oldRelationValue)) {
            $this->_oldRelationValue[$relationName] = $this->owner->{$relationName};
            return true;
        }
        return false;
    }
}
