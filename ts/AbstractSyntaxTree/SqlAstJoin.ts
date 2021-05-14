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
import { SqlAstTokenNode } from './SqlAstTokenNode'
import { SqlAstExpression } from './SqlAstExpression'
import { SqlToken } from './SqlToken'

export class SqlAstJoin implements SqlAstNode
{
    private parent: SqlAstNode;
    private joinToken: SqlAstTokenNode;
    private tableName: SqlAstTokenNode;
    private joinType: SqlAstTokenNode|null;
    private alias: SqlAstTokenNode|null;
    private onOrUsing: SqlAstTokenNode|null;
    private condition: SqlAstExpression|null;

    public function __construct(
        parent: SqlAstNode,
        joinToken: SqlAstTokenNode,
        tableName: SqlAstTokenNode,
        joinType: SqlAstTokenNode|null,
        alias: SqlAstTokenNode|null,
        onOrUsing: SqlAstTokenNode|null,
        condition: SqlAstExpression|null
    ) {
        this.parent = parent;
        this.joinToken = joinToken;
        this.joinType = joinType;
        this.tableName = tableName;
        this.alias = alias;
        this.onOrUsing = onOrUsing;
        this.condition = condition;
    }

    public static function mutateAstNode(
        node: SqlAstNode,
        number offset,
        parent: SqlAstMutableNode
    ): void {
        if (node instanceof SqlAstTokenNode && node.is(SqlToken.JOIN())) {
            var joinType: SqlAstNode|null = parent[offset - 1];
            var tableName: SqlAstTokenNode = parent[offset + 1];
            var alias: SqlAstNode|null = parent[offset + 2];

            if (!(tableName instanceof SqlAstTokenNode && tableName.is(SqlToken.SYMBOL()))) {
                tableName = null;
            }

            if (!(alias instanceof SqlAstTokenNode && alias.is(SqlToken.SYMBOL()))) {
                alias = null;
            }

            var beginOffset: number = is_object(joinType) ? offset - 1 : offset;
            var endOffset: number = is_object(alias) ? offset + 2 : offset + 1;
            var onOrUsing: SqlAstNode|null = parent[endOffset + 1];
            var condition: SqlAstExpression|null = null;

            if (onOrUsing instanceof SqlAstTokenNode) {
                if (onOrUsing.is(SqlToken.ON()) || onOrUsing.is(SqlToken.USING())) {
                    condition = parent[endOffset + 2];
                    endOffset += 2;

                    assert(condition instanceof SqlAstExpression);
                }
            }

            parent.replace(beginOffset, 1 + endOffset - beginOffset, new SqlAstJoin(
                parent,
                node,
                tableName,
                joinType,
                alias,
                onOrUsing,
                condition
            ));
        }
    }

    public function children(): array
    {
        return [
            this.joinType,
            this.tableName,
            this.alias,
        ].filer(node => node != null);
    }

    public function hash(): string
    {
        return Md5.hashStr(this.children().map(node => node.hash()).join('.'));
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
        return this.joinToken.line();
    }

    public function column(): number
    {
        return this.joinToken.column();
    }

    public function toSql(): string
    {
        var sql: string = this.joinType.toSql() + " JOIN " + this.tableName.toSql();

        if (typeof this.alias == 'object') {
            sql += " " + this.alias.toSql();
        }

        if (typeof this.condition == 'object') {
            sql += " " + this.onOrUsing.toSql() + " " + this.condition.toSql();
        }

        return sql;
    }

}
