<?php

namespace ripaym1970\autocrud\components\mail\components\modules\Grid;

class Helper extends \ripaym1970\autocrud\components\DataSetContainer
{
    public $saveFilters = false;

    /**
     * @var bool $addDynamicRelations
     * should it automatically go through configured dynamic relations
     * and add related one-to-one models ?
     */
    public $addDynamicRelations = true;

    /**
     * @var []|string $route
     * - (optional) route to get the list
     */
    public $route = '';

    //* htmlAttributes => (optional) html attributes
    public $htmlAttributes = [];

    // gridSettings => (optional) array of grid settings
    public $settings = [];

    public $element = 'div';

    /**
     * query => (optional) callback, which returns ActiveQuery object.
     * Default is something like ModelClass::find()->order("id")
     * parameter: activeQuery
     * @var \yii\db\Query|callable|null
     */
    public $query;

    // for tree representation only, parent_id field
    public $parentIdField = 'parent_id';

    // if not empty, appropriate config for grag-n-drop will be injected
    // works only for tree representation
    public $dragNDropUrl;

    //for tree representation only
    // optional, callable (model) should return true if node is expanded
    public $expanded;


    //for tree representation only
    // optional, callable (model) should return true if node has children
    // default is !empty($model->children)
    public $hasChildren;


    /** @var  $responseHandler callable */
    // if set, processResponce will call it with array of composed data
    public $responseHandler;

    // for tree representation only
    // if not filters are set, it tries to filter by parent field
    // automatically (null or $_REQUEST['id'] which is provided by kendo tree)
    public $autoFilterByParentId = true;


    public function init()
    {
        if (!in_array($this->representation, static::AVAILABLE_REPRESENTATIONS)) {
            throw new \yii\base\InvalidConfigException(
                "Representation parameter is invalid"
            );
        }


        // we don't accept trees with proper behaviour
        if ($this->representation == self::TREE_REPRESENTATION) {
            \ripaym1970\autocrud\components\Util::hasBehavior(
                new $this->classMap[0](),
                \ripaym1970\autocrud\components\behaviors\Hierarchical::class,
                true
            );
        }


        $extendClasses = $this->addDynamicRelations
            && $this->classMap
            && \ripaym1970\autocrud\components\Util::hasBehavior(
                new $this->classMap[0](),
                \ripaym1970\autocrud\components\modules\DynamicModels\components\Behavior::class,
                false
            );

        if ($extendClasses) {
            /** @var \ripaym1970\autocrud\components\modules\DynamicModels\models\BaseModel $model */
            $model = new $this->classMap[0]();
            $this->classMap = \yii\helpers\ArrayHelper::merge(
                $this->classMap,
                $model->relationsForGrid
            );
        }

        return parent::init();
    }

    public function widget()
    {
        $dragDropConfig = [];

        if ($this->dragNDropUrl) {
            if (is_array($this->dragNDropUrl)) {
                $this->dragNDropUrl = \yii\helpers\Url::to($this->dragNDropUrl);
            }

            if ($this->representation != self::TREE_REPRESENTATION) {
                throw new \yii\base\InvalidConfigException(
                    "Drag-n-drop is implemented for tree only"
                );
            }
            $dragDropConfig = [
                'editable' => [
                    'move' => true,
                ],
                'dataSource' => [
                    'autoSync' => true,
                    'transport' => [
                        'update' => [
                            'url' => $this->dragNDropUrl,
                            'type' => 'POST'
                        ],
                    ],
                ],
            ];
        }

        $settings = \yii\helpers\ArrayHelper::merge(
            $this->columnsConfig,
            [
                'dataSource' => [
                    'schema' => [
                        'model' => $this->dataSourceDescription,
                    ],
                ],
            ],
            $dragDropConfig,
            $this->settings,
            [
                'saveFilters' => $this->saveFilters,
            ]
        );

        if (is_array($this->route)) {
            $this->route = \yii\helpers\Url::to($this->route);
        }

        return widgets\Grid::widget([
            'route' => $this->route,
            'representation' => $this->representation,
            'htmlAttributes' => \yii\helpers\ArrayHelper::merge(
                $this->htmlAttributes,
                [
                    'class' => [
                        self::TREE_REPRESENTATION => ['kendo-tree'],
                        self::GRID_REPRESENTATION => ['kendo-grid'],
                    ][$this->representation]
                ]
            ),
            'settings' => $settings,
            'element' => $this->element,
        ]);
    }


