<?php


/**
 * @var \yii\web\View $this
 * @var \ripaym1970\autocrud\components\modules\Grid\Helper $gridHelper
 */

$this->title = \Yii::t('app', "Grid Profiles Management");

\ripaym1970\autocrud\components\modules\Grid\assets\ProfilesManagement::register($this);

$splitter = new \ripaym1970\autocrud\components\widgets\Splitter(uniqid());
$splitter->orientation('horizontal');

$leftPane = new \Kendo\UI\SplitterPane();
$leftPane->collapsible(true)
    ->size("80%")
    ->content(
        $gridHelper->widget()
    );
$splitter->addPane($leftPane);

$rightPane = new \Kendo\UI\SplitterPane();
$rightPane->collapsible(true)
    ->content(
        \yii\helpers\Html::tag(
            'div',
            '',
            [
                'id' => 'js-shares'
            ]
        )
    );

$splitter->addPane($rightPane);
echo $splitter->html();
$this->registerJs($splitter->script(false));

