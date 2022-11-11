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

final class SqlAstParenthesis implements SqlAstExpression
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstTokenNode $bracketOpening;

    /** @var array<int, SqlAstExpression> */
    private array $expressions = array();

    /** @var array<int, SqlAstTokenNode> */
    private array $flags = array();

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $bracketOpening,
        array $expressions,
        array $flags
    ) {
        $this->parent = $parent;
        $this->bracketOpening = $bracketOpening;

        foreach ($expressions as $expression) {
            Assert::isInstanceOf($expression, SqlAstExpression::class);

            $this->expressions[] = $expression;
        }

        foreach ($flags as $flag) {
            Assert::isInstanceOf($flag, SqlAstTokenNode::class);

            $this->flags[] = $flag;
        }
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::BRACKET_OPENING())) {
            /** @var int $currentOffset */
            $currentOffset = $offset;

            /** @var SqlAstNode|null $distinct */
            $distinct = $parent[$currentOffset + 1];

            if ($distinct instanceof SqlAstTokenNode && $distinct->is(SqlToken::DISTINCT())) {
                $currentOffset++;

            } else {
                $distinct = null;
            }

            /** @var array<int, SqlAstExpression> $expressions */
            $expressions = array();

            do {
                $currentOffset++;

                /** @var SqlAstNode|null $expression */
                $expression = $parent[$currentOffset];

                if (!$expression instanceof SqlAstTokenNode
                 || !$expression->is(SqlToken::BRACKET_CLOSING())
                ) {
                    if ($expression instanceof SqlAstTokenNode && $expression->is(SqlToken::SYMBOL())) {
                        $expression = new SqlAstColumn($parent, $expression, null, null);

                        $parent->replace($currentOffset, 1, $expression);
                    }

                    if ($expression instanceof SqlAstExpression) {
                        # TODO: also allow SELECT in here, for sub-selects

                        Assert::isInstanceOf($expression, SqlAstExpression::class);

                        $expressions[] = $expression;

                        $currentOffset++;
                    }
                }

                /** @var SqlAstTokenNode|null $close */
                $close = $parent[$currentOffset];
            } while (is_object($close) && $close->is(SqlToken::COMMA()));

            if (!$close instanceof SqlAstTokenNode || !$close->is(SqlToken::BRACKET_CLOSING())) {
                return;
            }

            UnparsableSqlException::assertToken($parent, $currentOffset, SqlToken::BRACKET_CLOSING());

            $parent->replace(
                $offset,
                1 + $currentOffset - $offset,
                new SqlAstParenthesis($parent, $node, $expressions, array_filter([$distinct]))
            );
        }
    }

    public function children(): array
    {
        return array_filter(array_merge($this->flags, $this->expressions));
    }

    /** @return array<int, SqlAstExpression> */
    public function expressions(): array
    {
        return $this->expressions;
    }

    /** @return array<int, SqlAstTokenNode> */
    public function flags(): array
    {
        return $this->flags;
    }

    public function hash(): string
    {
        return md5(implode('.', array_map(function (SqlAstNode $expression): string {
            return $expression->hash();
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
        return $this->bracketOpening->line();
    }

    public function column(): int
    {
        return $this->bracketOpening->column();
    }

    public function toSql(): string
    {
        /** @var string $flagsSql */
        $flagsSql = '';

        if (!empty($this->flags)) {
            $flagsSql = implode(' ', array_map(function (SqlAstTokenNode $flag): string {
                return $flag->toSql();
            }, $this->flags)) . ' ';
        }

        return '(' . $flagsSql . implode(', ', array_map(function (SqlAstExpression $expression) {
            return $expression->toSql();
        }, $this->expressions)) . ')';
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }

    public function extractFundamentalEquations(): array
    {
        /** @var array<SqlAstOperation> $fundamentalEquations */
        $fundamentalEquations = array();

        /** @var SqlAstExpression $expression */
        foreach ($this->expressions as $expression) {
            $fundamentalEquations = array_merge(
                $fundamentalEquations,
                $expression->extractFundamentalEquations(
                )
            );
        }

        return $fundamentalEquations;
    }
}
