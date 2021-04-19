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

use Addiks\StoredSQL\Lexing\SqlToken;
use ArrayIterator;
use Iterator;
use Webmozart\Assert\Assert;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstTokenNode;
use Addiks\StoredSQL\Lexing\SqlTokenInstance;

final class SqlAstLiteral implements SqlAstExpression
{
    private SqlAstTokenNode $literal;

    public function __construct(
        SqlAstTokenNode $literal
    ) {
        $this->literal = $literal;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode) {
            /** @var SqlTokenInstance $token */
            $token = $node->token();

            /** @var bool $isSomeLiteralNode */
            $isSomeLiteralNode = max(
                $token->is(SqlToken::LITERAL()),
                $token->is(SqlToken::NUMERIC()),
                $token->is(SqlToken::T_NULL()),
            );

            if ($isSomeLiteralNode) {
                $parent->replace($offset, 1, new SqlAstLiteral($node));
            }
        }
    }

    public function children(): array
    {
        return [$this->literal];
    }

    public function hash(): string
    {
        return $this->literal->hash();
    }

    /** @return Iterator<SqlAstNode> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->children());
    }
}