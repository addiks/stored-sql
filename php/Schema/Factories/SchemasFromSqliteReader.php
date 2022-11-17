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

final class SchemasFromSqliteReader implements SchemasFactory
{
    private const SQL_READ_TABLES = <<<SQL
        SELECT `name` 
        FROM `sqlite_master` 
        WHERE `type` = "table"
        SQL;

    private const SQL_READ_COLUMNS = <<<SQL
        PRAGMA table_info("%s")
        SQL;

    private const SQL_LIST_INDICIES = <<<SQL
        PRAGMA index_list("%s")
        SQL;

    private const SQL_DESCRIBE_INDEX = <<<SQL
        PRAGMA index_info("%s")
        SQL;

    /** @var array<string, PDOStatement> $statements */
    private array $statements = array();

    public function __construct(
        private PDO $pdo
    ) {
    }

    public function cacheKey(): string
    {
        return 'sqlite';
    }

    public function createSchemas(): Schemas
    {
        $schemas = new SchemasClass();

        $this->readSchemas($schemas);

        /** @var Schema|null $defaultSchema */
        $defaultSchema = $schemas->schema($this->defaultSchemaName());

        if (is_object($defaultSchema)) {
            $schemas->defineDefaultSchema($defaultSchema);
        }

        return $schemas;
    }

    private function defaultSchemaName(): string
    {
        return 'sqlite';
    }

    /** @return array<string, Schema> */
    private function readSchemas(Schemas $schemas): array
    {
        $schema = new SchemaClass($schemas, $this->defaultSchemaName());

        $this->readTables($schema);

        return [$this->defaultSchemaName() => $schema];
    }

    private function readTables(Schema $schema): void
    {
        foreach ($this->query(self::SQL_READ_TABLES) as [$tableName]) {
            $table = new TableClass($schema, (string) $tableName);

            $this->readColumns($table);

            $schema->addTable($table);
        }
    }

    private function readColumns(Table $table): void
    {
        /** @var array<string, string> $uniqueColumns */
        $uniqueColumns = array();

        foreach ($this->query(self::SQL_LIST_INDICIES, [$table->name()]) as [
            'unique' => $unique,
            'name' => $name
        ]) {
            if ($unique) {
                $indexColumns = $this->query(self::SQL_DESCRIBE_INDEX, [$name]);

                if (count($indexColumns) === 1) {
                    $uniqueColumns[$indexColumns[0]['name']] = $name;
                }
            }
        }

        foreach ($this->query(self::SQL_READ_COLUMNS, [$table->name()]) as [
            'name' => $columnName,
            'type' => $sqlType,
            'notnull' => $notnull,
            'pk' => $pk,
        ]) {
            $table->addColumn(new ColumnClass(
                $table,
                (string) $columnName,
                SqlTypeClass::fromName((string) $sqlType),
                ((int) $notnull === 0) && ((int) $pk === 0),
                isset($uniqueColumns[(string) $columnName])
            ));
        }
    }

    /** @return list<array<int|string, string|int>> */
    private function query(string $sql, array $arguments = []): array
    {
        /** @var PDOStatement $statement */
        $statement = $this->pdo->query(vsprintf($sql, $arguments));

        /** @var list<array<int|string, string>>|false $result */
        $result = $statement->fetchAll();

        Assert::isArray($result, sprintf('Could not query database, SQL "%s" failed!', $sql));

        return $result;
    }
}