    protected function getColumnsConfig()
    {
        $columns = [];
        foreach ($this->classMap as $relation => $className) {
            $model = new $className();

            $gridConfig = $this->getConfigForClass($className);

            $originalColumnsConfig = $gridConfig['columns'] ?? [];
            $columnsConfig = [];

            $parentField = self::reformatFieldName($model, $this->parentIdField);

            foreach ($originalColumnsConfig as $key => $content) {
                if ($relation && !($content['includeInParent'] ?? true)) {
                    continue;
                }

                // a bit different formats are required for grid and tree (in tree we can't have more than one section)
                if ($this->representation == self::TREE_REPRESENTATION) {
                    // tree. hack for parent_id
                    if ($parentField == $content['field']) {
                        $content['field'] = 'parentId';
                    }  // hardcoded in kendo tree
                }

                // we don't need to push this to column description, kendo expects function there
                // but we needed this before for field model description
                unset($content['editable']);

                $columnsConfig[] = $content;
            }
            $columns[] = [
                'title' => $gridConfig['columnTitle']
                    ?? \Yii::t('app', "Untitled"),
                'columns' => $columnsConfig,
            ];
        }
        return ['columns' => $columns];
    }

    public function processRequest(): array
    {
        \Yii::$app->session->close();

        /** @var $models \yii\db\ActiveQuery */
        $models = ($this->classMap[0])::find();

        is_callable($this->query) && $models = call_user_func($this->query, $models);

        if ($this->representation == self::TREE_REPRESENTATION) {
            $models->with('allParents');
        }

        $possibleRelations = [
            'files',
        ];

        foreach ($this->classMap as $relation => $className) {
            if ($relation) {
                $models->with($relation);
                // can potentially be used in filter
                $models->joinWith($relation);
            }

            /** @var \yii\db\ActiveRecord $classModel */
            $classModel = new $className();

            foreach ($possibleRelations as $possibleRelation) {
                if (!$classModel->hasProperty($possibleRelation)) {
                    continue;
                }
                $models->with(
                    implode(
                        '.',
                        array_filter(
                            [
                                $relation,
                                $possibleRelation
                            ]
                        )
                    )
                );
            }
        }

        $filters = ($_REQUEST['filter'] ?? []) ?: [];

        // for the tree we don't need to filter by parent, if we have standard filter inside
        if (
            $this->representation == self::TREE_REPRESENTATION
            && !$filters
            && $this->autoFilterByParentId
        ) {
            $parentId = ($_REQUEST['id'] ?? null) ?: null;
            $models->andWhere([
                $models->modelClass::tableName() . '.' . $this->parentIdField => $parentId
            ]);
        }

        $this->parseFilters($models, $filters);

        //// count has sense for grid only
        $count = 0;

        if ($this->representation == self::GRID_REPRESENTATION) {
            $count = $models->count();

            if (isset($_REQUEST['take']) && is_null($models->limit)) {
                $models->limit($_REQUEST['take']);
            }
            if (isset($_REQUEST['skip']) && is_null($models->offset)) {
                $models->offset($_REQUEST['skip']);
            }
        }


        // sorting
        $order = [];
        foreach ($_REQUEST['sort'] ?? [] as $sorting) {
            $sortDirection = $sorting['dir'] ?? 'asc';
            $sortField = $sorting['field'] ?? null;
            list($class, $field) = explode('__', $sortField);
            foreach ($this->classMap as $relation => $className) {
                $shortClass = \ripaym1970\autocrud\components\Util::getShortClassName($className);
                if ($shortClass != $class) {
                    continue;
                }
                $order[] = $className::tableName() . '.' . $field . ' ' . $sortDirection;
            }
        }

        // apply ordering after count calculation
        $order && $models->orderBy(implode(',', $order));
        $result = [];

        $cachedIds = [];
        $primaryKey = ($models->modelClass)::primaryKey()[0];

        foreach ($models->each(1000) as $mainModel) {
            $parentModels = [$mainModel];

            if ($this->representation == self::TREE_REPRESENTATION && $filters) {
                $parentModels = [];
                foreach ($mainModel->pathToParent as $item) {
                    if ($cachedIds[$item->$primaryKey] ?? null) {
                        break;
                    }
                    $cachedIds[$item->$primaryKey] = 1;
                    $parentModels[] = $item;
                }
            }


            foreach ($parentModels as $sourceModel) {
                $element = [];

                foreach ($this->classMap as $relation => $className) {
                    $model = $sourceModel;

                    if ($relation) {
                        foreach (explode('.', $relation) as $part) {
                            $model = $model->$part;
                            if (!$model) {
                                break;
                            }
                        }
                    }
                    if (!$model) {
                        continue;
                    }

                    $gridConfig = $this->getConfigForClass($className);
                    $originalColumnsConfig = $gridConfig['columns'] ?? [];

                    foreach ($originalColumnsConfig as $content) {
                        if ($relation && !($content['includeInParent'] ?? true)) {
                            continue;
                        }

                        // get initial field name back
                        $sourceFieldName = explode('__', $content['field'])[1];

                        // getter is not needed for grid, if it has values.
                        if (
                            $this->representation == self::GRID_REPRESENTATION
                            && isset($content['getter'])
                            && isset($content['values'])
                        ) {
                            unset($content['getter']);
                        }


                        $getter = $content['getter'] ?? null;

                        $element[$content['field']] = is_callable($getter)
                            ? call_user_func($getter, $model)
                            : $model->$sourceFieldName;
                    }

                    if ($this->representation == self::TREE_REPRESENTATION) {
                        // if filter is set, we can't rely on static or external callable
                        // and items are expanded if filter is set
                        if ($filters) {
                            $element ['expanded'] = true;
                        } else {
                            $element = \yii\helpers\ArrayHelper::merge(
                                $element,
                                [
                                    'hasChildren' => is_callable($this->hasChildren)
                                        ? call_user_func($this->hasChildren, $sourceModel)
                                        : !empty($sourceModel->children),
                                    'expanded' => is_callable($this->expanded)
                                        ? call_user_func($this->expanded, $sourceModel)
                                        : ($this->expanded ? true : false),
                                ]
                            );
                        }
                    }
                }
                $result[] = $element;
            }
        }

        $result = [
            'models' => $result,
            'total' => $count ?: count($result),
        ];


        return is_callable($this->responseHandler)
            ? call_user_func($this->responseHandler, $result)
            : $result;
    }

