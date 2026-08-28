<?php

namespace ripaym1970\autocrud\models\interfaces\basic;

/**
 * This is the interface class for table "language".
 *
 * @property string id      [varchar(2)]  Код
 * @property string name    [varchar(16)] Назва
 * @property bool   default [tinyint(1)]  Дефолтна
 * @property bool   active  [tinyint(1)]  Активно
 * @property string flag    [text]        SVG-код картинки флага
 */
class LanguageModelInterface extends ModelInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'language';
    }
}
