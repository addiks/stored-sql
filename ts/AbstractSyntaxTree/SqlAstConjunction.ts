/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstExpression } from './SqlAstExpression'
import { SqlAstNode } from './SqlAstNode'
import { SqlAstTokenNode } from './SqlAstTokenNode'
import { SqlToken } from '../SqlToken'

export class SqlAstConjunction implements SqlAstExpression
{
    private parent: SqlAstNode;

    /** @var array<array{0:SqlAstTokenNode|null, 1:SqlAstExpression}> */
    private parts: Array<Array<SqlAstNode>>;

    /** @param array<array{0:SqlAstTokenNode|null, 1:SqlAstExpression}> parts */
    constructor(
        parent: SqlAstNode,
        parts: Array<Array<SqlAstNode>>
    ) {
        this.parent = parent;
        this.parts = [];

        assert(parts.length >= 2);

        for (var index in parts) {
            var operator: SqlAstTokenNode|null = parts[index][0];
            var expression: SqlAstExpression = parts[index][1];

            if (index > 0) {
                assert(operator instanceof SqlAstTokenNode);

            } else {
                assert(operator == null);
            }

            this.parts[] = [operator, expression];
        }
    }

    public static function mutateAstNode(
        node: SqlAstNode,
        offset: number,
        parent: SqlAstMutableNode
    ): void {
        var originalOffset: number = offset;

        /** @var array<array{0:SqlAstTokenNode, 1:SqlAstExpression}> parts */
        var parts: Array<Array<SqlAstNode>> = [[null, node]];

        while (node instanceof SqlAstExpression) {
            node = null;

            var nextNode: SqlAstNode = parent[offset + 1];

            if (nextNode instanceof SqlAstTokenNode) {
                var isConjunction: boolean = max(
                    nextNode.is(SqlToken.AND()),
                    nextNode.is(SqlToken.OR())
                );

                if (isConjunction) {
                    var otherNode: SqlAstNode = parent[offset + 2];

                    if (otherNode instanceof SqlAstExpression) {
                        parts[] = [nextNode, otherNode];
                        offset += 2;
                        node = otherNode;
                    }
                }
            }
        }

        if (count(parts) > 1) {
            parent.replace(originalOffset, offset - originalOffset + 1, new SqlAstConjunction(parent, parts));
        }
    }

    public function children(): Array<SqlAstNode>
    {
        var children: Array<SqlAstNode|null> = [];

        for (var index in this.parts) {
            children[] = this.parts[index][0];
            children[] = this.parts[index][1];
        }

        return children.filter(node => node != null);
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
        return this.children()[0].line();
    }

    public function column(): number
    {
        return this.children()[0].column();
    }

    public function toSql(): string
    {
        var sql: string = "";

        for (var index in this.parts) {
            var operator: SqlAstTokenNode|null = this.parts[index][0];
            var expression: SqlAstExpression = this.parts[index][1];

            if (typeof operator == 'object') {
                sql += ' ' + operator.toSql();
            }

            sql += ' ' + expression.toSql();
        }

        return sql.trim();
    }
}
