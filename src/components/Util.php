<?php

namespace ripaym1970\autocrud\components;

//use ripaym1970\autocrud\components\ActiveField;
//use ripaym1970\autocrud\components\behaviors;
//use ripaym1970\autocrud\components\interfaces;
//use NumberFormatter;
use ReflectionClass;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\UserException;
use yii\console\Application as ConsoleApplication;
use yii\db\ActiveRecord;
use yii\db\ColumnSchema;
use yii\db\Expression;
use yii\db\StaleObjectException;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\View;

//use yii\bootstrap5\ActiveForm;
//use yii\log\Target;

class Util
{
    public static array $horizontalFormCss = [
        'label'   => ['col-md-3', 'col-form-label', 'text-right'],
        'wrapper' => 'col-md-9',
    ];

    public static function beginTypicalForm(array $options = []): ActiveForm
    {
        return \ripaym1970\autocrud\components\ActiveForm2::begin(
            ArrayHelper::merge(
                [
                    'fieldClass'  => \ripaym1970\autocrud\components\ActiveField2::class,
                    'fieldConfig' => [
                        'errorOptions' => [
                            'encode' => false,
                        ],
                        'hintOptions'  => [
                            'class' => ['w-100'],
                        ],
                    ],
                ],
                $options,
            ),
        );
    }

    public static function beginTypicalHorizontalForm(array $options = []): ActiveForm
    {
        return self::beginTypicalForm(
            ArrayHelper::merge(
                [
                    'layout'             => 'horizontal',
                    'encodeErrorSummary' => false,
                    'fieldConfig'        => [
                        'horizontalCssClasses' => self::$horizontalFormCss,
                    ],
                ],
                $options,
            ),
        );
    }

    public static function beginTypicalInlineForm(array $options = []): ActiveForm
    {
        return self::beginTypicalForm(
            ArrayHelper::merge(
                [
                    'layout'      => 'inline',
                    'fieldConfig' => [
                        'labelOptions' => [
                            'class' => [
                                'sr-label pr-2',
                            ],
                        ],
                    ],
                ],
                $options,
            ),
        );
    }

    public static function isInConsoleMode(): bool
    {
        return Yii::$app instanceof ConsoleApplication
            || PHP_SAPI == 'cli';
    }

    /**
     * @throws \yii\console\Exception
     * @throws NotFoundHttpException
     */
    //public static function isConsoleMigrateCommand(): bool
    //{
    //    if (!self::isInConsoleMode()) {
    //        return false;
    //    }
    //
    //    $controller = Yii::$app->controller;
    //
    //    if ($controller && $controller->action->id == 'migrate') {
    //        return true;
    //    }
    //
    //    $route = Yii::$app->request->resolve()[0] ?? '';
    //    return $route
    //        && explode('/', $route)[0] == 'migrate';
    //}

    public static function strToLower($string)
    {
        return mb_strtolower($string, Yii::$app->charset);
    }

    public static function logException(Throwable $e, $message)
    {
        Yii::error(
            $message
            . $e->getMessage() . PHP_EOL
            . "code:" . $e->getCode() . PHP_EOL
            . "file:" . $e->getFile() . PHP_EOL
            . "line:" . $e->getLine() . PHP_EOL
            . "trace: " . PHP_EOL
            . $e->getTraceAsString() . PHP_EOL,
        );
    }

    /**
     * @throws \Yii\base\Exception
     */
    public static function makeTransaction($failIfAlreadyStarted = false): ?Transaction
    {
        $transaction = Yii::$app->db->transaction;

        if ($transaction && $failIfAlreadyStarted) {
            throw new \Yii\base\Exception('Transaction already started');
        }

        return $transaction
            ? null
            : Yii::$app->db->beginTransaction();
    }

