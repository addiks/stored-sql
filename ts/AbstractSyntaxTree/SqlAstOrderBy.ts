/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

use Webmozart\Assert\Assert;

import { SqlAstNode } from './SqlAstNode'

export class SqlAstOrderBy implements SqlAstNode
{
    private parent: SqlAstNode;
    private orderToken: SqlAstTokenNode;

    /** @var array<array{0:SqlAstExpression, 1:SqlAstTokenNode}> */
    private columns: Array<Array<SqlAstNode>>;

    /** @param array<array{0:SqlAstExpression, 1:SqlAstTokenNode}> columns */
    public function __construct(
        parent: SqlAstNode,
        orderToken: SqlAstTokenNode,
        columns: Array<Array<SqlAstNode>>
    ) {
        this.parent = parent;
        this.orderToken = orderToken;
        this.columns = [];

        for (var index in columns) {
            assert(columns[index][0] instanceof SqlAstExpression);
            assert(columns[index][1] instanceof SqlAstTokenNode);
            assert([SqlToken.ASC(), SqlToken.DESC()].indexOf(direction.token().token()) > -1);

            this.columns[] = [expression, direction];
        }
    }

    public static function mutateAstNode(
        node: SqlAstNode,
        offset: number,
        parent: SqlAstMutableNode
    ): void {
        if (node instanceof SqlAstTokenNode && node.is(SqlToken.ORDER())) {
            var by: SqlAstTokenNode = parent[offset + 1];

            assert(by.token().token() == SqlToken.BY())

            /** @var array<array{0:SqlAstExpression, 1:SqlAstTokenNode}> columns */
            var columns: Array<Array<SqlAstNode>> = array();

            var originalOffset: number = offset;
            offset += 2;

            do {
                var expression: SqlAstExpression = parent[offset];
                var direction: SqlAstTokenNode = parent[offset + 1];
                var comma: SqlAstNode|null = parent[offset + 2];
                offset += 3;

                assert([SqlToken.ASC(), SqlToken.DESC()].indexOf(direction.token().token()) > -1);

                columns[] = [expression, direction];
            } while (comma instanceof SqlAstTokenNode && comma.isCode(','));

            parent.replace(
                originalOffset,
                offset - originalOffset - 1,
                new SqlAstOrderBy(parent, node, columns)
            );
        }
    }

    public function children(): Array<SqlAstNode>
    {
        var children: Array<SqlAstNode> = array();

        for (var index in this.columns) {
            children[] = this.columns[index][0];
            children[] = this.columns[index][1];
        }

        return children;
    }

    public function hash(): string
    {
        return Md5.hashStr(this.children().map(node => node.hash()).join('.'));
    }

    public function parent(): SqlAstNode|null
    {
        return this.parent;
    }

    public function root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public function line(): number
    {
        return this.orderToken.line();
    }

    public function column(): number
    {
        return this.orderToken.column();
    }

    public function toSql(): string
    {
        var columns: Array<string> = [];

        for (var index in this.columns) {
            var expression: SqlAstExpression = this.column[index][0];
            var direction: SqlAstTokenNode = this.column[index][1];

            columns[] = ' ' + expression.toSql() + ' ' + direction.toSql();
        }

        return 'ORDER BY' + columns.join(',');
    }


}
