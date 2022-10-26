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

use Addiks\StoredSQL\Schema\Schema; 
use Addiks\StoredSQL\Schema\Table;
use Addiks\StoredSQL\Schema\Column;
use Webmozart\Assert\Assert;
use PDO;
use Addiks\StoredSQL\Schema\Factories\SchemasFromMySQLInformationSchemaReader;
use Psr\SimpleCache\CacheInterface;
use Addiks\StoredSQL\Schema\Factories\SchemasFactory;

final class SchemasClass implements Schemas
{
    /** @var array<string, Schema> */
    private array $schemas;
    
    private string $defaultSchemaName;

    public function __construct(
        array $schemas,
        Schema $defaultSchema
    ) {
        foreach ($schemas as $schema) {
            Assert::isInstanceOf($schema, Schema::class);
            
            $this->schemas[$schema->name()] = $schema;
        }
        
        $this->defaultSchemaName = $defaultSchema->name();
        
        Assert::oneOf($this->defaultSchemaName, array_keys($this->schemas));
    }
    
    public static fromPDO(
        PDO $pdo, 
        ?CacheInterface $cache = null,
        ?SchemasFactory $factory = null
    ): Schemas
    {
        /** @var Schemas|null $schemas */
        $schemas = null;
        
        if (is_null($factory)) {
            # TODO: Select actually correct reader here ...
            $factory = new SchemasFromMySQLInformationSchemaReader($pdo);
        }
        
        if (is_object($cache)) {
            $cacheKey = self::class . ':' . $factory->cacheKey();
            
            /** @var string|null $serializedSchemas*/
            $serializedSchemas = $cache->get($cacheKey);
            
            if (!empty($serializedSchemas)) {
                $schemas = unserialize($serializedSchemas);
                
                Assert::isInstanceOf($schemas, Schemas::class, 'Invalid cache contents!');
                
            } else {
                $schemas = $factory->createSchemas();
                
                $cache->set($cacheKey, serialize($schemas));
            }

        } else {
            $schemas = $factory->createSchemas();
        }
        
        return $schemas;
    }

    public function schemas(): array
    {
        return $this->schemas;
    }

    public function defaultSchema(): Schema
    {
        return $this->schemas[$this->defaultSchemaName];
    }

    public function allTables(): array
    {
        /** @var array<Table> $allTables */
        $allTables = array();
        
        /** @var Schema $schema */
        foreach ($this->schemas as $schema) {
            $allTables = array_merge($allTables, $schema->tables());
        }
        
        return $allTables;
    }

    public function allColumns(): array
    {
        /** @var array<Table> $allColumns */
        $allColumns = array();
        
        /** @var Schema $schema */
        foreach ($this->schemas as $schema) {
            $allColumns = array_merge($allColumns, $schema->allColumns());
        }
        
        return $allColumns;
    }
}
