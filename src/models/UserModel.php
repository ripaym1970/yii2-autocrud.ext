<?php

namespace ripaym1970\autocrud\models;

use ripaym1970\autocrud\models\crud\LanguageModel;
use ripaym1970\autocrud\models\crud\User_auth_logModel;
use ripaym1970\autocrud\models\crud\User_authModel;
use ripaym1970\autocrud\models\crud\User_profileModel;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\Exception;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii2tech\authlog\AuthLogIdentityBehavior;

// use ripaym1970\autocrud\models\interfaces\User_profileModelInterface;

/**
 * This is the model class for table "user".
 *
 * @property int    id
 * @property string email                  [varchar(255)]  E-mail в якості логіну
 * @property bool   email_confirmed        [tinyint(1)]    Флаг подтверждения почты
 * @property string password_hash          [varchar(255)]  Хеш пароля
 * @property string password_reset_token   [varchar(255)]  Токен пароля
 * @property string auth_key               [varchar(255)]  Ключ повторной авторизации
 * @property int    status                 [varchar(255)]  Статус
 *
 * @property string name                   [varchar(255)]  Никнейм
 *
 * @property string language_id            [varchar(2)]    Язык отображения сайта пользователя
 * @property bool   is_test                [tinyint(1)]    Флаг
 * @property bool   is_receive             [tinyint(1)]    Флаг разрешающий отправку мне сообщений
 *
 * @property int    lastlogin_at           [int(11)]       Останній візит
 * @property int    created_at             [int(11)]       Cтворено
 * @property int    updated_at             [int(11)]       Змінено
 *
 * @property User_profileModelInterface  profile
 */
class UserModel extends \yii2mod\user\models\UserModel
{
    public const STATUS_INACTIVE  =  0;  // Неактивно
    public const STATUS_ACTIVE    =  1;  // Активно
    public const STATUS_PENDING   =  5;  // Ожидает активации?
    public const STATUS_SUSPENDED =  7;  // Приостановлено
    public const STATUS_DELETED   = 10;  // Удален сам или?

