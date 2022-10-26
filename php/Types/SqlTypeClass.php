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

namespace Addiks\StoredSQL\Types;

use Addiks\StoredSQL\Types\SqlType;

class SqlTypeClass implements SqlType
{

    public function __construct(
        private string $name
    ) {
    }
    
    public static function fromName(string $name): SqlType
    {
        # TODO: When more types are implemented, chose correct class here.
        return new SqlTypeClass($name);
    }

    public function name(): string
    {
        return $this->name;
    }
}
