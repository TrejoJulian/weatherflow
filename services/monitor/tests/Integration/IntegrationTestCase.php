<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Feature\RefreshMongoCollections;
use Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    use RefreshMongoCollections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collectionsToClean = ['measurements'];
        $this->cleanCollections();
    }
}