    /**
     * @link http://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size
     */
    public static function parseSize(string $size): float
    {
        // Remove the non-unit characters from the size.
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        // Remove the non-numeric characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size);

        // Find the position of the unit in the ordered string
        // which is the power of magnitude to multiply a kilobyte by.
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    public static function distinctValues(string $className, string $field, array $conditions): array
    {
        /** @var ActiveRecord $className */
        $items = $className::find()
            ->select($field)
            ->groupBy(new Expression("1"))
            ->orderBy($field);

        if ($conditions) {
            $items->andWhere($conditions);
        }

        return ArrayHelper::getColumn(
            $items->all(),
            $field,
        );
    }

    public static function defaultHeaderOptions(array $options = []): array
    {
        return ArrayHelper::merge(
            [
                'class' => [
                    'text-center',
                    'bg-primary-500',
                    'bg-success-gradient',
                ],
            ],
            $options,
        );
    }

    public static function defaultDetailViewOptions(array $options = []): array
    {
        return ArrayHelper::merge(
            [
                'class' => [
                    'table',
                    'table-hover',
                    'table-striped',
                    'table-bordered',
                    'table-sm',
                ],
            ],
            $options,
        );
    }

    public static function reduceSlashes($str)
    {
        return preg_replace('#(?<!:)//+#', '/', $str);
    }

    public static function buildUrl($url, $uri, array $data = [])
    {
        $parsed = parse_url($url);
        $url = [
            $parsed['scheme'] ?? 'http',
            '://',
            $parsed['host'] ?? null,
            '/',
            $parsed['path'] ?? null,
            $uri,
            $data
                ? '?' . http_build_query($data)
                : '',
        ];
        return self::reduceSlashes(implode('', $url));
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function hasBehavior(
        Component $object,
        string $behaviorClassName,
        bool $throwException
    ): bool {
        foreach ($object->behaviors as $name => $value) {
            if (is_object($name)) {
                $class = $name;
            } elseif (is_object($value)) {
                $class = get_class($value);
            } elseif ($value['class'] ?? null) {
                $class = $value['class'];
            } elseif (is_string($value)) {
                $class = $value;
            } else {
                throw new \Yii\base\Exception(
                    "Could not parse behaviors",
                );
            }

            if ($class == $behaviorClassName) {
                return true;
            }
        }

        if (!$throwException) {
            return false;
        }

        throw new \Yii\base\InvalidConfigException(
            Yii::t(
                "app",
                "Component {componentName} has no behaviour {behaviourName}",
                [
                    'componentName' => get_class($object),
                    'behaviourName' => $behaviorClassName,
                ],
            ),
        );
    }

    public static function compareMoney($a, $b): bool
    {
        return abs($a - $b) < 0.0001;
    }

    public static function getMonthsList(): array
    {
        // in yii1 we could fetch localized list, have no idea (and no time) how to do it in yii2
        return [
            1  => Yii::t('app', 'January'),
            2  => Yii::t('app', 'February'),
            3  => Yii::t('app', "March"),
            4  => Yii::t('app', 'April'),
            5  => Yii::t('app', 'May'),
            6  => Yii::t('app', 'June'),
            7  => Yii::t('app', 'July'),
            8  => Yii::t('app', 'August'),
            9  => Yii::t('app', 'September'),
            10 => Yii::t('app', 'October'),
            11 => Yii::t('app', 'November'),
            12 => Yii::t('app', 'December'),
        ];
    }

    public static function getYearsList(): array
    {
        $list = range(
            intval(date('Y')),
            intval(date("Y")) + 10,
        );
        return array_combine($list, $list);
    }

    public static function daysInMonth($year, $month): int
    {
        return cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    /**
     * Creates the pairs from plain list, i.e.
     *
     * @throws Exception
     * @example source data = [a, b, c,d, ...] returns: [a => b, c => d , ...]
     */
    public static function makeAssociative(array $list): array
    {
        $result = [];
        if (count($list) % 2) {
            throw new \Yii\base\Exception(
                "this won't work with odd elements count",
            );
        }
        foreach (range(0, count($list) - 1, 2) as $index) {
            $result[$list[$index]] = $list[$index + 1];
        }
        return $result;
    }

    /**
     * Returns true if array is associative
     *
     * @link  https://stackoverflow.com/questions/5431766/how-to-check-if-array-is-list
     * @todo  Should be deprecated after upgrading to php version >= 8.1.0)
     * @todo  and substituted with native array_is_list function.
     */
    public static function isAssoc(array $a): bool
    {
        return array_values($a) !== $a;
    }

    public static function isColumnPresent($tableName, $columnName): bool
    {
        return (bool) static::getColumn($tableName, $columnName);
    }

    public static function getColumn($tableName, $columnName): ?ColumnSchema
    {
        return Yii::$app->db->schema
            ->getTableSchema($tableName)
            ->getColumn($columnName);
    }

    public static function isTableExists($tableName): bool
    {
        return (bool) Yii::$app->db->schema->getTableSchema(
            $tableName,
            true,
        );
    }

    /**
     * @throws ReflectionException
     * @throws InvalidConfigException
     */
    public static function implements($classOrObject, $interfaceName, bool $throwException): bool
    {
        $result = is_string($classOrObject)
            ? (new ReflectionClass($classOrObject))->implementsInterface($interfaceName)
            : $classOrObject instanceof $interfaceName;

        if ($result || !$throwException) {
            return $result;
        }
        throw new \Yii\base\InvalidConfigException(
            Yii::t(
                "app",
                "{objectName} must implement {interfaceName}",
                [
                    'objectName'    => is_string($classOrObject)
                        ? $classOrObject
                        : get_class($classOrObject),
                    'interfaceName' => $interfaceName,
                ],
            ),
        );
    }

    /**
     * @link https://stackoverflow.com/questions/14773072/php-str-pad-unicode-issue
     */
    public static function strPadUnicode($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT): ?string
    {
        $str_len = mb_strlen($str);
        $pad_str_len = mb_strlen($pad_str);
        if (!$str_len && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
            $str_len = 1; // @debug
        }
        if (!$pad_len || !$pad_str_len || $pad_len <= $str_len) {
            return $str;
        }

        $result = null;
        if ($dir == STR_PAD_BOTH) {
            $length = ($pad_len - $str_len) / 2;
            $repeat = ceil($length / $pad_str_len);
            $result = mb_substr(str_repeat($pad_str, $repeat), 0, floor($length))
                . $str
                . mb_substr(str_repeat($pad_str, $repeat), 0, ceil($length));
        } else {
            $repeat = ceil($str_len - $pad_str_len + $pad_len);
            if ($dir == STR_PAD_RIGHT) {
                $result = $str . str_repeat($pad_str, $repeat);
                $result = mb_substr($result, 0, $pad_len);
            } elseif ($dir == STR_PAD_LEFT) {
                $result = str_repeat($pad_str, $repeat);
                $result = mb_substr(
                        $result,
                        0,
                        $pad_len - (($str_len - $pad_str_len) + $pad_str_len),
                    )
                    . $str;
            }
        }

        return $result;
    }

    public static function daysOfWeek(): array
    {
        return [
            1 => Yii::t('app', "Monday"),
            2 => Yii::t('app', "Tuesday"),
            3 => Yii::t('app', "Wednesday"),
            4 => Yii::t('app', "Thursday"),
            5 => Yii::t('app', "Friday"),
            6 => Yii::t('app', "Saturday"),
            0 => Yii::t('app', "Sunday"),
        ];
    }

    public static function runUnderDefaultLocale(callable $callable)
    {
        $locale = Yii::$app->formatter->locale;
        Yii::$app->formatter->locale = Yii::$app->sourceLanguage;
        call_user_func($callable);
        Yii::$app->formatter->locale = $locale;
    }

    public static function noContent()
    {
        Yii::$app->response->statusCode = 204;
    }

    //public static function getCurrencySymbol()
    //{
    //    $locale = Yii::$app->formatter->locale;
    //    $currencyCode = Yii::$app->formatter->currencyCode
    //        ?: "USD";
    //
    //    $locale .= "@currency=" . $currencyCode;
    //    $formatter = new NumberFormatter(
    //        $locale,
    //        NumberFormatter::CURRENCY
    //    );
    //    return $formatter->getSymbol($formatter::CURRENCY_SYMBOL);
    //}

    /**
     * Generates maskVars log target property for secure classes
     *
     * @throws ReflectionException
     * @throws InvalidConfigException
     */
    //public static function getSecureVariables($classList): array
    //{
    //    $reflection = new ReflectionClass(Target::class);
    //    $secureVariables = $reflection->getDefaultProperties()['maskVars'];
    //    foreach ($classList as $modelClass) {
    //        self::implements(
    //            $modelClass,
    //            interfaces\IDataProtectionProvider::class,
    //            true
    //        );
    //
    //        /** @var interfaces\IDataProtectionProvider $modelClass */
    //        $fields = $modelClass::getSecureFields();
    //        $shortClass = self::getShortClassName($modelClass);
    //        foreach ($fields as $field) {
    //            foreach (['_GET', '_POST'] as $method) {
    //                $secureVariables [] = $method . '.' . $field;
    //                $secureVariables [] = $method . '.' . $shortClass . '.' . $field;
    //            }
    //        }
    //    }
    //    return $secureVariables;
    //}

    /**
     * Convert concentration unit mgdl to mmol
     *
     * @param  float|int  $value
     */
    public static function convertMgdlToMmol($value): float
    {
        return round($value * 0.0555, 1);
    }

    /**
     * Convert temperature unit celsius (c) to fahrenheit (f)
     *
     * @param $value
     *
     * @return float|int
     */
    public static function convertCelsiusToFahrenheit($value)
    {
        return $value * 9 / 5 + 32;
    }

    /**
     * Convert weight unit gram (gm) to pound (lb)
     *
     * @param  float|int  $value
     */
    public static function convertGmToLb($value): float
    {
        return $value * 2.2046 / 1000;
    }

    /**
     * Convert pressure unit pascal (pa) to "millimetres of mercury" (mmhg)
     *
     * @param  float|int  $value
     */
    public static function convertPaToMmhg($value): float
    {
        return round($value * 0.0075006);
    }

    /**
     * Returns distance between geo points
     *
     * @link https://www.codexworld.com/distance-between-two-addresses-google-maps-api-php/
     */
    public static function distance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo): float
    {
        $theta = $longitudeFrom - $longitudeTo;
        $dist = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo))
            + cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344;
    }

