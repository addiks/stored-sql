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
     * @dataProvider dataProviderForShouldDetectCorrectTokens
     *
     * @param array<int, SqlToken> $expectedTokens
     */
    public function shouldDetectCorrectTokens(string $sql, array $expectedTokens): void
    {
        try {
            /** @var SqlTokens $tokens */
            $tokens = SqlToken::readTokens($sql);
            $tokens = $tokens->withoutWhitespace();

        } catch (UnlexableSqlException $exception) {
            echo $exception->asciiLocationDump();

            throw $exception;
        }

        /** @var array<int, SqlToken> $tokenList */
        $tokenList = array_map(function (SqlTokenInstance $token) {
            return $token->token();
        }, iterator_to_array($tokens));

        /** @var string $expected */
        $expected = $this->tokenListToString($expectedTokens);

        /** @var string $actual */
        $actual = $this->tokenListToString($tokenList);

        $this->assertEquals($expected, $actual);
    }

    public function dataProviderForShouldDetectCorrectTokens(): array
    {
        /** @var array<string> $sqlFiles */
        $sqlFiles = glob(sprintf("%s/%s/*.sql", __DIR__, self::DATA_FOLDER_NAME));

        /** @var array<array{0:string,1:array<SqlToken>}> $dataSets */
        $dataSets = array();

        /** @var string $sqlFile */
        foreach ($sqlFiles as $sqlFile) {

            /** @var string $tokenFile */
            $tokenFile = $sqlFile . ".tokens";

            if (file_exists($tokenFile)) {
                /** @var array<string> $tokenNames */
                $tokenNames = explode("\n", file_get_contents($tokenFile));
                $tokenNames = array_filter($tokenNames);

                /** @var array<SqlToken> $tokens */
                $tokens = array_map(function (string $tokenName): SqlToken {
                    return SqlToken::valueOf($tokenName);
                }, $tokenNames);

                $dataSets[basename($tokenFile)] = [
                    file_get_contents($sqlFile),
                    $tokens
                ];
            }
        }

        return $dataSets;
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldProduceCorrectLinesAndOffsets
     */
    public function shouldProduceCorrectLinesAndOffsets(string $sql, array $lines, array $offsets): void
    {
        try {
            /** @var SqlTokens $tokens */
            $tokens = SqlToken::readTokens($sql);
            $tokens = $tokens->withoutWhitespace();

        } catch (UnlexableSqlException $exception) {
            echo $exception->asciiLocationDump();

            throw $exception;
        }

        /** @var SqlTokenInstance $token */
        foreach ($tokens as $index => $token) {
            $this->assertEquals($lines[$index], $token->line(), sprintf(
                "Wrong line-number '%d' at token #%d, expected line-number '%d'",
                $token->line(),
                $index,
                $lines[$index]
            ));

            $this->assertEquals($offsets[$index], $token->offset(), sprintf(
                "Wrong offset-number '%d' at token #%d, expected offset-number '%d'",
                $token->offset(),
                $index,
                $offsets[$index]
            ));
        }
    }

    public function dataProviderForShouldProduceCorrectLinesAndOffsets(): array
    {
        /** @var array<string> $sqlFiles */
        $sqlFiles = glob(sprintf("%s/%s/*.sql", __DIR__, self::DATA_FOLDER_NAME));

        /** @var array<array{0:string,1:array<SqlToken>}> $dataSets */
        $dataSets = array();

        /** @var string $sqlFile */
        foreach ($sqlFiles as $sqlFile) {

            /** @var string $positionsFile */
            $positionsFile = $sqlFile . ".positions";

            if (file_exists($positionsFile)) {
                /** @var array<string> $positions */
                $positions = explode("\n", file_get_contents($positionsFile));
                $positions = array_filter($positions);

                /** @var array{0: int, 1: int} $linesAndOffsets */
                $linesAndOffsets = array_map(function (string $position): array {
                    return explode(",", $position);
                }, $positions);

                $dataSets[basename($positionsFile)] = [
                    file_get_contents($sqlFile),
                    array_column($linesAndOffsets, 0),
                    array_column($linesAndOffsets, 1),
                ];
            }
        }

        return $dataSets;
    }

    /** @test */
    public function shouldNotAcceptNonSqlTokens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SqlTokensClass(['foo']);
    }

    /** @param array<int, SqlToken> $tokens */
    private function tokenListToString(array $tokens): string
    {
        return implode("\n", array_map(function (SqlToken $token) {
            return $token->name();
        }, $tokens));
    }

}
