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

    /** @var array<int, SqlAstExpression|SqlAstAllColumnsSelector> */
    private array $expressions = array();

    /** @var array<int, SqlAstTokenNode> */
    private array $flags = array();

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $functionNode,
        array $expressions,
        array $flags
    ) {
        $this->parent = $parent;
        $this->functionNode = $functionNode;

        /** @var SqlAstExpression|SqlAstAllColumnsSelector $expression */
        foreach ($expressions as $expression) {
            Assert::isInstanceOfAny($expression, [SqlAstExpression::class, SqlAstAllColumnsSelector::class]);

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
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::SYMBOL())) {
            /** @var SqlAstNode|null $bracketOpening */
            $bracketOpening = $parent[$offset + 1];

            if ($bracketOpening instanceof SqlAstParenthesis) {
                $parent->replace(
                    $offset,
                    2,
                    new SqlAstFunctionCall(
                        $parent,
                        $node,
                        $bracketOpening->expressions(),
                        $bracketOpening->flags()
                    )
                );

            } elseif ($bracketOpening instanceof SqlAstTokenNode && $bracketOpening->is(SqlToken::BRACKET_OPENING())) {
                /** @var int $currentOffset */
                $currentOffset = $offset + 1;

                /** @var SqlAstTokenNode|null $distinct */
                $distinct = $parent[$currentOffset + 1];

                if ($distinct instanceof SqlAstTokenNode && $distinct->is(SqlToken::DISTINCT())) {
                    $currentOffset++;

                } else {
                    $distinct = null;
                }

                /** @var array<int, SqlAstExpression|SqlAstAllColumnsSelector> $expressions */
                $expressions = array();

                do {
                    $currentOffset++;

                    /** @var SqlAstNode|null $expression */
                    $expression = $parent[$currentOffset];

                    if ($expression instanceof SqlAstTokenNode) {
                        if ($expression->is(SqlToken::STAR())) {
                            $parent->replaceNode(
                                $expression,
                                new SqlAstAllColumnsSelector($parent, $expression, null, null)
                            );

                        } elseif ($expression->is(SqlToken::SYMBOL())) {
                            $parent->replaceNode(
                                $expression,
                                new SqlAstColumn($parent, $expression, null, null)
                            );
                        }
                    }

                    /** @var SqlAstExpression|SqlAstAllColumnsSelector $expression */
                    $expression = $parent[$currentOffset];

                    # TODO: also allow SELECT in here, for sub-selects

                    Assert::isInstanceOfAny($expression, [SqlAstAllColumnsSelector::class, SqlAstExpression::class]);

                    $expressions[] = $expression;

                    $currentOffset++;

                    /** @var SqlAstNode|null $close */
                    $close = $parent[$currentOffset];
                } while (is_object($close) && $close instanceof SqlAstTokenNode && $close->is(SqlToken::COMMA()));

                if ($close instanceof SqlAstTokenNode && $close->is(SqlToken::BRACKET_CLOSING())) {
                    $parent->replace(
                        $offset,
                        1 + $currentOffset - $offset,
                        new SqlAstFunctionCall(
                            $parent,
                            $node,
                            $expressions,
                            array_filter([$distinct])
                        )
                    );
                }
            }
        }
    }

    public function children(): array
    {
        return array_filter(array_merge([$this->functionNode], $this->flags, $this->expressions));
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
        return $this->functionNode->line();
    }

    public function column(): int
    {
        return $this->functionNode->column();
    }

    public function name(): string
    {
        return $this->functionNode->toSql();
    }

    /** @return array<int, SqlAstExpression|SqlAstAllColumnsSelector> */
    public function arguments(): array
    {
        return $this->expressions;
    }

    public function toSql(): string
    {
        return implode('', [
            $this->functionNode->toSql(),
            '(',
            (!empty($this->flags) ? implode(' ', array_map(fn ($flag) => $flag->toSql(), $this->flags)) . ' ' : ''),
            implode(', ', array_map(function (SqlAstExpression|SqlAstAllColumnsSelector $expression) {
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
