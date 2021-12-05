/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstNodeClass, SqlAstNode } from 'storedsql';

// If you are looking for the SqlAstMutableNode interface,
// that one is defined in 'SqlAstNode.ts' to prevent a circular reference.

/** Typescript cannot test for interfaces at runtime, test for this class instead. */
export class SqlAstMutableNodeClass extends SqlAstNodeClass
{
    constructor(
        parent?: SqlAstNode,
        nodeType: string = 'SqlAstMutableNode'
    ) {
        super(parent, nodeType);
    }
    
    public walk(mutators: Array<Function>): void
    {
        throw new Error('SqlAstMutableNode is an interface! Implement missing methods!');
    }

    public replace(
        offset: number,
        length: number,
        newNode: SqlAstNode
    ): void {
        throw new Error('SqlAstMutableNode is an interface! Implement missing methods!');
    }

    public replaceNode(oldNode: SqlAstNode, newNode: SqlAstNode): void
    {
        throw new Error('SqlAstMutableNode is an interface! Implement missing methods!');
    }

    public get(offset: number): SqlAstNode
    {
        throw new Error('SqlAstMutableNode is an interface! Implement missing methods!');
    }
    
    public has(offset: number): boolean
    {
        throw new Error('SqlAstMutableNode is an interface! Implement missing methods!');
    }
}
