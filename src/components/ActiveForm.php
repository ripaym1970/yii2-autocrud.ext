<?php

namespace ripaym1970\autocrud\components; // ripaym1970\autocrud\components\ActiveForm

use kartik\helpers\Html;
use yii2tech\admin\widgets\ButtonContextMenu;

class ActiveForm extends \kartik\widgets\ActiveForm
{
    public $title = null;
    public $panel = true;

    public function run()
    {
        $buttons = ButtonContextMenu::widget([
            'items' => $this->view->params['contextMenuItems'] ?? [],
        ]);

        if (isset($this->view->params['contextMenuItems'])) {
            unset($this->view->params['contextMenuItems']);
        }

        if ($this->panel) {
            return Html::panel([
                'heading'  => $this->title ?: $this->view->title,
                'preBody'  => Html::tag('div', $buttons, ['class' => 'kv-panel-before'])
                            . Html::beginTag('div', ['class' => 'kv-panel-body']),
                'body'     => parent::run(),
                'postBody' => Html::endTag('div'),
            ], Html::TYPE_PRIMARY);
        }

        return parent::run();
    }
}
