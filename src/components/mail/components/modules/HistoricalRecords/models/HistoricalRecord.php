<?php

namespace ripaym1970\autocrud\components\mail\components\modules\HistoricalRecords\models;

use ripaym1970\autocrud\components\behaviors\Representation;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "historical_records".
 *
 * @property int     id
 * @property string  created_at
 * @property int     action_id
 * @property array   details
 * @property string  owner_class
 * @property int     owner_id
 * @property string  author_class
 * @property int     author_id
 *
 * @property bool    isCreate
 * @property bool    isRemove
 * @property bool    isUpdate
 * @property bool    isMessage
 * @property array   actions
 *
 * @property string  rowActions
 * @property string  viewIcon
 *
 * @property ActiveRecord|null $author
 */
class HistoricalRecord extends ActiveRecord
    implements \ripaym1970\autocrud\components\interfaces\IPolymorphicModel,
               \ripaym1970\autocrud\components\interfaces\IHumanFriendlyName
{
    const ACTION_ID_CREATE = 0;
    const ACTION_ID_REMOVE = 1;
    const ACTION_ID_UPDATE = 2;
    const ACTION_ID_MESSAGE = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'historical_records';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['created_at', 'owner_class', 'owner_id', 'action_id'], 'required'
            ],
            [
                ['owner_id', 'action_id', 'author_id'], 'integer'
            ],
            [
                ['owner_class', 'author_class'],
                'string',
                'max' => 255
            ],
            [
                ['action_id'],
                'in',
                'range' => array_keys($this->actions)
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'           => Yiit::t('ID'),
            'created_at'   => Yiit::t('Created On'),
            'owner_class'  => Yiit::t('Owner Class'),
            'owner_id'     => Yiit::t('Owner ID'),
            'author_class' => Yiit::t('Author Class'),
            'author_id'    => Yiit::t('Author ID'),
            'action_id'    => Yiit::t('Action'),
            'details'      => Yiit::t('Details'),
            'author'       => Yiit::t('Author'),
            'rowActions'   => Yiit::t('Actions'),
        ];
    }

    public function delete()
    {
        throw new \yii\base\UserException('Should not be implemented');
    }

    public function getActions()
    {
        return [
            self::ACTION_ID_CREATE  => Yiit::t('Create'),
            self::ACTION_ID_UPDATE  => Yiit::t('Update'),
            self::ACTION_ID_REMOVE  => Yiit::t('Remove'),
            self::ACTION_ID_MESSAGE => Yiit::t('Message'),
        ];
    }

    public static function getHumanFriendlyName(): string
    {
        return Yiit::t('Historical Records');
    }

    public function getIsCreate()
    {
        return $this->action_id == self::ACTION_ID_CREATE;
    }

    public function getIsRemove()
    {
        return $this->action_id == self::ACTION_ID_REMOVE;
    }

    public function getIsUpdate()
    {
        return $this->action_id == self::ACTION_ID_UPDATE;
    }

    public function getIsMessage()
    {
        return $this->action_id == self::ACTION_ID_MESSAGE;
    }

    public function revert()
    {
        if (!$this->isRemove) {
            throw new \yii\base\UserException(
                'Only removal can be reverted'
            );
        }

        $target = new $this->owner_class([
            $this->owner_class::primaryKey()[0] => $this->owner_id,
        ]);
        \ripaym1970\autocrud\components\Util::setModelAttributes($target, $this->details);
        \ripaym1970\autocrud\components\Util::saveModel($target);
    }

    public function getAuthor(): ?ActiveRecord
    {
        /** @var string|ActiveRecord|null $class */
        $class = $this->author_class;
        $shouldReturn = !$class
            || !class_exists($class)
            || !$this->author_id;
        if ($shouldReturn) {
            return null;
        }
        return $class::findOne($this->author_id);
    }

    public function getViewIcon(): string
    {
        return ''
            . \ripaym1970\autocrud\components\HtmlActions\Icon::dialogView([
                '/historical-records/historical-records/view',
                'id' => $this->id,
            ]);
    }

    public static function buildMessage(ActiveRecord $owner, string $message)
    {
        $model = new self([
            'owner_class'  => 'Message',
            'owner_id'     => 0,
            'author_class' => get_class($owner),
            'author_id'    => $owner->id,
            'action_id'    => self::ACTION_ID_MESSAGE,
            'details'      => [
                'message' => $message,
            ],
        ]);
        \ripaym1970\autocrud\components\Util::saveModel($model);
    }

    public static function clearHistoryByOwnerClass($class)
    {
        Yii::$app->db->createCommand()
            ->delete(
                self::tableName(),
                [
                    'owner_class' => $class,
                ]
            )
            ->execute();
    }

    public static function queryByField(\yii\db\ActiveQuery $query, string $field)
    {
        $query->andWhere(
            [
                'or',
                new \yii\db\Expression(
                    'jsonb_exists(details::jsonb, :field)'
                ),
                new \yii\db\Expression(
                    "jsonb_exists(details::jsonb->'oldAttributes', :field)"
                ),
                new \yii\db\Expression(
                    "jsonb_exists(details::jsonb->'newAttributes', :field)"
                ),
            ],
            [
                'field' => $field,
            ]
        );
    }

    public function latestValueByField(string $field)
    {
        $class = $this->owner_class;
        $value = $this->details[$field] ?? $this->details['newAttributes'][$field] ?? '';
        if (!class_exists($class)) {
            return $value;
        }

        $object = new $class();
        if (!array_key_exists($field, $object->attributes)) {
            return $value;
        }
        $shouldContinue = \ripaym1970\autocrud\components\Util::hasBehavior(
            $object,
            Representation::class,
            false
        );
        if (!$shouldContinue) {
            return $value;
        }
        /** @var Representation $object */
        $object->$field = $value;
        return $object->getRepresentation($field, $object->$field);
    }

    // protected static function gridColumns(): array
    // {
    //     $model = new self();
    //     return [
    //         'id' => Helper::typicalNumberColumn(
    //             $model,
    //             'id'
    //         ),
    //         'created_at' => Helper::typicalDateTimeColumn(
    //             $model,
    //             'created_at'
    //         ),
    //
    //         'action_id' => Helper::typicalNumberColumn(
    //             $model,
    //             'action_id',
    //             [
    //                 'values' => Helper::filterItemsFromArray($model->actions),
    //             ]
    //         ),
    //         'author' => Helper::typicalColumn(
    //             $model,
    //             'author',
    //             [
    //                 'computable' => true,
    //                 'getter' => function (self $x) {
    //                     $author = $x->author;
    //                     if (!$author) {
    //                         return '';
    //                     }
    //                     $implements = \ripaym1970\autocrud\components\Util::implements(
    //                         $author,
    //                         \ripaym1970\autocrud\components\interfaces\IStringRepresentation::class,
    //                         false
    //                     );
    //                     /** @var \ripaym1970\autocrud\components\interfaces\IStringRepresentation $author */
    //                     return $implements
    //                         ? $author->stringRepresentation
    //                         : '';
    //                 },
    //             ]
    //         ),
    //     ];
    // }
    //
    // public function getRowActions()
    // {
    //     $actions = [
    //         $this->viewIcon,
    //     ];
    //
    //     if ($this->isRemove) {
    //         $actions[] = new \ripaym1970\autocrud\components\HtmlActions\Icon([
    //             'url' => [
    //                 '/historical-records/historical-records/restore',
    //                 'id' => $this->id,
    //             ],
    //             'title' => Yiit::t('Restore'),
    //             'icon' => \rmrevin\yii\fontawesome\FAS::icon(
    //                 \rmrevin\yii\fontawesome\FAS::_UNDO
    //             ),
    //             'reloadMode' => \ripaym1970\autocrud\components\HtmlActions\Icon::RELOAD_MODE_RELOAD_PARENT,
    //             'confirmationMessage' => Yiit::t(
    //                 'Are You Sure You Want to Restore Record ?'
    //             ),
    //             'command' => true,
    //             'requiresConfirmation' => true,
    //         ]);
    //     }
    //     return implode(' ', $actions);
    // }
    //
    // public static function generalGridConfig()
    // {
    //     $actions = self::gridColumns();
    //     $model = new self();
    //     return [
    //         'columnTitle' => self::getHumanFriendlyName(),
    //         'columns' => [
    //             $actions['id'],
    //             $actions['created_at'],
    //             Helper::typicalColumn(
    //                 $model,
    //                 'owner_class',
    //                 [
    //                     'values' => Helper::filterItems(
    //                         self::class,
    //                         'owner_class'
    //                     ),
    //                 ]
    //             ),
    //             Helper::typicalNumberColumn(
    //                 $model,
    //                 'owner_id'
    //             ),
    //             Helper::typicalColumn(
    //                 $model,
    //                 'author_class',
    //                 [
    //                     'values' => Helper::filterItems(
    //                         self::class,
    //                         'author_class'
    //                     ),
    //                 ]
    //             ),
    //             $actions['action_id'],
    //             Helper::typicalNumberColumn(
    //                 $model,
    //                 'author_id'
    //             ),
    //             Helper::typicalActionsColumn(
    //                 $model,
    //                 'rowActions',
    //                 [
    //                     'width' => '80px',
    //                 ]
    //             )
    //         ],
    //     ];
    // }
    //
    // public static function gridConfigByOwner()
    // {
    //     $actions = self::gridColumns();
    //     return [
    //         'columnTitle' => self::getHumanFriendlyName(),
    //         'columns' => [
    //             $actions['id'],
    //             $actions['created_at'],
    //             $actions['author'],
    //             $actions['action_id'],
    //             Helper::typicalActionsColumn(
    //                 new self(),
    //                 'actions',
    //                 [
    //                     'width' => '80px',
    //                     'getter' => fn(self $x) => $x->viewIcon,
    //                 ],
    //             ),
    //         ],
    //     ];
    // }
    //
    // public static function gridConfigByField(string $field)
    // {
    //     $actions = self::gridColumns();
    //     return [
    //         'columnTitle' => self::getHumanFriendlyName(),
    //         'columns' => [
    //             $actions['id'],
    //             $actions['created_at'],
    //             $actions['author'],
    //             $actions['action_id'],
    //             Helper::typicalColumn(
    //                 new self(),
    //                 'value',
    //                 [
    //                     'computable' => true,
    //                     'getter' => fn(self $x) => $x->latestValueByField($field),
    //                 ],
    //             ),
    //         ],
    //     ];
    // }
}
