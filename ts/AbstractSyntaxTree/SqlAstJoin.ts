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
    SqlAstNode, SqlAstNodeClass, SqlAstTokenNode, SqlAstExpression, SqlToken, SqlAstRoot, assert, SqlAstMutableNode,
    SqlAstExpressionClass, mutateParenthesisAstNode
} from 'storedsql'

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstJoin extends SqlAstNodeClass
{

    constructor(
        parent: SqlAstNode,
        private readonly joinToken: SqlAstTokenNode,
        private readonly tableName: SqlAstTokenNode,
        private readonly joinType: SqlAstTokenNode|null,
        private readonly alias: SqlAstTokenNode|null,
        private readonly onOrUsing: SqlAstTokenNode|null,
        private readonly condition: SqlAstExpression|null,
        nodeType: string = 'SqlAstJoin'
    ) {
        super(parent, nodeType);
    }

    public children(): Array<SqlAstNode>
    {
        return [
            this.joinType,
            this.tableName,
            this.alias,
            this.onOrUsing,
            this.condition,
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
        return this.joinToken.line();
    }

    public column(): number
    {
        return this.joinToken.column();
    }

    public toSql(): string
    {
        var sql: string = this.joinType.toSql() + " JOIN " + this.tableName.toSql();

        if (typeof this.alias == 'object') {
            sql += " " + this.alias.toSql();
        }

        if (typeof this.condition == 'object') {
            sql += " " + this.onOrUsing.toSql() + " " + this.condition.toSql();
        }

        return sql;
    }

}

export function mutateJoinAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode && (node as SqlAstTokenNode).is(SqlToken.JOIN)) {
        var joinType: SqlAstNode|null = parent.get(offset - 1);
        var tableName: SqlAstTokenNode = (parent.get(offset + 1) as SqlAstTokenNode);
        var alias: SqlAstNode|null = parent.get(offset + 2);
        
        if (!(tableName instanceof SqlAstTokenNode && (tableName as SqlAstTokenNode).is(SqlToken.SYMBOL))) {
            tableName = null;
        }

        if (!(alias instanceof SqlAstTokenNode && (alias as SqlAstTokenNode).is(SqlToken.SYMBOL))) {
            alias = null;
        }

        var beginOffset: number = (joinType != null) ? offset - 1 : offset;
        var endOffset: number = (alias != null) ? offset + 2 : offset + 1;
        var onOrUsing: SqlAstNode|null = parent.get(endOffset + 1);
        var onOrUsingToken: SqlAstTokenNode|null = null;
        var condition: SqlAstExpression|null = null;

        if (typeof onOrUsing == 'object' && onOrUsing.nodeType == 'SqlAstTokenNode') {
            onOrUsingToken = (onOrUsing as SqlAstTokenNode);
            
            if (onOrUsingToken.is(SqlToken.ON) || onOrUsingToken.is(SqlToken.USING)) {
                
                parent.walk([mutateParenthesisAstNode]);
                
                condition = parent.get(endOffset + 2);
                endOffset += 2;

                assert(condition instanceof SqlAstExpressionClass);
            }
        }

        parent.replace(beginOffset, 1 + endOffset - beginOffset, new SqlAstJoin(
            parent,
            (node as SqlAstTokenNode),
            tableName,
            (joinType as SqlAstTokenNode),
            (alias as SqlAstTokenNode),
            onOrUsingToken,
            condition
        ));
    }
}

