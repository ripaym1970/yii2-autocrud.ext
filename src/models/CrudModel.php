<?php

namespace ripaym1970\autocrud\models;

use Closure;
use ripaym1970\autocrud\models\behaviors\NestedSetsBehavior;
use ripaym1970\autocrud\models\behaviors\SynonymsBehavior;
use paulzi\adjacencyList\AdjacencyListBehavior;
use ripaym1970\autocrud\components\widgets\SaveRelations\SaveRelationsTrait;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\Model;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii2tech\ar\position\PositionBehavior;

/**
 * @property ActiveQuery images
 * @property string      mainImage
 * @property string      poster
 */
class CrudModel extends ActiveRecord
{
    use SaveRelationsTrait;
    //use \ripaym1970\autocrud\components\behaviors\RelationTrait;

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE   = 1;

    private $_fields = [];

    public static function tableName()
    {
        // need use ripaym1970\autocrud\models\crud\{Table}Model for real tables
    }

    public static function autoload($className)
    {
        if (preg_match('|ripaym1970\autocrud\\\\models\\\\crud\\\\([a-z_]+)Model|ism', $className, $match)) {
            $tableName = strtolower($match[1]);
            //$classNameModel = Inflector::id2camel($match[1], '_') . 'Model';
            $classNameModel = $match[1] . 'Model';
            $classNameImage = $match[1] . '_imageModel';
            //dd([$className, $tableName, $classNameModel]);
            $static = 'static'; // чтобы не выдавало ошибку ниже

            if (isset(Yii::$app->params['tables'][$tableName])) {
                $const = '';
                if (isset(Yii::$app->params['tables'][$tableName]['const'])) {
                    foreach (Yii::$app->params['tables'][$tableName]['const'] as $key => $item) {
                        $const .= 'const ' . $key . ' = ' . $item . ';';
                    }
                }

                $useBlock = '';
                $imagesBlock = '';
                if (true && str_contains($tableName, '_image')) {
                    $imagesBlock = '
    /**
     * @param UploadedFile $file
     *
     * @return ImageModelInterface|self
     */
    public static function create(UploadedFile $file)
    {
        /** @var ImageModelInterface $image2 */
        $image2 = new self();
        $image2->file = $file;
        return $image2;
    }

    /**
     * Устанавливает позицию отображения для моделей "*_imageModel"
     *
     * @param int $sort
     *
     * @return void
     */
    public function setSort($sort): void
    {
        $this->sort = $sort;
    }

    /**
     * Проверка записи на совпадение ID для моделей "*_imageModel"
     *
     * @param int $imageId
     *
     * @return bool
     */
    public function isIdEqualTo($imageId): bool
    {
        return $this->id == $imageId;
    }
                    ';
                    $useBlock = 'use yii\web\UploadedFile;';
                }

                // Получим связи
                $tableRelations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.relations', []);
                if (true && isset($tableRelations['images'])) {
                    $imagesBlock = '
    //==================================================================
    // Images
    //==================================================================
    /**
     * @return ActiveQuery
     * @throws \Exception
     */
    //public function getImages()
    //{
    //    $classNameImage = "ripaym1970\autocrud\models\crud\\' . $classNameImage . '";
    //
    //    return $this->hasMany($classNameImage, ["' . $tableName . '_id" => "id"])
    //        ->orderBy("sort");
    //}

    /**
     * Обновляем сортировку
     *
     * @param array $images
     */
    private function updateSort(array $images): void
    {
        /** @var ActiveRecord $image */
        foreach ($images as $i => $image) {
            $image->setSort($i);
        }
        $this->images = $images;

        // Сохраняем все изображения
        //$this->populateRelation("images", reset($images));
    }

    /**
     * @param $image
     */
    public function addImage($image)
    {
        $images = $this->images;
        $images[] = $image;

        // Обновляем сортировку
        $this->updateSort((array)$images);
    }

    /**
     * @param ImagesForm $imagesForm
     *
     * @return bool
     */
    public function addImages(ImagesForm $imagesForm): bool
    {
        $classNameImage = "ripaym1970\autocrud\models\crud\\' . $classNameImage . '";
        foreach ($imagesForm->files as $file) {
            /** @var ImageModelInterface $image */
            $image = $classNameImage::create($file);
            $this->addImage($image);
        }
        if (!$this->save()) {
            dd($this->errors);
        }
        return true;
    }

    /**
     * @param string|null $size
     *
     * @return string
     */
    public function getPoster(?string $size = "thumb")
    {
        $src = "/img/default/load-avatar.jpg";
        /** @var ImageUploadBehavior $mainImage */
        $mainImage = $this->mainImage;
        if ($mainImage) {
            $src1 = \yii\helpers\Url::to($mainImage->getThumbFileUrl("file", $size));
            // Если нет в размере, то показываем оригинал
            if (!$src1) {
                $src1 = \yii\helpers\Url::to($mainImage->getImageFileUrl("file"));
            }
            $src = $src1;
        }

        return $src;
    }

    /**
     * @return ActiveQuery
     */
    public function getMainImage()
    {
        $classNameImage = "ripaym1970\autocrud\models\crud\\' . $classNameImage . '";
        return $this->hasOne($classNameImage, ["' . $tableName . '_id" => "id"])
            ->where(["sort" => 0])
            ->orderBy("sort");
    }

    public function removeImage($id): bool
    {
        $images = $this->images;
        foreach ($images as $i => $image) {
            if ($image->isIdEqualTo($id)) {
                unset($images[$i]);
                // Обновляем сортировку
                $this->updateSort((array)$images);
                // Сохраняем с фото
                return $this->save(false);
            }
        }
        return false;
    }

    public function removeImages(): void
    {
        $this->updateSort([]);
        // Сохраняем без фото
        $this->save(false);
    }

    /**
     * @param int $imageId
     */
    public function moveImageUp(int $imageId): void
    {
        /** @var ActiveRecord[] $images */
        $images = $this->images;
        if (!$images) {
            throw new DomainException("Зображень не знайдено");
        }

        if (count($images) == 1) {
            throw new DomainException("Кількість зображень дорівнює 1");
        }

        foreach ($images as $i => $image) {
            if ($image->isIdEqualTo($imageId)) {
                if ($prev = $images[$i - 1] ?? null) {
                    $images[$i - 1] = $image;
                    $images[$i] = $prev;
                    // Обновляем сортировку
                    $this->updateSort($images);
                    // Сохраняем с фото
                    $this->save(false);
                }
                return;
            }
        }
    }

    /**
     * @param int $imageId
     */
    public function moveImageDown(int $imageId): void
    {
        /** @var ActiveRecord[] $images */
        $images = $this->images;
        if (!$images) {
            throw new DomainException("Зображень не знайдено");
        }

        if (count($images) == 1) {
            throw new DomainException("Кількість зображень дорівнює 1");
        }

        foreach ($images as $i => $image) {
            // Если нужное фото
            if ($image->isIdEqualTo($imageId)) {
                // Если есть следующее
                if ($next = $images[$i + 1] ?? null) {
                    // Меняем местами
                    $images[$i]     = $next;
                    $images[$i + 1] = $image;
                    // Обновляем сортировку
                    $this->updateSort($images);
                    // Сохраняем с фото
                    $this->save(false);
                }
                return;
            }
        }
    }

                    ';
                    $useBlock = 'use ripaym1970\autocrud\models\forms\ImagesForm;';
                }

                // Наследуемся от ripaym1970\autocrud\models\CrudModel
                $class = <<<PHP
namespace ripaym1970\autocrud\models\crud;

use ripaym1970\autocrud\models\CrudModel;
{$useBlock}

class {$classNameModel} extends CrudModel
{
    {$const}

    public {$static} function tableName() {
        return '$tableName';
    }

    {$imagesBlock}
}
PHP;
                eval($class);
            }
        }
    }

