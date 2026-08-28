<?php

use ripaym1970\autocrud\components\Yiit;
use yii\db\DataReader;
use yii\db\Exception;
use yii\db\Query;
use yii\helpers\VarDumper;

if (!function_exists('str_replace_once')) {
    function str_replace_once($search, $replace, $text)
    {
        $pos = strpos($text, $search);
        return $pos !== false
            ? substr_replace($text, $replace, $pos, strlen($search))
            : $text;
    }
}

if (!function_exists('dg')) {
    function dg($data, $data_name = '$data')
    {
        if (isset($_GET['_dbg'])) {
            $tmp_var = debug_backtrace(1);
            $caller = array_shift($tmp_var);

            error_reporting(-1);
            //header('Content-Type: text/html; charset=utf-8');

            echo '<code>File: ' . $caller['file'] . ' ' . $caller['line'] . '</code>';
            echo '<pre>';
            echo $data_name . '=', PHP_EOL;
            \yii\helpers\VarDumper::dump($data, 10, true);
            echo '</pre>';
        }
    }
}

if (!function_exists('d')) {
    /**
     * Debug function
     *
     * @param mixed $data
     * @param string $data_name
     */
    function d(...$data)
    {
        $tmp_var = debug_backtrace(1);
        $caller = array_shift($tmp_var);

        error_reporting(-1);

        echo '<code>File: ' . $caller['file'] . '  ' . $caller['line'] . '</code>';
        echo '<pre>';

        if (is_array($data) && count($data) == 1) {
            VarDumper::dump($data[0], 10, true);
        } else {
            VarDumper::dump($data, 10, true);
        }

        echo '</pre>';
    }
}

if (!function_exists('dd')) {
    /**
     * Debug function with die() after
     *
     * @param        $data
     */
    function dd(...$data)
    {
        $tmp_var = debug_backtrace(1);
        $caller = array_shift($tmp_var);

        error_reporting(-1);
        //header('Content-Type: text/html; charset=utf-8');

        echo '<code>File: ' . $caller['file'] . ' ' . $caller['line'] . '</code>';
        echo '<pre>';
        //echo '$data_name' . '=', PHP_EOL;
        VarDumper::dump($data, 10, true);
        echo '</pre>';

        die();
    }
}

if (!function_exists('numberFormat')) {
    function numberFormat($num, $_with_plus = false)
    {
        $res = number_format($num, 0, '.', '&nbsp;');
        if ($_with_plus && $num > 0) {
            $res = '+' . $res;
        }

        return $res;
    }
}

if (!function_exists('floatFormat')) {
    function floatFormat($num)
    {
        return number_format($num, 2, '.', '&nbsp;');
    }
}

//https://gist.github.com/artoodetoo/0379133f2c9fe88407ff
//function array_usearch($needle, $haystack, $callback)
//{
//    foreach ($haystack as $key => $value) {
//        if (call_user_func($callback, $needle, $value) !== false) {
//            return $key;
//        }
//    }
//    return false;
//}

if (!function_exists('unique_multidim_array')) {
    /**
     * @param $array
     * @param $key
     *
     * @return array
     */
    function unique_multidim_array($array, $key)
    {
        $temp_array = [];
        $i = 0;
        $key_array = [];

        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }
}

///**
// * Fork public function asRelativeTime(){} vendor/yiisoft/yii2/i18n/Formatter.php
// *
// * TODO: Надо бы более точнее сделать вывод, а то почти "два месяца", а пишет "месяц назад"
// *
// * @param      $value
// * @param null $referenceTime
// *
// * @return string
// * @throws Exception
// */
//function relativeTime($value, $referenceTime = null)
//{
//    if ($value === null) {
//        return Yii::$app->formatter->nullDisplay;
//    }
//
//    if ($value instanceof DateInterval) {
//        $interval = $value;
//    } else {
//        $timestamp = Yii::$app->formatter->normalizeDatetimeValue($value);
//        $timeZone = new DateTimeZone(Yii::$app->formatter->timeZone);
//
//        if ($referenceTime === null) {
//            $dateNow = new DateTime('now', $timeZone);
//        } else {
//            $dateNow = Yii::$app->formatter->normalizeDatetimeValue($referenceTime);
//            $dateNow->setTimezone($timeZone);
//        }
//
//        $dateThen = $timestamp->setTimezone($timeZone);
//        $interval = $dateThen->diff($dateNow);
//    }
//
//    $language = Yii::$app->formatter->language;
//
//    if ($interval->invert) {
//        if ($interval->y >= 1) {
//            return Yii::t('yii', 'in {delta, plural, =1{a year} other{# years}}', ['delta' => $interval->y], $language);
//        }
//        if ($interval->m >= 1) {
//            return Yii::t('yii', 'in {delta, plural, =1{a month} other{# months}}', ['delta' => $interval->m], $language);
//        }
//        if ($interval->d >= 1) {
//            return Yii::t('yii', 'in {delta, plural, =1{a day} other{# days}}', ['delta' => $interval->d], $language);
//        }
//        if ($interval->h >= 1) {
//            return Yii::t('yii', 'in {delta, plural, =1{an hour} other{# hours}}', ['delta' => $interval->h], $language);
//        }
//        if ($interval->i >= 1) {
//            return Yii::t('yii', 'in {delta, plural, =1{a minute} other{# minutes}}', ['delta' => $interval->i], $language);
//        }
//        if ($interval->s == 0) {
//            return Yii::t('yii', 'just now', [], $language);
//        }
//
//        return Yii::t('yii', 'in {delta, plural, =1{a second} other{# seconds}}', ['delta' => $interval->s], $language);
//    }
//
//    if ($interval->y >= 1) {
//        return Yii::t('yii', '{delta, plural, =1{a year} other{# years}} ago', ['delta' => $interval->y], $language);
//    }
//    if ($interval->m >= 1) {
//        return Yii::t('yii', '{delta, plural, =1{a month} other{# months}} ago', ['delta' => $interval->m], $language);
//    }
//    if ($interval->d >= 1) {
//        return Yii::t('yii', '{delta, plural, =1{a day} other{# days}} ago', ['delta' => $interval->d], $language);
//    }
//    if ($interval->h >= 1) {
//        return Yii::t('yii', '{delta, plural, =1{an hour} other{# hours}} ago', ['delta' => $interval->h], $language);
//    }
//    if ($interval->i >= 1) {
//        return Yii::t('yii', '{delta, plural, =1{a minute} other{# minutes}} ago', ['delta' => $interval->i], $language);
//    }
//    if ($interval->s == 0) {
//        return Yii::t('yii', 'just now', [], $language);
//    }
//
//    return Yii::t('yii', '{delta, plural, =1{a second} other{# seconds}} ago', ['delta' => $interval->s], $language);
//}

