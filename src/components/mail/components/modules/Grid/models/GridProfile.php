<?php

namespace ripaym1970\autocrud\components\mail\components\modules\Grid\models;

use ripaym1970\autocrud\components\modules\Grid\Helper;
use Yii;

/**
 * @property int $id
 * @property string $name
 * @property string $notes
 * @property string $url
 * @property int $type_id
 * @property array $data
 * @property array $data_filter
 * @property string $parent_class
 * @property int $parent_id
 * @property int $auto_refresh_time
 * @property bool $has_custom_filter
 * @property string $rowActions
 *
 * @property GridProfileShare[] $shares
 * @property GridProfileUsage[] $usage
 * @property \yii\db\ActiveRecord $owner
 *
 * @property bool $isEditable
 * @property array $profileRepresentation
 *
 * @mixin \ripaym1970\autocrud\components\behaviors\Relation
 * @mixin \ripaym1970\autocrud\components\behaviors\Representation
 */
class GridProfile extends \yii\db\ActiveRecord
    implements \ripaym1970\autocrud\components\interfaces\IPolymorphicModel
{
    public $saveFilters = false;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'grid_profiles';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['name'],
                'default',
                'value' => Yii::t('app', "Unnamed"),
            ],
            [
                ['auto_refresh_time'],
                'default',
                'value' => 0,
            ],
            [
                ['auto_refresh_time'],
                'filter',
                'filter' => function ($value) {
                    return is_numeric($value)
                        ? $value
                        : $this->convertAutoRefreshTimeToNumber();
                }
            ],
            [
                ['name', 'parent_class', 'parent_id', 'url', 'type_id', 'has_custom_filter'],
                'required'
            ],
            [
                ['parent_id', 'type_id'],
                'integer'
            ],
            [
                ['url'],
                'filter',
                'filter' => function ($x) {
                    return parse_url($x, PHP_URL_PATH);
                }
            ],
            [
                ['type_id'],
                'in',
                'range' => [
                    \ripaym1970\autocrud\components\modules\Grid\Helper::GRID_REPRESENTATION,
                    \ripaym1970\autocrud\components\modules\Grid\Helper::TREE_REPRESENTATION,
                ],
            ],
            [
                ['name', 'url', 'notes'],
                'string',
                'max' => 255
            ],
            [
                ['parent_class'],
                \ripaym1970\autocrud\components\validators\Owner::class,
                'idField' => 'parent_id',
            ],
            [
                ['auto_refresh_time'],
                'integer',
                'min' => 0,
                'max' => 3600,
                'enableClientValidation' => false,
            ],
            [
                ['has_custom_filter'],
                'boolean'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yiit::t('ID'),
            'name' => Yiit::t('Name'),
            'notes' => Yiit::t('Notes'),
            'url' => Yiit::t('Data source URL'),
            'type_id' => Yiit::t('Type ID'),
            'data' => Yiit::t('Data'),
            'data_filter' => Yiit::t('Data Filter'),
            'parent_class' => Yiit::t('Parent class'),
            'parent_id' => Yiit::t('Parent ID'),
            'auto_refresh_time' => Yiit::t('Auto Refresh Time, min:sec'),
            'has_custom_filter' => Yiit::t('Use custom filter'),
            'rowActions' => Yiit::t("Actions"),
            'saveFilters' => Yiit::t('Also save current filters(s)'),
        ];
    }

    public function attributeHints()
    {
        return [
            'auto_refresh_time' => Yii::t(
                "app",
                "Leave empty or set to zero to disable auto refresh"
            ),
            'has_custom_filter' => Yii::t(
                "app",
                "This will remove standard filtering and add custom filter widget at the grid header. Possible to change only for profiles without filter"
            ),
        ];
    }


    public function behaviors()
    {
        return \yii\helpers\ArrayHelper::merge(
            parent::behaviors(),
            [
                \ripaym1970\autocrud\components\modules\HistoricalRecords\behaviors\HistoricalRecord::class,
                \ripaym1970\autocrud\components\behaviors\Representation::class,
                [
                    'class' => \ripaym1970\autocrud\components\behaviors\Relation::class,
                    'relationsToRemove' => ['shares', 'usage'],
                ],
            ]
        );
    }

    public function getOwner()
    {
        /** @var \yii\db\ActiveRecord $class */
        $class = $this->parent_class;
        return $class::findOne($this->parent_id);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShares()
    {
        return $this->hasMany(GridProfileShare::class, ['profile_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsage()
    {
        return $this->hasMany(GridProfileUsage::class, ['profile_id' => 'id']);
    }

    public function getIsEditable(): bool
    {
        return Yii::$app->user->id == $this->parent_id
            && Yii::$app->user->identityClass == $this->parent_class;
    }

    public static function getAvailable($typeId, $url): array
    {
        $result = [
            (new self())->profileRepresentation,
        ];

        /** @var self[] $models */
        $models = self::find()
            ->where([
                'type_id' => $typeId,
                'url' => $url,
            ])
            ->orderBy('name')
            ->all();
        foreach ($models as $model) {
            $addToList = $model->isEditable;

            // check shared profiles
            if (!$addToList) {
                $ownerShares = \ripaym1970\autocrud\components\modules\Grid\Module::getInstance()->getOwnerShares(
                    Yii::$app->user->identity
                );

                foreach ($ownerShares as $ownerShare) {
                    foreach ($model->shares as $share) {
                        if ($share->parent_class != get_class($ownerShare)) {
                            throw new \yii\base\InvalidConfigException("Invalid sharing classes detected");
                        }

                        if (!$share->parent_id || $share->parent_id == $ownerShare->id) {
                            $addToList = true;
                            break 2;
                        }
                    }
                }
            }

            if (!$model->owner) {
                throw new \yii\base\Exception("Owner was not found");
            }

            if ($addToList) {
                $result[] = $model->profileRepresentation;
            }
        }

        return $result;
    }

    public function getRowActions()
    {
        $actions = [
            \ripaym1970\autocrud\components\HtmlActions\Icon::gridDestroy([
                "/grid/profiles-management/destroy",
                'id' => $this->id,
            ])
        ];
        return implode(' ', $actions);
    }

    public function getProfileRepresentation(): array
    {
        // default profile
        if ($this->isNewRecord) {
            return [
                'id' => 0,
                'editable' => false,
                'data' => [],
                'data_filter' => [],
                'group' => '',
                'name' => Yii::t('app', "Default layout"),
                'auto_refresh_time' => 0,
                'has_custom_filter' => false,
            ];
        }
        $group = $this->isEditable
            ? Yiit::t('My profiles')
            : Yiit::t('Other profiles');
        return [
            'id' => $this->id,
            'editable' => $this->isEditable,
            'data' => $this->data,
            'data_filter' => $this->data_filter,
            'group' => $group,
            'name' => $this->name,
            'auto_refresh_time' => $this->auto_refresh_time,
            'has_custom_filter' => $this->has_custom_filter,
        ];
    }

    public function cloneModel()
    {
        $clone = new self([
            'parent_id' => Yii::$app->user->id,
            'parent_class' => Yii::$app->user->identityClass,
        ]);

        $fields = [
            'url',
            'name',
            'data',
            'type_id',
            'auto_refresh_time',
            'has_custom_filter',
        ];
        foreach ($fields as $field) {
            $clone->$field = $this->$field;
        }
        $clone->data_filter = $this->data_filter ?: new \yii\db\Expression("'{}'::json");
        $clone->name .= ' (clone)';
        $transaction = \ripaym1970\autocrud\components\Util::makeTransaction(true);
        \ripaym1970\autocrud\components\Util::saveModel($clone);

        foreach ($this->shares as $share) {
            $clonedShare = new GridProfileShare([
                'parent_class' => $share->parent_class,
                'parent_id' => $share->parent_id,
                'profile_id' => $clone->id,
            ]);
            \ripaym1970\autocrud\components\Util::saveModel($clonedShare);
        }
        $transaction->commit();
        return $clone;
    }

    public function setShares(array $shareIds)
    {
        if (in_array(-1, $shareIds)) {
            $shareIds = [0];
        }

        \ripaym1970\autocrud\components\Util::deleteCollection($this->shares);

        $firstAvailableShare = \ripaym1970\autocrud\components\modules\Grid\Module::getInstance()->possibleShares[0] ?? null;
        if ($firstAvailableShare) {
            foreach ($shareIds as $shareId) {
                $share = new GridProfileShare([
                    'parent_id' => $shareId,
                    'parent_class' => get_class($firstAvailableShare),
                    'profile_id' => $this->id,
                ]);
                \ripaym1970\autocrud\components\Util::saveModel($share);
            }
        }
        \ripaym1970\autocrud\components\modules\Grid\models\GridProfileUsage::fixSharing($this);
    }

    public function convertAutoRefreshTimeToString(): string
    {
        $multipliers = [60, 1];
        $result = [];
        $value = $this->auto_refresh_time;
        foreach ($multipliers as $multiplier) {
            $portion = floor($value / $multiplier);
            $value -= $portion * $multiplier;
            $result[] = \ripaym1970\autocrud\components\Util::strPadUnicode(
                $portion,
                '0',
                STR_PAD_LEFT
            );
        }
        return implode(':', $result);
    }

    public function convertAutoRefreshTimeToNumber(): int
    {
        $values = array_reverse(
            explode(':', $this->auto_refresh_time)
        );
        $multipliers = [1, 60];
        $result = 0;
        foreach ($values as $i => $value) {
            $result += $multipliers[$i] * $value;
        }
        return $result;
    }

    public static function getSharesData(array $selectedShareIds)
    {
        $module = \ripaym1970\autocrud\components\modules\Grid\Module::getInstance();

        $items = array_map(
            function (\yii\db\ActiveRecord $x) use ($selectedShareIds, $module) {
                $element = [
                    'label' => $module->getShareRepresentation($x),
                    'id' => $x->id,
                ];
                if (in_array($x->id, $selectedShareIds)) {
                    $element['checked'] = true;
                }
                return $element;
            },
            $module->possibleShares
        );

        return $items
            ? [
                [
                    'label' => Yii::t('app', "All"),
                    'id' => -1,
                    'checked' => in_array(null, $selectedShareIds),
                    'items' => $items,
                    'expanded' => count($items) > 0,
                ]
            ]
            : $items;
    }

    public static function gridConfig()
    {
        $model = new self();
        $module = \ripaym1970\autocrud\components\modules\Grid\Module::getInstance();
        return [
            'columnTitle' => Yii::t('app', "Grid Profiles Management"),
            'columns' => [
                Helper::typicalNumberColumn(
                    $model,
                    'id'
                ),
                Helper::typicalHtmlColumn(
                    $model,
                    'url'
                ),
                Helper::typicalHtmlColumn(
                    $model,
                    'name'
                ),
                Helper::typicalColumn(
                    $model,
                    'notes'
                ),
                Helper::typicalColumn(
                    $model,
                    'author',
                    [
                        'computable' => true,
                        'getter' => fn(self $x) => $module->getOwnerIdentifier(
                            $x->owner
                        ),
                    ]
                ),
                Helper::typicalColumn(
                    $model,
                    'auto_refresh_time',
                    [
                        'computable' => true,
                        'getter' => fn(self $x) => Yii::$app->formatter->asDuration($x->auto_refresh_time),
                    ]
                ),
                Helper::typicalBooleanColumn(
                    $model,
                    'has_custom_filter',
                ),
                Helper::typicalActionsColumn(
                    $model,
                    'rowActions',
                    [
                        'width' => '80px',
                    ]
                )
            ],
        ];
    }
}
