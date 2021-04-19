<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\StoredSQL\Lexing;

/**
 * @method static self SPACE()
 * @method static self COMMENT()
 * @method static self DOT()
 * @method static self SYMBOL()
 * @method static self LITERAL()
 * @method static self NUMERIC()
 * @method static self OPERATOR()
 * @method static self BRACKET_OPENING()
 * @method static self BRACKET_CLOSING()
 * @method static self COMMA()
 * @method static self SEMICOLON()
 * @method static self SELECT()
 * @method static self FROM()
 * @method static self LEFT()
 * @method static self INNER()
 * @method static self JOIN()
 * @method static self ON()
 * @method static self USING()
 * @method static self WHERE()
 * @method static self IN()
 * @method static self IS()
 * @method static self AND()
 * @method static self OR()
 * @method static self LIKE()
 * @method static self HAVING()
 * @method static self LIMIT()
 * @method static self ORDER()
 * @method static self BY()
 * @method static self DISTINCT()
 * @method static self FUNCTION_NAME()
 * @method static self UPDATE()
 * @method static self DELETE()
 * @method static self ALTER()
 * @method static self TABLE()
 * @method static self SCHEMA()
 * @method static self COLUMN()
 * @method static self INDEX()
 * @method static self KEY()
 * @method static self CREATE()
 * @method static self DATA_TYPE()
 * @method static self NOT()
 * @method static self T_NULL()
 * @method static self PRIMARY_KEY()
 * @method static self SET()
 * @method static self SHOW()
 */
final class SqlToken extends AbstractSqlToken
{
    protected const SPACE = null;
    protected const COMMENT = null;
    protected const DOT = null;
    protected const SYMBOL = null;
    protected const LITERAL = null;
    protected const NUMERIC = null;
    protected const OPERATOR = null;

    protected const BRACKET_OPENING = null;
    protected const BRACKET_CLOSING = null;
    protected const COMMA = null;
    protected const SEMICOLON = null;

    protected const SELECT = null;
    protected const FROM = null;
    protected const LEFT = null;
    protected const INNER = null;
    protected const JOIN = null;
    protected const ON = null;
    protected const USING = null;
    protected const WHERE = null;
    protected const IN = null;
    protected const IS = null;
    protected const AND = null;
    protected const OR = null;
    protected const LIKE = null;
    protected const HAVING = null;
    protected const LIMIT = null;
    protected const ORDER = null;
    protected const BY = null;
    protected const DISTINCT = null;
    protected const FUNCTION_NAME = null;

    protected const UPDATE = null;

    protected const DELETE = null;

    protected const ALTER = null;
    protected const TABLE = null;
    protected const SCHEMA = null;
    protected const COLUMN = null;
    protected const INDEX = null;
    protected const KEY = null;

    protected const CREATE = null;
    protected const DATA_TYPE = null;
    protected const NOT = null;
    protected const T_NULL = null;
    protected const PRIMARY_KEY = null;

    protected const SET = null;

    protected const SHOW = null;
}
