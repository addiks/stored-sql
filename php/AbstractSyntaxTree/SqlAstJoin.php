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

final class SqlAstJoin implements SqlAstNode
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstTokenNode $joinToken;

    private SqlAstTokenNode $tableName;

    private ?SqlAstTokenNode $innerOuterJoinType;

    private ?SqlAstTokenNode $leftRightJoinType;

    private ?SqlAstTokenNode $alias;

    private ?SqlAstTokenNode $onOrUsing;

    private ?SqlAstExpression $condition;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $joinToken,
        SqlAstTokenNode $tableName,
        ?SqlAstTokenNode $innerOuterJoinType,
        ?SqlAstTokenNode $leftRightJoinType,
        ?SqlAstTokenNode $alias,
        ?SqlAstTokenNode $onOrUsing,
        ?SqlAstExpression $condition
    ) {
        $this->parent = $parent;
        $this->joinToken = $joinToken;
        $this->innerOuterJoinType = $innerOuterJoinType;
        $this->leftRightJoinType = $leftRightJoinType;
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
            /** @var SqlAstTokenNode|null $innerOuterJoinType */
            $innerOuterJoinType = null;

            /** @var SqlAstTokenNode|null $leftRightJoinType */
            $leftRightJoinType = null;

            if ($parent[$offset - 1] instanceof SqlAstTokenNode) {
                /** @var SqlAstTokenNode $joinType */
                $joinType = $parent[$offset - 1];

                if ($joinType->is(SqlToken::LEFT()) || $joinType->is(SqlToken::RIGHT())) {
                    $leftRightJoinType = $joinType;

                } elseif ($joinType->is(SqlToken::INNER()) || $joinType->is(SqlToken::OUTER())) {
                    $innerOuterJoinType = $joinType;
                }

                if ($parent[$offset - 2] instanceof SqlAstTokenNode) {
                    /** @var SqlAstTokenNode $joinType */
                    $joinType = $parent[$offset - 2];

                    if ($joinType->is(SqlToken::LEFT()) || $joinType->is(SqlToken::RIGHT())) {
                        $leftRightJoinType = $joinType;

                    } elseif ($joinType->is(SqlToken::INNER()) || $joinType->is(SqlToken::OUTER())) {
                        $innerOuterJoinType = $joinType;
                    }
                }
            }

            /** @var SqlAstTokenNode $tableName */
            $tableName = $parent[$offset + 1];

            /** @var SqlAstNode|null $alias */
            $alias = $parent[$offset + 2];

            if (!($alias instanceof SqlAstTokenNode && $alias->is(SqlToken::SYMBOL()))) {
                $alias = null;
            }

            /** @var int $beginOffset */
            $beginOffset = $offset;

            if (is_object($innerOuterJoinType)) {
                $beginOffset--;
            }

            if (is_object($leftRightJoinType)) {
                $beginOffset--;
            }

            /** @var int $endOffset */
            $endOffset = is_object($alias) ? $offset + 2 : $offset + 1;

            /** @var SqlAstTokenNode|null $onOrUsing */
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
                $innerOuterJoinType,
                $leftRightJoinType,
                $alias,
                $onOrUsing,
                $condition
            ));
        }
    }

    public function children(): array
    {
        return array_filter([
            $this->innerOuterJoinType,
            $this->leftRightJoinType,
            $this->tableName,
            $this->alias,
            $this->onOrUsing,
            $this->condition,
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

    public function joinedTable(): SqlAstTokenNode
    {
        return $this->tableName;
    }

    public function joinedTableName(): string
    {
        return SqlUtils::unquote($this->tableName->toSql());
    }

    public function innerOuterJoinType(): ?SqlAstTokenNode
    {
        return $this->innerOuterJoinType;
    }

    public function isOuterJoin(): bool
    {
        return is_object($this->innerOuterJoinType) && $this->innerOuterJoinType->is(SqlToken::OUTER());
    }

    public function leftRightJoinType(): ?SqlAstTokenNode
    {
        return $this->leftRightJoinType;
    }

    public function alias(): ?SqlAstTokenNode
    {
        return $this->alias;
    }

    public function aliasName(): string
    {
        return SqlUtils::unquote($this->alias?->toSql() ?? $this->joinedTableName());
    }

    public function condition(): ?SqlAstExpression
    {
        return $this->condition;
    }

    /**
     * Is this a "... JOIN foo ON(a = b)" or "... JOIN foo USING(a)" join?
     * This returns true for the "USING" version.
     */
    public function isUsingColumnCondition(): bool
    {
        return $this->onOrUsing?->is(SqlToken::USING()) ?? false;
    }

    public function toSql(): string
    {
        /** @var string $sql */
        $sql = 'JOIN ' . $this->tableName->toSql();

        if (is_object($this->innerOuterJoinType)) {
            $sql = $this->innerOuterJoinType->toSql() . ' ' . $sql;
        }

        if (is_object($this->leftRightJoinType)) {
            $sql = $this->leftRightJoinType->toSql() . ' ' . $sql;
        }

        if (is_object($this->alias)) {
            $sql .= ' ' . $this->alias->toSql();
        }

        if (is_object($this->condition) && is_object($this->onOrUsing)) {
            $sql .= ' ' . $this->onOrUsing->toSql() . ' ' . $this->condition->toSql() . '';
        }

        return $sql;
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }
}
