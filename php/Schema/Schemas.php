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

interface Schemas
{
    /** @return array<Schema> */
    public function schemas(): array;
    
    public function addSchema(Schema $schema): void;

    public function defaultSchema(): Schema|null;

    public function schema(string $schemaName): ?Schema;

    /** @return array<Table> */
    public function allTables(): array;

    /** @return array<Column> */
    public function allColumns(): array;
}
