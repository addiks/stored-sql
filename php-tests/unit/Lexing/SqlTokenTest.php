<?php
/**
 * Copyright (C) 2019  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\StoredSQL\Tests\Unit\Lexing;

use PHPUnit\Framework\TestCase;
use Addiks\StoredSQL\Lexing\SqlToken;
use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\Lexing\SqlTokensClass;
use Addiks\StoredSQL\Lexing\SqlTokenInstance;
use Addiks\StoredSQL\Exception\UnlexableSqlException;
use InvalidArgumentException;

/**
 * @covers Addiks\StoredSQL\Lexing\SqlToken
 * @covers Addiks\StoredSQL\Lexing\SqlTokensClass
 * @covers Addiks\StoredSQL\Lexing\SqlTokenInstanceClass
 */
final class SqlTokenTest extends TestCase
{
    const DATA_FOLDER_NAME = "SqlTokenTestData";

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function shouldDetectCorrectTokens(
        string $sql,
        string $expectedDump
    ): void {
        try {
            /** @var SqlTokens $tokens */
            $tokens = SqlToken::readTokens($sql);
            $tokens = $tokens->withoutWhitespace();

        } catch (UnlexableSqlException $exception) {
            echo $exception->asciiLocationDump();

            throw $exception;
        }

        /** @var array<string> $actualLines */
        $actualLines = array();

        /** @var SqlTokenInstance $token */
        foreach ($tokens as $index => $token) {
            $actualLines[] = sprintf("%d,%d,%s",
                $token->line(),
                $token->offset(),
                $token->token()
            );
        }

        $actualDump = implode("\n", $actualLines);

        $this->assertEquals($expectedDump, $actualDump);
    }

    public function dataProvider(): array
    {
        /** @var array<string> $sqlFiles */
        $sqlFiles = glob(sprintf("%s/%s/*.sql", __DIR__, self::DATA_FOLDER_NAME));

        /** @var array<string, array{0:string, 1:int, 2:int, 3:array<SqlToken>}> $dataSets */
        $dataSets = array();

        /** @var string $sqlFile */
        foreach ($sqlFiles as $sqlFile) {

            /** @var string $tokenFile */
            $tokenFile = $sqlFile . ".tokens";

            if (file_exists($tokenFile)) {
                $dataSets[basename($tokenFile)] = [
                    file_get_contents($sqlFile),
                    trim(file_get_contents($tokenFile)),
                ];
            }
        }

        return $dataSets;
    }

    /** @test */
    public function shouldNotAcceptNonSqlTokens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SqlTokensClass(['foo'], "");
    }

    /** @param array<int, SqlToken> $tokens */
    private function tokenListToString(array $tokens): string
    {
        return implode("\n", array_map(function (SqlToken $token) {
            return $token->name();
        }, $tokens));
    }

}
