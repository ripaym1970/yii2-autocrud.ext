<?php

namespace ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors;

use ripaym1970\autocrud\components\modules\HistoricalRecords;
use ripaym1970\autocrud\components\Yiit;

/**
 * @property HistoricalRecords\models\HistoricalRecord[] $historicalRecords
 * @property \ripaym1970\autocrud\components\HtmlActions\Icon         $historyIcon
 */
class HistoricalRecord extends AbstractHistoricalRecord
{
    /** @return \yii\db\ActiveQuery */
    public function getHistoricalRecords()
    {
        return $this->owner
            ->hasMany(
                HistoricalRecords\models\HistoricalRecord::class,
                ['owner_id' => 'id',]
            )
            ->onCondition([
                'owner_class' => get_class($this->owner),
            ])
            ->orderBy('created_at');
    }

    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            \yii\db\ActiveRecord::EVENT_AFTER_DELETE => 'onDelete',
        ];
    }

    /**
     * Ниже есть конкретные экшены на Insert, Update, Delete
     * А этот метод хз где юзается
     *
     // * @param \yii\db\ActiveRecord $x
     // * @param string $message
     // * @return void
     // * @throws \yii\base\Exception
     // * @throws \yii\base\InvalidConfigException
     // * @throws \yii\base\UserException
     */
    // public function buildHistoricalMessage(\yii\db\ActiveRecord $x, string $message)
    // {
    //     //dd(get_class($x),$x->id,);
    //     $model = new HistoricalRecords\models\HistoricalRecord([
    //         'created_at' => time(),
    //         'details' => [
    //             'message' => $message,
    //         ],
    //         'action_id' => HistoricalRecords\models\HistoricalRecord::ACTION_ID_MESSAGE,
    //         'owner_class' => get_class($this->owner),
    //         'owner_id' => $this->owner->id,
    //         'author_class' => get_class($x),
    //         'author_id' => $x->id,
    //     ]);
    //     \ripaym1970\autocrud\components\Util::saveModel($model);
    // }

    public function getHistoricalDetailViewElement(string $field)
    {
        return [
            'label' => $this->owner->getAttributeLabel($field),
            'value' => $this->owner->getHistoryIconByField($field)
                . ' '
                . $this->owner->getRepresentation($field, $this->owner->$field),
            'format' => 'raw',
        ];
    }

    public function getHistoryIconByField(string $field)
    {
        $icon = \ripaym1970\autocrud\components\HtmlActions\Icon::dialogView([
            '/historical-records/historical-records/index-by-owner-and-field',
            'ownerClass' => get_class($this->owner),
            'ownerId' => $this->owner->id,
            'field' => $field,
            'getGridDefinition' => 1,
        ]);
        $icon->title = Yiit::t('Property History');
        $icon->dialogTitle = Yiit::t(
            "Historical Data for Model #{id} field '{field}'",
            [
                'id' => $this->owner->id,
                'field' => $field,
            ]
        );

        $hasHumanFriendlyName = \ripaym1970\autocrud\components\Util::implements(
            $this->owner,
            \ripaym1970\autocrud\components\interfaces\IHumanFriendlyName::class,
            false
        );
        if ($hasHumanFriendlyName) {
            $icon->dialogTitle .= ' (' . $this->owner::getHumanFriendlyName() . ')';
        }

        $icon->icon = \rmrevin\yii\fontawesome\FAS::icon(
            \rmrevin\yii\fontawesome\FAS::_HISTORY
        );
        return $icon;
    }

    public function getHistoryIcon()
    {
        $icon = \ripaym1970\autocrud\components\HtmlActions\Icon::dialogView([
                '/historical-records/historical-records/index-by-owner',
                'ownerClass' => get_class($this->owner),
                'ownerId' => $this->owner->id,
                'getGridDefinition' => 1,
            ]);
        $icon->title = Yiit::t('Object History');
        $icon->dialogTitle = Yiit::t(
            'Historical Data for Model #{id}',
            [
                'id' => $this->owner->id,
            ]
        );

        $hasHumanFriendlyName = \ripaym1970\autocrud\components\Util::implements(
            $this->owner,
            \ripaym1970\autocrud\components\interfaces\IHumanFriendlyName::class,
            false
        );
        if ($hasHumanFriendlyName) {
            $icon->dialogTitle .= ' (' . $this->owner::getHumanFriendlyName() . ')';
        }

        $icon->icon = \rmrevin\yii\fontawesome\FAS::icon(
            \rmrevin\yii\fontawesome\FAS::_HISTORY
        );
        return $icon;
    }


    public function afterInsert(\yii\db\AfterSaveEvent $event)
    {
        $model = $this->composeModel();
        $result = $this->processNewRecord($model);
        if (!$result) {
            return;
        }

        $model->details = json_encode($result);
        \ripaym1970\autocrud\components\Util::saveModel($model);
    }


    public function afterUpdate(\yii\db\AfterSaveEvent $event)
    {
        $model = $this->composeModel();
        $result = $this->processExistingRecord(
            $model,
            $event->changedAttributes
        );
        if (!$result) {
            return;
        }

        $model->details = json_encode($result);
        \ripaym1970\autocrud\components\Util::saveModel($model);
    }

    public function onDelete($event)
    {
        $model = $this->composeModel();
        $model->action_id = HistoricalRecords\models\HistoricalRecord::ACTION_ID_REMOVE;
        $model->details = json_encode($this->detailsFilter($this->owner->attributes));
        \ripaym1970\autocrud\components\Util::saveModel($model);
    }

    /** @return HistoricalRecords\models\HistoricalRecord */
    private function composeModel()
    {
        //$this->checkRepresentationInterface();
        $model = new HistoricalRecords\models\HistoricalRecord([
            'created_at'  => time(),
            'owner_class' => get_class($this->owner),
            'owner_id'    => $this->owner->{$this->primaryKey},
        ]);
        $model = $this->fillUserMap($model);
        return $model;
    }

    private function processNewRecord(HistoricalRecords\models\HistoricalRecord &$model)
    {
        $model->action_id = HistoricalRecords\models\HistoricalRecord::ACTION_ID_CREATE;
        return $this->detailsFilter($this->owner->attributes);
    }

    private function processExistingRecord(
        HistoricalRecords\models\HistoricalRecord &$model,
        array $changedAttributes
    ) {
        $model->action_id = $model::ACTION_ID_UPDATE;

        $oldAttributes = [];
        $newAttributes = [];

        foreach ($this->columnNames as $columnName) {
            $newValue = $this->owner->$columnName;
            $oldValue = $newValue;
            if (array_key_exists($columnName, $changedAttributes)) {
                $oldValue = $changedAttributes[$columnName];
            } elseif (array_key_exists($columnName, $this->owner->oldAttributes)) {
                $oldValue = $this->owner->oldAttributes[$columnName];
            }

            if ($oldValue == $newValue) {
                continue;
            }

            $oldAttributes[$columnName] = $oldValue;
            $newAttributes[$columnName] = $newValue;
        }

        // don't fill details if there were no changes
        return $newAttributes
            ? [
                'oldAttributes' => $oldAttributes,
                'newAttributes' => $newAttributes,
            ]
            : null;
    }
}
