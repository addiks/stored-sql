/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstNode } from './SqlAstNode';

interface SqlAstMutableNode extends SqlAstNode
{
    /**
     * Executes the given callback for every child-node in this AST recursively.
     * If AST was modified during execution, the callback will also be executed for any newly added nodes.
     * This will be repeatet until all nodes were executed.
     *
     * The node will be the first parameter for the callback.
     */
    public function walk(mutators: Function[]): void;

    /** Mutates this node so that a segment of the child-nodes are replaced with another node. */
    public function replaceNode(
        offset: number,
        length: number,
        newNode: SqlAstNode
    ): void;

    replaceNode(oldNode: SqlAstNode, newNode: SqlAstNode): void;

    get(number offset): SqlAstNode | null;
    has(number offset): boolean;
}
