Немного о вариантах связей

1 Многие ко многим / many-to-many. Пример "Теги".
    Если у обоих есть методы hasMany() и они связаны через via-таблицу.
    Есть две таблицы owner и related.
    Причем related заполняется отдельно.
    А при заполнении $owner в форме выводится select (single/multiple) для выбора $related.
    Т.е. при сохранении: сначала идет $owner->save(), а потом в via-таблицу сохраняются пары $owner->id и $related->id

2 Один ко многим / one-to-many. Пример "Телефоны".
    Если у owner метод hasMany(), то у related метод hasOne() .
    Есть две таблицы owner и related.
    Причем related заполняется вместе с owner в форме.
    А при заполнении $owner в форме выводится textInput для ввода $related->value.
    Т.е. при сохранении: сначала идет $owner->save(), а потом в related-таблицу сохраняется $owner->id с $related->value

3 Один ко многим / one-to-many. Пример "Изображения".
    Тоже что и 2, но еще добавляется сохранение из $_FILES в нужную папку.




Yii2 Active Record Save Relations Behavior
==========================================
Automatically validate and save related Active Record models.

[![Latest Stable Version](https://poser.pugx.org/la-haute-societe/yii2-save-relations-behavior/v/stable)](https://packagist.org/packages/la-haute-societe/yii2-save-relations-behavior)
[![Total Downloads](https://poser.pugx.org/la-haute-societe/yii2-save-relations-behavior/downloads)](https://packagist.org/packages/la-haute-societe/yii2-save-relations-behavior)
[![Code Coverage](https://scrutinizer-ci.com/g/la-haute-societe/yii2-save-relations-behavior/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/la-haute-societe/yii2-save-relations-behavior/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/la-haute-societe/yii2-save-relations-behavior/badges/build.png?b=master)](https://scrutinizer-ci.com/g/la-haute-societe/yii2-save-relations-behavior/build-status/master)
[![Latest Unstable Version](https://poser.pugx.org/la-haute-societe/yii2-save-relations-behavior/v/unstable)](https://packagist.org/packages/la-haute-societe/yii2-save-relations-behavior)
[![License](https://poser.pugx.org/la-haute-societe/yii2-save-relations-behavior/license)](https://packagist.org/packages/la-haute-societe/yii2-save-relations-behavior)


Функции
--------
- Поддерживаются отношения `hasOne()` и `hasMany()`.
- Работает как с существующими, так и с новыми связанными моделями.
- Поддерживаются составные первичные ключи.
- Используется только чистый API Active Record, поэтому он должен работать с любым драйвером БД.
- Начиная с версии 1.5.0 связанные записи теперь можно удалять вместе с основной моделью.

Настройка
-----------

Настройте модель следующим образом

```php
use SaveRelations\SaveRelationsBehavior;

class Project extends \yii\db\ActiveRecord
{
    use SaveRelationsTrait; // Optional

    public function behaviors()
    {
        return [
            'timestamp'     => TimestampBehavior::className(),
            'blameable'     => BlameableBehavior::className(),
            ...
            'saveRelations' => [
                'class'     => SaveRelationsBehavior::className(),
                'relations' => [
                    'company',
                    'users',
                    'tags'  => [
                        'extraColumns' => function ($model) {
                            /** @var $model Tag */
                            return [
                                'order' => $model->order
                            ];
                        }
                    ],
                    'projectLinks' => ['cascadeDelete' => true],
                ],
            ],
        ];
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    ...


    /**
     * @return ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getProjectUsers()
    {
        return $this->hasMany(ProjectUser::className(), ['project_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->via('ProjectUsers');
    }

    /**
     * @return ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::className(), ['id' => 'tag_id'])
            ->viaTable('ProjectTags', ['project_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getProjectLinks()
    {
        return $this->hasMany(ProjectLink::className(), ['project_id' => 'id']);
    }
}
```

> Хотя это и не обязательно, настоятельно рекомендуется активировать транзакции для модели владельца.


Применение
-----

Каждое объявленное отношение в параметре поведения «relations» теперь можно установить и сохранить следующим образом:

```php
$project = new Project();
$project->name = "New project";
$project->company = Company::findOne(2);
$project->users = User::findAll([1,3]);
$project->save();
```

Вы можете установить связанную модель, указав только ее первичный ключ:

```php
$project = new Project();
$project->name = "Another project";
$project->company = 2;
$project->users = [1,3];
$project->save();
```

Вы даже можете установить связанные модели в виде ассоциативных массивов, например:

```php
$project = Project::findOne(1);
$project->company = ['name' => 'GiHub', 'description' => 'Awesome']; // Will create a new company record
// $project->company = ['id' => 3, 'name' => 'GiHub', 'description' => 'Awesome']; // Will update an existing company record
$project->save();
```

Атрибуты связанной модели будут массово присваиваться с помощью метода load().
Поэтому не забудьте объявить соответствующие атрибуты безопасными в правилах соответствующей модели.

> **Примечания**
> Будут сохранены только вновь созданные или измененные связанные модели.

> Дополнительные примеры см. в тестах PHPUnit.


Заполнение дополнительных столбцов соединительной таблицы в отношении «многие ко многим».
---------------------------------------------------------------------
В отношении «многие ко многим», включающем соединительную таблицу, дополнительные значения столбцов могут быть сохранены в соединительной таблице для каждой модели.
Примеры см. в разделе конфигурации.

> **Примечания**
> Если свойства соединительной таблицы настроены для отношения, строки, связанные со связанными моделями в соединительной таблице, будут удаляться и вставляться снова при каждом сохранении.
> чтобы гарантировать сохранение изменений в свойствах соединительной таблицы.


Проверка
----------
Каждая заявленная связанная модель будет проверена перед сохранением. Если какая-либо проверка не удалась, для каждого ошибочного атрибута связанной модели в модель владельца будет добавлена ошибка, связанная с именованным отношением.

Для отношений hasMany() индекс связанной модели будет использоваться для идентификации связанного сообщения об ошибке.

Для каждого отношения можно указать сценарий проверки, объявив ассоциативный массив, в котором ключ сценария должен содержать необходимое значение сценария.
Например, в следующей конфигурации записи, связанные с `links`, будут проверяться с использованием сценария `Link::SOME_SCENARIO`:
```php
...
    public function behaviors()
    {
        return [
            'saveRelations' => [
                'class'     => SaveRelationsBehavior::className(),
                'relations' => [
                    'company',
                    'users',
                    'links' => ['scenario' => Link::SOME_SCENARIO],
                ],
            ],
        ];
    }
...
```

Также возможно установить сценарий отношений во время выполнения, используя setRelationScenario следующим образом:

```php
$model->setRelationScenario('relationName', 'scenarioName');
```

> **Советы**
> Для отношений, не включающих соединительную таблицу, с использованием методов via() или viaTable(),
вам следует удалить атрибуты, указывающие на модель владельца, из «обязательных» правил проверки,
чтобы иметь возможность пройти проверки.

> **Примечания**
> Если по какой-либо причине во время процесса сохранения связанных записей в событии afterSave возникает ошибка,
при первой возникшей ошибке будет выдано исключение `yii\db\Exception`.
> К атрибуту отношения модели владельца будет прикреплено сообщение об ошибке.
> Чтобы иметь возможность обрабатывать эти случаи удобным для пользователя способом,
необходимо перехватывать исключения `yii\db\Exception`.


Удалить связанные записи при удалении основной модели
-----------------------------------------------------

Для DBM без встроенных реляционных ограничений, начиная с версии 1.5.0, теперь можно указать отношение,
которое необходимо удалить вместе с основной моделью.

Для этого отношение должно быть объявлено со свойством cascadeDelete, установленным в значение true.
Например, связанные записи projectLinks будут автоматически удалены при удалении основной модели:

```php
...
'saveRelations' => [
    'class'     => SaveRelationsBehavior::className(),
    'relations' => [
        'projectLinks' => ['cascadeDelete' => true,],
    ],
],
...
```

> **Примечания**
> Все записи, относящиеся к основной модели, как они определены в операторе ActiveQuery, будут удалены.


Заполните модель и ее отношения входными данными.
-------------------------------------------------- --
Такое поведение добавляет удобный метод для загрузки атрибутов модели отношений таким же образом, как это делает метод load().
Просто вызовите `loadRelations()` с соответствующими входными данными.

Например:

```php
$project = Project::findOne(1);
/**
 * $_POST could be something like:
 * [
 *     'Company'     => [
 *         'name' => 'YiiSoft'
 *     ],
 *     'ProjectLink' => [
 *         [
 *             'language' => 'en',
 *             'name'     => 'yii',
 *             'link'     => 'http://www.yiiframework.com'
 *         ],
 *         [
 *             'language' => 'fr',
 *             'name'     => 'yii',
 *             'link'     => 'http://www.yiiframework.fr'
 *         ]
 *     ]
 * ];
 */
$project->loadRelations(Yii::$app->request->post());
```

Вы можете еще больше упростить процесс, добавив в свою модель SaveRelationsTrait.
В этом случае вызов метода load() также автоматически инициирует вызов метода loadRelations() с использованием тех же данных,
поэтому вам практически не придется менять свои контроллеры.

Свойство relationKeyName можно использовать, чтобы решить, как данные отношений будут извлекаться из параметра данных.

Возможные значения констант:
* `SaveRelationsBehavior::RELATION_KEY_FORM_NAME` (по умолчанию): имя ключа будет вычислено с использованием модели
[`formName()`](https://www.yiiframework.com/doc/api/2.0/yii-base-model# formName()-детализация) метод
* `SaveRelationsBehavior::RELATION_KEY_RELATION_NAME`: будет использоваться имя отношения, определенное в объявлениях поведения.

Получить старые значения отношений
------------------------

Чтобы получить значение отношений до самой последней модификации до тех пор, пока модель не будет сохранена, можно использовать следующие методы:
* `getOldRelation($name)`: Получить старое значение именованного отношения.
* `getOldRelations()`: Получить массив индексов отношений по имени со старыми значениями.

> **Примечания**
> * Если отношение еще не было изменено, будет возвращено его исходное значение
> * Будут возвращены только отношения, определенные в параметрах поведения.


Испачкать отношения
-------------------
Чтобы справиться с «грязными» (измененными) отношениями после загрузки модели, можно использовать следующие методы:
* `getDirtyRelations()`: Получить отношения, которые были изменены с момента их загрузки (пары имя-значение).
* `markRelationDirty($name)`: пометить отношение как «грязное», даже если оно не было изменено.

