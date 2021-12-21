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
    SqlAstNode, SqlToken, SqlAstTokenNode, SqlAstMutableNode, SqlAstNodeClass, SqlAstRoot, assert 
} from 'storedsql'

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstFrom extends SqlAstNodeClass
{
    constructor(
        parent: SqlAstNode,
        private readonly fromToken: SqlAstTokenNode,
        private readonly tableName: SqlAstTokenNode,
        private readonly alias: SqlAstTokenNode|null,
        nodeType: string = 'SqlAstFrom'
    ) {
        super(parent, nodeType);
    }

    public children(): Array<SqlAstNode>
    {
        return ([
            this.tableName,
            this.alias,
        ] as Array<SqlAstNode>).filter(node => node != null);
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
        return this.fromToken.line();
    }

    public column(): number
    {
        return this.fromToken.column();
    }

    public toSql(): string
    {
        return "FROM " + this.tableName.toSql() + ((typeof this.alias == 'object') ?(' ' + this.alias.toSql()) :'');
    }
}

export function mutateFromAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode && node.is(SqlToken.FROM)) {
        var tableName: SqlAstTokenNode = (parent.get(offset + 1) as SqlAstTokenNode);

        assert(tableName.is(SqlToken.SYMBOL));

        var alias: SqlAstTokenNode|null = (parent.get(offset + 2) as SqlAstTokenNode);
        
        if (alias instanceof SqlAstTokenNode && !alias.is(SqlToken.SYMBOL)) {
            alias = null;
            
        } else {
            alias = null;
        }

        parent.replace(offset, (alias == null) ? 2 : 3, new SqlAstFrom(parent, node, tableName, alias));
    }
}

