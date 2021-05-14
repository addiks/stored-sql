/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstNode } from './SqlAstNode'
import { SqlToken } from './SqlToken'
import { SqlAstTokenNode } from './SqlAstTokenNode'
import { SqlAstMutableNode } from './SqlAstMutableNode'

export class SqlAstFrom implements SqlAstNode
{
    private parent: SqlAstNode;
    private fromToken: SqlAstTokenNode;
    private tableName: SqlAstTokenNode;
    private alias: SqlAstTokenNode|null;

    public function __construct(
        parent: SqlAstNode,
        fromToken: SqlAstTokenNode,
        tableName: SqlAstTokenNode,
        alias: SqlAstTokenNode|null
    ) {
        this.parent = parent;
        this.fromToken = fromToken;
        this.tableName = tableName;
        this.alias = alias;
    }

    public static function mutateAstNode(
        node: SqlAstNode,
        offset: number,
        parent: SqlAstMutableNode
    ): void {
        if (node instanceof SqlAstTokenNode && node.is(SqlToken.FROM())) {
            var tableName: SqlAstTokenNode = parent[offset + 1];

            assert(tableName.is(SqlToken.SYMBOL()));

            var alias: SqlAstNode|null = parent[offset + 2];

            if (!(alias instanceof SqlAstTokenNode) || !alias.is(SqlToken.SYMBOL())) {
                alias = null;
            }

            parent.replace(offset, is_object(alias) ? 3 : 2, new SqlAstFrom(parent, node, tableName, alias));
        }
    }

    public function children(): Array<SqlAstNode>
    {
        return [
            this.tableName,
            this.alias,
        ].filter(node => node != null);
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
        return this.fromToken.line();
    }

    public function column(): number
    {
        return this.fromToken.column();
    }

    public function toSql(): string
    {
        return "FROM " + this.tableName.toSql() + (is_object(this.alias) ?(' ' + this.alias.toSql()) :'');
    }
}
