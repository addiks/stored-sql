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

use ArrayIterator;
use Iterator;
use Webmozart\Assert\Assert;

final class SqlAstConjunction implements SqlAstExpression
{
    /** @var array<array{0:SqlAstTokenNode, 1:SqlAstExpression}> */
    private array $parts;

    /** @param array<array{0:SqlAstTokenNode, 1:SqlAstExpression}> $parts */
    public function __construct(array $parts)
    {
        $this->parts = array();

        foreach ($parts as [$operator, $expression]) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isInstanceOf($operator, SqlAstTokenNode::class);

            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isInstanceOf($expression, SqlAstExpression::class);

            $this->parts[] = [$operator, $expression];
        }
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
    }

    public function children(): array
    {
        return [];
    }

    public function hash(): string
    {
        return '';
    }

    /** @return Iterator<SqlAstNode> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->children());
    }
}
