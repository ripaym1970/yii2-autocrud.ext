<?php

namespace ripaym1970\autocrud\models;

use ripaym1970\autocrud\models\crud\Admin_company_assignmentModel;
use ripaym1970\autocrud\models\crud\CompanyModel;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\Exception;
use yii\behaviors\TimestampBehavior;
use yii\db\BaseActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "admin".
 *
 * @property int           id
 * @property string        login
 * @property string        name
 * @property string        username
 * @property string        email
 * @property int           role
 * @property int           status
 * @property int           lastlogin_at
 * @property int           created_at
 * @property int           updated_at
 *
 * @property string         first_name [varchar(255)]
 * @property string         last_name  [varchar(255)]
 * @property string         phone      [varchar(255)]  Телефон
 */
class AdminModel extends \yii2mod\user\models\UserModel
{
    public const STATUS_INACTIVE  = 0;
    public const STATUS_ACTIVE    = 1;

    public const ROLE_USER = 'user';
    public const ROLE_ADMINISTRATOR = 'admin';

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'admin';
    }

    public function behaviors()
    {
        return [
            'timestampBehavior' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
            ],
            //'uploadBehavior' => [
            //    'class'       => UploadBehavior::class,
            //    'path'        => 'images/{model_class_name}/{folder}/{id}-{file_name}.{extension}',
            //    'patterns'    => ['folder' => 'original'],
            //    'properties'  => ['url' => ['getUrl', ['folder' => '{name}']]],
            //    'afterUpload' => function ($model) {
            //        $image = $model->getPath();
            //        $thumb = $model->getPath(false, '_medium');
            //        if (!FileHelper::createDirectory(dirname($thumb))) {
            //            throw new InvalidArgumentException("Directory specified in 'path' attribute doesn't exist or cannot be created.");
            //        }
            //        Image::thumbnail($image, 480, 360)->save($thumb);
            //        $thumb = $model->getPath(false, '_small');
            //        if (!FileHelper::createDirectory(dirname($thumb))) {
            //            throw new InvalidArgumentException("Directory specified in 'path' attribute doesn't exist or cannot be created.");
            //        }
            //        Image::thumbnail($image, 120, 80)->save($thumb);
            //    },
            //],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['login'], 'required'],
            [['role'], 'required'],
            //[['role'], 'string'],
            ['login',    'unique', 'message' => Yii::t('yii2mod.user', 'This email address has already been taken.')],
            ['name', 'unique', 'message' => Yii::t('yii2mod.user', 'This name has already been taken.')],
            ['username', 'unique', 'message' => Yii::t('yii2mod.user', 'This username has already been taken.')],
            //['email', 'email'],
            [['login', 'name', 'username', 'password_hash', /*'email'*/], 'string', 'min' => 3, 'max' => 255],
            [['role'], 'in', 'range' => array_keys(AdminModel::roleList())],
            [['role'], 'default', 'value' => self::ROLE_USER],
            [['status'], 'in', 'range' => array_keys(AdminModel::statusList()),],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['lastlogin_at', 'image', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'password_hash' => Yiit::t('Хеш паролю'),
            'name'          => Yiit::t('Користувач'),
            'username'      => Yiit::t('Користувач'),
            //'email'         => 'E-mail',
            'role'          => Yiit::t('Роль'),
            'status'        => Yiit::t('Статус'),
            'lastlogin_at'  => Yiit::t('Останній логін'),
            'created_at'    => Yiit::t('Створено'),
            'updated_at'    => Yiit::t('Змінено'),
            'image'         => Yiit::t('Файл аватару'),
        ];
    }

    public function beforeValidate(): bool
    {
        if (empty($this->username)) {
            unset($this->username);
        }
        //if (empty($this->email)) {
        //    unset($this->email);
        //}
        return parent::beforeValidate();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        Yii::$app->authManager->revokeAll($this->id);
    }

    public function updateLastLogin()
    {
        $this->updateAttributes(['lastlogin_at' => new Expression('UNIX_TIMESTAMP()')]);
        //$identity = Yii::$app->user->identity;
        //$role = Yii::$app->authManager->getRole($identity->role);
        //dd($role);
        //if (empty($role)) {
        //    $role = Yii::$app->authManager->getRole(self::ROLE_EDITOR);
        //    $identity->role = self::ROLE_EDITOR;
        //}
        //$roles = Yii::$app->authManager->getAssignments($identity->id);
        //if (empty($roles[$role->username])) {
        //    Yii::$app->authManager->assign($role, $identity->id);
        //}
        //unset($roles[$role->username]);
        //if (!empty($roles)) {
        //    foreach ($roles as $roleName => $roleAssignment) {
        //        $role = Yii::$app->authManager->getRole($roleName);
        //        Yii::$app->authManager->revoke($role, $identity->id);
        //    }
        //}
    }

    //public static function lastActivity($event)
    //{
    //    if (!Yii::$app->user->isGuest) {
    //        Yii::$app->user->identity->updateAttributes([
    //            'last_activity' => time(),
    //            'ip' => Yii::$app->request->getUserIP(),
    //        ]);
    //    }
    //}

    /**
     * Validates password
     *
     * @param string $password password to validate
     *
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password): bool
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password_hash);
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return '';
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     *
     * @throws Exception
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
    }

    public static function statusList()
    {
        return [
            self::STATUS_INACTIVE => Yiit::t('Неактивно'), // Проблема с очередностью загрузки таблиц для Yiit::t()
            self::STATUS_ACTIVE   => Yiit::t('Активно'),
        ];
    }

    public static function roleList()
    {
        return [
            self::ROLE_USER          => Yiit::t('Користувач'), // Проблема с очередностью загрузки таблиц для Yiit::t()
            self::ROLE_ADMINISTRATOR => Yiit::t('Адміністратор'),
        ];
    }

    public static function listRoleDescription()
    {
        return [
            self::ROLE_USER          => Yiit::t('Бачить задані Локації та Квести. Може правити лише розклад квестів.'),
            self::ROLE_ADMINISTRATOR => Yiit::t('Адміністратор'),
        ];
    }

    /**
     * @param array $usernames
     *
     * @return bool
     */
    public function isNeededUser($usernames)
    {
        return in_array($this->username, $usernames);
    }
    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role == self::ROLE_ADMINISTRATOR;
    }
    /**
     * @return bool
     */
    public function isUser()
    {
        return $this->role == self::ROLE_USER;
    }
}
