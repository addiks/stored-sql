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

interface SqlAstExpression extends SqlAstNode
{
    /**
     * Extracts all equations ("A = B") from this expression that are true regardless of any other circumstances.
     * These are used to determine if an expression can make use an index or if it is a one-to-one join, etc...
     *
     * For example:
     *
     *  Given this expression:
     *      "A = B AND C = D AND (E = F OR G = H) AND I > J AND (K = M AND N = O)"
     *
     *  ... then the list of resulting equations for this expression would be:
     *      - "A = B"
     *      - "C = D"
     *      - "K = M"
     *      - "N = O"
     *
     * Fundamental equations can only compare symbols or literals with each other.
     * If an equation compares anything else (like a function-call or a sub-query),
     * then it does not qualify as a fundamental equation.
     *
     * @see SqlAstOperation
     *
     * @return array<SqlAstOperation>
     */
    public function extractFundamentalEquations(): array;
}
