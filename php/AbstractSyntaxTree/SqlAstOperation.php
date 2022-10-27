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
}
