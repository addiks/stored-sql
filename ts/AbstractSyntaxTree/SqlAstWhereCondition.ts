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
    SqlToken, SqlAstMergable, SqlAstConjunction, SqlAstTokenNode, SqlTokenInstance, SqlTokenInstanceClass, 
    SqlAstMutableNode, SqlAstExpression
} from 'storedsql';

export function mutateAstNode(
    node: SqlAstNode,
    offset: number,
    parent: SqlAstMutableNode
): void {
    if (node instanceof SqlAstTokenNode && node.is(SqlToken.WHERE())) {
        var expression: SqlAstExpression = parent.get(offset + 1);

        parent.replace(offset, 2, new SqlAstWhere(parent, node, expression));
    }
}

export class SqlAstWhere extends SqlAstMergableClass
{
    private parent: SqlAstNode;

    constructor(
        parent: SqlAstNode,
        private readonly whereToken: SqlAstTokenNode,
        public readonly expression: SqlAstExpression,
        nodeType: string = 'SqlAstWhere'
    ) {
        super(parent, nodeType);
    }

    public children(): Array<SqlAstNode>
    {
        return [this.expression];
    }

    public hash(): string
    {
        return this.expression.hash();
    }

    public root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public line(): number
    {
        return this.whereToken.line();
    }

    public column(): number
    {
        return this.whereToken.column();
    }

    public toSql(): string
    {
        return 'WHERE ' + this.expression.toSql();
    }

    public merge(SqlAstMergable toMerge): SqlAstMergable
    {
        assert(toMerge instanceof SqlAstWhere);

        operator = new SqlAstTokenNode(this.parent, new SqlTokenInstanceClass(
            "AND",
            SqlToken.AND(),
            this.line,
            this.column
        ));

        var mergedExpression = new SqlAstConjunction(this.parent, [
            [null, this.expression],
            [operator, toMerge.expression]
        ]);

        var newWhere = new SqlAstWhere(this.parent, this.whereToken, mergedExpression);

        this.parent.replaceNode(this, newWhere);

        return newWhere;
    }
}
