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

namespace Addiks\StoredSQL\Tests\Unit\Parsing;

use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstMutableNode;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstNode;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstRoot;
use Addiks\StoredSQL\Exception\UnparsableSqlException;
use Addiks\StoredSQL\Lexing\SqlTokenizer;
use Addiks\StoredSQL\Lexing\SqlTokenizerClass;
use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\Parsing\SqlParser;
use Addiks\StoredSQL\Parsing\SqlParserClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/** @psalm-import-type Mutator from SqlAstMutableNode */
final class SqlParserClassTest extends TestCase
{
    public const DATA_FOLDER_NAME = '../../../fixtures';

    private SqlParserClass $subject;

    /** @var MockObject&SqlTokenizer */
    private SqlTokenizer $tokenizer;

    /** @var array<Mutator> $mutators */
    private array $mutators;

    public function setUp(): void
    {
        $this->tokenizer = $this->createMock(SqlTokenizer::class);
        $this->mutators = array(function (): void {
        });

        $this->subject = new SqlParserClass($this->tokenizer, $this->mutators);
    }

    /**
     * @test
     *
     * @covers SqlParserClass::parseSql
     */
    public function shouldParseSql(): void
    {
        /** @var string $sql */
        $sql = 'Some test-SQL snippet';

        /** @var MockObject&SqlTokens $tokens */
        $tokens = $this->createMock(SqlTokens::class);

        $this->tokenizer->expects($this->once())->method('tokenize')->with($this->equalTo($sql))->willReturn($tokens);

        $tokens->expects($this->once())->method('withoutWhitespace')->willReturn($tokens);
        $tokens->expects($this->once())->method('withoutComments')->willReturn($tokens);

        /** @var MockObject&SqlAstRoot $syntaxTree */
        $syntaxTree = $this->createMock(SqlAstRoot::class);

        $tokens->expects($this->once())->method('convertToSyntaxTree')->willReturn($syntaxTree);

        $syntaxTree->expects($this->once())->method('mutate')->with($this->equalTo($this->mutators));

        $this->subject->parseSql($sql);
    }

    /**
     * @test
     *
     * @covers SqlParserClass::defaultParser
     * @covers SqlParserClass::tokenizer
     * @covers SqlParserClass::mutators
     */
    public function shouldProvideDefaultParser(): void
    {
        /** @var SqlParserClass $defaultParser */
        $defaultParser = SqlParserClass::defaultParser();

        $this->assertEquals(SqlTokenizerClass::defaultTokenizer(), $defaultParser->tokenizer());
        $this->assertEquals(SqlParserClass::defaultMutators(), $defaultParser->mutators());
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     *
     * @covers SqlParserClass::parseSql
     */
    public function shouldBuildCorrectAst(
        string $astFile,
        string $sql,
        string $expectedDump
    ): void {
        /** @var SqlParser $parser */
        $parser = SqlParserClass::defaultParser();

        try {
            /** @var array<SqlAstNode> $detectedContent */
            $detectedContent = $parser->parseSql($sql);

        } catch (UnparsableSqlException $exception) {
            echo $exception->asciiLocationDump();

            throw $exception;
        }

        /** @var string $actualDump */
        $actualDump = $this->dumpNodes($detectedContent);

        if ($expectedDump !== $actualDump) {
            #file_put_contents('/tmp/ga_debug.ast', $this->dumpNodes($detectedContent, 0, false));
        }

        $this->assertEquals($expectedDump, $actualDump);
    }

    /** @return array<string, array{0:string, 1:string, 2:string}> */
    public function dataProvider(): array
    {
        /** @var array<string> $sqlFiles */
        $sqlFiles = glob(sprintf('%s/%s/*.sql', __DIR__, self::DATA_FOLDER_NAME));

        /** @var array<string, array{0:string, 1:string, 2:string}> $dataSets */
        $dataSets = array();

        /** @var string $sqlFile */
        foreach ($sqlFiles as $sqlFile) {
            /** @var string $astFile */
            $astFile = $sqlFile . '.ast';

            if (file_exists($astFile)) {
                $dataSets[basename($astFile)] = [
                    realpath($astFile),
                    (string) file_get_contents($sqlFile),
                    trim((string) file_get_contents($astFile)),
                ];
            }
        }

        return $dataSets;
    }

    /** @param array<SqlAstNode> $nodes */
    private function dumpNodes(array $nodes, int $level = 0, bool $withSql = false): string
    {
        /** @var array<string> $dumpLines */
        $dumpLines = array();

        /** @var SqlAstNode $node */
        foreach ($nodes as $node) {
            /** @var string $line */
            $line = str_pad('', $level, '-') . array_reverse(explode('\\', get_class($node)))[0];

            if ($withSql) {
                $line .= ':' . $node->toSql();
            }

            $dumpLines[] = $line;

            /** @var array<SqlAstNode> $children */
            $children = $node->children();

            if (!empty($children)) {
                $dumpLines[] = $this->dumpNodes($children, $level + 1, $withSql);
            }
        }

        return implode("\n", $dumpLines);
    }
}
