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

use Addiks\StoredSQL\Exception\UnparsableSqlException;
use Addiks\StoredSQL\Lexing\SqlToken;
use Addiks\StoredSQL\SqlUtils;
use Webmozart\Assert\Assert;

final class SqlAstAllColumnsSelector implements SqlAstNode
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstTokenNode $star;

    private ?SqlAstTokenNode $table;

    private ?SqlAstTokenNode $database;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $star,
        ?SqlAstTokenNode $table,
        ?SqlAstTokenNode $database
    ) {
        /** @var int $offset */
        $offset = (int) array_search($star, $parent->children(), true);

        UnparsableSqlException::assertToken($parent, $offset, SqlToken::STAR());

        if (is_object($table)) {
            $offset = (int) array_search($table, $parent->children(), true);
            UnparsableSqlException::assertToken($parent, $offset, SqlToken::SYMBOL());
        }

        if (is_object($database)) {
            $offset = (int) array_search($table, $database->children(), true);
            UnparsableSqlException::assertToken($parent, $offset, SqlToken::SYMBOL());
        }

        $this->parent = $parent;
        $this->star = $star;
        $this->table = $table;
        $this->database = $database;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->token()->is(SqlToken::STAR())) {
            /** @var int $length */
            $length = 1;

            /** @var SqlAstTokenNode $star */
            $star = $node;

            /** @var SqlAstTokenNode|null $table */
            $table = null;

            /** @var SqlAstTokenNode|null $database */
            $database = null;

            /** @var SqlAstNode $dot */
            $dot = $parent[$offset - 1];

            if ($dot instanceof SqlAstTokenNode && $dot->is(SqlToken::DOT())) {
                $node = $parent[$offset - 2];
                Assert::isInstanceOf($node, SqlAstTokenNode::class);

                if ($node->is(SqlToken::SYMBOL())) {
                    $table = $node;
                    $offset -= 2;
                    $length += 2;

                    $dot = $parent[$offset - 1];

                    if ($dot instanceof SqlAstTokenNode && $dot->is(SqlToken::DOT())) {
                        $node = $parent[$offset - 2];
                        Assert::isInstanceOf($node, SqlAstTokenNode::class);

                        if ($node->is(SqlToken::SYMBOL())) {
                            $database = $node;
                            $offset -= 2;
                            $length += 2;
                        }
                    }
                }

                $parent->replace($offset, $length, new SqlAstAllColumnsSelector(
                    $parent,
                    $star,
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
            $this->star,
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

    public function tableName(): ?SqlAstTokenNode
    {
        return $this->table;
    }

    public function tableNameString(): ?string
    {
        if (is_object($this->table)) {
            return SqlUtils::unquote($this->table->toSql());

        } else {
            return null;
        }
    }

    public function schemaName(): ?SqlAstTokenNode
    {
        return $this->database;
    }

    public function schemaNameString(): ?string
    {
        if (is_object($this->database)) {
            return SqlUtils::unquote($this->database->toSql());

        } else {
            return null;
        }
    }

    public function line(): int
    {
        return $this->star->line();
    }

    public function column(): int
    {
        return $this->star->column();
    }

    public function toSql(): string
    {
        return implode('.', array_map(function (SqlAstNode $node) {
            return $node->toSql();
        }, $this->children()));
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }
}
