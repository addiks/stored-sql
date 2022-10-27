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

namespace Addiks\StoredSQL\Tests\Behaviour;

use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstJoin;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstNode;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstSelect;
use Addiks\StoredSQL\AbstractSyntaxTree\SqlAstWhere;
use Addiks\StoredSQL\Exception\UnlexableSqlException;
use Addiks\StoredSQL\Exception\UnparsableSqlException;
use Addiks\StoredSQL\Parsing\SqlParser;
use Addiks\StoredSQL\Parsing\SqlParserClass;
use PHPUnit\Framework\TestCase;

final class ParseSqlTest extends TestCase
{
    /** @test */
    public function shouldParseSelectSql(): void
    {
        /** @var SqlParser $parser */
        $parser = SqlParserClass::defaultParser();

        try {
            /** @var array<SqlAstNode> $detectedContent */
            $detectedContent = $parser->parseSql("
                SELECT u.name, u.email, f.name, f.size
                FROM users u
                LEFT JOIN files f ON(u.id = f.owner_id)
                WHERE f.name LIKE '%.pdf'
                AND f.type = 'symbolic'
                OR f.foo IS NULL
                ORDER BY f.size DESC, f.owner ASC
            ", [SqlAstSelect::class]);

        } catch (UnparsableSqlException $exception) {
            echo $exception->asciiLocationDump();

            throw $exception;

        } catch (UnlexableSqlException $exception) {
            echo $exception->asciiLocationDump();

            throw $exception;
        }

        $this->assertEquals(1, count($detectedContent));

        /** @var SqlAstSelect $select */
        $select = $detectedContent[0];

        $this->assertTrue($select instanceof SqlAstSelect);

        /** @var string $regeneratedSql */
        $regeneratedSql = $select->toSql();

        #var_dump($regeneratedSql);
    }

    /** @test */
    public function shouldParseConditionalSql(): void
    {
        /** @var SqlParser $parser */
        $parser = SqlParserClass::defaultParser();

        try {
            /** @var array<SqlAstNode> $detectedContent */
            $detectedContent = $parser->parseSql("
                LEFT JOIN files f ON(u.id = f.owner_id)
                WHERE f.name LIKE '%.pdf'
                AND f.type = 'symbolic'
                OR f.foo IS NULL
            ", [SqlAstJoin::class, SqlAstWhere::class]);

        } catch (UnparsableSqlException $exception) {
            echo $exception->asciiLocationDump();

            throw $exception;

        } catch (UnlexableSqlException $exception) {
            echo $exception->asciiLocationDump();

            throw $exception;
        }

        $this->assertEquals(2, count($detectedContent));
        $this->assertTrue($detectedContent[0] instanceof SqlAstJoin);
        $this->assertTrue($detectedContent[1] instanceof SqlAstWhere);
    }
}
