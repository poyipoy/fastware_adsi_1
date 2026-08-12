<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Enums\KnowledgeManagement\KmVersionStatus;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Models\MstDepartment;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Services\KnowledgeManagement\KmAccessService;
use App\Services\KnowledgeManagement\KmApprovalService;
use App\Services\KnowledgeManagement\KmGamificationService;
use App\Services\KnowledgeManagement\KmHrisOutboundService;
use App\Services\KnowledgeManagement\KmPublicationNotificationService;
use App\Services\KnowledgeManagement\KmRbacService;
use App\Services\KnowledgeManagement\KmRecommendationService;
use App\Services\KnowledgeManagement\KmVersioningService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class KmRoadmapPriorityTest extends KmTestCase
{
    public function test_ready_recommendation_is_enabled_and_streams_normalized_pdf(): void
    {
        Storage::fake('km_private');
        $owner = $this->loginEnabledUser('Recommendation Owner');
        $reader = $this->loginEnabledUser('Recommendation Reader');
        $document = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'judul' => 'Materi Rekomendasi Siap Baca',
            'keterangan' => 'Materi hasil konversi harus dapat dibuka dari rekomendasi.',
            'posisi' => 'All Employee',
        ]);
        $document->forceFill($this->legacyFileMetadata($document, 'recommendation.pptx'))->save();
        $version = $this->version(
            $document,
            $owner,
            KmVersionStatus::PUBLISHED,
            KmProcessingStatus::READY,
        );
        $pdf = "%PDF-1.4\nrecommendation preview\n%%EOF";
        $pdfPath = 'documents/'.$document->getKey().'/versions/'.$version->getKey().'/normalized.pdf';
        Storage::disk('km_private')->put($pdfPath, $pdf);
        $version->forceFill([
            'normalized_pdf_path' => $pdfPath,
            'normalized_pdf_size_bytes' => strlen($pdf),
            'normalized_pdf_checksum_sha256' => hash('sha256', $pdf),
        ])->save();
        $document->forceFill([
            'current_version_id' => $version->getKey(),
            'published_version_id' => $version->getKey(),
        ])->save();

        $recommendation = app(KmRecommendationService::class)
            ->forUser($reader, 6)
            ->firstWhere('id', $document->getKey());

        $this->assertNotNull($recommendation);
        $this->assertTrue($recommendation->isPreviewableFile());

        $dashboard = $this->actingAs($reader)->get(route('dsKnowlege'));
        $dashboard->assertOk();
        $html = (string) $dashboard->getContent();
        $recommendationStart = strpos($html, 'id="km-recommendation-title"');
        $filterStart = strpos($html, 'class="km-panel km-filter-bar"');
        $this->assertNotFalse($recommendationStart);
        $this->assertNotFalse($filterStart);
        $recommendationHtml = substr(
            $html,
            (int) $recommendationStart,
            (int) $filterStart - (int) $recommendationStart,
        );
        $this->assertMatchesRegularExpression(
            '/<button\b(?=[^>]*data-document-id="'.preg_quote((string) $document->getKey(), '/').'")[^>]*>/i',
            $recommendationHtml,
        );
        preg_match(
            '/<button\b(?=[^>]*data-document-id="'.preg_quote((string) $document->getKey(), '/').'")[^>]*>/i',
            $recommendationHtml,
            $button,
        );
        $this->assertStringNotContainsString('disabled', $button[0]);

        $this->actingAs($reader)
            ->get(route('km.documents.preview', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_locked_cross_release_contracts_remain_idempotent_and_policy_scoped(): void
    {
        config()->set('knowledge_management.upload.office_submission_enabled', false);
        config()->set('knowledge_management.processing.enabled', false);

        $owner = $this->loginEnabledUser('Roadmap Owner');
        $approver = $this->grantKmApprovalAccess($this->loginEnabledUser('Roadmap Approver'));
        $office = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'judul' => 'Office Roadmap',
            'keterangan' => 'Dokumen pengujian versioning dan processing.',
            'posisi' => 'All Employee',
        ]);
        $office->forceFill($this->legacyFileMetadata($office, 'deck.pptx'))->save();
        $versionOne = $this->version($office, $owner, KmVersionStatus::DRAFT, KmProcessingStatus::PENDING);
        $office->forceFill(['current_version_id' => $versionOne->getKey()])->save();

        try {
            app(KmApprovalService::class)->submit($office->refresh(), $owner);
            $this->fail('Office pending tidak boleh diajukan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }

        $versionOne->forceFill([
            'processing_status' => KmProcessingStatus::READY,
            'antivirus_status' => 'clean',
            'normalized_pdf_disk' => 'km_private',
            'normalized_pdf_path' => 'documents/'.$office->getKey().'/versions/'.$versionOne->getKey().'/normalized.pdf',
            'normalized_pdf_size_bytes' => 2048,
            'normalized_pdf_checksum_sha256' => str_repeat('b', 64),
            'processed_at' => now(),
        ])->save();
        $this->assertFalse($office->refresh()->isReadyForSubmission(), 'Gate Office harus tetap menutup submit.');

        config()->set('knowledge_management.upload.office_submission_enabled', true);
        app(KmApprovalService::class)->submit($office->refresh(), $owner);
        $this->assertSame(KmDocumentStatus::PENDING_APPROVAL, $office->refresh()->documentStatus());
        $this->assertSame(KmVersionStatus::PENDING_APPROVAL, $versionOne->refresh()->version_status);

        app(KmApprovalService::class)->approve($office->refresh(), $approver, [
            'posisi' => 'All Employee',
        ]);
        $office->refresh();
        $this->assertSame($versionOne->getKey(), (int) $office->published_version_id);
        $this->assertDatabaseHas('km_user_badges', [
            'user_id' => $owner->getKey(),
            'badge_id' => DB::table('km_badges')->where('slug', 'first-publication')->value('id'),
        ]);

        $versions = app(KmVersioningService::class);
        $minor = $versions->createMinorRevision($office, $owner, 'Pembaruan tag administratif.');
        $this->assertSame('1.1', $minor->number());
        $this->assertSame($minor->getKey(), (int) $office->refresh()->published_version_id);

        $major = $versions->createMajorRevision($office->refresh(), $owner, 'Revisi isi utama.');
        $this->assertSame('2.0', $major->number());
        $this->assertSame($minor->getKey(), (int) $office->refresh()->published_version_id);
        app(KmApprovalService::class)->submit($office->refresh(), $owner);
        $this->assertNull($office->refresh()->published_version_id);
        $this->assertSame(KmVersionStatus::WITHDRAWN, $minor->refresh()->version_status);
        app(KmApprovalService::class)->reject($office->refresh(), $approver, 'Perlu revisi substansi.', []);
        $this->assertNull($office->refresh()->published_version_id, 'Versi lama tidak boleh dipulihkan otomatis.');
        $this->assertSame(KmVersionStatus::DRAFT, $major->refresh()->version_status);

        $major->forceFill([
            'processing_status' => KmProcessingStatus::PROCESSING,
            'processing_attempts' => 3,
            'processing_started_at' => now()->subHours(2),
        ])->save();
        config()->set('knowledge_management.processing.enabled', true);
        $this->assertSame(0, Artisan::call('km:process-pending-documents', ['--limit' => 1]), Artisan::output());
        $this->assertSame(KmProcessingStatus::FAILED, $major->refresh()->processing_status);

        $department = MstDepartment::query()->create(['name' => 'Legal', 'is_active' => true]);
        $position = MstJobPosition::query()->create([
            'position_name' => 'Legal Employee',
            'department_id' => $department->getKey(),
            'is_active' => true,
        ]);
        $reader = $this->loginEnabledUser('Targeted Reader');
        $outsider = $this->loginEnabledUser('Targeted Outsider');
        $assignment = UserJobPosition::query()->create([
            'user_id' => $reader->getKey(),
            'mst_job_position_id' => $position->getKey(),
            'is_active' => true,
            'effective_from' => today(),
            'assignment_source' => 'test',
        ]);
        $targeted = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'judul' => 'Materi Bertarget',
            'posisi' => 'All Employee',
        ]);
        $targeted->forceFill($this->legacyFileMetadata($targeted, 'targeted.pdf'))->save();
        $targetedVersion = $this->version(
            $targeted,
            $owner,
            KmVersionStatus::PUBLISHED,
            KmProcessingStatus::READY,
        );
        $targetedVersion->forceFill([
            'extracted_text' => 'Prosedur unik orchestrationquartz untuk pengujian pencarian isi dokumen.',
        ])->save();
        $targetedVersion->targetDepartments()->sync([$department->getKey()]);
        $targeted->forceFill([
            'current_version_id' => $targetedVersion->getKey(),
            'published_version_id' => $targetedVersion->getKey(),
        ])->save();

        $access = app(KmAccessService::class);
        $this->assertTrue($access->isPublishedDocumentEligible($reader, $targeted->refresh()));
        $this->assertFalse($access->isPublishedDocumentEligible($outsider, $targeted->refresh()));
        $readerSearch = $this->actingAs($reader)->get(route('dsKnowlege', [
            'q' => 'orchestrationquartz',
        ]));
        $readerSearch->assertOk();
        $this->assertTrue($readerSearch->viewData('pengajuans')->contains($targeted));
        $outsiderSearch = $this->actingAs($outsider)->get(route('dsKnowlege', [
            'q' => 'orchestrationquartz',
        ]));
        $outsiderSearch->assertOk();
        $this->assertFalse($outsiderSearch->viewData('pengajuans')->contains($targeted));
        $this->assertTrue(app(KmRecommendationService::class)->forUser($reader)
            ->contains(fn (KmPengajuan $candidate): bool => $candidate->is($targeted)));
        $this->assertFalse(app(KmRecommendationService::class)->forUser($outsider)
            ->contains(fn (KmPengajuan $candidate): bool => $candidate->is($targeted)));
        app(KmRbacService::class)->createRule($owner, [
            'subject_type' => 'user',
            'subject_id' => $outsider->getKey(),
            'ability' => 'km.oversight',
            'effect' => 'allow',
            'valid_from' => null,
            'valid_until' => null,
            'reason' => 'Pengujian ability oversight terpisah dari approval.',
        ]);
        $this->assertTrue($access->canAccessKnowledgeOversight($outsider));
        $this->assertFalse($access->canApprove($outsider), 'RBAC tidak boleh memperluas approver terkunci.');

        $progressUrl = route('km.reading.progress', $targeted);
        $this->actingAs($reader)->patchJson($progressUrl, [
            'last_page' => 1, 'pages_total' => 10, 'pages' => [1], 'active_delta' => 30,
            'session_token' => 'tab-a', 'device_token' => 'device-a', 'session_active_seconds' => 30,
        ])->assertOk()->assertJsonPath('active_seconds', 30);
        $this->patchJson($progressUrl, [
            'last_page' => 2, 'pages_total' => 10, 'pages' => [2], 'active_delta' => 30,
            'session_token' => 'tab-b', 'device_token' => 'device-b', 'session_active_seconds' => 30,
        ])->assertOk()->assertJsonPath('active_seconds', 30);
        $this->assertSame(2, DB::table('km_reading_sessions')->count());

        $baselineNow = now();
        Carbon::setTestNow($baselineNow->copy()->addSeconds(65));
        try {
            $this->patchJson($progressUrl, [
                'last_page' => 3, 'pages_total' => 10, 'pages' => [3], 'active_delta' => 20,
                'session_token' => 'tab-a', 'device_token' => 'device-a', 'session_active_seconds' => 50,
            ])->assertOk()->assertJsonPath('active_seconds', 50);
        } finally {
            Carbon::setTestNow();
        }

        $publication = app(KmPublicationNotificationService::class);
        $batch = $publication->queue($targetedVersion->load('document'));
        $this->assertNotNull($batch);
        $this->assertDatabaseHas('km_publication_recipients', [
            'publication_batch_id' => $batch->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $this->assertDatabaseMissing('km_publication_recipients', [
            'publication_batch_id' => $batch->getKey(),
            'user_id' => $outsider->getKey(),
        ]);

        $assignment->forceFill(['is_active' => false])->save();
        $publication->dispatch(10);
        $this->assertDatabaseHas('km_notifications', [
            'user_id' => $reader->getKey(),
            'type' => 'new_material',
        ]);
        $this->assertFalse($access->canView($reader, $targeted->refresh()), 'Akses harus diperiksa ulang setelah mapping berubah.');

        $owner->forceFill(['km_total_poin' => 150])->save();
        $this->assertSame('Silver', app(KmGamificationService::class)->profile($owner->refresh())['tier']);
        $this->assertFalse(app(KmHrisOutboundService::class)->isReady());
    }

    private function loginEnabledUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => 99,
            'is_active' => false,
            'km_total_poin' => 0,
        ]);
    }

    /** @return array<string, mixed> */
    private function legacyFileMetadata(KmPengajuan $document, string $name): array
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return [
            'file' => $name,
            'file_name' => $name,
            'file_disk' => 'km_private',
            'file_path' => 'documents/'.$document->getKey().'/00000000-0000-4000-8000-'.str_pad((string) $document->getKey(), 12, '0', STR_PAD_LEFT).'.'.$extension,
            'file_original_name' => $name,
            'file_mime_type' => $extension === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'file_size_bytes' => 1024,
            'file_checksum_sha256' => str_repeat('a', 64),
            'file_migrated_at' => now(),
        ];
    }

    private function version(
        KmPengajuan $document,
        User $actor,
        KmVersionStatus $status,
        KmProcessingStatus $processing,
    ): KmDocumentVersion {
        $ready = $processing === KmProcessingStatus::READY;

        return KmDocumentVersion::query()->create([
            'km_pengajuan_id' => $document->getKey(),
            'version_major' => 1,
            'version_minor' => 0,
            'change_type' => 'major',
            'change_note' => 'Versi awal pengujian roadmap.',
            'version_status' => $status,
            'title' => $document->judul,
            'synopsis' => $document->keterangan,
            'audience' => $document->posisi,
            'original_disk' => $document->file_disk,
            'original_path' => $document->file_path,
            'original_name' => $document->file_original_name,
            'original_mime_type' => $document->file_mime_type,
            'original_size_bytes' => $document->file_size_bytes,
            'original_checksum_sha256' => $document->file_checksum_sha256,
            'normalized_pdf_disk' => $ready ? 'km_private' : null,
            'normalized_pdf_path' => $ready
                ? 'documents/'.$document->getKey().'/versions/initial/normalized.pdf'
                : null,
            'normalized_pdf_size_bytes' => $ready ? 1024 : null,
            'normalized_pdf_checksum_sha256' => $ready ? str_repeat('b', 64) : null,
            'processing_status' => $processing,
            'antivirus_status' => $ready ? 'clean' : 'pending',
            'processing_attempts' => 0,
            'created_by' => $actor->getKey(),
            'published_at' => $status === KmVersionStatus::PUBLISHED ? now() : null,
        ]);
    }
}
