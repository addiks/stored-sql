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

use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstRoot;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstRootClass;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstTokenNode;
use ArrayIterator;
use ErrorException;
use Iterator;
use Webmozart\Assert\Assert;

final class SqlTokensClass implements SqlTokens
{
    /** @var array<int, SqlTokenInstance> */
    private array $tokens = array();

    private string $originalSql;

    /** @param array<int, SqlTokenInstance> $tokens */
    public function __construct(array $tokens, string $originalSql)
    {
        $this->originalSql = $originalSql;

        foreach ($tokens as $token) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isInstanceOf($token, SqlTokenInstance::class);

            $this->tokens[] = $token;
        }
    }

    public function convertToSyntaxTree(): SqlAstRoot
    {
        /** @var array<SqlAstTokenNode> $tokenNodes */
        $tokenNodes = array_map(function (SqlTokenInstance $token) {
            return new SqlAstTokenNode($token);
        }, $this->tokens);

        return new SqlAstRootClass($tokenNodes, $this);
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

    /** @return Iterator<SqlTokenInstance> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->tokens);
    }

    /** @param array-key $offset */
    public function offsetGet($offset): ?SqlTokenInstance
    {
        Assert::numeric($offset);

        return $this->tokens[(int) $offset] ?? null;
    }

    /** @param array-key $offset */
    public function offsetExists($offset): bool
    {
        Assert::numeric($offset);

        return isset($this->tokens[(int) $offset]);
    }

    /**
     * @param array-key        $offset
     * @param SqlTokenInstance $value
     */
    public function offsetSet($offset, $value): void
    {
        throw new ErrorException(sprintf('Objects of %s are immutable!', __CLASS__));
    }

    /** @param array-key $offset */
    public function offsetUnset($offset): void
    {
        throw new ErrorException(sprintf('Objects of %s are immutable!', __CLASS__));
    }
}
