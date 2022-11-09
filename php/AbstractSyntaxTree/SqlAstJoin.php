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

use Addiks\StoredSQL\ExecutionContext;
use Addiks\StoredSQL\Lexing\SqlToken;
use Addiks\StoredSQL\Schema\Column;
use Addiks\StoredSQL\Schema\Table;
use Addiks\StoredSQL\SqlUtils;
use Webmozart\Assert\Assert;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstTable;
use Addiks\StoredSQL\Exception\UnparsableSqlException;

final class SqlAstJoin implements SqlAstNode
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstTokenNode $joinToken;

    private SqlAstTable $tableName;

    private bool $isLeftOuterJoin;

    private bool $isRightOuterJoin;

    private ?SqlAstTokenNode $alias;

    private ?SqlAstTokenNode $onOrUsing;

    private ?SqlAstExpression $condition;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $joinToken,
        SqlAstTable $tableName,
        bool $isLeftOuterJoin,
        bool $isRightOuterJoin,
        ?SqlAstTokenNode $alias,
        ?SqlAstTokenNode $onOrUsing,
        ?SqlAstExpression $condition
    ) {
        $this->parent = $parent;
        $this->joinToken = $joinToken;
        $this->isLeftOuterJoin = $isLeftOuterJoin;
        $this->isRightOuterJoin = $isRightOuterJoin;
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
            /** @var int $beginOffset */
            $beginOffset = $offset;

            /** @var bool $isLeftOuterJoin */
            $isLeftOuterJoin = false;

            /** @var bool $isRightOuterJoin */
            $isRightOuterJoin = false;

            /** @var SqlAstNode|null $joinType */
            $joinType = $parent[$offset - 1];

            if ($joinType instanceof SqlAstTokenNode) {
                if ($joinType->is(SqlToken::OUTER())) {
                    /** @var SqlAstNode|null $joinType */
                    $joinType = $parent[$offset - 2];
                    $beginOffset--;
                }

                if ($joinType instanceof SqlAstTokenNode) {
                    if ($joinType->is(SqlToken::LEFT())) {
                        $isLeftOuterJoin = true;
                        $beginOffset--;

                    } elseif ($joinType->is(SqlToken::RIGHT())) {
                        $isRightOuterJoin = true;
                        $beginOffset--;

                    } elseif ($joinType->is(SqlToken::FULL())) {
                        $isLeftOuterJoin = true;
                        $isRightOuterJoin = true;
                        $beginOffset--;

                    } elseif ($joinType->is(SqlToken::INNER())) {
                        $beginOffset--;
                    }
                }
            }

            /** @var SqlAstTokenNode|null $tableName */
            $tableName = $parent[$offset + 1];
            
            if ($tableName instanceof SqlAstTokenNode && $tableName->is(SqlToken::SYMBOL())) {
                SqlAstColumn::mutateAstNode($tableName, $offset + 1, $parent);
                $tableName = $parent[$offset + 1];
            }

            if ($tableName instanceof SqlAstColumn) {
                $tableName = $tableName->convertToTable();
            }
            
            if ($tableName instanceof SqlAstTokenNode && $tableName->is(SqlToken::SYMBOL())) {
                $parent->replaceNode($tableName, new SqlAstTable($parent, $tableName, null));
                $tableName = $parent[$offset + 1];
            }

            UnparsableSqlException::assertType($parent, $offset + 1, SqlAstTable::class);
            Assert::isInstanceOf($tableName, SqlAstTable::class);

            /** @var SqlAstNode|null $alias */
            $alias = $parent[$offset + 2];

            if (!($alias instanceof SqlAstTokenNode && $alias->is(SqlToken::SYMBOL()))) {
                $alias = null;
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

                    if (!$condition instanceof SqlAstExpression) {
                        return;
                    }
                }
                
            } else {
                $onOrUsing = null;
            }

            $parent->replace($beginOffset, 1 + $endOffset - $beginOffset, new SqlAstJoin(
                $parent,
                $node,
                $tableName,
                $isLeftOuterJoin,
                $isRightOuterJoin,
                $alias,
                $onOrUsing,
                $condition
            ));
        }
    }

    public function children(): array
    {
        return array_filter([
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

    public function joinedTable(): SqlAstTable
    {
        return $this->tableName;
    }

    public function joinedTableName(): string
    {
        return $this->tableName->tableName();
    }

    public function isLeftOuterJoin(): bool
    {
        return $this->isLeftOuterJoin;
    }

    public function isRightOuterJoin(): bool
    {
        return $this->isRightOuterJoin;
    }

    public function isFullOuterJoin(): bool
    {
        return $this->isLeftOuterJoin && $this->isRightOuterJoin;
    }

    public function isInnerJoin(): bool
    {
        return !$this->isLeftOuterJoin && !$this->isRightOuterJoin;
    }

    public function isOuterJoin(): bool
    {
        return $this->isLeftOuterJoin || $this->isRightOuterJoin;
    }

    public function alias(): ?SqlAstTokenNode
    {
        return $this->alias;
    }

    public function aliasName(): string
    {
        return SqlUtils::unquote($this->alias?->toSql() ?? $this->joinedTableName());
    }

    public function schemaName(): ?string
    {
        return null; # TODO: add support for JOIN from other schema
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

        if ($this->isFullOuterJoin()) {
            $sql = 'FULL ' . $sql;

        } elseif ($this->isLeftOuterJoin()) {
            $sql = 'LEFT ' . $sql;

        } elseif ($this->isRightOuterJoin()) {
            $sql = 'RIGHT ' . $sql;
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

    public function canChangeResultSetSize(ExecutionContext $context): bool
    {
        /** @var SqlAstExpression|null $condition */
        $condition = $this->condition();

        if ($this->isUsingColumnCondition()) {
            # "... JOIN foo USING(bar_id)"

            if ($condition instanceof SqlAstColumn) {
                return $this->canUsingJoinChangeResultSetSize($context);
            }

        } elseif (is_object($condition)) {
            # "... JOIN foo ON(foo.id = bar.foo_id)"

            return $this->canOnJoinChangeResultSetSize($context);
        }

        return true;
    }

    private function canUsingJoinChangeResultSetSize(ExecutionContext $context): bool
    {
        /** @var SqlAstExpression|null $column */
        $column = $this->condition();

        Assert::isInstanceOf($column, SqlAstColumn::class);

        /** @var string $columnName */
        $columnName = $column->columnNameString();

        /** @var Table|null $joiningTable */
        $joiningTable = $context->findTableWithColumn($columnName);

        if (is_null($joiningTable)) {
            return true;
        }

        return !$context->isOneToOneRelation(
            $joiningTable->name(),
            $columnName,
            $this->joinedTableName(),
            $columnName
        );
    }

    private function canOnJoinChangeResultSetSize(ExecutionContext $context): bool
    {
        /** @var SqlAstExpression|null $condition */
        $condition = $this->condition();

        if (is_null($condition)) {
            return true;
        }

        /** @var array<SqlAstOperation> $equations */
        $equations = $condition->extractFundamentalEquations();

        /** @var array<SqlAstOperation> $alwaysFalseEquations */
        $alwaysFalseEquations = array_filter($equations, function (SqlAstOperation $equation): bool {
            return $equation->isAlwaysFalse();
        });

        if (!empty($alwaysFalseEquations)) {
            return true;
        }

        $equations = array_filter($equations, function (SqlAstOperation $equation): bool {
            return !$equation->isAlwaysTrue();
        });

        /** @var string $joinAlias */
        $joinAlias = $this->aliasName();

        foreach ($equations as $equation) {
            /** @var SqlAstExpression $leftSide */
            $leftSide = $equation->leftSide();

            /** @var SqlAstExpression $rightSide */
            $rightSide = $equation->rightSide();

            if ($leftSide instanceof SqlAstColumn && $leftSide->tableNameString() === $joinAlias) {
                /** @var SqlAstExpression $joiningSide */
                $joiningSide = $rightSide;

                /** @var SqlAstExpression $joinedSide */
                $joinedSide = $leftSide;

            } elseif ($rightSide instanceof SqlAstColumn && $rightSide->tableNameString() === $joinAlias) {
                /** @var SqlAstExpression $joiningSide */
                $joiningSide = $leftSide;

                /** @var SqlAstExpression $joinedSide */
                $joinedSide = $rightSide;

            } else {
                # Unknown condition, let's assume that this JOIN can change result size to be safe.
                return true;
            }

            if ($joinedSide instanceof SqlAstColumn && $joiningSide instanceof SqlAstColumn) {
                /** @var Column|null $joinedColumn */
                $joinedColumn = $context->columnByNode($joinedSide);

                /** @var Column|null $joiningColumn */
                $joiningColumn = $context->columnByNode($joiningSide);

                if (is_object($joinedColumn) && is_object($joiningColumn)) {
                    foreach ([
                        [$this->isRightOuterJoin(), $joiningColumn],
                        [$this->isLeftOuterJoin(), $joinedColumn],
                    ] as [$isOuterJoin, $column]) {
                        if ($isOuterJoin) {
                            if ($column->nullable() || !$column->unique()) {
                                return true;
                            }

                        } else {
                            if (!$column->unique()) {
                                return true;
                            }
                        }
                    }

                    return false;
                }

            } else {
                # Either a literal (which will change result size), or an unknown condition (which might change it).
                return true;
            }
        }

        # All equations are either always true or always false. Either way, this JOIN changes the result size.
        return true;
    }
}
