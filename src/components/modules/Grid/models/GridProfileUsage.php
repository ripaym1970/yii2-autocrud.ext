<?php

namespace ripaym1970\autocrud\components\modules\Grid\models;

/**
 * @property int $id
 * @property int $profile_id
 * @property string $parent_class
 * @property int $parent_id
 *
 * @property GridProfile $profile
 *
 * @mixin \ripaym1970\autocrud\components\behaviors\Representation
 */
class GridProfileUsage extends \yii\db\ActiveRecord
    implements \ripaym1970\autocrud\components\interfaces\IPolymorphicModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'grid_profile_usage';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['parent_class', 'parent_id', 'profile_id'],
                'required'
            ],
            [
                ['parent_id', 'profile_id'],
                'integer'
            ],
            [
                ['parent_class'],
                \ripaym1970\autocrud\components\validators\Owner::class,
                'idField' => 'parent_id',
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
            'parent_class' => Yiit::t('Parent class'),
            'parent_id' => Yiit::t('Parent ID'),
            'profile_id' => Yiit::t('Profile ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(GridProfile::class, ['id' => 'profile_id']);
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

    public static function getAssigneeIds($profileId)
    {
        return self::find()
            ->select([
                'parent_id',
            ])
            ->andWhere([
                'profile_id' => $profileId,
            ])
            ->column();
    }

    public static function getLast(\yii\web\IdentityInterface $user, array $possibleProfileIds): ?GridProfile
    {
        return self::findOne([
            'profile_id' => $possibleProfileIds,
            'parent_class' => get_class($user),
            'parent_id' => $user->id,
        ])->profile ?? null;
    }

    public static function bind(int $profileId, array $userIds)
    {
        $module = \ripaym1970\autocrud\components\modules\Grid\Module::getInstance();
        $class = $module->profilesListFilterClass;
        if (!$class) {
            throw new \yii\base\InvalidConfigException("Callable profilesListFilter should return a class name.");
        }

        $transaction = \ripaym1970\autocrud\components\Util::makeTransaction(true);

        $notUsedUsages = self::find()
            ->andWhere([
                'profile_id' => $profileId,
                'parent_class' => $class,
            ])
            ->andWhere([
                'NOT',
                ['parent_id' => $userIds],
            ])
            ->all();
        \ripaym1970\autocrud\components\Util::deleteCollection($notUsedUsages);

        foreach ($userIds as $userId) {
            $notUser = $userId < 0
                || $userId == \ripaym1970\autocrud\components\modules\Grid\models\ProfilesManagementForm::SELECT_ALL;
            if ($notUser) {
                continue;
            }
            $params = [
                'profile_id' => $profileId,
                'parent_class' => $class,
                'parent_id' => $userId,
            ];
            $model = self::findOne($params)
                ?: new self($params);
            if ($model->isNewRecord) {
                \ripaym1970\autocrud\components\Util::saveModel($model);
            }
        }

        $transaction->commit();
    }

    public static function remove(int $profileId)
    {
        $possibleUsages = self::findAll([
            'profile_id' => $profileId,
        ]);

        \ripaym1970\autocrud\components\Util::deleteCollection($possibleUsages);
    }

    public static function replace(
        \yii\web\IdentityInterface $user,
        int $oldProfileId,
        int $newProfileId
    ) {
        $params = [
            'profile_id' => $oldProfileId,
            'parent_class' => get_class($user),
            'parent_id' => $user->id,
        ];
        $model = self::findOne($params)
            ?: new self($params);
        if ($newProfileId) {
            $model->profile_id = $newProfileId;
            \ripaym1970\autocrud\components\Util::saveModel($model);
            return;
        }
        // new profile is not set, which means we can remove tracking record
        if (!$model->isNewRecord) {
            \ripaym1970\autocrud\components\Util::deleteModel($model);
        }
    }

    // if user changed sharing option, we need to clean up
    // the tracking
    public static function fixSharing(GridProfile $profile)
    {
        // hack. It will remove all tracked usages for all users of given profile
        // which we don't need
        return;
        foreach ($profile->shares as $share) {
            if (!$share->parent_id) {
                return;
            }
        }
        \ripaym1970\autocrud\components\Util::deleteCollection($profile->usage);
    }
}
