/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstNode } from '../AbstractSyntaxTree/SqlAstNode'

export interface SqlParser
{
    parseSql(sql: string, expectedResultTypes: Array<string> = null): Array<SqlAstNode>;
}

export class SqlParserClass implements SqlParser
{
    private tokenizer: SqlTokenizer;
    private array mutators: Array<Function>;

    constructor(
        tokenizer: SqlTokenizer,
        mutators: Array<Function>
    ) {
        this.tokenizer = tokenizer;
        this.mutators = mutators;
    }

    public static defaultParser(): SqlParser
    {
        return new SqlParserClass(
            SqlTokenizerClass.defaultTokenizer(),
            SqlParserClass.defaultMutators()
        );
    }

    /** @return array<callable> */
    public static defaultMutators(): array
    {
        return [
            SqlAstLiteral.mutateAstNode(),
            SqlAstColumn.mutateAstNode(),
            SqlAstOperation.mutateAstNode(),
            SqlAstConjunction.mutateAstNode(),
            SqlAstWhere.mutateAstNode(),
            SqlAstOrderBy.mutateAstNode(),
            SqlAstParenthesis.mutateAstNode(),
            SqlAstFrom.mutateAstNode(),
            SqlAstJoin.mutateAstNode(),
            SqlAstSelect.mutateAstNode(),
        ];
    }

    public parseSql(sql: string, expectedResultTypes: Array<string> = null): Array<SqlAstNode>
    {
        /** @var SqlTokens tokens */
        tokens = this.tokenizer.tokenize(sql);

        tokens = tokens.withoutWhitespace();
        tokens = tokens.withoutComments();

        /** @var SqlAstRoot syntaxTree */
        syntaxTree = tokens.convertToSyntaxTree();

        /** @var callable mutator */
        foreach (this.mutators as mutator) {
            syntaxTree.walk([mutator]);
        } 

        var detectedContent: Array<SqlAstNode> = syntaxTree.children();

        /** @var SqlAstNode detectedNode */
        foreach (detectedContent as detectedNode) {

            /** @var class-string expectedClass */
            foreach (expectedResultTypes as expectedClass) {
                Assert::classExists(expectedClass);

                if (detectedNode instanceof expectedClass) {
                    continue 2;
                }
            }

            throw new UnparsableSqlException(sprintf(
                "Unexpected node of type '%s' detected, expected one of: [%s]!",
                get_class(detectedNode),
                implode(', ', expectedResultTypes)
            ), detectedNode);
        }

        # TODO: make sure all detected nodes are "final" nodes (like a select statement)

        return detectedContent;
    }

    public tokenizer(): SqlTokenizer
    {
        return this.tokenizer;
    }

    public mutators(): Array<Function>
    {
        return this.mutators;
    }
}
