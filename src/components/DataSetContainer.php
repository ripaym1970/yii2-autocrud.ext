<?php

namespace ripaym1970\autocrud\components;

/**
 * general logic class to handle grid and kendo UI filter component
 *
 * @property array $dataSourceDescription
 */
class DataSetContainer extends \yii\base\Component
{
    // tree or grid, self::TREE_REPRESENTATION or self::GRID_REPRESENTATION
    public $representation;

    public const TREE_REPRESENTATION = 1;
    public const GRID_REPRESENTATION = 2;

    protected const AVAILABLE_REPRESENTATIONS = [
        self::TREE_REPRESENTATION,
        self::GRID_REPRESENTATION,
    ];
    /**
     * classMap => (optional) class name for source model
     * associative array of related models to join and publish in the grid. Format is
     *  - 'relationName' => 'relationClass'
     *  - first element, source model, should have only class name, without relation
     */
    public $classMap = [];

    /**
     * @var callable|null $modelConfig
     * optional, callable which receives
     * string $className, string
     * array $config, which describes all sections for grid/tree columns for given $class
     * take $config by reference and modify if needed
     * return value is ignored
     */
    public $modelConfig;

    // this should cache all ::gridConfig results
    protected $_configCache = [];

    /**
     * @var callable|null $beforeFilter
     * specify this callback if you want to do custom filtering. callback params are:
     *  - string $field
     *  - string $value
     *  - string $operator
     *  \yii\db\ActiveQuery $query (take it by ref and modify)
     *  return true to stop "standard" filter processing (for given field/value/operator)
     */
    public $beforeFilter;


    protected const FILTER_DATE_LESS_THAN_DAYS = 'ltEqDay';
    protected const FILTER_DATE_LESS_THAN_WEEKS = 'ltEqWeek';
    protected const FILTER_DATE_LESS_THAN_MONTHS = 'ltEqMonth';
    protected const FILTER_DATE_LESS_THAN_YEARS = 'ltEqYear';
    protected const FILTER_DATE_PREVIOUS_MONTH = 'previousMonth';
    protected const FILTER_DATE_GREATER_THAN_DAYS = 'gtEqDay';
    protected const FILTER_DATE_GREATER_THAN_WEEKS = 'gtEqWeek';
    protected const FILTER_DATE_GREATER_THAN_MONTHS = 'gtEqMonth';
    protected const FILTER_DATE_GREATER_THAN_YEARS = 'gtEqYear';

    protected const CUSTOM_DATE_FILTERS = [
        self::FILTER_DATE_LESS_THAN_DAYS,
        self::FILTER_DATE_LESS_THAN_WEEKS,
        self::FILTER_DATE_LESS_THAN_MONTHS,
        self::FILTER_DATE_LESS_THAN_YEARS,
        self::FILTER_DATE_PREVIOUS_MONTH,
        self::FILTER_DATE_GREATER_THAN_DAYS,
        self::FILTER_DATE_GREATER_THAN_WEEKS,
        self::FILTER_DATE_GREATER_THAN_MONTHS,
        self::FILTER_DATE_GREATER_THAN_YEARS,
    ];

    public static function reformatFieldName(\yii\db\ActiveRecord $model, $fieldName)
    {
        return \ripaym1970\autocrud\components\Util::getShortClassName($model)
            . '__'
            . $fieldName;
    }

