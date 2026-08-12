<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Enums\KnowledgeManagement\KmVersionStatus;
use App\Models\KmAssignment;
use App\Models\KmAssignmentUser;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\MstDepartment;
use App\Models\User;
use App\Services\KnowledgeManagement\KmCompletionService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class KmMandatoryMaterialDashboardTest extends KmTestCase
{
    private User $reader;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('km_private');

        DB::table('roles')->insert([
            'role' => 'Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = 99;
        DB::table('roles')->insert([
            'id' => $roleId,
            'role' => 'Employee',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->reader = User::factory()->create([
            'id' => 100,
            'name' => 'Mandatory Reader',
            'role_id' => $roleId,
            'is_active' => false,
            'km_total_poin' => 0,
        ]);
        $this->otherUser = User::factory()->create([
            'id' => 101,
            'name' => 'Other Reader',
            'role_id' => $roleId,
            'is_active' => false,
            'km_total_poin' => 0,
        ]);
    }

    public function test_dashboard_payload_counts_and_orders_only_visible_assignments_for_viewer(): void
    {
        [$document, $version] = $this->publishedReadyDocument('Materi Keselamatan');

        $future = $this->assign($version, $this->reader, now()->addDays(5), 'Orientasi keselamatan');
        $overdue = $this->assign($version, $this->reader, now()->subDay(), 'Refresh keselamatan');
        $completed = $this->assign($version, $this->reader, now()->addDays(2), 'Evaluasi keselamatan', [
            'completed_at' => now()->subHour(),
        ]);
        $this->assign($version, $this->reader, now()->addDays(3), 'Dikecualikan', [
            'exempted_at' => now()->subHour(),
            'exemption_reason' => 'Tidak lagi relevan.',
        ]);
        $this->assign($version, $this->otherUser, now()->subDays(2), 'Milik pengguna lain');

        KmTransaksi::query()->create([
            'id_km_pengajuan' => $document->getKey(),
            'document_version_id' => $version->getKey(),
            'id_user' => $this->reader->getKey(),
            'status' => KmReadStatus::READING->value,
            'progress_percent' => 40,
            'last_page' => 4,
            'pages_total' => 10,
            'unique_pages_count' => 4,
            'active_seconds' => 80,
        ]);

        $response = $this->actingAs($this->reader)->get(route('dsKnowlege'));

        $response->assertOk()
            ->assertViewHas('mandatorySummary', [
                'active_count' => 2,
                'overdue_count' => 1,
                'completed_count' => 1,
            ])
            ->assertSee('Materi Wajib Saya')
            ->assertSee('Orientasi keselamatan')
            ->assertSee('Refresh keselamatan')
            ->assertDontSee('Milik pengguna lain');

        $materials = $response->viewData('mandatoryMaterials');
        $this->assertSame([
            $overdue->assignment_id,
            $future->assignment_id,
        ], $materials->pluck('assignment_id')->all());
        $this->assertSame(40, $materials->first()['progress_percent']);
        $this->assertSame(
            route('km.document-versions.preview', [$document, $version]),
            $materials->first()['preview_url'],
        );
        $this->assertSame($completed->completed_at->toDateTimeString(), $completed->fresh()->completed_at->toDateTimeString());
    }

    public function test_mandatory_filter_returns_only_active_assignments_owned_by_viewer(): void
    {
        [$assignedDocument, $assignedVersion] = $this->publishedReadyDocument('Materi Wajib Aktif');
        [$completedDocument, $completedVersion] = $this->publishedReadyDocument('Materi Wajib Selesai');
        [$otherDocument, $otherVersion] = $this->publishedReadyDocument('Materi Pengguna Lain');
        [$unassignedDocument] = $this->publishedReadyDocument('Materi Umum');

        $this->assign($assignedVersion, $this->reader, now()->addDays(4), 'Tugas aktif');
        $this->assign($completedVersion, $this->reader, now()->addDays(4), 'Tugas selesai', [
            'completed_at' => now(),
        ]);
        $this->assign($otherVersion, $this->otherUser, now()->addDays(4), 'Tugas pengguna lain');

        $response = $this->actingAs($this->reader)->get(route('dsKnowlege', ['mandatory' => 1]));

        $response->assertOk()
            ->assertSee('Hanya Materi Wajib Saya')
            ->assertSee('Materi Wajib Saya');
        $this->assertSame(
            [$assignedVersion->getKey()],
            $response->viewData('mandatoryAssignments')->pluck('document_version_id')->all(),
        );
        $this->assertFalse($response->viewData('mandatoryAssignments')->contains(
            fn (array $material): bool => $material['document_id'] === $completedDocument->getKey(),
        ));
        $this->assertFalse($response->viewData('mandatoryAssignments')->contains(
            fn (array $material): bool => $material['document_id'] === $otherDocument->getKey(),
        ));
        $this->assertFalse($response->viewData('mandatoryAssignments')->contains(
            fn (array $material): bool => $material['document_id'] === $unassignedDocument->getKey(),
        ));
    }

    public function test_assignment_deep_link_rejects_other_user_and_mismatched_document(): void
    {
        [$document, $version] = $this->publishedReadyDocument('Materi Deep Link');
        [$otherDocument] = $this->publishedReadyDocument('Materi Lain');
        $foreignAssignment = $this->assign($version, $this->otherUser, now()->addDays(2), 'Tugas asing');
        $ownAssignment = $this->assign($version, $this->reader, now()->addDays(2), 'Tugas saya');

        $this->actingAs($this->reader)->get(route('dsKnowlege', [
            'document' => $document->getKey(),
            'assignment' => $foreignAssignment->assignment_id,
        ]))->assertNotFound();

        $this->actingAs($this->reader)->get(route('dsKnowlege', [
            'document' => $otherDocument->getKey(),
            'assignment' => $ownAssignment->assignment_id,
        ]))->assertNotFound();

        $response = $this->actingAs($this->reader)->get(route('dsKnowlege', [
            'document' => $document->getKey(),
            'assignment' => $ownAssignment->assignment_id,
        ]));
        $response->assertOk()->assertSee('Materi Deep Link');
        $this->assertTrue($response->viewData('pengajuans')->contains($document));
    }

    public function test_owned_assignment_with_revoked_visibility_shows_notice_without_document_leak(): void
    {
        [$document, $version] = $this->publishedReadyDocument('Materi Rahasia', 'Dept. Head');
        $assignment = $this->assign($version, $this->reader, now()->addDays(2), 'Tugas lama');

        $response = $this->actingAs($this->reader)->get(route('dsKnowlege', [
            'document' => $document->getKey(),
            'assignment' => $assignment->assignment_id,
        ]));

        $response->assertOk()
            ->assertSee('Materi wajib ini tidak lagi tersedia untuk akun Anda.')
            ->assertDontSee('Materi Rahasia');
        $this->assertTrue($response->viewData('pengajuans')->isEmpty());
    }

    public function test_completion_updates_only_assignment_for_the_exact_document_version(): void
    {
        [$document, $version] = $this->publishedReadyDocument('Materi Completion');
        [$otherDocument, $otherVersion] = $this->publishedReadyDocument('Materi Versi Lain');
        $matching = $this->assign($version, $this->reader, now()->addDays(2), 'Tugas cocok');
        $notMatching = $this->assign($otherVersion, $this->reader, now()->addDays(2), 'Tugas lain');
        $transaction = KmTransaksi::query()->create([
            'id_km_pengajuan' => $document->getKey(),
            'document_version_id' => $version->getKey(),
            'id_user' => $this->reader->getKey(),
            'status' => KmReadStatus::COMPLETED->value,
            'completed_at' => now(),
            'progress_percent' => 100,
            'poin' => 5,
        ]);

        app(KmCompletionService::class)->recordOfficial($this->reader, $document, $transaction);

        $this->assertNotNull($matching->fresh()->completed_at);
        $this->assertNull($notMatching->fresh()->completed_at);
        $this->assertSame($otherDocument->getKey(), (int) $otherVersion->km_pengajuan_id);
    }

    public function test_historical_assignment_remains_readable_and_records_progress_on_exact_version(): void
    {
        Config::set('knowledge_management.reading.minimum_active_seconds', 0);
        Config::set('knowledge_management.reading.seconds_per_page', 0);
        [$document, $versionOne] = $this->publishedReadyDocument('Materi Wajib V1');
        $assignment = $this->assign($versionOne, $this->reader, now()->addDays(3), 'Selesaikan versi satu');
        $versionTwo = $this->publishRevision($document, 'Materi Wajib V2');

        $this->assertTrue(Gate::forUser($this->reader)->allows('viewVersion', [$document, $versionOne]));
        $this->actingAs($this->reader)
            ->get(route('km.document-versions.preview', [$document, $versionOne]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename=materi-wajib-v1.pdf');
        $this->get(route('km.document-versions.thumbnail', [$document, $versionOne]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $dashboard = $this->get(route('dsKnowlege', ['mandatory' => 1]));
        $dashboard->assertOk()->assertSee('Materi Wajib V1');
        $this->assertSame(
            [$assignment->assignment_id],
            $dashboard->viewData('mandatoryAssignments')->pluck('assignment_id')->all(),
        );

        $this->postJson(route('kmTransaksi.markAsRead'), [
            'id_km_pengajuan' => $document->getKey(),
            'document_version_id' => $versionOne->getKey(),
        ])->assertOk()->assertJsonPath('already_completed', false);
        $this->patchJson(route('km.reading.progress', $document), [
            'document_version_id' => $versionOne->getKey(),
            'last_page' => 10,
            'pages_total' => 10,
            'pages' => range(1, 10),
            'active_delta' => 0,
        ])->assertOk()->assertJsonPath('progress_percent', 100);
        $this->postJson(route('kmTransaksi.saveTransaction'), [
            'id_km_pengajuan' => $document->getKey(),
            'document_version_id' => $versionOne->getKey(),
            'acknowledged' => true,
        ])->assertOk()->assertJsonPath('already_completed', false);

        $this->assertDatabaseHas('km_transaksis', [
            'id_user' => $this->reader->getKey(),
            'document_version_id' => $versionOne->getKey(),
            'status' => KmReadStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseMissing('km_transaksis', [
            'id_user' => $this->reader->getKey(),
            'document_version_id' => $versionTwo->getKey(),
        ]);
        $this->assertDatabaseHas('km_point_ledger', [
            'user_id' => $this->reader->getKey(),
            'document_version_id' => $versionOne->getKey(),
            'event_type' => 'completion',
        ]);

        Storage::disk('km_private')->put($versionOne->normalized_pdf_path, 'tampered-pdf');
        $this->get(route('km.document-versions.thumbnail', [$document, $versionOne]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_historical_version_denies_non_recipient_inactive_document_and_unready_version(): void
    {
        [$document, $versionOne] = $this->publishedReadyDocument('Materi Terbatas V1');
        $this->assign($versionOne, $this->reader, now()->addDays(3), 'Tugas terbatas');
        $this->publishRevision($document, 'Materi Terbatas V2');
        $intruder = User::factory()->create([
            'name' => 'Historical Version Intruder',
            'role_id' => $this->reader->role_id,
            'is_active' => false,
        ]);

        $this->assertFalse(Gate::forUser($intruder)->allows('viewVersion', [$document, $versionOne]));

        $versionOne->forceFill(['audience' => 'Dept. Head'])->save();
        $this->assertFalse(Gate::forUser($this->reader)->allows('viewVersion', [$document, $versionOne]));

        $versionOne->forceFill(['audience' => 'All Employee'])->save();
        $department = MstDepartment::query()->create([
            'name' => 'Department Historical Restricted',
            'is_active' => true,
        ]);
        $versionOne->targetDepartments()->sync([$department->getKey()]);
        $this->assertFalse(Gate::forUser($this->reader)->allows('viewVersion', [$document, $versionOne]));
        $versionOne->targetDepartments()->detach();
        $this->assertTrue(Gate::forUser($this->reader)->allows('viewVersion', [$document, $versionOne]));

        $versionOne->forceFill(['processing_status' => KmProcessingStatus::PROCESSING])->save();
        $this->assertFalse(Gate::forUser($this->reader)->allows('viewVersion', [$document, $versionOne]));

        $versionOne->forceFill(['processing_status' => KmProcessingStatus::READY])->save();
        $document->forceFill(['status' => KmDocumentStatus::INACTIVE->value])->save();
        $this->assertFalse(Gate::forUser($this->reader)->allows('viewVersion', [$document, $versionOne]));
    }

    public function test_mandatory_filter_keeps_multiple_assignments_for_same_document_version(): void
    {
        [, $version] = $this->publishedReadyDocument('Materi Assignment Ganda');
        $first = $this->assign($version, $this->reader, now()->addDays(2), 'Assignment pertama');
        $second = $this->assign($version, $this->reader, now()->addDays(4), 'Assignment kedua');

        $response = $this->actingAs($this->reader)->get(route('dsKnowlege', ['mandatory' => 1]));
        $response->assertOk()->assertSee('Assignment pertama')->assertSee('Assignment kedua');
        $this->assertEqualsCanonicalizing(
            [$first->assignment_id, $second->assignment_id],
            $response->viewData('mandatoryAssignments')->pluck('assignment_id')->all(),
        );
    }

    /** @return array{KmPengajuan, KmDocumentVersion} */
    private function publishedReadyDocument(string $title, string $audience = 'All Employee'): array
    {
        $document = KmPengajuan::factory()->create([
            'id_user' => $this->otherUser->getKey(),
            'judul' => $title,
            'keterangan' => 'Sinopsis '.$title,
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => $audience,
        ]);
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF";
        $checksum = hash('sha256', $pdf);
        $version = KmDocumentVersion::query()->create([
            'km_pengajuan_id' => $document->getKey(),
            'version_major' => 1,
            'version_minor' => 0,
            'change_type' => 'major',
            'change_note' => 'Versi awal untuk pengujian materi wajib.',
            'version_status' => KmVersionStatus::PUBLISHED,
            'title' => $title,
            'synopsis' => 'Sinopsis '.$title,
            'audience' => $audience,
            'original_name' => Str::slug($title).'.pdf',
            'normalized_pdf_disk' => 'km_private',
            'normalized_pdf_path' => 'documents/'.$document->getKey().'/versions/1/normalized.pdf',
            'normalized_pdf_size_bytes' => strlen($pdf),
            'normalized_pdf_checksum_sha256' => $checksum,
            'processing_status' => KmProcessingStatus::READY,
            'antivirus_status' => 'clean',
            'processing_attempts' => 0,
            'created_by' => $this->otherUser->getKey(),
            'published_at' => now(),
        ]);
        $document->forceFill([
            'current_version_id' => $version->getKey(),
            'published_version_id' => $version->getKey(),
        ])->save();
        Storage::disk('km_private')->put($version->normalized_pdf_path, $pdf);
        Storage::disk('km_private')->put(
            'thumbnails/'.$document->getKey().'/versions/'.$version->getKey().'.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );

        return [$document->refresh(), $version->refresh()];
    }

    private function publishRevision(KmPengajuan $document, string $title): KmDocumentVersion
    {
        $document->publishedVersion?->forceFill([
            'version_status' => KmVersionStatus::WITHDRAWN,
            'withdrawn_at' => now(),
        ])->save();
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n% {$title}\n%%EOF";
        $version = KmDocumentVersion::query()->create([
            'km_pengajuan_id' => $document->getKey(),
            'version_major' => 2,
            'version_minor' => 0,
            'change_type' => 'major',
            'change_note' => 'Revisi untuk pengujian versi historis.',
            'version_status' => KmVersionStatus::PUBLISHED,
            'title' => $title,
            'synopsis' => 'Sinopsis '.$title,
            'audience' => 'All Employee',
            'original_name' => Str::slug($title).'.pdf',
            'normalized_pdf_disk' => 'km_private',
            'normalized_pdf_path' => 'documents/'.$document->getKey().'/versions/2/normalized.pdf',
            'normalized_pdf_size_bytes' => strlen($pdf),
            'normalized_pdf_checksum_sha256' => hash('sha256', $pdf),
            'processing_status' => KmProcessingStatus::READY,
            'antivirus_status' => 'clean',
            'processing_attempts' => 0,
            'created_by' => $this->otherUser->getKey(),
            'published_at' => now(),
        ]);
        Storage::disk('km_private')->put($version->normalized_pdf_path, $pdf);
        $document->forceFill([
            'judul' => $title,
            'current_version_id' => $version->getKey(),
            'published_version_id' => $version->getKey(),
        ])->save();

        return $version->refresh();
    }

    /** @param array<string, mixed> $attributes */
    private function assign(
        KmDocumentVersion $version,
        User $user,
        mixed $dueAt,
        string $title,
        array $attributes = [],
    ): KmAssignmentUser {
        $assignment = KmAssignment::query()->create([
            'document_version_id' => $version->getKey(),
            'title' => $title,
            'status' => 'active',
            'due_at' => $dueAt,
            'target_snapshot' => ['user_ids' => [$user->getKey()]],
            'created_by' => $this->otherUser->getKey(),
            'reason' => 'Kebutuhan pengujian materi wajib.',
        ]);

        return KmAssignmentUser::query()->create([
            'assignment_id' => $assignment->getKey(),
            'user_id' => $user->getKey(),
            'due_at' => $dueAt,
            ...$attributes,
        ]);
    }
}
