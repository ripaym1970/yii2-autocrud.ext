<?php


namespace ripaym1970\autocrud\components\modules\Grid\controllers;

class GridProfileUsageController extends \ripaym1970\autocrud\components\TypicalController
{

    public function getAccessControlRules(): array
    {
        if (!\Yii::$app->authManager) {
            return parent::getAccessControlRules();
        }

        return [
            [
                'roles' => [
                    \ripaym1970\autocrud\components\modules\Grid\Module::CAN_MANAGE_OWN_PROFILES
                ],
                'allow' => true,
            ],
            self::RESTRICT_EVERYBODY_RULE
        ];
    }


    public function actionTrack($id)
    {
        $previousId = $_REQUEST['previousId'];

        if ($id == $previousId) {
            return;
        }
        /** @var \yii\web\User $user */
        $user = \Yii::$app->{$this->userAccessComponent};
        \ripaym1970\autocrud\components\modules\Grid\models\GridProfileUsage::replace(
            $user->identity,
            $previousId,
            $id
        );
    }

}
