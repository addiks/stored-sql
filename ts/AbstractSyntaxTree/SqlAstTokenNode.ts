/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { AbstractSqlToken } from './AbstractSqlToken'
import { SqlAstNode } from './SqlAstNode'
import { SqlTokenInstance } from './SqlTokenInstance'
import { SqlAstRoot } from './SqlAstRoot'

export class SqlAstTokenNode implements SqlAstNode
{
    private parent: SqlAstNode;
    private token: SqlTokenInstance;

    public function __construct(parent: SqlAstNode, token: SqlTokenInstance)
    {
        this.parent = parent;
        this.token = token;
    }

    public function token(): SqlTokenInstance
    {
        return this.token;
    }

    public function is(token: AbstractSqlToken): boolean
    {
        return this.token.is(token);
    }

    public function isCode(string code): boolean
    {
        return this.token.isCode(code);
    }

    public function children(): Array<SqlAstNode>
    {
        return [];
    }

    public function hash(): string
    {
        return sprintf(
            '%d:%d:%s',
            this.token.line(),
            this.token.offset(),
            this.token.token().name()
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
        return this.token.line();
    }

    public function column(): number
    {
        return this.token.offset();
    }

    public function toSql(): string
    {
        return this.token.code();
    }
}
