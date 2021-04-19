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

use Addiks\StoredSQL\Lexing\SqlTokenizer;
use Addiks\StoredSQL\Lexing\SqlTokenizerClass;
use Addiks\StoredSQL\Lexing\SqlTokens;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstColumn;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstConjunction;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstOperation;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstRoot;
use Closure;
use Webmozart\Assert\Assert;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstLiteral;

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
        );
    }

    public function parseSql(string $sql): array
    {
        /** @var SqlTokens $tokens */
        $tokens = $this->tokenizer->tokenize($sql);

        $tokens = $tokens->withoutWhitespace();
        $tokens = $tokens->withoutComments();

        /** @var SqlAstRoot $syntaxTree */
        $syntaxTree = $tokens->convertToSyntaxTree();

        $syntaxTree->walk($this->mutators);

        /** @var array<SqlAstNode> $detectedContent */
        $detectedContent = $syntaxTree->children();

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
