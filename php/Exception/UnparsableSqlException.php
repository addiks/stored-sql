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

namespace Addiks\StoredSQL\Exception;

use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstNode;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstTokenNode;
use Addiks\StoredSQL\Lexing\AbstractSqlToken;
use Exception;
use Webmozart\Assert\Assert;

final class UnparsableSqlException extends Exception
{
    use AsciiLocationDumpTrait;

    private SqlAstNode $node;

    private string $sql;

    private int $sqlLine;

    private int $sqlOffset;

    public function __construct(string $message, SqlAstNode $node, Exception $parent = null)
    {
        $this->node = $node;
        $this->sql = $node->root()->tokens()->sql();
        $this->sqlLine = $node->line();
        $this->sqlOffset = $node->column();

        parent::__construct($message, 0, $parent);
    }

    public function __toString(): string
    {
        return parent::__toString() . $this->asciiLocationDump();
    }

    public static function assertSql(SqlAstNode $parent, int $offset, string $expectedSql): void
    {
        /** @var SqlAstNode|null $actualNode */
        $actualNode = $parent->children()[$offset] ?? null;

        $expectedSql = strtoupper($expectedSql);

        if (is_null($actualNode) || !($actualNode instanceof SqlAstTokenNode) || strtoupper($actualNode->toSql()) !== $expectedSql) {
            throw new UnparsableSqlException(sprintf(
                "Expected SQL code '%s' at offset %d, found %s instead!",
                $expectedSql,
                $offset,
                is_object($actualNode) ? get_class($actualNode) : 'nothing'
            ), $actualNode ?? $parent);
        }
    }

    public static function assertToken(SqlAstNode $parent, int $offset, AbstractSqlToken $expectedToken): void
    {
        /** @var SqlAstNode|null $actualNode */
        $actualNode = $parent->children()[$offset] ?? null;

        if (is_null($actualNode) || !($actualNode instanceof SqlAstTokenNode) || !$actualNode->is($expectedToken)) {
            throw new UnparsableSqlException(sprintf(
                "Expected token '%s' at offset %d, found %s instead!",
                $expectedToken->name(),
                $offset,
                is_object($actualNode) ? get_class($actualNode) : 'nothing'
            ), $actualNode ?? $parent);
        }
    }

    /** @param class-string $expectedClassName */
    public static function assertType(SqlAstNode $parent, int $offset, string $expectedClassName): void
    {
        Assert::true($expectedClassName === SqlAstNode::class || is_subclass_of($expectedClassName, SqlAstNode::class));

        /** @var SqlAstNode|null $actualNode */
        $actualNode = $parent->children()[$offset] ?? null;

        if (is_null($actualNode) || !($actualNode instanceof $expectedClassName)) {
            throw new UnparsableSqlException(sprintf(
                "Expected node of '%s' at offset %d, found %s instead!",
                $expectedClassName,
                $offset,
                is_object($actualNode) ? get_class($actualNode) : 'nothing'
            ), $actualNode ?? $parent);
        }
    }

    public function node(): SqlAstNode
    {
        return $this->node;
    }

    public function sql(): string
    {
        return $this->sql;
    }

    public function sqlLine(): int
    {
        return $this->sqlLine;
    }

    public function sqlOffset(): int
    {
        return $this->sqlOffset;
    }
}
