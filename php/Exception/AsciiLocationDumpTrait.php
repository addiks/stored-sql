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

trait AsciiLocationDumpTrait
{
    abstract public function sql(): string;

    abstract public function sqlLine(): int;

    abstract public function sqlOffset(): int;

    public function asciiLocationDump(): string
    {
        /** @var non-empty-array<string> $lines */
        $lines = explode("\n", $this->sql());

        # \u{219X} are unicode arrow-characters

        /** @var int $sqlLine */
        $sqlLine = $this->sqlLine();

        /** @var int $sqlOffset */
        $sqlOffset = $this->sqlOffset();

        /** @var int $longestLength */
        $longestLength = max(array_map('strlen', $lines));

        foreach ($lines as $lineIndex => &$line) {
            if ($lineIndex === $sqlLine) {
                $line = " \u{2192} " . $line . str_pad('', $longestLength - strlen($line), ' ') . " \u{2190}";

            } else {
                $line = '   ' . $line;
            }
        }

        return sprintf(
            "\n\n%s\n%s\n%s\n",
            str_pad("\u{2193}", $sqlOffset + 6, ' ', STR_PAD_LEFT),
            implode("\n", $lines),
            str_pad("\u{2191}", $sqlOffset + 6, ' ', STR_PAD_LEFT)
        );
    }
}
