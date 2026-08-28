<?php

namespace ripaym1970\autocrud\models\interfaces\basic;

use yii\behaviors\TimestampBehavior;
use yii\db\BaseActiveRecord;
use yii2tech\ar\variation\VariationBehavior;

/**
 * @property int id
 */
class ModelInterface extends \yii\db\ActiveRecord
    implements \yii\db\ActiveRecordInterface
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'timestampBehavior' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
            ],
            'translations' => [
                'class' => VariationBehavior::class,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getVariationModels()
    {
        return [];
    }
}
