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

final class SqlAstTable implements SqlAstExpression
{
    use SqlAstWalkableTrait;

    private SqlAstNode $parent;

    private SqlAstTokenNode $table;

    private ?SqlAstTokenNode $database;

    public function __construct(
        SqlAstNode $parent,
        SqlAstTokenNode $table,
        ?SqlAstTokenNode $database
    ) {
        $this->parent = $parent;
        $this->table = $table;
        $this->database = $database;
    }

    public function children(): array
    {
        return array_filter([
            $this->database,
            $this->table,
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
        return $this->table->line();
    }

    public function column(): int
    {
        return $this->table->column();
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
