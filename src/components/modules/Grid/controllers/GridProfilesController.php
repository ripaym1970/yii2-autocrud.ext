<?php

namespace ripaym1970\autocrud\components\modules\Grid\controllers;

use Yii;

/**
 * @mixin \ripaym1970\autocrud\components\behaviors\Finder
 */
class GridProfilesController extends \ripaym1970\autocrud\components\TypicalController
{
    const MODEL_CLASS = \ripaym1970\autocrud\components\modules\Grid\models\GridProfile::class;

    public function behaviors()
    {
        return \yii\helpers\ArrayHelper::merge(
            parent::behaviors(),
            [
                [
                    'class' => \ripaym1970\autocrud\components\behaviors\Finder::class,
                    'checkAccess' => function (\ripaym1970\autocrud\components\modules\Grid\models\GridProfile $model) {
                        /** @var \yii\web\User $user */
                        $user = Yii::$app->{$this->userAccessComponent};
                        return $model->parent_id == $user->id
                            && $model->parent_class == $user->identityClass;
                    }
                ]
            ]
        );
    }


    public function getAccessControlRules(): array
    {
        if (!Yii::$app->authManager) {
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


    public function actions()
    {
        return \yii\helpers\ArrayHelper::merge(
            parent::actions(),
            [
                'destroy' => \ripaym1970\autocrud\components\TypicalActions\Destroy::class,
            ]
        );
    }

    public function actionEdit($id = null)
    {
        /** @var \yii\web\User $user */
        $user = Yii::$app->{$this->userAccessComponent};

        $model = $id
            ? $this->findModel($id)
            : new \ripaym1970\autocrud\components\modules\Grid\models\GridProfile(
                [
                    'parent_class' => $user->identityClass,
                    'parent_id' => $user->id,
                ]
            );

        if (Yii::$app->request->isGet) {
            if ($model->isNewRecord) {
                Yii::$app->session->addFlash(
                    'info',
                    Yii::t(
                        "app",
                        "You can not modify this profile, but you can save it under different name"
                    )
                );
            }
            return $this->renderAjax('edit', ['model' => $model]);
        }

        $fields = [
            'name',
            'notes',
            'data',
            'auto_refresh_time',
            'has_custom_filter',
        ];
        if ($model->isNewRecord) {
            $fields = array_merge(
                $fields,
                [
                    'url',
                    'type_id',
                ]
            );
        }

        $data = \yii\helpers\Json::decode(Yii::$app->request->rawBody);
        if (!empty($data['saveFilters'])) {
            $fields[] = 'data_filter';
        }
        foreach ($fields as $field) {
            $model->$field = $data[$field];
        }


        $transaction = \ripaym1970\autocrud\components\Util::makeTransaction(true);
        \ripaym1970\autocrud\components\Util::saveModel($model);
        $shareIds = array_filter(
            \yii\helpers\ArrayHelper::getValue($data, 'shareIds', [])
                ?: []
        );
        $model->setShares($shareIds);
        $transaction->commit();

        return $this->asJson($model->profileRepresentation);
    }

    public function actionClone($id)
    {
        /** @var $model \ripaym1970\autocrud\components\modules\Grid\models\GridProfile */
        $model = $this->findModel($id);
        return $this->asJson(
            $model->cloneModel()->profileRepresentation
        );
    }
}
