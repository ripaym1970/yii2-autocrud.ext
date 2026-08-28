<?php

namespace ripaym1970\autocrud\console\controllers;

use Yii;
use yii\console\Controller;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * Checks and synchronizes configured database tables.
 *
 * Usage:
 *   php yii autocrud/tables/check
 *
 * Replace "autocrud" with the module ID configured in the console application.
 */
class TablesController extends Controller
{
    public $defaultAction = 'check';
    public $prevColumnName = 'id';

    /**
     * Проверка таблиц базы данных и при необходимости создает или добавляет/изменяет столбцы.
     */
    public function actionCheck()
    {
        // Получим таблицы которые уже есть в БД
        $existsTables = ArrayHelper::index(Yii::$app->db->getSchema()->getTableSchemas(), 'name');
        // Получим наши схемы таблиц
        $tables = ArrayHelper::getValue(Yii::$app->params, 'tables', []);
        if (true) {
            foreach ($tables as $table => $params) {
                $this->prevColumnName = 'id';
                Console::output("Check table '$table'");
                // Если таблица есть
                if (isset($existsTables[$table])) {
                    // Получим индексы для таблицы которые уже есть в БД
                    $indexes = Yii::$app->db->getSchema()->findUniqueIndexes($existsTables[$table]);
                    // Если есть столбцы для таблицы
                    if (isset($params['columns'])) {
                        Console::output("Check columns for table '$table'");
                        $existsColumns = array_keys($existsTables[$table]->columns);
                        $needColumns   = array_keys($params['columns']);
                        $deleteColumns = array_diff($existsColumns, $needColumns);

                        if ($deleteColumns) {
                            Console::output(
                                "Warning! A request was created to delete a columns '"
                                . implode('\', \'', $deleteColumns) . "' from table '$table'."
                            );
                            Console::output(
                                "Warning! Data from these columns will be lost!"
                            );
                            $success = 'yes';
                            //$success = $this->select('Delete columns:', ['yes' => 'yes', 'no' => 'no']);
                            if ($success == 'yes') {
                                Yii::$app->db
                                    ->createCommand(
                                        "ALTER TABLE `$table` DROP COLUMN `" . implode('`, DROP COLUMN`', $deleteColumns) . "`"
                                    )
                                    ->execute();
                                Console::output(
                                    "Delete columns '" . implode('\', \'', $deleteColumns) . "' for table '$table'"
                                );
                            }
                        }
                        foreach ($params['columns'] as $column => $columnParams) {
                            if ($column === 'id') {
                                continue;
                            }
                            Console::stdout("Check column '$column' for table '$table' ...");
                            // Если столбец есть
                            if (isset($existsTables[$table]->columns[$column])) {
                                // Получим тип из БД
                                $type = $existsTables[$table]->columns[$column]->type;

                                // Получим размер из БД
                                $typeSize = $existsTables[$table]->columns[$column]->size;

                                $sql = "ALTER TABLE $table MODIFY $column ";
                                //      ALTER TABLE city_translation MODIFY language_id varchar2 DEFAULT 'uk' NOT NULL COMMENT 'Мова'

                                $change = false;

                                // Заменим наши нестандартные типы на varchar
                                if (in_array($columnParams['type'], ['email', 'phone', 'string'])) {
                                    $type = 'varchar';
                                    if (empty($columnParams['size'])) {
                                        $type .= '(255)';
                                    } elseif ($typeSize != $columnParams['size']) {
                                        // Если размер поля другой
                                        Console::output('1 new SIZE "' . $typeSize . '" !== "' . $columnParams['size'] .'"');
                                        $type .= '(' . $columnParams['size'] . ')';
                                        $change = true;
                                    }
                                }
                                // Если `boolean`, а в БД `tinyint(1)` - фиксим
                                if ($columnParams['type'] === 'boolean' && $type === 'tinyint' && $typeSize == 1) {
                                    $type     = 'boolean';
                                    $typeSize = '';
                                }

                                // Тип `enumint` - обновляем список
                                //if ($columnParams['type'] === 'enumint') {
                                //    $type = 'integer';
                                //    //$typeSize = '';
                                //}

                                // Если `enum` - обновляем список
                                if ($columnParams['type'] === 'enum') {
                                    $type = 'enum';
                                    $typeSize = '';
                                    if (isset($columnParams['items']) && $columnParams['items'] instanceof \Closure) {
                                        $columnItems = array_keys(call_user_func($columnParams['items']));
                                        $typeSize = 'enum(\'' . implode('\',\'', $columnItems) . '\')';
                                    }
                                    if ($existsTables[$table]->columns[$column]->dbType == $typeSize) {
                                        $type = '';
                                        $typeSize = '';
                                    } else {
                                        $change = true;
                                    }
                                }

                                $sql .= $type;
                                $sql .= $typeSize
                                    ? '('.$typeSize.')'
                                    : '';
                                //if ($column === 'language_id') {
                                //    Console::output('SQL-request: '.$sql);
                                //    continue;
                                //}
                                // Если есть DEFAULT - преобразуем
                                // Для boolean значение должно быть 0/1
                                // При 'PRIMARY' => 'currency_id, language_id', default не работает,
                                // так что его не надо добавлять в описание
                                $default = $existsTables[$table]->columns[$column]->defaultValue;
                                //if ($column == 'language_id') {
                                //if ($column == 'role') {
                                //    var_dump('=',$default,$existsTables[$table]->columns[$column]);//exit();
                                //}

                                if ($default) {
                                    $default = " DEFAULT " . (is_int($columnParams['default'])
                                        ? $columnParams['default']
                                        : "'" . $columnParams['default'] . "'");
                                } else {
                                    $default = '';
                                }
                                // Если есть DEFAULT - обновим
                                if (!empty($columnParams['default'])) {
                                    $defaultNew = " DEFAULT " . (is_int($columnParams['default'])
                                        ? $columnParams['default']
                                        : "'" . $columnParams['default'] . "'");

                                    if ($default != $defaultNew) {
                                        Console::output('6 UPDATE DEFAULT "' . $default . '" != "' . $defaultNew . '"');
                                        $default = $defaultNew;
                                        $change = true;
                                    }
                                }
                                $sql .= $default ?? ' ';

                                $null = $existsTables[$table]->columns[$column]->allowNull
                                    ? ' NULL'
                                    : ' NOT NULL';
                                if (isset($columnParams['null'])) {
                                    $nullOld = $existsTables[$table]->columns[$column]->allowNull;
                                    //Console::output('5 Old=' . (int)$nullOld . ' New=' .(int)$columnParams['null']);
                                    if ($nullOld !== $columnParams['null']) {
                                        Console::output('5 new ' . $null);
                                        $null = $columnParams['null'] ? ' NULL' : ' NOT NULL';
                                        $change = true;
                                    }
                                }
                                $sql .= $null;

                                // Если есть COMMENT - обновим
                                $comment = $existsTables[$table]->columns[$column]->comment;
                                if (isset($columnParams['comment']) && $columnParams['comment'] != $comment) {
                                    Console::output('7 new COMMENT');
                                    $comment = $columnParams['comment'];
                                    $change = true;
                                }
                                $sql .= " COMMENT '" . $comment . "'";

                                if ($change) {
                                    //Console::output('8 EXECUTE $sql');
                                    /**
                                     * TODO: Такой формат для изменений
                                     *
                                     * Название здесь НЕ меняем
                                     *
                                     * ALTER TABLE
                                     *     user_support      // имя таблицы
                                     * MODIFY
                                     *     active            // имя поля
                                     *     tinyint(1)        // тип поля
                                     *     DEFAULT 1         // DEFAULT поля
                                     *     NULL              // NULL поля
                                     *     COMMENT 'Активно' // COMMENT поля
                                     * ;
                                     */
                                    //var_dump($existsTables[$table]->columns[$column]);
                                    // Проблема с такими запросами!
                                    // alter table service_translation modify language_id varchar(2) default 'uk' not null comment 'Мова;';

                                    $sql = str_replace('integer(11)', 'int', $sql);
                                    if (Yii::$app->db->createCommand($sql)->execute() == 0) {
                                        Console::output('SQL-request failed: '.$sql);
                                    }
                                }
                            }
                            else {
                                // Иначе - столбца нет
                                Console::output("Column '$column' for table '$table' not exists. Create It.");
                                $add = '';
                                if (in_array($columnParams['type'], ['email', 'phone', 'string'])) {
                                    $columnParams['type'] = 'varchar';
                                    if (empty($columnParams['size'])) {
                                        $columnParams['size'] = 255;
                                    }
                                }
                                if (isset($columnParams['size'])) {
                                    $add .= '(' . $columnParams['size'] . ')';
                                }
                                if ($columnParams['type'] === 'enumint') {
                                    $columnParams['type'] = 'integer';
                                }
                                // TODO: Тут тестить надо
                                if ($columnParams['type'] === 'enum' && isset($columnParams['items']) && $columnParams['items'] instanceof \Closure) {
                                    $columnItems = array_keys(call_user_func($columnParams['items']));
                                    $add .= 'enum(\'' . implode('\',\'', $columnItems) . '\')';
                                }
                                if (isset($columnParams['null'])) {
                                    $add .= $columnParams['null'] ? ' NULL' : ' NOT NULL';
                                }
                                if (isset($columnParams['default'])) {
                                    $add .= ' DEFAULT ' . (is_int($columnParams['default'])
                                        ? $columnParams['default']
                                        : '\'' . $columnParams['default'] . '\'');
                                }
                                if (isset($columnParams['comment'])) {
                                    $add .= ' COMMENT "' . $columnParams['comment'] . '"';
                                }
                                // Если имя столбца перед создаваемым не совпадает
                                if ($this->prevColumnName != $column) {
                                    $add .= " AFTER `$this->prevColumnName`";
                                }
                                Yii::$app->db
                                    ->createCommand("ALTER TABLE `$table` ADD COLUMN `$column` {$columnParams['type']}" . $add)
                                    ->execute();
                                if (isset($params['default'])) {
                                    $key = current(array_keys(current($params['default'])));
                                    $keys = ArrayHelper::getColumn($params['default'], $key);
                                    /** @var ActiveRecord $className */
                                    $className = 'ripaym1970\autocrud\models\crud\\' . ucfirst($table) . 'Model';
                                    $exists = $className::find()
                                        ->select($key . ',' . $column)
                                        ->where([$key => $keys])
                                        ->asArray()
                                        ->all();
                                    $exists = ArrayHelper::map($exists, $key, $column);
                                    $data = ArrayHelper::index($params['default'], $key);
                                    foreach ($exists as $key1 => $value) {
                                        if (isset($data[$key1][$column]) && $data[$key1][$column] != $value) {
                                            $val = str_replace("'", "\\'", $data[$key1][$column]);
                                            Yii::$app->db
                                                ->createCommand("UPDATE `$table` SET `$column`='$val' WHERE `$key`='$key1'")
                                                ->execute();
                                        }
                                    }
                                }
                            }
                            if (isset($columnParams['unique']) && !isset($indexes["idx-$table-$column"])) {
                                Yii::$app->db
                                    ->createCommand("ALTER TABLE `$table` ADD UNIQUE `idx-$table-$column` (`$column`)")
                                    ->execute();
                            }
                            $this->prevColumnName = $column;
                            Console::output("OK");
                        }
                    }
                }
                else {
                    // Иначе - таблицы нет
                    Console::output("Table '$table' not exists. Create It.");
                    $columns = [];
                    if (isset($params['columns']['id'])) {
                        if (!empty($params['columns']['id']['type'])) {
                            if ($params['columns']['id']['type'] === 'bigint') {
                                $columns = ['id' => 'bigpk'];
                            } elseif ($params['columns']['id']['type'] === 'integer') {
                                $columns = ['id' => 'pk'];
                            } elseif ($params['columns']['id']['type'] === 'string') {
                                $columns = ['id' => 'varchar' . '(' . ($params['columns']['id']['size'] ?? 255) . ') PRIMARY KEY NOT NULL COMMENT "Код"'];
                            } else {
                                Console::output("Table '$table' PK ID not type supported!");
                                die();
                            }
                        } else {
                            $columns = ['id' => 'pk'];
                        }
                    }

                    $indexes = [];
                    // Если есть столбцы
                    if (isset($params['columns'])) {
                        foreach ($params['columns'] as $column => $columnParams) {
                            // Если первичный ключ
                            if ($column === 'id') {
                                // Пропускаем
                                continue;
                            }
                            if (in_array($columnParams['type'], ['email', 'phone', 'string'])) {
                                $columnParams['type'] = 'varchar';
                                $columnParams['size'] = $columnParams['size'] ?? 255;
                            }
                            if ($columnParams['type'] === 'enumint') {
                                $columnParams['type'] = 'integer';
                            }
                            if ($columnParams['type'] === 'image') {
                                $columnParams['type'] = 'varchar';
                                $columnParams['size'] = 255;
                            }

                            $columns[$column] = $columnParams['type'];

                            if (!empty($columnParams['size'])) {
                                if ($columnParams['type'] === 'string') {
                                    $columnParams['type'] = 'varchar';
                                }
                                $columns[$column] = $columnParams['type'] . '(' . $columnParams['size'] . ')';
                            }
                            if ($columnParams['type'] === 'enum' && isset($columnParams['items']) && $columnParams['items'] instanceof \Closure) {
                                $columnItems = array_keys(call_user_func($columnParams['items']));
                                $columns[$column] = 'enum(\'' . implode('\',\'', $columnItems) . '\')';
                            }
                            if (isset($columnParams['null'])) {
                                $columns[$column] .= $columnParams['null'] ? ' NULL' : ' NOT NULL';
                            } else {
                                if ($columnParams['type'] == 'boolean') {
                                    $columns[$column] .= ' NOT NULL';
                                }
                            }
                            if (isset($columnParams['default'])) {
                                $columns[$column] .= ' DEFAULT ' . (is_int($columnParams['default']) ? $columnParams['default'] : '\'' . $columnParams['default'] . '\'');
                            }
                            if (isset($columnParams['comment'])) {
                                $columns[$column] .= ' COMMENT "' . $columnParams['comment'] . '"';
                            }
                            if (isset($columnParams['unique'])) {
                                $indexes[] = "ALTER TABLE `$table` ADD UNIQUE `idx-$table-$column` (`$column`)";
                            }
                        }
                    }
                    //echo '<pre>';var_dump($columns);echo '</pre>';exit();

                    // Создаем таблицу
                    $sql = Yii::$app->db->getQueryBuilder()->createTable($table, $columns);
                    //Console::output('sql=' . $sql);
                    Yii::$app->db->createCommand($sql)->execute();

                    $sql = "ALTER TABLE $table ENGINE=InnoDB;";
                    Yii::$app->db->createCommand($sql)->execute();

                    // Создаем уникальные индексы по названию столбца
                    foreach ($indexes as $index) {
                        Yii::$app->db->createCommand($index)->execute();
                    }
                }

                // Если нужны НЕ уникальные индексы для столбцов

                //if ($table =='city_translation') {
                //    echo '<pre>';var_dump($params);echo '</pre>';exit();
                //}
                if (isset($params['PRIMARY'])) {
                    $nameColumn = $params['PRIMARY'];
                    $pk = $this->getPk($table);
                    // Если есть PRIMARY
                    if (!empty($pk['PRIMARY'])) {
                        // Если совпали поля
                        if (implode(', ', $pk['PRIMARY']['columns']) == $params['PRIMARY']) {
                            Console::output("PRIMARY for table '$table' exists.");
                        } else {
                            Console::output("PRIMARY for table '$table' changed. Delete It.");
                            Yii::$app->db->createCommand("ALTER TABLE `$table` ADD CONSTRAINT PRIMARY KEY ($nameColumn)")->execute();
                            Console::output("PRIMARY for table '$table' not exists. Create It.");
                            Yii::$app->db->createCommand("ALTER TABLE `$table` ADD CONSTRAINT PRIMARY KEY ($nameColumn)")->execute();
                        }
                    } else {
                        Console::output("PRIMARY for table '$table' not exists. Create It.");
                        Yii::$app->db->createCommand("ALTER TABLE `$table` ADD CONSTRAINT PRIMARY KEY ($nameColumn)")->execute();
                    }
                }
                if (isset($params['composite'])) {
                    $indexes = $this->getIndexes($table);
                    foreach ($params['composite'] as $key => $columns) {
                        $listColumns = implode(', ', $columns);
                        $nameColumns = $key;
                        if (is_numeric($nameColumns)) {
                            $nameColumns = implode('-', $columns);
                        }
                        $idx = "idx-$table-$nameColumns";

                        // Если есть такой индекс
                        if (isset($indexes[$idx])) {
                            Console::output("Index '$idx' for table '$table' exists.");
                        } else {
                            Console::output("Index '$idx' for table '$table' not exists. Create It.");
                            $sql = "CREATE INDEX `$idx` ON `$table` ($listColumns)";
                            Yii::$app->db
                                //->createCommand("ALTER TABLE `$table` ADD INDEX `$idx` (`$nameColumns`)")
                                ->createCommand($sql)
                                ->execute();
                        }
                    }
                }
                if (isset($params['index'])) {
                    $indexes = $this->getIndexes($table);
                    foreach ($params['index'] as $key => $nameColumn) {
                        $idx = "idx-$table-$key";
                        if (is_numeric($key)) {
                            $idx = "idx-$table-$nameColumn";
                        }
                        // Если есть такой индекс
                        if (isset($indexes[$idx])) {
                            Console::output("Index '$idx' for table '$table' exists.");
                        } else {
                            Console::output("Index '$idx' for table '$table' not exists. Create It.");
                            Yii::$app->db->createCommand("ALTER TABLE `$table` ADD INDEX `$idx` ($nameColumn)")->execute();
                        }
                    }
                }

                // Если есть данные для заполнения
                if (isset($params['fill'])) {
                    /** @var ActiveRecord $className */
                    $className = 'ripaym1970\autocrud\models\crud\\' . ucfirst($table) . 'Model';
                    $count = $className::find()->count();
                    if ($count) {
                        Console::error("Data cannot be added because the table `$table` is not empty.");
                    } else {
                        // Получаем названия полей для заполнения
                        $data = array_values($params['fill']);
                        if ($data) {
                            $t = time();
                            foreach ($data as $i => $attributes) {
                                foreach ($attributes as $key => $attribute) {
                                    if ($attribute instanceof \Closure) {
                                        $data[$i][$key] = call_user_func($attribute);
                                    }
                                }
                                if (isset($params['columns']['created_at'])) {
                                    $data[$i]['created_at'] = $t;
                                }
                                if (isset($params['columns']['updated_at'])) {
                                    $data[$i]['updated_at'] = $t;
                                }
                                if (isset($params['columns']['updated'])) {
                                    $data[$i]['updated'] = $t;
                                }
                            }
                            Yii::$app->db
                                ->createCommand(
                                    Yii::$app->db
                                        ->queryBuilder
                                        ->batchInsert($table, array_keys(current($data)), $data)
                                )
                                ->execute();
                            Console::output("Data added to '$table' table");
                        }
                    }
                }
            }
        }

        if (true) {
            foreach ($tables as $tableNameTo => $params) {
                $delete = 'CASCADE';
                $update = 'NO ACTION';

                foreach ($params['columns'] as $column => $columnParams) {
                    if ($column === 'id') {
                        continue;
                    }

                    if (str_contains($column, '_id')) {
                        $fk = $columnParams['fk'] ?? false;
                        if (!$fk) {
                            //echo '<pre>';var_dump('$null=',$null,$column);echo '</pre>';//exit();
                            continue;
                        }
                        //echo '<pre>';var_dump('$null=',$null);echo '</pre>';exit();
                        [$tableNameFrom] = explode('_', $column);
                        //echo '$column='.$column. '    $tableNameFrom=' . $tableNameFrom;
                        $foreignKeyName = "fk_{$tableNameTo}_{$column}";
                        //echo '   $foreignKeyName='.$foreignKeyName;
                        //alter table message_translation
                        //    add constraint message_translation_message_id_fk
                        //    foreign key (message_id) references message (id);

                        $sql = 'ALTER TABLE ' . $tableNameTo
                            . ' ADD CONSTRAINT ' . $foreignKeyName
                            . ' FOREIGN KEY (' . $column . ')'
                            . ' REFERENCES ' . $tableNameFrom
                            . ' (id)';
                        if ($delete !== null) {
                            $sql .= ' ON DELETE ' . $delete;
                        }
                        if ($update !== null) {
                            $sql .= ' ON UPDATE ' . $update;
                        }

                        //echo '<pre>';var_dump('=',$sql->prepare(Yii::$app->db->queryBuilder)->createCommand()->rawSql);echo '</pre>';//exit();

                        Yii::$app->db
                            ->createCommand($sql)
                            ->execute();
                        //echo '<pre>';var_dump('$sql=',$sql);echo '</pre>';exit();
                    }
                }
            }
        }
    }

