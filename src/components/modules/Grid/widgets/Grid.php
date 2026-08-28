<?php

namespace ripaym1970\autocrud\components\modules\Grid\widgets;

/**
 * @inheritdoc
 * @property array $profiles
 */
class Grid extends \yii\base\Widget
{

    public $userAccessComponent = 'user';

    protected const ALL = "All";

    /** @var  string $route */
    public $route;

    // grid or tree constant
    public $representation;


    public $htmlAttributes = [];
    public $settings = [];

    public $element = 'div';

    protected $defaultHtmlAttributes = [];

    protected const PAGE_SIZES = [
        100,
        200,
        400,
        1000,
        2000,
        4000,
        10000,
        self::ALL
    ];

    protected $defaultSettings = [
        // hack, hide multi-column top header
        // We really don't want it sometimes
        'hideMultiColumn' => false,
        'columns' => [],
        'excel' => [
            'filterable' => true,
        ],
        'filterable' => [
            'mode' => 'menu, row',
        ],
        'groupable' => [
            'enabled' => true,
            'showFooter' => true,
        ],
        'pageable' => [
            'numeric' => false,
            'input' => true,
            'info' => true,
            'pageSizes' => self::PAGE_SIZES,
            'refresh' => true, // can be updated in init() if url is empty
            'navigatable' => true,
        ],
        'resizable' => true,
        'reorderable' => true,
        'selectable' => 'row',
        'sortable' => [
            'allowUnsort' => false,
        ],
        'toolbar' => '',
        'scrollable' => true,
        'mobile' => true,
        'draggable' => false,
        'droppable' => false,
        'noRecords' => '',
        'dataSource' => [
            'autoSync' => true,
            'serverAggregates' => false,
            'serverGrouping' => false,
            'serverFiltering' => true,
            'serverPaging' => true,
            'serverSorting' => true,
            'pageSize' => null, // changed in init()
            'schema' => [
                'data' => 'models',
                'total' => 'total',
            ],
            'transport' => [
                'read' => [
                    'url' => '', // changed in init()
                    'dataType' => 'json',
                    'cache' => false,
                ],
            ],
        ],
    ];


    public function init()
    {
        $this->defaultSettings['noRecords'] = [
            'template' => \yii\helpers\Html::tag(
                'div',
                \yii\helpers\Html::tag(
                    'span',
                    \Yii::t('app', 'No records found'),
                    [
                        'aria-live' => 'polite',
                    ]
                ),
                ['class' => 'mx-auto']
            ),
        ];

        // for grid only
        if ($this->representation == \ripaym1970\autocrud\components\modules\Grid\Helper::GRID_REPRESENTATION) {
            $this->defaultSettings['persistSelection'] = true;
            $this->defaultSettings['navigatable'] = true;
        }

        $toolbar = $this->settings['toolbar'] ?? '';
        if (is_object($toolbar)) {
            $this->settings['toolbar'] = (string)$toolbar;
        }


        /// it's faster to go through columns and fetch titles/fields
        /// then push it to columnMenu
        /// see https://docs.telerik.com/kendo-ui/api/javascript/ui/grid/configuration/columnmenu.columns.groups
        $groups = [];
        foreach ($this->settings['columns'] as $group) {
            $element = [
                'title' => $group['title'],
                'columns' => [],
            ];
            foreach ($group['columns'] as $column) {
                $element['columns'][] = $column['field'];
            }
            $groups[] = $element;
        }
        $this->settings['columnMenu'] = [
            'columns' => [
                'groups' => $groups,
            ]
        ];

        $this->settings = \yii\helpers\ArrayHelper::merge(
            $this->defaultSettings,
            [
                'dataSource' => [
                    'pageSize' => self::PAGE_SIZES[0],
                    'transport' => [
                        'read' => [
                            'url' => $this->route
                        ],
                    ],
                ],
            ],
            $this->settings
        );

        if ($this->representation == \ripaym1970\autocrud\components\modules\Grid\Helper::TREE_REPRESENTATION) {
            $this->settings = \yii\helpers\ArrayHelper::merge(
                $this->settings,
                [
                    'pageable' => false,
                    'dataSource' => [
                        'pageSize' => null,
                    ]
                ]
            );
        }

        $this->htmlAttributes = \yii\helpers\ArrayHelper::merge(
            $this->defaultHtmlAttributes,
            $this->htmlAttributes
        );

        $this->htmlAttributes['id'] = $this->htmlAttributes['id'] ?? uniqid();

        return parent::init();
    }

    public function getProfiles()
    {
        $profilesInfo = [
            'enabled' => true,
        ];


        $url = parse_url($this->route, PHP_URL_PATH);
        // no url --->  profiles are not possible;

        if (!$url) {
            $profilesInfo['enabled'] = false;
            return $profilesInfo;
        }

        $availableProfiles = \ripaym1970\autocrud\components\modules\Grid\models\GridProfile::getAvailable(
            $this->representation,
            $url
        );

        /** @var \yii\web\User $user */
        $user = \Yii::$app->{$this->userAccessComponent};
        $currentProfile = \ripaym1970\autocrud\components\modules\Grid\models\GridProfileUsage::getLast(
            $user->identity,
            \yii\helpers\ArrayHelper::getColumn(
                $availableProfiles,
                'id'
            )
        );

        $controllable = \Yii::$app->authManager
            && $user->can(
                \ripaym1970\autocrud\components\modules\Grid\Module::CAN_MANAGE_OWN_PROFILES
            )
            || !\Yii::$app->authManager;

        $limitedAccess = \Yii::$app->authManager
            && $user->can(
                \ripaym1970\autocrud\components\modules\Grid\Module::CAN_ACCESS_LIMITED_PROFILE
            )
            || !\Yii::$app->authManager;


        return \yii\helpers\ArrayHelper::merge(
            $profilesInfo,
            [
                'currentProfileId' => $currentProfile->id ?? 0,
                'availableProfiles' => $availableProfiles,
                'controllable' => $controllable,
                'limitedAccess' => $limitedAccess
            ]
        );
    }


    public function run()
    {
        return $this->render(
            "grid",
            ['widget' => $this]
        );
    }
}
