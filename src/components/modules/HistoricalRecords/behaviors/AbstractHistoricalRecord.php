<?php

namespace ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors;

use ripaym1970\autocrud\components\interfaces\IStringRepresentation;
use ripaym1970\autocrud\components\Util;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * @property ActiveRecord|HistoricalRecord|IStringRepresentation $owner
 */
class AbstractHistoricalRecord extends Behavior
{
    public $primaryKey = 'id';
    public $excludeAttributes = [];

    protected function fillUserMap($model)
    {
        $userAccessComponent = Yii::$app->controller->userAccessComponent ?? 'user';

        $shouldReturn = !$userAccessComponent || Util::isInConsoleMode();
        if ($shouldReturn) {
            return $model;
        }

        $identityClass = Yii::$app->$userAccessComponent->identityClass;

        $id = Yii::$app->$userAccessComponent->id;
        if ($id) {
            $model->author_class = $identityClass;
            $model->author_id = $id;
        }

        //\ripaym1970\autocrud\components\Util::implements(
        //    $identityClass,
        //    \ripaym1970\autocrud\components\interfaces\IStringRepresentation::class,
        //    true
        //);

        return $model;
    }

    //protected function checkRepresentationInterface()
    //{
    //    \ripaym1970\autocrud\components\Util::hasBehavior(
    //        $this->owner,
    //        \ripaym1970\autocrud\components\behaviors\Representation::class,
    //        true
    //    );
    //}

    protected function getColumnNames()
    {
        $result = [];
        foreach ($this->owner->tableSchema->columns as $name => $data) {
            if (!in_array($name, $this->excludeAttributes)) {
                $result[] = $name;
            }
        }
        return $result;
    }

    protected function detailsFilter($attributes)
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            if (!in_array($name, $this->excludeAttributes)) {
                $result[$name] = $value;
            }
        }
        return $result;
    }
}
