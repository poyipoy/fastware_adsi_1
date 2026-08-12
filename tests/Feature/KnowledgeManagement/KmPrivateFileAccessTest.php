<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Enums\KnowledgeManagement\KmVersionChangeType;
use App\Enums\KnowledgeManagement\KmVersionStatus;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmFileService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class KmPrivateFileAccessTest extends KmTestCase
{
    /** @var list<string> */
    private array $temporaryLegacyFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(KmFileService::DISK);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryLegacyFiles as $path) {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_authorized_employee_receives_pdf_inline_but_download_is_forbidden(): void
    {
        $owner = $this->user(2101, 'PDF Owner');
        $employee = $this->user(2102, 'PDF Reader');
        $document = $this->privateDocument(
            $owner,
            'application/pdf',
            'pdf',
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF",
        );

        $preview = $this->actingAs($employee)->get(route('km.documents.preview', $document));
        $preview->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith(
            'inline;',
            (string) $preview->headers->get('Content-Disposition'),
        );
        $this->assertStringContainsString('private', (string) $preview->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $preview->headers->get('Cache-Control'));

        $download = $this->actingAs($employee)->get(route('km.documents.download', $document));
        $download->assertForbidden();
        $this->assertFalse($download->headers->has('Content-Disposition'));

        $queryAttempt = $this->actingAs($employee)->get(
            route('km.documents.preview', $document).'?path=../../.env',
        );
        $queryAttempt->assertOk();
        $this->assertSame(
            Storage::disk(KmFileService::DISK)->path((string) $document->file_path),
            $queryAttempt->baseResponse->getFile()->getPathname(),
        );
    }

    public function test_office_document_preview_is_415_and_download_is_forbidden(): void
    {
        $owner = $this->user(2103, 'Office Owner');
        $employee = $this->user(2104, 'Office Reader');
        $document = $this->privateDocument(
            $owner,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pptx',
            'office-binary-content',
        );

        $this->actingAs($employee)
            ->get(route('km.documents.preview', $document))
            ->assertStatus(415);

        $download = $this->actingAs($employee)
            ->get(route('km.documents.download', $document));
        $download->assertForbidden();
        $this->assertFalse($download->headers->has('Content-Disposition'));
    }

    public function test_ready_office_version_streams_its_normalized_pdf_inline(): void
    {
        $owner = $this->user(2126, 'Converted Office Owner');
        $employee = $this->user(2127, 'Converted Office Reader');
        $document = $this->privateDocument(
            $owner,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pptx',
            'office-binary-content',
        );
        $pdf = "%PDF-1.4\nconverted office preview\n%%EOF";
        $version = KmDocumentVersion::query()->create([
            'km_pengajuan_id' => $document->getKey(),
            'version_major' => 1,
            'version_minor' => 0,
            'change_type' => KmVersionChangeType::MAJOR,
            'change_note' => 'Versi Office berhasil diproses.',
            'version_status' => KmVersionStatus::PUBLISHED,
            'title' => $document->judul,
            'synopsis' => $document->keterangan,
            'audience' => 'All Employee',
            'original_disk' => $document->file_disk,
            'original_path' => $document->file_path,
            'original_name' => $document->file_original_name,
            'original_mime_type' => $document->file_mime_type,
            'original_size_bytes' => $document->file_size_bytes,
            'original_checksum_sha256' => $document->file_checksum_sha256,
            'normalized_pdf_disk' => KmFileService::DISK,
            'normalized_pdf_path' => 'documents/'.$document->getKey().'/versions/1/normalized.pdf',
            'normalized_pdf_size_bytes' => strlen($pdf),
            'normalized_pdf_checksum_sha256' => hash('sha256', $pdf),
            'processing_status' => KmProcessingStatus::READY,
            'antivirus_status' => 'clean',
            'processing_attempts' => 1,
            'created_by' => $owner->getKey(),
            'published_at' => now(),
        ]);
        $version->forceFill([
            'normalized_pdf_path' => 'documents/'.$document->getKey().'/versions/'.$version->getKey().'/normalized.pdf',
        ])->save();
        Storage::disk(KmFileService::DISK)->put((string) $version->normalized_pdf_path, $pdf);
        $document->forceFill([
            'current_version_id' => $version->getKey(),
            'published_version_id' => $version->getKey(),
        ])->save();

        $this->assertTrue($document->refresh()->isPreviewableFile());
        $response = $this->actingAs($employee)
            ->get(route('km.documents.preview', $document));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith(
            'inline;',
            (string) $response->headers->get('Content-Disposition'),
        );
        $this->assertSame(
            Storage::disk(KmFileService::DISK)->path((string) $version->normalized_pdf_path),
            $response->baseResponse->getFile()->getPathname(),
        );
    }

    public function test_pending_version_never_falls_back_to_the_raw_pdf(): void
    {
        $owner = $this->user(2125, 'Pending Version Owner');
        $document = $this->privateDocument(
            $owner,
            'application/pdf',
            'pdf',
            "%PDF-1.4\nraw file must not be streamed\n%%EOF",
        );
        $version = KmDocumentVersion::query()->create([
            'km_pengajuan_id' => $document->getKey(),
            'version_major' => 1,
            'version_minor' => 0,
            'change_type' => KmVersionChangeType::MAJOR,
            'change_note' => 'Versi menunggu pemrosesan.',
            'version_status' => KmVersionStatus::PUBLISHED,
            'title' => $document->judul,
            'synopsis' => $document->keterangan,
            'audience' => 'All Employee',
            'original_disk' => $document->file_disk,
            'original_path' => $document->file_path,
            'original_name' => $document->file_original_name,
            'original_mime_type' => $document->file_mime_type,
            'original_size_bytes' => $document->file_size_bytes,
            'original_checksum_sha256' => $document->file_checksum_sha256,
            'processing_status' => KmProcessingStatus::PENDING,
            'antivirus_status' => 'pending',
            'processing_attempts' => 0,
            'created_by' => $owner->getKey(),
            'published_at' => now(),
        ]);
        $document->forceFill([
            'current_version_id' => $version->getKey(),
            'published_version_id' => $version->getKey(),
        ])->save();

        $response = $this->actingAs($owner)
            ->get(route('km.documents.preview', $document->refresh()));

        $response->assertStatus(415);
        $this->assertFalse($response->headers->has('Content-Disposition'));
    }

    public function test_legacy_pdf_mime_alias_is_normalized_for_preview_consumers(): void
    {
        $owner = $this->user(2116, 'PDF Alias Owner');
        $employee = $this->user(2117, 'PDF Alias Reader');
        $document = $this->privateDocument(
            $owner,
            'application/x-pdf',
            'pdf',
            "%PDF-1.4\nlegacy mime alias\n%%EOF",
        );

        $this->actingAs($employee)
            ->get(route('km.documents.preview', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($employee)
            ->getJson(route('showPersetujuan', $document))
            ->assertOk()
            ->assertJsonPath('km.previewable', true);
    }

    public function test_private_stream_rejects_unsafe_metadata_path_missing_file_and_checksum_mismatch(): void
    {
        $owner = $this->user(2105, 'Unsafe File Owner');
        $employee = $this->user(2106, 'Unsafe File Reader');

        $unsafe = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'posisi' => 'All Employee',
            'file_disk' => KmFileService::DISK,
            'file_path' => 'documents/1/../secrets/credentials.pdf',
            'file_original_name' => 'credentials.pdf',
            'file_mime_type' => 'application/pdf',
            'file_checksum_sha256' => hash('sha256', 'secret'),
        ]);
        $this->actingAs($employee)
            ->get(route('km.documents.preview', $unsafe))
            ->assertNotFound();

        $crossDocument = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'posisi' => 'All Employee',
        ]);
        $otherDocumentPath = sprintf('documents/%d/%s.pdf', $unsafe->id, Str::uuid());
        $otherContent = "%PDF-1.4\nwrong document binding";
        Storage::disk(KmFileService::DISK)->put($otherDocumentPath, $otherContent);
        $crossDocument->forceFill([
            'file_disk' => KmFileService::DISK,
            'file_path' => $otherDocumentPath,
            'file_original_name' => 'wrong-document.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($otherContent),
            'file_checksum_sha256' => hash('sha256', $otherContent),
            'file_migrated_at' => now(),
        ])->save();
        $this->actingAs($employee)
            ->get(route('km.documents.preview', $crossDocument))
            ->assertNotFound();

        $missing = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'posisi' => 'All Employee',
        ]);
        $missingPath = sprintf('documents/%d/%s.pdf', $missing->id, Str::uuid());
        $missing->forceFill([
            'file_disk' => KmFileService::DISK,
            'file_path' => $missingPath,
            'file_original_name' => 'missing.pdf',
            'file_mime_type' => 'application/pdf',
            'file_checksum_sha256' => hash('sha256', 'missing'),
        ])->save();
        $this->actingAs($employee)
            ->get(route('km.documents.preview', $missing))
            ->assertNotFound();

        $mismatch = $this->privateDocument(
            $owner,
            'application/pdf',
            'pdf',
            "%PDF-1.4\nchecksum target",
        );
        $mismatch->forceFill(['file_checksum_sha256' => str_repeat('0', 64)])->save();
        $this->actingAs($employee)
            ->get(route('km.documents.preview', $mismatch))
            ->assertStatus(409);
    }

    public function test_ineligible_employee_cannot_stream_private_document_even_with_direct_url(): void
    {
        $owner = $this->user(2107, 'Restricted File Owner');
        $employee = $this->user(2108, 'Regular Employee');
        $document = $this->privateDocument(
            $owner,
            'application/pdf',
            'pdf',
            "%PDF-1.4\nrestricted",
            'Dept. Head',
        );

        $this->actingAs($employee)
            ->get(route('km.documents.preview', $document))
            ->assertForbidden();
        $this->actingAs($employee)
            ->get(route('km.documents.download', $document))
            ->assertForbidden();
    }

    public function test_owner_and_approver_can_preview_allowed_documents_but_cannot_download_them(): void
    {
        $owner = $this->user(2113, 'Non Published Owner');
        $approver = $this->grantKmApprovalAccess($this->user(2114, 'HRGA Legal Approver'));
        $employee = $this->user(2115, 'Non Published Employee');
        $draft = $this->privateDocument(
            $owner,
            'application/pdf',
            'pdf',
            "%PDF-1.4\nowner draft",
        );
        $draft->forceFill([
            'status' => KmDocumentStatus::DRAFT->value,
            'persetujuan' => KmDocumentStatus::DRAFT->legacyApprovalValue(),
        ])->save();

        $this->actingAs($owner)
            ->get(route('km.documents.preview', $draft))
            ->assertOk();
        $this->actingAs($owner)
            ->get(route('km.documents.download', $draft))
            ->assertForbidden();

        $pending = $this->privateDocument(
            $owner,
            'application/pdf',
            'pdf',
            "%PDF-1.4\npending approval",
        );
        $pending->forceFill([
            'status' => KmDocumentStatus::PENDING_APPROVAL->value,
            'persetujuan' => KmDocumentStatus::PENDING_APPROVAL->legacyApprovalValue(),
        ])->save();

        $this->actingAs($approver)
            ->get(route('km.documents.preview', $pending))
            ->assertOk();
        $this->actingAs($approver)
            ->get(route('km.documents.download', $pending))
            ->assertForbidden();
        $this->actingAs($employee)
            ->get(route('km.documents.download', $pending))
            ->assertForbidden();
    }

    public function test_new_pdf_upload_is_randomized_and_stored_only_on_private_disk(): void
    {
        $uploader = $this->user(2109, 'MUGI PRAMONO');
        $originalName = 'codex-private-'.Str::uuid().'.pdf';
        $publicPath = public_path('assets/image/'.$originalName);
        $this->assertFalse(File::exists($publicPath));

        $upload = UploadedFile::fake()->createWithContent(
            $originalName,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF",
        );

        $this->actingAs($uploader)
            ->post(route('storeKM'), [
                'judul' => 'Dokumen private baru',
                'keterangan' => 'Binary harus disimpan di private disk.',
                'file' => $upload,
            ])
            ->assertRedirect(route('pengajuanKM'));

        $document = KmPengajuan::query()->sole();
        $this->assertSame(KmFileService::DISK, $document->file_disk);
        $this->assertSame($originalName, $document->file_original_name);
        $this->assertSame('application/pdf', $document->file_mime_type);
        $this->assertMatchesRegularExpression(
            sprintf('#^documents/%d/[a-f0-9-]+\.pdf$#', $document->id),
            (string) $document->file_path,
        );
        $this->assertStringNotContainsString($originalName, (string) $document->file_path);
        Storage::disk(KmFileService::DISK)->assertExists((string) $document->file_path);
        $this->assertSame(
            hash_file(
                'sha256',
                Storage::disk(KmFileService::DISK)->path((string) $document->file_path),
            ),
            $document->file_checksum_sha256,
        );
        $this->assertFalse(File::exists($publicPath));
    }

    public function test_mime_spoofed_pdf_upload_is_rejected_without_creating_document_or_file(): void
    {
        $uploader = $this->user(2110, 'SITI MARIA ULFA');
        $spoofed = UploadedFile::fake()->createWithContent('spoofed.pdf', 'plain text, not a PDF');

        $this->actingAs($uploader)
            ->postJson(route('storeKM'), [
                'judul' => 'Spoofed PDF',
                'keterangan' => 'Harus ditolak berdasarkan MIME server.',
                'file' => $spoofed,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('km_pengajuans', 0);
        $this->assertSame([], Storage::disk(KmFileService::DISK)->allFiles());
    }

    public function test_legacy_file_migration_is_replay_safe_and_manifest_restore_is_idempotent(): void
    {
        $owner = $this->user(2111, 'Legacy File Owner');
        $legacyName = 'km-legacy-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $content = "%PDF-1.4\nlegacy migration payload\n%%EOF";
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $this->temporaryLegacyFiles[] = $source;

        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => 'Dokumen Legacy.pdf',
            'file_disk' => null,
            'file_path' => null,
        ]);

        $this->assertSame(0, Artisan::call('km:migrate-private-files', ['--limit' => 10]), Artisan::output());
        $document->refresh();
        $firstDestination = (string) $document->file_path;
        $this->assertFalse(File::exists($source));
        $this->assertSame(KmFileService::DISK, $document->file_disk);
        Storage::disk(KmFileService::DISK)->assertExists($firstDestination);
        Storage::disk(KmFileService::DISK)->assertExists('legacy-backup/'.$document->id.'/'.$legacyName);
        $this->assertSame(1, count(Storage::disk(KmFileService::DISK)->allFiles('documents')));
        $this->assertSame(1, count(Storage::disk(KmFileService::DISK)->allFiles('legacy-backup')));

        $firstManifest = Storage::disk(KmFileService::DISK)->allFiles('file-migrations')[0] ?? null;
        $this->assertNotNull($firstManifest);
        $manifestPayload = json_decode(
            Storage::disk(KmFileService::DISK)->get($firstManifest),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertSame(2, $manifestPayload['version']);
        $this->assertSame($firstDestination, $manifestPayload['entries'][0]['new_metadata']['file_path']);
        $this->assertSame(
            $document->getRawOriginal('file_migrated_at'),
            $manifestPayload['entries'][0]['new_metadata']['file_migrated_at'],
        );

        $this->assertSame(0, Artisan::call('km:migrate-private-files', ['--limit' => 10]), Artisan::output());
        $document->refresh();
        $this->assertSame($firstDestination, $document->file_path);
        $this->assertSame(1, count(Storage::disk(KmFileService::DISK)->allFiles('documents')));
        $this->assertSame(1, count(Storage::disk(KmFileService::DISK)->allFiles('legacy-backup')));

        $manifestPath = Storage::disk(KmFileService::DISK)->path((string) $firstManifest);
        $this->assertSame(0, Artisan::call('km:migrate-private-files', [
            '--restore-manifest' => $manifestPath,
        ]), Artisan::output());
        $document->refresh();
        $this->assertTrue(File::isFile($source));
        $this->assertSame(hash('sha256', $content), hash_file('sha256', $source));
        $this->assertNull($document->file_disk);
        $this->assertNull($document->file_path);
        Storage::disk(KmFileService::DISK)->assertExists($firstDestination);

        $this->assertSame(0, Artisan::call('km:migrate-private-files', [
            '--restore-manifest' => $manifestPath,
        ]), Artisan::output());
        $this->assertSame(hash('sha256', $content), hash_file('sha256', $source));
    }

    public function test_legacy_file_migration_compensates_when_move_returns_false(): void
    {
        $owner = $this->user(2116, 'Move Failure Owner');
        $legacyName = 'km-move-failure-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $content = "%PDF-1.4\nmove failure\n%%EOF";
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $this->temporaryLegacyFiles[] = $source;

        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
            'file_disk' => null,
            'file_path' => null,
        ]);
        $backupPath = Storage::disk(KmFileService::DISK)->path(
            'legacy-backup/'.$document->id.'/'.$legacyName,
        );
        $metadataAtMove = null;

        File::partialMock()
            ->shouldReceive('move')
            ->once()
            ->with($source, $backupPath)
            ->andReturnUsing(function () use ($document, &$metadataAtMove): bool {
                $metadataAtMove = $document->fresh()?->only([
                    'file_disk',
                    'file_path',
                    'file_checksum_sha256',
                ]);

                return false;
            });

        try {
            $exitCode = Artisan::call('km:migrate-private-files');
        } finally {
            File::swap(new Filesystem());
        }

        $this->assertSame(1, $exitCode, Artisan::output());
        $this->assertSame(KmFileService::DISK, $metadataAtMove['file_disk'] ?? null);
        $this->assertNotEmpty($metadataAtMove['file_path'] ?? null);
        $this->assertSame(hash('sha256', $content), $metadataAtMove['file_checksum_sha256'] ?? null);
        $document->refresh();
        $this->assertNull($document->file_disk);
        $this->assertNull($document->file_path);
        $this->assertSame(hash('sha256', $content), hash_file('sha256', $source));
        $this->assertSame([], Storage::disk(KmFileService::DISK)->allFiles('documents'));
    }

    public function test_legacy_file_migration_replay_finalizes_source_left_after_metadata_commit(): void
    {
        $owner = $this->user(2120, 'Crash Recovery Owner');
        $legacyName = 'km-crash-recovery-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $content = "%PDF-1.4\nmetadata committed before crash\n%%EOF";
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $this->temporaryLegacyFiles[] = $source;

        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
            'file_disk' => null,
            'file_path' => null,
        ]);
        $destination = 'documents/'.$document->id.'/'.Str::uuid().'.pdf';
        $checksum = hash('sha256', $content);
        Storage::disk(KmFileService::DISK)->put($destination, $content);
        $document->forceFill([
            'file_disk' => KmFileService::DISK,
            'file_path' => $destination,
            'file_original_name' => $legacyName,
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($content),
            'file_checksum_sha256' => $checksum,
            'file_migrated_at' => now(),
        ])->save();

        $this->assertSame(0, Artisan::call('km:migrate-private-files'), Artisan::output());

        $document->refresh();
        $this->assertSame(KmFileService::DISK, $document->file_disk);
        $this->assertSame($destination, $document->file_path);
        $this->assertSame($checksum, $document->file_checksum_sha256);
        $this->assertFalse(File::exists($source));
        Storage::disk(KmFileService::DISK)->assertExists($destination);
        Storage::disk(KmFileService::DISK)->assertExists(
            'legacy-backup/'.$document->id.'/'.$legacyName,
        );
        $this->assertSame(1, count(Storage::disk(KmFileService::DISK)->allFiles('documents')));

        $manifest = Storage::disk(KmFileService::DISK)->allFiles('file-migrations')[0];
        $this->assertSame(0, Artisan::call('km:migrate-private-files', [
            '--restore-manifest' => Storage::disk(KmFileService::DISK)->path($manifest),
        ]), Artisan::output());
        $document->refresh();
        $this->assertNull($document->file_disk);
        $this->assertNull($document->file_path);
        $this->assertSame($checksum, hash_file('sha256', $source));
    }

    public function test_legacy_file_migration_rejournals_backup_only_crash_state_exactly_once(): void
    {
        $owner = $this->user(2122, 'Backup Only Recovery Owner');
        $legacyName = 'km-backup-only-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $this->temporaryLegacyFiles[] = $source;
        $content = "%PDF-1.4\nsource already moved before manifest write\n%%EOF";
        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
        ]);
        $destination = 'documents/'.$document->id.'/'.Str::uuid().'.pdf';
        $backup = 'legacy-backup/'.$document->id.'/'.$legacyName;
        $checksum = hash('sha256', $content);
        Storage::disk(KmFileService::DISK)->put($destination, $content);
        Storage::disk(KmFileService::DISK)->put($backup, $content);
        $document->forceFill([
            'file_disk' => KmFileService::DISK,
            'file_path' => $destination,
            'file_original_name' => $legacyName,
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($content),
            'file_checksum_sha256' => $checksum,
            'file_migrated_at' => now(),
        ])->save();

        $this->assertFalse(File::exists($source));
        $this->assertSame(0, Artisan::call('km:migrate-private-files'), Artisan::output());
        $this->assertSame(1, $this->matchingManifestEntryCount($document, $destination, $checksum));

        $manifestFiles = Storage::disk(KmFileService::DISK)->allFiles('file-migrations');
        $this->assertCount(1, $manifestFiles);
        $this->assertSame(0, Artisan::call('km:migrate-private-files'), Artisan::output());
        $this->assertSame($manifestFiles, Storage::disk(KmFileService::DISK)->allFiles('file-migrations'));
        $this->assertSame(1, $this->matchingManifestEntryCount($document, $destination, $checksum));

        $this->assertSame(0, Artisan::call('km:migrate-private-files', [
            '--restore-manifest' => Storage::disk(KmFileService::DISK)->path($manifestFiles[0]),
        ]), Artisan::output());
        $document->refresh();
        $this->assertNull($document->file_disk);
        $this->assertNull($document->file_path);
        $this->assertSame($checksum, hash_file('sha256', $source));
    }

    public function test_manifest_restore_rejects_wrong_envelope_and_unsafe_metadata_snapshots(): void
    {
        $owner = $this->user(2123, 'Manifest Validation Owner');
        $legacyName = 'km-manifest-validation-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $content = "%PDF-1.4\nmanifest validation\n%%EOF";
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $this->temporaryLegacyFiles[] = $source;
        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
        ]);
        $this->assertSame(0, Artisan::call('km:migrate-private-files'), Artisan::output());
        $manifest = Storage::disk(KmFileService::DISK)->allFiles('file-migrations')[0];
        $original = json_decode(
            Storage::disk(KmFileService::DISK)->get($manifest),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $status = $document->refresh()->status;

        $mutations = [
            static function (array $payload): array {
                $payload['version'] = 999;

                return $payload;
            },
            static function (array $payload): array {
                $payload['database'] = 'other_database_testing';

                return $payload;
            },
            static function (array $payload): array {
                $payload['entries'][0]['old_metadata']['status'] = 1;

                return $payload;
            },
            static function (array $payload): array {
                unset($payload['entries'][0]['old_metadata']['file_disk']);

                return $payload;
            },
            static function (array $payload): array {
                $payload['entries'][0]['old_metadata']['file_disk'] = 'public';

                return $payload;
            },
            static function (array $payload): array {
                $payload['entries'][0]['new_metadata']['status'] = 1;

                return $payload;
            },
            static function (array $payload): array {
                unset($payload['entries'][0]['new_metadata']['file_disk']);

                return $payload;
            },
            static function (array $payload): array {
                $payload['entries'][0]['new_metadata']['file_size_bytes'] = '123';

                return $payload;
            },
            static function (array $payload): array {
                $payload['entries'][0]['new_metadata']['file_disk'] = 'public';

                return $payload;
            },
            static function (array $payload): array {
                $payload['entries'][0]['new_metadata']['file_path'] = sprintf(
                    'documents/%d/%s.pdf',
                    $payload['entries'][0]['document_id'],
                    Str::uuid(),
                );

                return $payload;
            },
            static function (array $payload): array {
                $payload['entries'][0]['new_metadata']['file_checksum_sha256'] = str_repeat('0', 64);

                return $payload;
            },
        ];

        foreach ($mutations as $mutation) {
            Storage::disk(KmFileService::DISK)->put(
                $manifest,
                json_encode($mutation($original), JSON_THROW_ON_ERROR),
            );
            $this->assertSame(1, Artisan::call('km:migrate-private-files', [
                '--restore-manifest' => Storage::disk(KmFileService::DISK)->path($manifest),
            ]), Artisan::output());
            $document->refresh();
            $this->assertSame(KmFileService::DISK, $document->file_disk);
            $this->assertSame($status, $document->status);
            $this->assertFalse(File::exists($source));
        }
    }

    public function test_manifest_restore_rejects_changes_to_every_non_key_private_metadata_field(): void
    {
        $owner = $this->user(2124, 'Manifest State Guard Owner');
        $legacyName = 'km-manifest-state-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $content = "%PDF-1.4\nmanifest state guard\n%%EOF";
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $this->temporaryLegacyFiles[] = $source;
        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
        ]);

        $this->assertSame(0, Artisan::call('km:migrate-private-files'), Artisan::output());
        $manifest = Storage::disk(KmFileService::DISK)->allFiles('file-migrations')[0];
        $manifestPath = Storage::disk(KmFileService::DISK)->path($manifest);
        $payload = json_decode(
            Storage::disk(KmFileService::DISK)->get($manifest),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $newMetadata = $payload['entries'][0]['new_metadata'];
        $destination = $payload['entries'][0]['destination'];
        $backup = $payload['entries'][0]['backup'];
        $tamperedValues = [
            'file_original_name' => 'changed-'.$legacyName,
            'file_mime_type' => 'application/x-pdf',
            'file_size_bytes' => $newMetadata['file_size_bytes'] + 1,
            'file_migrated_at' => '2001-01-01 00:00:00',
        ];

        foreach ($tamperedValues as $field => $tamperedValue) {
            $document->forceFill($newMetadata)->save();
            $document->forceFill([$field => $tamperedValue])->save();
            $document->refresh();
            $metadataBeforeRestore = array_intersect_key(
                $document->getAttributes(),
                array_flip(array_keys($newMetadata)),
            );

            $this->assertSame(1, Artisan::call('km:migrate-private-files', [
                '--restore-manifest' => $manifestPath,
            ]), Artisan::output());

            $document->refresh();
            $this->assertSame(
                $metadataBeforeRestore,
                array_intersect_key($document->getAttributes(), array_flip(array_keys($newMetadata))),
            );
            $this->assertFalse(File::exists($source));
            Storage::disk(KmFileService::DISK)->assertExists($destination);
            Storage::disk(KmFileService::DISK)->assertExists($backup);
        }
    }

    public function test_legacy_file_migration_compensates_when_public_delete_returns_false(): void
    {
        $owner = $this->user(2117, 'Delete Failure Owner');
        $legacyName = 'km-delete-failure-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $content = "%PDF-1.4\ndelete failure\n%%EOF";
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $this->temporaryLegacyFiles[] = $source;

        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
            'file_disk' => null,
            'file_path' => null,
        ]);
        $backup = 'legacy-backup/'.$document->id.'/'.$legacyName;
        Storage::disk(KmFileService::DISK)->put($backup, $content);
        $metadataAtDelete = null;

        File::partialMock()
            ->shouldReceive('delete')
            ->once()
            ->with($source)
            ->andReturnUsing(function () use ($document, &$metadataAtDelete): bool {
                $metadataAtDelete = $document->fresh()?->only([
                    'file_disk',
                    'file_path',
                    'file_checksum_sha256',
                ]);

                return false;
            });

        try {
            $exitCode = Artisan::call('km:migrate-private-files');
        } finally {
            File::swap(new Filesystem());
        }

        $this->assertSame(1, $exitCode, Artisan::output());
        $this->assertSame(KmFileService::DISK, $metadataAtDelete['file_disk'] ?? null);
        $this->assertNotEmpty($metadataAtDelete['file_path'] ?? null);
        $this->assertSame(hash('sha256', $content), $metadataAtDelete['file_checksum_sha256'] ?? null);
        $document->refresh();
        $this->assertNull($document->file_disk);
        $this->assertNull($document->file_path);
        $this->assertSame(hash('sha256', $content), hash_file('sha256', $source));
        $this->assertSame([], Storage::disk(KmFileService::DISK)->allFiles('documents'));
        Storage::disk(KmFileService::DISK)->assertExists($backup);
    }

    public function test_legacy_file_migration_recovers_from_destination_when_backup_verification_fails(): void
    {
        $owner = $this->user(2121, 'Backup Verification Failure Owner');
        $legacyName = 'km-backup-verification-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $content = "%PDF-1.4\nbackup verification failure\n%%EOF";
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $this->temporaryLegacyFiles[] = $source;

        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
            'file_disk' => null,
            'file_path' => null,
        ]);
        $backup = 'legacy-backup/'.$document->id.'/'.$legacyName;
        $backupPath = Storage::disk(KmFileService::DISK)->path($backup);
        $metadataAtMove = null;

        File::partialMock()
            ->shouldReceive('move')
            ->once()
            ->with($source, $backupPath)
            ->andReturnUsing(function (string $from, string $to) use ($document, &$metadataAtMove): bool {
                $metadataAtMove = $document->fresh()?->only(['file_disk', 'file_path']);
                if (! rename($from, $to)) {
                    return false;
                }

                file_put_contents($to, 'corrupt backup payload');

                return true;
            });

        try {
            $exitCode = Artisan::call('km:migrate-private-files');
        } finally {
            File::swap(new Filesystem());
        }

        $this->assertSame(1, $exitCode, Artisan::output());
        $this->assertSame(KmFileService::DISK, $metadataAtMove['file_disk'] ?? null);
        $this->assertNotEmpty($metadataAtMove['file_path'] ?? null);
        $document->refresh();
        $this->assertNull($document->file_disk);
        $this->assertNull($document->file_path);
        $this->assertSame(hash('sha256', $content), hash_file('sha256', $source));
        $this->assertSame([], Storage::disk(KmFileService::DISK)->allFiles('documents'));
        Storage::disk(KmFileService::DISK)->assertMissing($backup);
    }

    public function test_manifest_restore_keeps_private_metadata_when_copy_returns_false(): void
    {
        $owner = $this->user(2118, 'Copy Failure Owner');
        $legacyName = 'km-copy-failure-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        $content = "%PDF-1.4\ncopy failure\n%%EOF";
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $this->temporaryLegacyFiles[] = $source;

        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
            'file_disk' => null,
            'file_path' => null,
        ]);
        $this->assertSame(0, Artisan::call('km:migrate-private-files'), Artisan::output());
        $document->refresh();
        $destination = (string) $document->file_path;
        $backupPath = Storage::disk(KmFileService::DISK)->path(
            'legacy-backup/'.$document->id.'/'.$legacyName,
        );
        $manifest = Storage::disk(KmFileService::DISK)->allFiles('file-migrations')[0];

        File::partialMock()
            ->shouldReceive('copy')
            ->once()
            ->with($backupPath, $source)
            ->andReturnFalse();

        try {
            $exitCode = Artisan::call('km:migrate-private-files', [
                '--restore-manifest' => Storage::disk(KmFileService::DISK)->path($manifest),
            ]);
        } finally {
            File::swap(new Filesystem());
        }

        $this->assertSame(1, $exitCode, Artisan::output());
        $document->refresh();
        $this->assertSame(KmFileService::DISK, $document->file_disk);
        $this->assertSame($destination, $document->file_path);
        $this->assertFalse(File::exists($source));
        Storage::disk(KmFileService::DISK)->assertExists($destination);
    }

    public function test_manifest_restore_refuses_to_overwrite_public_file_with_different_checksum(): void
    {
        $owner = $this->user(2112, 'Restore Guard Owner');
        $legacyName = 'km-restore-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        File::ensureDirectoryExists(dirname($source));
        File::put($source, "%PDF-1.4\noriginal");
        $this->temporaryLegacyFiles[] = $source;

        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => $legacyName,
            'file_name' => $legacyName,
            'file_disk' => null,
            'file_path' => null,
        ]);
        $this->assertSame(0, Artisan::call('km:migrate-private-files'), Artisan::output());
        $document->refresh();
        $destination = $document->file_path;
        $manifest = Storage::disk(KmFileService::DISK)->allFiles('file-migrations')[0];

        File::put($source, 'different public content');
        $this->assertSame(1, Artisan::call('km:migrate-private-files', [
            '--restore-manifest' => Storage::disk(KmFileService::DISK)->path($manifest),
        ]));

        $document->refresh();
        $this->assertSame(KmFileService::DISK, $document->file_disk);
        $this->assertSame($destination, $document->file_path);
        $this->assertSame('different public content', File::get($source));
    }

    private function user(int $id, string $name): User
    {
        return User::factory()->create([
            'id' => $id,
            'name' => $name,
            'role_id' => 4,
        ]);
    }

    private function privateDocument(
        User $owner,
        string $mime,
        string $extension,
        string $content,
        string $position = 'All Employee',
    ): KmPengajuan {
        $document = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'posisi' => $position,
        ]);
        $path = sprintf('documents/%d/%s.%s', $document->id, Str::uuid(), $extension);
        Storage::disk(KmFileService::DISK)->put($path, $content);
        $document->forceFill([
            'file' => basename($path),
            'file_name' => 'Materi.'.$extension,
            'file_disk' => KmFileService::DISK,
            'file_path' => $path,
            'file_original_name' => 'Materi.'.$extension,
            'file_mime_type' => $mime,
            'file_size_bytes' => strlen($content),
            'file_checksum_sha256' => hash('sha256', $content),
            'file_migrated_at' => now(),
        ])->save();

        return $document->refresh();
    }

    private function matchingManifestEntryCount(
        KmPengajuan $document,
        string $destination,
        string $checksum,
    ): int {
        $count = 0;
        foreach (Storage::disk(KmFileService::DISK)->allFiles('file-migrations') as $manifest) {
            $payload = json_decode(
                Storage::disk(KmFileService::DISK)->get($manifest),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
            foreach ($payload['entries'] ?? [] as $entry) {
                if ((int) ($entry['document_id'] ?? 0) === (int) $document->getKey()
                    && ($entry['destination'] ?? null) === $destination
                    && ($entry['checksum_sha256'] ?? null) === $checksum) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