    /**
     * @param string $className
     * @param string $field
     * @param array $conditions
     *
     * @return array|\string[][]
     */
    public static function filterItems(string $className, string $field, array $conditions = [])
    {
        return array_map(
            function (string $x) {
                return [
                    'value' => $x,
                    'text' => $x,
                ];
            },
            \ripaym1970\autocrud\components\Util::distinctValues($className, $field, $conditions)
        );
    }

    /** @return array */
    /* helper to build filter items from arrays */
    public static function filterItemsFromArray(array $collection)
    {
        $result = [];
        foreach ($collection as $k => $v) {
            $result[] = [
                'value' => $k,
                'text' => $v,
            ];
        }
        return $result;
    }


    /** @return array */
    public static function typicalColumn(
        \yii\db\ActiveRecord $model,
        $fieldName,
        array $additionalAttributes = []
    ) {
        if ($additionalAttributes['computable'] ?? false) {
            $additionalAttributes['filterable'] = false;
            $additionalAttributes['sortable'] = false;
        } else {
            $additionalAttributes = \yii\helpers\ArrayHelper::merge(
                [
                    'filterable' => [
                        'cell' => [
                            'operator' => ($additionalAttributes['values'] ?? null)
                                ? 'eq'
                                : 'contains',
                            'minLength' => 5000,
                        ],
                    ],
                    'sortable' => true
                ],
                $additionalAttributes
            );
        }
        unset($additionalAttributes['computable']);

        return \yii\helpers\ArrayHelper::merge(
            [
                'field' => self::reformatFieldName($model, $fieldName),
                'title' => $model->getAttributeLabel($fieldName),
                'resizable' => true,
                'columnMenu' => true,
                'type' => 'string',
            ],
            $additionalAttributes
        );
    }

