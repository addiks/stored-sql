
import { defaultParser, SqlParser, SqlAstNode } from 'storedsql';

export interface WidgetHelper
{
    writeSqlNodes(nodes: Array<SqlAstNode>): void;
    readSqlNodes(): Array<SqlAstNode>;
    hideOriginalElement(): void;
    showOriginalElement(): void;
    appendHtml(html: string): void;
}

export function loadWidgetHelperById(
    inputElementId: string,
    targetElementId: string,
    parser: SqlParser = null
): WidgetHelper {
    let inputElement: HTMLElement|null = document.querySelector('#' + inputElementId);
    let targetElement: HTMLElement|null = document.querySelector('#' + targetElementId);

    if (inputElement instanceof HTMLTextAreaElement) {
        return new TextareaHelper(inputElement, targetElement, parser);

    } else if (inputElement instanceof HTMLInputElement) {
        return new InputHelper(inputElement, targetElement, parser);
    }
    
    throw Error('Could not load widget-helper for html element with id "'+ inputElementId +'"');
}

class ElementHelper
{
    public readonly parser: SqlParser;
    
    constructor(
        private readonly inputElement: HTMLElement,
        private readonly targetElement: HTMLElement,
        parser: SqlParser = null
    ) {
        this.parser = parser ?? defaultParser();
    }
    
    hideOriginalElement(): void
    {
        this.inputElement.hidden = true;
    }
    
    showOriginalElement(): void
    {
        this.inputElement.hidden = false;
    }
    
    appendHtml(html: string): void
    {
        let document: HTMLDocument = this.targetElement.getRootNode() as HTMLDocument;
        let div: HTMLElement = document.createElement('div');
        
        div.innerHTML = html;
        
        this.targetElement.parentNode.insertBefore(div, this.targetElement.nextSibling);
    }
}

class TextareaHelper extends ElementHelper implements WidgetHelper
{
    constructor(
        public readonly textarea: HTMLTextAreaElement,
        public readonly target: HTMLElement,
        parser: SqlParser = null
    ) {
        super(textarea, target, parser);
    }
    
    writeSqlNodes(nodes: Array<SqlAstNode>): void
    {
        this.textarea.value = nodes.map(node => node.toSql()).join(";\n");
    }
    
    readSqlNodes(): Array<SqlAstNode>
    {
        return this.parser.parseSql(this.textarea.value);
    }
}

class InputHelper extends ElementHelper implements WidgetHelper
{
    
    constructor(
        private readonly input: HTMLInputElement,
        public readonly target: HTMLElement,
        parser: SqlParser = null
    ) {
        super(input, target, parser);
    }
    
    writeSqlNodes(nodes: Array<SqlAstNode>): void
    {
        this.input.value = nodes.map(node => node.toSql()).join(";\n");
    }
    
    readSqlNodes(): Array<SqlAstNode>
    {
        return this.parser.parseSql(this.input.value);
    }
}

