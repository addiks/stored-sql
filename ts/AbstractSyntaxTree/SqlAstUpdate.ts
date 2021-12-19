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
    SqlAstNode, SqlAstNodeClass, assert, SqlAstOperation, SqlAstJoin, SqlAstWhere, SqlAstOrderBy, SqlAstColumn,
    SqlToken, assertSqlType, assertSqlToken, SqlAstTokenNode, SqlAstTable, SqlAstMutableNode, SqlAstRoot
} from 'storedsql';

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstUpdate extends SqlAstNodeClass
{
 //   use SqlAstWalkableTrait;

    private operations: Array<SqlAstOperation>;

    constructor(
        parent: SqlAstNode,
        private readonly updateToken: SqlAstTokenNode,
        private readonly tableName: SqlAstTable,
        operations: Array<SqlAstOperation>,
        private readonly joins: Array<SqlAstJoin>,
        private readonly where: SqlAstWhere|null,
        private readonly orderBy: SqlAstOrderBy|null,
        nodeType: string = 'SqlAstUpdate'
    ) {
        super(parent, nodeType);

        assert(operations.length > 0)
        
        this.operations = Array();
        for (let operation of operations) {
            assert(operation.leftSide instanceof SqlAstColumn);
            assert('=' == operation.operator.toSql());

            this.operations.push(operation);
        }
    }

    public children(): Array<SqlAstNode>
    {
        return ([
            this.tableName,
            this.where,
            this.orderBy,
        ] as Array<SqlAstNode>).concat(this.operations, this.joins).filter(node => node != null);
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
        return this.updateToken.line();
    }

    public column(): number
    {
        return this.updateToken.column();
    }

    public toSql(): string
    {
        let sql: string = 'UPDATE ' + this.tableName.toSql();
        
        for (let join of this.joins) {
            sql += ' ' + join.toSql();
        }

        let columnsSql: Array<string> = Array();

        for (let operation of this.operations) {
            columnsSql.push(operation.toSql());
        }

        sql += columnsSql.join(', ');

        if (typeof this.where == 'object') {
            sql += ' ' + this.where.toSql();
        }

        if (typeof this.orderBy == 'object') {
            sql += ' ' + this.orderBy.toSql();
        }

        return sql;
    }
}

export function mutateUpdateAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode && node.is(SqlToken.UPDATE)) {
        let beginOffset: number = offset;
        offset++;

        if (parent.get(offset) instanceof SqlAstColumn) {
            (parent.get(offset) as SqlAstColumn).convertToTable();

        } else if (parent.get(offset) instanceof SqlAstTokenNode) {
            parent.replaceNode(
                parent.get(offset), 
                new SqlAstTable(
                    parent, 
                    (parent.get(offset) as SqlAstTokenNode), 
                    null
                )
            );
        }

        assertSqlType(parent, offset, 'SqlAstTable');

        let tableName: SqlAstTable = (parent.get(offset) as SqlAstTable);
        let joins: Array<SqlAstJoin> = Array();
        let join: SqlAstNode|null = null;

        do {
            join = parent.get(offset + 1);

            if (join instanceof SqlAstJoin) {
                joins.push(join);
                offset++;
            }
        } while (join instanceof SqlAstJoin);

        offset++;
        assertSqlToken(parent, offset, SqlToken.SET);

        let operations: Array<SqlAstOperation> = Array();
        let isComma: boolean = false;

        do {
            offset++;
            assertSqlType(parent, offset, 'SqlAstOperation');

            let operation: SqlAstOperation = (parent.get(offset) as SqlAstOperation);

            operations.push(operation);

            let comma: SqlAstTokenNode|null = (parent.get(offset + 1) as SqlAstTokenNode);
            isComma = comma instanceof SqlAstTokenNode && comma.is(SqlToken.COMMA);

            if (isComma) {
                offset++;
            }
        } while (isComma);

        let where: SqlAstWhere|null = (parent.get(offset + 1) as SqlAstWhere);

        if (where instanceof SqlAstWhere) {
            offset++;

        } else {
            where = null;
        }

        let orderBy: SqlAstOrderBy|null = (parent.get(offset + 1) as SqlAstOrderBy);

        if (orderBy instanceof SqlAstOrderBy) {
            offset++;

        } else {
            orderBy = null;
        }

        parent.replace(beginOffset, 1 + offset - beginOffset, new SqlAstUpdate(
            parent,
            node,
            tableName,
            operations,
            joins,
            where,
            orderBy
        ));
    }
}

