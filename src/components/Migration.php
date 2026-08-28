<?php

namespace ripaym1970\autocrud\components;

use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class Migration extends \yii\db\Migration
{
    /**
     * @param string $tableName
     * @param array $fieldNames
     *
     * @return void
     */
    protected function makePrimaryKey($tableName, $fieldNames)
    {
        $indexName = 'pk-' . $tableName;

        if (!$this->isIndexExists($tableName, $indexName)) {
            $this->addPrimaryKey($indexName, $tableName, $fieldNames);
        }
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @param bool   $unique
     *
     * @return void
     */
    protected function makeIndex($tableName, $fieldName, $unique = false)
    {
        $indexName = ($unique ? 'uidx-' : 'idx-') . $tableName . '-' . $fieldName;
        $isIndexExists = $this->isIndexExists($tableName, $indexName);
        if (!$isIndexExists) {
            $this->createIndex(
                $indexName,
                $tableName,
                $fieldName,
                $unique
            );
        }
    }

    /**
     * @param string $tableNameFrom
     * @param string $tableNameTo
     * @param string $delete
     * @param string $update
     *
     * @return void
     */
    protected function makeForeignKey($tableNameFrom, $tableNameTo, $delete = 'NO ACTION', $update = 'NO ACTION')
    {
        $foreignKeyName = "fk-{$tableNameFrom}-{$tableNameTo}_id";

        $this->addForeignKey(
            $foreignKeyName,
            $tableNameFrom,
            "{$tableNameTo}_id",
            $tableNameTo,
            'id',
            $delete,
            $update
        );
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    protected function isTableExists($tableName): bool
    {
        return (bool)Yii::$app->db
            ->schema
            ->getTableSchema($tableName);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     *
     * @return bool
     */
    protected function isColumnExists($tableName, $columnName): bool
    {
        return (bool)Yii::$app->db
            ->schema
            ->getTableSchema($tableName)
            ->getColumn($columnName);
    }

    /**
     * @param string $tableName
     * @param string $indexName
     *
     * @return bool
     * @throws Exception
     */
    protected function isIndexExists($tableName, $indexName): bool
    {
        if ($this->db->driverName === 'mysql') {
            $sql = 'SHOW INDEX FROM `' . $tableName . '` WHERE `Key_name` = "' . $indexName . '"';
            return Yii::$app->db
                ->createCommand($sql)
                ->execute();
        }

        $sql = "
            SELECT c.relname
            FROM pg_class c
            JOIN pg_index i ON i.indexrelid = c.oid
            JOIN pg_class t ON i.indrelid = t.oid
            WHERE t.relname = '$tableName' AND c.relname = '$indexName';
        ";

        return $indexName == Yii::$app->db
                ->createCommand($sql)
                ->execute();
    }

    /**
     * @param string $tableName1
     * @param string $tableName2
     *
     * @return void
     */
    protected function createMany2Many($tableName1, $tableName2)
    {
        $tables = [
            $tableName1,
            $tableName2
        ];
        sort($tables);

        // sort by alphabet
        // first name should be singular
        // second name should be plural

        $tables[0] = Inflector::singularize($tables[0]);
        $tables[1] = Inflector::pluralize(
            Inflector::singularize($tables[1])
        );
        $joinName = implode('_', $tables);

        $fields = [
            'id' => 'bigserial not null primary key',
        ];

        $indexes = [];
        // each related field: consider only the last part of name after underscore
        // and make in singular, then add "_id"
        // later create index for each related key
        foreach ([$tableName1, $tableName2] as $tableName) {
            $key = Inflector::singularize(
                    array_reverse(
                        explode('_', $tableName)
                    )[0]
                ) . '_id';
            $fields [$key] = 'int8 not null references ' . $tableName . ' match full';
            $indexes[] = $key;
        }

        $this->createTable(
            $joinName,
            $fields
        );

        $constraint = implode(
            '_',
            array_merge(
                [$joinName,],
                $indexes,
                ['uniq']
            )
        );

        $this->execute(
            'ALTER TABLE ' . $joinName . ' ADD CONSTRAINT ' . $constraint . ' UNIQUE (' . implode(',', $indexes) . ')'
        );

        // index for key[1], the key for [0] is covered by unique constraint above
        $this->makeIndex($joinName, $indexes[1]);
    }

    /**
     * @param string $tableName
     *
     * @return int
     * @throws Exception
     */
    protected function getIndexes($tableName)
    {
        $sql = 'SHOW INDEX FROM ' . $tableName;
        return Yii::$app->db
            ->createCommand($sql)
            ->execute();
    }

    /**
     * @param string $tableName
     * @param string $foreignKeyName
     *
     * @return bool
     * @throws Exception
     */
    protected function isForeignKeyExists($tableName, $foreignKeyName): bool
    {
        if ($this->db->driverName === 'mysql') {
            $sql = "
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = '$tableName'
                    AND TABLE_SCHEMA = 'имя_вашей_базы_данных'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                    AND CONSTRAINT_NAME = '$foreignKeyName'
            ;";

            return Yii::$app->db
                ->createCommand($sql)
                ->execute();
        }

        $sql = "
            SELECT
                tc.constraint_name
            FROM
                information_schema.table_constraints AS tc
            JOIN
                information_schema.key_column_usage AS kcu
            ON
                tc.constraint_name = kcu.constraint_name
            JOIN
                information_schema.constraint_column_usage AS ccu
            ON
                ccu.constraint_name = tc.constraint_name
            WHERE
                tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_name = '$tableName'
                AND tc.constraint_name = '$foreignKeyName'
        ;";

        return Yii::$app->db
            ->createCommand($sql)
            ->execute();
    }

    protected function removeTable($tableName)
    {
        if ($this->isTableExists($tableName)) {
            $this->dropTable($tableName);
            return;
        }

        //Console::error("removeTable: No found table '$tableName'. No drop table.");
        echo "\033[31mremoveTable: No found table '$tableName'. No drop table.\033[0m\n";
    }

    /**
     * @param string $tableName
     * @param string $columnName
     *
     * @return void
     */
    protected function removeColumn($tableName, $columnName)
    {
        if ($this->isColumnExists($tableName, $columnName)) {
            $this->dropColumn($tableName, $columnName);
            return;
        }

        //Console::error("removeColumn: No found column `$columnName` in table '$tableName'. No drop column");
        echo "\033[31mremoveColumn: No found column `$columnName` in table '$tableName'. No drop column.\033[0m\n";
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @param bool   $unique
     *
     * @return void
     * @throws Exception
     */
    protected function removeIndex($tableName, $fieldName, $unique = false)
    {
        $indexName = ($unique ? 'uidx-' : 'idx-') . $tableName . '-' . $fieldName;
        $isIndexExists = $this->isIndexExists($tableName, $indexName);
        if ($isIndexExists) {
            $this->dropIndex($tableName, $indexName);
            return;
        }

        //Console::error("removeIndex: No found index '$indexName' in table '$tableName'. No drop index");
        echo "\033[31mremoveIndex: No found index '$indexName' in table '$tableName'. No drop index.\033[0m\n";
    }

    /**
     * @param string $tableNameFrom
     * @param string $tableNameTo
     *
     * @return void
     * @throws Exception
     */
    protected function removeForeignKey($tableNameFrom, $tableNameTo)
    {
        $foreignKeyName = "fk-{$tableNameFrom}-{$tableNameTo}_id";

        if ($this->isForeignKeyExists($tableNameFrom, $foreignKeyName)) {
            $this->dropForeignKey($foreignKeyName, $tableNameFrom);
            return;
        }

        //Console::error("removeForeignKey: No found foreign key '$foreignKeyName' in table '$tableNameFrom'.\nNo drop foreign key.");
        echo "\033[31mremoveForeignKey: No found foreign key '$foreignKeyName' in table '$tableNameFrom'.\nNo drop foreign key.\033[0m\n";
    }

    protected function removeForeignKeys($tableName)
    {
        if ($this->isTableExists($tableName)) {
            $db = $this->getDb();
            $tableSchema = $db->getSchema()->getTableSchema($tableName);
            foreach ($tableSchema->foreignKeys as $foreignKeyName => $fk) {
                $this->dropForeignKey($foreignKeyName, $tableName);
                Console::output("removeForeignKeys: Delete foreign key '$foreignKeyName'");
            }

            return;
        }

        //Console::error("removeForeignKeys: Failed to drop foreign keys of table '$tableName'.");
        echo "\033[31mremoveForeignKeys: Failed to drop foreign keys of table '$tableName'.\033[0m\n";
    }

    protected function changeColumnName($tableName, $name, $newName)
    {
        if ($this->isColumnExists($tableName, $name)) {
            $this->renameColumn($tableName, $name, $newName);
            return;
        }

        //Console::error("changeColumnName: Column '$name' not found in table '$tableName'.\nNo rename column.");
        echo "\033[31mchangeColumnName: Column '$name' not found in table '$tableName'.\nNo rename column.\033[0m\n";
    }


    /**
     * @param string $tableName
     * @param array $fieldNames
     * @param string $tableOptions
     */
    protected function makeTableTranslation($tableName, $fieldNames, $tableOptions)
    {
        $createTableName = $tableName . '_translation';
        $this->createTable(
            $createTableName,
            ArrayHelper::merge(
                [
                    "{$tableName}_id" => 'integer NOT NULL',
                    'language_id'     => 'string(2) NOT NULL',
                ],
                array_combine($fieldNames, array_fill(0, count($fieldNames), 'string'))
            ),
            $tableOptions
        );

        $this->addCommentOnTable($createTableName, 'Переклади для ' . $tableName);

        $this->makePrimaryKey($createTableName, ["{$tableName}_id", 'language_id']);

        //$this->addForeignKey(
        //    "fk-{$createTableName}_{$tableName}",
        //    $createTableName,
        //    "{$tableName}_id",
        //    $tableName,
        //    'id',
        //    'CASCADE',
        //    'CASCADE'
        //);
    }

    /**
     * @param string $tableName1
     * @param string $tableName2
     * @param string $tableOptions
     *
     * @throws Exception
     */
    protected function makeTableAssignment($tableName1, $tableName2, $tableOptions)
    {
        $createTableName = "{$tableName1}_{$tableName2}_assignment";
        $this->createTable(
            $createTableName,
            [
                "{$tableName1}_id" => $this->integer()->notNull()->comment(''),
                "{$tableName2}_id" => $this->integer()->notNull()->comment(''),
                'sort'             => $this->integer()->comment('Позиція'),
            ],
            $tableOptions
        );

        $this->addCommentOnTable($createTableName, 'Прикріплено з ' . $tableName2 . ' до ' . $tableName1);

        $this->makePrimaryKey($createTableName, ["{$tableName1}_id", "{$tableName2}_id"]);

        $this->makeForeignKey($createTableName, $tableName1, 'CASCADE');
        $this->makeForeignKey($createTableName, $tableName2, 'CASCADE');
    }

    protected function makeTablePhoto($tableName, $tableOptions, $name = 'Сутність')
    {
        $createTableName = "{$tableName}_image";
        $this->createTable(
            $createTableName,
            [
                'id' => $this->primaryKey()->notNull(),

                "{$tableName}_id" => $this->integer()->notNull()->comment($name),
                'file'    => $this->string()->notNull()->comment('Зображення'),
                'sort'    => $this->integer()->notNull()->comment('Позиція'),
            ],
            $tableOptions
        );

        $this->addCommentOnTable($createTableName, $name . ' - Зображення');

        $this->makeIndex($createTableName, "{$tableName}_id");
        $this->makeForeignKey($createTableName, $tableName, 'CASCADE');
    }

    protected function makeTableAuth($tableName, $tableOptions, $name = 'Сутність')
    {
        $createTableName = "{$tableName}_auth";
        $this->createTable(
            $createTableName,
            [
                'id' => $this->primaryKey()->notNull(),

                "{$tableName}_id" => $this->integer()->notNull()->comment($name),
                'source'          => $this->string()->notNull()->comment('Назва соц.мережі'),
                'source_id'       => $this->string()->notNull()->comment('ID в соц.мережі'),
            ],
            $tableOptions
        );

        $this->addCommentOnTable($createTableName, $name.' - Привязка до соц.мереж');

        $this->makeIndex($createTableName, "{$tableName}_id");

        $indexName = 'idx-' . $createTableName . '-social';
        $this->createIndex(
            $indexName,
            $createTableName,
            ["{$tableName}_id", 'source', 'source_id']
        );

        $this->makeForeignKey($createTableName, $tableName, 'CASCADE', 'CASCADE');
    }

    ///**
    // * @param $tableName
    // */
    //protected function addHierarchicalConstraints($tableName)
    //{
    //    $constraintName = $tableName . '_parent_id_check';
    //    $sql = 'ALTER TABLE ' . $tableName
    //        . ' ADD CONSTRAINT ' . $constraintName
    //        . ' CHECK (id <> parent_id)';
    //
    //    $this->execute($sql);
    //
    //    $triggerName = $tableName . '_prevent_cycle';
    //
    //    $sql = 'CREATE TRIGGER ' . $triggerName
    //        . ' AFTER INSERT OR UPDATE OF parent_id ON ' . $tableName
    //        . ' FOR EACH ROW EXECUTE procedure prevent_cycle();';
    //
    //    $this->execute($sql);
    //}
}
