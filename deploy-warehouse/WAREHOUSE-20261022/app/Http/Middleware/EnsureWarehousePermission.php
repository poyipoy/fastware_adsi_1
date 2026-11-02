<?php

namespace App\Http\Middleware;

use App\Services\Warehouse\WarehouseAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWarehousePermission
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        abort_unless(app(WarehouseAccessService::class)->can($request->user(), $ability), 403);

        return $next($request);
    }
}
