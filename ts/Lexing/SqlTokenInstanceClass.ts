/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlTokenInstance } from './SqlTokenInstance'
import { AbstractSqlToken } from './AbstractSqlToken'

export class SqlTokenInstanceClass implements SqlTokenInstance
{
    private code: string;
    private token: AbstractSqlToken;
    private line: number;
    private offset: number;

    construtor(
        code: string, 
        token: AbstraqlToken, 
        line: number, 
        offset: number
    ) {
        this.code = code;
        this.token = token;
        this.line = line;
        this.offset = offset;
    }

    public code(): string
    {
        return this.code;
    }

    public token(): AbstractSqlToken
    {
        return this.token;
    }

    public is(token: AbstractSqlToken): boolean
    {
        return this.token == token;
    }

    public isCode(code: string): boolean
    {
        return this.code == code;
    }

    public line(): number
    {
        return this.line;
    }

    public offset(): number
    {
        return this.offset;
    }
}
