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
    SqlToken, SqlTokenInstance, SqlAstNode, SqlAstExpressionClass, SqlAstTokenNode, SqlAstMutableNode,
    SqlAstRoot
} from 'storedsql'

export function mutateLiteralAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode) {
        var token: SqlTokenInstance = node.token;
        var isSomeLiteralNode: boolean = false;
        
        isSomeLiteralNode = isSomeLiteralNode || token.is(SqlToken.LITERAL);
        isSomeLiteralNode = isSomeLiteralNode || token.is(SqlToken.NUMERIC);
        isSomeLiteralNode = isSomeLiteralNode || token.is(SqlToken.T_NULL);

        if (isSomeLiteralNode) {
            parent.replace(offset, 1, new SqlAstLiteral(parent, node));
        }
    }
}

export class SqlAstLiteral extends SqlAstExpressionClass
{
    constructor(
        parent: SqlAstNode,
        private readonly literal: SqlAstTokenNode,
        nodeType: string = 'SqlAstLiteral'
    ) {
        super(parent, nodeType);
    }

    public children(): Array<SqlAstNode>
    {
        return [this.literal];
    }

    public hash(): string
    {
        return this.literal.hash();
    }

    public root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public line(): number
    {
        return this.literal.line();
    }

    public column(): number
    {
        return this.literal.column();
    }

    public toSql(): string
    {
        return this.literal.toSql();
    }
}
