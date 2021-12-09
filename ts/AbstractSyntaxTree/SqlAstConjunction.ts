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
    SqlAstExpression, SqlAstExpressionClass, SqlAstNode, SqlAstTokenNode, SqlToken, assert, SqlAstRoot, 
    SqlAstMutableNode
} from 'storedsql'

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstConjunction extends SqlAstExpressionClass
{
    /** @var array<array{0:SqlAstTokenNode|null, 1:SqlAstExpression}> */
    private parts: Array<Array<SqlAstNode>>;

    /** @param array<array{0:SqlAstTokenNode|null, 1:SqlAstExpression}> parts */
    constructor(
        parent: SqlAstNode,
        parts: Array<Array<SqlAstNode>>,
        nodeType: string = 'SqlAstConjunction'
    ) {
        super(parent, nodeType);
        
        this.parts = [];

        assert(parts.length >= 2);

        for (var index in parts) {
            var operator: SqlAstTokenNode|null = null;
            var expression: SqlAstExpression = (parts[index][1] as SqlAstExpression);

            if (parts[index][0] != null) {
                operator = (parts[index][0] as SqlAstTokenNode);
            }

            if (parseInt(index) > 0) {
                assert(operator instanceof SqlAstTokenNode);

            } else {
                assert(operator == null);
            }

            this.parts.push([operator, expression]);
        }
    }

    public children(): Array<SqlAstNode>
    {
        var children: Array<SqlAstNode|null> = [];

        for (var index in this.parts) {
            children.push(this.parts[index][0]);
            children.push(this.parts[index][1]);
        }

        return children.filter(node => node != null);
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
        return this.children()[0].line();
    }

    public column(): number
    {
        return this.children()[0].column();
    }

    public toSql(): string
    {
        var sql: string = "";

        for (var index in this.parts) {
            var operator: SqlAstTokenNode|null = null;
            var expression: SqlAstExpression = this.parts[index][1];

            if (this.parts[index][0] != null) {
                operator = (this.parts[index][0] as SqlAstTokenNode);
            }

            if (typeof operator == 'object') {
                sql += ' ' + operator.toSql();
            }

            sql += ' ' + expression.toSql();
        }

        return sql.trim();
    }
}

export function mutateConjunctionAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    var originalOffset: number = offset;

    /** @var array<array{0:SqlAstTokenNode, 1:SqlAstExpression}> parts */
    var parts: Array<Array<SqlAstNode>> = [[null, node]];

    while (node instanceof SqlAstExpressionClass) {
        node = null;

        var nextNode: SqlAstNode = parent.get(offset + 1);

        if (nextNode instanceof SqlAstTokenNode) {
            var isConjunction: boolean = false;
            isConjunction = isConjunction || nextNode.is(SqlToken.AND);
            isConjunction = isConjunction || nextNode.is(SqlToken.OR);

            if (isConjunction) {
                var otherNode: SqlAstNode = parent.get(offset + 2);

                if (otherNode instanceof SqlAstExpressionClass) {
                    parts.push([nextNode, otherNode]);
                    offset += 2;
                    node = otherNode;
                }
            }
        }
    }

    if (parts.length > 1) {
        parent.replace(originalOffset, offset - originalOffset + 1, new SqlAstConjunction(parent, parts));
    }
}

