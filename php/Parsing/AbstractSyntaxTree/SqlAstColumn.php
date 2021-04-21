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

use Addiks\StoredSQL\Lexing\SqlToken;
use ArrayIterator;
use Iterator;
use Webmozart\Assert\Assert;

final class SqlAstColumn implements SqlAstExpression
{
    private string $column;

    private ?string $table;

    private ?string $database;

    public function __construct(
        string $column,
        ?string $table,
        ?string $database
    ) {
        $this->column = $column;
        $this->table = $table;
        $this->database = $database;
    }

    public static function mutateAstNode(
        SqlAstNode $node,
        int $offset,
        SqlAstMutableNode $parent
    ): void {
        if ($node instanceof SqlAstTokenNode && $node->token()->is(SqlToken::SYMBOL())) {

            /** @var int $length */
            $length = 1;

            /** @var string $column */
            $column = $node->token()->code();

            /** @var string|null $table */
            $table = null;

            /** @var string|null $database */
            $database = null;

            /** @var SqlAstNode $dot */
            $dot = $parent[$offset + 1];

            if ($dot instanceof SqlAstTokenNode && $dot->is(SqlToken::DOT())) {
                $node = $parent[$offset + 2];
                Assert::isInstanceOf($node, SqlAstTokenNode::class);

                if ($node->is(SqlToken::SYMBOL())) {
                    $length += 2;

                    $table = $column;
                    $column = $node->token()->code();

                    $dot = $parent[$offset + 3];

                    if ($dot instanceof SqlAstTokenNode && $dot->is(SqlToken::DOT())) {
                        $node = $parent[$offset + 4];
                        Assert::isInstanceOf($node, SqlAstTokenNode::class);

                        if ($node->is(SqlToken::SYMBOL())) {
                            $length += 2;

                            $database = $table;
                            $table = $column;
                            $column = $node->token()->code();
                        }
                    }
                }

                $parent->replace($offset, $length, new SqlAstColumn(
                    $column,
                    $table,
                    $database
                ));
            }
        }
    }

    public function children(): array
    {
        return [];
    }

    public function hash(): string
    {
        return sprintf(
            '`%s`.`%s`.`%s`',
            $this->database ?? '?',
            $this->table ?? '?',
            $this->column
        );
    }
}
