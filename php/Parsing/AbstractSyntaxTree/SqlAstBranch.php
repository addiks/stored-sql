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
use ErrorException;
use Iterator;
use Webmozart\Assert\Assert;

abstract class SqlAstBranch implements SqlAstMutableNode
{
    /** @var array<SqlAstNode> $children */
    private array $children;

    /** @param array<SqlAstNode> $children */
    public function __construct(array $children)
    {
        $this->children = array();

        foreach ($children as $child) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isInstanceOf($child, SqlAstNode::class);

            $this->children[] = $child;
        }
    }

    public function children(): array
    {
        return $this->children;
    }

    public function hash(): string
    {
        /** @var array<string> $childHashes */
        $childHashes = array_map(function (SqlAstNode $child) {
            return $child->hash();
        }, $this->children);

        return md5(implode('.', $childHashes));
    }

    public function walk(array $mutators = array()): void
    {
        do {
            /** @var string $hashBefore */
            $hashBefore = $this->hash();

            foreach ($mutators as $callback) {
                /** @psalm-suppress RedundantConditionGivenDocblockType */
                Assert::isCallable($callback);

                /** SqlAstNode $child */
                foreach ($this->children as $offset => $child) {
                    $callback($child, $offset, $this);

                    if ($hashBefore !== $this->hash()) {
                        break;
                    }

                    if ($child instanceof SqlAstMutableNode) {
                        $child->walk($mutators);
                    }
                }
            }
        } while ($hashBefore !== $this->hash());
    }

    public function replace(
        int $offset,
        int $length,
        SqlAstNode $newNode
    ): void {
        Assert::greaterThanEq($offset, 0);
        Assert::greaterThanEq($length, 0);
        Assert::lessThanEq($offset + $length, count($this->children));

        $this->children = array_merge(
            array_slice($this->children, 0, $offset),
            [$newNode],
            array_slice($this->children, $offset + $length)
        );
    }

    /** @return Iterator<SqlAstNode> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->children());
    }

    /** @param int $offset */
    public function offsetGet($offset): ?SqlAstNode
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        Assert::integer($offset);

        return $this->children[$offset] ?? null;
    }

    /** @param int $offset */
    public function offsetExists($offset): bool
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        Assert::integer($offset);

        return isset($this->children[$offset]);
    }

    /**
     * @param int        $offset
     * @param SqlAstNode $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->replace($offset, 1, $value);
    }

    /** @param int $offset */
    public function offsetUnset($offset): void
    {
        throw new ErrorException(sprintf('Objects of %s are immutable!', __CLASS__));
    }
}
