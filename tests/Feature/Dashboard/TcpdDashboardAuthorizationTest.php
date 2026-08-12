<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Services\Dashboard\TcpdDashboardService;
use App\Services\DashboardMenuService;
use App\Services\HR\HRRoleAccessService;
use App\Services\HR\TcpdDashboardAccessService;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TcpdDashboardAuthorizationTest extends TestCase
{
    public function test_ordinary_employee_is_forbidden_from_every_web_tcpd_route(): void
    {
        $this->bindAccess(canView: false);
        $user = $this->user(5001);

        $getRoutes = [
            'dashboardTCPD',
            'dashboardTCPD.data',
            'dashboardTCPD.companyData',
            'dashboardTCPD.sensitiveData',
            'dashboardTCPD.export',
            'dashboardTCPD.companyExport',
            'dashboardTCPD.exportAll',
            'dashboardTCPD.exportEmployees',
            'dashboardTCPD.exportTopJobs',
            'dashboardTCPD.exportCriticalFocus',
        ];

        foreach ($getRoutes as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertForbidden();
        }

        $this->actingAs($user)
            ->post(route('dashboardTCPD.clearCache'))
            ->assertForbidden();
    }

    public function test_ordinary_employee_is_forbidden_from_every_api_tcpd_route(): void
    {
        $this->bindAccess(canView: false);
        Sanctum::actingAs($this->user(5002));

        foreach ([
            '/api/dashboard/tcpd',
            '/api/dashboard/tcpd/company',
            '/api/dashboard/tcpd/sensitive',
            '/api/dashboard/tcpd/job',
        ] as $uri) {
            $this->getJson($uri)->assertForbidden();
        }
    }

    public function test_section_head_cannot_clear_cache_or_open_server_driven_page_when_denied(): void
    {
        $user = $this->user(5003);
        $this->bindAccess(canView: true, canClearCache: false);

        $this->actingAs($user)
            ->post(route('dashboardTCPD.clearCache'))
            ->assertForbidden();

        $this->bindAccess(canView: false);
        Sanctum::actingAs($user);

        $this->getJson('/api/pages/dashboardTCPD')->assertForbidden();
    }

    public function test_tcpd_menu_visibility_uses_the_same_access_decision(): void
    {
        $user = $this->user(5004);
        $denied = $this->bindAccess(canView: false);
        $deniedMenu = (new DashboardMenuService($denied))
            ->getMenuStructure(null, $user->name, $user);

        $this->assertFalse(
            collect($deniedMenu['dashboard']['items'])->contains('key', 'dashboard_tcpd'),
        );

        $allowed = $this->bindAccess(canView: true);
        $allowedMenu = (new DashboardMenuService($allowed))
            ->getMenuStructure(null, $user->name, $user);

        $this->assertTrue(
            collect($allowedMenu['dashboard']['items'])->contains('key', 'dashboard_tcpd'),
        );
    }

    public function test_job_position_manipulation_outside_scope_is_forbidden_before_data_query(): void
    {
        $user = $this->user(5005);
        $access = $this->bindAccess(canView: true, jobPositionIds: [101]);
        $service = new TcpdDashboardService($access);

        try {
            $service->getCompetencyPayload(
                Request::create('/dashboard-tcpd/data', 'GET', ['job_position_id' => 999]),
                $user,
            );
            $this->fail('Job position di luar scope seharusnya ditolak.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->actingAs($user)
            ->getJson(route('dashboardTCPD.data', ['job_position_id' => 999]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('dashboardTCPD.export', ['job_position_id' => 999]))
            ->assertForbidden();
    }

    private function bindAccess(
        bool $canView,
        bool $canClearCache = false,
        array $jobPositionIds = [],
    ): StubTcpdDashboardAccessService {
        $service = new StubTcpdDashboardAccessService(
            new HRRoleAccessService(),
            $canView,
            $canClearCache,
            $jobPositionIds,
        );
        $this->app->instance(TcpdDashboardAccessService::class, $service);

        return $service;
    }

    private function user(int $id): User
    {
        $user = new User([
            'name' => "TCPD User {$id}",
            'is_active' => 0,
        ]);
        $user->id = $id;

        return $user;
    }
}

class StubTcpdDashboardAccessService extends TcpdDashboardAccessService
{
    public function __construct(
        HRRoleAccessService $roleAccess,
        private readonly bool $view,
        private readonly bool $clearCache,
        private readonly array $jobPositionIds,
    ) {
        parent::__construct($roleAccess);
    }

    public function scope(?User $user, bool $abort = false): array
    {
        if (! $this->view && $abort) {
            abort(403, 'Anda tidak berhak mengakses Dashboard TCPD.');
        }

        return [
            'can_view' => $this->view,
            'access_class' => $this->view ? 'section_head' : 'denied',
            'user_id' => $user?->getKey() !== null ? (int) $user->getKey() : null,
            'section_ids' => $this->view ? [11] : [],
            'department_ids' => $this->view ? [21] : [],
            'job_position_ids' => $this->view ? $this->jobPositionIds : [],
        ];
    }

    public function canView(?User $user): bool
    {
        return $this->view;
    }

    public function canClearCache(?User $user): bool
    {
        return $this->clearCache;
    }
}
