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

namespace Addiks\StoredSQL\Schema;

use Addiks\StoredSQL\Schema\Table;
use Addiks\StoredSQL\Schema\Schema;

final class TableClass implements Table
{
    /** @var array<string, Column> */
    private array $columns;
    
    /** @param array<array-key, Column> $columns */
    public function __construct(
        private Schema $schema,
        private string $name,
        array $columns = array()
    ) {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
        
        $schema->addTable($this);
    }

    public function schema(): Schema
    {
        return $this->schema;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function addColumn(Column $column): void
    {
        $this->columns[$column->name()] = $column;
    }
}
