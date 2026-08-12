<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\DashboardMenuItem;
use App\Models\KmPengajuan;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Services\HRMenuService;
use App\Services\KnowledgeManagement\KmAccessService;
use Illuminate\Support\Facades\Gate;

final class KmAccessControlTest extends KmTestCase
{
    public function test_all_authenticated_users_can_open_dashboard_and_authoring_menu(): void
    {
        $employee = $this->user(1501, 'Ordinary Employee');

        $this->assertTrue(app(KmAccessService::class)->canCreate($employee));
        $this->assertTrue(Gate::forUser($employee)->allows('create', KmPengajuan::class));
        $this->assertTrue(
            DashboardMenuItem::KNOWLEDGE_MANAGEMENT->toArray($employee->role_id, $employee->name)['visible'],
        );

        $hrMenu = app(HRMenuService::class)->getMenuStructure($employee);
        $this->assertTrue($hrMenu['show_main_menu']);
        $this->assertTrue($hrMenu['knowledge_management']['show_form']);
        $this->assertFalse($hrMenu['knowledge_management']['show_approval']);

        $this->actingAs($employee)->get(route('dsKnowlege'))->assertOk();
        $this->actingAs($employee)
            ->get(route('pengajuanKM'))
            ->assertOk()
            ->assertSee('id="navbarDropdown2"', false)
            ->assertSee('href="'.route('dsKnowlege').'"', false)
            ->assertDontSee('href="'.route('dashboardTCPD').'"', false)
            ->assertDontSee('href="'.route('feedback.dashboard').'"', false)
            ->assertDontSee('href="'.route('bopm.dashboard.index').'"', false)
            ->assertDontSee('href="'.route('salesvisit.dashboard').'"', false);
    }

    public function test_approval_requires_exact_active_position_or_siti_user_id(): void
    {
        $access = app(KmAccessService::class);
        $positionApprover = $this->grantKmApprovalAccess($this->user(1502, 'Position Approver'));
        $inactiveAssignment = $this->grantKmApprovalAccess(
            $this->user(1503, 'Inactive Assignment'),
            assignmentActive: false,
        );
        $nearMatch = $this->grantKmApprovalAccess(
            $this->user(1504, 'Near Match'),
            positionName: 'HRGA Staff',
        );
        $departmentHead = $this->grantKmApprovalAccess(
            $this->user(1506, 'HRGA Department Head'),
            positionName: 'Finance, Accounting & HRGA Dept.Head',
        );
        $sameNameDifferentId = $this->user(1505, 'SITI MARIA ULFA');
        $siti = $this->user(91, 'SITI MARIA ULFA');
        $administrator = $this->user(1, 'ADMINSTRATOR');
        $futureApprover = $this->grantKmApprovalAccess($this->user(1507, 'Future Approver'));
        $expiredApprover = $this->grantKmApprovalAccess($this->user(1508, 'Expired Approver'));
        $missingPeriodApprover = $this->grantKmApprovalAccess($this->user(1509, 'Missing Period Approver'));
        UserJobPosition::query()->where('user_id', $futureApprover->getKey())->update([
            'effective_from' => today()->addDay(),
        ]);
        UserJobPosition::query()->where('user_id', $expiredApprover->getKey())->update([
            'effective_from' => today()->subDays(2),
            'effective_until' => today()->subDay(),
        ]);
        UserJobPosition::query()->where('user_id', $missingPeriodApprover->getKey())->update([
            'effective_from' => null,
        ]);

        $this->assertTrue($access->canApprove($positionApprover));
        $this->assertTrue($access->canApprove($siti));
        $this->assertFalse($access->canApprove($inactiveAssignment));
        $this->assertFalse($access->canApprove($nearMatch));
        $this->assertFalse($access->canApprove($departmentHead));
        $this->assertFalse($access->canApprove($sameNameDifferentId));
        $this->assertFalse($access->canApprove($administrator));
        $this->assertFalse($access->canApprove($futureApprover));
        $this->assertFalse($access->canApprove($expiredApprover));
        $this->assertFalse($access->canApprove($missingPeriodApprover));

        $this->assertTrue(
            app(HRMenuService::class)
                ->getMenuStructure($positionApprover)['knowledge_management']['show_approval'],
        );
        $this->assertFalse(
            app(HRMenuService::class)
                ->getMenuStructure($administrator)['knowledge_management']['show_approval'],
        );

        MstJobPosition::query()
            ->where('position_name', 'HRGA & Legal Staff')
            ->update(['is_active' => false]);

        $this->assertFalse($access->canApprove($positionApprover));
    }

    public function test_eligible_approvers_only_include_login_enabled_exact_matches_without_duplicates(): void
    {
        $positionApprover = $this->user(1510, 'Login Enabled Approver', false);
        $blockedApprover = $this->user(1511, 'Blocked Approver', true);
        $siti = $this->user(91, 'SITI MARIA ULFA', false);
        $unrelated = $this->user(1512, 'Unrelated Employee', false);

        $this->grantKmApprovalAccess($positionApprover);
        $this->grantKmApprovalAccess($blockedApprover);
        $this->grantKmApprovalAccess($siti);

        $ids = app(KmAccessService::class)->eligibleApproverIds();

        $this->assertEqualsCanonicalizing([$positionApprover->getKey(), $siti->getKey()], $ids);
        $this->assertCount(2, $ids);
        $this->assertNotContains($blockedApprover->getKey(), $ids);
        $this->assertNotContains($unrelated->getKey(), $ids);
    }

    public function test_legacy_oversight_keeps_non_approval_rights_but_cannot_open_pending_workflow(): void
    {
        $oversightUser = $this->user(1520, 'MUGI PRAMONO');
        $owner = $this->user(1521, 'Pending Owner');
        $pending = KmPengajuan::factory()->pending()->for($owner, 'user')->create();
        $published = KmPengajuan::factory()->published()->for($owner, 'user')->create();
        $access = app(KmAccessService::class);

        $this->assertTrue($access->canAccessKnowledgeOversight($oversightUser));
        $this->assertFalse($access->canApprove($oversightUser));
        $this->assertTrue(Gate::forUser($oversightUser)->allows('viewPopularAnalytics', KmPengajuan::class));
        $this->assertTrue(Gate::forUser($oversightUser)->allows('moderateInsights', $published));
        $this->assertTrue(Gate::forUser($oversightUser)->allows('deactivate', $published));

        $this->actingAs($oversightUser)->get(route('persetujuanKM'))->assertForbidden();
        $this->actingAs($oversightUser)
            ->getJson(route('showPersetujuan', $pending))
            ->assertForbidden();
    }

    private function user(int $id, string $name, bool $isActive = false): User
    {
        return User::factory()->create([
            'id' => $id,
            'name' => $name,
            'role_id' => null,
            'is_active' => $isActive,
            'km_total_poin' => 0,
        ]);
    }
}
