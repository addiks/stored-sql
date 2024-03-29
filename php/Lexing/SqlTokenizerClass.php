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

use Addiks\StoredSQL\Exception\UnlexableSqlException;
use Webmozart\Assert\Assert;

final class SqlTokenizerClass implements SqlTokenizer
{
    /** @var array<string, AbstractSqlToken> $keywords */
    private array $keywords;

    /** @param array<string, AbstractSqlToken> $keywords */
    public function __construct(array $keywords)
    {
        array_map(function ($keyword) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isInstanceOf($keyword, AbstractSqlToken::class);
        }, $keywords);

        $this->keywords = $keywords;
    }

    public static function defaultTokenizer(): SqlTokenizerClass
    {
        return new SqlTokenizerClass(self::defaultKeywords());
    }

    public function tokenize(string $sql): SqlTokens
    {
        /** @var array<int, SqlTokenInstance> $tokens */
        $tokens = array();

        /** @var string $originalSql */
        $originalSql = $sql;

        /** @var int $line */
        $line = 0;

        /** @var int $offset */
        $offset = 0;

        while (strlen($sql) > 0) {
            /** @var string $tokenSql */
            $tokenSql = '';

            /** @var AbstractSqlToken|null $token */
            $token = $this->readToken($sql, $tokenSql);

            if (is_null($token)) {
                throw new UnlexableSqlException($originalSql, $line, $offset, $tokens);
            }

            if ($token === SqlToken::OPERATOR() && $tokenSql === '*' && !empty($tokens)) {
                if (!in_array(end($tokens)->token(), [SqlToken::SYMBOL(), SqlToken::NUMERIC()], true)) {
                    # This is not a multiplication ("SELECT 3 * 3 as nine"),
                    # this is a all-columns-selector ("SELECT foo.* FROM foo JOIN bar")
                    $token = SqlToken::STAR();
                }
            }

            $tokens[] = new SqlTokenInstanceClass($tokenSql, $token, $line, $offset);

            $sql = substr($sql, strlen($tokenSql));

            /** @var int $newLines */
            $newLines = substr_count($tokenSql, "\n");

            $line += $newLines;

            if ($newLines > 0) {
                $offset = strlen($tokenSql) - ((int) strrpos($tokenSql, "\n") + 1);

            } else {
                $offset += strlen($tokenSql);
            }
        }

        return new SqlTokensClass($tokens, $originalSql);
    }

    /** @return array<string, AbstractSqlToken> */
    public static function defaultKeywords(): array
    {
        return [
            '(' => SqlToken::BRACKET_OPENING(),
            ')' => SqlToken::BRACKET_CLOSING(),
            '.' => SqlToken::DOT(),
            ',' => SqlToken::COMMA(),
            ';' => SqlToken::SEMICOLON(),

            '<=' => SqlToken::OPERATOR(),
            '>=' => SqlToken::OPERATOR(),
            '!=' => SqlToken::OPERATOR(),
            '=' => SqlToken::OPERATOR(),
            '<' => SqlToken::OPERATOR(),
            '>' => SqlToken::OPERATOR(),

            '+' => SqlToken::OPERATOR(),
            '-' => SqlToken::OPERATOR(),
            '/' => SqlToken::OPERATOR(),
            '*' => SqlToken::OPERATOR(),

            'SELECT' => SqlToken::SELECT(),
            'FROM' => SqlToken::FROM(),
            'LEFT' => SqlToken::LEFT(),
            'RIGHT' => SqlToken::RIGHT(),
            'INNER' => SqlToken::INNER(),
            'OUTER' => SqlToken::OUTER(),
            'CROSS' => SqlToken::CROSS(),
            'JOIN' => SqlToken::JOIN(),
            'ON' => SqlToken::ON(),
            'IN' => SqlToken::IN(),
            'IS' => SqlToken::IS(),
            'AND' => SqlToken::AND(),
            'ORDER' => SqlToken::ORDER(),
            'BY' => SqlToken::BY(),
            'ASC' => SqlToken::ASC(),
            'AS' => SqlToken::AS(),
            'DESC' => SqlToken::DESC(),
            'OR' => SqlToken::OR(),
            'LIKE' => SqlToken::LIKE(),
            'USING' => SqlToken::USING(),
            'WHERE' => SqlToken::WHERE(),
            'GROUP' => SqlToken::GROUP(),
            'HAVING' => SqlToken::HAVING(),
            'LIMIT' => SqlToken::LIMIT(),
            'DISTINCT' => SqlToken::DISTINCT(),
            'FUNCTION_NAME' => SqlToken::FUNCTION_NAME(),
            'UPDATE' => SqlToken::UPDATE(),
            'DELETE' => SqlToken::DELETE(),
            'ALTER' => SqlToken::ALTER(),
            'TABLE' => SqlToken::TABLE(),
            'SCHEMA' => SqlToken::SCHEMA(),
            'COLUMN' => SqlToken::COLUMN(),
            'INDEX' => SqlToken::INDEX(),
            'KEY' => SqlToken::KEY(),
            'CREATE' => SqlToken::CREATE(),
            'DATA_TYPE' => SqlToken::DATA_TYPE(),
            'NOT' => SqlToken::NOT(),
            'NULL' => SqlToken::T_NULL(),
            'PRIMARY_KEY' => SqlToken::PRIMARY_KEY(),
            'SET' => SqlToken::SET(),
            'SHOW' => SqlToken::SHOW(),

        ];
    }

    private function readToken(string $sql, string &$readSql = ''): ?AbstractSqlToken
    {
        if (preg_match('/^(\s+)/is', $sql, $match)) {
            $readSql = $match[1];

            return SqlToken::SPACE();
        }

        if ($sql[0] === '#' || ($sql[0] === '-' && $sql[1] === '-') || ($sql[0] === '/' && $sql[1] === '*')) {
            /** @var int|bool $newLinePosition */
            $newLinePosition = strpos($sql, "\n");

            /** @var int $commentEndPosition */
            $commentEndPosition = PHP_INT_MAX;

            if ($sql[0] === '-' || $sql[0] === '/') {
                $commentEndPosition = strpos($sql, ($sql[0] === '-' ? '--' : '*/'), 2);

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

        /** @var AbstractSqlToken $token */
        foreach ($this->keywords as $keyword => $token) {
            if (strcasecmp($keyword, substr($sql, 0, strlen($keyword))) === 0) {
                if (!$this->isSymbolCharacter($keyword[-1]) || !$this->isSymbolCharacter($sql[strlen($keyword)] ?? '')) {
                    $readSql = substr($sql, 0, strlen($keyword));

                    return $token;
                }
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

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*/is', $sql, $match)) {
            $readSql = $match[0];

            return SqlToken::SYMBOL();
        }

        if (preg_match('/^(\?|\:[a-zA-Z_][a-zA-Z0-9_]*)/is', $sql, $match)) {
            $readSql = $match[0];

            return SqlToken::PREPARED_ARGUMENT();
        }

        return null;
    }

    private function isSymbolCharacter(string $char): bool
    {
        if (empty($char)) {
            return false;
        }

        if (in_array($char, ['_'], true)) {
            return true;
        }

        /** @var int $dec */
        $dec = ord($char);

        if ($dec < 48) {
            return false;
        }

        if ($dec < 58) {
            return true; # 0 - 9
        }

        if ($dec < 65) {
            return false;
        }

        if ($dec < 91) {
            return true; # A - Z
        }

        if ($dec < 97) {
            return false;
        }

        if ($dec < 123) {
            return true; # a - z

        } else {
            return false;
        }
    }
}
