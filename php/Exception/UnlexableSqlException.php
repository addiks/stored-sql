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

use Exception;

final class UnlexableSqlException extends Exception
{
    use AsciiLocationDumpTrait;

    private string $sql;

    private int $sqlLine;

    private int $sqlOffset;

    public function __construct(string $sql, int $line, int $offset)
    {
        $this->sql = $sql;
        $this->sqlLine = $line;
        $this->sqlOffset = $offset;

        parent::__construct(sprintf(
            'There was an error while lexing the given SQL code at line %d, offset %d!',
            $line + 1,
            $offset
        ));
    }

    public function __toString(): string
    {
        return parent::__toString() . $this->asciiLocationDump();
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
