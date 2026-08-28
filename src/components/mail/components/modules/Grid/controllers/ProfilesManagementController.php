<?php


namespace ripaym1970\autocrud\components\mail\components\modules\Grid\controllers;

use ripaym1970\autocrud\components\modules\Grid;
use Yii;

class ProfilesManagementController extends \ripaym1970\autocrud\components\TypicalController
{
    const MODEL_CLASS = Grid\models\GridProfile::class;

    public function behaviors()
    {
        return \yii\helpers\ArrayHelper::merge(
            parent::behaviors(),
            [
                \ripaym1970\autocrud\components\behaviors\Finder::class,
            ]
        );
    }

    public function getAccessControlRules(): array
    {
        return [
            [
                'roles' => [
                    Grid\Module::PROFILES_MANAGEMENT_PERMISSION,
                ],
                'allow' => true,
            ],
            self::RESTRICT_EVERYBODY_RULE
        ];
    }

    public static function getHelper()
    {
        $export = \ripaym1970\autocrud\components\HtmlActions\Icon::export(
            [
                '/grid/profiles-management/export',
            ]
        );
        $export->attributes['id'] = 'js-export';
        $export->enabled = false;

        $toolbar = [
            $export,
            \ripaym1970\autocrud\components\HtmlActions\Icon::import(
                [
                    '/grid/profiles-management/import',
                ]
            ),
        ];

        return new \ripaym1970\autocrud\components\modules\Grid\Helper(
            [
                'classMap' => [
                    self::MODEL_CLASS,
                ],
                'representation' => Grid\Helper::GRID_REPRESENTATION,
                'route' => ['/grid/profiles-management/index'],
                'htmlAttributes' => [
                    'id' => 'js-profiles',
                ],
                'query' => function (\yii\db\ActiveQuery $query) {
                    $module = \ripaym1970\autocrud\components\modules\Grid\Module::getInstance();
                    $class = $module->profilesListFilterClass;
                    if ($class) {
                        $query->andWhere([
                            'parent_class' => $class,
                        ]);
                    }
                    return $query->orderBy("id desc");
                },
                'settings' => [
                    'toolbar' => implode(' ', $toolbar),
                    'selectable' => 'multiple',
                    'hideMultiColumn' => true,
                ]
            ]
        );
    }

    public function actions()
    {
        return \yii\helpers\ArrayHelper::merge(
            parent::actions(),
            [
                'index' => [
                    'class' => \ripaym1970\autocrud\components\TypicalActions\Index::class,
                    'gridHelper' => self::getHelper(),
                ],
                'destroy' => \ripaym1970\autocrud\components\TypicalActions\Destroy::class,
            ]
        );
    }

    public function actionGetShares($id)
    {
        $model = new Grid\models\ProfilesManagementForm([
            'profileId' => $id,
            'assigneeIds' => \ripaym1970\autocrud\components\modules\Grid\models\GridProfileUsage::getAssigneeIds($id),
        ]);

        if (Yii::$app->request->isPost) {
            \ripaym1970\autocrud\components\Util::fillModelFields(
                $model,
                ['assigneeIds']
            );
            if ($model->setAssignees()) {
                Yii::$app->session->addFlash(
                    'success',
                    Yiit::t("Saved Successfully")
                );
                return \ripaym1970\autocrud\components\widgets\Alert::widget();
            }
        }

        return $this->renderAjax(
            "form",
            [
                'model' => $model,
            ]
        );
    }

    public function actionImport()
    {
        $model = new Grid\models\ImportForm();

        if (Yii::$app->request->isPost) {
            $model->file = \yii\web\UploadedFile::getInstance(
                $model,
                'file'
            );
            \ripaym1970\autocrud\components\Util::fillModelFields(
                $model,
                ['shareIds']
            );

            if ($model->validate() && $model->upload() && $model->process()) {
                \ripaym1970\autocrud\components\Util::noContent();
                return;
            }
        }

        return $this->renderAjax('import', ['model' => $model]);
    }

    public function actionExport(array $ids)
    {
        $form = new Grid\models\ExportForm(
            [
                'ids' => $ids,
            ]
        );

        if (!$form->validate()) {
            throw new \yii\base\UserException("Invalid ids");
        }

        Yii::$app->response->sendContentAsFile(
            $form->process(),
            uniqid('grid_profile_') . '.json'
        );
    }

}
