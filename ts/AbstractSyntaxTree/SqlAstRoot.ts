/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlTokens, SqlAstMutableNode, SqlAstBranch, SqlAstRoot, SqlAstNode, SqlAstTokenNode, assert } from 'storedsql';

// If you are looking for the SqlAstRoot interface,
// that one is defined in 'SqlAstNode.ts' to prevent a circular reference.

export function convertTokensToSyntaxTree(tokens: SqlTokens): SqlAstRoot
{
    var root: SqlAstRootClass = new SqlAstRootClass([], tokens);

    var tokenNodes: Array<SqlAstTokenNode> = tokens.tokens.map(token => new SqlAstTokenNode(root, token));

    for (var tokenNode of tokenNodes) {
        root.addToken(tokenNode);
    }

    root.markLexingFinished();

    return root;
}

export class SqlAstRootClass extends SqlAstBranch implements SqlAstRoot
{
    private lexingFinished: boolean = false;

    constructor(
        children: Array<SqlAstNode>, 
        public readonly tokens: SqlTokens,
        nodeType: string = 'SqlAstRoot'
    ) {
        super(nodeType, children);
    }

    public addToken(token: SqlAstTokenNode): void
    {
        assert(!this.lexingFinished);

        super.replace(this.children().length, 1, token);
    }

    public markLexingFinished(): void
    {
        this.lexingFinished = true;
    }

    public replace(
        offset: number,
        length: number,
        newNode: SqlAstNode
    ): void {
        assert(this.lexingFinished);

        super.replace(offset, length, newNode);
    }

    public root(): SqlAstRoot
    {
        return this;
    }

    public line(): number
    {
        return 1;
    }

    public column(): number
    {
        return 0;
    }

    public toSql(): string
    {
        var sql: string = "";

        for (var node of this.children()) {
            sql += node.toSql();
        }

        return sql;
    }
}