    public static function setMetaParameters(View $view)
    {
        $view->registerMetaTag([
            'name'    => 'viewport',
            'content' => 'width=device-width, height=device-height, initial-scale=1',
        ]);
        $view->registerMetaTag([
            'http-equiv' => 'content-type',
            'content'    => 'text/html; charset=' . Yii::$app->charset,
        ]);
        $view->registerMetaTag([
            'charset' => Yii::$app->charset,
        ]);
    }

    /**
     * @param  ActiveRecord  $model
     *
     * @return string
     */
    public static function dumpModel(ActiveRecord $model): string
    {
        return implode(
            PHP_EOL,
            array_filter([
                get_class($model),
                print_r($model->errors, true),
                YII_DEBUG || self::isInConsoleMode()
                    ? "Model attributes:"
                    : '',
                YII_DEBUG || self::isInConsoleMode()
                    ? print_r($model->attributes, true)
                    : '',
            ]),
        );
    }

    /**
     * @param  ActiveRecord  $model
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws StaleObjectException
     * @throws UserException
     * @throws Throwable
     * @throws \yii\db\Exception
     */
    public static function deleteCollection(array $models)
    {
        $transaction = self::makeTransaction();
        foreach ($models as $model) {
            self::deleteModel($model);
        }
        $transaction && $transaction->commit();
    }

