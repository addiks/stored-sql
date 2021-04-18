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

use Addiks\StoredSQL\Lexing\SqlTokens;

final class SqlAstRootClass extends SqlAstBranch implements SqlAstRoot
{
    private SqlTokens $tokens;

    /** @param array<SqlAstNode> $children */
    public function __construct(array $children, SqlTokens $tokens)
    {
        parent::__construct($children);

        $this->tokens = $tokens;
    }

    public function tokens(): SqlTokens
    {
        return $this->tokens;
    }
}
