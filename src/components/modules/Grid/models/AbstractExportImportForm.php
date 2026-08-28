<?php

namespace ripaym1970\autocrud\components\modules\Grid\models;

abstract class AbstractExportImportForm extends \yii\base\Model
{
    protected const ATTRIBUTES = [
        'name',
        'url',
        'type_id',
        'data',
    ];
}
