<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\StoredSQL\Schema\Factories;

use Addiks\StoredSQL\Schema\ColumnClass;
use Addiks\StoredSQL\Schema\Schema;
use Addiks\StoredSQL\Schema\SchemaClass;
use Addiks\StoredSQL\Schema\Schemas;
use Addiks\StoredSQL\Schema\SchemasClass;
use Addiks\StoredSQL\Schema\Table;
use Addiks\StoredSQL\Schema\TableClass;
use Addiks\StoredSQL\Types\SqlTypeClass;
use PDO;
use PDOStatement;
use Webmozart\Assert\Assert;

final class SchemasFromMySQLInformationSchemaReader implements SchemasFactory
{
    private const SQL_READ_SCHEMA_NAMES = <<<SQL
        SELECT `SCHEMA_NAME`
        FROM `information_schema`.`SCHEMATA`
        SQL;

    private const SQL_READ_DEFAULT_SCHEMA_NAME = 'SELECT DATABASE()';

    private const SQL_READ_TABLES = <<<SQL
        SELECT `TABLE_NAME` 
        FROM `information_schema`.`TABLES` 
        WHERE `TABLE_SCHEMA` = ?
        SQL;

    private const SQL_READ_COLUMNS = <<<SQL
        SELECT `COLUMN_NAME`, `DATA_TYPE`, `IS_NULLABLE`, `COLUMN_KEY`
        FROM `information_schema`.`COLUMNS` 
        WHERE `TABLE_SCHEMA` = ?
        AND `TABLE_NAME` = ?
        ORDER BY `ORDINAL_POSITION` ASC
        SQL;

    private const SQL_READ_FOREIGN_KEYS = <<<SQL
        SELECT 
            `TABLE_SCHEMA`,
            `TABLE_NAME`,
            `COLUMN_NAME`,
            `REFERENCED_TABLE_SCHEMA`, 
            `REFERENCED_TABLE_NAME`, 
            `REFERENCED_COLUMN_NAME`
        FROM `information_schema`.`KEY_COLUMN_USAGE` 
        WHERE `REFERENCED_COLUMN_NAME` IS NOT NULL
        SQL;

    private const SQL_GET_CACHE_KEY = <<<SQL
        SELECT CONCAT(@@hostname, '.', DATABASE(), '.', USER())
        SQL;

    /** @var array<string, PDOStatement> $statements */
    private array $statements = array();

    public function __construct(
        private PDO $pdo
    ) {
    }

    public function cacheKey(): string
    {
        return $this->query(self::SQL_GET_CACHE_KEY)[0][0];
    }

    public function createSchemas(): Schemas
    {
        $schemas = new SchemasClass();

        $this->readSchemas($schemas);
        $schemas->defineDefaultSchema($schemas->schema($this->defaultSchemaName()));

        $this->readForeignKeys($schemas);

        return $schemas;
    }

    private function defaultSchemaName(): string
    {
        return $this->query(self::SQL_READ_DEFAULT_SCHEMA_NAME)[0][0];
    }

    /** @return array<string, Schema> */
    private function readSchemas(Schemas $schemas): void
    {
        foreach ($this->query(self::SQL_READ_SCHEMA_NAMES) as [$schemaName]) {
            $this->readTables(new SchemaClass($schemas, $schemaName));
        }
    }

    private function readTables(Schema $schema): void
    {
        foreach ($this->query(self::SQL_READ_TABLES, [$schema->name()]) as [$tableName]) {
            $table = new TableClass($schema, $tableName);

            $this->readColumns($table);

            $schema->addTable($table);
        }
    }

    private function readColumns(Table $table): void
    {
        foreach ($this->query(
            self::SQL_READ_COLUMNS,
            [$table->schema()->name(), $table->name()]
        ) as [$columnName, $sqlType, $nullable, $columnKey]) {
            $table->addColumn(new ColumnClass(
                $table,
                $columnName,
                SqlTypeClass::fromName($sqlType),
                $nullable === 'YES',
                in_array($columnKey, ['PRI', 'UNI'], true)
            ));
        }
    }

    private function readForeignKeys(Schemas $schemas): void
    {
        foreach ($this->query(self::SQL_READ_FOREIGN_KEYS) as [
            $schemaName,
            $tableName,
            $columnName,
            $referencedSchemaName,
            $referencedTableName,
            $referencedColumnName,
        ]) {
            $column = $schemas
                ->schema($schemaName)
                ?->table($tableName)
                ?->column($columnName);

            $foreignKeyColumn = $schemas
                ->schema($referencedSchemaName)
                ?->table($referencedTableName)
                ?->column($referencedColumnName);

            if (is_object($column) && is_object($foreignKeyColumn)) {
                $column->defineForeignKey($foreignKeyColumn);
            }
        }
    }

    /** @return list<array<int|string, string>> */
    private function query(string $sql, array $arguments = []): array
    {
        /** @var PDOStatement $statement */
        $statement = $this->statements[$sql] ?? $this->prepare($sql);

        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $statement;
        }

        $statement->execute($arguments);

        /** @var list<array<int|string, string>>|false $result */
        $result = $statement->fetchAll();

        Assert::isArray($result, sprintf('Could not query database, SQL "%s" failed!', $sql));

        return $result;
    }

    private function prepare(string $sql): PDOStatement
    {
        /** @var PDOStatement|false $statement */
        $statement = $this->pdo->prepare($sql);

        Assert::object($statement, 'Could not query database!');

        return $statement;
    }
}
