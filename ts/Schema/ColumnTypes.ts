
export interface SqlType
{
    name: string;
}

class IntegerType implements SqlType
{
    constructor(
        public readonly name: string
    ) {}
}

class VarCharType implements SqlType
{
    constructor(
        public readonly name: string
    ) {}
}

export function createType(
    name: string,
    options: Map<string, any> = new Map<string, any>()
): SqlType {
    if (name == 'integer') {
        return new IntegerType(name);
    }
    if (name == 'varchar') {
        return new VarCharType(name);
    }
    
    throw Error('Unknown type: ' + name);
}
