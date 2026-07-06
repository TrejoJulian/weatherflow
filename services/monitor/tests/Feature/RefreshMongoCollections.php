<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;

trait RefreshMongoCollections
{
    protected function cleanCollections(): void
    {
        $databaseName = DB::connection('mongodb')->getDatabaseName();

        // With a cached config (php artisan optimize) the phpunit.xml overrides
        // never apply and the tests would silently point at the real database.
        if (! str_ends_with($databaseName, '_test')) {
            throw new RuntimeException(
                "Refusing to drop collections from '{$databaseName}': tests must run against a '*_test' database. "
                .'Run `php artisan config:clear` and retry.',
            );
        }

        foreach ($this->collectionsToClean as $collection) {
            DB::connection('mongodb')->getCollection($collection)->drop();
        }
    }
}