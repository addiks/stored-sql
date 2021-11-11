
import { sprintf } from 'sprintf-js';

export class UnlexableSqlException extends Error
{

    private _sql: string;
    private _sqlLine: number;
    private _sqlOffset: number;

    constructor(sql: string, line: number, offset: number)
    {
        super(sprintf(
            'There was an error while lexing the given SQL code at line %d, offset %d!',
            line + 1,
            offset
        ));
        
        this._sql = sql;
        this._sqlLine = line;
        this._sqlOffset = offset;
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
