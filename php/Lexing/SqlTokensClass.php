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

use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\Lexing\SqlToken;
use Webmozart\Assert\Assert;
use ErrorException;
use ArrayIterator;
use Iterator;
use Addiks\StoredSQL\Lexing\SqlTokenInstance;
use Addiks\StoredSQL\Lexing\SqlTokenInstanceClass;
use Addiks\StoredSQL\Exception\UnlexableSqlException;

final class SqlTokensClass implements SqlTokens
{

    /** @var array<int, SqlTokenInstance> */
    private array $tokens = array();

    private string $originalSql;

    public function __construct(array $tokens, string $originalSql)
    {
        $this->originalSql = $originalSql;

        foreach ($tokens as $token) {
            Assert::isInstanceOf($token, SqlTokenInstance::class);

            $this->tokens[] = $token;
        }
    }

    public static function readTokens(string $sql): SqlTokens
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
            $tokenSql = "";

            /** @var SqlToken|null $token */
            $token = SqlToken::readToken($sql, $tokenSql);

            if (is_null($token)) {
                throw new UnlexableSqlException($originalSql, $line, $offset);
            }

            $tokens[] = new SqlTokenInstanceClass($tokenSql, $token, $line, $offset);

            $sql = substr($sql, strlen($tokenSql));

            /** @var int $newLines */
            $newLines = substr_count($tokenSql, "\n");

            $line += $newLines;

            if ($newLines > 0) {
                $offset = strlen($tokenSql) - (strrpos($tokenSql, "\n") + 1);

            } else {
                $offset += strlen($tokenSql);
            }
        }

        return new SqlTokensClass($tokens, $originalSql);
    }

    public function withoutWhitespace(): SqlTokens
    {
        return new self(array_filter($this->tokens, function (SqlTokenInstance $token) {
            return !$token->is(SqlToken::SPACE());
        }), $this->originalSql);
    }

    public function withoutComments(): SqlTokens
    {
        return new self(array_filter($this->tokens, function (SqlTokenInstance $token) {
            return !$token->is(SqlToken::COMMENT());
        }), $this->originalSql);
    }

    public function sql(): string
    {
        return $this->originalSql;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->tokens);
    }

    public function offsetGet($offset): ?SqlTokenInstance
    {
        Assert::numeric($offset);

        return $this->tokens[(int)$offset] ?? null;
    }

    public function offsetExists($offset): bool
    {
        Assert::numeric($offset);

        return isset($this->tokens[(int)$offset]);
    }

    public function offsetSet($offset, $value): void
    {
        throw new ErrorException(sprintf("Objects of %s are immutable!", __CLASS__));
    }

    public function offsetUnset($offset): void
    {
        throw new ErrorException(sprintf("Objects of %s are immutable!", __CLASS__));
    }

}
