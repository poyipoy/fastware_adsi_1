<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\User;
use App\Services\KnowledgeManagement\KmReadingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\KnowledgeManagement\RunsKmWorkers;

final class KmReadingPointIdempotencyTest extends KmTestCase
{
    use RunsKmWorkers;

    public function test_first_completion_awards_category_points_once_and_replay_is_idempotent(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader(['km_total_poin' => 7]);
        $category = KmKategori::factory()->create(['poin_kategori' => 25]);
        $document = $this->publishedDocument($owner, $category);

        $first = $this->actingAs($reader)->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
        ]);

        $first->assertOk()->assertJson([
            'success' => true,
            'already_completed' => false,
            'points_awarded' => 25,
        ]);

        $transaction = KmTransaksi::query()->sole();
        $this->assertSame($reader->getKey(), $transaction->id_user);
        $this->assertSame($document->getKey(), $transaction->id_km_pengajuan);
        $this->assertSame(KmReadStatus::COMPLETED->value, $transaction->status);
        $this->assertSame(25, $transaction->poin);
        $this->assertNotNull($transaction->completed_at);
        $this->assertNotNull($transaction->points_awarded_at);
        $this->assertSame(32, (int) $reader->refresh()->km_total_poin);

        $replay = $this->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
        ]);

        $replay->assertOk()->assertJson([
            'success' => true,
            'already_completed' => true,
            'points_awarded' => 0,
        ]);
        $this->assertSame(1, KmTransaksi::query()->count());
        $this->assertSame(32, (int) $reader->refresh()->km_total_poin);
        $this->assertSame(25, (int) KmTransaksi::query()->value('poin'));
    }

    public function test_historical_completed_transaction_is_never_awarded_again(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader(['km_total_poin' => 80]);
        $category = KmKategori::factory()->create(['poin_kategori' => 40]);
        $document = $this->publishedDocument($owner, $category);
        $historical = KmTransaksi::factory()->completed()->create([
            'id_user' => $reader->getKey(),
            'id_km_pengajuan' => $document->getKey(),
            'poin' => 9,
            'modified_by' => $reader->getKey(),
        ]);
        $awardedAt = $historical->points_awarded_at?->toDateTimeString();

        $response = $this->actingAs($reader)->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'already_completed' => true,
            'points_awarded' => 0,
        ]);
        $this->assertSame(80, (int) $reader->refresh()->km_total_poin);
        $this->assertSame(1, KmTransaksi::query()->count());
        $this->assertSame(9, (int) $historical->refresh()->poin);
        $this->assertSame($awardedAt, $historical->points_awarded_at?->toDateTimeString());
    }

    public function test_mark_started_uses_one_view_counter_and_never_downgrades_completion(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader();
        $category = KmKategori::factory()->create(['poin_kategori' => 12]);
        $document = $this->publishedDocument($owner, $category);

        foreach ([1, 2] as $expectedViews) {
            $response = $this->actingAs($reader)->postJson(route('kmTransaksi.markAsRead'), [
                'id_km_pengajuan' => $document->getKey(),
            ]);

            $response->assertOk()->assertJson([
                'success' => true,
                'already_completed' => false,
                'points_awarded' => 0,
            ]);
            $this->assertSame(1, DB::table('km_lihat_bukus')->count());
            $this->assertSame(
                $expectedViews,
                (int) DB::table('km_lihat_bukus')->value('jumlah_lihat'),
            );
        }

        $this->assertSame(KmReadStatus::READING->value, (int) KmTransaksi::query()->value('status'));

        $this->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
        ])->assertOk()->assertJsonPath('points_awarded', 12);

        $afterCompletion = $this->postJson(route('kmTransaksi.markAsRead'), [
            'id_km_pengajuan' => $document->getKey(),
        ]);

        $afterCompletion->assertOk()->assertJson([
            'success' => true,
            'already_completed' => true,
            'points_awarded' => 0,
        ]);
        $this->assertSame(KmReadStatus::COMPLETED->value, (int) KmTransaksi::query()->value('status'));
        $this->assertSame(1, KmTransaksi::query()->count());
        $this->assertSame(1, DB::table('km_lihat_bukus')->count());
        $this->assertSame(3, (int) DB::table('km_lihat_bukus')->value('jumlah_lihat'));
        $this->assertSame(12, (int) $reader->refresh()->km_total_poin);
    }

    public function test_missing_category_rolls_back_transaction_and_point_changes(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader(['km_total_poin' => 14]);
        $document = KmPengajuan::factory()->published()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => null,
            'posisi' => 'All Employee',
        ]);

        $response = $this->actingAs($reader)->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
        ]);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'already_completed' => false,
            'points_awarded' => 0,
        ]);
        $this->assertStringContainsString(
            'Kategori',
            (string) $response->json('message'),
        );
        $this->assertSame(0, KmTransaksi::query()->count());
        $this->assertSame(14, (int) $reader->refresh()->km_total_poin);
    }

    public function test_completion_rejects_invalid_legacy_read_status_without_awarding_points(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader(['km_total_poin' => 19]);
        $category = KmKategori::factory()->create(['poin_kategori' => 44]);
        $document = $this->publishedDocument($owner, $category);
        $transaction = KmTransaksi::factory()->create([
            'id_user' => $reader->getKey(),
            'id_km_pengajuan' => $document->getKey(),
            'status' => 99,
            'poin' => null,
            'completed_at' => null,
            'points_awarded_at' => null,
            'modified_by' => $reader->getKey(),
        ]);

        $this->actingAs($reader)->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('points_awarded', 0);

        $transaction->refresh();
        $this->assertSame(99, (int) $transaction->status);
        $this->assertNull($transaction->points_awarded_at);
        $this->assertSame(19, (int) $reader->refresh()->km_total_poin);
    }

    public function test_services_recheck_fresh_document_access_after_deactivation(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader(['km_total_poin' => 8]);
        $category = KmKategori::factory()->create(['poin_kategori' => 16]);
        $completionDocument = $this->publishedDocument($owner, $category);
        $staleCompletionDocument = $completionDocument->fresh();
        $completionDocument->forceFill([
            'status' => KmDocumentStatus::INACTIVE->value,
            'persetujuan' => KmDocumentStatus::INACTIVE->legacyApprovalValue(),
        ])->save();

        try {
            app(KmReadingService::class)->complete($reader, $staleCompletionDocument);
            $this->fail('Completion should recheck the locked document state.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $markDocument = $this->publishedDocument($owner, $category);
        $staleMarkDocument = $markDocument->fresh();
        $markDocument->forceFill([
            'status' => KmDocumentStatus::INACTIVE->value,
            'persetujuan' => KmDocumentStatus::INACTIVE->legacyApprovalValue(),
        ])->save();

        try {
            app(KmReadingService::class)->markStarted($reader, $staleMarkDocument);
            $this->fail('Mark-started should recheck the locked document state.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, KmTransaksi::query()->count());
        $this->assertSame(0, DB::table('km_lihat_bukus')->count());
        $this->assertSame(8, (int) $reader->refresh()->km_total_poin);
    }

    public function test_guest_forbidden_document_and_missing_document_are_rejected_without_mutation(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader();
        $category = KmKategori::factory()->create(['poin_kategori' => 18]);
        $restricted = $this->publishedDocument($owner, $category, 'Dept. Head');

        $this->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $restricted->getKey(),
        ])->assertUnauthorized();

        $this->actingAs($reader)->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $restricted->getKey(),
        ])->assertForbidden();

        $this->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => 999999,
        ])->assertUnprocessable()->assertJsonValidationErrors('id_km_pengajuan');

        $this->assertSame(0, KmTransaksi::query()->count());
        $this->assertSame(0, (int) $reader->refresh()->km_total_poin);
    }

    public function test_database_unique_constraint_rejects_a_second_user_document_transaction(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader();
        $category = KmKategori::factory()->create();
        $document = $this->publishedDocument($owner, $category);
        KmTransaksi::factory()->reading()->create([
            'id_user' => $reader->getKey(),
            'id_km_pengajuan' => $document->getKey(),
            'modified_by' => $reader->getKey(),
        ]);

        try {
            DB::table('km_transaksis')->insert([
                'id_user' => $reader->getKey(),
                'id_km_pengajuan' => $document->getKey(),
                'level' => 0,
                'status' => KmReadStatus::READING->value,
                'modified_by' => $reader->getKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Unique user/document transaction constraint did not reject a duplicate.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString(
                'km_transaksis_user_document_unique',
                strtolower($exception->getMessage()),
            );
        }

        $this->assertSame(1, KmTransaksi::query()->count());
    }

    public function test_two_connection_insert_race_recovers_the_canonical_row_and_awards_once(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader();
        $category = KmKategori::factory()->create(['poin_kategori' => 31]);
        $document = $this->publishedDocument($owner, $category);
        $raceConnection = 'km_reading_race';
        $defaultConnection = DB::getDefaultConnection();
        $connectionConfig = config("database.connections.{$defaultConnection}");
        $this->assertIsArray($connectionConfig);
        config()->set("database.connections.{$raceConnection}", $connectionConfig);
        DB::purge($raceConnection);

        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::connection($raceConnection)->statement(
            'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED'
        );
        DB::connection($raceConnection)->statement('SET SESSION FOREIGN_KEY_CHECKS = 0');
        DB::connection($raceConnection)->statement('SET SESSION innodb_lock_wait_timeout = 3');

        $armed = true;
        KmTransaksi::creating(function (KmTransaksi $candidate) use (
            &$armed,
            $raceConnection,
            $reader,
            $document,
        ): void {
            if (! $armed
                || (int) $candidate->id_user !== (int) $reader->getKey()
                || (int) $candidate->id_km_pengajuan !== (int) $document->getKey()) {
                return;
            }

            $armed = false;
            DB::connection($raceConnection)->table('km_transaksis')->insert([
                'id_user' => $reader->getKey(),
                'id_km_pengajuan' => $document->getKey(),
                'poin' => null,
                'level' => 0,
                'status' => KmReadStatus::READING->value,
                'completed_at' => null,
                'points_awarded_at' => null,
                'modified_by' => $reader->getKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            $response = $this->actingAs($reader)->postJson(route('kmTransaksi.saveTransaction'), [
                'id_km_pengajuan' => $document->getKey(),
            ]);

            $response->assertOk()->assertJson([
                'success' => true,
                'already_completed' => false,
                'points_awarded' => 31,
            ]);
            $this->assertFalse($armed, 'The second-connection race injection was not exercised.');
            $this->assertSame(1, KmTransaksi::query()->count());
            $this->assertSame(KmReadStatus::COMPLETED->value, (int) KmTransaksi::query()->value('status'));
            $this->assertSame(31, (int) $reader->refresh()->km_total_poin);

            $this->postJson(route('kmTransaksi.saveTransaction'), [
                'id_km_pengajuan' => $document->getKey(),
            ])->assertOk()->assertJson([
                'already_completed' => true,
                'points_awarded' => 0,
            ]);
            $this->assertSame(31, (int) $reader->refresh()->km_total_poin);
        } finally {
            $armed = false;
            DB::connection($raceConnection)->statement('SET SESSION FOREIGN_KEY_CHECKS = 1');
            DB::disconnect($raceConnection);
            DB::disconnect($defaultConnection);
        }
    }

    public function test_two_parallel_completion_workers_award_points_exactly_once(): void
    {
        $owner = $this->createAdministrator();
        $reader = $this->createReader();
        $category = KmKategori::factory()->create(['poin_kategori' => 37]);
        $document = $this->publishedDocument($owner, $category);

        $results = $this->runKmWorkers([
            ['complete', (string) $reader->getKey(), (string) $document->getKey()],
            ['complete', (string) $reader->getKey(), (string) $document->getKey()],
        ]);

        $this->assertEqualsCanonicalizing(
            [
                ['already_completed' => false, 'points_awarded' => 37],
                ['already_completed' => true, 'points_awarded' => 0],
            ],
            $results,
        );
        $this->assertSame(1, KmTransaksi::query()->count());
        $this->assertSame(KmReadStatus::COMPLETED->value, (int) KmTransaksi::query()->value('status'));
        $this->assertSame(37, (int) $reader->refresh()->km_total_poin);
    }

    private function createAdministrator(): User
    {
        return User::factory()->create([
            'name' => 'KM Test Administrator',
            'role_id' => null,
            'km_total_poin' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createReader(array $attributes = []): User
    {
        return User::factory()->create([
            'name' => 'KM Test Reader',
            'role_id' => 99,
            'km_total_poin' => 0,
            ...$attributes,
        ]);
    }

    private function publishedDocument(
        User $owner,
        KmKategori $category,
        string $position = 'All Employee',
    ): KmPengajuan {
        return KmPengajuan::factory()->published()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
            'posisi' => $position,
        ]);
    }
}
