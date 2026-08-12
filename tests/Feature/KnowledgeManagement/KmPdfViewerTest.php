<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmThumbnailStatus;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmFileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * KmPdfViewerTest
 *
 * Verifikasi endpoint preview, download, dan thumbnail yang digunakan oleh
 * komponen pdf-viewer.js (pdfjs-dist 2.14.305 lokal melalui Vite).
 */
final class KmPdfViewerTest extends KmTestCase
{
    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(KmFileService::DISK);

        $role = DB::table('roles')->where('role', 'Employee')->first();
        if ($role) {
            $this->roleId = $role->id;
        } else {
            $this->roleId = DB::table('roles')->insertGetId([
                'role' => 'Employee',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function makeEmployee(string $name): User
    {
        return User::factory()->create([
            'role_id' => $this->roleId,
            'name' => $name,
            'is_active' => true,
            'km_total_poin' => 0,
        ]);
    }

    private function makePublishedPdfDocument(User $owner): KmPengajuan
    {
        $kategori = KmKategori::factory()->create(['poin_kategori' => 10]);
        $content = "%PDF-1.4\n%%EOF";
        $document = KmPengajuan::factory()->create([
            'id_user' => $owner->id,
            'id_km_kategori' => $kategori->id,
            'status' => KmDocumentStatus::PUBLISHED->value,
            'persetujuan' => 2,
            'posisi' => 'All Employee',
            'thumbnail_status' => KmThumbnailStatus::UNAVAILABLE->value,
        ]);
        $path = 'documents/'.$document->getKey().'/'.Str::uuid().'.pdf';
        Storage::disk(KmFileService::DISK)->put($path, $content);

        $document->forceFill([
            'file' => basename($path),
            'file_name' => 'sample.pdf',
            'file_disk' => KmFileService::DISK,
            'file_path' => $path,
            'file_original_name' => 'sample.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($content),
            'file_checksum_sha256' => hash('sha256', $content),
            'file_migrated_at' => now(),
        ])->save();

        return $document;
    }

    private function makePublishedOfficeDocument(User $owner): KmPengajuan
    {
        $kategori = KmKategori::factory()->create(['poin_kategori' => 10]);
        $content = 'fake-pptx-binary-data';
        $document = KmPengajuan::factory()->create([
            'id_user' => $owner->id,
            'id_km_kategori' => $kategori->id,
            'status' => KmDocumentStatus::PUBLISHED->value,
            'persetujuan' => 2,
            'posisi' => 'All Employee',
            'thumbnail_status' => KmThumbnailStatus::UNSUPPORTED->value,
        ]);
        $path = 'documents/'.$document->getKey().'/'.Str::uuid().'.pptx';
        Storage::disk(KmFileService::DISK)->put($path, $content);

        $document->forceFill([
            'file' => basename($path),
            'file_name' => 'sample.pptx',
            'file_disk' => KmFileService::DISK,
            'file_path' => $path,
            'file_original_name' => 'sample.pptx',
            'file_mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'file_size_bytes' => strlen($content),
            'file_checksum_sha256' => hash('sha256', $content),
            'file_migrated_at' => now(),
        ])->save();

        return $document;
    }

    public function test_authenticated_user_can_preview_published_pdf(): void
    {
        $owner = $this->makeEmployee('PDF Owner');
        $reader = $this->makeEmployee('PDF Reader');
        $document = $this->makePublishedPdfDocument($owner);

        $response = $this->actingAs($reader)->get(route('km.documents.preview', $document));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('inline;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_guest_cannot_access_preview_endpoint(): void
    {
        $owner = $this->makeEmployee('PDF Owner 2');
        $document = $this->makePublishedPdfDocument($owner);

        $response = $this->get(route('km.documents.preview', $document));

        $this->assertTrue($response->isRedirect() || $response->status() === 401);
    }

    public function test_authenticated_user_cannot_download_published_pdf(): void
    {
        $owner = $this->makeEmployee('DL Owner');
        $reader = $this->makeEmployee('DL Reader');
        $document = $this->makePublishedPdfDocument($owner);

        $response = $this->actingAs($reader)->get(route('km.documents.download', $document));

        $response->assertForbidden();
        $this->assertFalse($response->headers->has('Content-Disposition'));
    }

    public function test_guest_cannot_access_download_endpoint(): void
    {
        $owner = $this->makeEmployee('DL Owner 2');
        $document = $this->makePublishedPdfDocument($owner);

        $response = $this->get(route('km.documents.download', $document));

        $this->assertTrue($response->isRedirect() || $response->status() === 401);
    }

    public function test_office_document_preview_does_not_serve_application_pdf_content_type(): void
    {
        $owner = $this->makeEmployee('Office Owner');
        $reader = $this->makeEmployee('Office Reader');
        $document = $this->makePublishedOfficeDocument($owner);

        $response = $this->actingAs($reader)->get(route('km.documents.preview', $document));

        if ($response->isOk()) {
            $this->assertNotEquals('application/pdf', $response->headers->get('Content-Type'));
        } else {
            $this->assertContains($response->status(), [415, 403, 422]);
        }
    }

    public function test_thumbnail_endpoint_returns_image_for_published_document(): void
    {
        $owner = $this->makeEmployee('Thumb Owner');
        $reader = $this->makeEmployee('Thumb Reader');
        $document = $this->makePublishedPdfDocument($owner);

        $response = $this->actingAs($reader)->get(route('km.documents.thumbnail', $document));

        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('Content-Type'));
    }

    public function test_thumbnail_endpoint_requires_authentication(): void
    {
        $owner = $this->makeEmployee('Thumb Owner 2');
        $document = $this->makePublishedPdfDocument($owner);

        $response = $this->get(route('km.documents.thumbnail', $document));

        $this->assertTrue($response->isRedirect() || $response->status() === 401);
    }

    public function test_employee_cannot_preview_draft_document(): void
    {
        $owner = $this->makeEmployee('Draft Owner');
        $reader = $this->makeEmployee('Draft Reader');
        $kategori = KmKategori::factory()->create(['poin_kategori' => 10]);
        $content = "%PDF-1.4\n%%EOF";
        $document = KmPengajuan::factory()->create([
            'id_user' => $owner->id,
            'id_km_kategori' => $kategori->id,
            'status' => KmDocumentStatus::DRAFT->value,
            'persetujuan' => 1,
            'posisi' => 'All Employee',
            'thumbnail_status' => KmThumbnailStatus::UNAVAILABLE->value,
        ]);
        $path = 'documents/'.$document->getKey().'/'.Str::uuid().'.pdf';
        Storage::disk(KmFileService::DISK)->put($path, $content);
        $document->forceFill([
            'file' => basename($path),
            'file_name' => 'draft.pdf',
            'file_disk' => KmFileService::DISK,
            'file_path' => $path,
            'file_original_name' => 'draft.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($content),
            'file_checksum_sha256' => hash('sha256', $content),
            'file_migrated_at' => now(),
        ])->save();

        $response = $this->actingAs($reader)->get(route('km.documents.preview', $document));

        $response->assertForbidden();
    }

    public function test_pdf_viewer_uses_one_local_vite_entry_with_server_synced_progress(): void
    {
        $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
        $view = file_get_contents(resource_path('views/dashboard/dsKnowlege.blade.php'));
        $viewer = file_get_contents(resource_path('js/km/pdf-viewer.js'));
        $vite = file_get_contents(base_path('vite.config.js'));

        $this->assertSame('2.14.305', $package['dependencies']['pdfjs-dist']);
        $this->assertStringContainsString('pdf.worker.min.js?url', $viewer);
        $this->assertStringContainsString("method: 'PATCH'", $viewer);
        $this->assertStringContainsString("document.visibilityState === 'visible'", $viewer);
        $this->assertStringContainsString('hasReadingLease()', $viewer);
        $this->assertStringContainsString('completionEligible', $viewer);
        $this->assertStringNotContainsString('openKmDocument', $viewer);
        $this->assertStringContainsString('resources/js/km/dashboard.js', $view);
        $this->assertStringNotContainsString("resources/js/km/pdf-viewer.js'", $view);
        $this->assertSame(0, substr_count($vite, "'resources/js/km/pdf-viewer.js'"));
    }
}
