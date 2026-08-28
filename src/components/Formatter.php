<?php

namespace ripaym1970\autocrud\components;

//use ripaym1970\autocrud\models\core\Image;
use ripaym1970\autocrud\models\CrudModel;
use yii\helpers\ArrayHelper;

class Formatter extends \yii\i18n\Formatter
{
    public $timeFormat = 'HH:mm:ss';

    public $datetimeFormat = 'dd.MM.yyyy HH:mm:ss';

    public $dateFormat = 'dd.MM.yyyy';

    public function asT($value)
    {
        return Yiit::t(ucfirst($value));
    }

    public function asArray($value)
    {
        if (empty($value)) {
            return null;
        }

        if (is_array($value) && current($value) instanceof CrudModel) {
            return implode(', ', ArrayHelper::getColumn($value, 'name'));
        }

        //if (is_array($value)) {
        //    dd('is_array2');
        //    return implode(',', $value);
        //}

        return $value;
    }

    /*public function asImage($value, $options = [])
    {
        if ($value instanceof Image) {
            $value = $value->urlAdminSmall;
        }
        return parent::asImage($value, $options);
    }*/
}
