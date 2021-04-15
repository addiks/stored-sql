<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\StoredSQL\Parsing;

use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\Lexing\SqlToken;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\MutableSqlAstNode;
use Webmozart\Assert\Assert;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstNode;
use Addiks\StoredSQL\Lexing\SqlTokenizer;
use Addiks\StoredSQL\Lexing\SqlTokenizerClass;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstColumnNode;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstTokenNode;

final class SqlParserClass implements SqlParser
{
    private SqlTokenizer $tokenizer;

    /** @var array<callable> */
    private array $mutators;

    /** @param array<callable> $mutators */
    public function __construct(
        SqlTokenizer $tokenizer,
        array $mutators
    ) {
        array_map(function ($callback) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            Assert::isCallable($callback);
        }, $mutators);

        $this->tokenizer = $tokenizer;
        $this->mutators = $mutators;
    }

    public static function defaultParser(): SqlParser
    {
        return new SqlParserClass(
            SqlTokenizerClass::defaultTokenizer(),
            self::defaultMutators()
        );
    }

    /** @return array<callable> */
    public static function defaultMutators(): array
    {
        return array(
            function (SqlAstNode $node, int $offset, MutableSqlAstNode $parent) {

                if ($node instanceof SqlAstTokenNode && $node->token()->is(SqlToken::SYMBOL())) {

                    /** @var int $length */
                    $length = 1;

                    /** @var string $column */
                    $column = $node->token()->code();

                    /** @var string|null $table */
                    $table = null;

                    /** @var string|null $database */
                    $database = null;

                    if ($parent[$offset + 1]->is(SqlToken::DOT()) && $parent[$offset + 2]->is(SqlToken::SYMBOL())) {
                        $length += 2;

                        $table = $column;
                        $column = $parent[$offset + 2]->token()->code();

                        if ($parent[$offset + 3]->is(SqlToken::DOT()) && $parent[$offset + 4]->is(SqlToken::SYMBOL())) {
                            $length += 2;

                            $database = $table;
                            $table = $column;
                            $column = $parent[$offset + 4]->token()->code();
                        }
                    }

                    $parent->replace($offset, $length, new SqlAstColumnNode(
                        $column,
                        $table,
                        $database
                    ));
                }
            }
        );
    }

    public function parseSql(string $sql): void
    {
        /** @var SqlTokens $tokens */
        $tokens = $this->tokenizer->tokenize($sql);

        $tokens = $tokens->withoutWhitespace();
        $tokens = $tokens->withoutComments();

        /** @var SqlAstRoot $syntaxTree */
        $syntaxTree = $tokens->convertToSyntaxTree();

        $syntaxTree->walk($this->mutators);
    }
}