if (!function_exists('relativeDate')) {
    function relativeDate($value)
    {
        $now = time();
        $last_midnight = $now - ($now % (24 * 60 * 60));
        if ($value >= $last_midnight) {
            return Yiit::t('Сьогодні');
        }

        if ($value >= ($last_midnight - (24 * 60 * 60))) {
            return Yiit::t('Вчора');
        }

        return date('d F Y', $value);
    }
}

if (!function_exists('varexport')) {
    /**
     *  echo '<pre>';varexport($this->characters, '$this->characters');echo '</pre>';exit();
     * @param $expression
     * @param $name
     * @param $return
     *
     * @return array|string|string[]|void|null
     */
    function varexport($expression, $name = '', $return = false)
    {
        echo $name . ' = ';
        $export = var_export($expression, true);
        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];

        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);
        if ((bool)$return) {
            return $export;
        }
        echo $export;
    }
}

if (!function_exists('varToString')) {
    function varToString($var, $emptyText = '', $prefix = '', $postfix = '')
    {
        if (empty($var)) {
            return $emptyText;
        }

        return str_replace('%value%', $var, $prefix . (empty($prefix) || !empty($postfix) ? '%value%' : '') . $postfix);
    }
}

if (!function_exists('normalizeHtml')) {
    function normalizeHtml($html)
    {
        $html = preg_replace('/<h1[^>]*>(.*)<\/h1>/imsU', '<h2 class="titles-h2 mt25 mb25">$1</h2>', $html);
        $html = preg_replace('/<h2[^>]*>/imsU', '<h2 class="titles-h2 mt25 mb25">', $html);
        $html = preg_replace('/<p[^>]*>/imsU', '<p class="content-p mb5">', $html);
        $html = preg_replace('/\s*style="[^"]+"/imsU', '', $html);
        //$html = str_replace('\n', ', ', $html);

        return $html;
    }
}

if (!function_exists('crop_text')) {
    /**
     * Обрезает текст с русскими и латинскими словами до нужной длины
     *
     * @param $text
     * @param $num
     *
     * @return string
     */
    function crop_text($text, $num)
    {
        $text = strip_tags($text); #удаляем все html теги
        $text = str_replace('&amp;nbsp;', ' ', $text); #заменяем &amp;nbsp; на пробел
        $text = iconv('utf-8', 'windows-1251', $text); #кодируем в win-1251
        if (strlen($text) > $num) {
            // strlen считает кол-во символов и, если оно больше указанного в переменной $num, то режем ее
            $text = substr($text, 0, $num); #функция substr режет текст от 0 до кол-ва символов в переменной $num
            $text = iconv('windows-1251', 'utf-8', $text); #кодируем обратно в utf-8
            $crop = trim($text) . "..."; #убираем пробел, если он остался после резки и добавляем троеточие
        } else {
            // иначе, если у нас количество символов не больше указанного в переменной $num, то мы кодируем обратно в utf-8
            $text = iconv('windows-1251', 'utf-8', $text);
            $crop = $text;
        }

        return $crop;
    }
}

if (!function_exists('array_key_first')) {
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return null;
    }
}

if (!function_exists('x_week_range')) {
    function x_week_range($date)
    {
        $ts = strtotime($date);
        $start = (date('w', $ts) == 1) ? $ts : strtotime('last monday', $ts);
        return date('Y-m-d', $start) . '/' . date('Y-m-d', strtotime('next sunday', $start));
    }
}