    public static function typicalJsonColumn(
        \yii\db\ActiveRecord $model,
        $fieldName,
        array $additionalAttributes = []
    ) {
        $getter = function (array $data) use (&$getter) {
            $content = [
                \yii\helpers\Html::beginTag('ul', ['class' => 'mb-0']),
            ];
            foreach ($data as $key => $value) {
                $isFile = is_array($value)
                    && count(array_keys($value)) == 3
                    && array_key_exists('name', $value)
                    && array_key_exists('type', $value)
                    && array_key_exists('content', $value);
                $line = [
                    $key . ': '
                ];
                if (is_scalar($value)) {
                    $line [] = $value;
                } elseif ($isFile) {
                    $line [] = \yii\helpers\Html::tag(
                        'object',
                        $value['name'],
                        [
                            'data' => $value['content'],
                            'style' => 'max-height: 100px;'
                        ]
                    );
                } else {
                    $line [] = $getter($value);
                }
                $content [] = \yii\helpers\Html::tag(
                    'li',
                    implode('', $line),
                );
            }
            $content[] = \yii\helpers\Html::endTag('ul');
            return implode(' ', $content);
        };
        return self::typicalHtmlColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'computable' => true,
                    'getter' => static fn(\yii\db\ActiveRecord $x) => $getter($x->$fieldName),
                ],
                $additionalAttributes
            )
        );
    }


    /** @return array */
    public static function typicalHtmlColumn(
        \yii\db\ActiveRecord $model,
        $fieldName,
        array $additionalAttributes = []
    ) {
        return self::typicalColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'encoded' => false,
                ],
                $additionalAttributes
            )
        );
    }

    /** @return array */
    public static function typicalEmailColumn(
        \yii\db\ActiveRecord $model,
        $fieldName,
        array $additionalAttributes = []
    ) {
        $field = self::reformatFieldName($model, $fieldName);
        return self::typicalColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'template' => '# if (' . $field . ') { # <a class="badge badge-primary" href="mailto: #: ' . $field . ' #">
                        #: ' . $field . ' #
                    </a> # } else "" #
                    ',
                ],
                $additionalAttributes
            )
        );
    }

    public static function typicalBooleanColumn(
        \yii\db\ActiveRecord $model,
        $fieldName,
        array $additionalAttributes = []
    ) {
        $f = self::reformatFieldName($model, $fieldName);

        if ($additionalAttributes['editable'] ?? false) {
            $template = '<input type="checkbox" name="' . $f . '" #= '
                . $f
                . ' ? "checked=checked" : "" # ></input>';
        } else {
            $template = '<input type="checkbox" disabled="disabled" name="' . $f . '" #= '
                . $f
                . ' ? "checked=checked" : "" # ></input>';
        }
        return self::typicalColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'template' => $template,
                    'attributes' => [
                        'align' => 'center',
                        'class' => 'no-tooltip',
                    ],
                    'type' => 'boolean',
                    'filterable' => new \yii\helpers\ReplaceArrayValue(
                        [
                            'cell' => [
                                'operator' => 'eq',
                            ],
                        ]
                    ),
                    'values' => [
                        [
                            'text' => \Yii::t('app', 'Yes'),
                            'value' => true,
                        ],
                        [
                            'text' => \Yii::t('app', 'No'),
                            'value' => false,
                        ]
                    ]
                ],
                $additionalAttributes
            )
        );
    }

    public static function typicalMoneyColumn(
        \yii\db\ActiveRecord $model,
        $fieldName,
        array $additionalAttributes = []
    ) {
        return self::typicalColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'format' => "{0:c}",
                    'type' => 'number',
                    'attributes' => [
                        'align' => 'right',
                    ],
                    'filterable' => new \yii\helpers\ReplaceArrayValue([
                        'cell' => [
                            'operator' => 'eq',
                        ],
                    ]),
                ],
                $additionalAttributes
            )
        );
    }

    public static function typicalActionsColumn(
        \yii\db\ActiveRecord $model,
        $fieldName = '',
        array $additionalAttributes = []
    ) {
        return self::typicalHtmlColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'attributes' => [
                        'align' => 'center',
                        'class' => 'no-tooltip text-wrap',
                    ],
                    'computable' => true,
                    'groupable' => false,
                    'includeInParent' => false,
                    'exportable' => false,
                ],
                $additionalAttributes
            )
        );
    }

    public static function typicalDetailsColumn(
        \yii\db\ActiveRecord $model,
        $detailsPath,
        array $additionalAttributes = [],
        $fieldName = ''
    ) {
        return self::typicalActionsColumn(
            $model,
            $fieldName ?: 'details',
            \yii\helpers\ArrayHelper::merge(
                [
                    'attributes' => [
                        'align' => 'center',
                        'class' => 'no-tooltip',
                    ],
                    'title' => \Yii::t('app', "Details"),
                    'getter' => fn($model) => (string)\rmrevin\yii\fontawesome\FAS::icon(
                        \rmrevin\yii\fontawesome\FAS::_EYE,
                        [
                            'class' => 'js-property-icon',
                            'data-href' => \yii\helpers\Url::to(
                                is_callable($detailsPath)
                                    ? $detailsPath($model)
                                    : $detailsPath
                            ),
                        ]
                    ),
                ],
                $additionalAttributes
            )
        );
    }

    public static function typicalDateTimeColumn(
        \yii\db\ActiveRecord $model,
        $fieldName = '',
        array $additionalAttributes = []
    ) {
        return self::typicalColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'type' => 'date',
                    'filterable' => new \yii\helpers\ReplaceArrayValue(
                        [
                            'ui' => 'datepicker',
                            'cell' => [
                                'operator' => 'eq',
                            ],
                        ]
                    ),
                    'format' => '{0:G}'
                ],
                $additionalAttributes
            )
        );
    }

    public static function typicalTimeColumn(
        \yii\db\ActiveRecord $model,
        $fieldName = '',
        array $additionalAttributes = []
    ) {
        return self::typicalColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'filterable' => new \yii\helpers\ReplaceArrayValue(
                        [
                            'ui' => 'timepicker',
                            'cell' => [
                                'operator' => 'eq',
                            ],
                        ]
                    ),
                    'format' => "{0:T}",
                    'type' => 'date',
                ],
                $additionalAttributes
            )
        );
    }

    public static function typicalDateColumn(
        \yii\db\ActiveRecord $model,
        $fieldName = '',
        array $additionalAttributes = []
    ) {
        return self::typicalColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'filterable' => new \yii\helpers\ReplaceArrayValue(
                        [
                            'ui' => 'datepicker',
                            'cell' => [
                                'operator' => 'eq',
                            ],
                        ]
                    ),
                    'type' => 'date',
                    'format' => '{0:d}',
                ],
                $additionalAttributes
            )
        );
    }

    public static function typicalNumberColumn(
        \yii\db\ActiveRecord $model,
        $fieldName,
        array $additionalAttributes = []
    ) {
        return self::typicalColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'type' => 'number',
                    'filterable' => new \yii\helpers\ReplaceArrayValue(
                        [
                            'cell' => [
                                'operator' => 'eq',
                            ],
                        ]
                    ),
                ],
                $additionalAttributes
            )
        );
    }

    /**
     * Display image along with text
     *
     * @param \yii\db\ActiveRecord $model
     * @param $fieldName
     * @param array $additionalAttributes
     *
     * @return array
     */
    public static function typicalImageTextColumn(
        \yii\db\ActiveRecord $model,
        $fieldName,
        array $additionalAttributes = []
    ) {
        return self::typicalHtmlColumn(
            $model,
            $fieldName,
            \yii\helpers\ArrayHelper::merge(
                [
                    'getter' => function (\yii\db\ActiveRecord $x) use ($fieldName) {
                        /** @var \ripaym1970\autocrud\components\modules\File\behaviors\File $x */
                        $content = [
                            $x->fileLink
                                ? \yii\helpers\Html::img(
                                $x->fileLink,
                                ['style' => 'max-height: 20px;']
                            )
                                : ''
                        ];

                        if (!$x->{$fieldName} instanceof \ripaym1970\autocrud\components\modules\File\models\File) {
                            $content[] = $x->{$fieldName};
                        }

                        return implode(' ', $content);
                    }
                ],
                $additionalAttributes
            )
        );
    }
}
