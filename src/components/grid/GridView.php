<?php

namespace ripaym1970\autocrud\components\grid;

//use ripaym1970\autocrud\models\behaviors\PublicationStatusBehavior;
//use kartik\grid\EnumColumn;
use ripaym1970\autocrud\models\CrudModel;
use kartik\widgets\TouchSpin;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii2tech\admin\widgets\ButtonContextMenu;

class GridView extends \kartik\grid\GridView
{
    public $pjax = true;

    public $bordered = true;

    public $export = false;

    public $striped = false;

    public $condensed = false;

    public $responsive = false;

    public $hover = false;

    public $resizableColumns = false;

    public $floatHeader = false;

    //public $showPageSummary         = true;
    public $toolbar = ['{index}', '{create}',];

    public $panel = true;

    public $panelHeadingTemplate = '{title} {summary} <div class="clearfix"></div>';

    public $toolbarContainerOptions = ['class' => 'btn-toolbar kv-grid-toolbar toolbar-container pull-left', 'style' => 'width:100%'];

    public $dataColumnClass = 'kartik\grid\DataColumn';

    public $lists = [];

    public function init()
    {
        $this->panelFooterTemplate = <<< HTML
    <div class="kv-panel-pager">
        {pagesize}
    </div>
    <div class="kv-panel-pager">
        {pager}
    </div>
    {footer}
    <div class="clearfix"></div>
    <style>
        .panel-footer {
            display: flex;
            justify-content: left;
        }
        .kv-panel-pager:first-child {
            padding-right: 20px;
        }
    </style>
HTML;
        //echo '<pre>';var_dump('$this->view->params[contextMenuItems]=',$this->view->params['contextMenuItems']);echo '</pre>';exit();
        // Зачем эта проверка?
        //echo '<pre>';var_dump('=',$this->view->params);echo '</pre>';exit();
        //if (empty($this->view->params['contextMenuItems'][0]['table'])) {
        //    dd(['Что-то пошло не так: Таблица системная?', $this->view->params['contextMenuItems']]);
        //}
        $table = Yii::$app->request->get('table');
        //$tableClass = '\ripaym1970\autocrud\models\crud\\' . ucfirst($table) . 'Model';

        //$zzz = $this->dataProvider->query;
        //d(
        //    $zzz->prepare(\Yii::$app->db->queryBuilder)->createCommand()->rawSql,
        //    $this->dataProvider->pagination,
        //    $this->dataProvider->sort
        //);
        //return;

        $selectPageSize = '';
        $pageSizeLimit = $this->dataProvider->pagination->pageSizeLimit;
        if ($pageSizeLimit) {
            $currentPageSize = $this->dataProvider->getPagination()->getPageSize();
            $selectPageSize = '<select class="form-control" onchange="location = this.value;">';
            foreach ($pageSizeLimit as $value) {
                $url = Html::encode(
                    Url::current(['per-page' => $value, 'page' => null])
                );
                $selectPageSize .= '<option value="' . $url . '"' . ($currentPageSize == $value ? ' selected="selected"' : '') . '>';
                $selectPageSize .= $value;
                $selectPageSize .= '</option>';
            }
            $selectPageSize .= '</select>';
        }
        $this->replaceTags['{pagesize}'] = function () use ($selectPageSize) {
            return $selectPageSize;
        };

        $this->replaceTags['{index}'] = function () use ($table) {
            return '<a class="btn btn-default" href="/admin/' . $table . '/' . Yii::$app->controller->action->id . '">'
                . Yiit::t('Скинути фільтри')
                . '</a>';
        };
        $createButton = ButtonContextMenu::widget(['items' => $this->view->params['contextMenuItems'] ?? []]);
        if (isset($this->view->params['contextMenuItems'])) {
            unset($this->view->params['contextMenuItems']);
        }
        $this->replaceTags['{create}'] = function () use ($createButton) {
            return $createButton;
        };
        // TODO хз куда надо flush отправлять
        $this->replaceTags['{flush}'] = function () use ($table) {
            return '<a class="btn btn-default" href="/admin/' . $table . '/cache-flush">'
            . Yiit::t('Очистити кеш')
            . '</a>';
        };

        $this->replaceTags['{download}'] = function () use ($table) {
            return '<a class="btn btn-default" href="/admin/' . $table . '/np-download">'
                . Yiit::t('Отримати оновлення')
                . '</a>';
        };



        if ($this->panel === true) {
            $this->panel = ['type' => GridView::TYPE_PRIMARY, 'titleOptions' => ['class' => 'panel-title pull-left']];
        }
        if (is_array($this->panel)) {
            $this->panel['heading'] = $this->view->title;
        }
        //if (isset($this->filterModel->counts)) {
        //    $this->toolbar[] = '<div class="clearfix"></div>{tabs}';
        //    $items = [];
        //    foreach (PublicationStatusBehavior::statuses() as $name => $title) {
        //        $items[] = [
        //            'active' => $this->filterModel->publication_status == $name,
        //            'label' => $title . ' (' . (isset($this->filterModel->counts[$name]) ? $this->filterModel->counts[$name] : 0) . ')',
        //            'url' => [Yii::$app->controller->id . '/' . Yii::$app->controller->action->id,
        //                $this->filterModel->formName() => [
        //                    'publication_status' => $name,
        //                ],
        //            ],
        //        ];
        //    }
        //    $this->replaceTags['{tabs}'] = Tabs::widget([
        //        'items' => $items,
        //        'options' => ['class' => 'justify-content-center'],
        //    ]);
        //}

        $languageId = Yii::$app->language;

        $columns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.columns');
        $columnsTranslation = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '_translation.columns');

