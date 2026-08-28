<?php

/**
 * @see \backend\controllers\CrudController
 */

use ripaym1970\autocrud\models\crud\LanguageModel;
use ripaym1970\autocrud\models\CrudModel;
use kartik\date\DatePicker;
use kartik\datetime\DateTimePicker;
use kartik\helpers\Html;
use kartik\number\NumberControl;
use kartik\select2\Select2;
use kartik\switchinput\SwitchInput;
use kartik\time\TimePicker;
use kartik\touchspin\TouchSpin;
use mihaildev\ckeditor\CKEditor;
use ripaym1970\autocrud\components\ActiveForm;
use ripaym1970\autocrud\components\extend\yiidreamteam\ImageUploadBehavior;
use ripaym1970\autocrud\components\grid\ActionColumn;
use ripaym1970\autocrud\components\grid\GridView;
use ripaym1970\autocrud\components\widgets\Alert\Alert;
use ripaym1970\autocrud\components\widgets\MultipleInput\TabularColumn;
use ripaym1970\autocrud\components\widgets\MultipleInput\TabularInput;
use ripaym1970\autocrud\components\Yiit;
use yii\data\ArrayDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\web\View;

/** @var View $this */
/** @var CrudModel $model */
/** @var ActiveForm $aForm */
/** @var ActiveRecord $className */
/** @var string $form */

//dd('$model=',$model->errors);
echo Alert::widget();

//$session = Yii::$app->session;
//dd('$session=',$session->getFlash('error'));

$languageId = Yii::$app->language;
$languageIds = LanguageModel::activeIds();
//$countLanguageIds = count($languageIds);

