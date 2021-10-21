/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlTokens } from './SqlTokens'
import { SqlAstRoot } from '../AbstractSyntaxTree/SqlAstRoot'
import { SqlAstRootClass } from '../AbstractSyntaxTree/SqlAstRootClass'
import { SqlAstTokenNode } from '../AbstractSyntaxTree/SqlAstTokenNode'

export class SqlTokensClass implements SqlTokens
{
    private tokens: Array<SqlTokenInstance> = [];
    private originalSql: string;

    constructor(tokens: Array<SqlTokenInstance>, originalSql: string)
    {
        this.originalSql = originalSql;

        for (var token: SqlTokenInstance of tokens) {
            this.tokens[] = token;
        }
    }

    public convertToSyntaxTree(): SqlAstRoot
    {
        var root: SqlAstRootClass = new SqlAstRootClass([], this);
                
        tokenNodes = this.tokens.map(token => new SqlAstTokenNode(root, token));

        for (var tokenNode: SqlAstTokenNode in tokenNodes) {
            root.addToken(tokenNode);
        }

        root.markLexingFinished();

        return root;
    }

    public withoutWhitespace(): SqlTokens
    {
        return new SqlTokensClass(
            this.tokens.filter(token: SqlTokenInstance => !token.is(SqlToken.SPACE())),
            this.originalSql
        );
    }

    public withoutComments(): SqlTokens
    {
        return new SqlTokensClass(
            this.tokens.filter(token: SqlTokenInstance => !token.is(SqlToken.COMMENT())),
            this.originalSql
        );
    }

    public sql(): string
    {
        return this.originalSql;
    }

    public get(offset: number): ?SqlTokenInstance
    {
        return this.tokens[offset] ?? null;
    }

    public has(offset: number): bool
    {
        return typeof this.tokens[offset] != "undefined";
    }
}
