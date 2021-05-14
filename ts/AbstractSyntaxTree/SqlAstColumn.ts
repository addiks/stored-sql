/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\StoredSQL\AbstractSyntaxTree;

use Addiks\StoredSQL\Lexing\SqlToken;
use Webmozart\Assert\Assert;

import { SqlAstExpression } from './SqlAstExpression'
import { SqlAstNode } from './SqlAstNode'
import { SqlAstTokenNode } from './SqlAstTokenNode'
import { SqlAstMutableNode } from './SqlAstMutableNode'
import { SqlToken } from '../Lexing/SqlToken'
import { Md5 } from 'ts-md5/dist/md5'

class SqlAstColumn implements SqlAstExpression
{
    private parent: SqlAstNode;
    private column: SqlAstTokenNode;
    private table: SqlAstTokenNode|null;
    private database: SqlAstTokenNode|null;

    constructor(
        parent: SqlAstNode,
        column: SqlAstTokenNode,
        table: SqlAstTokenNode|null,
        database: SqlAstTokenNode|null
    ) {
        this.parent = parent;
        this.column = column;
        this.table = table;
        this.database = database;
    }

    public static mutateAstNode(
        node: SqlAstNode,
        offset: int,
        parent: SqlAstMutableNode
    ): void {
        if (node instanceof SqlAstTokenNode && node.token().is(SqlToken.SYMBOL())) {

            var length: int = 1;
            var column: SqlAstTokenNode = node;
            var table: SqlAstTokenNode|null = null;
            var database: SqlAstTokenNode|null = null;
            var dot: SqlAstNode = parent[offset + 1];

            if (dot instanceof SqlAstTokenNode && dot.is(SqlToken.DOT())) {
                node = parent[offset + 2];
                assert(typeof node == SqlAstTokenNode);

                if (node.is(SqlToken.SYMBOL())) {
                    length += 2;

                    table = column;
                    column = node;

                    dot = parent[offset + 3];

                    if (dot instanceof SqlAstTokenNode && dot.is(SqlToken.DOT())) {
                        node = parent[offset + 4];
                        assert(node instanceof SqlAstTokenNode);

                        if (node.is(SqlToken.SYMBOL())) {
                            length += 2;

                            database = table;
                            table = column;
                            column = node;
                        }
                    }
                }

                parent.replace(offset, length, new SqlAstColumn(
                    parent,
                    column,
                    table,
                    database
                ));
            }
        }
    }

    public children(): array
    {
        return [
            this.database,
            this.table,
            this.column,
        ].filter(node => node != null);
    }

    public hash(): string
    {
        return Md5.hashStr(this.children().map(node => node.hash()).join('.'));
    }

    public parent(): SqlAstNode|null
    {
        return this.parent;
    }

    public root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public line(): number
    {
        return this.column.line();
    }

    public column(): number
    {
        return this.column.column();
    }

    public toSql(): string
    {
        this.children().map(node => node.toSql()).join('.');
    }
}
