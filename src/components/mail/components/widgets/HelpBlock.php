<?php

namespace ripaym1970\autocrud\components\mail\components\widgets;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @property string $contentArea
 * @property string $linkArea
 */
class HelpBlock extends \yii\base\Widget
{
    /** @var string $content */
    public $content;

    /** @var bool $expanded */
    public $expanded = true;

    /** @var array $options */
    public $options = [];

    protected $_id;

    public function run()
    {
        $this->_id = uniqid('collapse_');

        return Html::tag(
            'div',
            $this->linkArea . $this->contentArea,
            ArrayHelper::merge(
                [
                    'class' => ['d-flex flex-row helpblock-widget']
                ],
                $this->options
            )
        );
    }

    protected function getLinkArea()
    {
        $linkOptions = $this->options['link'] ?? [];

        $linkOptions = ArrayHelper::merge(
            $linkOptions,
            [
                'data-toggle' => 'collapse',
                'role' => 'button',
                'aria-expanded' => true,
                'aria-controls' => $this->_id,
                'class' => 'float-left p-1'
            ]
        );

        $icon = \rmrevin\yii\fontawesome\FAS::icon(
            \rmrevin\yii\fontawesome\FAS::_QUESTION_CIRCLE
        )->size(
            \rmrevin\yii\fontawesome\FAS::SIZE_LARGE
        );

        return Html::a(
            $icon,
            '#' . $this->_id,
            $linkOptions
        );
    }

    protected function getContentArea()
    {
        $contentOptions = $this->options['content'] ?? [];

        Html::addCssClass(
            $contentOptions,
            'collapse'
        );
        Html::addCssClass(
            $contentOptions,
            ['alert alert-warning mt-1 float-left w-100']
        );

        if ($this->expanded) {
            Html::addCssClass(
                $contentOptions,
                'show'
            );
        }

        $contentOptions = ArrayHelper::merge(
            $contentOptions,
            [
                'id' => $this->_id,
            ]
        );

        return Html::tag(
            'div',
            $this->content,
            $contentOptions
        );
    }
}
