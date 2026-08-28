<?php

use ripaym1970\autocrud\components\modules\Grid\Helper;
use ripaym1970\autocrud\components\Yiit;
use yii\web\View;

/**
 * @var View   $this
 * @var Helper $gridHelper
 */

$this->title = Yiit::t('Historical Records List');

echo $this->render('render-helper', ['gridHelper' => $gridHelper]);
