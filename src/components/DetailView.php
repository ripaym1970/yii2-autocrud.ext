<?php

namespace ripaym1970\autocrud\components;

use yii2tech\admin\widgets\ButtonContextMenu;

class DetailView extends \kartik\detail\DetailView
{
    public $panel = [
        'type' => DetailView::TYPE_PRIMARY,
    ];

    public $buttonContainer = ['class' => 'pull-left'];

    public $enableEditMode = false;

    public $panelTemplate = <<< HTML
{panelHeading}
<div class="kv-panel-before">
{contextMenuItems}
</div>
{items}
{panelAfter}
{panelFooter}
HTML;

    public function init()
    {
        $this->panel['heading'] = $this->view->title;
        $this->panelTemplate = str_replace(
            '{contextMenuItems}',
            ButtonContextMenu::widget([
                'items' => $this->view->params['contextMenuItems'] ?? [],
            ]),
            $this->panelTemplate
        );
        if (isset($this->view->params['contextMenuItems'])) {
            unset($this->view->params['contextMenuItems']);
        }

        foreach ($this->attributes as $key => $attribute) {
            if (isset($attribute['value']) && $attribute['value'] instanceof \Closure) {
                $this->attributes[$key]['value'] = call_user_func($attribute['value'], $this->model);
            }
        }
        return parent::init();
    }

    protected function renderAttributeItem($attribute)
    {
        if ($attribute['format'] === 'array') {
            $attribute['value'] = $this->formatter->format($attribute['value'], $attribute['format']);
        }

        return parent::renderAttributeItem($attribute);
    }
}
