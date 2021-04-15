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

namespace Addiks\StoredSQL\Parsing\AbstractSyntaxTree;

use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstNode;
use ArrayIterator;
use Iterator;
use Addiks\StoredSQL\Lexing\SqlTokenInstance;
use ErrorException;
use Addiks\StoredSQL\Lexing\SqlTokens;

final class SqlAstTokenNode implements SqlAstNode
{
    private SqlTokenInstance $token;

    public function __construct(SqlTokenInstance $token)
    {
        $this->token = $token;
    }

    public function token(): SqlTokenInstance
    {
        return $this->token;
    }

    public function children(): array
    {
        return [];
    }

    public function hash(): string
    {
        return sprintf(
            "%d:%d:%s",
            $this->token->line(),
            $this->token->offset(),
            $this->token->token()->name()
        );
    }

    /** @return Iterator<SqlAstNode> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator([]);
    }
}
