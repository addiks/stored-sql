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

interface SqlAstMergable extends SqlAstNode
{
    /**
     * Merges (or add's) another node of the same type.
     * Depending on the semantics of the node-type, the original node might be removed of it's parent.
     * For example:
     *  - Merging a WHERE clause into another one results in one new WHERE which contains the conditions of both originals.
     *  - Merging a JOIN into another results in both (old and new) JOIN's be present in the parent node.
     */
    public function merge(SqlAstMergable $toMerge): SqlAstMergable;
}
