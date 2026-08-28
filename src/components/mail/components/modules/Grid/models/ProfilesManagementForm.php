<?php

namespace ripaym1970\autocrud\components\mail\components\modules\Grid\models;

/**
 * @property GridProfile $profile;
 * @property array $shares
 */
class ProfilesManagementForm extends \yii\base\Model
{
    const SELECT_ALL = 'ALL';

    public $profileId;

    /** @var array $assigneeIds User Ids */
    public $assigneeIds = [];

    public function rules()
    {
        return [
            [
                ['profileId', 'assigneeIds'],
                'required',
            ],
            [
                ['assigneeIds'],
                'filter',
                'filter' => fn(array $x) => array_filter($x),
            ]
        ];
    }

    public function attributeLabels()
    {
        return [
            'assigneeIds' => \Yii::t(
                "app",
                "Select Entities to use selected Profile by default"
            ),
        ];
    }

    public function getProfile()
    {
        return \ripaym1970\autocrud\components\Util::findModel(
            GridProfile::class,
            $this->profileId
        );
    }

    public function getShares()
    {
        $module = \ripaym1970\autocrud\components\modules\Grid\Module::getInstance();
        $result = [];
        foreach ($this->profile->shares as $share) {
            $identities = $this->getIdentitiesForShare($share);
            if (!$identities) {
                continue;
            }
            $result [] = [
                'id' => $share->parent_id,
                'name' => $module->getShareRepresentation($share->owner),
                'identities' => $identities,
            ];
        }
        return $result;
    }

    protected function getIdentitiesForShare(GridProfileShare $share)
    {
        $module = \ripaym1970\autocrud\components\modules\Grid\Module::getInstance();
        if (!is_callable($module->identitiesByShare)) {
            return [];
        }
        $result = [];
        $identities = call_user_func($module->identitiesByShare, $share->owner);
        /** @var \ripaym1970\autocrud\components\interfaces\IStringRepresentation $identity */
        foreach ($identities as $identity) {
            $result[] = [
                'id' => $identity->id,
                'name' => $identity->stringRepresentation,
                'object' => $identity,
            ];
        }
        return $result;
    }

    public function setAssignees(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        if ($this->assigneeIds) {
            GridProfileUsage::bind(
                $this->profileId,
                array_diff($this->assigneeIds, [self::SELECT_ALL])
            );
        } else {
            GridProfileUsage::remove(
                $this->profileId
            );
        }
        return true;
    }
}
