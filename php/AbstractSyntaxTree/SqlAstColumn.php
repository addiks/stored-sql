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

final class SqlAstColumn implements SqlAstExpression
{
    use SqlAstWalkableTrait;

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
        /** @var int $offset */
        $offset = (int) array_search($column, $parent->children(), true);

        UnparsableSqlException::assertToken($parent, $offset, SqlToken::SYMBOL());

        if (is_object($table)) {
            $offset = (int) array_search($table, $parent->children(), true);
            UnparsableSqlException::assertToken($parent, $offset, SqlToken::SYMBOL());
        }

        if (is_object($database)) {
            $offset = (int) array_search($table, $database->children(), true);
            UnparsableSqlException::assertToken($parent, $offset, SqlToken::SYMBOL());
        }

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
        /** @var SqlAstNode|null $previousNode */
        $previousNode = $parent[$offset - 1];
        
        /** @var SqlAstNode|null $nextNode */
        $nextNode = $parent[$offset + 1];
        
        if (($previousNode instanceof SqlAstTokenNode && $previousNode->is(SqlToken::SYMBOL()))
        || ($nextNode instanceof SqlAstTokenNode && $nextNode->is(SqlToken::SYMBOL()))) {
            # If two symbols follow each other, none of them are column references
            return;
        }
        
        if ($nextNode instanceof SqlAstTokenNode && $nextNode->is(SqlToken::BRACKET_OPENING())) {
            return;
        }
        
        if ($node instanceof SqlAstTokenNode && $node->is(SqlToken::SYMBOL())) {
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
            }

            $parent->replace($offset, $length, new SqlAstColumn(
                $parent,
                $column,
                $table,
                $database
            ));
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

    public function columnName(): SqlAstTokenNode
    {
        return $this->column;
    }

    public function columnNameString(): string
    {
        return SqlUtils::unquote($this->column->toSql());
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

    /**
     * Calling this indicates that this occurence of a detected "column" (e.g.: 'foo.baz') is actually a table.
     * To identify if 'foo.baz' refers to column 'baz' in table 'foo' or to table 'baz' in database 'foo' depends on
     * the context, thus some other component (f.e.: the SELECT or UPDATE statement node) has to make this distinction.
     */
    public function convertToTable(): SqlAstTable
    {
        Assert::null($this->database);

        $table = new SqlAstTable($this->parent, $this->column, $this->table);

        if ($this->parent instanceof SqlAstMutableNode) {
            $this->parent->replaceNode($this, $table);
        }

        return $table;
    }

    public function extractFundamentalEquations(): array
    {
        return [];
    }

    public function canBeExecutedAsIs(): bool
    {
        return false;
    }
}
