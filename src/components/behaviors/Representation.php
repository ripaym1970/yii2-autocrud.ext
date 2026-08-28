<?php

namespace ripaym1970\autocrud\components\behaviors;

/**
 * @property \yii\db\ActiveRecord $owner
 */
class Representation extends \yii\base\Behavior
{
    public $fieldCallbacks = [];
    public $autoSuggestFormat = true;

    public function getRepresentation($field, $value)
    {
        $callback = $this->fieldCallbacks[$field] ?? null;
        if ($callback) {
            return call_user_func($callback, $this->owner, $value);
        }

        if (!$this->autoSuggestFormat) {
            return $value;
        }

        if (is_array($value)) {
            return \yii\helpers\Json::encode(
                $value,
                JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRETTY_PRINT
            );
        }

        if (!is_scalar($value)) {
            return $value;
        }

        $columnType = $this->owner::getTableSchema()->columns[$field]
            ?? null;

        switch ($columnType->type ?? null) {
            case \yii\db\Schema::TYPE_TIME:
                return \yii::$app->formatter->asTime($value);

            case \yii\db\Schema::TYPE_TIMESTAMP:
            case \yii\db\Schema::TYPE_DATETIME:
                return \yii::$app->formatter->asDatetime($value);

            case \yii\db\Schema::TYPE_DATE:
                return \yii::$app->formatter->asDate($value);

            case \yii\db\Schema::TYPE_BOOLEAN:
                return \yii::$app->formatter->asBoolean($value);

            default:
                return $value;
        }
    }
}
