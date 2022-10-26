
import { Schema, SqlFunction } from 'storedsql'

export interface Environment
{
    schemata: Array<Schema>;
    functions: Array<SqlFunction>
}

class EnvironmentClass implements Environment
{
    constructor(
        public readonly schemata: Array<Schema>,
        public readonly functions: Array<SqlFunction>
    ) {
    }
}

export function createEnvironment(options: Map<string, any>): Environment
{
    return new EnvironmentClass(
        options['schemata'] ?? [],
        options['functions'] ?? []
    )
}
