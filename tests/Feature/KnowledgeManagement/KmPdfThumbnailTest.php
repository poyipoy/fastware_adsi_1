<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmThumbnailStatus;
use App\Jobs\KnowledgeManagement\GenerateKmPdfThumbnail;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmPdfThumbnailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class KmPdfThumbnailTest extends KmTestCase
{
    private User $owner;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $roleId = DB::table('roles')->insertGetId(['role' => 'Employee', 'created_at' => now(), 'updated_at' => now()]);

        $this->owner = User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
            'km_total_poin' => 0,
        ]);

        $this->viewer = User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
            'km_total_poin' => 0,
        ]);
    }

    /** @test */
    public function unauthenticated_cannot_access_thumbnail(): void
    {
        $doc = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
        ]);

        $response = $this->get(route('km.documents.thumbnail', $doc));
        $response->assertRedirect(); // Redirect to login
    }

    /** @test */
    public function thumbnail_route_returns_svg_when_thumbnail_missing(): void
    {
        $doc = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
            'thumbnail_status' => KmThumbnailStatus::MISSING->value,
            'thumbnail_path' => null,
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('km.documents.thumbnail', $doc));

        // Harus 200 dan content-type image/svg+xml (SVG fallback)
        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('image/svg+xml', $contentType ?? '');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control') ?? '');
    }

    /** @test */
    public function thumbnail_route_returns_svg_when_thumbnail_pending(): void
    {
        $doc = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
            'thumbnail_status' => KmThumbnailStatus::PENDING->value,
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('km.documents.thumbnail', $doc));

        $response->assertOk();
        $this->assertStringContainsString('svg', $response->headers->get('Content-Type') ?? '');
    }

    /** @test */
    public function thumbnail_route_is_forbidden_for_unauthorized_user(): void
    {
        $draftDoc = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::DRAFT->value,
            'posisi' => 'All Employee',
        ]);

        // viewer bukan owner dan bukan approver → tidak boleh akses draft
        $response = $this->actingAs($this->viewer)
            ->get(route('km.documents.thumbnail', $draftDoc));

        $response->assertForbidden();
    }

    /** @test */
    public function thumbnail_status_enum_has_expected_values(): void
    {
        $this->assertSame('missing', KmThumbnailStatus::MISSING->value);
        $this->assertSame('pending', KmThumbnailStatus::PENDING->value);
        $this->assertSame('processing', KmThumbnailStatus::PROCESSING->value);
        $this->assertSame('ready', KmThumbnailStatus::READY->value);
        $this->assertSame('unsupported', KmThumbnailStatus::UNSUPPORTED->value);
        $this->assertSame('unavailable', KmThumbnailStatus::UNAVAILABLE->value);
        $this->assertSame('failed', KmThumbnailStatus::FAILED->value);
    }

    /** @test */
    public function thumbnail_status_fallback_logic_is_correct(): void
    {
        $this->assertTrue(KmThumbnailStatus::MISSING->shouldUseFallback());
        $this->assertTrue(KmThumbnailStatus::PENDING->shouldUseFallback());
        $this->assertTrue(KmThumbnailStatus::FAILED->shouldUseFallback());
        $this->assertFalse(KmThumbnailStatus::READY->shouldUseFallback());
    }

    /** @test */
    public function km_pengajuan_casts_thumbnail_status_to_enum(): void
    {
        $doc = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
            'thumbnail_status' => 'pending',
        ]);

        $fresh = $doc->fresh();
        $this->assertInstanceOf(KmThumbnailStatus::class, $fresh->thumbnail_status);
        $this->assertSame(KmThumbnailStatus::PENDING, $fresh->thumbnail_status);
    }

    /** @test */
    public function new_migrations_have_reversible_up_and_down(): void
    {
        $files = [
            '2026_07_18_110001_create_km_bookmarks_table.php',
            '2026_07_18_110002_add_km_thumbnail_pipeline_fields_to_km_pengajuans.php',
            '2026_07_18_110003_create_km_tags_table.php',
            '2026_07_18_110004_create_km_document_tag_table.php',
            '2026_07_18_110005_create_km_document_authors_table.php',
            '2026_07_18_110006_add_km_authoring_metadata_to_km_pengajuans.php',
        ];
        $migrations = collect($files)
            ->mapWithKeys(fn (string $file): array => [$file => require database_path('migrations/'.$file)]);

        $migrations->reverse()->each(fn ($migration) => $migration->down());
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('km_bookmarks'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('km_tags'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('km_pengajuans', 'thumbnail_status'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('km_pengajuans', 'draft_revision'));

        $migrations->each(fn ($migration) => $migration->up());

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('km_bookmarks'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('km_tags'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('km_document_tag'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('km_document_authors'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('km_pengajuans', 'thumbnail_status'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('km_pengajuans', 'reading_minutes'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('km_pengajuans', 'draft_revision'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('km_pengajuans', 'autosaved_at'));

        $bookmarkIndexes = collect(DB::select('SHOW INDEX FROM km_bookmarks'))->pluck('Key_name');
        $this->assertTrue($bookmarkIndexes->contains('km_bookmarks_user_document_unique'));
        $this->assertTrue($bookmarkIndexes->contains('km_bookmarks_document_user_index'));

        $authorForeignKeys = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'km_document_authors')
            ->count();
        $this->assertGreaterThanOrEqual(2, $authorForeignKeys);

        $thumbnailColumn = DB::selectOne("SHOW COLUMNS FROM km_pengajuans LIKE 'thumbnail_status'");
        $this->assertSame('missing', $thumbnailColumn->Default);
    }

    /** @test */
    public function stale_job_does_not_modify_current_thumbnail_state(): void
    {
        $doc = $this->pdfDocument('new-checksum', KmThumbnailStatus::PENDING);
        $service = \Mockery::mock(KmPdfThumbnailService::class);
        $service->shouldNotReceive('probeCapability');

        (new GenerateKmPdfThumbnail($doc->getKey(), 'old-checksum'))->handle($service);

        $this->assertSame(KmThumbnailStatus::PENDING, $doc->fresh()->thumbnail_status);
    }

    /** @test */
    public function failed_handler_is_checksum_guarded(): void
    {
        $doc = $this->pdfDocument('new-checksum', KmThumbnailStatus::PENDING);

        (new GenerateKmPdfThumbnail($doc->getKey(), 'old-checksum'))
            ->failed(new \RuntimeException('stale failure'));

        $this->assertSame(KmThumbnailStatus::PENDING, $doc->fresh()->thumbnail_status);
    }

    /** @test */
    public function unavailable_binary_sets_checksum_guarded_fallback_status_without_throwing(): void
    {
        config(['knowledge_management.thumbnail.enabled' => true]);
        $doc = $this->pdfDocument(str_repeat('a', 64), KmThumbnailStatus::PENDING);
        $service = \Mockery::mock(KmPdfThumbnailService::class);
        $service->shouldReceive('probeCapability')->once()->andReturn('Binary tidak tersedia.');

        (new GenerateKmPdfThumbnail($doc->getKey(), str_repeat('a', 64)))->handle($service);

        $this->assertSame(KmThumbnailStatus::UNAVAILABLE, $doc->fresh()->thumbnail_status);
    }

    /** @test */
    public function checksum_change_during_generation_deletes_orphan_output(): void
    {
        config(['knowledge_management.thumbnail.enabled' => true]);
        Storage::fake('km_private');
        $checksum = str_repeat('b', 64);
        $doc = $this->pdfDocument($checksum, KmThumbnailStatus::PENDING);
        $newPath = 'thumbnails/'.$doc->getKey().'/11111111-1111-1111-1111-111111111111.png';
        Storage::disk('km_private')->put($newPath, $this->pngContent());

        $service = \Mockery::mock(KmPdfThumbnailService::class);
        $service->shouldReceive('probeCapability')->once()->andReturnNull();
        $service->shouldReceive('sourceValidationError')->once()->andReturnNull();
        $service->shouldReceive('generate')->once()->andReturnUsing(function () use ($doc, $newPath): string {
            DB::table('km_pengajuans')->where('id', $doc->getKey())->update([
                'file_checksum_sha256' => str_repeat('c', 64),
            ]);

            return $newPath;
        });
        $service->shouldReceive('deleteThumbnail')->once()->with($newPath)->andReturnUsing(
            fn (string $path) => Storage::disk('km_private')->delete($path),
        );

        (new GenerateKmPdfThumbnail($doc->getKey(), $checksum))->handle($service);

        Storage::disk('km_private')->assertMissing($newPath);
        $this->assertNotSame(KmThumbnailStatus::READY, $doc->fresh()->thumbnail_status);
    }

    /** @test */
    public function ready_thumbnail_requires_pdf_valid_png_and_matching_checksum(): void
    {
        Storage::fake('km_private');
        $checksum = str_repeat('d', 64);
        $doc = $this->pdfDocument($checksum, KmThumbnailStatus::READY);
        $path = 'thumbnails/'.$doc->getKey().'/22222222-2222-2222-2222-222222222222.png';
        Storage::disk('km_private')->put($path, $this->pngContent());
        $doc->forceFill([
            'thumbnail_path' => $path,
            'thumbnail_source_checksum' => $checksum,
        ])->save();

        $this->actingAs($this->viewer)
            ->get(route('km.documents.thumbnail', $doc))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $doc->forceFill(['thumbnail_source_checksum' => str_repeat('e', 64)])->save();
        $this->actingAs($this->viewer)
            ->get(route('km.documents.thumbnail', $doc))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    /** @test */
    public function ready_legacy_thumbnail_path_is_streamed(): void
    {
        Storage::fake('km_private');
        $checksum = str_repeat('3', 64);
        $doc = $this->pdfDocument($checksum, KmThumbnailStatus::READY);
        $path = 'thumbnails/'.$doc->getKey().'/AbCdEfGhIjKlMnOpQrStUvWxYz012345.png';
        Storage::disk('km_private')->put($path, $this->pngContent());
        $doc->forceFill([
            'thumbnail_path' => $path,
            'thumbnail_source_checksum' => $checksum,
        ])->save();

        $this->actingAs($this->viewer)
            ->get(route('km.documents.thumbnail', $doc))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    /** @test */
    public function thumbnail_path_from_another_document_directory_is_rejected(): void
    {
        Storage::fake('km_private');
        $checksum = str_repeat('4', 64);
        $doc = $this->pdfDocument($checksum, KmThumbnailStatus::READY);
        $path = 'thumbnails/'.($doc->getKey() + 1).'/AbCdEfGhIjKlMnOpQrStUvWxYz012345.png';
        Storage::disk('km_private')->put($path, $this->pngContent());
        $doc->forceFill([
            'thumbnail_path' => $path,
            'thumbnail_source_checksum' => $checksum,
        ])->save();

        $this->actingAs($this->viewer)
            ->get(route('km.documents.thumbnail', $doc))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    /** @test */
    public function source_size_limit_is_enforced_before_conversion(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'km-thumb-');
        file_put_contents($path, '12345');
        config(['knowledge_management.thumbnail.max_file_bytes' => 4]);

        try {
            $this->assertSame(
                'Ukuran file sumber melebihi batas thumbnail.',
                app(KmPdfThumbnailService::class)->sourceValidationError($path),
            );
        } finally {
            @unlink($path);
        }
    }

    /** @test */
    public function async_timeout_is_rethrown_for_queue_retry(): void
    {
        config([
            'knowledge_management.thumbnail.enabled' => true,
            'queue.default' => 'database',
        ]);
        $checksum = str_repeat('f', 64);
        $doc = $this->pdfDocument($checksum, KmThumbnailStatus::PENDING);
        $timeout = new ProcessTimedOutException(
            new Process(['pdftoppm']),
            ProcessTimedOutException::TYPE_GENERAL,
        );
        $service = \Mockery::mock(KmPdfThumbnailService::class);
        $service->shouldReceive('probeCapability')->once()->andReturnNull();
        $service->shouldReceive('sourceValidationError')->once()->andReturnNull();
        $service->shouldReceive('generate')->once()->andThrow($timeout);
        $service->shouldReceive('deleteThumbnail')->once()->with(null);
        $service->shouldReceive('sanitizeReason')->once()->andReturn('Konversi melewati batas waktu.');

        $job = new GenerateKmPdfThumbnail($doc->getKey(), $checksum);
        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300], $job->backoff);

        try {
            $job->handle($service);
            $this->fail('Timeout pada queue asynchronous harus dilempar ulang untuk retry.');
        } catch (ProcessTimedOutException $exception) {
            $this->assertSame($timeout, $exception);
        }

        $this->assertSame(KmThumbnailStatus::FAILED, $doc->fresh()->thumbnail_status);
    }

    /** @test */
    public function corrupt_pdf_marks_sync_job_failed_without_breaking_request(): void
    {
        config([
            'knowledge_management.thumbnail.enabled' => true,
            'queue.default' => 'sync',
        ]);
        $checksum = str_repeat('1', 64);
        $doc = $this->pdfDocument($checksum, KmThumbnailStatus::PENDING);
        $service = \Mockery::mock(KmPdfThumbnailService::class);
        $service->shouldReceive('probeCapability')->once()->andReturnNull();
        $service->shouldReceive('sourceValidationError')->once()->andReturnNull();
        $service->shouldReceive('generate')->once()->andThrow(new RuntimeException('PDF corrupt.'));
        $service->shouldReceive('deleteThumbnail')->once()->with(null);
        $service->shouldReceive('sanitizeReason')->once()->andReturn('PDF corrupt.');

        (new GenerateKmPdfThumbnail($doc->getKey(), $checksum))->handle($service);

        $fresh = $doc->fresh();
        $this->assertSame(KmThumbnailStatus::FAILED, $fresh->thumbnail_status);
        $this->assertSame('PDF corrupt.', $fresh->thumbnail_failure_reason);
    }

    /** @test */
    public function non_pdf_job_and_route_use_unsupported_svg_fallback(): void
    {
        config(['knowledge_management.thumbnail.enabled' => true]);
        $checksum = str_repeat('2', 64);
        $doc = KmPengajuan::factory()->published()->create([
            'id_user' => $this->owner->getKey(),
            'posisi' => 'All Employee',
            'file_mime_type' => 'application/vnd.ms-powerpoint',
            'file_checksum_sha256' => $checksum,
            'thumbnail_status' => KmThumbnailStatus::PENDING->value,
        ]);
        $service = \Mockery::mock(KmPdfThumbnailService::class);
        $service->shouldNotReceive('probeCapability');

        (new GenerateKmPdfThumbnail($doc->getKey(), $checksum))->handle($service);

        $this->assertSame(KmThumbnailStatus::UNSUPPORTED, $doc->fresh()->thumbnail_status);
        $this->actingAs($this->viewer)
            ->get(route('km.documents.thumbnail', $doc))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    private function pdfDocument(string $checksum, KmThumbnailStatus $status): KmPengajuan
    {
        Storage::fake('km_private');
        $doc = KmPengajuan::factory()->published()->create([
            'id_user' => $this->owner->getKey(),
            'posisi' => 'All Employee',
            'file_mime_type' => 'application/pdf',
            'file_checksum_sha256' => $checksum,
            'thumbnail_status' => $status->value,
        ]);
        $path = 'documents/'.$doc->getKey().'/33333333-3333-3333-3333-333333333333.pdf';
        Storage::disk('km_private')->put($path, "%PDF-1.4\n%%EOF");
        $doc->forceFill(['file_path' => $path])->save();

        return $doc;
    }

    private function pngContent(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
    }
}
