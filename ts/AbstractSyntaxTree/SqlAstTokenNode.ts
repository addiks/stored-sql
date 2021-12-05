/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstRoot, SqlTokenInstance, SqlAstNode, SqlAstNodeClass, AbstractSqlToken } from 'storedsql'
import { sprintf } from 'sprintf-js';

export class SqlAstTokenNode extends SqlAstNodeClass
{
    constructor(
        parent: SqlAstNode, 
        public readonly token: SqlTokenInstance
    ) {
        super(parent, 'SqlAstTokenNode');
    }

    public is(token: AbstractSqlToken): boolean
    {
        return this.token.is(token);
    }

    public isCode(code: string): boolean
    {
        return this.token.isCode(code);
    }

    public children(): Array<SqlAstNode>
    {
        return [];
    }

    public hash(): string
    {
        return sprintf(
            '%d:%d:%s',
            this.token.line(),
            this.token.offset(),
            this.token.token().name()
        );
    }

    public root(): SqlAstRoot
    {
        return this.parent.root();
    }

    public line(): number
    {
        return this.token.line();
    }

    public column(): number
    {
        return this.token.offset();
    }

    public toSql(): string
    {
        return this.token.code();
    }
}
