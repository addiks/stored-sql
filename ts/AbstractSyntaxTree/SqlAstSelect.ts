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
    SqlAstNode, UnparsableSqlException, SqlToken, SqlAstTokenNode, SqlAstExpression, SqlAstFrom, SqlAstJoin, 
    SqlAstWhere, SqlAstOrderBy, SqlAstMutableNode, SqlAstNodeClass, assert, assertSqlType, SqlAstRoot,
    SqlAstExpressionClass, SqlAstColumn, mutateJoinAstNode
} from 'storedsql';

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstSelect extends SqlAstNodeClass
{
    // TODO: HAVING, LIMIT,

    constructor(
        parent: SqlAstNode,
        public readonly selectToken: SqlAstTokenNode,
        public readonly columns: Map<string, SqlAstExpression>,
        public readonly from: SqlAstFrom|null,
        public readonly joins: Array<SqlAstJoin>,
        public readonly where: SqlAstWhere|null,
        public readonly orderBy: SqlAstOrderBy|null,
        nodeType: string = 'SqlAstSelect'
    ) {
        super(parent, nodeType);
        assert(this.columnsAsArray().length > 0);
    }
    
    public children(): Array<SqlAstNode>
    {
        return ([] as Array<SqlAstNode>).concat(
            this.columnsAsArray(),
            [this.from],
            this.joins,
            [this.where],
            [this.orderBy],
        ).filter(node => node != null)
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
        return this.selectToken.line();
    }

    public column(): number
    {
        return this.selectToken.column();
    }

    public toSql(): string
    {
        var sql: string = "SELECT ";
        var columnsSql: Array<string> = new Array();

        for (var alias of this.columns.keys()) {
            var column: SqlAstExpression = this.columns.get(alias);
            var columnSql: string = column.toSql();

            columnsSql.push(columnSql + ((alias != columnSql) ?(' AS ' + alias) :''));
        }

        sql += columnsSql.join(', ');

        if (this.from != null) {
            sql += ' ' + this.from.toSql();
        }

        for (var join of this.joins) {
            sql += ' ' + join.toSql();
        }

        if (this.where != null) {
            sql += ' ' + this.where.toSql();
        }

        if (this.orderBy != null) {
            sql += ' ' + this.orderBy.toSql();
        }

        return sql;
    }
    
    private columnsAsArray(): Array<SqlAstExpression>
    {
        return Array.from(this.columns, ([alias, column]) => (column));
    }
}


export function mutateSelectAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode && node.is(SqlToken.SELECT)) {
        var beginOffset: number = offset;
        var columns: Map<string, SqlAstExpression> = new Map();

        do {
            offset++;
            
            let columnNode: SqlAstNode|null = parent.get(offset);
            
            if (columnNode instanceof SqlAstTokenNode && (columnNode as SqlAstTokenNode).is(SqlToken.SYMBOL)) {
                parent.replaceNode(columnNode, new SqlAstColumn(parent, columnNode, null, null))
            }

            assertSqlType(parent, offset, 'SqlAstExpression', node => node instanceof SqlAstExpressionClass);

            var column: SqlAstExpression = parent.get(offset);
            var alias: string|null = null;
            var aliasNode: SqlAstTokenNode|null = parent.get(offset+1) as SqlAstTokenNode;
            
            if (aliasNode != null && aliasNode.nodeType == 'SqlAstTokenNode' && aliasNode.is(SqlToken.AS)) {
                aliasNode = parent.get(offset+2) as SqlAstTokenNode;
                alias = aliasNode.token.code();
                offset += 2;
                
            } else {
                alias = column.toSql();
            }
            
            columns.set(alias, column);

            var comma: SqlAstNode|null = parent.get(offset + 1);
            var isComma: boolean = (comma instanceof SqlAstTokenNode && comma.is(SqlToken.COMMA));

            if (isComma) {
                offset++;
            }
        } while (isComma);

        var from: SqlAstFrom|null = (parent.get(offset + 1) as SqlAstFrom);

        if (from instanceof SqlAstFrom && from.nodeType == 'SqlAstFrom') {
            offset++;
        } else {
            from = null;
        }

        var joins: Array<SqlAstJoin> = new Array();

        do {
            parent.walk([mutateJoinAstNode]);
            
            var join: SqlAstNode|null = parent.get(offset + 1);

            if (join instanceof SqlAstJoin && join.nodeType == 'SqlAstJoin') {
                joins.push(join);
                offset++;
            }
        } while (join instanceof SqlAstJoin && join.nodeType == 'SqlAstJoin');
        
        var where: SqlAstWhere|null = (parent.get(offset + 1) as SqlAstWhere);

        if (where instanceof SqlAstWhere && where.nodeType == 'SqlAstWhere') {
            offset++;
        } else {
            where = null;
        }

        var orderBy: SqlAstOrderBy|null = (parent.get(offset + 1) as SqlAstOrderBy);

        if (orderBy instanceof SqlAstOrderBy && orderBy.nodeType == 'SqlAstOrderBy') {
            offset++;
        } else {
            orderBy = null;
        }
        
        parent.replace(beginOffset, 1 + offset - beginOffset, new SqlAstSelect(
            parent,
            node,
            columns,
            from,
            joins,
            where,
            orderBy
        ));
    }
}