    public function rules()
    {
        $tableName = static::tableName();
        $rules = [];

        $columns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.columns', []);
        foreach ($columns as $columnName => $columnParams) {
            $columnType = $columnParams['type'] ?? 'text'; //dd("У поля '$columnName' не задан тип");
            if ($columnType == 'image') {
                if (isset($columnParams['required'])) {
                    //dd($columnParams);
                    $rules[] = [[$columnName], 'required'];
                }
                $rules[] = [[$columnName], 'file', 'mimeTypes' => ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif'], 'skipOnEmpty' => !$this->isNewRecord];
                continue;
            }
            //if (!true && $columnName == 'location_id') {
            //    continue;
            //}

            if (in_array($columnType, ['text', 'date', 'datetime', 'time', 'enum'])) {
                $columnType = 'string';
            }
            if ($columnType === 'bigint') {
                $columnType = 'integer';
            }
            if ($columnType === 'enumint') {
                $columnType = 'integer';
            }
            if (strpos($columnType, 'decimal') === 0) {
                $columnType = 'double';
            }
            $rules[] = [[$columnName], $columnType];
            if (!empty($columnParams['required'])) {
                if (!empty($columnParams['required_if'])) {
                    if (call_user_func($columnParams['required_if'], $this)) {
                        $rules[] = [[$columnName], 'required'];
                    }
                } else {
                    $rules[] = [[$columnName], 'required'];
                }
            }
            if (!empty($columnParams['unique'])) {
                // Если уникальный, то и обязательный
                $rules[] = [[$columnName], 'required'];
                $rules[] = [[$columnName], 'unique', 'message' => $columnParams['message'] ?? null];
                //if (false) {
                //    [['username', 'email'], 'unique', 'targetClass' => UserAdmin::class, 'targetAttribute' => ['username', 'email'], 'message' => Yiit::t('Такі "Нікнейм" та "E-mail" вже заняті')],
                //} else {
                //    $rules[] = [[$columnName], 'unique'];
                //}
            }
            if (isset($columnParams['default'])) {
                $rules[] = [[$columnName], 'default', 'value' => $columnParams['default']];
            }
            if (isset($columnParams['source'])) {
                if ($columnParams['source'] === 'settings' &&
                    ((isset($columnParams['key']) && $items = Yii::$app->settings->get('default', $this->{$columnParams['key']} . 's'))
                        || (isset($columnParams['setting']) && $items = Yii::$app->settings->get('default', $columnParams['setting'])))
                ) {
                    $columnType = 'dropdown';
                    $columnParams['items'] = $items;
                }
            }
            if (in_array($columnType, ['enumint', 'enum'])) {
                $rules[] = [[$columnName], 'in', 'range' => array_keys($columnParams['items'])];
            }
        }

        $tableName = static::tableName();
        // Получим связи
        $tableRelations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.relations', []);
        $relations = prepareRelations($tableRelations);
        foreach ($relations as $relation => $relationParams) {
            if ($relationParams instanceof Closure) {
                continue;
            }

            // Если это переводы
            if ($relation == 'translations') {
                $attributesTranslate = array_keys(
                    ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.behaviors.translations.variationAttributeDefaultValueMap', [])
                );
                foreach ($attributesTranslate as $attributeTranslate) {
                    // Здесь все поля string
                    $rules[] = [[$attributeTranslate], 'string'];
                }
            }
        }

        // Для каждого спец поля
        foreach (ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.fields', []) as $field) {
            $rules[] = [[$field], 'safe'];
        }

        $rules = ArrayHelper::merge(
            $rules,
            ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.rules', [])
        );
        //d($tableName, $rules);
        return $rules;
    }

    public function attributeLabels()
    {
        $out = [];

        $columns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.columns', []);
        foreach ($columns as $columnName => $columnParams) {
            $out[$columnName] = empty($columnParams['comment'])
                ? (ucfirst($columnName))
                : Yiit::t($columnParams['comment']);
        }

        $columns = ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '_translation.columns', []);
        foreach ($columns as $columnName => $columnParams) {
            $out[$columnName] = empty($columnParams['comment'])
                ? (ucfirst($columnName))
                : Yiit::t($columnParams['comment']);
        }

        return $out;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        if (!isset($scenarios['create'])) {
            $scenarios['create'] = $scenarios[self::SCENARIO_DEFAULT];
        }
        return $scenarios;
    }

