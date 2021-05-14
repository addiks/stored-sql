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
use Webmozart\Assert\Assert;

final class SqlAstJoin implements SqlAstNode
{
    private SqlAstNode $parent;

    private SqlAstTokenNode $joinToken;

    private SqlAstTokenNode $tableName;

    private ?SqlAstTokenNode $joinType;

    private ?SqlAstTokenNode $alias;

    private ?SqlAstTokenNode $onOrUsing;

    private ?SqlAstExpression $condition;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $joinToken,
        SqlAstTokenNode $tableName,
        ?SqlAstTokenNode $joinType,
        ?SqlAstTokenNode $alias,
        ?SqlAstTokenNode $onOrUsing,
        ?SqlAstExpression $condition
    ) {
        $this->parent = $parent;
        $this->joinToken = $joinToken;
        $this->joinType = $joinType;
        $this->tableName = $tableName;
        $this->alias = $alias;
        $this->onOrUsing = $onOrUsing;
        $this->condition = $condition;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::JOIN())) {
            /** @var SqlAstNode|null $joinType */
            $joinType = $parent[$offset - 1];

            /** @var SqlAstTokenNode $tableName */
            $tableName = $parent[$offset + 1];

            /** @var SqlAstNode|null $alias */
            $alias = $parent[$offset + 2];

            if (!($tableName instanceof SqlAstTokenNode && $tableName->is(SqlToken::SYMBOL()))) {
                $tableName = null;
            }

            if (!($alias instanceof SqlAstTokenNode && $alias->is(SqlToken::SYMBOL()))) {
                $alias = null;
            }

            /** @var int $beginOffset */
            $beginOffset = is_object($joinType) ? $offset - 1 : $offset;

            /** @var int $endOffset */
            $endOffset = is_object($alias) ? $offset + 2 : $offset + 1;

            /** @var SqlAstNode|null $onOrUsing */
            $onOrUsing = $parent[$endOffset + 1];

            /** @var SqlAstExpression|null $condition */
            $condition = null;

            if ($onOrUsing instanceof SqlAstTokenNode) {
                if ($onOrUsing->is(SqlToken::ON()) || $onOrUsing->is(SqlToken::USING())) {
                    $condition = $parent[$endOffset + 2];
                    $endOffset += 2;

                    Assert::isInstanceOf($condition, SqlAstExpression::class);
                }
            }

            $parent->replace($beginOffset, 1 + $endOffset - $beginOffset, new SqlAstJoin(
                $parent,
                $node,
                $tableName,
                $joinType,
                $alias,
                $onOrUsing,
                $condition
            ));
        }
    }

    public function children(): array
    {
        return array_filter([
            $this->joinType,
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
        return $this->joinToken->line();
    }

    public function column(): int
    {
        return $this->joinToken->column();
    }

    public function toSql(): string
    {
        /** @var string $sql */
        $sql = $this->joinType->toSql() . " JOIN " . $this->tableName->toSql();

        if (is_object($this->alias)) {
            $sql .= " " . $this->alias->toSql();
        }

        if (is_object($this->condition)) {
            $sql .= " " . $this->onOrUsing->toSql() . " " . $this->condition->toSql() . "";
        }

        return $sql;
    }

}
