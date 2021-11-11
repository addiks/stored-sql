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

namespace Addiks\StoredSQL\AbstractSyntaxTree;

use Addiks\StoredSQL\Exception\UnparsableSqlException;
use Addiks\StoredSQL\Lexing\SqlToken;
use Webmozart\Assert\Assert;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstWalkableTrait;


final class SqlAstUpdate implements SqlAstNode
{
    use SqlAstWalkableTrait;
    
    private SqlAstNode $parent;

    private SqlAstTokenNode $updateToken;

    private SqlAstTable $tableName;

    /** @var array<SqlAstOperation> */
    private array $operations;

    /** @var array<SqlAstJoin> $joins */
    private array $joins;

    private ?SqlAstWhere $where;

    private ?SqlAstOrderBy $orderBy;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $updateToken,
        SqlAstTable $tableName,
        array $operations,
        array $joins,
        ?SqlAstWhere $where,
        ?SqlAstOrderBy $orderBy
    ) {
        Assert::notEmpty($operations);

        $this->parent = $parent;
        $this->updateToken = $updateToken;
        $this->tableName = $tableName;
        $this->operations = array();
        $this->joins = array();
        $this->where = $where;
        $this->orderBy = $orderBy;

        /** @var SqlAstOperation $operation */
        foreach ($operations as $operation) {
            Assert::isInstanceOf($operation, SqlAstOperation::class);
            Assert::isInstanceOf($operation->leftSide(), SqlAstColumn::class);
            Assert::same('=', $operation->operator()->toSql());

            $this->operations[] = $operation;
        }

        /** @var SqlAstJoin $join */
        foreach ($joins as $join) {
            Assert::isInstanceOf($join, SqlAstJoin::class);

            $this->joins[] = $join;
        }
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::UPDATE())) {
            /** @var int $beginOffset */
            $beginOffset = $offset;
            $offset++;

            if ($parent[$offset] instanceof SqlAstColumn) {
                $parent[$offset]->convertToTable();

            } elseif ($parent[$offset] instanceof SqlAstTokenNode) {
                $parent->replaceNode($parent[$offset], new SqlAstTable($parent, $parent[$offset], null));
            }

            UnparsableSqlException::assertType($parent, $offset, SqlAstTable::class);

            /** @var SqlAstTable $tableName */
            $tableName = $parent[$offset];

            /** @var array<SqlAstJoin> $joins */
            $joins = array();

            do {
                /** @var SqlAstNode|null $join */
                $join = $parent[$offset + 1];

                if ($join instanceof SqlAstJoin) {
                    $joins[] = $join;
                    $offset++;
                }
            } while ($join instanceof SqlAstJoin);

            $offset++;
            UnparsableSqlException::assertToken($parent, $offset, SqlToken::SET());

            /** @var array<SqlAstOperation> $operations */
            $operations = array();

            do {
                $offset++;
                UnparsableSqlException::assertType($parent, $offset, SqlAstOperation::class);

                /** @var SqlAstOperation $operation */
                $operation = $parent[$offset];

                $operations[] = $operation;

                /** @var SqlAstNode|null $comma */
                $comma = $parent[$offset + 1];

                /** @var bool $isComma */
                $isComma = ($comma instanceof SqlAstTokenNode && $comma->is(SqlToken::COMMA()));

                if ($isComma) {
                    $offset++;
                }
            } while ($isComma);

            /** @var SqlAstNode|null $where */
            $where = $parent[$offset + 1];

            if ($where instanceof SqlAstWhere) {
                $offset++;

            } else {
                $where = null;
            }

            /** @var SqlAstNode|null $orderBy */
            $orderBy = $parent[$offset + 1];

            if ($orderBy instanceof SqlAstOrderBy) {
                $offset++;

            } else {
                $orderBy = null;
            }

            $parent->replace($beginOffset, 1 + $offset - $beginOffset, new SqlAstUpdate(
                $parent,
                $node,
                $tableName,
                $operations,
                $joins,
                $where,
                $orderBy
            ));
        }
    }

    public function children(): array
    {
        return array_filter(array_merge([
            $this->tableName,
            $this->where,
            $this->orderBy,
        ], $this->operations, $this->joins));
    }

    public function hash(): string
    {
        return md5(implode('.', array_map(function (SqlAstNode $node) {
            return $node->hash();
        }, $this->children())));
    }

    public function parent(): ?SqlAstNode
    {
        return $this->parent;
    }

    public function root(): SqlAstRoot
    {
        return $this->parent->root();
    }

    public function line(): int
    {
        return $this->updateToken->line();
    }

    public function column(): int
    {
        return $this->updateToken->column();
    }

    public function toSql(): string
    {
        /** @var string $sql */
        $sql = 'UPDATE ' . $this->tableName->toSql();

        /** @var array<string> $columnsSql */
        $columnsSql = array();

        /** @var SqlAstOperation $operation */
        foreach ($this->operations as $operation) {
            $columnsSql[] = $operation->toSql();
        }

        $sql .= implode(', ', $columnsSql);

        if (is_object($this->from)) {
            $sql .= ' ' . $this->from->toSql();
        }

        /** @var SqlAstJoin $join */
        foreach ($this->joins as $join) {
            $sql .= ' ' . $join->toSql();
        }

        if (is_object($this->where)) {
            $sql .= ' ' . $this->where->toSql();
        }

        if (is_object($this->orderBy)) {
            $sql .= ' ' . $this->orderBy->toSql();
        }

        return $sql;
    }
}