    public function behaviors()
    {
        $behaviors = [];

        $attributes = $this->attributes();

        if (in_array('updated_at', $attributes)
            // || in_array('created_at', $attributes)
        ) {
            $behaviors['timestampBehavior'] = [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
            ];
        }
        // TODO: Если у поля есть связь, то не работает blameableBehavior - можно просто присвоить
        // TODO: Если у поля нет  связи, то не работают gridColumns и viewAttributes, что более критично
        //if (in_array('updated_by', $attributes)) {
        //    $behaviors['blameableBehavior'] = [
        //        'class' => BlameableBehavior::class,
        //        'attributes' => [
        //            BaseActiveRecord::EVENT_BEFORE_INSERT => 'created_by',
        //            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'updated_by',
        //        ],
        //    ];
        //}
        if (in_array('lft', $attributes)) {
            $behaviors['nestedSetsBehavior'] = [
                'class' => NestedSetsBehavior::class,
            ];
        }
        if (in_array('parent_id', $attributes) && in_array('sort', $attributes)) {
            $behaviors['adjacencyListBehavior'] = [
                'class' => AdjacencyListBehavior::class,
                //'sortable' => ['parent_id', 'updated_at'],
                'sortable' => false,
            ];
        }
        if (in_array('index', $attributes)) {
            $groupAttributes = [];
            foreach (['project_category_id', 'filter_id', 'feature_id', 'banner_id', 'model_class_name', 'model_id', 'level'] as $attribute) {
                if (in_array($attribute, $attributes)) {
                    $groupAttributes[] = $attribute;
                }
            }
            $behaviors['positionBehavior'] = [
                'class'             => PositionBehavior::class,
                'positionAttribute' => 'index',
                'groupAttributes'   => $groupAttributes,
            ];
        }
        if (in_array('slug', $attributes)) {
            $attribute = '';
            foreach (['title', 'name', 'plural_name', 'singular_name', 'value'] as $attr) {
                if (in_array($attr, $attributes)) {
                    $attribute = $attr;
                    break;
                }
            }
            if ($attribute) {
                //dd('есть атрибут '. $attribute);
                $behaviors['sluggableBehavior'] = [
                    'class'         => SluggableBehavior::class,
                    'attribute'     => function ($model) use ($attribute) {
                        if (ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.behaviors.translations', false)) {
                            //dd($model->getVariationModels()[0]);
                            return $model->getVariationModels()[0]->{$attribute};
                        }

                        return $model->{$attribute};
                    },
                    'slugAttribute' => 'slug',
                    'immutable'     => false,  // Не автообновлять - нет
                    'ensureUnique'  => true,   // генерировать уникальный - да
                    'skipOnEmpty'   => true,   // пропускать для пустого атрибута - да
                    'uniqueSlugGenerator' => function ($baseSlug, $iteration) {
                        if (!$baseSlug) {
                            $baseSlug = 'default-slug';
                        }

                        return $baseSlug . '-' . ($iteration + 1);
                    },
                ];
            }
        }
        //if (in_array('slug', $attributes)) {
        //    $attribute = '';
        //    foreach (['title', 'name', 'plural_name', 'singular_name', 'value'] as $attr) {
        //        if (in_array($attr, $attributes)) {
        //            $attribute = $attr;
        //            break;
        //        }
        //    }
        //    if ($attribute) {
        //        $behaviors['sluggableBehavior'] = [
        //            'class'         => SluggableBehavior::class,
        //            'slugAttribute' => 'slug',
        //            'immutable'     => true,
        //            'attribute'     => function($model) use ($attribute) {
        //                if (ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.behaviors.translations', false)) {
        //                    return $model->getVariationModels()[0]->{$attribute};
        //                }
        //
        //                return $model->{$attribute};
        //            },
        //        ];
        //    }
        //}
        if (in_array('synonyms', $attributes)) {
            $behaviors['synonymsBehavior'] = [
                'class' => SynonymsBehavior::class,
            ];
        }

        $tableName = static::tableName();

        //// Получим связи
        //$tableRelations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.relations', []);
        //$relations = prepareRelations($tableRelations);
        //// Если есть таблица с изображениями
        //if (isset(Yii::$app->params['tables'][$tableName . '_image'])) {
        //    $relations['images'] = 'images'; // Location_imageModel[]
        //}
        // Получим связи
        //$tableSaveRelations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . 'behaviors.saveRelations', []);
        //$saveRelations = prepareRelations($tableSaveRelations);
        //if ($saveRelations) {
        //    //echo '<pre>';var_dump('array_keys($relations)=',array_keys($relations));echo '</pre>';exit();
        //    $behaviors['saveRelations'] = [
        //        'class'     => SaveRelationsBehavior::class,
        //        'relations' => array_keys($saveRelations),
        //    ];
        //}

        $behaviors =  ArrayHelper::merge(
            $behaviors,
            ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.behaviors', [])
        );

        //if ($tableName == 'quest_image') {
        if (!true && $tableName == 'location') {
        //if ($tableName == 'location_image') {
            dd($tableName, $behaviors);
        }

        return $behaviors;
    }

