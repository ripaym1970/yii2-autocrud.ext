<?php

namespace ripaym1970\autocrud\components\grid;

use kartik\helpers\Html;
use Yii;
use yii\web\View;

class ToggleColumn extends \yii2mod\toggle\ToggleColumn
{
    protected function renderDataCellContent($model, $key, $index)
    {
        $attribute = $this->attribute;
        $value = $model->$attribute;

        $url = [$this->action, 'id' => $model->id, 'attribute' => $attribute, 'table' => Yii::$app->request->get('table')];

        // Для полей default=1 удаляем переключалку
        if ($value && $attribute == 'default') {
            return Html::icon('check text-success', [], 'fa fa-');
        }

        return Html::a(
            Html::icon($value ? 'check text-success' : 'times text-danger', [], 'fa fa-'),
            $url,
            [
                //'title'       => ($value === null || $value == true) ? Yiit::t('Off') : Yiit::t('On'),
                'class'       => 'toggle-column',
                'data-method' => 'post',
                'data-pjax'   => '0',
            ]
        );
    }

    public function registerJs()
    {
        $js = <<< JS
            $('body').on('click', 'a.toggle-column', function(e) {
                e.preventDefault();
                $.post($(this).attr('href'), function(data) {
                  var pjaxId = $(e.target).closest('.grid-view').parent().attr('id');
                  $.pjax.reload({container:'#' + pjaxId, timeout: 5000});
                });
                return false;
            });
JS;
        $this->grid->view->registerJs($js, View::POS_READY, 'yii2mod-toggle-column');
    }
}
