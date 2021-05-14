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

final class SqlAstColumn implements SqlAstExpression
{
    private SqlAstNode $parent;

    private SqlAstTokenNode $column;

    private ?SqlAstTokenNode $table;

    private ?SqlAstTokenNode $database;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $column,
        ?SqlAstTokenNode $table,
        ?SqlAstTokenNode $database
    ) {
        $this->parent = $parent;
        $this->column = $column;
        $this->table = $table;
        $this->database = $database;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->token()->is(SqlToken::SYMBOL())) {

            /** @var int $length */
            $length = 1;

            /** @var SqlAstTokenNode $column */
            $column = $node;

            /** @var SqlAstTokenNode|null $table */
            $table = null;

            /** @var SqlAstTokenNode|null $database */
            $database = null;

            /** @var SqlAstNode $dot */
            $dot = $parent[$offset + 1];

            if ($dot instanceof SqlAstTokenNode && $dot->is(SqlToken::DOT())) {
                $node = $parent[$offset + 2];
                Assert::isInstanceOf($node, SqlAstTokenNode::class);

                if ($node->is(SqlToken::SYMBOL())) {
                    $length += 2;

                    $table = $column;
                    $column = $node;

                    $dot = $parent[$offset + 3];

                    if ($dot instanceof SqlAstTokenNode && $dot->is(SqlToken::DOT())) {
                        $node = $parent[$offset + 4];
                        Assert::isInstanceOf($node, SqlAstTokenNode::class);

                        if ($node->is(SqlToken::SYMBOL())) {
                            $length += 2;

                            $database = $table;
                            $table = $column;
                            $column = $node;
                        }
                    }
                }

                $parent->replace($offset, $length, new SqlAstColumn(
                    $parent,
                    $column,
                    $table,
                    $database
                ));
            }
        }
    }

    public function children(): array
    {
        return array_filter([
            $this->database,
            $this->table,
            $this->column,
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
        return $this->column->line();
    }

    public function column(): int
    {
        return $this->column->column();
    }

    public function toSql(): string
    {
        return implode('.', array_map(function (SqlAstNode $node) {
            return $node->toSql();
        }, $this->children()));
    }
}
