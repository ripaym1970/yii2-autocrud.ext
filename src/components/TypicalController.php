<?php

namespace ripaym1970\autocrud\components;

/**
 * @property array $accessControlRules
 * @property array $verbsControlRules
 */
abstract class TypicalController extends \yii\web\Controller
{
    /**
     * @var string $userAccessComponent should contain the name of app component
     *                                  to authenticate user
     */
    public $userAccessComponent = 'user';
    protected const ALLOW_EVERYBODY_RULE = [
        'allow' => true,
        'roles' => ['?', '@'],
    ];
    protected const RESTRICT_EVERYBODY_RULE = [
        'allow' => false,
        'roles' => ['?', '@'],
    ];
    protected const ALLOW_REGISTERED_USERS_ONLY_RULES = [
        [
            'allow' => true,
            'roles' => ['@'],
        ],
        [
            'allow' => false,
            'roles' => ['?'],
        ],
    ];

    protected function getAccessControlRules(): array
    {
        return self::ALLOW_REGISTERED_USERS_ONLY_RULES;
    }

    protected function getVerbsControlRules(): array
    {
        return [
            'destroy' => ['delete'],
        ];
    }

    public function behaviors()
    {
        $behaviors = [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => $this->verbsControlRules,
            ],
        ];
        if ($this->userAccessComponent) {
            $behaviors['access'] = [
                'class' => \yii\filters\AccessControl::class,
                'rules' => $this->accessControlRules,
                'user' => $this->userAccessComponent,
            ];
        }
        return \yii\helpers\ArrayHelper::merge(
            parent::behaviors(),
            $behaviors
        );
    }
}
