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

namespace Addiks\StoredSQL\AbstractSyntaxTree;

use Addiks\StoredSQL\Lexing\AbstractSqlToken;
use Addiks\StoredSQL\Lexing\SqlTokenInstance;

final class SqlAstTokenNode implements SqlAstNode
{
    private SqlAstNode $parent;

    private SqlTokenInstance $token;

    public function __construct(SqlAstNode $parent, SqlTokenInstance $token)
    {
        $this->parent = $parent;
        $this->token = $token;
    }

    public function token(): SqlTokenInstance
    {
        return $this->token;
    }

    public function is(AbstractSqlToken $token): bool
    {
        return $this->token->is($token);
    }

    public function isCode(string $code): bool
    {
        return $this->token->isCode($code);
    }

    public function children(): array
    {
        return [];
    }

    public function hash(): string
    {
        return sprintf(
            '%d:%d:%s',
            $this->token->line(),
            $this->token->offset(),
            $this->token->token()->name()
        );
    }

    public function parent(): ?SqlAstNode
    {
        return $this->parent;
    }

    public function root(): SqlAstRoot
    {
        return $this->parent->root();
    }

    public function line(): int
    {
        return $this->token->line();
    }

    public function column(): int
    {
        return $this->token->offset();
    }

    public function toSql(): string
    {
        return $this->token->code();
    }
}
