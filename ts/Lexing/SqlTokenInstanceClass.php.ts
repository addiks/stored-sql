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

namespace Addiks\StoredSQL\Lexing;

final class SqlTokenInstanceClass implements SqlTokenInstance
{
    private string $code;

    private AbstractSqlToken $token;

    private int $line;

    private int $offset;

    public function __construct(string $code, AbstractSqlToken $token, int $line, int $offset)
    {
        $this->code = $code;
        $this->token = $token;
        $this->line = $line;
        $this->offset = $offset;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function token(): AbstractSqlToken
    {
        return $this->token;
    }

    public function is(AbstractSqlToken $token): bool
    {
        return $this->token === $token;
    }

    public function isCode(string $code): bool
    {
        return $this->code === $code;
    }

    public function line(): int
    {
        return $this->line;
    }

    public function offset(): int
    {
        return $this->offset;
    }
}
