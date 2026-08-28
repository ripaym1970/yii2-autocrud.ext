<?php

namespace ripaym1970\autocrud\components;

class Settings extends \yii2mod\settings\components\Settings
{
    protected function convertSettingType($type)
    {
        if ($type === 'array') {
            $settings = [];
            $elements = explode("\n", $this->setting);
            foreach ($elements as $element) {
                if (strpos($element, '=>') !== false) {
                    [$key, $value] = explode('=>', $element);
                    $settings[$key] = $value;
                } else {
                    $settings[] = $element;
                }
            }
            $this->setting = $settings;
        } else {
            parent::convertSettingType($type);
        }
    }
}
