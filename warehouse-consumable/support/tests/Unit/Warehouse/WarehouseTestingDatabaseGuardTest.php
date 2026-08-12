<?php

namespace Tests\Unit\Warehouse;

use Tests\Support\Concerns\GuardsWarehouseTestingDatabase;
use Tests\TestCase;

class WarehouseTestingDatabaseGuardTest extends TestCase
{
    use GuardsWarehouseTestingDatabase;

    public function test_phpunit_testing_database_is_accepted(): void
    {
        $this->guardWarehouseTestingDatabase();

        $this->assertTrue(true);
    }

    public function test_non_testing_environment_is_rejected(): void
    {
        $this->expectExceptionMessage('APP_ENV=testing');

        $this->guardWarehouseTestingDatabase('local', 'fastware_adsi_1_testing');
    }

    public function test_database_without_testing_suffix_is_rejected(): void
    {
        $this->expectExceptionMessage('ending in _testing');

        $this->guardWarehouseTestingDatabase('testing', 'dms_adasi_rev1');
    }
}
