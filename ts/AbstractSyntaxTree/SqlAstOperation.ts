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
import { SqlAstMutableNode } from './SqlAstMutableNode'

export class SqlAstOperation implements SqlAstExpression
{
    private parent: SqlAstNode;
    private leftSide: SqlAstExpression;
    private operator: SqlAstTokenNode;
    private rightSide: SqlAstExpression;

    public function __construct(
        parent: SqlAstNode,
        leftSide: SqlAstExpression,
        operator: SqlAstTokenNode,
        rightSide: SqlAstExpression
    ) {
        this.parent = parent;
        this.leftSide = leftSide;
        this.operator = operator;
        this.rightSide = rightSide;
    }

    public static function mutateAstNode(
        node: SqlAstNode,
        offset: number,
        parent: SqlAstMutableNode
    ): void {
        if (node instanceof SqlAstExpression) {
            var leftSide: SqlAstExpression = node;
            var operator: SqlAstNode = parent[offset + 1];
            var rightSide: SqlAstExpression = parent[offset + 2];

            if (operator instanceof SqlAstTokenNode && rightSide instanceof SqlAstExpression) {
                var isOperator: boolean = max(
                    operator.is(SqlToken.OPERATOR()),
                    operator.is(SqlToken.LIKE()),
                    operator.is(SqlToken.IS()),
                );

                if (isOperator) {
                    parent.replace(offset, 3, new SqlAstOperation(
                        parent,
                        leftSide,
                        operator,
                        rightSide
                    ));
                }
            }
        }
    }

    public function children(): Array<SqlAstNode>
    {
        return [
            this.leftSide,
            this.operator,
            this.rightSide,
        ];
    }

    public function hash(): string
    {
        return sprintf(
            '(%s/%s/%s)',
            this.leftSide.hash(),
            this.operator.hash(),
            this.rightSide.hash()
        );
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
        return this.operator.line();
    }

    public function column(): number
    {
        return this.operator.column();
    }

    public function toSql(): string
    {
        return this.leftSide.toSql() + ' ' + this.operator.toSql() + ' ' + this.rightSide.toSql();
    }
}
