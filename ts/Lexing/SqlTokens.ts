/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlToken, SqlTokenInstance } from 'storedsql'

export interface SqlTokens
{
    readonly tokens: Array<SqlTokenInstance>;
    withoutWhitespace(): SqlTokens;
    withoutComments(): SqlTokens;
    sql(): string;
}

export class SqlTokensClass implements SqlTokens
{
    constructor(
        public readonly tokens: Array<SqlTokenInstance>, 
        private originalSql: string
    ) {
    }

    public withoutWhitespace(): SqlTokens
    {
        return new SqlTokensClass(
            this.tokens.filter(token => !token.is(SqlToken.SPACE)),
            this.originalSql
        );
    }

    public withoutComments(): SqlTokens
    {
        return new SqlTokensClass(
            this.tokens.filter(token => !token.is(SqlToken.COMMENT)),
            this.originalSql
        );
    }

    public sql(): string
    {
        return this.originalSql;
    }

    public get(offset: number): SqlTokenInstance
    {
        return this.tokens[offset] ?? null;
    }

    public has(offset: number): boolean
    {
        return typeof this.tokens[offset] != "undefined";
    }
}
