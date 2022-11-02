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
use Addiks\StoredSQL\ExecutionContext;
use Addiks\StoredSQL\Lexing\SqlToken;
use Addiks\StoredSQL\Schema\Schemas;
use Webmozart\Assert\Assert;

final class SqlAstSelect implements SqlAstNode
{
    use SqlAstWalkableTrait;

    private SqlAstMutableNode $parent;

    private SqlAstTokenNode $selectToken;

    private ?SqlAstTokenNode $distinctToken;

    /** @var array<string|int, SqlAstExpression> */
    private array $columns;

    private ?SqlAstFrom $from;

    /** @var array<SqlAstJoin> $joins */
    private array $joins;

    private ?SqlAstWhere $where;

    private ?SqlAstGroupBy $groupBy;

    private ?SqlAstHaving $having;

    private ?SqlAstOrderBy $orderBy;

    private ?int $limit;

    private ?int $offset;

    private ?SqlAstSelect $union;

    public function __construct(
        SqlAstMutableNode $parent,
        SqlAstTokenNode $selectToken,
        ?SqlAstTokenNode $distinctToken,
        array $columns,
        ?SqlAstFrom $from,
        array $joins,
        ?SqlAstWhere $where,
        ?SqlAstGroupBy $groupBy,
        ?SqlAstHaving $having,
        ?SqlAstOrderBy $orderBy,
        ?int $limit,
        ?int $offset,
        ?SqlAstSelect $union
    ) {
        Assert::notEmpty($columns);

        $this->parent = $parent;
        $this->selectToken = $selectToken;
        $this->distinctToken = $distinctToken;
        $this->columns = array();
        $this->from = $from;
        $this->joins = array();
        $this->where = $where;
        $this->groupBy = $groupBy;
        $this->having = $having;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->union = $union;

        /** @var SqlAstExpression $column */
        foreach ($columns as $alias => $column) {
            Assert::isInstanceOf($column, SqlAstExpression::class);

            $this->columns[$alias] = $column;
        }

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
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::SELECT())) {
            /** @var int $beginOffset */
            $beginOffset = $offset;

            /** @var SqlAstNode|null $distinct */
            $distinct = $parent[$offset + 1];

            if ($distinct instanceof SqlAstTokenNode && $distinct->is(SqlToken::DISTINCT())) {
                $offset++;

            } else {
                $distinct = null;
            }

            /** @var array<string|int, SqlAstExpression> $columns */
            $columns = array();

            do {
                $offset++;

                if ($parent[$offset] instanceof SqlAstTokenNode) {
                    $parent->replaceNode($parent[$offset], new SqlAstColumn($parent, $parent[$offset], null, null));
                }

                UnparsableSqlException::assertType($parent, $offset, SqlAstExpression::class);

                /** @var SqlAstExpression $column */
                $column = $parent[$offset];

                /** @var string|null $alias */
                $alias = null;

                /** @var SqlAstNode|null $as */
                $as = $parent[$offset + 1];

                if ($as instanceof SqlAstTokenNode && $as->is(SqlToken::AS())) {
                    $alias = $parent[$offset + 2]->toSql();
                    $offset += 2;
                }

                if (is_null($alias)) {
                    $columns[] = $column;

                } else {
                    $columns[$alias] = $column;
                }

                /** @var SqlAstNode|null $comma */
                $comma = $parent[$offset + 1];

                /** @var bool $isComma */
                $isComma = ($comma instanceof SqlAstTokenNode && $comma->is(SqlToken::COMMA()));

                if ($isComma) {
                    $offset++;
                }
            } while ($isComma);

            /** @var SqlAstNode|null $from */
            $from = $parent[$offset + 1];

            if ($from instanceof SqlAstFrom) {
                $offset++;

            } else {
                $from = null;
            }

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

            /** @var SqlAstNode|null $where */
            $where = $parent[$offset + 1];

            if ($where instanceof SqlAstWhere) {
                $offset++;

            } else {
                $where = null;
            }

            /** @var SqlAstNode|null $groupBy */
            $groupBy = $parent[$offset + 1];

            if ($groupBy instanceof SqlAstGroupBy) {
                $offset++;

            } else {
                $groupBy = null;
            }

            /** @var SqlAstNode|null $having */
            $having = $parent[$offset + 1];

            if ($having instanceof SqlAstHaving) {
                $offset++;

            } else {
                $having = null;
            }

            /** @var SqlAstNode|null $orderBy */
            $orderBy = $parent[$offset + 1];

            if ($orderBy instanceof SqlAstOrderBy) {
                $offset++;

            } else {
                $orderBy = null;
            }

            /** @var SqlAstNode|null $limitToken */
            $limitToken = $parent[$offset + 1];

            /** @var int|null $limit */
            $limit = null;

            /** @var int|null $limitOffset */
            $limitOffset = null;

            if ($limitToken instanceof SqlAstTokenNode && $limitToken->is(SqlToken::LIMIT())) {
                $limit = (int) $parent[$offset + 2]->toSql();
                $offset += 2;

                /** @var SqlAstNode|null $comma */
                $comma = $parent[$offset + 1];

                if ($comma instanceof SqlAstTokenNode && $comma->is(SqlToken::COMMA())) {
                    $limitOffset = (int) $parent[$offset + 2]->toSql();
                    $offset += 2;
                }

            } else {
                $orderBy = null;
            }

            /** @var SqlAstNode|null $union */
            $union = $parent[$offset + 1];

            if ($union instanceof SqlAstTokenNode && $union->is(SqlToken::UNION())) {
                $offset++;

                $union = $parent[$offset + 1];

                if ($union instanceof SqlAstSelect) {
                    $offset++;

                } else {
                    $union = null;
                }

            } else {
                $union = null;
            }

            /** @var SqlAstNode|null $semicolon */
            $semicolon = $parent[$offset + 1];

            if ($semicolon instanceof SqlAstTokenNode && $semicolon->is(SqlToken::SEMICOLON())) {
                $semicolon = null;
            }

            if ($semicolon === null) {
                $parent->replace($beginOffset, 1 + $offset - $beginOffset, new SqlAstSelect(
                    $parent,
                    $node,
                    $distinct,
                    $columns,
                    $from,
                    $joins,
                    $where,
                    $groupBy,
                    $having,
                    $orderBy,
                    $limit,
                    $limitOffset,
                    $union
                ));
            }
        }
    }

    public function createContext(Schemas $schemas): ExecutionContext
    {
        $context = new ExecutionContext($schemas);

        if (is_object($this->from)) {
            $context->includeTable(
                $this->from->tableName(),
                $this->from->aliasName()
            );
        }

        /** @var SqlAstJoin $join */
        foreach ($this->joins as $join) {
            $context->includeTable(
                $join->joinedTableName(),
                $join->aliasName()
            );
        }

        return $context;
    }

    public function selectToken(): SqlAstTokenNode
    {
        return $this->selectToken;
    }

    /** @return array<string|int, SqlAstExpression> */
    public function columns(): array
    {
        return $this->columns;
    }

    public function from(): ?SqlAstFrom
    {
        return $this->from;
    }

    /** @return array<SqlAstJoin> */
    public function joins(): array
    {
        return $this->joins;
    }

    public function addJoin(SqlAstJoin $join): void
    {
        $this->joins[] = $join;
    }

    public function replaceJoin(SqlAstJoin $old, ?SqlAstJoin $new): void
    {
        /** @var int|false $offset */
        $offset = array_search($old, $this->joins, true);

        Assert::integer($offset, 'SELECT does not contain the JOIN that should be replaced!');

        if (is_object($new)) {
            $this->joins[$offset] = $new;

        } else {
            unset($this->joins[$offset]);
        }

        $this->joins = array_values(array_filter($this->joins));
    }

    public function where(): ?SqlAstWhere
    {
        return $this->where;
    }

    public function orderBy(): ?SqlAstOrderBy
    {
        return $this->orderBy;
    }

    public function children(): array
    {
        return array_values(array_filter(array_merge(
            [$this->selectToken, $this->distinctToken],
            $this->columns,
            [$this->from],
            $this->joins,
            [
                $this->where,
                $this->groupBy,
                $this->having,
                $this->orderBy,
                $this->union,
            ]
        )));
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
        return $this->selectToken->line();
    }

    public function column(): int
    {
        return $this->selectToken->column();
    }

    public function toSql(): string
    {
        /** @var string $sql */
        $sql = 'SELECT ';

        if (is_object($this->distinctToken)) {
            $sql .= $this->distinctToken->toSql() . ' ';
        }

        /** @var array<string> $columnsSql */
        $columnsSql = array();

        /** @var SqlAstExpression $column */
        foreach ($this->columns as $alias => $column) {
            $columnsSql[] = $column->toSql() . (is_string($alias) ? (' AS ' . $alias) : '');
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

        if (is_object($this->groupBy)) {
            $sql .= ' ' . $this->groupBy->toSql();
        }

        if (is_object($this->having)) {
            $sql .= ' ' . $this->having->toSql();
        }

        if (is_object($this->orderBy)) {
            $sql .= ' ' . $this->orderBy->toSql();
        }

        if (!is_null($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;

            if (!is_null($this->offset)) {
                $sql .= ', ' . $this->offset;
            }
        }

        if (is_object($this->union)) {
            $sql .= ' UNION ' . $this->union->toSql();
        }

        return $sql;
    }

    public function canBeExecutedAsIs(): bool
    {
        return true;
    }
}
