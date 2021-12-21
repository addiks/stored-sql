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
    SqlAstExpression, SqlAstExpressionClass, SqlAstNode, SqlAstTokenNode, SqlAstMutableNode, SqlToken, SqlAstRoot, 
    assertSqlToken, SqlAstColumn, assertSqlType
} from 'storedsql'

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstParenthesis extends SqlAstExpressionClass
{
    constructor(
        parent: SqlAstNode,
        private readonly bracketOpening: SqlAstTokenNode,
        private readonly expressions: Array<SqlAstExpression>,
        nodeType: string = 'SqlAstParenthesis'
    ) {
        super(parent, nodeType);
    }

    public children(): Array<SqlAstNode>
    {
        return this.expressions;
    }

    public hash(): string
    {
        return Md5.hashStr(this.expressions.map(node => node.hash()).join('.'));
    }

    public root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public line(): number
    {
        return this.bracketOpening.line();
    }

    public column(): number
    {
        return this.bracketOpening.column();
    }

    public toSql(): string
    {
        return '(' + this.expressions.map(node => node.toSql()).join(', ') + ')';
    }
}

export function mutateParenthesisAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    // TODO: also allow SELECT in here, for sub-selects
        
    if (node instanceof SqlAstTokenNode && node.is(SqlToken.BRACKET_OPENING)) {
        
        let expressions: Array<SqlAstExpression> = [];
        let currentOffset: number = offset;
        let close: SqlAstNode|null = null;
        
        do {
            currentOffset++;
            
            if (parent.get(currentOffset) instanceof SqlAstTokenNode) {
                parent.replace(
                    currentOffset, 
                    1,
                    new SqlAstColumn(parent, (parent.get(currentOffset) as SqlAstTokenNode), null, null)
                );
            }
            
            assertSqlType(parent, offset + 1, 'SqlAstExpression', node => node instanceof SqlAstExpressionClass);
            
            expressions.push(parent.get(currentOffset) as SqlAstExpression);
            
            currentOffset++;
            
            close = parent.get(currentOffset);
        } while (close instanceof SqlAstTokenNode && close.is(SqlToken.COMMA));
        
        assertSqlToken(parent, currentOffset, SqlToken.BRACKET_CLOSING);

        parent.replace(offset, 1 + currentOffset - offset, new SqlAstParenthesis(parent, node, expressions));
    }
}

