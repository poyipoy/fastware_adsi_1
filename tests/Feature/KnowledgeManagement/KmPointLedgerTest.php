<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\KmPointLedgerEntry;
use App\Models\User;
use App\Services\KnowledgeManagement\KmPointLedgerService;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class KmPointLedgerTest extends KmTestCase
{
    public function test_award_requires_transaction_and_duplicate_event_is_idempotent(): void
    {
        $user = User::factory()->create([
            'name' => 'Ledger User',
            'section' => 'Assembly',
            'km_total_poin' => 0,
        ]);
        $service = app(KmPointLedgerService::class);

        try {
            $service->award($user, 'test', 'outside-transaction', 5);
            $this->fail('Award di luar transaction seharusnya ditolak.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }

        $results = DB::transaction(fn (): array => [
            $service->award($user, 'test', 'idempotent-event', 5),
            $service->award($user, 'test', 'idempotent-event', 5),
        ]);

        $this->assertSame([true, false], $results);
        $this->assertSame(1, KmPointLedgerEntry::query()->count());
        $this->assertSame(5, (int) $user->refresh()->km_total_poin);
    }

    public function test_department_leaderboard_masks_small_cohort_and_uses_snapshot(): void
    {
        $service = app(KmPointLedgerService::class);
        $users = User::factory()->count(5)->sequence(
            fn ($sequence): array => [
                'name' => 'Assembly Reader '.($sequence->index + 1),
                'section' => 'Assembly',
                'km_total_poin' => 0,
            ],
        )->create();

        foreach ($users as $index => $user) {
            DB::transaction(fn (): bool => $service->award(
                $user,
                'test',
                'leaderboard:'.$user->getKey(),
                ($index + 1) * 5,
            ));
        }

        $masked = $service->departmentLeaderboard($users->first(), 10, 6);
        $this->assertTrue($masked['insufficient_cohort']);
        $this->assertSame(5, $masked['cohort_size']);
        $this->assertTrue($masked['leaders']->isEmpty());
        $this->assertNull($masked['viewer_rank']);
        $this->assertSame(0, $masked['viewer_points']);

        $visible = $service->departmentLeaderboard($users->first(), 10, 5);
        $this->assertFalse($visible['insufficient_cohort']);
        $this->assertSame('Assembly', $visible['department']);
        $this->assertSame($users->last()->getKey(), (int) $visible['leaders']->first()->user_id);
        $this->assertSame(25, (int) $visible['leaders']->first()->points);
        $this->assertSame(5, $visible['viewer_rank']);
        $this->assertSame(5, $visible['viewer_points']);
    }

    public function test_department_leaderboard_uses_unique_rank_and_handles_viewer_without_ledger(): void
    {
        $service = app(KmPointLedgerService::class);
        $users = User::factory()->count(5)->sequence(
            fn ($sequence): array => [
                'name' => 'Ranked Reader '.($sequence->index + 1),
                'section' => 'Assembly',
                'km_total_poin' => 0,
            ],
        )->create();
        $points = [30, 20, 20, 10, 5];

        foreach ($users as $index => $user) {
            DB::transaction(fn (): bool => $service->award(
                $user,
                'test',
                'unique-rank:'.$user->getKey(),
                $points[$index],
            ));
        }

        $visible = $service->departmentLeaderboard($users[2], 10, 5);
        $this->assertSame([1, 2, 3, 4, 5], $visible['leaders']->pluck('leaderboard_rank')->all());
        $this->assertSame(3, $visible['viewer_rank']);
        $this->assertSame(20, $visible['viewer_points']);

        $viewerWithoutLedger = User::factory()->create([
            'name' => 'Reader Without Ledger',
            'section' => 'Assembly',
            'km_total_poin' => 0,
        ]);
        $withoutLedger = $service->departmentLeaderboard($viewerWithoutLedger, 10, 5);
        $this->assertFalse($withoutLedger['insufficient_cohort']);
        $this->assertNull($withoutLedger['viewer_rank']);
        $this->assertSame(0, $withoutLedger['viewer_points']);
    }

    public function test_award_rolls_back_ledger_and_cached_points_with_parent_transaction(): void
    {
        $user = User::factory()->create([
            'name' => 'Rollback Ledger User',
            'section' => 'Assembly',
            'km_total_poin' => 0,
        ]);
        $service = app(KmPointLedgerService::class);

        try {
            DB::transaction(function () use ($service, $user): void {
                $service->award($user, 'test', 'rolled-back-award', 25);
                throw new RuntimeException('Paksa rollback ledger.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Paksa rollback ledger.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('km_point_ledger', ['event_key' => 'rolled-back-award']);
        $this->assertSame(0, (int) $user->refresh()->km_total_poin);
    }

    public function test_reconciliation_reports_and_repairs_drift_while_ledger_stays_append_only(): void
    {
        $user = User::factory()->create([
            'name' => 'Drift User',
            'section' => 'Quality',
            'km_total_poin' => 0,
        ]);
        DB::table('km_point_ledger')->insert([
            'user_id' => $user->getKey(),
            'event_type' => 'test',
            'event_key' => 'drift-event',
            'points' => 15,
            'department_snapshot' => 'Quality',
            'created_at' => now(),
        ]);

        $service = app(KmPointLedgerService::class);
        $drift = $service->reconcile();
        $this->assertCount(1, $drift);
        $this->assertSame(-15, $drift->first()['drift']);

        $service->reconcile(true);
        $this->assertSame(15, (int) $user->refresh()->km_total_poin);
        $this->assertTrue($service->reconcile()->isEmpty());

        $entry = KmPointLedgerEntry::query()->sole();
        $this->expectException(LogicException::class);
        $entry->update(['points' => 99]);
    }
}
