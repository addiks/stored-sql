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

namespace Addiks\StoredSQL\Parsing;

use Addiks\StoredSQL\Exception\UnparsableSqlException;
use Addiks\StoredSQL\Lexing\SqlTokenizer;
use Addiks\StoredSQL\Lexing\SqlTokenizerClass;
use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstColumn;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstConjunction;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstFrom;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstJoin;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstLiteral;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstOperation;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstOrderBy;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstParenthesis;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstRoot;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstSelect;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstWhereCondition;
use Closure;
use Webmozart\Assert\Assert;

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
        );
    }

    public function parseSql(string $sql, array $expectedResultTypes = null): array
    {
        /** @var SqlTokens $tokens */
        $tokens = $this->tokenizer->tokenize($sql);

        $tokens = $tokens->withoutWhitespace();
        $tokens = $tokens->withoutComments();

        /** @var SqlAstRoot $syntaxTree */
        $syntaxTree = $tokens->convertToSyntaxTree();

        /** @var callable $mutator */
        foreach ($this->mutators as $mutator) {
            $syntaxTree->walk([$mutator]);
        }

        /** @var array<SqlAstNode> $detectedContent */
        $detectedContent = $syntaxTree->children();

        /** @var SqlAstNode $detectedNode */
        foreach ($detectedContent as $detectedNode) {

            /** @var class-string $expectedClass */
            foreach ($expectedResultTypes as $expectedClass) {
                Assert::classExists($expectedClass);

                if ($detectedNode instanceof $expectedClass) {
                    continue 2;
                }
            }

            throw new UnparsableSqlException(sprintf(
                "Unexpected node of type '%s' detected, expected one of: [%s]!",
                get_class($detectedNode),
                implode(', ', $expectedResultTypes)
            ), $detectedNode);
        }

        # TODO: make sure all detected nodes are "final" nodes (like a select statement)

        return $detectedContent;
    }

    public function tokenizer(): SqlTokenizer
    {
        return $this->tokenizer;
    }

    /** @return array<callable> */
    public function mutators(): array
    {
        return $this->mutators;
    }
}