    /**
     * @param string $tableName
     *
     * @return array Information about table indexes
     * @throws Exception
     */
    protected function getIndexes($tableName)
    {
        $indexesData = Yii::$app->db->createCommand('SHOW INDEX FROM ' . Yii::$app->db->quoteTableName($tableName))->queryAll();
        $indexes = [];
        foreach ($indexesData as $row) {
            // Если первичный ключ
            if ($row['Key_name'] == 'PRIMARY') {
                // Пропускаем
                continue;
            }
            $indexes[$row['Key_name']]['isUnique']                               = ((int)$row['Non_unique'] ? 'false' : 'true');
            $indexes[$row['Key_name']]['columns'][(int)$row['Seq_in_index'] - 1] = $row['Column_name'];
        }
        return $indexes;
    }

    /**
     * @param string $tableName
     *
     * @return array Information about table indexes
     * @throws Exception
     */
    protected function getPk(string $tableName): array
    {
        $indexesData = Yii::$app->db->createCommand('SHOW INDEX FROM ' . Yii::$app->db->quoteTableName($tableName))->queryAll();
        $indexes = [];
        foreach ($indexesData as $row) {
            // Если НЕ первичный ключ
            if ($row['Key_name'] != 'PRIMARY') {
                // Пропускаем
                continue;
            }
            $indexes[$row['Key_name']]['columns'][(int)$row['Seq_in_index'] - 1] = $row['Column_name'];
        }
        return $indexes;
    }
}
