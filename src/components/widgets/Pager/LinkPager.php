<?php

namespace ripaym1970\autocrud\components\widgets\Pager;

use Yii;
use yii\helpers\Html;

/**
 * @property array $pageRange
 */
class LinkPager extends \yii\widgets\LinkPager
{
    public const SHOW_MORE_PARAM_NAME = '_loadMore';
    public const NUM = 7;
    public const LEFT = 5;

    /** @var Pager */
    public $pager;
    /** @var array  */
    public $liOptions = [];

    /**
     * Renders a page button.
     * You may override this method to customize the generation of page buttons.
     *
     * @param string $label the text label for the button
     * @param int $page the page number
     * @param string $class the CSS class for the page button.
     * @param bool $disabled whether this page button is disabled
     * @param bool $active whether this page button is active
     *
     * @return string the rendering result
     */
    protected function renderPageButton($label, $page, $class, $disabled, $active)
    {
        $options = array_merge($this->liOptions, ['class' => empty($class) ? $this->pageCssClass : $class]);
        if ($this->_isActive($page, $active)) {
            Html::addCssClass($options, $this->activePageCssClass);
        }
        $linkOptions = $this->linkOptions;
        $linkOptions['data-page'] = $page;

        return Html::tag('li', Html::a($label, $this->pagination->createUrl($page), $linkOptions), $options);
    }

    /**
     * @return array the begin and end pages that need to be displayed.
     */
    protected function getPageRange()
    {
        $currentPage = $this->pagination->getPage();
        $pageCount = $this->pagination->getPageCount();

        if ($pageCount > self::NUM && $currentPage < self::LEFT - 1) {
            return [
                0,
                self::LEFT - 1
            ];
        }

        if ($pageCount > self::NUM && $pageCount - $currentPage <= self::LEFT - 1) {
            return [
                $pageCount - self::LEFT,
                $pageCount - 1
            ];
        }

        $beginPage = max(0, $currentPage - (int) ($this->maxButtonCount / 2));
        if (($endPage = $beginPage + $this->maxButtonCount - 1) >= $pageCount) {
            $endPage = $pageCount - 1;
            $beginPage = max(0, $endPage - $this->maxButtonCount + 1);
        }

        return [
            $beginPage,
            $endPage
        ];
    }

    /**
     * @param $page
     * @param $value
     * @return bool
     */
    protected function _isActive($page, $value)
    {
        $requestPage = Yii::$app->request->get(self::SHOW_MORE_PARAM_NAME);
        if (is_null($requestPage)) {
            return $value;
        }

        return $page >= $requestPage && $page <= $this->pagination->page;
    }
}