    public function beforeDelete()
    {
        $beforeDelete = ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.beforeDelete');
        if (parent::beforeDelete() && $beforeDelete) {
            return $beforeDelete($this);
        }
        return true;
    }

    public function afterDelete()
    {
        $afterDelete = ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.afterDelete');
        if (parent::afterDelete() && $afterDelete) {
            $afterDelete($this);
        }
    }

    public function beforeValidate()
    {
        $beforeValidate = ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.beforeValidate');
        if (parent::beforeValidate() && $beforeValidate) {
            return $beforeValidate($this);
        }
        return true;
    }

    public function beforeSave($insert)
    {
        $parentBeforeSave = true;
        if ($insert) {
            $parentBeforeSave = parent::beforeSave($insert);
        }
        $beforeSave = ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.beforeSave');
        if ($parentBeforeSave && $beforeSave) {
            return $beforeSave($this);
        }
        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $afterSave = ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.afterSave');
        if ($afterSave) {
            $afterSave($insert, $changedAttributes);
        }
    }

    public function afterFind()
    {
        parent::afterFind();
        $afterFind = ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.afterFind');
        if ($afterFind) {
            $afterFind($this);
        }
    }

    public function getAttributeLabel($attribute)
    {
        return Yiit::t(parent::getAttributeLabel($attribute));
    }

