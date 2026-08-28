<?php

namespace ripaym1970\autocrud\components\widgets\Pager;

use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\data\Pagination;
use yii\helpers\Html;

/**
 * @property bool $hasNext
 * @property string $urlNext
 * @property string $buttonClass
 * @property bool $firstCondition
 * @property bool $lastCondition
 * @property int $buttonCount
 * @property string $labelMoreButton
 * @property int $pageCount
 */
class Pager extends Widget
{
    use WidgetTrait;

    public const MIDDLE = 3;
    public const LEFT = 5;

    /** @var $isMore */
    public $isMore = false;
    /** @var  Pagination */
    public $pagination;
    /** @var int */
    public $pageSize = 10;
    /** @var bool  */
    public $history = true;
    /** @var bool  */
    public $addToHistory = true;
    /** @var  string */
    public $itemsContainerSelector;

    /** @var string  */
    public $template = 'common';
    /** @var string  */
    public $btnMoreClass = 'pager-new-btn-more-items';

    /** @var  int */
    protected $_pageCount;
    /** @var  bool */
    protected $_hasNext;
    /** @var  string */
    protected $_urlNext;
    /** @var array  */
    protected $_showMoreParam = ['name' => LinkPager::SHOW_MORE_PARAM_NAME, 'value' => false];

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        $class = get_class($this);
        if (!$this->pagination instanceof Pagination) {
            throw new InvalidConfigException("{$class}::pagination must bee instanceof Pagination");
        }
        if (empty($this->itemsContainerSelector)) {
            throw new InvalidConfigException("{$class}::itemsContainerSelector must bee set");
        }

        // Надо для js-части пагинатора
        //$this->triggerJsData('pager-new-ready', [
        //    'btn'           => ".{$this->btnMoreClass}",
        //    'showMoreParam' => $this->_showMoreParam,
        //    'history'       => $this->history,
        //    'addToHistory'  => $this->addToHistory,
        //]);

        $this->pagination->params = !empty($this->pagination->params) ? $this->pagination->params : Yii::$app->request->getQueryParams();
        if (isset($this->pagination->params[LinkPager::SHOW_MORE_PARAM_NAME])) {
            unset($this->pagination->params[LinkPager::SHOW_MORE_PARAM_NAME]);
        }

        $this->_pageCount = $this->pagination->pageCount;
        $links = $this->pagination->getLinks();
        $this->_hasNext = isset($links[Pagination::LINK_NEXT]);
        $this->_urlNext = isset($links[Pagination::LINK_NEXT]) ? $links[Pagination::LINK_NEXT] : '';
    }

    /**
     * @return string
     */
    public function run()
    {
        return $this->render($this->template, [
            'pagination' => $this->pagination
        ]);
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return $this->_pageCount;
    }

    /**
     * @return int
     */
    public function getButtonCount()
    {
        return $this->_pageCount <= LinkPager::NUM ? LinkPager::NUM : ($this->pagination->page >= LinkPager::LEFT - 1 && $this->pagination->page <= $this->_pageCount - LinkPager::LEFT ? self::MIDDLE : LinkPager::LEFT);
    }

    /**
     * @return string
     */
    public function getButtonClass()
    {
        return !$this->_hasNext ? "disabled {$this->btnMoreClass}" : $this->btnMoreClass;
    }

    /**
     * @return bool
     */
    public function getHasNext()
    {
        return $this->_hasNext;
    }

    /**
     * @return string
     */
    public function getUrlNext()
    {
        return $this->_urlNext;
    }

    /**
     * @return bool
     */
    public function getFirstCondition()
    {
        return $this->_pageCount > LinkPager::NUM && $this->pagination->page >= LinkPager::LEFT - 1;
    }

    /**
     * @return bool
     */
    public function getLastCondition()
    {
        return $this->_pageCount > LinkPager::NUM && (($this->_pageCount - LinkPager::LEFT < LinkPager::LEFT && $this->pagination->page >= LinkPager::LEFT - 1) || ($this->pagination->page <= $this->_pageCount - LinkPager::LEFT)) && !($this->_pageCount - $this->pagination->page <= self::LEFT - 1);
    }

    /**
     * @return string
     */
    public function getLabelMoreButton()
    {
        if (!$this->isMore) {
            return '';
        }

        $i = Html::tag('i', '', [
            'class' => 'load-more-spinner__bounce',
        ]);

        return Yiit::t('Показати ще') . Html::tag('span', $i . ' ' . $i . ' ' . $i, ['class' => 'load-more-spinner']);
    }
}
