/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { AbstractSqlToken } from './AbstractSqlToken'

export class SqlToken extends AbstractSqlToken
{
    static readonly SPACE = new SqlToken('SPACE');
    static readonly COMMENT = new SqlToken('COMMENT');
    static readonly DOT = new SqlToken('DOT');
    static readonly SYMBOL = new SqlToken('SYMBOL');
    static readonly LITERAL = new SqlToken('LITERAL');
    static readonly NUMERIC = new SqlToken('NUMERIC');
    static readonly OPERATOR = new SqlToken('OPERATOR');

    static readonly BRACKET_OPENING = new SqlToken('BRACKET_OPENING');
    static readonly BRACKET_CLOSING = new SqlToken('BRACKET_CLOSING');
    static readonly COMMA = new SqlToken('COMMA');
    static readonly SEMICOLON = new SqlToken('SEMICOLON');

    static readonly SELECT = new SqlToken('SELECT');
    static readonly AS = new SqlToken('AS');
    static readonly FROM = new SqlToken('FROM');
    static readonly LEFT = new SqlToken('LEFT');
    static readonly INNER = new SqlToken('INNER');
    static readonly JOIN = new SqlToken('JOIN');
    static readonly ON = new SqlToken('ON');
    static readonly USING = new SqlToken('USING');
    static readonly WHERE = new SqlToken('WHERE');
    static readonly IN = new SqlToken('IN');
    static readonly IS = new SqlToken('IS');
    static readonly AND = new SqlToken('AND');
    static readonly OR = new SqlToken('OR');
    static readonly LIKE = new SqlToken('LIKE');
    static readonly HAVING = new SqlToken('HAVING');
    static readonly LIMIT = new SqlToken('LIMIT');
    static readonly ORDER = new SqlToken('ORDER');
    static readonly BY = new SqlToken('BY');
    static readonly ASC = new SqlToken('ASC');
    static readonly DESC = new SqlToken('DESC');
    static readonly DISTINCT = new SqlToken('DISTINCT');
    static readonly FUNCTION_NAME = new SqlToken('FUNCTION_NAME');

    static readonly UPDATE = new SqlToken('UPDATE');

    static readonly DELETE = new SqlToken('DELETE');

    static readonly ALTER = new SqlToken('ALTER');
    static readonly TABLE = new SqlToken('TABLE');
    static readonly SCHEMA = new SqlToken('SCHEMA');
    static readonly COLUMN = new SqlToken('COLUMN');
    static readonly INDEX = new SqlToken('INDEX');
    static readonly KEY = new SqlToken('KEY');

    static readonly CREATE = new SqlToken('CREATE');
    static readonly DATA_TYPE = new SqlToken('DATA_TYPE');
    static readonly NOT = new SqlToken('NOT');
    static readonly T_NULL = new SqlToken('NULL');
    static readonly PRIMARY_KEY = new SqlToken('PRIMARY_KEY');

    static readonly SET = new SqlToken('SET');

    static readonly SHOW = new SqlToken('SHOW');
}
