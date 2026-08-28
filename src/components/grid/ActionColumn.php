<?php

namespace ripaym1970\autocrud\components\grid;

use Closure;
use ripaym1970\autocrud\models\behaviors\PublicationStatusBehavior;
use kartik\grid\ColumnTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\bootstrap5\Html;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class ActionColumn extends \kartik\grid\ActionColumn
{
    use ColumnTrait;

    public function init()
    {
        if (isset($this->grid->filterModel->counts)) {
            $this->template = '{view} {update} {items} {delete} {restore}<br/>' . PublicationStatusBehavior::getButtonsTemplate(0);
            $this->buttons = ArrayHelper::merge($this->buttons, PublicationStatusBehavior::getButtons());
        }
        $this->buttons['view']['icon'] = 'eye text-success';
        $this->buttons['update']['icon'] = 'pencil text-info';
        $this->buttons['delete']['icon'] = 'trash text-danger';
        $this->buttons['delete']['disabled'] = function ($model, $key, $index) {
            if (is_object($model) && method_exists($model, 'hasAttribute') && $model->hasAttribute('publication_status')) {
                return $model->getAttribute('publication_status') != PublicationStatusBehavior::STATUS_TRASH;
            }
            return false;
        };
        $this->buttonOptions['class'] = 'btn btn-xs btn-default';
        $this->buttonOptions['style'] = 'margin-top:5px;';
        parent::init();
        $this->width = '110px';
        $this->hAlign = GridView::ALIGN_LEFT;
    }

    /**
     * @param $model
     * @param $key
     * @param $index
     *
     * @return array|string|string[]|null
     * @throws InvalidConfigException
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        return preg_replace_callback('/\\{([\w\-\/]+)\\}/', function ($matches) use ($model, $key, $index) {
            $name = $matches[1];
            return $this->renderButton($name, $model, $key, $index);
        }, $this->template);
    }

    /**
     * Renders button.
     *
     * @param string $name button name.
     * @param        $model
     * @param        $key
     * @param        $index
     *
     * @return string rendered HTML
     * @throws InvalidConfigException on invalid button format.
     */
    protected function renderButton($name, $model, $key, $index)
    {
        if (!isset($this->buttons[$name])) {
            return '';
        }
        if (isset($this->visibleButtons[$name])) {
            $isVisible = $this->visibleButtons[$name] instanceof Closure
                ? call_user_func($this->visibleButtons[$name], $model, $key, $index)
                : $this->visibleButtons[$name];
        } else {
            $isVisible = true;
        }
        if (!$isVisible) {
            return '';
        }
        $button = $this->buttons[$name];
        $options = [];

        if ($button instanceof Closure) {
            $url = $this->createUrl($name, $model, $key, $index);
            return call_user_func($button, $url, $model, $key);
        }
        if (!is_array($button)) {
            throw new InvalidConfigException("Button should be either a Closure or array configuration.");
        }

        // Visibility :
        if (isset($button['visible'])) {
            if ($button['visible'] instanceof Closure) {
                if (!call_user_func($button['visible'], $model, $key, $index)) {
                    return '';
                }
            } elseif (!$button['visible']) {
                return '';
            }
        }

        // URL :
        if (isset($button['url'])) {
            $url = call_user_func($button['url'], $name, $model, $key, $index);
        } else {
            $url = $this->createUrl($name, $model, $key, $index);
        }

        // label :
        if (isset($button['label'])) {
            $label = $button['label'];

            if (isset($button['encode'])) {
                $encodeLabel = $button['encode'];
                unset($button['encode']);
            } else {
                $encodeLabel = true;
            }
            if ($encodeLabel) {
                $label = Html::encode($label);
            }
        } else {
            $label = '';
        }

        // icon :
        if (isset($button['icon'])) {
            $icon = $button['icon'];
            $label = Html::icon($icon, ['prefix' => 'fa fa-lg fa-']) . (empty($label) ? '' : ' ' . $label);
        }

        $options = array_merge($this->buttonOptions, ArrayHelper::getValue($button, 'options', []));

        // Disable :
        if (isset($button['disabled'])) {
            if ($button['disabled'] instanceof Closure) {
                if (call_user_func($button['disabled'], $model, $key, $index)) {
                    $options['class'] = ($options['class'] ? $options['class'] . ' ' : '') . 'disabled';
                }
            } elseif ($button['disabled']) {
                $options['class'] = ($options['class'] ? $options['class'] . ' ' : '') . 'disabled';
            }
        }

        return Html::a($label, $url, $options);
    }


    /**
     * Merges buttons with default configurations.
     */
    protected function initDefaultButtons()
    {
        $this->buttons = ArrayHelper::merge(
            [
                'view' => [
                    'icon' => 'eye-open',
                    'options' => [
                        'title' => Yii::t('yii', 'View'),
                        'aria-label' => Yii::t('yii', 'View'),
                        'data-pjax' => '0',
                    ],
                ],
                'update' => [
                    'icon' => 'pencil',
                    'options' => [
                        'title' => Yii::t('yii', 'Update'),
                        'aria-label' => Yii::t('yii', 'Update'),
                        'data-pjax' => '0',
                    ],
                ],
                'delete' => [
                    'icon' => 'trash',
                    'visible' => function ($model) {
                        /** @var $model BaseActiveRecord */
                        if (is_object($model) && $model->canGetProperty('isDeleted')) {
                            return !$model->isDeleted;
                        }
                        return true;
                    },
                    'options' => [
                        'title' => Yii::t('yii', 'Delete'),
                        'aria-label' => Yii::t('yii', 'Delete'),
                        'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                        'data-method' => 'post',
                        'data-pjax' => '0',
                    ],
                ],
                'restore' => [
                    'icon' => 'repeat',
                    'visible' => function ($model) {
                        /** @var $model BaseActiveRecord */
                        if (is_object($model) && $model->canGetProperty('isDeleted')) {
                            return $model->isDeleted;
                        }
                        return false;
                    },
                    'options' => [
                        'title' => Yii::t('yii2tech-admin', 'Restore'),
                        'aria-label' => Yii::t('yii2tech-admin', 'Restore'),
                        'data-confirm' => Yii::t('yii2tech-admin', 'Are you sure you want to restore this item?'),
                        'data-method' => 'post',
                        'data-pjax' => '0',
                    ],
                ],
            ],
            $this->buttons
        );
    }

    public function createUrl($action, $model, $key, $index)
    {
        if (is_callable($this->urlCreator)) {
            return call_user_func($this->urlCreator, $action, $model, $key, $index, $this);
        }

        $params = is_array($key) ? $key : ['id' => (string)$key];
        $params[0] = $this->controller ? $this->controller . '/' . $action : $action;
        $params['table'] = Yii::$app->request->get('table');

        return Url::toRoute($params, true);
    }
}