        //dd($this->columns);
        foreach ($this->columns as $i => $column) {
            if (is_null($column)) {
                unset($this->columns[$i]);
                continue;
            }

            if (is_string($column)) {
                $columnName = $column;
                $params = [];
                if (preg_match_all('|\[.*?\]|is', $columnName, $matches)) {
                    $columnName = str_replace($matches[0], '', $columnName);
                    foreach ($matches[0] as $match) {
                        if (strpos($match, '=')) {
                            $match = explode('=', trim($match, '[]'), 2);
                            $params[$match[0]] = $match[1];
                        }
                    }
                }

                $columnType = 'text';
                if (strpos($columnName, ':')) {
                    [$columnName, $columnType] = explode(':', $columnName, 2);
                }

                $columnLabel = Yiit::t(
                    $columns[$columnName]['comment']
                    ?? $columnsTranslation[$columnName]['comment']
                    ?? ''
                );

                switch ($columnType) {
                    case 'image':
                        $this->columns[$i] = [
                            'attribute' => $columnName,
                            'label'     => $columnLabel,
                            'format'    => $columnType,
                            'width'     => '120px',
                        ];
                        break;
                    case 'date':
                    case 'datetime':
                    case 'dateTime':
                        $this->columns[$i] = [
                            'attribute'           => $columnName,
                            'label'               => $columnLabel,
                            'format'              => $columnType,
                            'width'               => '230px',
                            'filterType'          => GridView::FILTER_DATE_RANGE,
                            'filterWidgetOptions' => [
                                'convertFormat' => true,
                                'pluginOptions' => [
                                    'locale' => [
                                        'format'    => 'd-m-Y',
                                        'separator' => ' - ',
                                    ],
                                ],
                            ],
                            //'tableOptions' => [
                            //    'style' => 'min-width:230px;width:230px;',
                            //],
                        ];
                        break;
                    case 'boolean':
                        $this->columns[$i] = [
                            'class'     => ToggleColumn::class,
                            'attribute' => $columnName,
                            'label'     => $columnLabel,
                            'filter' => [
                                '0' => Yiit::t('Неактивно'),
                                '1' => Yiit::t('Активно'),
                            ],
                            'filterInputOptions' => [
                                'class' => 'form-control',
                                'style' => 'padding:6px 2px;',
                                'prompt' => Yiit::t('Всі'),
                            ],
                            'contentOptions' => [
                                'class' => 'text-center',
                                //'prompt'  => Yiit::t('Виберіть'),
                            ],
                        ];
                        break;
                    case 'integer':
                        $this->columns[$i] = [
                            'attribute'           => $columnName,
                            'label'               => $columnLabel,
                            'filterType'          => GridView::FILTER_SPIN,
                            'filterWidgetOptions' => [
                                'pluginOptions' => [
                                    'verticalbuttons' => true,
                                ],
                            ],
                            'width'               => '10px',
                        ];
                        break;
                    case 'float':
                    case 'double':
                        $this->columns[$i] = [
                            'attribute'  => $columnName,
                            'label'      => $columnLabel,
                            'filterType' => GridView::FILTER_NUMBER,
                            'width'      => '10px',
                        ];
                        break;
                    //case 'publicationStatus':
                    //    $this->columns[$i] = [
                    //        'class' => EnumColumn::class,
                    //        'attribute' => $columnName,
                    //        'enum' => PublicationStatusBehavior::statuses(),
                    //        'width' => '10px',
                    //    ];
                    //    break;
                    case 'string':
                        $this->columns[$i] = [
                            'attribute' => $columnName,
                            'label'     => $columnLabel,
                            'format'    => 'raw',
                            'filter'    => false,
                        ];

                        if (!isset($params['table'])) {
                            break;
                        }
                        //dd($params);

                        $tableRel = $params['table'];
                        $isTranslations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableRel . '.behaviors.translations', false);
                        $className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($tableRel) . 'Model';

                        // Дефолтное имя поля
                        $nameRel = $tableRel;
                        // Уточним имя поля
                        if (isset($params['name_rel'])) {
                            $nameRel = $params['name_rel'];
                        }
                        $nameRel .= '_id';

                        //dd($params);
                        if (isset($params['target'])) {
                            $nameRel = $params['target'];
                        }

                        // Уточним имя поля для вывода
                        $name = $params['name'] ?? 'name';
                        //dd([$nameRel, $name]);

                        //$this->columns[$i]['attribute'] = $nameRel;
                        //dd([$this->columns[$i], $tableRel, $nameRel, $name]);

                        //$this->columns[$i]['label'] = Yiit::t(ucfirst($nameRel));
                        $this->columns[$i]['value'] = function (ActiveRecord $model) use ($className, $tableRel, $nameRel, $name, $isTranslations) {
                            $paramsId = $model->getAttribute($tableRel . '_' . $nameRel);
                            if (!empty($paramsId)) {
                                if ($isTranslations) {
                                    return $model->{$tableRel}->getVariationModels()[0]->{$name};
                                }

                                $paramsModel = $className::findOne([$nameRel => $paramsId]);

                                return Html::a(Html::encode($paramsModel->$name) . '&nbsp;(' . $paramsId . ')', [
                                    '/' . $tableRel . '/view/' . $paramsModel->id
                                ]);
                            }
                            return $paramsId;
                        };

                        // Получим IDs записей для фильтра (чтобы органичить присутствующими)
                        $thisClassName = '\ripaym1970\autocrud\models\crud\\' . ucfirst($table) . 'Model';
                        $targetName = isset($params['target'])
                            ? $params['name_rel'] . '_' . $params['target']
                            : $nameRel;
                        //dd([$thisClassName, $dd]);

                        $ids = $thisClassName::find()
                            ->distinct()
                            ->select([
                                $targetName,
                            ])
                            ->column()
                        ;
                        //dd($ids);

                        // Создадим запрос на получение списка для фильтра
                        $id = $params['target'] ?? 'id';
                        $query = $className::find()
                            ->distinct()
                            ->andWhere([
                                $id => $ids,
                            ]);
                        if (isset($params['where'])) {
                            $query->where($params['where']);
                        }
                        if ($isTranslations) {
                            $query->with([
                                'translations' => function ($query2) use ($languageId, $nameRel, $name) {
                                    $query2->select([
                                            $nameRel, // Надо для связи
                                            'language_id',
                                            $name,
                                        ])
                                        ->andWhere([
                                            'OR',
                                            ['language_id' => $languageId],
                                            ['language_id' => Yii::$app->params['languageSource']], // default
                                        ])
                                        ->andWhere(['NOT', [$name => null]]);
                                },
                            ]);
                        }
                        //dd($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);
                        $data = $query
                            ->asArray()
                            ->all();
                        //dd($data);

                        if ($isTranslations) {
                            foreach ($data as &$item) {
                                $item['translations'] = $item['translations'][0][$name] . ' (' . $item['id'] . ')';
                            }
                            ArrayHelper::multisort($data, 'translations');
                            $this->columns[$i]['filter'] = ArrayHelper::map($data, $id, 'translations');
                        } else {
                            ArrayHelper::multisort($data, $name);
                            //dd($data);
                            $this->columns[$i]['filter'] = ArrayHelper::map($data, $id, $name);
                        }
                        //dd($this->columns[$i]['filter']);
                        // Так тоже работает
                        //$this->columns[$i]['filter'] = ArrayHelper::map($className::find()->all(), 'id', 'name');

                        $this->columns[$i]['filterInputOptions'] = [
                            'class' => 'form-control',
                            'style' => 'padding:6px 2px;',
                            'prompt' => Yiit::t('Всі'),
                        ];

                        unset($params['table']);
                        unset($params['type']);
                        unset($params['where']);
                        unset($params['name_rel']);
                        unset($params['name']);
                        unset($params['target']);
                        //dd($this->columns[$i]);
                        break;
                    case 'array':
                        // Обычно пусто для привязанных городов, тегов если через таблицу связей
                        if (!$columnLabel) {
                            $columnLabel = ArrayHelper::getValue(
                                Yii::$app->params,
                                'tables.' . $table . '.relations.' . $columnName . '.label'
                            )
                                ?? Yiit::t(ucfirst($columnName));
                        }
                        $this->columns[$i] = [
                            'attribute' => $columnName,
                            'label'     => $columnLabel,
                            'format'    => $columnType,
                            'filter'    => false,
                        ];
                        //echo '<pre>';var_dump( $this->columns[$i]);echo '</pre>';exit();
                        if (isset($params['table'])) {
                            $className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($params['table']) . 'Model';
                            $this->columns[$i]['attribute'] = $params['table'] . '_id';
                            $this->columns[$i]['value'] = $columnName;
                            /** @var CrudModel $className */
                            if ($className::find()->count() < 300) {
                                $this->columns[$i]['filter'] = ArrayHelper::map($className::find()->all(), 'id', 'name');
                            }
                            $this->columns[$i]['filterInputOptions'] = [
                                'class' => 'form-control',
                                'style' => 'padding:6px 2px;',
                                'prompt' => Yiit::t('Всі'),
                            ];

                            unset($params['table']);
                        }
                        break;
                    case 'dropdown':
                        static $items = [];
                        if (empty($items[$columnName]) && isset($columns[$columnName]['items']) && $columns[$columnName]['items'] instanceof \Closure) {
                            $items[$columnName] = call_user_func($columns[$columnName]['items']);
                        }
                        $this->columns[$i] = [
                            //'class'     => EnumColumn::class,
                            'attribute'          => $columnName,
                            'label'              => $columnLabel,
                            'value'              => function (ActiveRecord $model) use ($columnName, $items) {
                                return $items[$columnName][$model->getAttribute($columnName)];
                            },
                            'filter'             => $items[$columnName],
                            'filterInputOptions' => [
                                'class'  => 'form-control zzzz',
                                'style'  => 'padding:6px 2px;',
                                'prompt' => Yiit::t('Всі'), // Это для фильтра, а не для создания
                            ],
                        ];
                        break;
                    case 'list':
                        $listColumnName = mb_strtolower(Inflector::id2camel($columnName, '_')) . 'List';
                        $this->columns[$i] = [
                            //'class'     => EnumColumn::class,
                            'attribute' => $columnName,
                            'label'     => $columnLabel,
                            'value'     => function (ActiveRecord $model) use ($columnName, $listColumnName) {
                                return $this->lists[$listColumnName][$model->getAttribute($columnName)];
                            },
                            'filter'             => $this->lists[$listColumnName],
                            'filterInputOptions' => [
                                'class' => 'form-control',
                                'style' => 'padding:6px 2px;',
                                'prompt' => Yiit::t('Всі'),
                            ],
                        ];
                        break;
                    default:
                        //dd('default'.$columnName);
                        $this->columns[$i] = [
                            'attribute' => $columnName,
                            'label'     => $columnLabel,
                            'format'    => $columnType,
                        ];

                        break;
                }
                //dd($this->columns);

                if ($columnName == 'index') {
                    if ($this->dataProvider->sort && empty($this->dataProvider->sort->defaultOrder)) {
                        $this->dataProvider->sort->defaultOrder = ['index' => SORT_ASC];
                    }
                    $sort = Yii::$app->request->get(
                        $this->dataProvider->sort
                            ? $this->dataProvider->sort->sortParam
                            : 'sort'
                    );
                    if (empty($sort) || $sort == 'index' || $sort == '-index') {
                        $this->columns[$i] = [
                            'class'          => IndexColumn::class,
                            'attribute'      => 'index',
                            'label'          => $columnLabel,
                            'format'         => 'integer',
                            'filter'         => $this->filterModel ? TouchSpin::widget([
                                'model'         => $this->filterModel, 'attribute' => 'index',
                                'pluginOptions' => [
                                    'verticalbuttons' => true,
                                ],
                                'options'       => [
                                    'class' => 'form-control',
                                ],
                            ]) : null,
                            'headerOptions'  => ['style' => 'width:100px;'],
                            'contentOptions' => ['style' => 'white-space:nowrap;'],
                        ];
                    }
                    if ($sort == '-index') {
                        $this->columns[$i]['buttons'] = [
                            'first' => [
                                'icon' => 'triangle-bottom',
                            ],
                            'last'  => [
                                'icon' => 'triangle-top',
                            ],
                            'prev'  => [
                                'icon' => 'arrow-down',
                            ],
                            'next'  => [
                                'icon' => 'arrow-up',
                            ],
                        ];
                    }
                }
                if (is_array($this->columns[$i]) && $params) {
                    $this->columns[$i] = ArrayHelper::merge($this->columns[$i], $params);
                }
                //dd($this->columns);
            } else {
                if (isset($column['attribute']) && !isset($column['label'])) {
                    $columnName = $column['attribute'];
                    $this->columns[$i]['label'] = $columns[$columnName]['comment'] ?? $columnsTranslation[$columnName]['comment'] ?? $columnName;
                }
            }