    /**
     * Срабатывает каждый раз при:
     * - $modelImages = $model->images;       получении связанных записей
     * - $model->images = [] / $modelImages;  удалении / добавлении
     * - $model->save() = [];                 сохранении модели
     *
     * @param string $name
     * @param array  $params
     *
     * @return mixed|ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function __call($name, $params)
    {
        $dbg = !true;

        //$dbg
        //&& d('__call ', $name, $params)
        //&& $name == 'phones' && d('__call ', $name, $params);
        // Если связь (перввые символы get)
        if (strpos($name, 'get') === 0) {
            //dd(substr($name, 3));
            $relation = ArrayHelper::getValue(
                Yii::$app->params,
                'tables.' . static::tableName() . '.relations.' . substr($name, 3),
                []
            );
            //$dbg
            //&& d($name, substr($name, 3), $params, $relation)
            //&& dd('=',static::tableName(),$name,$relation,$params);
            if ($relation) {
                //$dbg && d($name, substr($name, 3), $params, $relation);
                if ($relation instanceof Closure) {
                    //dd('Closure');
                    return call_user_func($relation, $this);
                }
                $className = '\ripaym1970\autocrud\models\crud\\' . ucfirst($relation['table']) . 'Model';

                //$dbg
                //&& $relation['table'] == 'quest_image'
                //&& dd($name,$relation,$className);

                $relationAttribute = $relation['attribute'] ?? $relation['table'] . '_id';
//dd($relationAttribute);
                // Если нет via-таблицы
                if (empty($relation['via'])) {
                    //$dbg && dd('Нет via');
                    $isMultiple = $relation['multiple'] ?? false; // если нет 'multiple', то $isMultiple = false;
                    $sort = $relation['sort'] ?? false; // если нет 'сортировки', то $isSort = false;

                    //$dbg = true;
                    //$dbg && d($relationAttribute, $relationInverse, $isMultiple, $relation, $link);

                    // Инвертированный запрос для HasMany
                    $link = [$relationAttribute => 'id'];
                    if ($isMultiple) {
                        if ($sort) {
                            $query = $this->hasMany($className, $link)->orderBy($sort);
                        } else {
                            $query = $this->hasMany($className, $link);
                        }
                    } else {
                        $relationInverse = $relation['inverse'] ?? false;
                        if (!$relationInverse) {
                            $link = ['id' => $relationAttribute];
                        }
                        $query = $this->hasOne($className, $link);
                    }

                    if (isset($relation['condition'])) {
                        foreach ($relation['condition'] as $key => $val) {
                            if (is_callable($val)) {
                                $relation['condition'][$key] = call_user_func($val);
                            }
                        }
                        $query->andOnCondition($relation['condition']);
                    }
                } else {
                    //$dbg = true;
                    //$dbg
                    //&& d('Есть via-таблица')
                    //&& d($className, $relation['table'] . '_id', $relation['attribute'], $relation['via']);
                    // Соединяем 2 метода в один запрос:
                    // 1й $via   = $this->hasMany($relation['via'], [$relation['attribute'] => 'id']); // Связываем услуги с промежуточной
                    // 2q $query = $this->hasMany($className, ['id' => $relation['attribute']])->viaTable($via); // Связываем промежуточную с городами

                    $query = $this->hasMany($className, ['id' => $relation['table'] . '_id'])
                        ->viaTable($relation['via'], [$relation['attribute'] => 'id']);
                }
                //$dbg = true;
                //$dbg
                //&& d($query);
                //&& $relation['table'] == 'tag'
                //&& d($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);

                return $query;
            //} else {
            //    dd('! $relation');
            }
        }
        //dd('заглушку');

        return parent::__call($name, $params); // Изменить автоматически созданную заглушку
    }

    public function __set($name, $value)
    {
        $dbg = !true;
        //$dbg && $name == 'phones' && d('__set ', $name, $value);
        $dbg && $name == 'phones' && d('__set ', $name);

        // Если поле из спец списка полей
        if (
            in_array(
                $name,
                ArrayHelper::getValue(
                    Yii::$app->params,
                    'tables.' . static::tableName() . '.fields',
                    []
                ),
                true
            )
        ) {
            $this->_fields[$name] = $value;
            return;
        }

        // Не все связи нуждаются в спец сохранении
        //if (
        //    !in_array(
        //        $name,
        //        ArrayHelper::getValue(
        //            Yii::$app->params,
        //            'tables.' . static::tableName() . '.saveRelations',
        //            []
        //        ),
        //        true
        //    )
        //) {
        //    $dbg && $name == 'phones' && d('Не все связи нуждаются в спец сохранении', $name);
        //    //$this->_fields[$name] = $value;
        //    return;
        //}


        $isRelation = ArrayHelper::getValue(
            Yii::$app->params,
            'tables.' . static::tableName() . '.relations.' . $name,
            false
        );

        // Если есть связь
        if (!true && $isRelation) {
            $dbg && $name == 'phones' && d('$isRelation ', $name);
            $relation = $this->getRelation($name);
            //d($relation->prepare(\Yii::$app->db->queryBuilder)->createCommand()->rawSql);
            $modelClass = $relation->modelClass;
            //d($modelClass, $relation->multiple);
            if ($relation->multiple === true) {
                $dbg && $name == 'phones' && d('multiple ', $name);
                if (!is_array($value)) {
                    if (!empty($value)) {
                        $value = [$value];
                    } else {
                        $value = [];
                    }
                }
                //d($modelClass, $relation->multiple, count($value));
                // Получим текущие привязанные записи с ключами ID
                $relatedRecords = ArrayHelper::index($this->{$name}, 'id');
                //dd('Привязано записей '. count($relatedModels),$relatedModels);

                // Сюда будем складывать записи которые надо привязать
                $needToLinkRecords = [];
                // По каждой записи
                foreach ($value as $i => $id) {
                    // Пытаемся получить или создать модель
                    $record = $this->findOrCreateRelationModel($name, $modelClass, $id);
                    //$record && d('новая запись', $record->attributes, $name, $modelClass, $id->attributes);
                    //!$record && d('не нашли и не создали запись', $name, $modelClass, $id->attributes);
                    if ($record) {
                        $needToLinkRecords[] = $record;
                        $value[$i] = $record->id;

                        // Удалим из текущих привязанных записей
                        unset($relatedRecords[$record->id]);

                        // Если есть via-таблица (Many-To-Many)
                        //dd($name, $relation->via);
                        if ($relation->via) {
                            $record->save();
                            $this->save();
                        }
                        // Привяжем запись к модели
                        $this->link($name, $record);
                    //} else {
                    //    // иначе - добавляемая запись
                    //    $needToLinkRecords[] = $id;
                    //    // Привяжем запись к модели
                    //    $this->link($name, $id);
                    }
                }

                // Каждую оставшуюся запись отвяжем от модели
                foreach ($relatedRecords as $relatedRecord) {
                    //d('Отвязываем ',$relatedRecord->attributes);
                    $this->unlink($name, $relatedRecord, true);
                }

                // Привяжем записи к модели
                $this->populateRelation($name, $needToLinkRecords);
            } else {
                // Если связи нет
                $relatedRecord = $this->{$name};
                // Пытаемся получить или создать запись
                $record = $this->findOrCreateRelationModel($name, $modelClass, $value);
                if ($record) {
                    // Привяжем запись к модели
                    $this->populateRelation($name, $record);
                    // Если есть via-таблица (Many-To-Many)
                    if ($relation->via) {
                        $record->save();
                        $this->save();
                    }
                    // Привяжем запись к модели
                    $this->link($name, $record);
                }
                // Если есть привязанная запись и (нет или она это сама запись)
                $isLinking = $relatedRecord
                    && (!$record || $record->id !== $relatedRecord->id);
                // Если надо привязать
                if ($isLinking) {
                    $this->link($name, $relatedRecord, true);
                }
            }
            return;
        }

        //dd($name, $value);
        parent::__set($name, $value);
    }

    /**
     * Находим или создаем связанную модель
     *
     * @param string $nameRelation
     * @param ActiveRecord|string $modelClass
     * @param int|string|array $value
     *
     * @return CrudModel|mixed
     * @throws \Exception
     */
    private function findOrCreateRelationModel($nameRelation, $modelClass, $value)
    {
        $model = null;
        //d($nameRelation, $modelClass, $value);

        $dbg = !true;
        $dbg && d('findOrCreateRelationModel');


        //// Если значение и оно массив
        //if ($value && is_array($value)) {
        //    // Если у массива есть ID
        //    if (!empty($value['id'])) {
        //        // Получим запись по ID
        //        $model = $modelClass::findOne(['id' => $value['id']]);
        //        $model->setAttributes($value);
        //    } else {
        //        // Иначе у массива нет ID
        //        $model = new $modelClass($value);
        //        // Надо ли сохранять?
        //        //if (!$model->save(false)) {
        //        //    dd($model->errors);
        //        //}
        //    }
        //    return $model;
        //}

        //// Если значение и оно объект
        //if ($value && is_object($value)) {
        //    // Если у объекта нет ID
        //    if (!$value->id) {
        //        if (!$value->save(false)) {
        //            dd($value->errors);
        //        }
        //    }
        //    return $value;
        //}

        // Если значение и оно число
        if ($value && is_numeric($value)) {
            //$dbg && d('Получим запись по ID');
            // Получим запись по ID
            $model = $modelClass::findOne(['id' => $value]);
            if ($model) {
                return $model;
            }
        }

        // Если значение и оно scalar (строка, ?)
        if ($value && is_scalar($value)) {
            // Попытаемся найти по названию
            $query = $modelClass::find()
                ->where([
                    'LOWER(`title`)' => mb_strtolower($value),
                ]);
            $translations = false;
            // Если есть вариации
            if (ArrayHelper::getValue(Yii::$app->params, 'tables.' . static::tableName() . '.behaviors.translations', false)) {
                // Приджойним вариации
                $query->joinWith('translations');
                $translations = true;
            }
            // Получим модель
            $model = $query->one();
            // Если НЕ нашли
            if (!$model) {
                // Создадим новую
                $model = new $modelClass();
                // Если нет вариаций, то пытаемся сохранить в модель
                $model->title = $value;

                // Получим поля связи (не юзаем?)
                $attributes = ArrayHelper::getValue(
                    Yii::$app->params,
                    'tables.' . static::tableName() . '.relations.' . $nameRelation . '.attributes',
                    []
                );
                if ($attributes) {
                    $model->setAttributes($attributes);
                }

                // Если есть вариации
                if ($translations) {
                    // Заполним их
                    Model::loadMultiple(
                        // Получим модели вариаций
                        $model->getVariationModels(),
                        [
                            ['title' => $value],
                            ['title' => $value], // не понимаю почему два раза?
                        ],
                        '' // этот параметр нужен как '', чтобы при загрузке проверки на null пропускать
                    );
                }
            }
        }
        !$model && d('Что-то пошло не так?', $nameRelation, $modelClass, $value);
        return $model;
    }

