
import { Schemas, Column, Table, SqlAstFrom, SqlAstJoin } from 'storedsql';

export interface QueryContext
{
    columns(): Array<Column>;
    tables(): Array<Table>;
    mapAstColumnToSchemaColumn(source: SqlAstColumn): Column|null;
    mapAstTableToSchemaTable(source: SqlAstTokenNode): Table|null;
    sources(): Map<string,SqlAstFrom|SqlAstJoin|QueryContext>;
}

class QueryContextAggregate implements QueryContext
{
    constructor(
        private readonly _sources: Map<string,QueryContext>
    ) {
    }
    
    public columns(): Array<Column>
    {
        var columns: Array<Column> = new Array<Column>();
        
        for (var source of this._sources) {
            columns = columns.concat(source.columns());
        }
        
        return columns;
    }
    
    public tables(): Array<Table>
    {
        var tables: Array<Table> = new Array<Table>();
        
        for (var source of this._sources) {
            tables = tables.concat(source.tables());
        }
        
        return tables;
    }
    
    public mapAstColumnToSchemaColumn(source: SqlAstColumn): Column|null
    {
        // TODO: find specified sub-context and relay call
        return findColumn(this.columns(), source);
    }
    
    public mapAstTableToSchemaTable(source: SqlAstTokenNode): Table|null
    {
        // TODO: find specified sub-context and relay call
        return findTable(this.tables(), source);
    }
    
    public sources(): Map<string,QueryContext>
    {
        return this._sources
    }
    
}

class SchemaTableQueryContext implements QueryContext
{
    constructor (
        public readonly source: SqlAstFrom|SqlAstJoin,
        public readonly table: Table
    ) {
    }
    
    public columns(): Array<Column>
    {
        return this.table.columns;
    }
    
    public tables(): Array<Table>
    {
        return new Array<Table>(this.table);
    }
    
    public mapAstColumnToSchemaColumn(source: SqlAstColumn): Column|null;
    {
        return findColumn(this.columns(), source);
    }
    
    public mapAstTableToSchemaTable(source: SqlAstTokenNode): Table|null
    {
        return this.table;
    }
    
    public sources(): Map<string,SqlAstFrom|SqlAstJoin>
    {
        return new Map<string, SqlAstFrom|SqlAstJoin>([
            [this.table.name, this.source]
        ]);
    }
}

class BlindQueryContext implements QueryContext
{
    constructor(
        public readonly schemas: Schemas
    ) {}
    
    public columns(): Array<Column>
    {
        return this.schemas.allColumns();
    }
    
    public tables(): Array<Table>
    {
        return this.schemas.allTables();
    }
    
    public mapAstColumnToSchemaColumn(source: SqlAstColumn): Column|null
    {
        return findColumn(this.columns(), source);
    }
    
    public mapAstTableToSchemaTable(source: SqlAstTokenNode): Table|null
    {
        return findTable(this.tables(), source);
    }
    
    public sources(): Map<string,SqlAstFrom|SqlAstJoin|QueryContext>
    {
        return new Map<string,QueryContext>([]);
    }
    
}

// TODO: class SubQueryContext implements QueryContext

function findColumn(columns: Array<Column>, source: SqlAstColumn)
{
    var column: Column|null = null;
    
    for (var candidate of columns) {
        if (candidate.name == source.name()) {
            column = candidate;
            break;
        }
    }
    
    return column;
}

function findTable(tables: Array<Table>, source: SqlAstTokenNode)
{
    var table: Table|null = null;
    
    for (var candidate of tables) {
        if (candidate.name == source.token.code()) {
            table = candidate;
            break;
        }
    }
    
    return table;
}

export function createQueryContext(schemas: Schemas, SqlAstNode: query): QueryContext|null
{
    var queryContext: QueryContext|null = null;
    
    if (query.nodeType == "SqlAstFrom" || query.nodeType == "SqlAstJoin") {
        var tableName: string = "";
        
        if (query.nodeType == "SqlAstFrom") {
            tableName = (query as SqlAstFrom).tableName;
        } else {
            tableName = (query as SqlAstJoin).tableName;
        }
        
        var schemaName: string|null = null;
        var table: Table|null = schemas.tableByName(tableName, schemaName);
        
        return new SchemaTableQueryContext(query, table);

    } else if (query.nodeType == "SqlAstSelect") {
        
    }
    
    return new BlindQueryContext(schemas);
}
