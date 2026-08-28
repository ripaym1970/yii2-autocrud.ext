<?php

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\models\behaviors\NestedSetsBehavior;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\web\BadRequestHttpException;
use yii2tech\admin\actions\Position;
use yii2tech\ar\position\PositionBehavior;

class PositionAction extends Position
{
    protected function respondSuccess($model)
    {
        return $this->controller->redirect($this->createReturnUrl('index', $model));
    }

    /**
     * @param ActiveRecordInterface|PositionBehavior|NestedSetsBehavior $model
     * @param                                                           $position
     *
     * @return void
     * @throws BadRequestHttpException
     */
    protected function positionModel($model, $position)
    {
        //dd($model->attributes, $position);
        $position = strtolower($position);
        if (isset($model->behaviors()['nestedSetsBehavior']) && $model->{$model->depthAttribute} > 1) {
            $success = true;
            switch ($position) {
                case 'up':
                case 'prev':
                    $prev = $model->prev()->one();
                    if ($prev) {
                        $success = $model->insertBefore($prev);
                    }
                    break;
                case 'down':
                case 'next':
                    $next = $model->next()->one();
                    if ($next) {
                        $success = $model->insertAfter($next);
                    }
                    break;
                case 'top':
                case 'first':
                    $parent = $model->parents(1)->one();
                    if ($parent) {
                        $success = $model->prependTo($parent);
                    }
                    break;
                case 'bottom':
                case 'last':
                    $parent = $model->parents(1)->one();
                    if ($parent) {
                        $success = $model->appendTo($parent);
                    }
                    break;
                default:
                    throw new BadRequestHttpException(Yii::t('yii', '{attribute} is invalid.', ['attribute' => $this->positionParam]));
            }
            if (!$success) {
                $this->setFlash(['error' => implode("<br/>", $model->getErrorSummary(true))]);
            }
            return;
        }
        switch ($position) {
            case 'up':
            case 'prev':
                $model->movePrev();
                break;
            case 'down':
            case 'next':
                $model->moveNext();
                break;
            case 'top':
            case 'first':
                $model->moveFirst();
                break;
            case 'bottom':
            case 'last':
                $model->moveLast();
                break;
            default:
                if (is_numeric($position)) {
                    $model->moveToPosition($position);
                } else {
                    throw new BadRequestHttpException(Yii::t('yii', '{attribute} is invalid.', ['attribute' => $this->positionParam]));
                }
        }
    }
}
