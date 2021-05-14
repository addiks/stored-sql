/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlToken } from './SqlToken'
import { SqlTokenInstance } from './SqlTokenInstance'
import { SqlAstNode } from './SqlAstNode'
import { SqlAstExpression } from './SqlAstExpression'
import { SqlAstTokenNode } from './SqlAstTokenNode'
import { SqlAstMutableNode } from './SqlAstMutableNode'

export class SqlAstLiteral implements SqlAstExpression
{
    private parent: SqlAstNode;
    private literal: SqlAstTokenNode;

    public function __construct(
        parent: SqlAstNode,
        literal: SqlAstTokenNode
    ) {
        this.parent = parent;
        this.literal = literal;
    }

    public static function mutateAstNode(
        node: SqlAstNode,
        offset: number,
        parent: SqlAstMutableNode
    ): void {
        if (node instanceof SqlAstTokenNode) {
            var token: SqlTokenInstance = node.token();
            var isSomeLiteralNode: bool = max(
                token.is(SqlToken.LITERAL()),
                token.is(SqlToken.NUMERIC()),
                token.is(SqlToken.T_NULL()),
            );

            if (isSomeLiteralNode) {
                parent.replace(offset, 1, new SqlAstLiteral(parent, node));
            }
        }
    }

    public function children(): Array<SqlAstNode>
    {
        return [this.literal];
    }

    public function hash(): string
    {
        return this.literal.hash();
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
        return this.literal.line();
    }

    public function column(): number
    {
        return this.literal.column();
    }

    public function toSql(): string
    {
        return this.literal.toSql();
    }
}
