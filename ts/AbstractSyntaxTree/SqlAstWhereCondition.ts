/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlToken } from '../Lexing/SqlToken'
import { SqlAstMergable } from './SqlAstMergable'
import { SqlAstConjunction } from './SqlAstConjunction'
import { SqlAstTokenNode } from './SqlAstTokenNode'
import { SqlTokenInstance } from './SqlTokenInstance'
import { SqlTokenInstanceClass } from './SqlTokenInstanceClass'
import { SqlAstMutableNode } from './SqlAstMutableNode'

export class SqlAstWhere implements SqlAstMergable
{
    private parent: SqlAstNode;
    private whereToken: SqlAstTokenNode;
    private expression: SqlAstExpression;

    public function __construct(
        parent: SqlAstNode,
        whereToken: SqlAstTokenNode,
        expression: SqlAstExpression
    ) {
        this.parent = parent;
        this.whereToken = whereToken;
        this.expression = expression;
    }

    public static function mutateAstNode(
        node: SqlAstNode,
        offset: number,
        parent: SqlAstMutableNode
    ): void {
        if (node instanceof SqlAstTokenNode && node.is(SqlToken.WHERE())) {
            var expression: SqlAstExpression = parent[offset + 1];

            parent.replace(offset, 2, new SqlAstWhereCondition(parent, node, expression));
        }
    }

    public function children(): Array<SqlAstNode>
    {
        return [this.expression];
    }

    public function hash(): string
    {
        return this.expression.hash();
    }

    public function expression(): SqlAstExpression
    {
        return this.expression;
    }

    public function parent(): ?SqlAstNode
    {
        return this.parent;
    }

    public function root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public function line(): number
    {
        return this.whereToken.line();
    }

    public function column(): number
    {
        return this.whereToken.column();
    }

    public function toSql(): string
    {
        return 'WHERE ' + this.expression.toSql();
    }

    public function merge(SqlAstMergable toMerge): SqlAstMergable
    {
        assert(toMerge instanceof SqlAstWhereCondition);

        operator = new SqlAstTokenNode(this.parent, new SqlTokenInstanceClass(
            "AND",
            SqlToken.AND(),
            this.line,
            this.column
        ));

        mergedExpression = new SqlAstConjunction(this.parent, [
            [null, this.expression],
            [operator, toMerge.expression()]
        ]);

        newWhere = new SqlAstWhereCondition(this.parent, this.whereToken, mergedExpression);

        this.parent.replaceNode(this, newWhere);

        return newWhere;
    }
}
