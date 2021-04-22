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
use Webmozart\Assert\Assert;

final class SqlAstFrom implements SqlAstNode
{
    private SqlAstTokenNode $tableName;

    private ?SqlAstTokenNode $alias;

    public function __construct(SqlAstTokenNode $tableName, ?SqlAstTokenNode $alias)
    {
        $this->tableName = $tableName;
        $this->alias = $alias;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::FROM())) {
            /** @var SqlAstTokenNode $tableName */
            $tableName = $parent[$offset + 1];

            Assert::isInstanceOf($tableName, SqlAstTokenNode::class);
            Assert::true($tableName->is(SqlToken::SYMBOL()));

            /** @var SqlAstNode|null $alias */
            $alias = $parent[$offset + 2];

            if (!($alias instanceof SqlAstTokenNode) || !$alias->is(SqlToken::SYMBOL())) {
                $alias = null;
            }

            $parent->replace($offset, is_object($alias) ? 3 : 2, new SqlAstFrom($tableName, $alias));
        }
    }

    public function children(): array
    {
        /** @var array<SqlAstNode> $children */
        $children = [$this->tableName];

        if (is_object($this->alias)) {
            $children[] = $this->alias;
        }

        return $children;
    }

    public function hash(): string
    {
        /** @var string $hash */
        $hash = $this->tableName->hash();

        if (is_object($this->alias)) {
            $hash = md5($hash . $this->alias->hash());
        }

        return $hash;
    }
}
