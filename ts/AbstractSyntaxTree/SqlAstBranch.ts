/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstMutableNodeClass, SqlAstNode, assert } from 'storedsql'
import { Md5 } from 'ts-md5/dist/md5'
import { sprintf } from 'sprintf-js';

export abstract class SqlAstBranch extends SqlAstMutableNodeClass
{
    private _children: Array<SqlAstNode>;

    constructor(
        nodeType: string,
        children: Array<SqlAstNode>,
        parent?: SqlAstNode
    ) {
        super(parent, nodeType);
        
        this._children = children;
    }

    public children(): Array<SqlAstNode>
    {
        return this._children;
    }

    public hash(): string
    {
        return Md5.hashStr(this._children.map(child => child.hash()).join('.'));
    }

    public walk(mutators: Array<Function>): void
    {
        do {
            var hashBefore: string = this.hash();

            for (var mutatorIndex in mutators) {
                var mutator: Function = mutators[mutatorIndex];

                for (var key in this._children) {
                    var child: SqlAstNode = this._children[key];
                    
                    var offset: number = parseInt(key);

                    mutator(child, offset, this);

                    if (hashBefore !== this.hash()) {
                        break;
                    }

                    if (child instanceof SqlAstMutableNodeClass) {
                        child.walk(mutators);
                    }
                }
            }
        } while (hashBefore !== this.hash());
    }

    public replace(
        offset: number,
        length: number,
        newNode: SqlAstNode
    ): void {
        assert(offset >= 0, sprintf('Replace Offset %d must be >= 0!', offset));
        assert(length >= 0, sprintf('Replace Length %d must be >= 0!', length));
        assert(offset + length <= (this._children.length + 1), sprintf(
            "Cannot replace %d children from offset %d which is after end of list (%d)!",
            length,
            offset,
            this._children.length + 1
        ));

        this._children = [
            ... this._children.slice(0, offset),
            newNode,
            ... this._children.slice(offset + length)
        ];
    }

    public replaceNode(oldNode: SqlAstNode, newNode: SqlAstNode): void
    {
        var offset: number = this._children.indexOf(oldNode);

        if (offset < 0) {
            throw sprintf(
                "Node '%s' not found in branch '%s'!",
                oldNode.hash(),
                this.hash()
            )
        }

        this.replace(offset, 1, newNode);
    }

    public get(offset: number): SqlAstNode | null
    {
        if (!this.has(offset)) {
            return null;
        }

        return this._children[offset];
    }

    public has(offset: number): boolean
    {
        return typeof this._children[offset] != "undefined";
    }

}
