<?php

/**
 * @see \backend\controllers\CrudController
 */

use ripaym1970\autocrud\models\crud\LanguageModel;
use ripaym1970\autocrud\models\CrudModel;
use ripaym1970\autocrud\models\forms\ImagesForm;
use kartik\file\FileInput;
use kartik\widgets\ActiveForm;
use ripaym1970\autocrud\components\DetailView;
use ripaym1970\autocrud\components\extend\yiidreamteam\ImageUploadBehavior;
use ripaym1970\autocrud\components\Yiit;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\web\View;

/** @var View $this */
/** @var CrudModel $model */
/** @var ImagesForm $imagesForm */

$languageId = Yii::$app->language;
$languageIds = LanguageModel::activeIds();
$countLanguageIds = count($languageIds);

$table = Yii::$app->request->get('table');

$this->title = Yiit::t(ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.title', ucfirst(Inflector::pluralize($table))));
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index', 'table' => $table]];

$displayName = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.displayName', 'id');
$this->params['breadcrumbs'][] = $model->$displayName;
$attributes = [];
//$attributes['displayName'] = $model->$displayName;

$viewToolbar = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.viewToolbar', true);
if ($viewToolbar) {
    $this->params['contextMenuItems'][] = [
        'update',
        'id'      => $model->id,
        'table'   => $table,
        'visible' => !empty($viewToolbar),
    ];
    $formFieldsPassword = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.formFieldsPassword', false);
    if ($formFieldsPassword) {
        $this->params['contextMenuItems'][] = [
            'label' => Yiit::t('Змінити пароль'),
            'url'   => ['password', 'table' => $table, 'id' => $model->id, 'visible' => $table == 'user'],
        ];
    }
    $this->params['contextMenuItems'][] = ['delete', 'id' => $model->id, 'table' => $table];
}

// В большинстве случаев детальный просмотр записи состоит из полей gridColumns и полей не вошедших в gridColumns
$detailViewAttributes = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.viewAttributes', []);
$gridColumns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.gridColumns', []);
if ($detailViewAttributes) {
    if ($detailViewAttributes[0] == 'gridColumns') {
        unset($detailViewAttributes[0]);
        $detailViewAttributes = \yii\helpers\ArrayHelper::merge($gridColumns, $detailViewAttributes);
    }
} else {
    $detailViewAttributes = $gridColumns;
}
//dd($detailViewAttributes);

$viewAttributes = [];
foreach ($detailViewAttributes as $columnName => $item) {
    if (is_string($item)) {
        $columnName = $item;
        if (strpos($item, ':')) {
            try {
                [$columnName, $type] = explode(':', $item);
            } catch (\Exception $e) {
                dd('Не удалось распарсить ', $item);
            }
            if ($type == 'dropdown') {
                $item = [
                    'attribute' => $columnName,
                    'format'    => 'raw',
                    'value'     => function ($model) use ($table, $columnName) {
                        $columns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.columns', []);
                        $items = [];
                        if (isset($columns[$columnName]['items']) && $columns[$columnName]['items'] instanceof \Closure) {
                            $items = call_user_func($columns[$columnName]['items']);
                        }
                        $modelStatus = $model->$columnName;
                        return $items[$modelStatus] ?? 'Невідомий ключ ' . $modelStatus;
                    },
                    //'filter' => 'distinct',
                    //'filter_class_name' => 'language',
                ];
            }
        }
        $viewAttributes[$columnName] = $item;
    } else {
        // иначе массив
        $viewAttributes[$item['attribute']] = $item;
    }
}
//dd($viewAttributes);

$tableColumns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.columns', []);
//dd($tableColumns);
$columnsTranslation = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '_translation.columns', []);
//dd($columnsTranslation);

foreach ($viewAttributes as $key => $attribute) {
    if (is_string($attribute)) {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $attribute, $matches)) {
            throw new InvalidConfigException(
                'The attribute must be specified in the format of "attribute", "attribute:format" or ' .
                '"attribute:format:label"'
            );
        }
        $attributes[] = [
            'attribute' => $matches[1],
            'format'    => $matches[3] ?? 'text',
        ];
        //dd($attributes);
    } else {
        $attributes[] = $attribute;
    }
}

?>

<div class="row">
    <div class="col-lg-12 detail-view-wrap">
        <?php
        echo DetailView::widget([
            'model'      => $model,
            'attributes' => $attributes,
        ]);
        // if (YII_DEBUG) {
        //     echo 'Кількість полів моделі ' . count($attributes);
        // }
        ?>
    </div>
</div>

