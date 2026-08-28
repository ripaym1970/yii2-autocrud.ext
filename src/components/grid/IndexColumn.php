<?php

namespace ripaym1970\autocrud\components\grid;

use Yii;
use yii\helpers\ArrayHelper;
use yii2tech\admin\grid\PositionColumn;

class IndexColumn extends PositionColumn
{
    protected function initDefaultButtons()
    {
        $sort = Yii::$app->request->get($this->grid->dataProvider->sort ? $this->grid->dataProvider->sort->sortParam : 'sort');
        parent::initDefaultButtons();
        $this->buttons = ArrayHelper::merge($this->buttons, [
            'first' => [
                'icon' => $sort == '-index' ? 'triangle-bottom' : 'triangle-top',
                'visible' => function ($model, $key, $index) use ($sort) {
                    return $sort == '-index' ? $index + 1 < $this->grid->dataProvider->getTotalCount() : $index > 0;
                },
            ],
            'last' => [
                'icon' => $sort == '-index' ? 'triangle-top' : 'triangle-bottom',
                'visible' => function ($model, $key, $index) use ($sort) {
                    return $sort == '-index' ? $index > 0 : $index + 1 < $this->grid->dataProvider->getTotalCount();
                },
            ],
            'prev' => [
                'icon' => $sort == '-index' ? 'arrow-down' : 'arrow-up',
                'visible' => function ($model, $key, $index) use ($sort) {
                    return $sort == '-index' ? $index + 1 < $this->grid->dataProvider->getTotalCount() : $index > 0;
                },
            ],
            'next' => [
                'icon' => $sort == '-index' ? 'arrow-up' : 'arrow-down',
                'visible' => function ($model, $key, $index) use ($sort) {
                    return $sort == '-index' ? $index > 0 : $index + 1 < $this->grid->dataProvider->getTotalCount();
                },
            ],
        ]);
    }

    public function getDataCellValue($model, $key, $index)
    {
        if (isset($model->behaviors()['nestedSetsBehavior']) && $model->{$model->depthAttribute} > 1) {
            return $index + 1;
        }
        return parent::getDataCellValue($model, $key, $index);
    }
}
