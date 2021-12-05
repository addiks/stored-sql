/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { 
    SqlToken, SqlAstMutableNode, SqlAstTokenNode, SqlAstNode, SqlAstExpressionClass, SqlAstRoot, assert 
} from 'storedsql'

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstColumn extends SqlAstExpressionClass
{
    private _column: SqlAstTokenNode;
    
    constructor(
        parent: SqlAstNode,
        column: SqlAstTokenNode,
        private readonly table: SqlAstTokenNode|null,
        private readonly database: SqlAstTokenNode|null,
        nodeType: string = 'SqlAstColumn'
    ) {
        super(parent, nodeType);
        
        this._column = column;
    }

    public children(): Array<SqlAstNode>
    {
        return [
            this.database,
            this.table,
            this._column,
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
        return this._column.line();
    }

    public column(): number
    {
        return this._column.column();
    }

    public toSql(): string
    {
        return this.children().map(node => node.toSql()).join('.');
    }
}


export function mutateColumnAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode && node.token.is(SqlToken.SYMBOL)) {

        var length: number = 1;
        var column: SqlAstTokenNode = node;
        var table: SqlAstTokenNode|null = null;
        var database: SqlAstTokenNode|null = null;
        var dot: SqlAstNode = parent[offset + 1];

        if (dot instanceof SqlAstTokenNode && dot.is(SqlToken.DOT)) {
            node = parent[offset + 2];
            assert(node instanceof SqlAstTokenNode);

            if (node.is(SqlToken.SYMBOL)) {
                length += 2;

                table = column;
                column = node;

                dot = parent[offset + 3];

                if (dot instanceof SqlAstTokenNode && dot.is(SqlToken.DOT)) {
                    node = parent[offset + 4];
                    assert(node instanceof SqlAstTokenNode);

                    if (node.is(SqlToken.SYMBOL)) {
                        length += 2;

                        database = table;
                        table = column;
                        column = node;
                    }
                }
            }

            parent.replace(offset, length, new SqlAstColumn(
                parent,
                column,
                table,
                database
            ));
        }
    }
}

