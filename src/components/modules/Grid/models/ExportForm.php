<?php

namespace ripaym1970\autocrud\components\modules\Grid\models;

class ExportForm extends AbstractExportImportForm
{
    public $ids = [];

    public function rules()
    {
        return [
            [
                'ids',
                'required'
            ],
            [
                ['ids'],
                'each',
                'rule' => [
                    'integer',
                ],
            ],
        ];
    }

    public function process()
    {
        $result = [];
        foreach ($this->ids as $id) {
            $profile = GridProfile::findOne(
                [
                    'id' => $id,
                ]
            );
            $result[] = $profile->toArray(self::ATTRIBUTES);
        }
        return \yii\helpers\Json::encode($result, JSON_PRETTY_PRINT);
    }

}