    public function __get($name)
    {
        if (strpos($name, '-') === 0) {
            return -parent::__get(substr($name, 1));
        }

        $tableName = static::tableName();
        // У city_translation нет настроек formFields
        if (in_array($name, ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.fields', []), true)) {
            return $this->_fields[$name] ?? null;
        }

        // Если есть связь по имени поля
        if ($relation = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.relations.' . $name)) {
            //d($tableName, $name);
            if ($relation instanceof Closure) {
                return call_user_func($relation, $this)->one();
            }
            $related = $this->getRelatedRecords();
            if (isset($related[$name])) {
                return $related[$name];
            }
            return !empty($relation['multiple'])
                ? $this->__call('get' . $name, [])->all()
                : $this->__call('get' . $name, [])->one();
        }

        // Получим связи
        $tableRelations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.relations', []);
        $relations = prepareRelations($tableRelations);
        foreach ($relations as $relation) {
            if ($relation instanceof Closure) {
                continue;
            }
            if (!isset($relation['attribute'])) {
                dd('Отсутствует attribute =', $tableName, $name, $relation);
            }
            if ($name === $relation['attribute']) {
                if (in_array($name, $this->attributes(), true)) {
                    return parent::__get($name);
                }
                return null;
            }
        }
        return parent::__get($name);
    }

    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        $tableName = static::tableName();
        if (in_array($name, ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.fields', []), true)) {
            return true;
        }

        // Получим связи
        $tableRelations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.relations', []);
        $relations = prepareRelations($tableRelations);
        foreach ($relations as $relation) {
            if ($relation instanceof Closure) {
                continue;
            }
            if (!isset($relation['attribute'])) {
                dd('Отсутствует attribute =', $tableName, $name, $relation);
            }
            if ($name === $relation['attribute']) {
                return true;
            }
        }
        return parent::canGetProperty($name, $checkVars, $checkBehaviors); // TODO: Change the autogenerated stub
    }

    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        $tableName = static::tableName();
        if (in_array($name, ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.fields', []), true)) {
            return true;
        }

        // Получим связи
        $tableRelations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $tableName . '.relations', []);
        $relations = prepareRelations($tableRelations);
        foreach ($relations as $relation) {
            if ($relation instanceof Closure) {
                continue;
            }
            if (!isset($relation['attribute'])) {
                dd('Отсутствует attribute =', $tableName, $name, $relation);
            }
            if ($name === $relation['attribute']) {
                return true;
            }
        }
        return parent::canGetProperty($name, $checkVars, $checkBehaviors); // TODO: Change the autogenerated stub
    }