    /**
     * @param  ActiveRecord  $model
     *
     * @return mixed|true
     * @throws Exception
     * @throws StaleObjectException
     * @throws Throwable
     * @throws UserException
     */
    public static function deleteModel(ActiveRecord $model)
    {
        return $model->delete()
            ? true
            : self::throwModelException('Could not delete model', $model);
    }

    /**
     * @param  ActiveRecord  $model
     *
     * @return mixed|ActiveRecord
     * @throws Exception
     * @throws UserException
     */
    public static function saveModel(ActiveRecord $model)
    {
        return $model->save()
            ? $model
            : self::throwModelException('Could not save model', $model);
    }

    /**
     * @param  string  $class       \ripaym1970\autocrud\models\Customer::class
     * @param  array   $condition
     *
     * @return ActiveRecord
     * @throws NotFoundHttpException
     */
    public static function findModel(string $class, array $condition): ActiveRecord
    {
        /** @var ActiveRecord $class */
        return $class::findOne($condition)
            ?? throw new NotFoundHttpException(Yiit::t('Could not find model'));
    }

    /**
     * @param  ActiveRecord  $model
     * @param  array         $attributes
     *
     * @return ActiveRecord
     * @throws InvalidConfigException
     */
    public static function cloneModel(ActiveRecord $model, array $attributes): ActiveRecord {
        /** @var ActiveRecord $newModel */
        $newModel = Yii::createObject(get_class($model));

        foreach ($attributes as $attribute) {
            $newModel->$attribute = $model->$attribute;
            // hack for json fields, do not put there '[]'
            if (is_array($newModel->$attribute) && !$newModel->$attribute) {
                $newModel->$attribute = new Expression(
                    "'{}'::json",
                );
            }
        }

        return $newModel;
    }

