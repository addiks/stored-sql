
import { sprintf } from 'sprintf-js';
import { SqlAstNode, SqlAstTokenNode, SqlAstMutableNode, AbstractSqlToken } from 'storedsql';

declare function assert(value: unknown): asserts value;

export class UnparsableSqlException extends Error
{
    private _node: SqlAstNode;
    private _sql: string;
    private _sqlLine: number;
    private _sqlOffset: number;

    constructor(
        message: string, 
        node: SqlAstNode, 
        parent?: Error
    ) {
        super(message);
        
        this._node = node;
        this._sql = node.root().tokens.sql();
        this._sqlLine = node.line();
        this._sqlOffset = node.column();
    }

    public node(): SqlAstNode
    {
        return this._node;
    }

    public sql(): string
    {
        return this._sql;
    }

    public sqlLine(): number
    {
        return this._sqlLine;
    }

    public sqlOffset(): number
    {
        return this._sqlOffset;
    }
    
    public asciiLocationDump(): string
    {
        const lines: Array<string> = this.sql().split("\n");

        // \u{219X} are unicode arrow-characters

        const sqlLine: string = this.sqlLine().toString();
        const sqlOffset: number = this.sqlOffset();
        const longestLength: number = Math.max(...lines.map(line => line.length));

        for (const lineIndex in lines) {
            let line: string = lines[lineIndex];

            if (lineIndex === sqlLine) {
                line = " \u{2192} " + line + ''.padStart(longestLength - line.length) + " \u{2190}";

            } else {
                line = '   ' + line;
            }
            
            lines[lineIndex] = line;
        }

        return sprintf(
            "\n\n%s\n%s\n%s\n",
            "\u{2193}".padStart(sqlOffset + 6, ' '),
            lines.join("\n"),
            "\u{2191}".padStart(sqlOffset + 6, ' ')
        );
    }
}

export function assertSql(
    parent: SqlAstMutableNode, 
    offset: number, 
    expectedSql: string
): void {
    let actualNode: SqlAstNode | null = parent.get(offset);
    expectedSql = expectedSql.toUpperCase();

    if (!(actualNode instanceof SqlAstTokenNode) || actualNode.toSql().toUpperCase() !== expectedSql) {
        throw new UnparsableSqlException(sprintf(
            "Expected SQL code '%s' at offset %d, found %s instead!",
            expectedSql,
            offset,
            typeof actualNode == 'object' ? actualNode.constructor.name : 'nothing'
        ), actualNode);
    }
}


export function assertSqlToken(
    parent: SqlAstMutableNode, 
    offset: number, 
    expectedToken: AbstractSqlToken
): void {
    let actualNode: SqlAstNode | null = parent.get(offset);
    let success = true;
    
    if (actualNode instanceof SqlAstTokenNode) {
        let actualTokenNode: SqlAstTokenNode = actualNode;
        
        success = !actualTokenNode.is(expectedToken);
        
    } else {
        success = false;
    }

    if (!success) {
        throw new UnparsableSqlException(sprintf(
            "Expected token '%s' at offset %d, found %s instead!",
            expectedToken.name(),
            offset,
            typeof actualNode == 'object' ? actualNode.constructor.name : 'nothing'
        ), actualNode);
    }
}

export function assertSqlType(
    parent: SqlAstMutableNode, 
    offset: number, 
    expectedNodeType: string
): void {
    let actualNode: SqlAstNode | null = parent.get(offset);

    if (typeof actualNode != 'object' || actualNode.nodeType != expectedNodeType) {
        throw new UnparsableSqlException(sprintf(
            "Expected node of '%s' at offset %d, found %s instead!",
            expectedNodeType,
            offset,
            typeof actualNode == 'object' ? actualNode.constructor.name : 'nothing'
        ), actualNode ?? parent);
    }
}