            /**
             * Создадим список для фильтра столбца
             */
            if (isset($this->columns[$i]['filter'])) {
                if ($this->columns[$i]['filter'] === 'distinct') {
                    $filterId = $this->columns[$i]['filter_id'] ?? 'id';
                    $filterName = $this->columns[$i]['filter_name'] ?? 'name';
                    $filterClassName = $this->columns[$i]['filter_class_name']
                        ?? $this->columns[$i]['filter_class_name']
                        ?? 'user';
                    /** @var ActiveRecord $filterClass */
                    $filterClass = '\ripaym1970\autocrud\models\crud\\' . ucfirst($filterClassName) . 'Model';
                    //dd($filterClass);

                    unset($this->columns[$i]['filter_id']);
                    unset($this->columns[$i]['filter_name']);
                    unset($this->columns[$i]['filter_class_name']);

                    $className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($table) . 'Model';
                    //dd($className, $this->columns[$i]['attribute']);
                    $filterIds = $className::find()->distinct()->select($this->columns[$i]['attribute'])->column();
                    //dd($filterIds);

                    /** @var */
                    $query = $filterClass::find()
                        ->distinct()
                        ->alias('self')
                        ->select([
                            $filterName,
                            'self.' . $filterId,
                        ])
                        ->indexBy('self.' . $filterId)
                        ->orderBy($filterName)
                        ->andWhere([
                            'self.' . $filterId => $filterIds,
                        ]);

                    if (isset((new $filterClass())->behaviors()['translations'])) {
                        $query->joinWith(['defaultTranslation'], false);
                    }
                    $filter = $query
                        ->column();
                } else {
                    $filter = $this->columns[$i]['filter'];
                }
                //dd($filter);
                $this->columns[$i]['filter'] = $filter;
                $this->columns[$i]['filterInputOptions'] = [
                    'class' => 'form-control',
                    'style' => 'padding:6px 2px;',
                    'prompt' => Yiit::t('Всі'),
                ];
            }
            //dd($this->columns);

