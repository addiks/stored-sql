<?php
/**
 * Copyright (C) 2019  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\StoredSQL\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Addiks\StoredSQL\Exception\UnlexableSqlException;
use Webmozart\Assert\Assert;

/** @covers Addiks\StoredSQL\Exception\UnlexableSqlException */
final class UnlexableSqlExceptionTest extends TestCase
{
    private ?UnlexableSqlException $exception = null;

    public function setUp(): void
    {
        $this->exception = new UnlexableSqlException("SELECT foo\nFROM bar", 1, 6);
    }

    /** @test */
    public function shouldProduceAsciiLocationDump(): void
    {
        Assert::object($this->exception);

        /** @var mixed $expectedOutput */
        $expectedOutput = <<<EOL


         \u{2193}
   SELECT foo
 \u{2192} FROM bar \u{2190}
         \u{2191}

EOL;

        $this->assertEquals($expectedOutput, $this->exception->asciiLocationDump());
    }

    /** @test */
    public function shouldProvideSql(): void
    {
        Assert::object($this->exception);
        $this->assertEquals("SELECT foo\nFROM bar", $this->exception->sql());
    }

    /** @test */
    public function shouldProvideSqlLine(): void
    {
        Assert::object($this->exception);
        $this->assertEquals(1, $this->exception->sqlLine());
    }

    /** @test */
    public function shouldProvideSqlOffset(): void
    {
        Assert::object($this->exception);
        $this->assertEquals(6, $this->exception->sqlOffset());
    }
}
