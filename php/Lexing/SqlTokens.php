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

use IteratorAggregate;
use ArrayAccess;
use Addiks\StoredSQL\Lexing\SqlTokenInstance;

/** @extends IteratorAggregate<int, SqlTokenInstance> */
interface SqlTokens extends IteratorAggregate, ArrayAccess
{

    public function withoutWhitespace(): SqlTokens;

    public function withoutComments(): SqlTokens;

    public function sql(): string;

}
