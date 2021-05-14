/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstMutableNode } from './SqlAstMutableNode'
import { SqlAstNode } from './SqlAstNode'
import { Md5 } from 'ts-md5/dist/md5'

export abstract class SqlAstBranch implements SqlAstMutableNode
{
    private children: Array<SqlAstNode>;

    constructor(children: Array<SqlAstNode>)
    {
        this.children = children;
    }

    public children(): Array<SqlAstNode>
    {
        return this.children;
    }

    public function hash(): string
    {
        return Md5.hashStr(this.children.map(child => child.hash()).join('.'));
    }

    public function walk(mutators: Array<Function>): void
    {
        do {
            var hashBefore: string = this.hash();

            for (var mutatorIndex: number in mutators) {
                var mutator: Function = mutators[mutatorIndex];

                for (var offset: number in this.children) {
                    var child: SqlAstNode = this.children[offset];

                    mutator(child, offset, this);

                    if (hashBefore !== this.hash()) {
                        break;
                    }

                    if (child instanceof SqlAstMutableNode) {
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
        assert(offset >= 0);
        assert(length >= 0);
        assert(offset + length <= this.children.length + 1);

        this.children = [
            ... this.children.slice(0, offset),
            newNode,
            ... this.children.slice(offset + length)
        ];
    }

    public replaceNode(oldNode: SqlAstNode, newNode: SqlAstNode): void
    {
        var offset: number = this.children.indexOf(oldNode);

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

        return this.children[offset];
    }

    public has(offset: number): boolean
    {
        return typeof this.children[offset] != "undefined";
    }

}
