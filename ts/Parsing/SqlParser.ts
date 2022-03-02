/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { 
    SqlAstNode, SqlTokenizer, defaultTokenizer, SqlTokens, SqlAstRoot, convertTokensToSyntaxTree, 
    mutateLiteralAstNode, mutateColumnAstNode, mutateOperationAstNode, mutateWhereAstNode, 
    mutateUpdateAstNode, mutateConjunctionAstNode, mutateFromAstNode, mutateSelectAstNode,
    mutateOrderByAstNode, mutateParenthesisAstNode, mutateJoinAstNode
} from 'storedsql'

export function defaultParser(): SqlParser
{
    return new SqlParserClass(defaultTokenizer(), defaultMutators());
}

export function defaultMutators(): Array<Function>
{
        return [
            mutateLiteralAstNode,
            mutateColumnAstNode,
            mutateParenthesisAstNode,
            mutateOperationAstNode,
            mutateConjunctionAstNode,
            mutateWhereAstNode,
            mutateOrderByAstNode,
            mutateFromAstNode,
            mutateJoinAstNode,
            mutateSelectAstNode,
            mutateUpdateAstNode,
        ];
}

export interface SqlParser
{
    parseSql(sql: string, expectedResultTypes?: Array<string>): Array<SqlAstNode>;
}

export class SqlParserClass implements SqlParser
{
    constructor(
        private readonly tokenizer: SqlTokenizer,
        private readonly mutators: Array<Function>
    ) {
    }

    public parseSql(sql: string, expectedResultTypes?: Array<string>): Array<SqlAstNode>
    {
        
        let tokens: SqlTokens = this.tokenizer.tokenize(sql);
        
        tokens = tokens.withoutWhitespace();
        tokens = tokens.withoutComments();

        let syntaxTree: SqlAstRoot = convertTokensToSyntaxTree(tokens);

//        console.log(syntaxTree);

        let hashBefore: string = '';

        do {
            hashBefore = syntaxTree.hash();
            
            /** @var callable mutator */
            for (var mutator of this.mutators) {
                syntaxTree.walk([mutator]);
            } 
            
        } while (hashBefore != syntaxTree.hash());

        var detectedContent: Array<SqlAstNode> = syntaxTree.children();

//        /** @var SqlAstNode detectedNode */
//        foreach (detectedContent as detectedNode) {
//
//            /** @var class-string expectedClass */
//            foreach (expectedResultTypes as expectedClass) {
//                Assert::classExists(expectedClass);
//
//                if (detectedNode instanceof expectedClass) {
//                    continue 2;
//                }
//            }
//
//            throw new UnparsableSqlException(sprintf(
//                "Unexpected node of type '%s' detected, expected one of: [%s]!",
//                get_class(detectedNode),
//                implode(', ', expectedResultTypes)
//            ), detectedNode);
//        }

        // TODO: make sure all detected nodes are "final" nodes (like a select statement)

        return detectedContent;
    }

//    public tokenizer(): SqlTokenizer
//    {
//        return this.tokenizer;
//    }
//
//    public mutators(): Array<Function>
//    {
//        return this.mutators;
//    }
}
