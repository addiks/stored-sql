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
        private readonly selectToken: SqlAstTokenNode,
        private readonly columns: Array<SqlAstExpression>,
        private readonly from: SqlAstFrom|null,
        private readonly joins: Array<SqlAstJoin>,
        private readonly where: SqlAstWhere|null,
        private readonly orderBy: SqlAstOrderBy|null,
        nodeType: string = 'SqlAstSelect'
    ) {
        super(parent, nodeType);
        
        assert(columns.length > 0);
    }

    public children(): Array<SqlAstNode>
    {
        return ([] as Array<SqlAstNode>).concat(
            this.columns,
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

        for (var alias in this.columns) {
            var column: SqlAstExpression = this.columns[alias];

            columnsSql.push(column.toSql() + ((typeof alias == 'string') ?(' ' + alias) :''));
        }

        sql += columnsSql.join(', ');

        if (typeof this.from == 'object') {
            sql += ' ' + this.from.toSql();
        }

        for (var join of this.joins) {
            sql += ' ' + join.toSql();
        }

        if (typeof this.where == 'object') {
            sql += ' ' + this.where.toSql();
        }

        if (typeof this.orderBy == 'object') {
            sql += ' ' + this.orderBy.toSql();
        }

        return sql;
    }
}


export function mutateSelectAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode && node.is(SqlToken.SELECT)) {
        var beginOffset: number = offset;
        var columns: Array<SqlAstExpression> = [];

        do {
            offset++;
            
            let columnNode: SqlAstNode|null = parent.get(offset);
            
            if (columnNode instanceof SqlAstTokenNode && (columnNode as SqlAstTokenNode).is(SqlToken.SYMBOL)) {
                parent.replaceNode(columnNode, new SqlAstColumn(parent, columnNode, null, null))
            }

            assertSqlType(parent, offset, 'SqlAstExpression', node => node instanceof SqlAstExpressionClass);

            var column: SqlAstExpression = parent.get(offset);
            var alias: string|null = null;

            if (alias == null) {
                columns.push(column);

            } else {
                columns[alias] = column;
            }

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

