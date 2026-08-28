<?php

namespace ripaym1970\autocrud\models\interfaces\basic;

/**
 * This is the model class for table "admin".
 *
 * @property string role          [enum('franchisees', 'editor', 'owner', 'admin')]  Роль
 * @property string login         [varchar(255)]    Логін (E-mail)
 * @property string name          [varchar(255)]    Нікнейм
 * @property string username      [varchar(255)]    Нікнейм
 * @property string first_name    [varchar(255)]    Ім'я
 * @property string last_name     [varchar(255)]    Прізвище
 * @property string phone         [varchar(255)]    Телефон
 * @property bool   status        [tinyint(1)]      Активно
 * @property int    lastlogin_at  [int(11)]         Останній визит
 * @property string password_hash [varchar(255)]    Хеш пароля
 * @property int    created_at    [int(11)]         Cтворено
 * @property int    updated_at    [int(11)]         Змінено
 */
class AdminModelInterface extends ModelInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'admin';
    }
}
