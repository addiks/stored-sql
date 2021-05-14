/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlTokens } from '../Lexing/SqlTokens'
import { SqlAstBranch } from './SqlAstBranch'
import { SqlAstRoot } from './SqlAstRoot'
import { SqlAstNode } from './SqlAstNode'
import { SqlAstTokenNode } from './SqlAstTokenNode'

export class SqlAstRootClass extends SqlAstBranch implements SqlAstRoot
{
    private tokens: SqlTokens;
    private lexingFinished: boolean = false;

    constructor(children: Array<SqlAstNode>, tokens: SqlTokens)
    {
        super(children);

        this.tokens = tokens;
    }

    public function addToken(SqlAstTokenNode token): void
    {
        assert(!this.lexingFinished);

        parent::replace(count(this.children()), 1, token);
    }

    public function markLexingFinished(): void
    {
        this.lexingFinished = true;
    }

    public function replace(
        offset: number,
        length: number,
        newNode: SqlAstNode
    ): void {
        assert(this.lexingFinished);

        parent::replace(offset, length, newNode);
    }

    public function tokens(): SqlTokens
    {
        return this.tokens;
    }

    public function parent(): SqlAstNode|null
    {
        return null;
    }

    public function root(): SqlAstRoot
    {
        return this;
    }

    public function line(): number
    {
        return 1;
    }

    public function column(): number
    {
        return 0;
    }

    public function toSql(): string
    {
        var sql: string = "";

        for (var node of this.children()) {
            sql += node.toSql();
        }

        return sql;
    }
}
