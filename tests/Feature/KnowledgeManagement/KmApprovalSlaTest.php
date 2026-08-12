<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmApprovalEvent;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmApprovalService;
use App\Services\KnowledgeManagement\KmDocumentQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class KmApprovalSlaTest extends KmTestCase
{
    protected function tearDown(): void
    {
        Date::setTestNow();
        parent::tearDown();
    }

    public function test_working_day_age_excludes_submission_day_and_weekends(): void
    {
        $service = app(KmApprovalService::class);

        $this->assertSame(4, $service->workingDaysSince(
            CarbonImmutable::parse('2026-07-20 10:00:00'),
            CarbonImmutable::parse('2026-07-24 08:00:00'),
        ));
        $this->assertSame(1, $service->workingDaysSince(
            CarbonImmutable::parse('2026-07-17 10:00:00'),
            CarbonImmutable::parse('2026-07-20 08:00:00'),
        ));
    }

    public function test_due_reminders_and_overdue_notifications_are_idempotent(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2026-07-24 08:00:00'));
        [$approver, $owner] = $this->users();
        $document = $this->pendingDocument($owner, '2026-07-20 10:00:00');
        $service = app(KmApprovalService::class);

        $first = $service->generateDueReminders();
        $second = $service->generateDueReminders();

        $this->assertSame(1, $first['documents']);
        $this->assertSame(1, $second['documents']);
        $this->assertSame(2, DB::table('km_notifications')->count());
        $this->assertDatabaseHas('km_notifications', [
            'user_id' => $approver->getKey(),
            'type' => 'approval_reminder',
        ]);
        $this->assertDatabaseHas('km_notifications', [
            'user_id' => $approver->getKey(),
            'type' => 'approval_overdue',
        ]);

        $this->assertSame(0, Artisan::call('km:send-approval-reminders', ['--json' => true]));
        $this->assertSame(2, DB::table('km_notifications')->count());
        $this->assertStringContainsString('"documents": 1', Artisan::output());
        $this->assertSame($document->getKey(), (int) DB::table('km_approval_events')->value('km_pengajuan_id'));
    }

    public function test_approval_queue_sorts_server_side_and_exposes_overdue_state(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2026-07-24 08:00:00'));
        [$approver, $owner] = $this->users();
        $older = $this->pendingDocument($owner, '2026-07-20 10:00:00');
        $newer = $this->pendingDocument($owner, '2026-07-22 10:00:00');
        $documents = app(KmDocumentQueryService::class);

        $oldestFirst = $documents->paginateApprovals('oldest');
        $this->assertSame($older->getKey(), $oldestFirst->first()->getKey());
        $this->assertTrue((bool) $oldestFirst->first()->approval_overdue);
        $this->assertSame(4, (int) $oldestFirst->first()->waiting_working_days);

        $newestFirst = $documents->paginateApprovals('newest');
        $this->assertSame($newer->getKey(), $newestFirst->first()->getKey());
        $this->assertFalse((bool) $newestFirst->first()->approval_overdue);

        $this->actingAs($approver)
            ->get(route('persetujuanKM', ['sort' => 'oldest']))
            ->assertOk()
            ->assertSee('Terlambat')
            ->assertSee('aria-sort="ascending"', false);
    }

    public function test_approval_queue_rejects_invalid_or_unknown_query_parameters(): void
    {
        [$approver] = $this->users();

        $this->actingAs($approver)
            ->get(route('persetujuanKM', ['sort' => 'unsupported']))
            ->assertSessionHasErrors('sort');

        $this->actingAs($approver)
            ->get(route('persetujuanKM', ['status' => 'pending']))
            ->assertSessionHasErrors('query');
    }

    /** @return array{User, User} */
    private function users(): array
    {
        $approver = User::factory()->create([
            'name' => 'HRGA Legal SLA Approver',
            'role_id' => null,
            'is_active' => false,
        ]);
        $this->grantKmApprovalAccess($approver);

        return [
            $approver,
            User::factory()->create([
                'name' => 'SLA Owner',
                'role_id' => 99,
                'is_active' => false,
            ]),
        ];
    }

    private function pendingDocument(User $owner, string $submittedAt): KmPengajuan
    {
        $document = KmPengajuan::factory()->pending()->create([
            'id_user' => $owner->getKey(),
            'posisi' => 'All Employee',
        ]);
        KmApprovalEvent::query()->create([
            'km_pengajuan_id' => $document->getKey(),
            'actor_id' => $owner->getKey(),
            'actor_name' => $owner->name,
            'actor_role_snapshot' => 'Employee',
            'action' => KmApprovalAction::SUBMITTED,
            'from_status' => KmDocumentStatus::DRAFT->value,
            'to_status' => KmDocumentStatus::PENDING_APPROVAL->value,
            'acted_at' => $submittedAt,
        ]);

        return $document;
    }
}
