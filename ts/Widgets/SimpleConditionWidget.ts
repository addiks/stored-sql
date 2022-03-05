
import { WidgetHelper, loadWidgetHelperById } from 'storedsql';


export class SimpleConditionWidget
{
    constructor(
        private readonly widgetHelper: WidgetHelper
    ) {
        
    }
    
}

export function createSimpleConditionWidgetById(elementId: string): SimpleConditionWidget
{
    return new SimpleConditionWidget(
        loadWidgetHelperById(elementId)
    );
}
