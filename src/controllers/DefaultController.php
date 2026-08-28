<?php

namespace ripaym1970\autocrud\controllers;

use yii\web\Controller;

class DefaultController extends Controller
{
    public function actionIndex($page)
    {
        $model = $this->findModel($page);
        return $this->render('index', [
            'model' => $model,
        ]);
    }
}