$table = Yii::$app->request->get('table');
$aForm = ActiveForm::begin([
    'options' => ArrayHelper::merge(
        [
            'autocomplete' => 'off',
        ],
        ArrayHelper::getValue(
            Yii::$app->params,
            'tables.' . $table . '.formOptions',
            []
        )
    ),
]);
$columns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.columns', []);
$columnsTranslation = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '_translation.columns', []);
// TODO: formFieldsPassword ucfirst($form)==Password
//dd('$form=',$form);
$fields = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.formFields' . ucfirst($form), []);
foreach ($fields as $row) {
    //dd('$row=',$row);
    $countRow = count($row);
    $defaultWidth = ($countRow > 6) ? 1 :ceil(12 / $countRow);
    echo Html::beginTag('div', ['class' => 'row', 'data-width' => $defaultWidth,]);
    unset($item);
    foreach ($row as $item) {
        //if (isset($item['rel']) && $item['rel'] == 'country') {
        //    $relName = $item['rel'];
        //    //dd($item, $relName, isset($model->sity), isset($model->country), isset($model->$relName), $model);
        //}
        if ($model->isNewRecord && !empty($item['update'])) {
            continue;
        }
        if (isset($item['if'])) {
            $show = true;
            foreach ($item['if'] as $key => $value) {
                $show &= $model->$key == $value;
            }
            if (!$show) {
                continue;
            }
        }

        $width = $item['width'] ?? $defaultWidth;
        //dd($width);
        $type = $item['type'] ?? 'text';
        //dd($columns, $fields, $item, $type);

        //if (isset($item['source'])) {
        //    if ($item['source'] === 'settings' &&
        //        ((isset($item['key']) && $items = Yii::$app->settings->get('default', $model->{$item['key']} . 's'))
        //            || (isset($item['setting']) && $items = Yii::$app->settings->get('default', $item['setting'])))
        //    ) {
        //        $type = 'dropdown';
        //        $item['items'] = $items;
        //    } else {
        //        $className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($item['source']) . 'Model';
        //        $item['items'] = ArrayHelper::map($className::find()->all(), 'id', 'name');
        //    }
        //}

        $columnName = $item[0];
        $column = $columns[$columnName] ?? [];

        if ($model->isNewRecord && empty($model->{$columnName}) && isset($column['default'])) {
            $model->{$columnName} = $column['default'];
        }

        $defaultOptions = [
            'required' => $item['required'] ?? null,
            'readonly' => $item['readonly'] ?? null,
            'disabled' => $item['disabled'] ?? null,
        ];

        $tooltip = false;
        if (isset($item['tooltip'])) {
            $tooltip = $item['tooltip'];
            if (is_callable($tooltip)) {
                $tooltip = call_user_func($tooltip, $model);
            }
            $tooltip = "<div>$tooltip</div>";
        }

        $translations = [$model];
        // Если есть вариации и поле $columnName из этого списка
        if (
            isset($model->behaviors()['translations'])
            && array_key_exists($columnName, $model->behaviors()['translations']['variationAttributeDefaultValueMap'])
        ) {
            $translations = $model->getVariationModels();
            foreach ($translations as $key => $translation) {
                if (!in_array($translation->language_id, $languageIds)) {
                    unset($translations[$key]);
                }
            }
            //dd($translations);
        }

        //dd('$columnName='.$columnName);
        //$label = $columns[$columnName]['comment'] ?? ucfirst($columnName);
        //$label = !$label && isset($columnsTranslation[$columnName]['comment']) ? $columnsTranslation[$columnName]['comment'] : $label;
        //$label = !$label && isset($item['label']) ? $item['label'] : $label;
        //$label = $label ?? ucfirst(Inflector::pluralize($columnName));
        //d([$columnName,$label,$item]);
        //dd($translations);
        /** @var \ripaym1970\autocrud\models\interfaces\Currency_translationModelInterface[] $translations */
        foreach ($translations as $index => $translation) {

            echo Html::beginTag('div', ['class' => 'col-xs-' . $width]);
            //dd($index, $columnName, $item, $translation);
            $attribute = (get_class($translation) === get_class($model) ? '' : '[' . $index . ']') . $columnName;
            //d($attribute, $columnName, $type, $index, $translation->attributes);

            if ($columnName == 'slug') {
                $field = $aForm->field($translation, $attribute, [
                    'enableAjaxValidation' => true,
                    'template' => "{label}\n" . ($tooltip ?? '') . "{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
                ]);
            } else {
                // Дефолтное - задаётся для поля в formFields
                if ($model->isNewRecord && isset($item['default'])) {
                    if ($item['default'] instanceof \Closure) {
                        $default = call_user_func($item['default']);
                    } else {
                        $default = $item['default'];
                    }
                    $translation->{$columnName} = $default;
                }

                $field = $aForm->field($translation, $attribute, [
                    'template' => "{label}\n" . ($tooltip ?? '') . "{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
                ]);
            }

            // Если это поле с вариацией
            if (get_class($translation) !== get_class($model)) {
                //dd('$translation=',$translation);
                //dd('$translation=',$translation->language);
                // Получим название поля
                $label = $columnsTranslation[$columnName]['comment'] ?? $columnName;
                // Добавим название языка вариации
                $field->label($label . ' (' . $translation->language->name . ')');
            }

            // Если поле обязательно к заполнению
            if (!empty($defaultOptions['required'])) {
                $field->options['class'] .= ' required';
            }
            foreach ($defaultOptions as $key => $value) {
                if (is_callable($value)) {
                    $defaultOptions[$key] = call_user_func($value, $model);
                }
            }
            switch ($type) {
                case 'dropdown':
                    if (!isset($columns[$columnName]['items'])) {
                        dd('Не задано "$items" для dropDownList');
                    }

                    if ($columns[$columnName]['items'] instanceof \Closure) {
                        $items = ['' => 'Оберіть...',] + call_user_func($columns[$columnName]['items']);
                    } else {
                        $items = $columns[$columnName]['items'] ?? [];
                    }
                    echo $field->dropDownList($items, $defaultOptions);
                    break;
                case 'image':
                    // Проблема при редактировании - затираются старіе значения имени файла,
                    // т.к. из формы значения не возвращаются, а потом ->load() всё затирает
                    /**
                      <div class="image_block">
                        <img src="<?= $model->mfoto ?>" id="main_img" class="<?= !!$model->mfoto ?: 'hidden' ?>" alt=""
                             style="display: block; max-width: 100%; max-height: 100px; margin: 0 auto">
                        <?= $form->field($model, 'mfoto')->widget(InputFile::class, [
                            'language' => Yii::$app->language,
                            'controller' => 'elfinder',
                            'filter' => 'image',
                            'template' => '<div class="input-group">{input}<span class="input-group-btn">{button}</span></div>',
                            'options' => ['class' => 'form-control image-change'],
                            'buttonOptions' => ['class' => 'btn btn-default'],
                            'multiple' => false
                        ]) ?>
                      </div>
                     */

                    $field->template = "{label}{hint}{input}{error}";
                    $img = $model->$columnName;
                    echo HTML::img($img);
                    echo $field->fileInput([
                        'language'      => Yii::$app->language,
                        'controller'    => 'elfinder',
                        'filter'        => 'image',
                        'template'      => '<div class="input-group">{input}<span class="input-group-btn">{button}</span></div>',
                        'options'       => ['class' => 'form-control image-change'],
                        'buttonOptions' => ['class' => 'btn btn-default'],
                        'multiple'      => false,
                    ])
                        ->hint(Yiit::t('Поточне зображення') . ': ' . $model->$columnName);

                    //echo $field->widget(\kartik\file\FileInput::class, [
                    //    'language'      => Yii::$app->language,
                    //    'controller'    => 'elfinder',
                    //    'filter'        => 'image',
                    //    'template'      => '<div class="input-group">{input}<span class="input-group-btn">{button}</span></div>',
                    //    'options'       => [
                    //        'class'  => 'form-control image-change',
                    //        'accept' => 'image/*',
                    //    ],
                    //    'buttonOptions' => ['class' => 'btn btn-default'],
                    //    'multiple'      => false,
                    //
                    //])
                    //    ->hint(Yiit::t('Поточне зображення') . ': ' . $model->$columnName);
                    break;
                case 'checkbox':
                    echo $field->widget(SwitchInput::class, array_diff_key($defaultOptions, ['required' => null]));
                    break;
                case 'radio':
                    echo $field->radioButtonGroup($item['items'], $defaultOptions);
                    break;
                case 'textarea':
                    echo $field->textarea(ArrayHelper::merge(['rows' => $item['rows'] ?? 6], $defaultOptions));
                    break;
                case 'editor':
                    echo $field->widget(CKEditor::class, [
                        'editorOptions' => [
                            'preset' => isset($item['basic']) ? 'basic' : 'full',
                            'language' => 'uk',
                        ],
                    ]);
                    break;
                case 'integer':
                    echo $field->widget(TouchSpin::class, ['pluginOptions' => [
                        'verticalbuttons' => true,
                        'options'         => $defaultOptions,
                    ]]);
                    break;
                case 'decimal':
                case 'float':
                    echo $field->widget(NumberControl::class, ['options' => $defaultOptions]);
                    break;
                case 'datetime':
                    $model->$attribute = $model->$attribute
                        ? date('d.m.Y h:i', strtotime($model->$attribute))
                        : null;
                    echo $field->widget(DateTimePicker::class, ['pluginOptions' => ['autoclose' => true], 'options' => $defaultOptions]);
                    break;
                case 'date':
                    $model->$attribute = $model->$attribute
                        ? date('d.m.Y', strtotime($model->$attribute))
                        : null;
                    echo $field->widget(DatePicker::class, [
                        'pluginOptions' => [
                            'autoclose' => true,
                        ],
                        'options' => $defaultOptions,
                    ]);
                    break;
                case 'time':
                    $model->$attribute = $model->$attribute
                        ? date('h:i', strtotime($model->$attribute))
                        : null;
                    echo $field->widget(TimePicker::class, ['pluginOptions' => ['autoclose' => true], 'options' => $defaultOptions]);
                    break;
                case 'tabularInput':
                    // TODO: Для связи один ко многим с добавлением во многие (Пример: квест -> телефоны)
                    $relation = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.relations.'. $item[0], []);
                    if (!$relation) {
                        dd('Не заданий зв\'язок "$relation"'.$relation);
                    }
                    $relTableName = $relation['table'];
                    if (!$relTableName) {
                        dd('Не задана таблиця в зв\'язку "$relTableName"');
                    }

                    // TODO: $item - элемент из массива formFields
                    $className = 'ripaym1970\autocrud\models\crud\\' . ucfirst($relTableName) . 'Model';
                    $tabularInputColumns = [
                        [
                            'name' => 'id',
                            'type' => TabularColumn::TYPE_HIDDEN_INPUT,
                        ]
                    ];

                    // Столбцы таблицы отношений
                    $relationsTableColumns = ArrayHelper::getValue(
                        Yii::$app->params,
                        'tables.' . $relTableName . '.columns',
                        []
                    );
                    //dd($item['table'], $columnsRelationTable);
                    foreach ($relationsTableColumns as $key => $relationsTableColumn) {
                        //$show = !in_array($key, ['sort',]) && !str_contains($key, 'id');

                        if (in_array($key, ['sort','id'])) {
                            continue;
                        }

                        $title = Yiit::t($relationsTableColumn['comment'] ?? ucfirst($key)); // название столбца

                        $type = TabularColumn::TYPE_TEXT_INPUT;
                        //if ($relationsTableColumn['type'] == 'date') {
                        //    $type = TabularColumn::TYPE_TEXT_INPUT;
                        //}
                        //d($relationsTableColumn);
                        //if ($key == 'clubposition_id') {
                        //    $type = TabularColumn::TYPE_DROPDOWN;
                        //}

                        $options = [];
                        if ($key == 'phone') {
                            $type  = \yii\widgets\MaskedInput::class;
                            $options = [
                                'class' => 'input-phone',
                                'mask' => '+380(99)999-99-99',
                                //         +380(50)452-39-07
                            ];
                        }
                        $tabularInputColumns[] = [
                            'name'    => $key,    // это имя ищется в 'modelClass' и если нет - ошибка
                            'title'   => $title,  // название столбца
                            'type'    => $type,
                            'options' => $options,
                            'attributeOptions' => [
                                'enableClientValidation' => true,
                                'validateOnChange' => true,
                            ],
                            'defaultValue' => '',
                            'enableError'  => true,
                        ];
                    }
                    //dd($relationsTableColumn, $columnName, $item);
                    //$models = $translation->$attribute == []
                    //    ? [new $className()] // Для новой записи надо массив с новой записью
                    //    : $translation->$attribute;
                    $models = $translation->$attribute ?? [new $className()]; // Для новой записи надо массив с новой записью

                    echo TabularInput::widget([
                            'models'            => $models,     // тут модели Quest_phoneModel[]
                            //'cloneButton'     => true,        // кнопку клонировать - не надо
                            'sortable'          => true,        // менять местами строки
                            'min'               => 0,           // чтобы кнопка удалить была для каждой строки
                            'allowEmptyList'    => false,
                            'addButtonPosition' => [
                                TabularInput::POS_HEADER_BEGIN, // вначале строки header
                                //TabularInput::POS_HEADER,
                                //TabularInput::POS_ROW_BEGIN,
                                //TabularInput::POS_ROW,
                                //TabularInput::POS_FOOTER,
                            ],
                            'layoutConfig'      => [
                                'offsetClass'  => 'col-sm-offset-4',
                                'labelClass'   => 'col-sm-2',
                                'wrapperClass' => 'col-sm-10',
                                'errorClass'   => 'col-sm-4',
                            ],
                            'attributeOptions'  => [
                                'enableAjaxValidation'   => true,
                                'enableClientValidation' => false,
                                'validateOnChange'       => false,
                                'validateOnSubmit'       => true,
                                'validateOnBlur'         => false,
                            ],
                            'form'              => $aForm,
                            'tableTitle'        => Yiit::t($item['label'] ?? ucfirst($columnName)),
                            'columns'           => $tabularInputColumns,
                        ]
                    );
                    break;
                case 'array':
                    // TODO: ХЗ где юзается
                    // TODO: Uncaught TypeError: jQuery(...).inputmask is not a function
                    // TODO: В vendor/yiisoft/yii2/widgets/MaskedInputAsset.php требуется 'jquery.inputmask.bundle.js'
                    // TODO: А его нет, а есть 'jquery.inputmask.js'. Надо удалить '.bundle'
                    $elements = explode("\n", $model->{$columnName});
                    foreach ($elements as $key => $element) {
                        $k = '';
                        $v = $element;
                        if (strpos($element, '=>') !== false) {
                            [$k, $v] = explode('=>', $element);
                        }
                        $elements[$key] = ['key' => $k, 'value' => $v];
                    }
                    $field->label(Yiit::t($item['label'] ?? ucfirst($columnName)));
                    echo $aForm->field($model, $columnName . '[key][]', ['enableClientValidation' => false])
                        ->hiddenInput(['value' => ''])
                        ->label(false);
                    echo $aForm->field($model, $columnName . '[value][]', ['enableClientValidation' => false])
                        ->hiddenInput(['value' => ''])
                        ->label(false);
                    echo GridView::widget([
                        'panel'        => null,
                        'layout'       => '{items}',
                        'dataProvider' => new ArrayDataProvider(['allModels' => $elements]),
                        'columns'      => [
                            [
                                'attribute' => 'key',
                                'label'     => Yiit::t('Ключ'),
                                'format'    => 'raw',
                                'value'     => function ($element) use ($model, $columnName, $aForm) {
                                    return $aForm->field($model, $columnName . '[key][]', ['enableClientValidation' => false])
                                        ->textInput(['maxlength' => true, 'value' => $element['key']])
                                        ->label(false);
                                },
                            ],
                            [
                                'attribute' => 'value',
                                'label'     => Yiit::t('Значення'),
                                'format'    => 'raw',
                                'value'     => function ($element) use ($model, $columnName, $aForm) {
                                    return $aForm->field($model, $columnName . '[value][]', ['enableClientValidation' => false])
                                        ->textInput(['maxlength' => true, 'value' => $element['value']])
                                        ->label(false);
                                },
                            ],
                            [
                                'class'    => ActionColumn::class,
                                'template' => '{minus} {plus}',
                                'vAlign'   => '',
                                'buttons'  => [
                                    'minus' => [
                                        'icon'    => 'minus text-danger',
                                        'options' => [
                                            'class' => 'btn btn-xs btn-default delete-row',
                                        ],
                                        'url'     => function () {
                                            return '#';
                                        },
                                    ],
                                    'plus'  => [
                                        'icon'    => 'plus text-success',
                                        'options' => [
                                            'class' => 'btn btn-xs btn-default add-row',
                                        ],
                                        'url'     => function () {
                                            return '#';
                                        },
                                    ],
                                ],
                            ],
                        ],
                    ]);
                    break;
                case 'relatedMany':
                    // TODO: ХЗ где юзается
                    // TODO: Только для связей где есть "object"?
                    $tableName = $item['table'] ?? str_replace('_id', '', $item[0]);
                    // Уточним
                    $name = $item['name'] ?? 'name';
                    $condition = $item['condition'] ?? [];

                    $isTranslations = ArrayHelper::getValue(
                        Yii::$app->params,
                        'tables.' . $table . '.behaviors.translations',
                        false
                    );
                    // Для тегов (связь через таблицу) и предварительная загрузка списка
                    $className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($item['table']) . 'Model';
                    if ($isTranslations) {
                        $query = $className::find()
                            ->andWhere([
                                'active' => true,
                            ]);
                        if (isset($item['where'])) {
                            $query->andWhere($item['where']);
                        }
                        if ($isTranslations) {
                            $query->with(['translations' => function ($query2) use ($languageId, $item) {
                                $query2->select([
                                    $item['table'] . '_id', // Надо для связи
                                    'language_id',
                                    'name',
                                ])
                                    ->andWhere([
                                        'OR',
                                        ['language_id' => $languageId],
                                        ['language_id' => 'en'],
                                    ])
                                    ->andWhere(['NOT', ['name' => null]])
                                ;
                            }]);
                        }
                        $data = $query
                            ->asArray()
                            ->all();
                        if ($isTranslations) {
                            foreach ($data as $key => &$item) {
                                $item['translations'] = $item['translations'][0][$name] . ' (' . $item['id'] . ')';
                            }
                            ArrayHelper::multisort($data, 'translations');
                            $tags = ArrayHelper::map($data, 'id', 'translations');
                        } else {
                            $name = $item['name'] == 'user' ?? 'name';
                            ArrayHelper::multisort($data, $name);
                            $tags = ArrayHelper::map($data, 'id', $name);
                        }
                    } else {
                        $tags = ArrayHelper::map($className::find()->where(['object' => $table])->asArray()->all(), 'id', 'name');
                    }

                    echo $field->widget(Select2::class, [
                        'data'          => $tags,
                        'options'       => [
                            'multiple'    => true,
                            'placeholder' => Yiit::t('Виберіть...'),
                        ],
                        //'pluginOptions' => [
                        //    'tags' => true,
                        //],
                    ]);
                    break;
                case 'relatedOne':
                    // TODO: Для маленьких списков с предварительной загрузкой
                    $tableName = $item['table'] ?? str_replace('_id', '', $item[0]);
                    // Уточним
                    $name = $item['name'] ?? 'name';
                    $condition = $item['condition'] ?? [];

                    $className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($tableName) . 'Model';

                    if ($tableName == 'category_nested') {
                        $data = ArrayHelper::map($className::find()
                            ->select([
                                'id',
                                'name' => "CONCAT(repeat('-- ', (depth - 1)),  $name, ' (', id, ')')",
                            ])
                            ->andFilterWhere($condition)
                            ->asArray()
                            ->all(), 'id', 'name')
                        ;
                    } else {
                        $data = $className::listing($condition, $name) ?: [];
                    }

                    echo $field->widget(Select2::class, [
                        'data'          => $data,
                        'options'       => [
                            'multiple'    => false,
                            'placeholder' => Yiit::t('Виберіть...'),
                        ],
                    ]);
                    break;
                case 'relatedOneAjax':
                    // TODO: Для больших списков с ajax-загрузкой
                    //if ($item[0] == 'player1_id') {
                    //    dd($item);
                    //}
                    // Уточним
                    $tableName = $item['table'] ?? str_replace('_id', '', $item[0]);
                    $name = $item['name'] ?? 'name';
                    //if ($item[0] == 'player1_id') {
                    //    dd($name, $item);
                    //}
                    $fieldName = $row[0][0];
                    $relName   = $item['rel'] ?? $item[0];
                    $condition = json_encode($item['condition'] ?? []);

                    $className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($tableName) . 'Model';

                    //if (!empty($model->$tableName)) {
                    //    dd($className, $tableName, $name);
                    //}

                    $existingName = !empty($model->$relName)
                        ? $model->$relName->$name . ' ('.$model->$relName->id.')'
                        : Yiit::t('Не вдалося получити назву');
                    //$existingId   = $model->$fieldName;
                    //if (!$model->$tableName) {
                    //    dd($existingName, $tableName, $name, $fieldName, $tableName, $model->$tableName, $model->$fieldName);
                    //}
                    $existingId   = $model->$relName->id ?? null;

                    //if ($item[0] == 'club1_id') {
                    //    dd($item, $tableName, $fieldName, $name, $existingName, $existingId, $model->attributes, $model);
                    //    //dd($name, $item, $tableName, $existingName, $existingId);
                    //}

                    echo $field->widget(Select2::class, [
                        'initValueText' => $existingName,
                        'showToggleAll' => false,
                        'options' => [
                            'value'       => $existingId,
                            'multiple'    => false,
                            'placeholder' => Yiit::t('Виберіть...'),
                        ],
                        'language'      => Yii::$app->language,
                        'pluginOptions' => [
                            'allowClear'         => true,
                            'minimumInputLength' => 0,
                            'ajax' => [
                                'method'   => 'POST',
                                'url'      => '/admin/crud/list',
                                'dataType' => 'json',
                                'data'     => new JsExpression("function(params) { return {q:params.term, name:'$name', table:'$tableName', where:'$condition'}; }")
                            ],
                            'escapeMarkup'      => new JsExpression('function(markup) { return markup; }'),
                            'templateResult'    => new JsExpression('function(item) { return item.text; }'),
                            'templateSelection' => new JsExpression('function(item) { return item.text; }'),
                        ],
                    ]);
                    break;
                case 'relatedManyAjax':
                    // TODO: Для больших списков со связью через таблицу с ajax-загрузкой
                    // $item - элемент из массива formFields
                    $relation = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.relations.'. $item[0], []);
                    if (!$relation) {
                        dd('Не заданий зв\'язок "$relation"'.$relation);
                    }
                    $relTableName = $relation['table'];
                    if (!$relTableName) {
                        dd('Не задана таблиця в зв\'язку "$relTableName"');
                    }

                    $className = 'ripaym1970\autocrud\models\crud\\' . ucfirst($relTableName) . 'Model';

                    // Уточним
                    $name = $item['name'] ?? 'name';
                    $fieldName = $row[0][0];
                    $relName   = $item[0];
                    $condition = $item['condition'] ?? [];

                    $existingNames = [];
                    $existingIds   = [];

                    // Получаем IDs привязанных моделей
                    $ids = ArrayHelper::getColumn($model->$relName, 'id');
                    //dd($relation, $relName, $relTableName, $ids, $className, $model->$relName);
                    if ($ids) {
                        /** @var CrudModel $className */
                        $names = $className::listing();
                        $existingNames = [];
                        foreach ($ids as $key) {
                            $existingNames[$key] = $names[$key];
                        }
                        // Получаем IDs к названиям
                        $existingIds = $existingNames ? array_keys($existingNames) : [];
                    }
                    $field->label(Yiit::t($item['label'] ?? ucfirst($columnName)));
                    echo $field->widget(Select2::class, [
                        'initValueText' => $existingNames,
                        'showToggleAll' => false,
                        'options'       => [
                            'value'       => $existingIds,
                            'multiple'    => true,
                            'placeholder' => Yiit::t('Виберіть...'),
                        ],
                        'language'      => Yii::$app->language,
                        'pluginOptions' => [
                            //'tags'             => true,
                            'allowClear'         => true,
                            'minimumInputLength' => 0,
                            'ajax' => [
                                'method'   => 'POST',
                                'url'      => '/admin/crud/list',
                                'dataType' => 'json',
                                'data'     => new JsExpression("function(params) { return {q:params.term, name:'$name', table:'$relTableName'}; }")
                            ],
                            'escapeMarkup'      => new JsExpression('function(markup) { return markup; }'),
                            'templateResult'    => new JsExpression('function(item) { return item.text; }'),
                            'templateSelection' => new JsExpression('function(item) { return item.text; }'),
                        ],
                    ]);
                    break;
                default:
                    //  case 'password':
                    //  case 'text':
                    // Добавим классы для JS-генерации `slug`
                    $defaultOptions = ['class' => 'form-control',];
                    if (in_array($columnName, ['name', 'slug'])) {
                        $defaultOptions = ['class' => 'form-control ' . $columnName,];
                    }
                    echo $field->textInput(ArrayHelper::merge(['maxlength' => ($item['max'] ?? true), 'type' => $type], $defaultOptions));
            }
            echo Html::endTag('div');
        }
    }
    echo Html::endTag('div');
}
?>
<div class="form-group">
    <?= Html::submitButton(Yiit::t('Зберегти'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
</div>
<?php
ActiveForm::end(); ?>

<?php
// Если есть изображения к модели
$images = $model->images ?? [];
if ($images) { ?>
    <div id="images" class="box">
        <div class="box-header with-border"><?=Yiit::t('Посилання на зображення. Тут тільки беремо посилання для вставки в інформіцію.')?></div>
        <div class="box-body">
            <div class="row">
                <?php
                foreach ($images as $key => $image) { ?>
                    <div class="col-md-2 col-xs-3" style="text-align: center">
                        <div>
                            <?php
                            /* @var ImageUploadBehavior $image */
                            echo Html::a(
                                Html::img($image->getThumbFileUrl('file')),
                                $image->getUploadedFileUrl('file'),
                                ['class' => 'thumbnail', 'target' => '_blank']
                            );
                            echo '/uploads/original/' . $table . '/'.$model->id.'/' . $image->file;
                            ?>
                        </div>
                    </div>
                <?php
                } ?>
            </div>
        </div>
    </div>
<?php
} ?>

<script>
    document.onreadystatechange = function () {
        if (document.readyState === 'complete') {
            let name = document.getElementsByClassName('name')[0];
            let slug = document.getElementsByClassName('slug')[0];
            if (name && slug.value.length < 1) {
                function nameblur() {
                    slug.value = getSlug(name.value);
                    slug.dataset.value = slug.value;
                }
                name.addEventListener('blur', nameblur);

                var getSlug = function(str) {
                    str = str.replace(/^\s+|\s+$/g, ''); // trim
                    str = str.toLowerCase();
                    // remove accents, swap ñ for n, etc
                    let from = "їiабвгдеёжзийклмнопрстуфхцчшщъьэюяÁÄÂÀÃÅČÇĆĎÉĚËÈÊẼĔȆĞÍÌÎÏİŇÑÓÖÒÔÕØŘŔŠŞŤÚŮÜÙÛÝŸŽáäâàãåčçćďéěëèêẽĕȇğíìîïıňñóöòôõøðřŕšşťúůüùûýÿžþÞĐđßÆa·/_,:;";
                    let to   = "iiabvgdeegziyklmnoprstufhcccc--euyAAAAAACCCDEEEEEEEEGIIIIINNOOOOOORRSSTUUUUUYYZaaaaaacccdeeeeeeeegiiiiinnooooooorrsstuuuuuyyzbBDdBAa------";
                    for (let i=0, l=from.length ; i<l ; i++) {
                        str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
                    }
                    str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
                             .replace(/\s+/g, '-') // collapse whitespace and replace by -
                             .replace(/-+/g, '-'); // collapse dashes
                    return str;
                };
            }
        }
    }
</script>
