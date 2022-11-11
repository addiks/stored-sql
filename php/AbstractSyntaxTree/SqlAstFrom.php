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

use Addiks\StoredSQL\Lexing\SqlToken;
use Addiks\StoredSQL\SqlUtils;
use Webmozart\Assert\Assert;

final class SqlAstFrom implements SqlAstNode
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstTokenNode $fromToken;

    private SqlAstTable $tableName;

    private ?SqlAstTokenNode $alias;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $fromToken,
        SqlAstTable $tableName,
        ?SqlAstTokenNode $alias
    ) {
        $this->parent = $parent;
        $this->fromToken = $fromToken;
        $this->tableName = $tableName;
        $this->alias = $alias;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::FROM())) {
            $tableName = $parent[$offset + 1];

            if ($tableName instanceof SqlAstColumn) {
                $tableName = $tableName->convertToTable();
            }

            if (!$tableName instanceof SqlAstTable) {
                Assert::isInstanceOf($tableName, SqlAstTokenNode::class);
                Assert::true($tableName->is(SqlToken::SYMBOL()));

                $tableName = new SqlAstTable($parent, $tableName, null);
            }

            /** @var SqlAstNode|null $alias */
            $alias = $parent[$offset + 2];

            if (!($alias instanceof SqlAstTokenNode) || !$alias->is(SqlToken::SYMBOL())) {
                $alias = null;
            }

            $parent->replace($offset, is_object($alias) ? 3 : 2, new SqlAstFrom($parent, $node, $tableName, $alias));
        }
    }

    public function children(): array
    {
        return array_filter([
            $this->tableName,
            $this->alias,
        ]);
    }

    public function hash(): string
    {
        return md5(implode('.', array_map(function (SqlAstNode $node) {
            return $node->hash();
        }, $this->children())));
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
        return $this->fromToken->line();
    }

    public function column(): int
    {
        return $this->fromToken->column();
    }

    public function tableName(): string
    {
        return $this->tableName->tableName();
    }

    public function aliasName(): string
    {
        return SqlUtils::unquote($this->alias?->toSql() ?? '');
    }

    public function toSql(): string
    {
        return 'FROM ' . $this->tableName->toSql() . (is_object($this->alias) ? (' ' . $this->alias->toSql()) : '');
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }
}
