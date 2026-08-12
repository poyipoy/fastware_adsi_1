<?php

namespace Tests\Support\Concerns;

use LogicException;

trait GuardsWarehouseTestingDatabase
{
    /**
     * Guard every Warehouse test/setup that can create, migrate, or mutate data.
     *
     * The optional arguments make the guard independently testable without
     * changing the process environment or opening a database connection.
     */
    protected function guardWarehouseTestingDatabase(
        ?string $environment = null,
        ?string $database = null,
    ): void {
        $environment ??= (string) app()->environment();
        $database ??= (string) config('database.connections.'.config('database.default').'.database');

        if (strtolower($environment) !== 'testing' || ! str_ends_with(strtolower($database), '_testing')) {
            throw new LogicException(
                'Warehouse destructive setup requires APP_ENV=testing and a database name ending in _testing.'
            );
        }
    }
}