    public function getConfigForClass($className): array
    {
        if (isset($this->_configCache[$className])) {
            return $this->_configCache[$className];
        }

        $config = method_exists($className, 'gridConfig')
            ? $className::gridConfig()
            : [];

        // syntax for call is intentionnally made the way when callback can change $config
        if (is_callable($this->modelConfig)) {
            ($this->modelConfig)($className, $config);
        }
        $this->_configCache[$className] = $config;
        return $this->_configCache[$className];
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function processCondition(array $condition, \yii\db\Query $query)
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'] ?? '';
        [$filterClass, $filterField] = explode('__', $field);

        $paramName = ':value_' . uniqid();

        $processed = false;

        foreach ($this->classMap as $relation => $className) {
            $shortClass = \ripaym1970\autocrud\components\Util::getShortClassName($className);
            if ($shortClass != $filterClass) {
                continue;
            }

            if (is_callable($this->beforeFilter)) {
                $result = ($this->beforeFilter)(
                    $filterField,
                    $value,
                    $operator,
                    $query
                );
                if ($result) {
                    $processed = true;
                    break;
                }
            }

            $tableName = $className::tableName();
            $tableWithField = $tableName . "." . $filterField;
            $valid = \ripaym1970\autocrud\components\Util::isColumnPresent(
                $tableName,
                $filterField
            );
            if (!$valid) {
                throw new \yii\base\InvalidConfigException(
                    \Yii::t(
                        "app",
                        "Table {table} does not have column {column}",
                        [
                            'table' => $tableName,
                            'column' => $filterField
                        ]
                    )
                );
            }

            $isCustomDateOperator = in_array($operator, self::CUSTOM_DATE_FILTERS);
            if ($isCustomDateOperator) {
                if ($operator == self::FILTER_DATE_PREVIOUS_MONTH) {
                    return new \yii\db\Expression(
                        "date(" . $tableWithField . ") >= date_trunc('month', current_date - interval '1' month)
                        and date(" . $tableWithField . ") < date_trunc('month', current_date)
                        "
                    );
                }

                $operatorsMap = [
                    self::FILTER_DATE_LESS_THAN_DAYS => ['>=', 'days'],
                    self::FILTER_DATE_LESS_THAN_WEEKS => ['>=', 'weeks'],
                    self::FILTER_DATE_LESS_THAN_MONTHS => ['>=', 'months'],
                    self::FILTER_DATE_LESS_THAN_YEARS => ['>=', 'years'],
                    self::FILTER_DATE_GREATER_THAN_DAYS => ['<=', 'days'],
                    self::FILTER_DATE_GREATER_THAN_WEEKS => ['<=', 'weeks'],
                    self::FILTER_DATE_GREATER_THAN_MONTHS => ['<=', 'months'],
                    self::FILTER_DATE_GREATER_THAN_YEARS => ['<=', 'years'],
                ];
                list($localOperator, $period) = $operatorsMap[$operator];
                return [
                    $localOperator,
                    new \yii\db\Expression($tableWithField . "::date "),
                    new \yii\db\Expression(
                        " current_date - " . $paramName . '::interval',
                        [
                            $paramName => addslashes($value . ' ' . $period)
                        ]
                    )
                ];
            }

            $isDateOrDateTime = in_array(
                \ripaym1970\autocrud\components\Util::getColumn(
                    $tableName,
                    $filterField
                )->type,
                ['date', 'datetime', 'timestamp']
            );
            $dateTimeOperators = [
                'eq',
                'gte',
                'gt',
                'lte',
                'lt',
                'neq',
            ];
            if ($isDateOrDateTime && in_array($operator, $dateTimeOperators)) {
                $tableWithField = new \yii\db\Expression(
                    "date(" . $tableWithField . ')'
                );
                $value = new \yii\db\Expression('date(' . $paramName . ')', [
                    $paramName => $value
                ]);
            }

            switch ($operator) {
                case 'eq':
                    return ['=', $tableWithField, $value];
                case 'gte':
                    return [">=", $tableWithField, $value];
                case 'gt':
                    return [">", $tableWithField, $value];
                case 'lte':
                    return ["<=", $tableWithField, $value];
                case 'lt':
                    return ["<", $tableWithField, $value];
                case 'neq':
                    return ["<>", $tableWithField, $value];
                case 'startswith':
                    $query->addParams([$paramName => addslashes($value)]);
                    return $tableWithField
                        . '::text ilike '
                        . $paramName
                        . ' || \'%\'';
                case 'contains':
                    $query->addParams([$paramName => addslashes($value)]);
                    return $tableWithField
                        . '::text ilike \'%\' || '
                        . $paramName
                        . ' || \'%\'';
                case 'doesnotcontain':
                    $query->addParams([$paramName => addslashes($value)]);
                    return $tableWithField
                        . '::text not ilike \'%\' || '
                        . $paramName
                        . ' || \'%\'';
                case 'endswith':
                    $query->addParams([$paramName => addslashes($value)]);
                    return $tableWithField
                        . '::text ilike \'%\' || '
                        . $paramName;
                case 'isnull':
                    return [$tableWithField => null];
                case 'isnotnull':
                    return ['is not', $tableWithField, null];
                case 'isempty':
                    return $tableWithField . "::text = ''";
                case 'isnotempty':
                    return $tableWithField . "::text <> ''";
                case 'isnullorempty':
                    return [
                        'OR',
                        [$tableWithField => null],
                        $tableWithField . "::text = ''"
                    ];
                case 'isnotnullorempty':
                    return [
                        'OR',
                        ['is not', $tableWithField, null],
                        $tableWithField . "::text <> ''"
                    ];
                case 'currentDay':
                    return $tableWithField . '::date = current_date';
                case 'currentWeek':
                    return "extract(week from " . $tableWithField . ') = extract(week from current_date) and '
                        . "extract(isoyear from " . $tableWithField . ') = extract(isoyear from current_date)';
                case 'currentMonth':
                    return "extract(month from " . $tableWithField . ') = extract(month from current_date) and '
                        . "extract(year from " . $tableWithField . ') = extract(year from current_date)';
                case 'currentYear':
                    return "extract(year from " . $tableWithField . ') = extract(year from current_date)';
                default:
                    throw new \yii\base\UserException(
                        "Could not determine filter type '"
                        . $operator
                        . "'"
                    );
            }
        }
        if (!$processed) {
            throw new \yii\base\Exception(
                "Could not parse condition:"
                . print_r($condition, true)
            );
        }
    }

