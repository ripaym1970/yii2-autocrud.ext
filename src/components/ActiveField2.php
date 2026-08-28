<?php

namespace ripaym1970\autocrud\components;

use yii\helpers\Html;

/**
 * Class ActiveField
 * @package ripaym1970\autocrud\components
 *
 * normally activeField doesn't have events on run because
 * it's not a widget
 * but sometimes we need to replace it with something or just do not show
 * so event handler can modify event "result" property
 *
 * also it contains some wrappers for smartadmin
 *
 * and abstraction for form reload
 */
class ActiveField2 extends \yii\bootstrap5\ActiveField
{
    public const EVENT_AFTER_RUN = 'afterRun';
    public const JS_RELOAD_CLASS = 'js-reload';

    public $checkTemplate = "<div class=\"custom-control custom-switch\">\n{input}\n{label}\n{error}\n{hint}\n</div>";
    public $checkHorizontalTemplate = "{beginWrapper}\n<div class=\"custom-control custom-switch\">\n{input}\n{label}\n{error}\n{hint}\n</div>\n{endWrapper}";

    protected $prepend = [];
    protected $append = [];

    public function __toString()
    {
        $event = new \yii\base\WidgetEvent([
            'result' => parent::__toString()
        ]);
        $this->trigger(
            self::EVENT_AFTER_RUN,
            $event
        );
        return $event->result;
    }

    public static function isReloadable(): bool
    {
        return isset($_REQUEST[self::JS_RELOAD_CLASS]);
    }

    public function reloadable(): self
    {
        if (isset($this->parts['{input}'])) {
            throw new \yii\base\InvalidConfigException(
                "->reloadable() call should appear before content part (textInput dropdown etc.)"
            );
        }
        if (!is_array($this->inputOptions['class'])) {
            $this->inputOptions['class'] = [$this->inputOptions['class']];
        }
        Html::addCssClass($this->inputOptions, self::JS_RELOAD_CLASS);
        Html::addCssClass($this->radioOptions, self::JS_RELOAD_CLASS);

        return $this;
    }

    public function passwordInputWithoutAutocomplete($options = [])
    {
        $options = \yii\helpers\ArrayHelper::merge(
            [
                'autocomplete' => 'new-password',
            ],
            $options
        );
        return parent::passwordInput($options);
    }

    public function dropdownList($items, $options = [])
    {
        $options = $this->convertOptions($options, 'custom-select');
        return parent::dropdownList($items, $options);
    }

    public function checkbox($options = [], $enclosedByLabel = false)
    {
        $options = $this->convertOptions($options, 'custom-control-input');
        return parent::checkbox($options, $enclosedByLabel);
    }

    public function checkboxList($items, $options = [])
    {
        Html::addCssClass($this->options, 'align-items-center');
        return parent::checkboxList($items, $options);
    }

    protected function convertOptions(array $options, string $className)
    {
        if (isset($options['class']) && !is_array($options['class'])) {
            $options['class'] = [$options['class']];
        }
        Html::addCssClass($this->inputOptions, $className);
        Html::addCssClass($options, $this->inputOptions['class']);
        return $options;
    }

    public function prepend(string $content)
    {
        $this->prepend[] = $content;
        return $this;
    }

    public function append(string $content)
    {
        $this->append[] = $content;
        return $this;
    }

    public function render($content = null)
    {
        $this->processAppendPrepend();
        return parent::render($content);
    }


    protected function processAppendPrepend()
    {
        if (!$this->prepend && !$this->append) {
            return;
        }

        $labelPosition = strpos($this->template, "{label}") ?: 0;
        $inputPosition = strpos($this->template, "{input}") ?: 0;
        $inputAfterLabel = $inputPosition > $labelPosition;
        if (!$inputAfterLabel) {
            $this->template = strtr(
                $this->template,
                [
                    '{label}' => "{beginLabel}{prependLabel}\n{labelTitle}\n{appendLabel}{endLabel}"
                ]
            );
            $this->parts['{appendLabel}'] = implode(' ', $this->append);
            $this->parts['{prependLabel}'] = implode(' ', $this->prepend);
            return;
        }

        Html::addCssClass(
            $this->wrapperOptions,
            'input-group'
        );

        $this->template = strtr(
            $this->template,
            [
                '{input}' => Html::tag(
                    'div',
                    "\n{input}\n",
                    [
                        'class' => ['w-100', 'd-flex', 'flex-nowrap', 'align-items-center']
                    ]
                ),
            ]
        );

        $hasWrapper = strpos($this->template, "{beginWrapper}") !== false;
        $this->template = strtr(
            $this->template,
            [
                '{input}' => $hasWrapper
                    ? "{prependInput}\n{input}\n{appendInput}"
                    : "{beginWrapper}\n{prependInput}\n{input}\n{appendInput}\n{endWrapper}",
            ]
        );

        $this->parts['{prependInput}'] = Html::tag(
            'div',
            implode(' ', $this->prepend),
            [
                'class' => 'input-group-prepend'
            ]
        );
        $this->parts['{appendInput}'] = Html::tag(
            'div',
            implode(' ', $this->append),
            [
                'class' => 'input-group-append'
            ]
        );
    }
}
