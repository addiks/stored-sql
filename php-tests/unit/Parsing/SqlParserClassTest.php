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
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstColumnNode;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstConjunction;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstOperationNode;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstRoot;
use Addiks\StoredSQL\Parsing\SqlParserClass;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SqlParserClassTest extends TestCase
{
    private SqlParserClass $subject;

    /** @var MockObject&SqlTokenizer */
    private SqlTokenizer $tokenizer;

    /** @var array<callable> $mutators */
    private array $mutators;

    public function setUp(): void
    {
        $this->tokenizer = $this->createMock(SqlTokenizer::class);
        $this->mutators = array();

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
            Closure::fromCallable([SqlAstColumnNode::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstOperationNode::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstConjunction::class, 'mutateAstNode']),
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
}
