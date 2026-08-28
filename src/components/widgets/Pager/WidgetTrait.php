<?php

namespace ripaym1970\autocrud\components\widgets\Pager;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class WidgetTrait
 * @package frontend\components\widgets
 */
trait WidgetTrait
{
    /**
     * @param $event
     * @param $data
     */
    public function triggerJsData($event, $data)
    {
        $data = Json::encode($data);
        /** @var $view yii\web\View */
        $view = $this->view;
        $script = "$(document).trigger('{$event}', [{$data}]);";

        if (Yii::$app->request->isAjax) {
            echo Html::tag('script', $script, ['type' => 'text/javascript']);
        } else {
            $view->registerJs($script);
        }
    }
}
