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

final class SchemaClass implements Schema
{
    /** @var array<string, Table> */
    private array $tables = array();

    /** @param array<array-key, Table> $tables */
    public function __construct(
        private string $name,
        array $tables = array()
    ) {
        foreach ($tables as $table) {
            $this->addTable($table);
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return array<Table> */
    public function tables(): array
    {
        return $this->tables;
    }

    /** @return array<Column> */
    public function allColumns(): array
    {
        /** @var array<Column> $allColumns */
        $allColumns = array();

        /** @var Table $table */
        foreach ($this->tables as $table) {
            $allColumns = array_merge($allColumns, $table->columns());
        }

        return $allColumns;
    }

    public function addTable(Table $table): void
    {
        $this->tables[$table->name()] = $table;
    }
}
