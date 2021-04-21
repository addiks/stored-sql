<?php
/**
 * Copyright (C) 2019  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks;

use PHPUnit\Framework\TestCase;
use Addiks\StoredSQL\Parsing\SqlParserClass;
use Addiks\StoredSQL\Parsing\SqlParser;
use Addiks\StoredSQL\Parsing\AbstractSyntaxTree\SqlAstNode;

final class ParseSqlTest extends TestCase
{

    /** @test */
    public function shouldParseSomeSql(): void
    {
        /** @var SqlParser $parser */
        $parser = SqlParserClass::defaultParser();

        /** @var array<SqlAstNode> $detectedContent */
        $detectedContent = $parser->parseSql("
            SELECT u.name, u.email, f.name, f.size
            FROM users u
            LEFT JOIN files f ON(u.id = f.owner_id)
            WHERE f.name LIKE '%.pdf'
            AND f.type = 'symbolic'
            OR f.foo IS NULL
            ORDER BY f.size DESC, f.owner ASC
        ");

        var_dump($detectedContent);
    }

}
