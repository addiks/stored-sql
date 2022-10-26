
import { SqlType, createType, assert } from 'storedsql';

export interface Schemas
{
    schemas: Map<string, Schema>;
    defaultSchema(): Schema|null;
    allTables(): Array<Table>;
    allColumns(): Array<Column>;
}

export interface Schema
{
    name: string;
    tables(): Map<string, Table>;
    allColumns(): Array<Column>;
    addTable(table: Table): void;
}

export interface Table
{
    schema: Schema;
    name: string;
    columns(): Map<string, Column>;
    addColumn(column: Column): void;
}

export interface Column
{
    table: Table;
    name: string;
    type: SqlType;
    fullName(): string;
}

class SchemasClass implements Schemas
{
    private defaultSchemaName: string|null;

    constructor (
        public readonly schemas: Map<string, Schema>,
        defaultSchemaName: string|null = null
    ) {
        if (typeof defaultSchemaName == "string") {
            assert(schemas.has(defaultSchemaName));
            this.defaultSchemaName = defaultSchemaName;

        } else if (schemas.size > 0) {
            [this.defaultSchemaName] = schemas.keys();
        }
    }
    
    public defaultSchema(): Schema|null
    {
        if (typeof this.defaultSchemaName == "string") {
            return this.schemas.get(this.defaultSchemaName);
        }
    }
    
    public allTables(): Array<Table>
    {
        var tables: Array<Table> = new Array<Table>();
        
        for (var schema of this.schemas.values()) {
            tables = tables.concat(Array.from(schema.tables().values()));
        }
        
        return tables;
    }
    
    public tableByName(tableName: string, schemaName: string|null): Table|null
    {
        var result: Table|null = null;
        
        if (schemaName == null && this.defaultSchemaName != null) {
            result = this.tableByName(tableName, this.defaultSchemaName);
        }
        
        if (result == null) {
            for (var table of this.allTables()) {
                if (table.name == tableName) {
                    if (schemaName == null || schemaName == table.schema.name) {
                        result = table;
                        break;
                    }
                }
            }
        }
        
        return result;
    }
    
    public allColumns(): Array<Column>
    {
        var columns: Array<Column> = new Array<Column>();
        
        for (var schema of this.schemas.values()) {
            columns = columns.concat(schema.allColumns());
        }
        
        return columns;
    }
}

class SchemaClass implements Schema
{
    private _tables: Map<string, Table>;
    
    constructor (
        public readonly name: string,
        tables: Map<string, Table> = new Map<string, Table>()
    ) {
        this._tables = tables;
    }
    
    public tables(): Map<string, Table>
    {
        return this._tables;
    }
    
    public allColumns(): Array<Column>
    {
        var columns: Array<Column> = new Array<Column>();
        
        for (var table of this._tables.values()) {
            columns = columns.concat(Array.from(table.columns().values()));
        }
        
        return columns;
    }
    
    public addTable(table: Table): void
    {
        this._tables.set(table.name, table);
    }
}

class TableClass implements Table
{
    private _columns: Map<string, Column>
    
    constructor (
        public readonly schema: Schema,
        public readonly name: string,
        columns: Map<string, Column> = new Map<string, Column>()
    ) {
        this._columns = columns;

        this.schema.addTable(this);
    }
    
    public columns(): Map<string, Column>
    {
        return this._columns;
    }

    public addColumn(column: Column): void
    {
        this._columns.set(column.name, column);
    }
}

class ColumnClass implements Column
{
    constructor (
        public readonly table: Table,
        public readonly name: string,
        public readonly type: SqlType
    ) {
        this.table.addColumn(this);
    }
    
    public fullName(): string
    {
        return this.table.schema.name + '.' + this.table.name + '.' +  this.name;
    }
}

export function createSchemasFromArray(options: Map<string, any>|Array<Map<string, any>>): Schemas
{
    var schemas: Map<string, Schema> = new Map<string, Schema>();
    var schemasOptions = options['schemas'] ?? options;

    for (var schemaName in schemasOptions) {
        var tables: Map<string, Table> = new Map<string, Table>();
        var schemaOptions = schemasOptions[schemaName]['tables'] ?? schemasOptions[schemaName];
        var schema = new SchemaClass(schemasOptions[schemaName]['name'] ?? schemaName);

        for (var tableName in schemaOptions) {
            var columns: Map<string, Column> = new Map<string, Column>();
            var tableOptions = schemaOptions[tableName]['columns'] ?? schemaOptions[tableName];
            var table = new TableClass(schema, schemaOptions[tableName]['name'] ?? tableName);

            for (var columnName in tableOptions) {
                var column = tableOptions[columnName];

                if (typeof column == 'string') {
                    new ColumnClass(
                        table,
                        columnName,
                        createType(column, new Map<string, any>())
                    );

                } else {
                    new ColumnClass(
                        table,
                        column['name'] ?? columnName,
                        createType(column['type'], column)
                    );
                }
            }
        }

        schemas.set(schemasOptions[schemaName]['name'] ?? schemaName, schema);
    }

    return new SchemasClass(schemas);
}