if (!function_exists('week_dates')) {
    function week_dates()
    {
        $dates = [];
        $dateTime = new DateTime();
        $date = $dateTime->format('Y-m-d');
        $dates[1][1] = date('d.m.Y', strtotime('monday this week', strtotime($date)));
        $dates[1][7] = date('d.m.Y', strtotime('sunday this week', strtotime($date)));

        $dateTime->modify('next week');
        $date = $dateTime->format('Y-m-d');
        $dates[2][1] = date('d.m.Y', strtotime('monday this week', strtotime($date)));
        $dates[2][7] = date('d.m.Y', strtotime('sunday this week', strtotime($date)));

        $dateTime->modify('next week');
        $date = $dateTime->format('Y-m-d');
        $dates[3][1] = date('d.m.Y', strtotime('monday this week', strtotime($date)));
        $dates[3][7] = date('d.m.Y', strtotime('sunday this week', strtotime($date)));

        $dateTime->modify('next week');
        $date = $dateTime->format('Y-m-d');
        $dates[4][1] = date('d.m.Y', strtotime('monday this week', strtotime($date)));
        $dates[4][7] = date('d.m.Y', strtotime('sunday this week', strtotime($date)));

        return $dates;
    }
}

if (!function_exists('prepareRelations')) {
    function prepareRelations($tableRelations)
    {
        $relations = [];
        foreach ($tableRelations as $relationName => $relationParams) {
            $relName = $relationName;
            $relParams = $relationParams;
            if (is_string($relationParams)) {
                //echo '<pre>';var_dump('$relationName=',$relationName);echo '</pre>';//exit();
                $relName = $relationParams;
                $relParams = [
                    'table' => $relationParams,
                    'attribute' => $relationParams . '_id',
                ];
                if ($relationParams == 'language') {
                    $relParams['type'] = 'string';
                }
            }
            $relations[$relName] = $relParams;
        }

        return $relations;
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($str, $e = 'utf-8')
    {
        $fc = mb_strtoupper(mb_substr($str, 0, 1, $e), $e);
        return $fc . mb_substr($str, 1, mb_strlen($str, $e), $e);
    }
}

if (!function_exists('json_validate')) {
    function json_validate(string $json, int $depth = 512, int $flags = 0): bool
    {
        if ($flags !== 0 && $flags !== JSON_INVALID_UTF8_IGNORE) {
            throw new ValueError('json_validate(): Argument #3 ($flags) must be a valid flag (allowed flags: JSON_INVALID_UTF8_IGNORE)');
        }

        if ($depth <= 0) {
            throw new ValueError('json_validate(): Argument #2 ($depth) must be greater than 0');
        }

        json_decode($json, null, $depth, $flags);

        return json_last_error() === \JSON_ERROR_NONE;
    }
}

if (!function_exists('getCache1')) {
    /**
     * @param string $cacheKey Ключ для хранения в Кеше
     * @param string $tableName Имя таблицы
     * @param array $conditions Условия where ['active'=>1, 'name IS NOT NULL']
     * @param string $key
     * @param string $value
     * @param bool $lower Все маленькие
     * @param bool $reload Обновить
     *
     * @return array|mixed|DataReader
     * @throws Exception
     */
    function getCache1(
        string $cacheKey,
        string $tableName,
        array  $conditions,
        string $key = 'id',
        string $value = 'name',
        bool   $lower = true,
        bool   $reload = false
    )
    {
        // Попытаемся получить дынные по ключу из Кеша
        $resArr = Yii::$app->cache->get($cacheKey);

        // Если Кеш "просрочен, изменён, пуст" или "принудительно обновить" - выполним вычисления снова
        if ($resArr === false || $reload === true) {
            if ($lower === true) {
                $resArr = (new Query())
                    ->from(['self' => $tableName])
                    ->distinct()
                    ->select([
                        $key,
                        $value,
                    ])
                    ->andWhere($conditions)
                    ->indexBy($value)
                    ->orderBy($key)
                    ->column();
            }
            // Поместим данные по ключу в Кеш
            Yii::$app->cache->set($cacheKey, $resArr, 86400); // будет обновляться раз в сутки 60 * 60 * 24
        }

        return $resArr;
    }
}

if (!function_exists('calcExperience')) {
    /**
     * @param int $experience
     *
     * @return string
     */
    function calcExperience($experience)
    {
        $year = (new DateTime)->setTimestamp($experience)->diff(new DateTime)->y;
        $r1 = $year % 10;
        $r2 = $year % 100;
        return $year . ' ' . ($r1 == 1 && $r2 != 11 ? Yiit::t(
                'год'
            ) : ($r1 >= 2 && $r1 <= 4 && ($r2 < 10 || $r2 >= 20) ? Yiit::t('года') : Yiit::t('лет')));
    }
}

if (!function_exists('originalLabel')) {
    /**
     * @param string $label
     *
     * @return string
     */
    function originalLabel($label)
    {
        return str_replace('*', '', $label);
    }
}
