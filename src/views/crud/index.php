<?php

/**
 * @see \backend\controllers\CrudController
 */

use ripaym1970\autocrud\components\grid\ActionColumn;
use ripaym1970\autocrud\components\grid\GridView;
use ripaym1970\autocrud\components\Yiit;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\web\View;
use yii2tech\ar\search\ActiveSearchModel;

/** @var View $this */
/** @var ActiveSearchModel $searchModel */
/** @var ActiveDataProvider $dataProvider */

$tableName = Yii::$app->request->get('table');
$pageSize = Yii::$app->request->get('per-page');
//dd($pageSize);

$this->title = Yiit::t(ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.title', ucfirst(Inflector::pluralize($tableName))));
$this->params['breadcrumbs'][] = $this->title;

$isSystem = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.system');
$fields = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.formFields', false);

$this->params['contextMenuItems'] = ($isSystem || empty($fields))
    ? []
    : [['create', 'table' => $tableName]];

$gridButtons = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.gridButtons', []);
$lists = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.lists', []);

$className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($tableName) . 'Model';

$buttons1 = [
    'view' => [
    ],
    'update' => [
        'visible' => !empty($fields),
    ],
];

$buttons2 = ArrayHelper::merge([
    'delete' => [
        'visible' => !$isSystem,
    ],
], $gridButtons);
//dd([$buttons1, $buttons2]);

uksort($buttons1, function ($a, $b) use ($gridButtons) {
    if (isset($gridButtons[$a]) && isset($gridButtons[$b])) {
        $k = array_keys($gridButtons);
        return array_search($a, $k, true) <=> array_search($b, $k, true);
    }
    return 0;
});
uksort($buttons2, function ($a, $b) use ($gridButtons) {
    if (isset($gridButtons[$a]) && isset($gridButtons[$b])) {
        $k = array_keys($gridButtons);
        return array_search($a, $k, true) <=> array_search($b, $k, true);
    }
    return 0;
});
//dd($gridButtons);

//dd($gridColumns);
$gridToolbar =
    ArrayHelper::merge(
        [
            '{index}',
            '{create}',
        ],
        ArrayHelper::getValue(
            Yii::$app->params,
            'tables.' . $tableName . '.gridToolbar',
            []
        )
    );
//dd($gridToolbar);

//dd(Yii::$app->params);
$gridColumns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.gridColumns', []);
//echo '<pre>';var_dump('=',$gridColumns);echo '</pre>';exit();
if (!$gridColumns) {
    $gridColumns = array_keys(
        ArrayHelper::getValue(
            Yii::$app->params,
            'tables.' . $tableName . '.columns',
            []
        )
    );
    if (count($gridColumns) > 10) {
        //throw new InvalidConfigException('More columns to GridView');
        echo '<pre>';var_dump('More columns to GridView');echo '</pre>';exit();
    }
}

foreach ($gridColumns as $key => $item) {
    if (in_array($item, ['default', 'active', 'trash', 'is_published', 'is_active'])) {
        $gridColumns[$key] .= ':boolean';
        continue;
    }
    if (in_array($item, ['created_at', 'updated_at', 'logtime_at', 'date',])) {
        $gridColumns[$key] .= ':datetime';
    }
}
//echo '<pre>';var_dump('$gridColumns=',$gridColumns);echo '</pre>';//exit();
//exit();

$addColumns = [
    //['class' => SerialColumn::class],
];
if (in_array('id', $gridColumns)) {
    $addColumns[] = [
        'attribute'          => 'id',
        'label'              => 'ID',
        'headerOptions'      => ['style' => 'min-width:70px;width:70px;',],
        'filterInputOptions' => ['class' => 'form-control', 'style' => 'padding:6px 2px;text-align:right;'],
        'contentOptions' => ['align' => 'right', ],
    ];
    foreach($gridColumns as $key => $value) {
        if ($value == 'id') {
            unset($gridColumns[$key]);
        }
    }
}
if (in_array('image', $gridColumns)) {
    //echo '<pre>';var_dump('$gridColumns=',$gridColumns);echo '</pre>';exit();
    $addColumns[] = [
        'attribute' => 'image',
        'value' => 'image',
    ];
    foreach ($gridColumns as $key => $item) {
        if ($item == 'image') {
            unset($gridColumns[$key]);
        }
    }
}
if (in_array('depth', $gridColumns)) {
    $addColumns[] = [
        'label' => Yiit::t('Перемістити'),
        'format' => 'raw',
        'value' => function ($model) use ($tableName) {
            if ($model->id == 1) {
                return '';
            }

            return
                ($model->lft == 2 ? '' : Html::a('<span class="glyphicon glyphicon-arrow-up" title="'.Yiit::t('Вгору').'"></span>', ["/$tableName/move-up", 'id' => $model->id])) .
                ($model->lft + 1 == $model->rgt ? '' : Html::a('<span class="glyphicon glyphicon-arrow-down" title="'.Yiit::t('Вверх').'"></span>', ["/$tableName/move-down", 'id' => $model->id]));
        },
        'contentOptions' => ['style' => 'text-align: center'],
    ];

    unset($gridColumns[array_keys($gridColumns, 'name')[0]]);
    $addColumns[] = [
        'attribute' => 'name',
        'label'     => Yiit::t('Назва'),
        'format'    => 'raw',
        'value'     => function ($model) {
            $indent = ($model->depth > 1 ? str_repeat('&nbsp;&nbsp;', $model->depth - 1) . '-- ' : '');
            return $indent . Html::a(Html::encode($model->name), ['view', 'id' => $model->id]);
        },
    ];
}

$columns = ArrayHelper::merge(
    [
        [
            'class'    => ActionColumn::class,
            'template' => '{'.implode('}&nbsp;{', array_keys($buttons1)).'}',
            'buttons'  => $buttons1,
            //'header' => '',
            //'headerOptions' => ['style' => 'min-width:70px;width:70px;max-width:70px;', ],
            //'contentOptions' => ['style' => 'width:70px;', ],
        ],
        // TODO массовые операции
        //['class' => CheckboxColumn::class],
    ],
    $addColumns,
    $gridColumns,
    [
        [
            'class'    => ActionColumn::class,
            'template' => '{'.implode('}&nbsp;{', array_keys($buttons2)).'}',
            'buttons'  => $buttons2,
            //'header' => '',
            //'headerOptions' => ['style' => 'min-width:30px;width:30px;max-width:30px;', ],
            //'contentOptions' => ['style' => 'width:30px;', ],
        ],
        // TODO массовые операции
        //['class' => CheckboxColumn::class],
    ],
);

//dd($searchModel);
//dd($columns);
//dd($dataProvider);
//dd($dataProvider->query);

$queryTotalCount = clone $dataProvider->query;
$totalCount = (int) $queryTotalCount->limit(-1)->offset(-1)->orderBy([])->count();
$currentPageSize = $pageSize ?? (int) $dataProvider->getPagination()->getPageSize();

$dataProvider->pagination->totalCount = $totalCount;
$dataProvider->pagination->setPageSize($currentPageSize);
//dd($dataProvider->pagination);

$pageSizeLimit = [];
if ($currentPageSize <= $totalCount) {
    $pageSizeLimit = $dataProvider->pagination->pageSizeLimit;
}
if ($pageSizeLimit && $totalCount < 200) {
    foreach($pageSizeLimit as $key => $value) {
        if ($totalCount <= $value) {
            unset($pageSizeLimit[$key]);
        }
    }
    $pageSizeLimit[] = $totalCount;
}
$dataProvider->pagination->pageSizeLimit = $pageSizeLimit;

//$zzz = $dataProvider->query;
//d(
//    $zzz->prepare(\Yii::$app->db->queryBuilder)->createCommand()->rawSql,
//    $dataProvider->pagination,
//    $dataProvider->sort
//);
//return;
//dd($columns);

echo GridView::widget([
    'pager' => [
        //'class'        => 'yii\bootstrap5\LinkPager',
        'firstPageLabel' => true,
        'lastPageLabel'  => true,
    ],
    'toolbar'      => $gridToolbar,
    'dataProvider' => $dataProvider,
    'filterModel'  => $searchModel,
    'columns'      => $columns,
    'lists'        => $lists,
]);
