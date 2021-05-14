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

final class SqlAstOperation implements SqlAstExpression
{
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
        if ($node instanceof SqlAstExpression) {
            /** @var SqlAstExpression $leftSide */
            $leftSide = $node;

            /** @var SqlAstNode $operator */
            $operator = $parent[$offset + 1];

            /** @var SqlAstNode $rightSide */
            $rightSide = $parent[$offset + 2];

            if ($operator instanceof SqlAstTokenNode && $rightSide instanceof SqlAstExpression) {

                /** @var bool $isOperator */
                $isOperator = max(
                    $operator->is(SqlToken::OPERATOR()),
                    $operator->is(SqlToken::LIKE()),
                    $operator->is(SqlToken::IS()),
                );

                if ($isOperator) {
                    $parent->replace($offset, 3, new SqlAstOperation(
                        $parent,
                        $leftSide,
                        $operator,
                        $rightSide
                    ));
                }
            }
        }
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
}