    public function parseFilters(\yii\db\Query $query, array $filters)
    {
        if (!$filters) {
            return;
        }
        $iterator = function (\yii\db\Query &$query, array $filters) use (&$iterator) {
            $logic = $filters['logic'] ?? null;
            if (!$logic) {
                return $this->processCondition($filters, $query);
            }
            $conditions = [$logic];
            $filters = $filters['filters'] ?? [];
            foreach ($filters as $element) {
                $conditions[] = $iterator($query, $element);
            }
            return array_filter($conditions);
        };

        $conditions = $iterator($query, $filters);
        $query->andWhere(
            $conditions
        );
    }


    public function getDataSourceDescription(): array
    {
        $description = [];
        $idField = 'id';
        foreach ($this->classMap as $relation => $className) {
            $gridConfig = $this->getConfigForClass($className);

            $originalColumnsConfig = $gridConfig['columns'] ?? [];

            /** @var \yii\db\ActiveRecord $model */
            $model = new $className();

            if (!$description) {
                $description = [
                    'id' => self::reformatFieldName($model, $model::primaryKey()[0]),
                    'fields' => []
                ];
                if ($this->representation == self::TREE_REPRESENTATION) {
                    $description = \yii\helpers\ArrayHelper::merge(
                        $description,
                        [
                            'fields' => [
                                'parentId' => [
                                    'field' => self::reformatFieldName(
                                        $model,
                                        $this->parentIdField
                                    ),
                                    'nullable' => true,
                                    'type' => 'number',
                                ]
                            ]
                        ]
                    );
                }
            }

            foreach ($originalColumnsConfig as $content) {
                if ($relation && !($content['includeInParent'] ?? true)) {
                    continue;
                }

                if (!$relation && explode('__', $content['field'])[1] == 'id') {
                    $idField = $content['field'];
                }

                if (
                    $this->representation == self::TREE_REPRESENTATION &&
                    $content['field'] == self::reformatFieldName(
                        $model,
                        $this->parentIdField
                    )
                ) {
                    continue;
                }

                $description['fields'][$content['field']] = [
                    'type' => $content['type'] ?? 'string',
                    'editable' => $content['editable'] ?? false,
                ];
            }
        }

        $description['id'] = $idField;
        return $description;
    }
}
