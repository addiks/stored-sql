
import { SqlType } from 'storedsql';

export interface SqlFunction
{
    name: string;
    arguments: Array<SqlFunctionArgument>;
}

export interface SqlFunctionArgument
{
    type: string;
}

