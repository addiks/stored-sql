
import { 
    UnlexableSqlException, SqlTokens, SqlTokensClass, AbstractSqlToken, SqlTokenInstance, SqlTokenInstanceClass, 
    SqlToken 
} from 'storedsql'

export interface SqlTokenizer
{
    tokenize(sql: string): SqlTokens;
}

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
        const tokens: Array<SqlTokenInstance> = [];
        const originalSql: string = sql;
        let line = 0;
        let offset = 0;
        let token: AbstractSqlToken|null;
        let tokenSql = '';

        while (sql.length > 0) {
            [token, tokenSql] = this.readToken(sql);

            if (token == null) {
                throw new UnlexableSqlException(originalSql, line, offset);
            }

            tokens.push(new SqlTokenInstanceClass(tokenSql, token, line, offset));

            sql = sql.substr(tokenSql.length);

            const newLines: number = tokenSql.split("\n").length - 1;

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
        return new Map([
            ["(", SqlToken.BRACKET_OPENING],
            [")", SqlToken.BRACKET_CLOSING],
            [".", SqlToken.DOT],
            [",", SqlToken.COMMA],
            [";", SqlToken.SEMICOLON],

            ["<=", SqlToken.OPERATOR],
            [">=", SqlToken.OPERATOR],
            ["!=", SqlToken.OPERATOR],
            ["=", SqlToken.OPERATOR],
            ["<", SqlToken.OPERATOR],
            [">", SqlToken.OPERATOR],

            ["SELECT", SqlToken.SELECT],
            ["FROM", SqlToken.FROM],
            ["LEFT", SqlToken.LEFT],
            ["INNER", SqlToken.INNER],
            ["JOIN", SqlToken.JOIN],
            ["ON", SqlToken.ON],
            ["IN", SqlToken.IN],
            ["IS", SqlToken.IS],
            ["AND", SqlToken.AND],
            ["ORDER", SqlToken.ORDER],
            ["BY", SqlToken.BY],
            ["ASC", SqlToken.ASC],
            ["DESC", SqlToken.DESC],
            ["OR", SqlToken.OR],
            ["LIKE", SqlToken.LIKE],
            ["USING", SqlToken.USING],
            ["WHERE", SqlToken.WHERE],
            ["HAVING", SqlToken.HAVING],
            ["LIMIT", SqlToken.LIMIT],
            ["DISTINCT", SqlToken.DISTINCT],
            ["FUNCTION_NAME", SqlToken.FUNCTION_NAME],
            ["UPDATE", SqlToken.UPDATE],
            ["DELETE", SqlToken.DELETE],
            ["ALTER", SqlToken.ALTER],
            ["TABLE", SqlToken.TABLE],
            ["SCHEMA", SqlToken.SCHEMA],
            ["COLUMN", SqlToken.COLUMN],
            ["INDEX", SqlToken.INDEX],
            ["KEY", SqlToken.KEY],
            ["CREATE", SqlToken.CREATE],
            ["DATA_TYPE", SqlToken.DATA_TYPE],
            ["NOT", SqlToken.NOT],
            ["NULL", SqlToken.T_NULL],
            ["PRIMARY_KEY", SqlToken.PRIMARY_KEY],
            ["SET", SqlToken.SET],
            ["SHOW", SqlToken.SHOW],
        ]);
    }

    private readToken(sql: string): [AbstractSqlToken, string]
    {
        let match = /^(\s+)/gis.exec(sql);
        
        if (match) {
            return [SqlToken.SPACE, match[1]];
        }
        
        if (sql[0] === '#' || (sql[0] === '-' && sql[1] === '-')) {
            const newLinePosition: number|boolean = sql.indexOf("\n");
            let commentEndPosition: number = sql.length - 1;

            if (sql[0] === '-') {
                commentEndPosition = sql.indexOf('--', 2);

                if (commentEndPosition < 0) {
                    if (typeof newLinePosition == "number") {
                        commentEndPosition = newLinePosition;

                    } else {
                        commentEndPosition = sql.length - 1;
                    }
                }
            }
            
            let readSql = '';

            if (newLinePosition > commentEndPosition) {
                readSql = sql.substr(0, commentEndPosition + 2);

            } else if (typeof newLinePosition == "number") {
                readSql = sql.substr(0, newLinePosition + 1);

            } else {
                readSql = sql;
            }

            return [SqlToken.COMMENT, readSql];
        }
        
        if (sql[0] === '"' || sql[0] === "'") {
            return [SqlToken.LITERAL, sql.substr(0, sql.indexOf(sql[0], 1) + 1)];
        }

        for (const keyword in this.keywords) {
            const token: AbstractSqlToken = this.keywords[keyword];
            if (keyword.toUpperCase() == sql.substr(0, keyword.length).toUpperCase()) {
                return [token, sql.substr(0, keyword.length)];
            }
        }
        
        match = /^[0-9]+(\.[0-9]+)?/gis.exec(sql);

        if (match) {
            return [SqlToken.NUMERIC, match[0]];
        }

        if (sql[0] === '`') {
            return [SqlToken.SYMBOL, sql.substr(0, sql.indexOf('`', 1) + 1)];
        }

        if (/^[a-zA-Z_][a-zA-Z0-9_]*/gis.exec(sql)) {
            return [SqlToken.SYMBOL, match[0]];
        }

        return [null, ""];
    }
}