    /**
     * Prepares and throws exception for specified model.
     *
     * @param  string        $message
     * @param  ActiveRecord  $model
     *
     * @return mixed
     * @throws Exception
     * @throws UserException
     */
    public static function throwModelException(string $message, ActiveRecord $model)
    {
        $detailedMessage = $message
            . ': '
            . self::dumpModel($model);

        Yii::error($detailedMessage);

        if (YII_ENV_DEV || YII_DEBUG) {
            throw new \yii\base\UserException($detailedMessage);
        }
        throw new \yii\base\Exception($message);
    }

    /**
     * @param  ActiveRecord  $model
     * @param                $sourceValue
     * @param  string        $fieldName
     *
     * @return mixed|null
     */
    public static function generateClonedField(ActiveRecord $model, $sourceValue, string $fieldName) {
        $model->$fieldName = $sourceValue;
        while (!$model->validate([$fieldName])) {
            $model->$fieldName = $model->$fieldName . Yiit::t("(copy)");
        }
        return $model->$fieldName;
    }

    /**
     * @param  ActiveRecord  $model
     * @param  array         $attributes
     *
     * @return ActiveRecord
     */
    public static function setModelAttributes(ActiveRecord $model, array $attributes): ActiveRecord
    {
        foreach ($attributes as $key => $value) {
            $model->$key = $value;
        }
        return $model;
    }

    /**
     * @param  Model  $model
     * @param  array  $fields
     * @param  bool   $strictNames
     *
     * @return Model
     * @throws ReflectionException
     */
    public static function fillModelFields(Model $model, array $fields, bool $strictNames = false): Model
    {
        $shortName = self::getShortClassName($model);
        foreach ($fields as $field) {
            $value = $_REQUEST[$shortName][$field]
                ?? null;

            if (is_null($value) && !$strictNames) {
                $value = $_REQUEST[$field]
                    ?? null;
            }

            if (!is_null($value)) {
                $model->$field = $value;
            }
        }

        return $model;
    }

    /**
     * @param object $object
     *
     * @return string
     * @throws ReflectionException
     */
    public static function getShortClassName(object $object): string
    {
        return (new ReflectionClass($object))->getShortName();
    }
}
