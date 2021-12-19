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
    SqlAstNode, SqlAstNodeClass, assert, SqlAstTokenNode, SqlAstExpression, SqlAstMutableNode, SqlToken,
    SqlAstRoot, SqlAstExpressionClass
} from 'storedsql'

import { Md5 } from 'ts-md5/dist/md5'

export class SqlAstOrderBy extends SqlAstNodeClass
{
    /** @var array<array{0:SqlAstExpression, 1:SqlAstTokenNode}> */
    private columns: Array<Array<SqlAstNode>>;

    /** @param array<array{0:SqlAstExpression, 1:SqlAstTokenNode}> columns */
    constructor(
        parent: SqlAstNode,
        private readonly orderToken: SqlAstTokenNode,
        columns: Array<Array<SqlAstNode>>,
        nodeType: string = 'SqlAstOrderBy'
    ) {
        super(parent, nodeType);
        
        this.columns = new Array();

        for (var index in columns) {
            assert(columns[index][0] instanceof SqlAstExpressionClass);
            assert(columns[index][1] instanceof SqlAstTokenNode);
            
            let expression: SqlAstExpressionClass = (columns[index][0] as SqlAstExpressionClass);
            let direction: SqlAstTokenNode = (columns[index][1] as SqlAstTokenNode);
            
            assert([SqlToken.ASC, SqlToken.DESC].indexOf(direction.token.token()) > -1);

            this.columns.push([
                expression,
                direction
            ]);
        }
    }

    public children(): Array<SqlAstNode>
    {
        var children: Array<SqlAstNode> = new Array();

        for (var index in this.columns) {
            children.push(this.columns[index][0]);
            children.push(this.columns[index][1]);
        }

        return children;
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
        return this.orderToken.line();
    }

    public column(): number
    {
        return this.orderToken.column();
    }

    public toSql(): string
    {
        var columns: Array<string> = [];

        for (var index in this.columns) {
            var expression: SqlAstExpression = this.column[index][0];
            var direction: SqlAstTokenNode = this.column[index][1];

            columns.push(' ' + expression.toSql() + ' ' + direction.toSql());
        }

        return 'ORDER BY' + columns.join(',');
    }


}


export function mutateOrderByAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode && node.is(SqlToken.ORDER)) {
        var by: SqlAstTokenNode = parent[offset + 1];

        assert(by.token.token() == SqlToken.BY)

        /** @var array<array{0:SqlAstExpression, 1:SqlAstTokenNode}> columns */
        var columns: Array<Array<SqlAstNode>> = new Array();

        var originalOffset: number = offset;
        offset += 2;

        do {
            var expression: SqlAstExpression = parent[offset];
            var direction: SqlAstTokenNode = parent[offset + 1];
            var comma: SqlAstNode|null = parent[offset + 2];
            offset += 3;

            assert([SqlToken.ASC, SqlToken.DESC].indexOf(direction.token.token()) > -1);

            columns.push([expression, direction]);
        } while (comma instanceof SqlAstTokenNode && comma.isCode(','));

        parent.replace(
            originalOffset,
            offset - originalOffset - 1,
            new SqlAstOrderBy(parent, node, columns)
        );
    }
}

