/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

import { SqlAstRoot } from './SqlAstRoot';

export interface SqlAstNode
{
    children(): Array<SqlAstNode>;

    /**
     * Represents the state of this part of the AST.
     * If contents change, this hash must change.
     * Used to determine when the AST stops changing during parsing phase.
     */
    hash(): string;

    parent(): SqlAstNode | null;

    root(): SqlAstRoot;

    line(): number;

    column(): number;

    toSql(): string;
}