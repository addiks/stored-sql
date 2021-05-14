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
import { SqlToken } from './SqlToken'

export class SqlAstParenthesis implements SqlAstExpression
{
    private parent: SqlAstNode;
    private bracketOpening: SqlAstTokenNode;
    private expression: SqlAstExpression;

    public function __construct(
        parent: SqlAstNode,
        bracketOpening: SqlAstTokenNode,
        expression: SqlAstExpression
    ) {
        this.parent = parent;
        this.bracketOpening = bracketOpening;
        this.expression = expression;
    }

    public static function mutateAstNode(
        SqlAstNode node,
        int offset,
        SqlAstMutableNode parent
    ): void {
        if (node instanceof SqlAstTokenNode && node.is(SqlToken.BRACKET_OPENING())) {
            var expression: SqlAstExpression = parent[offset + 1];

            # TODO: also allow SELECT in here, for sub-selects

            var close: SqlAstTokenNode = parent[offset + 2];

            assert(close.token().token() == SqlToken.BRACKET_CLOSING())

            parent.replace(offset, 3, new SqlAstParenthesis(parent, node, expression));
        }
    }

    public function children(): Array<SqlAstNode>
    {
        return [this.expression];
    }

    public function hash(): string
    {
        return md5(this.expression.hash());
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
        return this.bracketOpening.line();
    }

    public function column(): number
    {
        return this.bracketOpening.column();
    }

    public function toSql(): string
    {
        return '(' + this.expression.toSql() + ')';
    }
}
