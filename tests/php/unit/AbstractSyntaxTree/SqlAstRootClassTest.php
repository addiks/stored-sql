<?php
/**
 * Copyright (C) 2019  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks;

use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstRootClass;
use PHPUnit\Framework\TestCase;

final class SqlAstRootClassTest extends TestCase
{
    private SqlAstRootClass $subject;

    private SqlTokens $tokens;

    public function setUp(): void
    {
        $this->tokens = $this->createMock(SqlTokens::class);

        $this->subject = new SqlAstRootClass([], $this->tokens);
    }

    /**
     * @test
     * @covers SqlAstRootClass::tokens
     */
    public function shouldProvideTokens(): void
    {
        $this->assertSame($this->tokens, $this->subject->tokens());
    }
}
