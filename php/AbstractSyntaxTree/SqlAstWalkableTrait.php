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

use Psalm\Issue\RedundantConditionGivenDocblockType;
use Webmozart\Assert\Assert;

/** @psalm-import-type SqlNodeWalker from SqlAstNode */
trait SqlAstWalkableTrait
{
    /** @param array<SqlNodeWalker> $callbacks */
    public function walk(array $callbacks = array()): void
    {
        Assert::isInstanceOf($this, SqlAstNode::class);

        /** @var string $hashBefore */
        $hashBefore = $this->hash();

        foreach ($callbacks as $callback) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isCallable($callback);

            /** SqlAstNode $child */
            foreach ($this->children() as $offset => $child) {
                $callback($child, $offset, $this);

                /** @var string $hashAfter */
                $hashAfter = $this->hash();

                Assert::same($hashBefore, $hashAfter, sprintf(
                    'Illegal mutation of SQL-AST-Node "%s" during walk operation! ("%s" != "%s")',
                    get_class($this),
                    $hashBefore,
                    $hashAfter
                ));

                if ($child instanceof SqlAstMutableNode) {
                    $child->walk($callbacks);
                }
            }
        }
    }
}
