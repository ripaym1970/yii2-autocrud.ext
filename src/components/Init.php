<?php

namespace ripaym1970\autocrud\components;

//use Yii;
use yii\base\Component;

class Init extends Component
{
    public function init()
    {
        //dd(Yii::$app->settings);
        // В переменные $app присваиваем свои значения из таблицы 'settings'
        //Yii::$app->name     = Yii::$app->settings->get('default', 'siteName');
        //Yii::$app->language = Yii::$app->settings->get('default', 'language');

        parent::init();
    }
}
