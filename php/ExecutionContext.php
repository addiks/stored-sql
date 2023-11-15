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

namespace Addiks\StoredSQL;

use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstColumn;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstOperation;
use Addiks\StoredSQL\Schema\Column;
use Addiks\StoredSQL\Schema\Schema;
use Addiks\StoredSQL\Schema\Schemas;
use Addiks\StoredSQL\Schema\Table;
use Webmozart\Assert\Assert;

final class ExecutionContext
{
    /** @var array<string, Table> $tables */
    private array $tables = array();

    public function __construct(
        public readonly Schemas $schemas
    ) {
    }

    public function includeTable(
        string $tableName,
        string $alias = null,
        string $schemaName = ''
    ): void {
        /** @var Schema|null $schema */
        $schema = $this->schemas->schema($schemaName) ?? $this->schemas->defaultSchema();

        Assert::object($schema, 'No schema selected!');

        /** @var Table|null $table */
        $table = $schema->table($tableName);

        Assert::object($table, sprintf('Table "%s" not found in schema "%s"!', $tableName, $schema->name()));

        $this->tables[$table->name()] = $table;

        if (!empty($alias)) {
            $this->tables[$alias] = $table;
        }
    }

    public function table(string $name): ?Table
    {
        return $this->tables[$name] ?? null;
    }

    public function findTableWithColumn(
        string $columnName,
        string $schemaName = 'null',
        string|null $excludeTableName = null
    ): ?Table {
        /** @var Schema|null $schema */
        $schema = $this->schemas->schema($schemaName) ?? $this->schemas->defaultSchema();

        /** @var list<string> $allTableNames */
        $allTableNames = array_unique(array_map(fn ($t) => $t->name(), $this->tables));
        
        /** @var list<string> $tableNameCandidates */
        $tableNameCandidates = array_filter(
            $allTableNames,
            fn ($t) => is_object($schema?->table($t)?->column($columnName))
        );

        if (is_string($excludeTableName)) {
            $tableNameCandidates = array_filter(
                $tableNameCandidates,
                fn ($t) => ($t !== $excludeTableName)
            );
        }

        Assert::notEmpty($tableNameCandidates, sprintf(
            'Did not find referenced column "%s" in any table in this context!',
            $columnName
        ));

        Assert::count($tableNameCandidates, 1, sprintf(
            'Ambiguous referenced column "%s" appears in tables %s!',
            $columnName,
            implode(', ', $tableNameCandidates)
        ));

        return $this->table($tableNameCandidates[0]);
    }

    public function columnByNode(SqlAstColumn $columnNode): ?Column
    {
        /** @var string $columnName */
        $columnName = $columnNode->columnNameString();

        /** @var string $tableName */
        $tableName = $columnNode->tableNameString() ?? $this->findTableWithColumn($columnName);

        return $this->table($tableName)?->column($columnName);
    }

    public function isEquationOneOnOneRelation(SqlAstOperation $equation): bool
    {
        if ($equation->isFundamentalEquation()) {
            $left = $equation->leftSide();
            $right = $equation->rightSide();

            if ($left instanceof SqlAstColumn && $right instanceof SqlAstColumn) {
                /** @var string $leftColumnName */
                $leftColumnName = $left->columnNameString();

                /** @var string $leftTableName */
                $leftTableName = $left->tableNameString() ?? $this->findTableWithColumn($leftColumnName);

                /** @var string $rightColumnName */
                $rightColumnName = $right->columnNameString();

                /** @var string $rightTableName */
                $rightTableName = $right->tableNameString() ?? $this->findTableWithColumn($rightColumnName);

                return $this->isOneToOneRelation(
                    $leftTableName,
                    $leftColumnName,
                    $rightTableName,
                    $rightColumnName
                );
            }
        }

        return false;
    }

    public function isOneToOneRelation(
        string $leftTableName,
        string $leftColumnName,
        string $rightTableName,
        string $rightColumnName
    ): bool {
        /** @var bool $isOneToOneRelation */
        $isOneToOneRelation = false;

        /** @var Column|null $leftColumn */
        $leftColumn = $this->table($leftTableName)?->column($leftColumnName);

        /** @var Column|null $rightColumn */
        $rightColumn = $this->table($rightTableName)?->column($rightColumnName);

        if (is_object($leftColumn) && is_object($rightColumn)) {
            $isOneToOneRelation = min(
                $leftColumn->unique(),
                $rightColumn->unique(),
                !$leftColumn->nullable(),
                !$rightColumn->nullable()
            );
        }

        return $isOneToOneRelation;
    }
}
