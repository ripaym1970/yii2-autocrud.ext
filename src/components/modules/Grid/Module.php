<?php

namespace ripaym1970\autocrud\components\modules\Grid;

/**
 * @property \yii\db\ActiveRecord[] $possibleShares
 */
class Module extends \ripaym1970\autocrud\components\AbstractModule
{
    public const PROFILES_MANAGEMENT_PERMISSION = 'grid_profile_management_permission';
    public const CAN_MANAGE_OWN_PROFILES = 'grid_own_profiles_management';
    public const CAN_ACCESS_LIMITED_PROFILE = 'grid_limited_profile_access';

    /**
     * @var callable|null $sharesByOwner
     *      // this should return possible user's shares
     *      // (for example object of UserGroup's models, which user belongs to)
     *      'sharesByOwner' => function(\yii\db\ActiveRecord $user) {
     *               return $user->userGroups
     *      }
     */
    public $sharesByOwner;

    /**
     * @var callable|null $availableShares
     *      // this should return the whole collection of possible shares, in the example below - all UserGroup models
     *      'availableShares' => function() {
     *              return UserGroup::find()->orderBy("name")->all();
     *      },
     */
    public $availableShares;

    /**
     * @var callable $profileOwnerName
     *      // this should return user name [with email] to indicate grid profile author
     *      'profileOwnerName' => function(User $user) {
     *          return $user->name . ' ( ' . $user->email . ')';
     *      }
     */
    public $profileOwnerName;

    /**
     * @var callable|null $profilesListFilter
     */
    public $profilesListFilterClass;

    /**
     * @var callable $shareRepresentation
     *      // this should return share name or another representation (to be used in the shares tab dropdown)
     *      'shareRepresentation' => function(UserGroup $userGroup) {
     *          return $userGroup->name;
     *      }
     */
    public $shareRepresentation;

    /**
     * @var callable|null
     */
    public $identitiesByShare;

    public function init()
    {
        if (!$this->profileOwnerName) {
            throw new \yii\base\InvalidConfigException("profileOwnerName is required");
        }

        foreach ($this->getPossibleShareClasses() as $class) {
            \yii\base\Event::on(
                $class,
                $class::EVENT_BEFORE_DELETE,
                [self::class, 'onGridProfileShareOwnerBeforeDelete']
            );
        }

        return parent::init();
    }


    public static function menuItems(): array
    {
        return [
            'label' => \Yii::t('app', "Grid Profiles Management"),
            'url' => ['/grid/profiles-management/index'],
            \ripaym1970\autocrud\components\modules\Rbac\Module::MENU_ITEM_ROLE_RESTRICTION => self::PROFILES_MANAGEMENT_PERMISSION,
        ];
    }

    public static function onGridProfileShareOwnerBeforeDelete(\yii\base\ModelEvent $e)
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = $e->sender;
        $shares = models\GridProfileShare::find()
            ->andWhere(
                [
                    'parent_class' => get_class($model),
                    'parent_id' => $model->id
                ]
            )
            ->all();

        \ripaym1970\autocrud\components\Util::deleteCollection($shares);
    }

    public function getOwnerIdentifier(\yii\db\ActiveRecord $owner)
    {
        return call_user_func($this->profileOwnerName, $owner);
    }

    public function getOwnerShares(\yii\db\ActiveRecord $owner)
    {
        return is_callable($this->sharesByOwner)
            ? call_user_func($this->sharesByOwner, $owner)
            : [];
    }

    public function getPossibleShares()
    {
        static $availableShares = null;
        if (is_null($availableShares)) {
            $availableShares = is_callable($this->availableShares)
                ? call_user_func($this->availableShares)
                : [];
        }
        return $availableShares;
    }

    protected function getPossibleShareClasses()
    {
        return array_unique(
            array_map(
                function ($share) {
                    return get_class($share);
                },
                $this->possibleShares
            )
        );
    }

    public function getShareRepresentation(?\yii\db\ActiveRecord $share)
    {
        return is_callable($this->shareRepresentation)
            ? call_user_func($this->shareRepresentation, $share)
            : [];
    }
}
