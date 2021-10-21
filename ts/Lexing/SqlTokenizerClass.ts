/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { UnlexableSqlException } from '../Exception/UnlexableSqlException'
import { SqlTokenizer } from './SqlTokenizer'

export class SqlTokenizerClass implements SqlTokenizer
{
    private keywords: Map<string, AbstractSqlToken>;

    constructor(keywords: Map<string, AbstractSqlToken>)
    {
        this.keywords = keywords;
    }

    public static defaultTokenizer(): SqlTokenizerClass
    {
        return new SqlTokenizerClass(SqlTokenizerClass.defaultKeywords());
    }

    public tokenize(sql: string): SqlTokens
    {
        var tokens: Array<SqlTokenInstance> = [];
        var originalSql: string = sql;
        var line: number = 0;
        var offset: number = 0;

        while (sql.length > 0) {
            var tokenSql: string = '';
            var token: AbstractSqlToken|null = this.readToken(sql, tokenSql);

            if (token == null) {
                throw new UnlexableSqlException(originalSql, line, offset);
            }

            tokens[] = new SqlTokenInstanceClass(tokenSql, token, line, offset);

            sql = sql.substr(tokenSql.length);

            var newLines: number = tokenSql.split("\n").length - 1;

            line += newLines;

            if (newLines > 0) {
                offset = tokenSql.split("").reverse().join("").indexOf("\n") + 1;

            } else {
                offset += tokenSql.length;
            }
        }

        return new SqlTokensClass(tokens, originalSql);
    }

    public static defaultKeywords(): Map<string, AbstractSqlToken>
    {
        return [
            '(' => SqlToken.BRACKET_OPENING(),
            ')' => SqlToken.BRACKET_CLOSING(),
            '.' => SqlToken.DOT(),
            ',' => SqlToken.COMMA(),
            ';' => SqlToken.SEMICOLON(),

            '<=' => SqlToken.OPERATOR(),
            '>=' => SqlToken.OPERATOR(),
            '!=' => SqlToken.OPERATOR(),
            '=' => SqlToken.OPERATOR(),
            '<' => SqlToken.OPERATOR(),
            '>' => SqlToken.OPERATOR(),

            'SELECT' => SqlToken.SELECT(),
            'FROM' => SqlToken.FROM(),
            'LEFT' => SqlToken.LEFT(),
            'INNER' => SqlToken.INNER(),
            'JOIN' => SqlToken.JOIN(),
            'ON' => SqlToken.ON(),
            'IN' => SqlToken.IN(),
            'IS' => SqlToken.IS(),
            'AND' => SqlToken.AND(),
            'ORDER' => SqlToken.ORDER(),
            'BY' => SqlToken.BY(),
            'ASC' => SqlToken.ASC(),
            'DESC' => SqlToken.DESC(),
            'OR' => SqlToken.OR(),
            'LIKE' => SqlToken.LIKE(),
            'USING' => SqlToken.USING(),
            'WHERE' => SqlToken.WHERE(),
            'HAVING' => SqlToken.HAVING(),
            'LIMIT' => SqlToken.LIMIT(),
            'DISTINCT' => SqlToken.DISTINCT(),
            'FUNCTION_NAME' => SqlToken.FUNCTION_NAME(),
            'UPDATE' => SqlToken.UPDATE(),
            'DELETE' => SqlToken.DELETE(),
            'ALTER' => SqlToken.ALTER(),
            'TABLE' => SqlToken.TABLE(),
            'SCHEMA' => SqlToken.SCHEMA(),
            'COLUMN' => SqlToken.COLUMN(),
            'INDEX' => SqlToken.INDEX(),
            'KEY' => SqlToken.KEY(),
            'CREATE' => SqlToken.CREATE(),
            'DATA_TYPE' => SqlToken.DATA_TYPE(),
            'NOT' => SqlToken.NOT(),
            'NULL' => SqlToken.T_NULL(),
            'PRIMARY_KEY' => SqlToken.PRIMARY_KEY(),
            'SET' => SqlToken.SET(),
            'SHOW' => SqlToken.SHOW(),

        ];
    }

    private readToken(sql: string, &readSql: string = ''): ?AbstractSqlToken
    {
        if (match = /^(\s+)/gis.exec(sql)) {
            readSql = match[1];

            return SqlToken.SPACE();
        }

        if (sql[0] === '#' || (sql[0] === '-' && sql[1] === '-')) {
            var newLinePosition: number|boolean = sql.indexOf("\n");
            var commentEndPosition: number = PHP_INT_MAX;

            if (sql[0] === '-') {
                commentEndPosition = sql.indexOf('--', 2);

                if (commentEndPosition < 0) {
                    if (typeof newLinePosition == "number") {
                        commentEndPosition = newLinePosition;

                    } else {
                        commentEndPosition = PHP_INT_MAX;
                    }
                }
            }

            if (newLinePosition > commentEndPosition) {
                readSql = sql.substr(0, commentEndPosition + 2);

            } else if (typeof newLinePosition == "number") {
                readSql = sql.substr(0, newLinePosition + 1);

            } else {
                readSql = sql;
            }

            return SqlToken.COMMENT();
        }

        if (sql[0] === '"' || sql[0] === "'") {
            var endPosition: number = sql.indexOf(sql[0], 1);

            readSql = sql.substr(0, endPosition + 1);

            return SqlToken.LITERAL();
        }

        for (var keyword: string in this.keywords) {
            var token: AbstractSqlToken = this.keywords[keyword];
            if (keyword.toUpperCase() == sql.substr(0, keyword.length).toUpperCase()) {
                readSql = sql.substr(0, keyword.length);

                return token;
            }
        }

        if (match = /^[0-9]+(\.[0-9]+)?/gis.exec(sql)) {
            readSql = match[0];

            return SqlToken.NUMERIC();
        }

        if (sql[0] === '`') {
            var endPosition: number = sql.indexOf('`', 1);

            readSql = sql.substr(0, endPosition + 1);

            return SqlToken.SYMBOL();
        }

        if (/^[a-zA-Z_][a-zA-Z0-9_]*/gis.exec(sql)) {
            readSql = match[0];

            return SqlToken.SYMBOL();
        }

        return null;
    }
}
