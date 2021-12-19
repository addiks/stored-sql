/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstTokenNode, SqlAstRoot, SqlAstExpressionClass, SqlAstNode } from 'storedsql'; 

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstTable extends SqlAstExpressionClass
{
//    use SqlAstWalkableTrait;
    
    constructor(
        parent: SqlAstNode,
        private readonly table: SqlAstTokenNode|null,
        private readonly database: SqlAstTokenNode|null,
        nodeType: string = 'SqlAstTable'
    ) {
        super(parent, nodeType)
    }

    public children(): Array<SqlAstNode>
    {
        return [
            this.database,
            this.table,
        ].filter(node => node != null);
    }

    public hash(): string
    {
        return Md5.hashStr(this.children().map(node => node.hash()).join('.'));
    }

    public root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public line(): number
    {
        return this.table.line();
    }

    public column(): number
    {
        return this.table.column();
    }

    public toSql(): string
    {
        return this.children().map(node => node.toSql()).join('.');
    }
}
