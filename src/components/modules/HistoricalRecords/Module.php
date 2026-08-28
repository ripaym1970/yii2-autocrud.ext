<?php

namespace ripaym1970\autocrud\components\modules\HistoricalRecords;

use ripaym1970\autocrud\components\Yiit;

class Module extends \ripaym1970\autocrud\components\AbstractModule
{
    public const MANAGEMENT_PERMISSION = 'historical_records_access';

    public static function menuItems(): array
    {
        return [
            'label' => Yiit::t('Historical records'),
            'url' => [
                '/historical-records/historical-records/index',
            ],
            //\ripaym1970\autocrud\components\modules\Rbac\Module::MENU_ITEM_ROLE_RESTRICTION => self::MANAGEMENT_PERMISSION,
            //true,
        ];
    }

    public static function getGrid(\yii\web\User $user, \yii\db\ActiveRecord $parent)
    {
        return $user->can(self::MANAGEMENT_PERMISSION)
            ? controllers\HistoricalRecordsController::generalHelper($parent)->widget()
            : \ripaym1970\autocrud\components\widgets\HelpBlock::widget([
                'content' => Yiit::t('Not enough permissions'),
            ]);
    }
}
