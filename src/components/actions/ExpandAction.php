<?php

namespace ripaym1970\autocrud\components\actions;

use Yii;
use yii2tech\admin\actions\Create;

class ExpandAction extends Create
{
    public function run()
    {
        $searchModel = $this->controller->newSearchModel();
        $searchModel->parent = Yii::$app->request->post('expandRowKey');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (Yii::$app->request->isAjax) {
            $result = $this->controller->renderAjax('index', [
                'searchModel'  => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
            return $result;
        }
        return $this->controller->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}
