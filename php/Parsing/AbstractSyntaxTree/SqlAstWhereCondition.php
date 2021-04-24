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

final class SqlAstWhereCondition implements SqlAstNode
{
    private SqlAstNode $parent;

    private SqlAstTokenNode $whereToken;

    private SqlAstExpression $expression;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $whereToken,
        SqlAstExpression $expression
    ) {
        $this->parent = $parent;
        $this->whereToken = $whereToken;
        $this->expression = $expression;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::WHERE())) {
            /** @var SqlAstExpression $expression */
            $expression = $parent[$offset + 1];

            Assert::isInstanceOf($expression, SqlAstExpression::class);

            $parent->replace($offset, 2, new SqlAstWhereCondition($parent, $node, $expression));
        }
    }

    public function children(): array
    {
        return [$this->expression];
    }

    public function hash(): string
    {
        return $this->expression->hash();
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
        return $this->whereToken->line();
    }

    public function column(): int
    {
        return $this->whereToken->column();
    }
}