    public static function activeIds($name = 'activeIds')
    {
        $name = static::tableName() . $name;
        $cache = Yii::$app->cache;
        return $cache->getOrSet($name, function () {
            return static::find()
                ->select([
                    'id',
                ])
                ->andWhere([
                    'active' => true,
                ])
                ->column();
        }, 3600);
    }

    /**
     * @param array  $condition
     * @param string $name
     * @param int    $id
     *
     * @return array
     */
    public static function listing($condition = [], $name = 'name', $id = 'id'): array
    {
        // TODO: Если не обновляется, но кеш есть - очистите кеш!
        //Yii::$app->cache->flush();

        $listName = Yii::$app->language . '_' . static::tableName();
        //echo '<pre>';var_dump('$listName=',$listName);echo '</pre>';exit();
        $cache = Yii::$app->cache;
        return $cache->getOrSet($listName, function () use ($condition, $name, $id) {
            $query = static::find()
                ->alias('self')
                ->select([
                    $name,
                    'self.' . $id,
                ])
                ->andWhere($condition)
                //->andWhere([
                //    'self.active' => true, // добавлять в $condition
                //])
                ->andWhere([
                    'AND',
                    ['NOT', [$name => '']],
                    ['NOT', [$name => null]],
                ])
                ->indexBy('self.' . $id)
                ->orderBy($name)
            ;

            if (isset((new static())->behaviors()['translations'])) {
                $query->joinWith(['defaultTranslation'], false);
            }
            //dd($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);

            return $query
                ->column();
        }, 3600);
    }

