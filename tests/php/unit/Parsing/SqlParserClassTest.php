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

use Addiks\StoredSQL\Lexing\SqlTokenizer;
use Addiks\StoredSQL\Lexing\SqlTokenizerClass;
use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstColumn;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstConjunction;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstOperation;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstRoot;
use Addiks\StoredSQL\Parsing\SqlParserClass;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstLiteral;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstOrderBy;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstParenthesis;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstFrom;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstJoin;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstSelect;
use Addiks\StoredSQL\Parsing\SqlParser;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstNode;
use Webmozart\Assert\Assert;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstWhere;

final class SqlParserClassTest extends TestCase
{
    const DATA_FOLDER_NAME = '../../../fixtures';

    private SqlParserClass $subject;

    /** @var MockObject&SqlTokenizer */
    private SqlTokenizer $tokenizer;

    /** @var array<callable> $mutators */
    private array $mutators;

    public function setUp(): void
    {
        $this->tokenizer = $this->createMock(SqlTokenizer::class);
        $this->mutators = array(function () {});

        $this->subject = new SqlParserClass($this->tokenizer, $this->mutators);
    }

    /**
     * @test
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

        $syntaxTree->expects($this->once())->method('walk')->with($this->equalTo($this->mutators));

        $this->subject->parseSql($sql);
    }

    /**
     * @test
     * @covers SqlParserClass::defaultMutators
     */
    public function shouldProvideDefaultMutators(): void
    {
        $this->assertEquals([
            Closure::fromCallable([SqlAstLiteral::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstColumn::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstOperation::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstConjunction::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstWhere::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstOrderBy::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstParenthesis::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstFrom::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstJoin::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstSelect::class, 'mutateAstNode']),
        ], SqlParserClass::defaultMutators());
    }

    /**
     * @test
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
     * @dataProvider dataProvider
     * @covers SqlParserClass::parseSql
     */
    public function shouldBuildCorrectAst(
        string $astFile,
        string $sql,
        string $expectedDump
    ): void {

        /** @var array<string> $dumpLines */
        $dumpLines = explode("\n", $expectedDump);

        /** @var SqlParser $parser */
        $parser = SqlParserClass::defaultParser();

        /** @var array<SqlAstNode> $detectedContent */
        $detectedContent = $parser->parseSql($sql);

        /** @var array<int, SqlAstNode> $stack */
        $stack = array();

        /** @var array<int, array<SqlAstNode>> $childrensStack */
        $childrensStack = array(-1 => $detectedContent);

        /** @var string $dumpLine */
        foreach ($dumpLines as $lineNumber => $dumpLine) {
            Assert::true(preg_match('/^(\-*)([a-zA-Z0-9]+)$/is', $dumpLine, $matches) === 1, sprintf(
                'Malformed line in file "%s" at line %d!',
                $astFile,
                $lineNumber
            ));

            /** @var int $level */
            $level = substr_count($matches[1], '-');

            /** @var string $expectedNodeType */
            $expectedNodeType = $matches[2];

            $this->assertNotEmpty($childrensStack[$level-1], sprintf(
                'Expected node-type "%s" at line %d, was end of nodes instead!',
                $expectedNodeType,
                $lineNumber
            ));

            $stack[$level] = array_shift($childrensStack[$level-1]);
            $childrensStack[$level] = $stack[$level]->children();

            /** @var string $actualNodeType */
            $actualNodeType = array_reverse(explode('\\', get_class($stack[$level])))[0];

            $this->assertEquals($expectedNodeType, $actualNodeType, sprintf(
                'Expected node-type "%s" at line %d, found "%s" instead!',
                $expectedNodeType,
                $lineNumber,
                $actualNodeType
            ));
        }
    }

    /** @return array<string, array{0:string, 1:string}> */
    public function dataProvider(): array
    {
        /** @var array<string> $sqlFiles */
        $sqlFiles = glob(sprintf('%s/%s/*.sql', __DIR__, self::DATA_FOLDER_NAME));

        /** @var array<string, array{0:string, 1:string}> $dataSets */
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


}
