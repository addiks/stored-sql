
import { WidgetHelper, loadWidgetHelperById, SqlAstNode, SqlParser, SqlAstSelect } from 'storedsql';
import widgetsTemplate from '../../twig/widgets.html.twig';

class SimpleWidget
{
    private nodes: Array<SqlAstNode>;
    
    constructor(
        private readonly widgetHelper: WidgetHelper,
        template: Function|null,
        hideOriginalElement: boolean
    ) {
        this.nodes = widgetHelper.readSqlNodes();
        
        if (template == null) {
            template = widgetsTemplate;
        }

        if (hideOriginalElement) {
            widgetHelper.hideOriginalElement();
        }
        
        widgetHelper.appendHtml(template(this.templateData()));
    }
    
    private templateData(): any
    {
        let nodes: Array<object> = [];
        
        for (let node: SqlAstNode of this.nodes) {
            console.log(node);
            
            if (node.nodeType == 'SqlAstSelect') {
                nodes.push(this.selectNode(node as SqlAstSelect));
            }
        }
        
        return {
            'foo': 'Lorem ipsum',
            'nodes': nodes
        };
    }
    
    private selectNode(node: SqlAstSelect): object
    {
        let columns: Array<object> = [];
        
        return {
            'type': node.nodeType,
            'columns': columns,
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