    /**
     * @return array
     */
    public static function all(): array
    {
        $query = static::find()
            ->alias('self')
            //->andWhere([
            //    'self.active' => true,
            //])
            ->andWhere(['NOT', ['name' => null]])
            ->orderBy('self.id');

        if (isset((new static())->behaviors()['translations'])) {
            $query->joinWith(['defaultTranslation'], false);
        }

        return ArrayHelper::index($query->all(), 'id');
    }

    /**
     * Массив "по условию с сортировкой с учетом смещения и кол-ва страниц"
     *
     * @param array $condition
     * @param array $order
     * @param int   $pageSize
     * @param int   $pageFirst
     * @param int   $countPages
     *
     * @return array
     */
    public static function getFromTable(
        array $condition = [],
        array $order = [],
        int $pageSize = 10,
        int $pageFirst = 0,
        int $countPages = 1
    ): array
    {
        $query = static::find()
            ->andWhere($condition)  // Условия
            ->orderBy($order)       // Сортировка
        ;
        // Вариации
        if (isset((new static())->behaviors()['translations'])) {
            $query->joinWith('translations');
        }

        //dd($query->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);

        return $query
            ->offset($pageSize * $pageFirst)
            ->limit($pageSize * $countPages)
            ->all();
    }

    /**
     * Кіл-ть записів
     *
     * @param array $where
     *
     * @return int
     */
    public static function getCountFromTable(array $condition = []): int
    {
        $query = static::find()
            ->andWhere($condition)  // Условия
        ;

        if (isset((new static())->behaviors()['translations'])) {
            $query->joinWith('translations');
        }

        return $query->count();
    }

    /**
     * Получения ID объекта по умолчанию
     *
     * @param string $nameDefault
     * @param string $attribute
     *
     * @return mixed|null
     */
    public static function getDefault($nameDefault, $attribute = 'id')
    {
        $defaultValue = Yii::$app->cache->get($nameDefault);
        if (!$defaultValue) {
            $defaultValue = self::find()
                ->select([
                    $attribute,
                ])
                ->andWhere([
                    'default' => 1,
                ])
                ->limit(1)
                ->scalar();
            Yii::$app->cache->set($nameDefault, $defaultValue, 60 * 60 * 24);
        }
        return $defaultValue;
    }

    // Использовать транзакции для SaveRelationsBehavior
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public function getGoogleMapUrl(): string
    {
        $search = urlencode($this->city->name . ', ' . $this->name);
        return 'https://www.google.com/maps/search/?api=1&query=' . $search;
    }
}
