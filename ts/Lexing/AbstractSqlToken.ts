
export abstract class AbstractSqlToken
{
    constructor(
        public readonly value: string
    ) {
    }
    
    public name(): string
    {
        return this.value;
    }
}
