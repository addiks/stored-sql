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

use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstColumn;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstConjunction;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstFrom;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstFunctionCall;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstGroupBy;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstHaving;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstJoin;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstLiteral;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstMutableNode;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstNode;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstOperation;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstOrderBy;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstParenthesis;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstRoot;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstSelect;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstUpdate;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstWhere;
use Addiks\StoredSQL\Exception\UnparsableSqlException;
use Addiks\StoredSQL\Lexing\SqlTokenizer;
use Addiks\StoredSQL\Lexing\SqlTokenizerClass;
use Addiks\StoredSQL\Lexing\SqlTokens;
use Closure;
use Webmozart\Assert\Assert;

/** @psalm-import-type Mutator from SqlAstMutableNode */
final class SqlParserClass implements SqlParser
{
    private SqlTokenizer $tokenizer;

    /** @var array<Mutator> */
    private array $mutators;

    /** @param array<Mutator> $mutators */
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

    /** @return array<Mutator> */
    public static function defaultMutators(): array
    {
        return array(
            Closure::fromCallable([SqlAstLiteral::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstColumn::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstOperation::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstConjunction::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstWhere::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstHaving::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstGroupBy::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstOrderBy::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstFunctionCall::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstParenthesis::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstFrom::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstJoin::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstSelect::class, 'mutateAstNode']),
            Closure::fromCallable([SqlAstUpdate::class, 'mutateAstNode']),
        );
    }

    public function parseSql(
        string $sql,
        array $expectedResultTypes = array(),
        array $validationCallbacks = array()
    ): SqlAstRoot {
        /** @var SqlTokens $tokens */
        $tokens = $this->tokenizer->tokenize($sql);

        $tokens = $tokens->withoutWhitespace();
        $tokens = $tokens->withoutComments();

        /** @var SqlAstRoot $syntaxTree */
        $syntaxTree = $tokens->convertToSyntaxTree();

        do {
            /** @var string $hashBefore */
            $hashBefore = $syntaxTree->hash();

            /** @var Mutator $mutator */
            foreach ($this->mutators as $mutator) {
                $syntaxTree->mutate([$mutator]);
            }
        } while ($hashBefore !== $syntaxTree->hash());

        if (!empty($expectedResultTypes) || !empty($validationCallbacks)) {
            /** @var SqlAstNode $detectedNode */
            foreach ($syntaxTree->children() as $detectedNode) {
                if (!empty($validationCallbacks)) {
                    $detectedNode->walk($validationCallbacks);
                }

                /** @var string $expectedClass */
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
        }

        return $syntaxTree;
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
