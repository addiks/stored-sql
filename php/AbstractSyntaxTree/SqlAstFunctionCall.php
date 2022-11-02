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

use Addiks\StoredSQL\Lexing\SqlToken;
use Webmozart\Assert\Assert;

final class SqlAstFunctionCall implements SqlAstExpression
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstTokenNode $functionNode;

    private ?SqlAstTokenNode $distinct;

    /** @var array<int, SqlAstExpression> */
    private array $expressions = array();

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $functionNode,
        ?SqlAstTokenNode $distinct,
        array $expressions
    ) {
        $this->parent = $parent;
        $this->functionNode = $functionNode;
        $this->distinct = $distinct;

        foreach ($expressions as $expression) {
            Assert::isInstanceOf($expression, SqlAstExpression::class);

            $this->expressions[] = $expression;
        }
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::SYMBOL())) {
            /** @var SqlAstNode|null $bracketOpening */
            $bracketOpening = $parent[$offset + 1];

            if ($bracketOpening instanceof SqlAstTokenNode && $bracketOpening->is(SqlToken::BRACKET_OPENING())) {
                /** @var int $currentOffset */
                $currentOffset = $offset + 1;

                /** @var SqlAstNode|null $distinct */
                $distinct = $parent[$currentOffset + 1];

                if ($distinct instanceof SqlAstTokenNode && $distinct->is(SqlToken::DISTINCT())) {
                    $currentOffset++;

                } else {
                    $distinct = null;
                }

                /** @var array<SqlAstExpression> $expressions */
                $expressions = array();

                do {
                    $currentOffset++;

                    if ($parent[$currentOffset] instanceof SqlAstTokenNode) {
                        $parent->replaceNode(
                            $parent[$currentOffset],
                            new SqlAstColumn($parent, $parent[$currentOffset], null, null)
                        );
                    }

                    /** @var SqlAstExpression $expression */
                    $expression = $parent[$currentOffset];

                    # TODO: also allow SELECT in here, for sub-selects

                    Assert::isInstanceOf($expression, SqlAstExpression::class);

                    $expressions[] = $expression;

                    $currentOffset++;

                    /** @var SqlAstTokenNode|null $close */
                    $close = $parent[$currentOffset];
                } while (is_object($close) && $close->is(SqlToken::COMMA()));

                Assert::isInstanceOf($close, SqlAstTokenNode::class);
                Assert::same($close->token()->token(), SqlToken::BRACKET_CLOSING());

                $parent->replace(
                    $offset,
                    1 + $currentOffset - $offset,
                    new SqlAstFunctionCall(
                        $parent,
                        $node,
                        $distinct,
                        $expressions
                    )
                );
            }
        }
    }

    public function children(): array
    {
        return array_filter(array_merge([$this->functionNode, $this->distinct], $this->expressions));
    }

    public function hash(): string
    {
        return md5(implode('.', array_map(function (SqlAstExpression $expression) {
            return $expression->hash();
        }, $this->expressions)));
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
        return $this->functionNode->line();
    }

    public function column(): int
    {
        return $this->functionNode->column();
    }

    public function toSql(): string
    {
        return implode('', [
            $this->functionNode->toSql(),
            '(',
            (is_object($this->distinct) ? $this->distinct->toSql() . ' ' : ''),
            implode(', ', array_map(function (SqlAstExpression $expression) {
                return $expression->toSql();
            }, $this->expressions)),
            ')',
        ]);
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