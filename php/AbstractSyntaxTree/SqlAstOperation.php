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

final class SqlAstOperation implements SqlAstExpression
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstExpression $leftSide;

    private SqlAstTokenNode $operator;

    private SqlAstExpression $rightSide;

    public function __construct(
        SqlAstNode $parent,
        SqlAstExpression $leftSide,
        SqlAstTokenNode $operator,
        SqlAstExpression $rightSide
    ) {
        $this->parent = $parent;
        $this->leftSide = $leftSide;
        $this->operator = $operator;
        $this->rightSide = $rightSide;
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
            $operator = $parent[$offset + 1];

            /** @var SqlAstNode $rightSide */
            $rightSide = $parent[$offset + 2];

            /** @var bool $leftSideIsExpression */
            $leftSideIsExpression = max(
                $leftSide instanceof SqlAstExpression,
                $leftSide instanceof SqlAstTokenNode && $leftSide->is(SqlToken::SYMBOL())
            );

            /** @var bool $rightSideIsExpression */
            $rightSideIsExpression = max(
                $rightSide instanceof SqlAstExpression,
                $rightSide instanceof SqlAstTokenNode && $rightSide->is(SqlToken::SYMBOL())
            );

            /** @var bool $isOperator */
            $isOperator = $operator instanceof SqlAstTokenNode && max(
                $operator->is(SqlToken::OPERATOR()),
                $operator->is(SqlToken::LIKE()),
                $operator->is(SqlToken::IS()),
                $operator->is(SqlToken::IN()),
            );

            if ($isOperator && $leftSideIsExpression && $rightSideIsExpression) {
                if ($leftSide instanceof SqlAstTokenNode && $leftSide->is(SqlToken::SYMBOL())) {
                    $leftSide = new SqlAstColumn($parent, $leftSide, null, null);

                    $parent->replace($offset, 1, $leftSide);
                }

                if ($rightSide instanceof SqlAstTokenNode && $rightSide->is(SqlToken::SYMBOL())) {
                    $rightSide = new SqlAstColumn($parent, $rightSide, null, null);

                    $parent->replace($offset + 2, 1, $rightSide);
                }

                Assert::isInstanceOf($leftSide, SqlAstExpression::class);
                Assert::isInstanceOf($rightSide, SqlAstExpression::class);

                $parent->replace($offset, 3, new SqlAstOperation(
                    $parent,
                    $leftSide,
                    $operator,
                    $rightSide
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
        return $this->operator;
    }

    public function rightSide(): SqlAstExpression
    {
        return $this->rightSide;
    }

    public function children(): array
    {
        return [
            $this->leftSide,
            $this->operator,
            $this->rightSide,
        ];
    }

    public function hash(): string
    {
        return sprintf(
            '(%s/%s/%s)',
            $this->leftSide->hash(),
            $this->operator->hash(),
            $this->rightSide->hash()
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
        return $this->operator->line();
    }

    public function column(): int
    {
        return $this->operator->column();
    }

    public function toSql(): string
    {
        return $this->leftSide->toSql() . ' ' . $this->operator->toSql() . ' ' . $this->rightSide->toSql();
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }

    public function extractFundamentalEquations(): array
    {
        return $this->isFundamentalEquation() ? [$this] : [];
    }

    public function isFundamentalEquation(): bool
    {
        /** @var bool $isFundamentalEquation */
        $isFundamentalEquation = $this->operator->isCode('=');

        /** @var SqlAstExpression $side */
        foreach ([$this->leftSide, $this->rightSide] as $side) {
            if ($side instanceof SqlAstTokenNode) {
                if (!in_array($side->token()->token(), [
                    SqlToken::SYMBOL(),
                    SqlToken::NUMERIC(),
                    SqlToken::LITERAL(),
                ], true)) {
                    $isFundamentalEquation = false;
                }

            } elseif (!$side instanceof SqlAstColumn) {
                $isFundamentalEquation = false;
            }
        }

        return $isFundamentalEquation;
    }

    public function isAlwaysTrue(): bool
    {
        if ($this->operator->isCode('=') && $this->bothSidesAreAlwaysEqual()) {
            return true;
        }

        if ($this->operator->isCode('!=') && $this->bothSidesAreAlwaysUnequal()) {
            return true;
        }

        return false;
    }

    public function isAlwaysFalse(): bool
    {
        if ($this->operator->isCode('=') && $this->bothSidesAreAlwaysUnequal()) {
            return true;
        }

        if ($this->operator->isCode('!=') && $this->bothSidesAreAlwaysEqual()) {
            return true;
        }

        return false;
    }

    private function bothSidesAreAlwaysEqual(): bool
    {
        return ($this->bothSidesAreLiterals() || $this->bothSidesAreColumns()) && $this->bothSidesHaveSameDefinition();
    }

    private function bothSidesAreAlwaysUnequal(): bool
    {
        return $this->bothSidesAreLiterals() && !$this->bothSidesHaveSameDefinition();
    }

    private function bothSidesHaveSameDefinition(): bool
    {
        return $this->leftSide->toSql() === $this->rightSide->toSql();
    }

    private function bothSidesAreLiterals(): bool
    {
        return $this->leftSide instanceof SqlAstLiteral && $this->rightSide instanceof SqlAstLiteral;
    }

    private function bothSidesAreColumns(): bool
    {
        return $this->leftSide instanceof SqlAstColumn && $this->rightSide instanceof SqlAstColumn;
    }
}
