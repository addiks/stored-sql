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
    /** @var array<int, string>|null */
    private ?array $foreignKeyRef = null;

    public function __construct(
        private Table $table,
        private string $name,
        private SqlType $type,
        private bool $nullable,
        private bool $unique,
        ?Column $foreignKey = null
    ) {
        $table->addColumn($this);

        if (is_object($foreignKey)) {
            $this->foreignKeyRef = [
                $foreignKey->table()->schema()->name(),
                $foreignKey->table()->name(),
                $foreignKey->name(),
            ];
        }
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

    public function foreignKey(): ?Column
    {
        if (is_null($this->foreignKeyRef)) {
            return null;
        }

        return $this->table->schema()->schemas()
            ->schema($this->foreignKeyRef[0])
            ?->table($this->foreignKeyRef[1])
            ?->column($this->foreignKeyRef[2]);
    }

    public function defineForeignKey(Column|null $foreignKey): void
    {
        if (is_object($foreignKey)) {
            $this->foreignKeyRef = [
                $foreignKey->table()->schema()->name(),
                $foreignKey->table()->name(),
                $foreignKey->name(),
            ];

        } else {
            $this->foreignKeyRef = null;
        }
    }
}
