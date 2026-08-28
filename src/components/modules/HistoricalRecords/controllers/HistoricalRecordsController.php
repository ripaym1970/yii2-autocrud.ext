<?php

namespace ripaym1970\autocrud\components\modules\HistoricalRecords\controllers;

use ripaym1970\autocrud\components\modules\HistoricalRecords;

/**
 * @mixin \ripaym1970\autocrud\components\behaviors\Finder
 */
class HistoricalRecordsController extends \ripaym1970\autocrud\components\TypicalController
{
    const MODEL_CLASS = HistoricalRecords\models\HistoricalRecord::class;

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
                    HistoricalRecords\Module::MANAGEMENT_PERMISSION,
                ],
                'allow' => true,
            ],
            self::RESTRICT_EVERYBODY_RULE
        ];
    }

    protected static function checkClass(string $className)
    {
        if ($className != HistoricalRecords\models\HistoricalRecord::class) {
            throw new \yii\base\Exception(
                'Unexpected class ' . $className
            );
        }
    }

    protected static function typicalHelperOptions()
    {
        return [
            'classMap' => [
                self::MODEL_CLASS,
            ],
            'representation' => \ripaym1970\autocrud\components\modules\Grid\Helper::GRID_REPRESENTATION,
        ];
    }

    protected static function queryByParent(\yii\db\ActiveQuery $query)
    {
        $map = [
            'parent_class' => $_REQUEST['parentClass'],
            'parent_id' => $_REQUEST['parentId'],
        ];
        foreach ($map as $field => $value) {
            if ($value) {
                $query->andWhere([
                    $field => $value,
                ]);
            }
        }
        return $query;
    }

    public static function helperByParent(string $parentClass, $parentId)
    {
        return new \ripaym1970\autocrud\components\modules\Grid\Helper(
            \yii\helpers\ArrayHelper::merge(
                self::typicalHelperOptions(),
                [
                    'modelConfig' => function (string $class, array &$config) {
                        self::checkClass($class);
                        $config = HistoricalRecords\models\HistoricalRecord::gridConfigByParent();
                    },
                    'route' => [
                        '/historical-records/historical-records/index-by-parent',
                        'parentClass' => $parentClass,
                        'parentId' => $parentId,
                    ],
                    'query' => function (\yii\db\ActiveQuery $query) {
                        return self::queryByParent($query)->orderBy('id desc');
                    },
                    'settings' => [
                        'toolbar' => false,
                        'groupable' => [
                            'enabled'  =>false,
                        ],
                        'hideMultiColumn' => true,
                    ],
                ]
            )
        );
    }

    public static function helperByParentAndField(
        string $parentClass,
        $parentId,
        string $field
    ) {
        return new \ripaym1970\autocrud\components\modules\Grid\Helper(
            \yii\helpers\ArrayHelper::merge(
                self::typicalHelperOptions(),
                [
                    'modelConfig' => function (string $class, array &$config) use ($field) {
                        self::checkClass($class);
                        $config = HistoricalRecords\models\HistoricalRecord::gridConfigByField($field);
                    },
                    'route' => [
                        '/historical-records/historical-records/index-by-parent-and-field',
                        'parentClass' => $parentClass,
                        'parentId' => $parentId,
                        'field' => $field,
                    ],
                    'query' => function (\yii\db\ActiveQuery $query) {
                        HistoricalRecords\models\HistoricalRecord::queryByField(
                            $query,
                            $_REQUEST['field']
                        );
                        return self::queryByParent($query)->orderBy('id desc');
                    },
                    'settings' => [
                        'toolbar' => false,
                    ],
                ]
            )
        );
    }

    public static function generalHelper()
    {
        return new \ripaym1970\autocrud\components\modules\Grid\Helper(
            \yii\helpers\ArrayHelper::merge(
                self::typicalHelperOptions(),
                [
                    'modelConfig' => function (string $class, array &$config) {
                        self::checkClass($class);
                        $config = HistoricalRecords\models\HistoricalRecord::generalGridConfig();
                    },
                    'route' => [
                        '/historical-records/historical-records/index'
                    ],
                    'query' => function (\yii\db\ActiveQuery $query) {
                        return $query->orderBy('id desc');
                    },
                ]
            )
        );
    }

    public function actions()
    {
        return \yii\helpers\ArrayHelper::merge(
            parent::actions(),
            [
                'index' => [
                    'class' => \ripaym1970\autocrud\components\TypicalActions\Index::class,
                    'gridHelper' => fn() => self::generalHelper(),
                ],
                'view' => \ripaym1970\autocrud\components\TypicalActions\View::class,
            ]
        );
    }


    public function actionRestore($id)
    {
        /** @var HistoricalRecords\models\HistoricalRecord $model */
        $model = $this->findModel($id);
        $model->revert();
    }


    public function actionIndexByParentAndField($parentClass, $parentId, $field)
    {
        $helper = self::helperByParentAndField($parentClass, $parentId, $field);
        return isset($_REQUEST['getGridDefinition'])
            ? $this->renderAjax('render-helper', ['gridHelper' => $helper])
            : $this->asJson(
                $helper->processRequest()
            );
    }

    public function actionIndexByParent($parentClass, $parentId)
    {
        $helper = self::helperByParent($parentClass, $parentId);
        return isset($_REQUEST['getGridDefinition'])
            ? $this->renderAjax('render-helper', ['gridHelper' => $helper])
            : $this->asJson(
                $helper->processRequest()
            );
    }
}
