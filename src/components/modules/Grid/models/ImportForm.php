<?php

namespace ripaym1970\autocrud\components\modules\Grid\models;

use ripaym1970\autocrud\components\Yiit;
use Yii;

/**
 *
 * @property string $successMessage
 *
 */
class ImportForm extends AbstractExportImportForm
{
    public $file;
    /** @var array */
    public $shareIds = [];

    protected $profiles = [];

    public function rules()
    {
        return [
            [
                ['file'],
                'file',
                'skipOnEmpty' => false,
            ],
            [
                ['shareIds'],
                'required',
                'skipOnEmpty' => false,
            ],
            [
                ['shareIds'],
                'each',
                'rule' => [
                    'integer'
                ],
            ],
            [
                ['shareIds'],
                'each',
                'rule' => [
                    'in',
                    'range' => array_merge(
                        \yii\helpers\ArrayHelper::getColumn(
                            \ripaym1970\autocrud\components\modules\Grid\Module::getInstance()->possibleShares,
                            'id'
                        ),
                        [-1]
                    ),
                ],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'shareIds' => \Yii::t('app', "Set sharing")
        ];
    }

    public function upload()
    {
        try {
            $this->profiles = \yii\helpers\Json::decode(
                file_get_contents($this->file->tempName),
                true
            );
        } catch (\yii\base\InvalidArgumentException $e) {
            $this->addError(
                'file',
                Yiit::t("File has invalid JSON format")
            );
            return false;
        }
        return true;
    }


    public function process()
    {
        $transaction = \ripaym1970\autocrud\components\Util::makeTransaction(true);
        foreach ($this->profiles as $data) {
            if (!$this->importProfile($data)) {
                return false;
            }
        }
        $transaction->commit();
        return true;
    }

    protected function importProfile(array $data)
    {
        foreach (self::ATTRIBUTES as $attribute) {
            if (!isset($data[$attribute])) {
                $this->addError(
                    'file',
                    \Yii::t(
                        "app",
                        'Required attribute {attribute} was not found',
                        [
                            'attribute' => $attribute,
                        ]
                    )
                );
                return null;
            }
        }

        $profile = new GridProfile($data);
        $profile->parent_id = Yii::$app->user->id;
        $profile->parent_class = Yii::$app->user->identityClass;

        if ($profile->save()) {
            $profile->setShares($this->shareIds);
            return true;
        }
        $this->addError(
            'file',
            Yiit::t(
                "Could not import profile {name}. The next errors appeared: {errors}",
                [
                    'name' => $profile->name,
                    'errors' => \yii\helpers\Html::errorSummary(
                        $profile,
                        [
                            'header' => '',
                        ]
                    )
                ]
            )
        );
        return false;
    }
}
