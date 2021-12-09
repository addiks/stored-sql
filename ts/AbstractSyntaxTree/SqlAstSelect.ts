/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstNode, UnparsableSqlException, SqlToken, SqlAstTokenNode, SqlAstExpression, SqlAstFrom, SqlAstJoin, 
    SqlAstWhere, SqlAstOrderBy, SqlAstMutableNode, SqlAstNodeClass, assert } from 'storedsql';

export class SqlAstSelect implements SqlAstNodeClass
{
    private selectToken: SqlAstTokenNode;
    private columns: Array<SqlAstExpression>;
    private joins: Array<SqlAstJoin>;
    private orderBy: SqlAstOrderBy|null;

    # TODO: HAVING, LIMIT,

    constructor(
        parent: SqlAstNode,
        private readonly selectToken: SqlAstTokenNode,
        columns: Array<SqlAstExpression>,
        private readonly from: SqlAstFrom|null,
        joins: Array<SqlAstJoin>,
        private readonly where: SqlAstWhere|null,
        private readonly orderBy: SqlAstOrderBy|null,
        nodeType: string = 'SqlAstSelect'
    ) {
        assert(columns.length > 0);
        
        super(parent, nodeType);

        this.columns = [];
        this.joins = [];

        for (var alias in columns) {
            var column: SqlAstExpression = columns[alias];

            assert(column instanceof SqlAstExpression);
            
            this.columns[alias] = column;
        }

        for (var join: SqlAstJoin of joins) {
            assert(join instanceof SqlAstJoin);
            
            this.joins[] = join;
        }
    }

    public children(): array
    {
        return ([
            this.from,
            this.where,
            this.orderBy,
        ] + this.columns + this.joins).filter(node => node != null);
    }

    public hash(): string
    {
        return md5.hashStr(this.children().map(node => node.hash()).join('.'));
    }

    public parent(): SqlAstNode|null
    {
        return this.parent;
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
        var columnsSql: Array<string> = array();

        for (var alias: string in this.columns) {
            var column: SqlAstExpression = this.columns[alias];

            columnsSql[] = column.toSql() . (is_string(alias) ?(' ' . alias) :'');
        }

        sql += columnsSql.join(', ');

        if (typeof this.from == 'object') {
            sql += ' ' . this.from.toSql();
        }

        for (var join: SqlAstJoin of this.joins)
            sql += ' ' . join.toSql();
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
    if (node instanceof SqlAstTokenNode && node.is(SqlToken.SELECT())) {
        var beginOffset: number = offset;
        var columns: Array<SqlAstExpression> = [];

        do {
            offset++;

            UnparsableSqlException::assertType(parent, offset, SqlAstExpression::class);

            var column: SqlAstExpression = parent[offset];
            var alias: string|null = null;

            if (is_null(alias)) {
                columns[] = column;

            } else {
                columns[alias] = column;
            }

            var comma: SqlAstNode|null = parent[offset + 1];
            var isComma: boolean = (comma instanceof SqlAstTokenNode && comma.is(SqlToken.COMMA()));

            if (isComma) {
                offset++;
            }
        } while (isComma);

        var from: SqlAstNode|null = parent[offset + 1];

        if (from instanceof SqlAstFrom) {
            offset++;

        } else {
            from = null;
        }

        var joins: Array<SqlAstJoin> = array();

        do {
            var join: SqlAstNode|null = parent[offset + 1];

            if (join instanceof SqlAstJoin) {
                joins[] = join;
                offset++;
            }
        } while (join instanceof SqlAstJoin);

        var where: SqlAstNode|null = parent[offset + 1];

        if (where instanceof SqlAstWhere) {
            offset++;

        } else {
            where = null;
        }

        var orderBy: SqlAstNode|null = parent[offset + 1];

        if (orderBy instanceof SqlAstOrderBy) {
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

