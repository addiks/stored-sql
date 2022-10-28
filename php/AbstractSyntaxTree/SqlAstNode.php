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

/** @psalm-type SqlNodeWalker = callable(SqlAstNode, int, SqlAstNode): void */
interface SqlAstNode
{
    /** @return array<int, self> */
    public function children(): array;

    /**
     * Executes the given callback for every child-node in this AST recursively.
     *
     * Mutation is not allowed during execution of the callback.
     *
     * The node will be the first parameter for the callback.
     *
     * @param array<SqlNodeWalker> $callbacks
     */
    public function walk(array $callbacks = array()): void;

    /**
     * Represents the state of this part of the AST.
     * If contents change, this hash must change.
     * Used to determine when the AST stops changing during parsing phase.
     */
    public function hash(): string;

    public function parent(): ?SqlAstNode;

    public function root(): SqlAstRoot;

    public function line(): int;

    public function column(): int;

    public function toSql(): string;

    /**
     * Can this SQL-AST-node be sent to an SQL server as a complete runnable statement?
     *
     * This can be used as a check to determine if a mutation process was successful:
     * If complete and runnable statements go in, then complete and runnable statements should come out.
     */
    public function canBeExecutedAsIs(): bool;
}
