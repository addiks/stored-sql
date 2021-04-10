<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\StoredSQL\Lexing;

use DASPRiD\Enum\AbstractEnum;
use Addiks\StoredSQL\Exception\UnlexableSqlException;
use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\Lexing\SqlTokensClass;

final class SqlToken extends AbstractEnum
{
    protected const SPACE = null;
    protected const COMMENT = null;
    protected const SYMBOL = null;
    protected const LITERAL = null;
    protected const NUMERIC = null;
    protected const EQUALS = null;

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
    protected const HAVING = null;
    protected const LIMIT = null;
    protected const ORDER_BY = null;
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
    protected const NOT_NULL = null;
    protected const PRIMARY_KEY = null;

    protected const SET = null;

    protected const SHOW = null;

    /** @var array<string, SqlToken>|null $keywords */
    private static ?array $keywords = null;

    public static function readToken(string $sql, string &$readSql = ""): ?SqlToken
    {
        if (preg_match('/^(\s+)/is', $sql, $match)) {
            $readSql = $match[1];
            return SqlToken::SPACE();
        }

        if ($sql[0] === '#' || ($sql[0] === '-' && $sql[1] === '-')) {
            /** @var int|bool $newLinePosition */
            $newLinePosition = strpos($sql, "\n");

            /** @var int $commentEndPosition */
            $commentEndPosition = PHP_INT_MAX;

            if ($sql[0] === '-') {
                $commentEndPosition = strpos($sql, '--', 2);

                if (!$commentEndPosition) {
                    if (is_int($newLinePosition)) {
                        $commentEndPosition = $newLinePosition;

                    } else {
                        $commentEndPosition = PHP_INT_MAX;
                    }
                }
            }

            if ($newLinePosition > $commentEndPosition) {
                $readSql = substr($sql, 0, $commentEndPosition + 2);

            } elseif (is_int($newLinePosition)) {
                $readSql = substr($sql, 0, $newLinePosition + 1);

            } else {
                $readSql = $sql;
            }

            return SqlToken::COMMENT();
        }

        if ($sql[0] === '"' || $sql[0] === "'") {
            /** @var int $endPosition */
            $endPosition = strpos($sql, $sql[0], 1);

            $readSql = substr($sql, 0, $endPosition + 1);
            return SqlToken::LITERAL();
        }

        /** @var SqlToken $token */
        foreach (self::keywords() as $keyword => $token) {
            if (strcasecmp($keyword, substr($sql, 0, strlen($keyword))) === 0) {
                $readSql = substr($sql, 0, strlen($keyword));
                return $token;
            }
        }

        if (preg_match("/^[0-9]+(\.[0-9]+)?/is", $sql, $match)) {
            $readSql = $match[0];
            return SqlToken::NUMERIC();
        }

        if ($sql[0] === '`') {
            /** @var int $endPosition */
            $endPosition = strpos($sql, '`', 1);

            $readSql = substr($sql, 0, $endPosition + 1);
            return SqlToken::SYMBOL();
        }

        if (preg_match("/^([a-zA-Z_][a-zA-Z0-9_]*)/is", $sql, $match)) {
            $readSql = $match[1];
            return SqlToken::SYMBOL();
        }

        return null;
    }

    public static function readTokens(string $sql): SqlTokens
    {
        return SqlTokensClass::readTokens($sql);
    }

    /** @return array<string, SqlToken> */
    private static function keywords(): array
    {
        if (is_null(self::$keywords)) {
            self::$keywords = [

                '(' => self::BRACKET_OPENING(),
                ')' => self::BRACKET_CLOSING(),
                ',' => self::COMMA(),
                ';' => self::SEMICOLON(),
                '=' => self::EQUALS(),

                'SELECT' => self::SELECT(),
                'FROM' => self::FROM(),
                'LEFT' => self::LEFT(),
                'INNER' => self::INNER(),
                'JOIN' => self::JOIN(),
                'ON' => self::ON(),
                'USING' => self::USING(),
                'WHERE' => self::WHERE(),
                'HAVING' => self::HAVING(),
                'LIMIT' => self::LIMIT(),
                'ORDER_BY' => self::ORDER_BY(),
                'DISTINCT' => self::DISTINCT(),
                'FUNCTION_NAME' => self::FUNCTION_NAME(),
                'UPDATE' => self::UPDATE(),
                'DELETE' => self::DELETE(),
                'ALTER' => self::ALTER(),
                'TABLE' => self::TABLE(),
                'SCHEMA' => self::SCHEMA(),
                'COLUMN' => self::COLUMN(),
                'INDEX' => self::INDEX(),
                'KEY' => self::KEY(),
                'CREATE' => self::CREATE(),
                'DATA_TYPE' => self::DATA_TYPE(),
                'NOT_NULL' => self::NOT_NULL(),
                'PRIMARY_KEY' => self::PRIMARY_KEY(),
                'SET' => self::SET(),
                'SHOW' => self::SHOW(),

            ];
        }

        return self::$keywords;
    }

}
