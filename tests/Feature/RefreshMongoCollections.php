<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

trait RefreshMongoCollections
{
    protected function cleanCollections(): void
    {
        foreach ($this->collectionsToClean as $collection) {
            DB::connection('mongodb')->getCollection($collection)->drop();
        }
    }
}