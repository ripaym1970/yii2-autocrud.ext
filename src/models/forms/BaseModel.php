<?php
namespace ripaym1970\autocrud\models\forms;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property ActiveQuery $lang
 * @property ActiveQuery $translateLang
 */
class BaseModel extends ActiveRecord
{
    public function getTitle()
    {
        return '';
    }

    public function getIndexConfig()
    {
        return [];
    }

    public function additionalOptions()
    {
        return [];
    }

    public function viewData()
    {
        return [];
    }

    public $langModel;

    /**
     * @return ActiveQuery
     */
    public function getLangs()
    {
        return $this->hasMany($this->langModel, ['model_id' => 'id']);
    }

    /**
     * @return false|mixed|ActiveQuery
     */
    public function getEditLang()
    {
        if ($this->isNewRecord) {
            return new $this->langModel;
        }
        if (!$this->langModel) {
            return false;
        }

        return !!$this->langModel::findOne([
            'model_id' => $this->id,
            'language' => Language::getEditLang(),
        ])
            ? $this->hasOne($this->langModel, ['model_id' => 'id'])
                ->andWhere([
                    'language' => Language::getEditLang(),
                ])
            : new $this->langModel;
    }

    /**
     * @param $language
     *
     * @return ActiveQuery
     */
    public function hasTranslate($language)
    {
        if (!$this->langModel) {
            return false;
        }

        return $this->hasOne($this->langModel, ['model_id' => 'id'])
            ->andWhere([
                'language' => $language,
            ]);
    }

    /**
     * @return ActiveQuery
     */
    public function getLang()
    {
        return $this->hasOne($this->langModel, ['model_id' => 'id'])
            ->andWhere([
                'language' => Yii::$app->language,
            ])
            ?? $this->hasOne($this->langModel, ['model_id' => 'id'])
                ->andWhere([
                    'language' => Language::getDefaultLang()->locale,
                ]);
    }

    /**
     * @return ActiveQuery
     */
    public function getTranslateLang()
    {
        return $this->hasOne($this->langModel, ['model_id' => 'id'])
            ->andWhere([
                'language' => Yii::$app->request->get('lang_id', Yii::$app->language),
            ]);
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'translate' => [
                'class' => TranslateBehavior::class,
            ],
        ]);
    }
}
