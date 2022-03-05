
import { defaultParser, SqlParser } from 'storedsql';

export interface WidgetHelper
{
    write(nodes: Array<SqlAstNode>): void;
    read(): Array<SqlAstNode>;
    hide(): void;
    show(): void;
}

export function loadWidgetHelperById(elementId: string): WidgetHelper
{
    let element: HTMLElement|null = document.querySelector('#' + elementId);
    
    if (element instanceof HTMLTextAreaElement) {
        return new TextareaHelper(element);

    } else if (element instanceof HTMLInputElement) {
        return new InputHelper(element);
    }
    
    throw Error('Could not load widget-helper for html element with id "'+ elementId +'"');
}

class ElementHelper
{
    public readonly parser: SqlParser;
    
    constructor(
        public readonly element: HTMLElement,
        parser: SqlParser = null
    ) {
        this.parser = parser ?? defaultParser();
    }
    
    hide(): void
    {
        
    }
    
    show(): void
    {
        
    }
}

class TextareaHelper extends ElementHelper implements WidgetHelper
{
    constructor(
        public readonly textarea: HTMLTextAreaElement,
        parser: SqlParser = null
    ) {
        super(textarea, parser);
    }
    
    write(nodes: Array<SqlAstNode>): void
    {
        this.textarea.value = nodes.map(node => node.toSql()).join(";\n");
    }
    
    read(): Array<SqlAstNode>
    {
        return this.parser.parseSql(this.textarea.value);
    }
}

class InputHelper extends ElementHelper implements WidgetHelper
{
    
    constructor(
        private readonly input: HTMLInputElement,
        parser: SqlParser = null
    ) {
        super(input, parser);
    }
    
    write(nodes: Array<SqlAstNode>): void
    {
        this.input.value = nodes.map(node => node.toSql()).join(";\n");
    }
    
    read(): Array<SqlAstNode>
    {
        return this.parser.parseSql(this.input.value);
    }
}

