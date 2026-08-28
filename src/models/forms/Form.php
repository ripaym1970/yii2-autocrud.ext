<?php

namespace ripaym1970\autocrud\models\forms;

use kartik\grid\GridView;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "form".
 *
 * @property int    $id
 * @property string $title
 * @property string $url
 * @property string $name
 * @property string $phone
 * @property int    $visited
 * @property int    $created_at
 * @property int    $updated_at
 */
class Form extends \ripaym1970\autocrud\models\forms\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'form';
    }

    public function getTitle()
    {
        return 'Requests';
    }

    public function additionalOptions()
    {
        return [
            'rowOptions' => function ($model) {
                if ($model->visited) {
                    return ['class' => GridView::TYPE_DANGER];
                }
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'url', 'name', 'phone'], 'required'],
            [['visited', 'created_at', 'updated_at'], 'integer'],
            [['title', 'url', 'name', 'phone'], 'string', 'max' => 255],
            [['visited'], 'default', 'value' => true],
        ];
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'timestamp' => [
                'class' => TimestampBehavior::class,
            ],
        ]);
    }

    public function viewData()
    {
        return [
            'title',
            'url',
            'name',
            'phone',
            'created_at:datetime',
        ];
    }

    public function getIndexConfig()
    {
        return [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            'phone',
            [
                'attribute' => 'visited',
                'value'     => function ($model) {
                    return !!$model->visited ? 'Нет' : 'Да';
                },
                'filter'    => [0 => 'Да', 1 => 'Нет'],
            ],
            //'visited:boolean',
            'created_at:datetime',
            ['class' => 'yii\grid\ActionColumn', 'template' => '{view}{delete}'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'title'      => 'Title',
            'url'        => 'Url',
            'name'       => 'Name',
            'phone'      => 'Phone',
            'visited'    => 'Visited',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
