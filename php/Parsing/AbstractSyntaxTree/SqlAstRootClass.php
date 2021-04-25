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

namespace Addiks\StoredSQL\Parsing\AbstractSyntaxTree;

use Addiks\StoredSQL\Lexing\SqlTokens;
use Webmozart\Assert\Assert;

final class SqlAstRootClass extends SqlAstBranch implements SqlAstRoot
{
    private SqlTokens $tokens;

    private bool $lexingFinished = false;

    /** @param array<SqlAstNode> $children */
    public function __construct(array $children, SqlTokens $tokens)
    {
        parent::__construct($children);

        $this->tokens = $tokens;
    }

    public function addToken(SqlAstTokenNode $token): void
    {
        Assert::false($this->lexingFinished);

        parent::replace(count($this->children()), 1, $token);
    }

    public function markLexingFinished(): void
    {
        $this->lexingFinished = true;
    }

    public function replace(
        int $offset,
        int $length,
        SqlAstNode $newNode
    ): void {
        Assert::true($this->lexingFinished);

        parent::replace($offset, $length, $newNode);
    }

    public function tokens(): SqlTokens
    {
        return $this->tokens;
    }

    public function parent(): ?SqlAstNode
    {
        return null;
    }

    public function root(): SqlAstRoot
    {
        return $this;
    }

    public function line(): int
    {
        return 1;
    }

    public function column(): int
    {
        return 0;
    }

    public function toSql(): string
    {
        /** @var string $sql */
        $sql = "";

        /** @var SqlAstNode $node */
        foreach ($this->children() as $node) {
            $sql .= $node->toSql();
        }

        return $sql;
    }
}
