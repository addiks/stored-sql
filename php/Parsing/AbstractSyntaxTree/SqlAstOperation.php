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

use Addiks\StoredSQL\Lexing\SqlToken;
use ArrayIterator;
use Iterator;

final class SqlAstOperation implements SqlAstExpression
{
    private SqlAstExpression $leftSide;

    private SqlAstTokenNode $operator;

    private SqlAstExpression $rightSide;

    public function __construct(
        SqlAstExpression $leftSide,
        SqlAstTokenNode $operator,
        SqlAstExpression $rightSide
    ) {
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

            if ($operator instanceof SqlAstTokenNode && $operator->is(SqlToken::OPERATOR())) {
                if ($rightSide instanceof SqlAstExpression) {
                    $parent->replace($offset, 3, new SqlAstOperation(
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

    /** @return Iterator<SqlAstNode> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->children());
    }
}
