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

namespace Addiks\StoredSQL\Parsing;

use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstNode;

/** @psalm-import-type SqlNodeWalker from SqlAstNode */
interface SqlParser
{
    /**
     * Parse a given SQL string into a list of detected AST node objects.
     *
     * @param array<class-string>  $expectedResultTypes
     * @param array<SqlNodeWalker> $validationCallbacks
     *
     * @return array<SqlAstNode>
     */
    public function parseSql(
        string $sql,
        array $expectedResultTypes = array(),
        array $validationCallbacks = array()
    ): array;
}