    public $password = '';

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'user';
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
            'authLog' => [
                'class'              => AuthLogIdentityBehavior::class,
                'authLogRelation'    => 'authLogs',
                'defaultAuthLogData' => function () {
                    return [
                        'ip'         => Yii::$app->request->getUserIP(),
                        'host'       => @gethostbyaddr(Yii::$app->request->getUserIP()),
                        'url'        => Yii::$app->request->getAbsoluteUrl(),
                        'user_agent' => Yii::$app->request->getUserAgent(),
                    ];
                },
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
            //
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
     * @return array
     */
    public function rules()
    {
        return [
            [['email'], 'required'],
            [['email'], 'email'],
            [['email'], 'unique'],

            [['lastlogin_at', 'created_at', 'updated_at'], 'safe'],
            [['lastlogin_at', 'created_at', 'updated_at'], 'default', 'value' => new Expression('UNIX_TIMESTAMP()')],

            [['password_hash', 'password_reset_token', 'auth_key'], 'string', 'max' => 255],

            [['status'], 'in', 'range' => [
                self::STATUS_INACTIVE,
                self::STATUS_ACTIVE,
                self::STATUS_PENDING,
                self::STATUS_SUSPENDED,
                self::STATUS_DELETED,
            ]],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],

            [['language_id'], 'string', 'min' => 2, 'max' => 2],
            [['email_confirmed', 'is_receive'], 'boolean'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            //'login'       => Yiit::t('Логін'),
            'email'         => 'E-mail',
            'password'      => Yiit::t('Пароль'),
            'password_hash' => Yiit::t('Хеш паролю'),
            'name'          => Yiit::t('Нікнейм'),

            'status'        => Yiit::t('Статус'),
            'language_id'   => Yiit::t('Мова'),
            'is_receive'    => Yiit::t('Надсилати повідомлення на електронну пошту'),

            'lastlogin_at'    => Yiit::t('Останній логін'),
            'created_at'    => Yiit::t('Створено'),
            'updated_at'    => Yiit::t('Змінено'),
        ];
    }

    public function beforeValidate(): bool
    {
        //if (empty($this->username)) {
        //    unset($this->username);
        //}
        //if (empty($this->phone)) {
        //    unset($this->phone);
        //}
        if (empty($this->email)) {
            unset($this->email);
        }
        return parent::beforeValidate();
    }

    public static function statusList()
    {
        return [
            self::STATUS_INACTIVE  => Yiit::t('Неактивно'),    // Проблема с очередностью загрузки таблиц для Yiit::t()
            self::STATUS_ACTIVE    => Yiit::t('Активно'),
            self::STATUS_PENDING   => Yiit::t('На модерації'), // Ожидает активации
            self::STATUS_SUSPENDED => Yiit::t('Припинено'),    // Приостановлено
            self::STATUS_DELETED   => Yiit::t('Видалено'),     // Удален сам или?
        ];
    }

    public function afterDelete()
    {
        parent::afterDelete();
        Yii::$app->authManager->revokeAll($this->id);
    }

    public static function onLanguageChanged()
    {
        if (!Yii::$app->user->isGuest) {
            //$user = Yii::$app->user->identity;
            //$user->language_id = LanguageHelper::languageId();
            // Save the current language to user record
            //$user->updateAttributes(['language_id' => LanguageHelper::languageId()]);
            //if (!$user->save()) {
            //    dd(['Язык не поменяли!',$user->rules()]);
            //}
        }
    }

    public function updateLastLogin()
    {
        $this->updateAttributes(['lastlogin_at' => new Expression('UNIX_TIMESTAMP()')]);
    }

    //public function loadUserLanguage()
    //{
    //    $language = 'en-US';
    //    $code     = 'en';
    //    $languageModel = LanguageModel::find()->where(['id' => Yii::$app->user->identity->language_id])->one();
    //    if ($languageModel) {
    //        $language = $languageModel->locale ?: 'en-US';
    //        $code     = $languageModel->code ?: 'en';
    //    }
    //    Yii::$app->language = $language;
    //    return $code;
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
        //dd([$password, $this->password_hash]);
        return Yii::$app->getSecurity()->validatePassword($password, $this->password_hash);
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

    /**
     * @return ActiveQuery|User_profileModelInterface
     */
    public function getProfile()
    {
        return $this->hasOne(User_profileModel::class, ['user_id' => 'id']);
    }

    //public function getProfileId() {
    //    return $this->profile->id;
    //}

    /**
     * @return ActiveQuery
     */
    public function getAuth()
    {
        return $this->hasMany(User_authModel::class, ['user_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getAuthLogs()
    {
        return $this->hasMany(User_auth_logModel::class, ['user_id' => 'id']);
    }

    //public function getFullName()
    //{
    //    return $this->first_name . ' ' . $this->last_name;
    //}

    /**
     * @param null $url путь к дефолтной картинке (разный для header и настроек)
     *
     * @return mixed|string|null
     */
    public function getPoster($url = null)
    {
        if ($this->profile->image) {
            $url = '/images/original/user/' . $this->image; // Показываем оригинал
        }

        return $url;
    }

    //public function getCoins() {
    //    $coins = 0;
    //
    //    if ($this->profile) {
    //        $coins = 100;
    //    }
    //
    //    return $coins;
    //}

    /**
     * @param array  $condition
     * @param string $name
     *
     * @return array
     */
    public static function listing($condition = [], $name = 'name'): array
    {
        $query = static::find()
            ->alias('self')
            ->select([
                $name,
                'self.id',
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
            ->indexBy('self.id')
            ->orderBy($name)
        ;

        if (isset((new static())->behaviors()['translations'])) {
            $query->joinWith(['defaultTranslation'], false);
        }
        //dd($query->prepare(\Yii::$app->db->queryBuilder)->createCommand()->rawSql);

        return $query
            ->column();
    }
}
