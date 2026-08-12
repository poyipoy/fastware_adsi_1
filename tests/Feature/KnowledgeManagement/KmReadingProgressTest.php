<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class KmReadingProgressTest extends KmTestCase
{
    public function test_pdf_progress_is_monotonic_and_completion_is_server_gated(): void
    {
        [$owner, $reader] = $this->users();
        $document = $this->publishedPdf($owner);
        $progressUrl = route('km.reading.progress', $document);

        $first = $this->actingAs($reader)->patchJson($progressUrl, [
            'last_page' => 3,
            'pages_total' => 10,
            'pages' => [1, 2, 3],
            'active_delta' => 30,
        ]);
        $first->assertOk()
            ->assertJsonPath('unique_pages_count', 3)
            ->assertJsonPath('active_seconds', 30)
            ->assertJsonPath('progress_percent', 30)
            ->assertJsonPath('completion_eligible', false);

        $second = $this->patchJson($progressUrl, [
            'last_page' => 4,
            'pages_total' => 10,
            'pages' => [2, 4],
            'active_delta' => 500,
        ]);
        $second->assertOk()
            ->assertJsonPath('unique_pages_count', 4)
            ->assertJsonPath('active_seconds', 150)
            ->assertJsonPath('progress_percent', 40);

        $this->patchJson($progressUrl, [
            'last_page' => 2,
            'pages_total' => 10,
            'pages' => [2],
            'active_delta' => 0,
        ])->assertOk()
            ->assertJsonPath('last_page', 4)
            ->assertJsonPath('unique_pages_count', 4)
            ->assertJsonPath('active_seconds', 150);

        $this->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
        ])->assertUnprocessable()->assertJsonValidationErrors('acknowledged');

        $this->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
            'acknowledged' => true,
        ])->assertUnprocessable()
            ->assertJsonPath('success', false);

        $eligible = $this->patchJson($progressUrl, [
            'last_page' => 10,
            'pages_total' => 10,
            'pages' => [5, 6, 7, 8, 9, 10],
            'active_delta' => 50,
        ]);
        $eligible->assertOk()
            ->assertJsonPath('unique_pages_count', 10)
            ->assertJsonPath('active_seconds', 200)
            ->assertJsonPath('progress_percent', 100)
            ->assertJsonPath('completion_eligible', true);

        $completion = $this->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
            'acknowledged' => true,
        ]);
        $completion->assertOk()
            ->assertJsonPath('already_completed', false)
            ->assertJsonPath('points_awarded', 5);

        $transaction = KmTransaksi::query()->sole();
        $this->assertSame(KmReadStatus::COMPLETED->value, (int) $transaction->status);
        $this->assertSame(5, (int) $reader->refresh()->km_total_poin);
        $this->assertSame(1, DB::table('km_point_ledger')->count());

        $this->patchJson($progressUrl, [
            'last_page' => 1,
            'pages_total' => 10,
            'pages' => [1],
            'active_delta' => 100,
        ])->assertOk()
            ->assertJsonPath('progress_percent', 100)
            ->assertJsonPath('completion_eligible', true);
    }

    public function test_progress_rejects_invalid_pages_and_inaccessible_documents_without_mutation(): void
    {
        [$owner, $reader] = $this->users();
        $document = $this->publishedPdf($owner);

        $this->actingAs($reader)->patchJson(route('km.reading.progress', $document), [
            'last_page' => 3,
            'pages_total' => 2,
            'pages' => [3],
            'active_delta' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['last_page', 'pages.0']);

        $restricted = $this->publishedPdf($owner, 'Dept. Head');
        $this->patchJson(route('km.reading.progress', $restricted), [
            'last_page' => 1,
            'pages_total' => 1,
            'pages' => [1],
            'active_delta' => 1,
        ])->assertForbidden();

        $this->assertSame(0, KmTransaksi::query()->count());
    }

    /** @return array{User, User} */
    private function users(): array
    {
        $owner = User::factory()->create([
            'name' => 'ADMINSTRATOR',
            'role_id' => null,
            'km_total_poin' => 0,
        ]);
        $reader = User::factory()->create([
            'name' => 'Progress Reader',
            'role_id' => 99,
            'km_total_poin' => 0,
        ]);

        return [$owner, $reader];
    }

    private function publishedPdf(User $owner, string $position = 'All Employee'): KmPengajuan
    {
        $document = KmPengajuan::factory()->published()->create([
            'id_user' => $owner->getKey(),
            'posisi' => $position,
        ]);
        $document->forceFill([
            'file_disk' => 'km_private',
            'file_path' => 'documents/'.$document->getKey().'/'.Str::uuid().'.pdf',
            'file_original_name' => 'materi.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_checksum_sha256' => str_repeat('a', 64),
            'file_migrated_at' => now(),
        ])->save();

        return $document->refresh();
    }
}
