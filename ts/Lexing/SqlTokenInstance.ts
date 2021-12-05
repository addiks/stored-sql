/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { AbstractSqlToken } from 'storedsql';

export interface SqlTokenInstance
{
    code(): string;
    token(): AbstractSqlToken;
    is(token: AbstractSqlToken): boolean;
    isCode(code: string): boolean;
    line(): number;
    offset(): number;
}

export class SqlTokenInstanceClass implements SqlTokenInstance
{
    constructor(
        private _code: string, 
        private _token: AbstractSqlToken, 
        private _line: number, 
        private _offset: number
    ) {
    }

    public code(): string
    {
        return this._code;
    }

    public token(): AbstractSqlToken
    {
        return this._token;
    }

    public is(token: AbstractSqlToken): boolean
    {
        return this._token == token;
    }

    public isCode(code: string): boolean
    {
        return this._code == code;
    }

    public line(): number
    {
        return this._line;
    }

    public offset(): number
    {
        return this._offset;
    }
}
