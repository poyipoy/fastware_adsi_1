<?php

namespace Tests\Feature\Warehouse;

use Illuminate\Routing\Middleware\ThrottleRequests;

class WarehouseSecurityTest extends WarehouseTestCase
{
    public function test_scan_routes_have_rate_limit_and_mutation_routes_are_csrf_protected(): void
    {
        $routes = app('router')->getRoutes();
        $item = $routes->getByName('warehouse.scans.item');
        $user = $routes->getByName('warehouse.scans.user');
        $mutation = $routes->getByName('warehouse.transactions.store');

        self::assertNotNull($item);
        self::assertTrue(collect($item->middleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'throttle:warehouse-scan')));
        self::assertTrue(collect($user->middleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'throttle:warehouse-scan')));
        self::assertTrue(collect($mutation->middleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'throttle:warehouse-mutation')));
        self::assertTrue(collect($mutation->middleware())->contains('web'));
        self::assertTrue(class_exists(ThrottleRequests::class));
    }
}
