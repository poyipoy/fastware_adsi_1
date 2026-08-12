<?php

namespace App\Http\Middleware;

use App\Enums\ProcurementMenuAccessGroup;
use App\Services\OutstandingMaterialAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function __construct(
        ?OutstandingMaterialAccessService $outstandingMaterialAccess = null,
    ) {
        $this->outstandingMaterialAccess = $outstandingMaterialAccess
            ?? new OutstandingMaterialAccessService();
    }

    private readonly OutstandingMaterialAccessService $outstandingMaterialAccess;

    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $userName = (string) $user->getAttribute('name');
        $enumRoles = [];
        $nameRoles = [];

        foreach ($roles as $role) {
            $enumRole = ProcurementMenuAccessGroup::tryFrom((string) $role);
            if ($enumRole) {
                $enumRoles[] = $enumRole;
                continue;
            }

            $nameRoles[] = (string) $role;
        }

        $hasAccess = false;

        foreach ($enumRoles as $enumRole) {
            $allowed = $enumRole === ProcurementMenuAccessGroup::OUTSTANDING_MATERIAL
                ? $this->outstandingMaterialAccess->canView($user)
                : $enumRole->hasAccess($userName);

            if ($allowed) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess && !empty($nameRoles)) {
            $normalizedUser = strtoupper(trim($userName));

            foreach ($nameRoles as $roleName) {
                if ($normalizedUser === strtoupper(trim($roleName))) {
                    $hasAccess = true;
                    break;
                }
            }
        }

        if (!$hasAccess) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
