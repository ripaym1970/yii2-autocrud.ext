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
$this->title = Yiit::t('Створити');

$label = Yiit::t(ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.title', ucfirst(Inflector::pluralize($table))));

$this->params['breadcrumbs'][] = ['label' => $label, 'url' => ['index', 'table' => $table]];
$this->params['breadcrumbs'][] = $this->title;

echo $this->render('_form', [
    'model' => $model,
    'form' => $form,
]);
