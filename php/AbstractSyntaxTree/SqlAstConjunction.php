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

final class SqlAstConjunction implements SqlAstExpression
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    /** @var array<array{0:SqlAstTokenNode|null, 1:SqlAstExpression}> */
    private array $parts;

    /** @param array<array{0:SqlAstTokenNode|null, 1:SqlAstExpression}> $parts */
    public function __construct(
        SqlAstNode $parent,
        array $parts
    ) {
        $this->parent = $parent;
        $this->parts = array();

        Assert::minCount($parts, 2);

        foreach ($parts as $index => [$operator, $expression]) {
            if ($index > 0) {
                /** @psalm-suppress RedundantConditionGivenDocblockType */
                Assert::isInstanceOf($operator, SqlAstTokenNode::class);

            } else {
                Assert::null($operator);
            }

            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isInstanceOf($expression, SqlAstExpression::class);

            $this->parts[] = [$operator, $expression];
        }
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        /** @var int $originalOffset */
        $originalOffset = $offset;

        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::SYMBOL())) {
            $node = new SqlAstColumn($parent, $node, null, null);
        }

        /** @var array<array{0:SqlAstTokenNode, 1:SqlAstExpression}> $parts */
        $parts = array([null, $node]);

        while ($node instanceof SqlAstExpression) {
            $node = null;

            /** @var SqlAstNode $nextNode */
            $nextNode = $parent[$offset + 1];

            if ($nextNode instanceof SqlAstTokenNode) {
                /** @var bool $isConjunction */
                $isConjunction = max(
                    $nextNode->is(SqlToken::AND()),
                    $nextNode->is(SqlToken::OR())
                );

                if ($isConjunction) {
                    /** @var SqlAstNode $otherNode */
                    $otherNode = $parent[$offset + 2];

                    if ($otherNode instanceof SqlAstTokenNode && $otherNode->is(SqlToken::SYMBOL())) {
                        $otherNode = new SqlAstColumn($parent, $otherNode, null, null);
                    }

                    if ($otherNode instanceof SqlAstExpression) {
                        $parts[] = [$nextNode, $otherNode];
                        $offset += 2;
                        $node = $otherNode;
                    }
                }
            }
        }

        if (count($parts) > 1) {
            $parent->replace($originalOffset, $offset - $originalOffset + 1, new SqlAstConjunction($parent, $parts));
        }
    }

    public function children(): array
    {
        /** @var array<int, SqlAstNode|null> $children */
        $children = array();

        foreach ($this->parts as [$operator, $expression]) {
            $children[] = $operator;
            $children[] = $expression;
        }

        return array_filter($children);
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
        return array_values($this->children())[0]->line();
    }

    public function column(): int
    {
        return array_values($this->children())[0]->column();
    }

    public function toSql(): string
    {
        /** @var string $sql */
        $sql = '';

        /**
         * @var SqlAstTokenNode|null $operator
         * @var SqlAstExpression     $expression
         */
        foreach ($this->parts as [$operator, $expression]) {
            if (is_object($operator)) {
                $sql .= ' ' . $operator->toSql();
            }

            $sql .= ' ' . $expression->toSql();
        }

        return trim($sql);
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }

    public function extractFundamentalEquations(): array
    {
        /** @var array<SqlAstOperation> $fundamentalEquations */
        $fundamentalEquations = array();

        /** @var bool $hasOnlyAndOperations */
        $hasOnlyAndOperations = true;

        /** @var SqlAstTokenNode $operator */
        foreach (array_filter(array_column($this->parts, 0)) as $operator) {
            if (!$operator->is(SqlToken::AND())) {
                $hasOnlyAndOperations = false;
            }
        }

        if ($hasOnlyAndOperations) {
            /** @var SqlAstExpression $expression */
            foreach (array_column($this->parts, 1) as $expression) {
                $fundamentalEquations = array_merge(
                    $fundamentalEquations,
                    $expression->extractFundamentalEquations(
                    )
                );
            }
        }

        return $fundamentalEquations;
    }
}