<?php
//dd($model['images']);
// Если есть изображения к модели
if (isset($model['images'])) { ?>
    <div id="images" class="box">
        <div class="box-header with-border"><?=Yiit::t('Зображення')?></div>
        <?php
        $messageMain = '';
        $main = ArrayHelper::getValue(
            Yii::$app->params,
            'tables.' . $table . '_image.behaviors.imageUpload.thumbs.main',
            []
        );
        if ($main) {
            $messageMain = Yiit::t('Головне Зображення повинно бути') . ' ';
            $messageMain .= Yiit::t('по висоті') . ' ' . $main['width'] . 'px та ';
            $messageMain .= Yiit::t('по ширині') . ' ' . $main['height'] . 'px ';
            $messageMain .= Yiit::t('або кратно цім розмірам');
        }

        $items = [
            Html::beginTag('ul'),
            Html::tag('li', Yiit::t('Чим більший розмір - тим краще')),
            Html::tag('li', Yiit::t('Перше зображення буде головним')),
            $messageMain ? Html::tag('li', $messageMain) : '',
            Html::endTag('ul'),
        ];

        echo \ripaym1970\autocrud\components\widgets\HelpBlock::widget([
            'content' => implode(' ', $items)
        ]);
        ?>

        <div class="box-body">
            <div class="row">
                <?php
                $images = $model->images ?? [];
                if ($images) {
                    $i = 1;
                    $countImages = count($images);
                    foreach ($images as $key => $image) { ?>
                        <div class="col-md-2 col-xs-3" style="text-align: center">
                            <div class="btn-group">
                                <?php
                                if ($i != 1) {
                                    echo Html::a(
                                        '<span class="glyphicon glyphicon-arrow-left"></span>',
                                        ["/$table/move-image-up", 'id' => $model->id, 'image_id' => $image->id],
                                        [
                                            'class'       => 'btn btn-default',
                                            'title'       => Yiit::t('Зображення перемістити вліво'),
                                            'data-method' => 'post',
                                        ]
                                    );
                                } ?>
                                <?= Html::a(
                                    '<span class="glyphicon glyphicon-remove"></span>',
                                    ["/$table/delete-image", 'id' => $model->id, 'image_id' => $image->id],
                                    [
                                        'class'        => 'btn btn-default',
                                        'title'        => Yiit::t('Видалення зображення'),
                                        'data-method'  => 'post',
                                        'data-confirm' => Yiit::t('Ви дійсно бажаєте видалити зображення?'),
                                    ]
                                ); ?>
                                <?php
                                if ($i != $countImages) {
                                    echo Html::a(
                                        '<span class="glyphicon glyphicon-arrow-right"></span>',
                                        ["/$table/move-image-down", 'id' => $model->id, 'image_id' => $image->id],
                                        [
                                            'class' => 'btn btn-default',
                                            'title' => Yiit::t('Зображення перемістити вправо'),
                                            'data-method' => 'post',
                                        ]
                                    );
                                }
                                ?>
                            </div>

                            <div>
                                <?php
                                /* @var ImageUploadBehavior $image */
                                //dd($image->getThumbFileUrl('file'), $image->getUploadedFileUrl('file'));
                                echo Html::a(
                                    Html::img($image->getThumbFileUrl('file')),
                                    $image->getUploadedFileUrl('file'),
                                    ['class' => 'thumbnail', 'target' => '_blank']
                                );
                                $i++;
                                ?>
                            </div>
                        </div>
                    <?php
                    } ?>
                <?php
                } else { ?>
                    <div class="col-md-2 col-xs-3" style="/*text-align: center*/">
                        <?=Yiit::t('Зображень не знайдено')?>
                    </div>
                <?php
                } ?>
            </div>
        </div>
    </div>

    <div id="add-images" class="box">
        <div class="box-header with-border"><?=Yiit::t('Додати зображення')?></div>
        <div class="box-body">
            <?php
            $form = ActiveForm::begin([
                'options' => ['enctype' => 'multipart/form-data'],
            ]);
            ?>

            <?php
            echo $form->field($imagesForm, 'files[]')
                ->widget(FileInput::class, [
                    'language' => Yii::$app->language,
                    'options' => [
                        'accept' => 'image/*',
                        'multiple' => true,
                    ],
                ])
                ->label(false);
            ?>

            <div class="form-group">
                <?= Html::submitButton(Yiit::t('Зберегти'), ['class' => 'btn btn-success']) ?>
            </div>

            <?php
            ActiveForm::end(); ?>
        </div>
    </div>
<?php
} ?>
