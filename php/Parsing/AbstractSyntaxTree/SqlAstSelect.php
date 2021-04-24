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

namespace Addiks\StoredSQL\Parsing\AbstractSyntaxTree;

use Addiks\StoredSQL\Exception\UnparsableSqlException;
use Addiks\StoredSQL\Lexing\SqlToken;
use Webmozart\Assert\Assert;

final class SqlAstSelect implements SqlAstNode
{
    private SqlAstNode $parent;

    private SqlAstTokenNode $selectToken;

    /** @var array<string, SqlAstExpression> */
    private array $columns;

    private ?SqlAstFrom $from;

    /** @var array<SqlAstJoin> $joins */
    private array $joins;

    private ?SqlAstWhereCondition $where;

    private ?SqlAstOrderBy $orderBy;

    # TODO: HAVING, LIMIT,

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $selectToken,
        array $columns,
        ?SqlAstFrom $from,
        array $joins,
        ?SqlAstWhereCondition $where,
        ?SqlAstOrderBy $orderBy
    ) {
        Assert::notEmpty($columns);

        $this->parent = $parent;
        $this->selectToken = $selectToken;
        $this->columns = array();
        $this->from = $from;
        $this->joins = array();
        $this->where = $where;
        $this->orderBy = $orderBy;

        /** @var SqlAstExpression $column */
        foreach ($columns as $alias => $column) {
            Assert::isInstanceOf($column, SqlAstExpression::class);

            if (is_int($alias)) {
                $alias = $column->hash(); # TODO: regenerate expression SQL as alias
            }

            $this->columns[$alias] = $column;
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
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::SELECT())) {
            /** @var int $beginOffset */
            $beginOffset = $offset;

            /** @var array<SqlAstExpression> $columns */
            $columns = array();

            do {
                $offset++;

                UnparsableSqlException::assertType($parent, $offset, SqlAstExpression::class);

                /** @var SqlAstExpression $column */
                $column = $parent[$offset];

                /** @var string|null $alias */
                $alias = null;

                if (is_null($alias)) {
                    $alias = $column->hash(); # TODO: regenerate expression SQL as alias
                }

                $columns[$alias] = $column;

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

            if ($where instanceof SqlAstWhereCondition) {
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

            $parent->replace($beginOffset, 1 + $offset - $beginOffset, new SqlAstSelect(
                $parent,
                $node,
                $columns,
                $from,
                $joins,
                $where,
                $orderBy
            ));
        }
    }

    public function children(): array
    {
        return array_filter(array_merge([
            $this->from,
            $this->where,
            $this->orderBy,
        ], $this->columns, $this->joins));
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
}
