

export function assert(value: unknown, message: string = "Assertion failed!"): asserts value
{
    if (value === undefined || !value) {
        throw new Error(message);
    }
}

export * from './Lexing/AbstractSqlToken'
export * from './Lexing/SqlToken'
export * from './Lexing/SqlTokens'
export * from './Lexing/SqlTokenizer'
export * from './Lexing/SqlTokenInstance'
export * from './Exception/UnlexableSqlException'
export * from './AbstractSyntaxTree/SqlAstNode'
export * from './AbstractSyntaxTree/SqlAstMutableNode'
export * from './AbstractSyntaxTree/SqlAstBranch'
export * from './AbstractSyntaxTree/SqlAstTokenNode'
export * from './AbstractSyntaxTree/SqlAstRoot'
export * from './AbstractSyntaxTree/SqlAstExpression'
export * from './AbstractSyntaxTree/SqlAstLiteral'
export * from './AbstractSyntaxTree/SqlAstColumn'
export * from './AbstractSyntaxTree/SqlAstOperation'
export * from './AbstractSyntaxTree/SqlAstMergable'
export * from './AbstractSyntaxTree/SqlAstConjunction'
export * from './AbstractSyntaxTree/SqlAstWhereCondition'
export * from './AbstractSyntaxTree/SqlAstTable'
export * from './AbstractSyntaxTree/SqlAstJoin'
export * from './AbstractSyntaxTree/SqlAstOrderBy'
export * from './AbstractSyntaxTree/SqlAstFrom'
export * from './AbstractSyntaxTree/SqlAstParenthesis'
export * from './AbstractSyntaxTree/SqlAstUpdate'
export * from './AbstractSyntaxTree/SqlAstSelect'
export * from './Exception/UnparsableSqlException'
export * from './Parsing/SqlParser'
export * from './Schema/ColumnTypes'
export * from './Schema/Schema'
export * from './Schema/Functions'
export * from './Schema/Environment'
export * from './QueryContext'
export * from './Widgets/WidgetHelper'
export * from './Widgets/SimpleWidget'
