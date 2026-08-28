<?php

/**
 * @see \backend\controllers\CrudController
 */

use ripaym1970\autocrud\models\CrudModel;
use ripaym1970\autocrud\components\Yiit;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\View;

/** @var View $this */
/** @var CrudModel $model */
/** @var string $form */

$table = Yii::$app->request->get('table');

$this->title = Yiit::t(ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.title', ucfirst(Inflector::pluralize($table))));
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index', 'table' => $table]];

$labelName = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.label', 'id');
$label = $model->$labelName;

$this->title = Yiit::t('Редагування') . ' ' . $label;

$this->params['breadcrumbs'][] = ['label' => $label, 'url' => ['view', 'id' => $model->id, 'table' => $table]];
$this->params['breadcrumbs'][] = Yiit::t('Редагування');
$this->params['contextMenuItems'] = [
    //['label' => Yiit::t('Change password'), 'url' => ['password', 'id' => $model->id, 'table' => $table]],
];

echo $this->render('_form', [
    'model' => $model,
    'form'  => $form,
]);
