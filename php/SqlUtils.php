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

namespace Addiks\StoredSQL;

/**
 * Yeah, yeah, I know. Yet another stupid util class ...
 * Feel free to optimize this away.
 */
final class SqlUtils
{
    private function __construct()
    {
    }

    public static function unquote(string $sql): string
    {
        if ($sql[0] === '`' && $sql[-1] === '`') {
            $sql = substr($sql, 1, -1);
        }

        return $sql;
    }
}
