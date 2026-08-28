<?php

namespace ripaym1970\autocrud\components;

use ripaym1970\autocrud\models\crud\SettingModel;
use ripaym1970\autocrud\models\interfaces\SettingModelInterface;
use Yii;
use yii\base\Component;

class CustomSettings extends Component
{
    public function get($key)
    {
        $value = Yii::$app->cache->get('settings_' . $key);
        if ($value) {
            return $value;
        }

        /** @var SettingModelInterface $setting */
        $setting = SettingModel::findOne(['key' => $key]);
        if (!$setting) {
            dd('Not set ' . $key);
            return 'Not set';
        }

        Yii::$app->cache->set('settings_' . $key, $setting->value, 3600);

        return $setting->value;
    }
}
