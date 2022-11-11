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

final class SqlAstOrderBy implements SqlAstNode
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstTokenNode $orderToken;

    /** @var array<array{0:SqlAstExpression, 1:SqlAstTokenNode|null}> */
    private array $columns;

    /** @param array<array{0:SqlAstExpression, 1:SqlAstTokenNode|null}> $columns */
    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $orderToken,
        array $columns
    ) {
        $this->parent = $parent;
        $this->orderToken = $orderToken;
        $this->columns = array();

        foreach ($columns as [$expression, $direction]) {
            Assert::isInstanceOf($expression, SqlAstExpression::class);

            if (!is_null($direction)) {
                /** @psalm-suppress RedundantConditionGivenDocblockType */
                Assert::isInstanceOf($direction, SqlAstTokenNode::class);
                Assert::oneOf($direction->token()->token(), [SqlToken::ASC(), SqlToken::DESC()]);
            }

            $this->columns[] = [$expression, $direction];
        }
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::ORDER())) {
            $by = $parent[$offset + 1];

            Assert::isInstanceOf($by, SqlAstTokenNode::class);
            Assert::same($by->token()->token(), SqlToken::BY());

            /** @var array<array{0:SqlAstExpression, 1:SqlAstTokenNode}> $columns */
            $columns = array();

            /** @var int $originalOffset */
            $originalOffset = $offset;
            $offset += 2;

            do {
                /** @var SqlAstExpression $expression */
                $expression = $parent[$offset];

                if ($expression instanceof SqlAstTokenNode && $expression->is(SqlToken::SYMBOL())) {
                    SqlAstColumn::mutateAstNode($expression, $offset, $parent);

                    $expression = $parent[$offset];
                }

                UnparsableSqlException::assertType($parent, $offset, SqlAstExpression::class);
                Assert::isInstanceOf($expression, SqlAstExpression::class);

                $direction = $parent[$offset + 1];

                if ($direction instanceof SqlAstTokenNode) {
                    if (in_array($direction->token()->token(), [SqlToken::ASC(), SqlToken::DESC()], true)) {
                        $offset++;

                    } else {
                        $direction = null;
                    }

                } else {
                    $direction = null;
                }

                $columns[] = [$expression, $direction];

                /** @var SqlAstNode|null $comma */
                $comma = $parent[$offset + 1];
                $offset += 2;
            } while ($comma instanceof SqlAstTokenNode && $comma->isCode(','));

            $parent->replace(
                $originalOffset,
                $offset - $originalOffset - 1,
                new SqlAstOrderBy($parent, $node, $columns)
            );
        }
    }

    public function children(): array
    {
        /** @var array<int, SqlAstNode> $children */
        $children = array();

        /**
         * @var SqlAstExpression     $expression
         * @var SqlAstTokenNode|null $direction
         */
        foreach ($this->columns as [$expression, $direction]) {
            $children[] = $expression;

            if (is_object($direction)) {
                $children[] = $direction;
            }
        }

        return $children;
    }

    public function hash(): string
    {
        /** @var array<string> $hashes */
        $hashes = array_map(function (SqlAstNode $child) {
            return $child->hash();
        }, $this->children());

        return md5(implode('.', $hashes));
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
        return $this->orderToken->line();
    }

    public function column(): int
    {
        return $this->orderToken->column();
    }

    public function toSql(): string
    {
        /** @var array<string> $columns */
        $columns = array();

        /**
         * @var SqlAstExpression     $expression
         * @var SqlAstTokenNode|null $direction
         */
        foreach ($this->columns as [$expression, $direction]) {
            $columns[] = ' ' . trim($expression->toSql() . ' ' . (string) $direction?->toSql());
        }

        return 'ORDER BY' . implode(',', $columns);
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }
}
