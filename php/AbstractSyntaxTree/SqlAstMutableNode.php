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

use ArrayAccess;

/**
 * @psalm-type Mutator = callable(SqlAstNode, int, SqlAstMutableNode): void
 *
 * @extends ArrayAccess<int, SqlAstNode>
 */
interface SqlAstMutableNode extends ArrayAccess, SqlAstNode
{
    /**
     * Executes the given callback for every child-node in this AST recursively.
     *
     * If AST was modified during execution, the callback will also be executed for any newly added nodes.
     * This will be repeatet until all nodes were executed.
     *
     * The node will be the first parameter for the callback.
     *
     * @param array<Mutator> $mutators
     */
    public function mutate(array $mutators = array()): void;

    /** Mutates this node so that a segment of the child-nodes are replaced with another node. */
    public function replace(
        int $offset,
        int $length,
        SqlAstNode $newNode
    ): void;

    public function replaceNode(SqlAstNode $old, SqlAstNode $new): void;
}
