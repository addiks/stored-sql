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

namespace Addiks\StoredSQL\Lexing;

use Addiks\StoredSQL\Lexing\AbstractSqlToken;

interface SqlTokenInstance
{
    public function code(): string;

    public function token(): AbstractSqlToken;

    public function is(AbstractSqlToken $token): bool;

    public function line(): int;

    public function offset(): int;
}
