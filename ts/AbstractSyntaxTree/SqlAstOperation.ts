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
    SqlAstExpression, SqlAstExpressionClass, SqlAstNode, SqlAstTokenNode, SqlAstMutableNode, SqlAstRoot, SqlToken,
    SqlAstColumn
} from 'storedsql';

import { sprintf } from 'sprintf-js';

export class SqlAstOperation extends SqlAstExpressionClass
{
    constructor(
        parent: SqlAstNode,
        private readonly leftSide: SqlAstExpression,
        private readonly operator: SqlAstTokenNode,
        private readonly rightSide: SqlAstExpression,
        nodeType: string = 'SqlAstOperation'
    ) {
        super(parent, nodeType);
    }

    public children(): Array<SqlAstNode>
    {
        return [
            this.leftSide,
            this.operator,
            this.rightSide,
        ];
    }

    public hash(): string
    {
        return sprintf(
            '(%s/%s/%s)',
            this.leftSide.hash(),
            this.operator.hash(),
            this.rightSide.hash()
        );
    }

    public root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public line(): number
    {
        return this.operator.line();
    }

    public column(): number
    {
        return this.operator.column();
    }

    public toSql(): string
    {
        return this.leftSide.toSql() + ' ' + this.operator.toSql() + ' ' + this.rightSide.toSql();
    }
}


export function mutateOperationAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstExpressionClass || node instanceof SqlAstTokenNode) {
        var leftSide: SqlAstExpression|SqlAstTokenNode = node;
        var operator: SqlAstNode = parent.get(offset + 1);
        var rightSide: SqlAstNode = parent.get(offset + 2);
        
        var leftSideIsExpression = leftSide instanceof SqlAstExpressionClass;
        if (leftSide instanceof SqlAstTokenNode && leftSide.is(SqlToken.SYMBOL)) {
            leftSideIsExpression = true;
        }

        var rightSideIsExpression = rightSide instanceof SqlAstExpressionClass;
        if (rightSide instanceof SqlAstTokenNode && rightSide.is(SqlToken.SYMBOL)) {
            rightSideIsExpression = true;
        }
        
        var isOperator: boolean = operator instanceof SqlAstTokenNode;
        console.log([operator, isOperator]);
        if (isOperator) {
            var operatorToken: SqlAstTokenNode = (operator as SqlAstTokenNode);
            
            isOperator = isOperator || operatorToken.is(SqlToken.OPERATOR);
            isOperator = isOperator || operatorToken.is(SqlToken.LIKE);
            isOperator = isOperator || operatorToken.is(SqlToken.IS);
            isOperator = isOperator || operatorToken.is(SqlToken.IN);

            if (isOperator && leftSideIsExpression && rightSideIsExpression) {
                if (leftSide instanceof SqlAstTokenNode && leftSide.is(SqlToken.SYMBOL)) {
                    leftSide = new SqlAstColumn(parent, leftSide, null, null);

                    parent.replace(offset, 1, leftSide);
                }
                
                if (rightSide instanceof SqlAstTokenNode && rightSide.is(SqlToken.SYMBOL)) {
                    rightSide = new SqlAstColumn(parent, rightSide, null, null);

                    parent.replace(offset, 1, rightSide);
                }

                parent.replace(offset, 3, new SqlAstOperation(
                    parent,
                    leftSide,
                    operatorToken,
                    rightSide
                ));
            }
        }
    }
}

