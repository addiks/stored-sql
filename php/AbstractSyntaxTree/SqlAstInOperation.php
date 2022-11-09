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
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstParenthesis;

final class SqlAstInOperation implements SqlAstExpression
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstExpression $leftSide;

    private SqlAstTokenNode $inOperator;

    private SqlAstParenthesis $parenthesis;

    public function __construct(
        SqlAstNode $parent,
        SqlAstExpression $leftSide,
        SqlAstTokenNode $inOperator,
        SqlAstParenthesis $parenthesis
    ) {
        $this->parent = $parent;
        $this->leftSide = $leftSide;
        $this->inOperator = $inOperator;
        $this->parenthesis = $parenthesis;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstExpression || $node instanceof SqlAstTokenNode) {
            /** @var SqlAstExpression|SqlAstTokenNode $leftSide */
            $leftSide = $node;

            /** @var SqlAstNode $operator */
            $inOperator = $parent[$offset + 1];

            /** @var SqlAstParenthesis|null $parenthesis */
            $parenthesis = $parent[$offset + 2];

            /** @var bool $leftSideIsExpression */
            $leftSideIsExpression = max(
                $leftSide instanceof SqlAstExpression,
                $leftSide instanceof SqlAstTokenNode && $leftSide->is(SqlToken::SYMBOL())
            );

            /** @var bool $isInOperator */
            $isInOperator = $inOperator instanceof SqlAstTokenNode && $inOperator->is(SqlToken::IN());

            if ($isInOperator && $leftSideIsExpression && $parenthesis instanceof SqlAstParenthesis) {
                if ($leftSide instanceof SqlAstTokenNode && $leftSide->is(SqlToken::SYMBOL())) {
                    $leftSide = new SqlAstColumn($parent, $leftSide, null, null);

                    $parent->replace($offset, 1, $leftSide);
                }

                Assert::isInstanceOf($leftSide, SqlAstExpression::class);

                $parent->replace($offset, 3, new SqlAstInOperation(
                    $parent,
                    $leftSide,
                    $inOperator,
                    $parenthesis
                ));
            }
        }
    }

    public function leftSide(): SqlAstExpression
    {
        return $this->leftSide;
    }

    public function operator(): SqlAstTokenNode
    {
        return $this->inOperator;
    }

    public function parenthesis(): SqlAstParenthesis
    {
        return $this->parenthesis;
    }

    public function children(): array
    {
        return [
            $this->leftSide,
            $this->inOperator,
            $this->parenthesis,
        ];
    }

    public function hash(): string
    {
        return sprintf(
            '(%s/%s/%s)',
            $this->leftSide->hash(),
            $this->inOperator->hash(),
            $this->parenthesis->hash()
        );
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
        return $this->inOperator->line();
    }

    public function column(): int
    {
        return $this->inOperator->column();
    }

    public function toSql(): string
    {
        return $this->leftSide->toSql() . ' ' . $this->inOperator->toSql() . ' ' . $this->parenthesis->toSql();
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }

    public function extractFundamentalEquations(): array
    {
        return [];
    }

    public function isFundamentalEquation(): bool
    {
        return false;
    }

    public function isAlwaysTrue(): bool
    {
        return false;
    }

    public function isAlwaysFalse(): bool
    {
        return false;
    }
}
