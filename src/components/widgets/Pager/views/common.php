<?php

use ripaym1970\autocrud\components\widgets\Pager\LinkPager;
use ripaym1970\autocrud\components\widgets\Pager\Pager;
use yii\data\Pagination;
use yii\helpers\Html;
use yii\web\View;

/** @var View $this */
/** @var Pagination $pagination */
/** @var Pager $context */

$context = $this->context;

?>

<div class="paginatorNew pager-new-main-container">
    <div class="paginatorNew__buttons">

        <?php
        echo $context->pageCount > 1 ? Html::tag('div', $context->labelMoreButton, [
            'class' => "paginatorNew__button-text {$context->buttonClass}",
            'data' => [
                'container' => '.pager-new-main-container',
                'current-page' => $context->pagination->page,
                'url' => $context->urlNext,
                'items-container-selector' => $context->itemsContainerSelector,
                'is-request' => $context->hasNext ? 0 : 1,
                'loader-class' => 'load-more-spinner_animate',
            ],
        ]) : '';

        echo LinkPager::widget([
            'options' => [
                'class' => 'paginatorNew__ul',
                'style' => 'display: inline-block;',
            ],
            'liOptions' => [
                'style' => 'display: inline-block;',
            ],
            'linkOptions' => [
                'class' => 'paginatorNew__button-num',
            ],
            'disabledListItemSubTagOptions' => [
                'tag' => 'a',
                'class' => 'paginatorNew__button-num',
            ],
            'maxButtonCount'    => $context->buttonCount,
            'firstPageCssClass' => 'paginatorNew__button',
            'lastPageCssClass'  => 'paginatorNew__button',
            'prevPageCssClass'  => 'paginatorNew__button',
            'nextPageCssClass'  => 'paginatorNew__button',
            'pageCssClass'      => 'paginatorNew__button',
            'activePageCssClass' => 'active',
            'firstPageLabel' => $context->firstCondition,
            'nextPageLabel'  => $context->lastCondition ? '...' : false,
            'prevPageLabel'  => $context->firstCondition ? '...' : false,
            'lastPageLabel'  => $context->lastCondition,
            'pagination'     => $pagination,
        ]);
        ?>
    </div>
</div>
