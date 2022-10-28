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

namespace Addiks\StoredSQL\Tests\Unit\AbstractSyntaxTree;

use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstBranch;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstMutableNode;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstNode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\Assert;

/**
 * @psalm-type MockAstNode = MockObject&SqlAstNode
 *
 * @psalm-import-type SqlNodeWalker from SqlAstNode
 */
final class SqlAstBranchTest extends TestCase
{
    private SqlAstBranch $subject;

    /** @var MockAstNode $childA */
    private SqlAstNode $childA;

    /** @var MockObject&SqlAstMutableNode $childB */
    private SqlAstMutableNode $childB;

    /** @var MockAstNode $childC */
    private SqlAstNode $childC;

    public function setUp(): void
    {
        $this->childA = $this->createMock(SqlAstNode::class);
        $this->childB = $this->createMock(SqlAstMutableNode::class);
        $this->childC = $this->createMock(SqlAstNode::class);

        $this->subject = $this->getMockForAbstractClass(SqlAstBranch::class, [
            [$this->childA, $this->childB, $this->childC],
        ]);
    }

    /**
     * @test
     *
     * @covers SqlAstBranch::children
     */
    public function shouldProvideChildren(): void
    {
        $this->assertSame([$this->childA, $this->childB, $this->childC], $this->subject->children());
    }

    /**
     * @test
     *
     * @covers SqlAstBranch::hash
     */
    public function shouldGenerateHash(): void
    {
        $this->childA->method('hash')->willReturn('hashA');
        $this->childB->method('hash')->willReturn('hashB');
        $this->childC->method('hash')->willReturn('hashC');

        $this->assertSame(md5('hashA.hashB.hashC'), $this->subject->hash());
    }

    /**
     * @test
     *
     * @covers SqlAstBranch::walk
     */
    public function shouldWalkChildren(): void
    {
        /** @var array<MockAstNode> $mutatorAExpectedChilds */
        $mutatorAExpectedChilds = [$this->childA, $this->childB, $this->childC];

        $mutatorA = function (SqlAstNode $child, int $offset, SqlAstMutableNode $subject) use (&$mutatorAExpectedChilds): void {
            Assert::isArray($mutatorAExpectedChilds);

            $this->assertSame($this->subject, $subject);
            $this->assertNotEmpty($mutatorAExpectedChilds);
            $this->assertEquals(3 - count($mutatorAExpectedChilds), $offset);
            $this->assertSame(array_shift($mutatorAExpectedChilds), $child);
        };

        /** @var array<MockAstNode> $mutatorBExpectedChilds */
        $mutatorBExpectedChilds = [$this->childA, $this->childB, $this->childC];

        $mutatorB = function (SqlAstNode $child, int $offset, SqlAstMutableNode $subject) use (&$mutatorBExpectedChilds): void {
            Assert::isArray($mutatorBExpectedChilds);

            $this->assertSame($this->subject, $subject);
            $this->assertNotEmpty($mutatorBExpectedChilds);
            $this->assertEquals(3 - count($mutatorBExpectedChilds), $offset);
            $this->assertSame(array_shift($mutatorBExpectedChilds), $child);
        };

        /** @var array<SqlNodeWalker> $mutators */
        $mutators = array($mutatorA, $mutatorB);

        $this->childB->expects($this->exactly(2))->method('walk')->with($this->equalTo($mutators));

        $this->subject->walk($mutators);
    }

    /**
     * @test
     *
     * @covers SqlAstBranch::replace
     *
     * @dataProvider dataProviderForShouldReplaceNodes
     */
    public function shouldReplaceNodes(int $offset, int $length, SqlAstNode $newNode, array $expectedChilds): void
    {
        $this->subject->replace($offset, $length, $newNode);

        /** @param string|MockAstNode $input */
        $expectedChilds = array_map(function (string|object $input): object {
            if (is_string($input) && isset($this->{$input})) {
                /** @psalm-suppress MixedAssignment */
                $input = $this->{$input};
            }

            Assert::object($input);

            return $input;
        }, $expectedChilds);

        $this->assertSame($expectedChilds, $this->subject->children());
    }

    /** @return list<array{0:int, 1:int, 2:MockAstNode, 3:list<string|MockAstNode>}> */
    public function dataProviderForShouldReplaceNodes(): array
    {
        /** @var MockAstNode $newNode */
        $newNode = $this->createMock(SqlAstNode::class);

        return [
            [0, 3, $newNode, [$newNode]],
            [0, 2, $newNode, [$newNode, 'childC']],
            [1, 2, $newNode, ['childA', $newNode]],
            [0, 1, $newNode, [$newNode, 'childB', 'childC']],
            [1, 1, $newNode, ['childA', $newNode, 'childC']],
            [2, 1, $newNode, ['childA', 'childB', $newNode]],
            [0, 0, $newNode, [$newNode, 'childA', 'childB', 'childC']],
            [1, 0, $newNode, ['childA', $newNode, 'childB', 'childC']],
            [2, 0, $newNode, ['childA', 'childB', $newNode, 'childC']],
            [3, 0, $newNode, ['childA', 'childB', 'childC', $newNode]],
        ];
    }
}
