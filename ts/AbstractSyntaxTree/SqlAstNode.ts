/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlTokens } from 'storedsql';

export interface SqlAstNode
{
    readonly parent?: SqlAstNode;
    readonly nodeType: string;

    children(): Array<SqlAstNode>;

    /**
     * Represents the state of this part of the AST.
     * If contents change, this hash must change.
     * Used to determine when the AST stops changing during parsing phase.
     */
    hash(): string;

    root(): SqlAstRoot;

    line(): number;

    column(): number;

    toSql(): string;
}

export interface SqlAstRoot extends SqlAstMutableNode
{
    readonly tokens: SqlTokens;
}

export interface SqlAstMutableNode extends SqlAstNode
{
    /**
     * Executes the given callback for every child-node in this AST recursively.
     * If AST was modified during execution, the callback will also be executed for any newly added nodes.
     * This will be repeatet until all nodes were executed.
     *
     * The node will be the first parameter for the callback.
     */
    walk(mutators: Array<Function>): void;

    /** Mutates this node so that a segment of the child-nodes are replaced with another node. */
    replace(
        offset: number,
        length: number,
        newNode: SqlAstNode
    ): void;

    replaceNode(oldNode: SqlAstNode, newNode: SqlAstNode): void;

    get(offset: number): SqlAstNode;
    
    has(offset: number): boolean;
}

/** Typescript cannot test for interfaces at runtime, test for this class instead. */
export class SqlAstNodeClass implements SqlAstNode
{
    constructor(
        public readonly parent?: SqlAstNode,
        public readonly nodeType: string = 'SqlAstNode'
    ) {
    }
    
    public children(): Array<SqlAstNode> 
    {
        throw new Error('SqlAstNode is an interface! Implement missing methods!');
    }

    public hash(): string
    {
        throw new Error('SqlAstNode is an interface! Implement missing methods!');
    }

    public root(): SqlAstRoot
    {
        throw new Error('SqlAstNode is an interface! Implement missing methods!');
    }

    public line(): number
    {
        throw new Error('SqlAstNode is an interface! Implement missing methods!');
    }

    public column(): number
    {
        throw new Error('SqlAstNode is an interface! Implement missing methods!');
    }

    public toSql(): string
    {
        throw new Error('SqlAstNode is an interface! Implement missing methods!');
    }
}


