<?php

namespace ripaym1970\autocrud\components\widgets\Menu;

use Exception;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Theme menu widget.
 */
class Menu extends \yii\widgets\Menu
{
    /**
     * @var string
     */
    public $linkTemplate = '<a href="{url}">{icon} {label}</a>';
    /**
     * Styles all labels of items on sidebar by AdminLTE
     */
    public $labelTemplate = '<span>{label}</span>';
    public $submenuTemplate = "\n<ul class='treeview-menu' {show}>\n{items}\n</ul>\n";
    public $activateParents = true;
    public $defaultIconHtml = '<i class="fa fa-circle-o"></i> ';
    public $options = ['class' => 'sidebar-menu', 'data-widget' => 'tree'];

    /**
     * @var string is prefix that will be added to $item['icon'] if it exist.
     * By default uses for Font Awesome (http://fontawesome.io/)
     */
    public static $iconClassPrefix = 'fa fa-';

    private ?string $controller = null;

    /**
     * Renders the menu.
     */
    public function run()
    {
        MenuAsset::register($this->getView());

        $requestUri = str_replace('/admin', '', $_SERVER['REQUEST_URI']);
        [, $this->controller,] = explode('/', $requestUri . '/index');
        //d([$this->controller]);

        if ($this->route === null && Yii::$app->controller !== null) {
            $this->route = Yii::$app->controller->getRoute();
            //d($this->route);
        }
        if ($this->params === null) {
            $this->params = Yii::$app->request->getQueryParams();
        }

        $items = $this->normalizeItems($this->items, $hasActiveChild);
        if (!empty($items)) {
            $options = $this->options;
            $tag = ArrayHelper::remove($options, 'tag', 'ul');
            // Рисуем меню
            echo Html::tag($tag, $this->renderItems($items), $options);
        }
    }

    /**
     * @param array $item
     *
     * @return string
     * @throws Exception
     */
    protected function renderItem($item)
    {
        if (isset($item['items'])) {
            $labelTemplate = '<a href="{url}">{icon} {label} <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>';
            $linkTemplate = '<a href="{url}">{icon} {label} <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>';
        } else {
            $labelTemplate = $this->labelTemplate;
            $linkTemplate = $this->linkTemplate;
        }

        $replacements = [
            '{label}' => strtr($this->labelTemplate, ['{label}' => $item['label'],]),
            '{icon}' => empty($item['icon']) ? $this->defaultIconHtml
                : '<i class="' . static::$iconClassPrefix . $item['icon'] . '"></i> ',
            '{url}' => $item['url'] ?? 'javascript:void(0);',
        ];

        $template = ArrayHelper::getValue($item, 'template', isset($item['url']) ? $linkTemplate : $labelTemplate);

        return strtr($template, $replacements);
    }

    /**
     * Recursively renders the menu items (without the container tag).
     *
     * @param array $items the menu items to be rendered recursively
     *
     * @return string the rendering result
     * @throws Exception
     */
    protected function renderItems($items)
    {
        $n = count($items);
        $lines = [];
        foreach ($items as $i => $item) {
            $options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));
            $tag = ArrayHelper::remove($options, 'tag', 'li');
            $class = [];

            if ($i === 0 && $this->firstItemCssClass !== null) {
                $class[] = $this->firstItemCssClass;
            }
            if ($i === $n - 1 && $this->lastItemCssClass !== null) {
                $class[] = $this->lastItemCssClass;
            }
            if (!empty($class)) {
                if (empty($options['class'])) {
                    $options['class'] = implode(' ', $class);
                } else {
                    $options['class'] .= ' ' . implode(' ', $class);
                }
            }

            //d('isItemActive');
            $item['active'] = $this->isItemActive($item);
            if (!empty($item['active'])) {
                $class[] = $this->activeCssClass;
            }
            //d($item);
            $options['class'] .= $item['active'] ? ' menu-open' : '';

            $menu = $this->renderItem($item);
            // Если есть субменю
            if (!empty($item['items'])) {
                $menu .= strtr($this->submenuTemplate, [
                    '{show}'  => $item['active'] ? 'style="display: block"' : '',
                    '{items}' => $this->renderItems($item['items']),
                ]);
                if (isset($options['class'])) {
                    $options['class'] .= ' treeview';
                } else {
                    $options['class'] = 'treeview';
                }

            }
            $lines[] = Html::tag($tag, $menu, $options);
        }
        //dd($lines);
        return implode("\n", $lines);
    }

    /**
     * @param array $items
     * @param bool  $active
     *
     * @return array
     */
    protected function normalizeItems($items, &$active): array
    {
        //d('normalizeItems');
        foreach ($items as $i => $item) {
            if (isset($item['visible']) && !$item['visible']) {
                unset($items[$i]);
                continue;
            }

            $items[$i]['active'] = $this->isItemActive($item);
            $items[$i]['label']  = $item['label'] ?? 'Нет label';
            $items[$i]['icon']   = $item['icon'] ?? '';
        }
        //dd($items);
        return array_values($items);
    }

    /**
     * Checks whether a menu item is active.

     * @param array $item the menu item to be checked
     *
     * @return boolean whether the menu item is active
     */
    protected function isItemActive($item): bool
    {
        // Если есть субменю у меню
        if (!empty($item['items'])) {
            //d('есть субменю у меню');
            foreach ($item['items'] as $item) {
                // Если хоть один пункт меню активен
                if ($this->isItemActive($item)) {
                    //d(['1',$item['label']]);
                    return true;
                }
            }
            //d(['2',$item['label']]);
            return false;
        }

        $url = isset($item['url']) ? str_replace('/admin/', '', $item['url']) : null;
        if ($url == $this->controller) {
            //d(['3',$item['label']]);
            return true;
        }
        //d(['4',$item['label']]);
        return false;
    }
}
