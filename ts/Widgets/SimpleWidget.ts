
import { 
    WidgetHelper, loadWidgetHelperById, SqlAstNode, SqlParser, SqlAstSelect, SqlAstExpression, SqlAstFrom, SqlAstColumn,
    SqlAstWhere, SqlAstOperation, SqlAstTokenNode, SqlAstLiteral
} from 'storedsql';
import widgetsTemplate from '../../twig/widgets.html.twig';

class SimpleWidget
{
    private nodes: Array<SqlAstNode>;
    
    constructor(
        private readonly widgetHelper: WidgetHelper,
        template: Function|null,
        hideOriginalElement: boolean,
        private readonly conversionMap?: Map<string, Function>
    ) {
        this.nodes = widgetHelper.readSqlNodes();
        
        if (conversionMap == null) {
            conversionMap = new Map<string, Function>();
        }
        
        if (template == null) {
            template = widgetsTemplate;
        }

        if (hideOriginalElement) {
            widgetHelper.hideOriginalElement();
        }
        
        let templateData: object = this.templateData();
        
        console.log(templateData);
        
        widgetHelper.appendHtml(template(templateData));
    }
    
    private templateData(): any
    {
        let nodes: Array<object> = [];
        let sqls: Array<string> = [];
        
        for (let node of this.nodes) {
            nodes.push(this.node(node));
            sqls.push(node.toSql());
        }
        
        return {
            'nodes': nodes,
            'sql': sqls.join(';')
        };
    }
    
    private node(node: SqlAstNode|null, alias?: string|null): object|null
    {
        if (node == null) {
            return null;
            
        } 
        
        let additionalNodeData: object|null = this.additionalNodeData(node, alias);
        
        if (additionalNodeData != null) {
            return {...node, ...additionalNodeData};
        }
        
        console.log(node);
        return {};
    }
    
    private additionalNodeData(node: SqlAstNode|null, alias?: string|null): object|null
    {
        if (node.nodeType == 'SqlAstSelect') {
            return this.selectNode(node as SqlAstSelect);
            
        } else if (node.nodeType == 'SqlAstColumn') {
            return this.columnNode(node as SqlAstColumn, alias);
            
        } else if (node.nodeType == 'SqlAstFrom') {
            return this.fromNode(node as SqlAstFrom);
            
        } else if (node.nodeType == 'SqlAstWhere') {
            return this.whereNode(node as SqlAstWhere);
            
        } else if (node.nodeType == 'SqlAstOperation') {
            return this.operationNode(node as SqlAstOperation);
            
        } else if (node.nodeType == 'SqlAstTokenNode') {
            return this.tokenNode(node as SqlAstTokenNode);
            
        } else if (node.nodeType == 'SqlAstLiteral') {
            return this.literalNode(node as SqlAstLiteral);
            
        } else if (this.conversionMap.has(node.nodeType)) {
            return this.conversionMap.get(node.nodeType)(node);
        }
        
        return null;
    }
    
    private selectNode(select: SqlAstSelect): object
    {
        let columns: Array<object> = [];
        
        for (let alias of select.columns.keys()) {
            columns.push(this.node(select.columns.get(alias), alias));
        }
        
        return {
            'columns': columns,
            'from': this.node(select.from),
            'joins': select.joins.map(node => this.node(node)),
            'where': this.node(select.where),
            'orderBy': this.node(select.orderBy)
        };
    }
    
    private fromNode(from: SqlAstFrom): object
    {
        return {
            'table': this.node(from.tableName),
            'alias': (from.alias != null ?from.alias.toSql() :'')
        };
    }
    
    private whereNode(where: SqlAstWhere): object
    {
        return {
            'expression': this.node(where.expression)
        };
    }
    
    private operationNode(operation: SqlAstOperation): object
    {
        return {
            'leftSide': this.node(operation.leftSide),
            'operator': this.node(operation.operator),
            'rightSide': this.node(operation.rightSide),
        };
    }
    
    private tokenNode(token: SqlAstTokenNode): object
    {
        return {
            'symbol': token.toSql()
        };
    }
    
    private literalNode(literal: SqlAstLiteral): object
    {
        return {
            'literal': literal.toSql()
        };
    }
    
    private columnNode(column: SqlAstColumn, alias: string|null): object
    {
        return {
            'alias': alias,
            'sql': column.toSql()
        };
    }
}

export function createWidget(options: Map<string, any>): SimpleWidget
{
    return new SimpleWidget(
        loadWidgetHelperById(
            options['input'],
            options['target'] ?? options['input'],
            options['parser'] ?? null
        ),
        options['template'] ?? null,
        options['hide_original_element'] ?? true
    );
} 

