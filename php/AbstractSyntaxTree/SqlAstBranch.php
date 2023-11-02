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

use ErrorException;
use Webmozart\Assert\Assert;

abstract class SqlAstBranch implements SqlAstMutableNode
{
    use SqlAstWalkableTrait;

    /** @var list<SqlAstNode> $children */
    private array $children;

    /** @var array<int, callable> */
    private array $dirtyStack = array();

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

    public function mutate(array $mutators = array()): void
    {
        foreach ($mutators as $callback) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isCallable($callback);

            /** @var list<string, SqlAstNode> $processedNodes */
            $processedNodes = array();

            do {
                try {
                    /** @var bool $dirty */
                    $dirty = false;

                    $this->dirtyStack[] = function () use (&$dirty): void {
                        $dirty = true;
                    };

                    /** @var SqlAstNode $child */
                    foreach ($this->children as $offset => $child) {
                        if (isset($processedNodes[spl_object_id($child)])) {
                            continue;
                        }

                        $callback($child, $offset, $this);

                        if ($child instanceof SqlAstMutableNode) {
                            $child->mutate($mutators);
                        }

                        $processedNodes[spl_object_id($child)] = $child;

                        if ($dirty) {
                            break;
                        }
                    }

                } finally {
                    array_pop($this->dirtyStack);
                }
            } while ($dirty);
        }
    }

    public function replace(
        int $offset,
        int $length,
        SqlAstNode $newNode
    ): void {
        Assert::greaterThanEq($offset, 0);
        Assert::greaterThanEq($length, 0);
        Assert::lessThanEq($offset + $length, count($this->children) + 1);

        $this->markDirty();
        $this->children = array_merge(
            array_slice($this->children, 0, $offset),
            [$newNode],
            array_slice($this->children, $offset + $length)
        );
    }

    public function replaceNode(SqlAstNode $old, SqlAstNode $new): void
    {
        /** @var int|false $offset */
        $offset = array_search($old, $this->children, true);

        Assert::integer($offset, sprintf(
            "Node '%s' not found in branch '%s'!",
            $old->hash(),
            $this->hash()
        ));

        $this->replace($offset, 1, $new);
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

    public function canBeExecutedAsIs(): bool
    {
        /** @var SqlAstNode $child */
        foreach ($this->children as $child) {
            if (!$child->canBeExecutedAsIs()) {
                return false;
            }
        }

        return true;
    }

    private function markDirty(): void
    {
        if (!empty($this->dirtyStack)) {
            $this->dirtyStack[count($this->dirtyStack) - 1]();
        }
    }
}
