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

use Addiks\StoredSQL\Schema\Factories\SchemasFactory;
use Addiks\StoredSQL\Schema\Factories\SchemasFromMySQLInformationSchemaReader;
use Addiks\StoredSQL\Schema\Factories\SchemasFromSqliteReader;
use PDO;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\Assert;

final class SchemasClass implements Schemas
{
    /** @var array<string, Schema> */
    private array $schemas = array();

    private string|null $defaultSchemaName = null;

    public function __construct(
        array $schemas = array(),
        ?Schema $defaultSchema = null
    ) {
        foreach ($schemas as $schema) {
            Assert::isInstanceOf($schema, Schema::class);

            $this->schemas[$schema->name()] = $schema;
        }

        if (is_object($defaultSchema)) {
            Assert::oneOf($defaultSchema, $this->schemas);

            $this->defaultSchemaName = $defaultSchema->name();
        }
    }

    public static function fromPDO(
        PDO $pdo,
        ?CacheInterface $cache = null,
        ?SchemasFactory $factory = null
    ): Schemas {
        if (is_null($factory)) {
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $factory = new SchemasFromSqliteReader($pdo);

            } else {
                $factory = new SchemasFromMySQLInformationSchemaReader($pdo);
            }
        }

        if (is_object($cache)) {
            $cacheKey = self::class . ':' . $factory->cacheKey();
            $cacheKey = preg_replace('/[\{\}\(\)\\\\\@\:]+/is', '_', $cacheKey);

            /** @var string|null $serializedSchemas */
            $serializedSchemas = $cache->get($cacheKey);

            if (!empty($serializedSchemas)) {
                /** @var Schemas|null $schemas */
                $schemas = unserialize($serializedSchemas);

                Assert::isInstanceOf($schemas, Schemas::class, 'Invalid cache contents!');

            } else {
                /** @var Schemas $schemas */
                $schemas = $factory->createSchemas();

                $cache->set($cacheKey, serialize($schemas));
            }

        } else {
            /** @var Schemas $schemas */
            $schemas = $factory->createSchemas();
        }

        return $schemas;
    }

    public function schemas(): array
    {
        return $this->schemas;
    }

    public function schema(string $schemaName): ?Schema
    {
        return $this->schemas[$schemaName] ?? null;
    }

    public function defaultSchema(): Schema|null
    {
        return $this->schemas[$this->defaultSchemaName] ?? null;
    }

    public function addSchema(Schema $schema): void
    {
        $this->schemas[$schema->name()] = $schema;
    }

    public function defineDefaultSchema(Schema $schema): void
    {
        $this->defaultSchemaName = $schema->name();
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
        /** @var array<Column> $allColumns */
        $allColumns = array();

        /** @var Schema $schema */
        foreach ($this->schemas as $schema) {
            $allColumns = array_merge($allColumns, $schema->allColumns());
        }

        return $allColumns;
    }
}
