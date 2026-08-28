<?php

namespace ripaym1970\autocrud\components\widgets\Translit;

use yii\base\Model;
use yii\bootstrap5\Html;
use yii\widgets\InputWidget;

class TranslitWidget extends InputWidget
{
    /** @var string тип инпута */
    public $type = 'text';
    /** @var string селестор поля откуда брать данные */
    public $donor_selector  = '.donor-selector';
    /** @var string селестор поля куда отдавать */
    public $target_selector = 'slug';
    /** @var bool обновлять slug при редактировании */
    public $on_update = false;
    /** @var array */
    public $options = ['class' => 'form-control'];

    public function run()
    {
        if ($this->hasModel() and ($this->model->isNewRecord or $this->on_update)) {
            $this->registerJs();
        }
        if ($this->hasModel()) {
            echo Html::activeInput($this->type, $this->model, $this->attribute, $this->options);
        } else {
            echo Html::input($this->type, $this->name, $this->value, $this->options);
        }
    }

    public function registerJs()
    {
        $view = $this->view;
        TranslitWidgetAssets::register($view);

        $donor_selector = $this->donor_selector;
        if ($this->hasModel()) {
            $target_selector = '#' . Html::getInputId($this->model, $this->attribute);
        } else {
            $target_selector = $this->target_selector;
        }

        //dd([$donor_selector, $this_selector]);
        $view->registerJs("
            $('" . $donor_selector . "').liTranslit({
                elAlias: $('" . $target_selector . "'),
                caseType:	'lower',
            });
        ");
    }

    /**
     * @return bool whether this widget is associated with a data model.
     */
    protected function hasModel()
    {
        return $this->model instanceof Model && $this->attribute !== null;
    }
}
