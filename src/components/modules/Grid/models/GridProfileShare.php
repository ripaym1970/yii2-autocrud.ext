<?php

namespace ripaym1970\autocrud\components\modules\Grid\models;

/**
 *
 * @property integer $id
 * @property integer $profile_id
 * @property string $parent_class
 * @property integer $parent_id
 *
 * @property \yii\db\ActiveRecord $owner
 * @property GridProfile $profile
 * @mixin \ripaym1970\autocrud\components\behaviors\Representation
 */
class GridProfileShare extends \yii\db\ActiveRecord
    implements \ripaym1970\autocrud\components\interfaces\IPolymorphicModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'grid_profile_shares';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'profile_id',
                    'parent_class',
                ],
                'required',
            ],
            [
                ['profile_id', 'parent_id'],
                'integer'
            ],
            [
                ['parent_class'],
                \ripaym1970\autocrud\components\validators\Owner::class,
                'idField' => 'parent_id',
                'when' => function(self $x) {
                    return $x->parent_id;
                }
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yiit::t('ID'),
            'profile_id' => Yiit::t('Profile ID'),
            'parent_class' => Yiit::t('Parent class'),
            'parent_id' => Yiit::t('Parent ID'),
        ];
    }

    public function behaviors()
    {
        return \yii\helpers\ArrayHelper::merge(
            parent::behaviors(),
            [
                \ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
                \ripaym1970\autocrud\components\behaviors\Representation::class,
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(GridProfile::class, ['id' => 'profile_id']);
    }

    public function getOwner()
    {
        /** @var \yii\db\ActiveRecord $class */
        $class = $this->parent_class;
        return $class::findOne($this->parent_id);
    }

}