            //if (isset($this->columns[$i]['filter'])) {
            //    if (isset($this->columns[$i]['filter']['distinct'])) {
            //        $filterClass = '\ripaym1970\autocrud\models\crud\\' . ucfirst(str_replace('_id','',$this->columns[$i]['attribute'])) . 'Model';
            //        $filterCondition = $this->columns[$i]['filter']['condition'] ?? [];
            //        $filterName = $this->columns[$i]['filter']['name'] ?? 'name';
            //        $filterId = $this->columns[$i]['filter']['id'] ?? 'id';
            //
            //        if (!empty($this->columns[$i]['filter']['distinct'])) {
            //            $ids = $tableClass::find()
            //                ->distinct()
            //                ->select([
            //                    $this->columns[$i]['attribute'],
            //                ])
            //                ->column()
            //            ;
            //            $filterCondition = [
            //                'self.id' => $ids,
            //            ];
            //        }
            //
            //        $this->columns[$i]['filter'] = ['' => 'Все',] + $filterClass::listing($filterCondition, $filterName,
            //                $filterId);
            //    }
            //}
            //dd($this->columns);
        }
        //dd($this->columns);

        if (isset($this->filterModel->parent)
            && $this->filterModel->parent
            && isset($this->filterModel->parentModel)
            && $this->filterModel->parentModel->hasAttribute('level')
        ) {
            for ($i = 0; $i < $this->filterModel->parentModel->level; $i++) {
                array_unshift($this->columns, [
                    'attribute' => 'id',
                    'label'     => 'ID',
                    'width'     => '50px',
                    'value'     => function ($model) {
                        return '';
                    },
                ]);
            }
        }
        //dd($this->columns);

        // Выше получили нужные нам свойства столбцов и передаем их в \kartik\grid\GridView
        return parent::init();
    }
}
