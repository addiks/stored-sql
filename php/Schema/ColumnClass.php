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

namespace Addiks\StoredSQL\Schema;

use Addiks\StoredSQL\Types\SqlType;

final class ColumnClass implements Column
{
    public function __construct(
        private Table $table,
        private string $name,
        private SqlType $type,
        private bool $nullable,
        private bool $unique
    ) {
        $table->addColumn($this);
    }

    public function table(): Table
    {
        return $this->table;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): SqlType
    {
        return $this->type;
    }

    public function unique(): bool
    {
        return $this->unique;
    }

    public function nullable(): bool
    {
        return $this->nullable;
    }

    public function fullName(): string
    {
        return implode('.', [
            $this->table->schema()->name(),
            $this->table->name(),
            $this->name,
        ]);
    }
}
