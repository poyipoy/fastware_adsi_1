<?php

namespace Tests\Feature\KnowledgeManagement;

use Tests\TestCase;

class KmBladeCompatibilityTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function kmViews(): array
    {
        return [
            'pengajuan' => resource_path('views/knowlege_management/pengajuanKM.blade.php'),
            'persetujuan' => resource_path('views/knowlege_management/persetujuanKM.blade.php'),
            'dashboard' => resource_path('views/dashboard/dsKnowlege.blade.php'),
        ];
    }

    public function test_km_views_do_not_load_page_level_legacy_or_remote_runtimes(): void
    {
        $forbidden = [
            'http://',
            'https://',
            'jquery',
            'xlsx',
            'simple-datatables',
            'bootstrap/4',
            'pdf.js',
            'pdfjs',
            'sweetalert',
        ];

        foreach ($this->kmViews() as $name => $path) {
            $view = strtolower((string) file_get_contents($path));

            foreach ($forbidden as $runtime) {
                $this->assertStringNotContainsString($runtime, $view, "$name still loads $runtime");
            }

            $this->assertDoesNotMatchRegularExpression('/\$\s*(?:\(|\.)/', $view);
        }
    }

    public function test_server_paginated_listings_do_not_enable_client_datatables(): void
    {
        foreach (['pengajuan', 'persetujuan'] as $name) {
            $view = (string) file_get_contents($this->kmViews()[$name]);

            $this->assertStringNotContainsString('class="datatable table"', $view);
            $this->assertStringContainsString("links('pagination::bootstrap-5')", $view);
        }
    }

    public function test_dashboard_uses_private_document_routes_with_bootstrap_five_modal_fallbacks(): void
    {
        $view = (string) file_get_contents($this->kmViews()['dashboard']);
        $viewer = (string) file_get_contents(resource_path('js/km/pdf-viewer.js'));
        $dashboard = (string) file_get_contents(resource_path('js/km/dashboard.js'));

        $this->assertDoesNotMatchRegularExpression('/\bdata-(?:toggle|target|dismiss)=/', $view);
        $this->assertStringNotContainsString('assets/files/', $view);
        $this->assertStringContainsString('data-bs-toggle="modal"', $view);
        $this->assertSame(1, substr_count($view, 'id="km-viewer-modal"'));
        $this->assertStringContainsString('data-preview-url="{{ $hasFile ? route(\'km.documents.preview\'', $view);
        $this->assertStringContainsString('data-download-url="{{ $hasFile ? route(\'km.documents.download\'', $view);
        $this->assertStringContainsString("@vite(['resources/css/km/dashboard.css', 'resources/js/km/dashboard.js'])", $view);
        $this->assertStringNotContainsString("@vite('resources/js/km/pdf-viewer.js')", $view);
        $this->assertStringContainsString('bootstrap.Modal.getOrCreateInstance', $viewer);
        $this->assertStringContainsString('trigger.dataset.previewUrl', $viewer);
        $this->assertStringContainsString('trigger.dataset.downloadUrl', $viewer);
        $this->assertStringContainsString('payload.already_completed', $dashboard);
        $this->assertStringNotContainsString('progress', strtolower($viewer));
    }

    public function test_edit_view_requires_a_new_upload_when_private_metadata_is_missing(): void
    {
        $view = (string) file_get_contents($this->kmViews()['pengajuan']);
        $authoring = (string) file_get_contents(resource_path('js/km/authoring.js'));

        $this->assertStringContainsString('id="editFile"', $view);
        $this->assertStringContainsString('fileInput.required = ! data.has_file', $authoring);
        $this->assertStringContainsString("fileInput.value = '';", $authoring);
        $this->assertStringContainsString('data.download_url', $authoring);
        $this->assertStringContainsString('Unggah PDF, PPT, atau PPTX sebelum menyimpan.', $authoring);
        $this->assertStringNotContainsString('thumbnailInput', $authoring);
        $this->assertStringContainsString('aria-labelledby="kmModalLabel"', $view);
        $this->assertStringContainsString('id="kmModalLabel"', $view);
    }

    public function test_approval_modal_restores_all_validated_fields_and_action(): void
    {
        $view = (string) file_get_contents($this->kmViews()['persetujuan']);
        $approval = (string) file_get_contents(resource_path('js/km/approval.js'));

        foreach (['judul', 'posisi', 'id_km_kategori', 'keterangan', 'reason'] as $field) {
            $this->assertStringContainsString("@error('$field')", $view);
        }

        $this->assertMatchesRegularExpression('/<input(?=[^>]*name="judul")(?=[^>]*required)[^>]*>/s', $view);
        $this->assertMatchesRegularExpression('/<select(?=[^>]*name="posisi")(?=[^>]*required)[^>]*>/s', $view);
        $this->assertMatchesRegularExpression('/<select(?=[^>]*name="id_km_kategori")(?=[^>]*required)[^>]*>/s', $view);
        $this->assertStringContainsString('data-restore-id="{{ $hasApprovalErrors ? (int) old(\'id\') : \'\' }}"', $view);
        $this->assertStringContainsString('value="{{ $oldApprovalAction }}"', $view);
        $this->assertStringContainsString("@vite('resources/js/km/approval.js')", $view);
        $this->assertStringContainsString('openEditKmModal(restoreId, true)', $approval);
        $this->assertStringContainsString('prepareApprovalAction(preserved.action)', $approval);
        $this->assertStringNotContainsString('<script>', $view);
    }

    public function test_approval_view_has_scoped_bulk_controls_responsive_modal_and_no_js_fallback(): void
    {
        $view = (string) file_get_contents($this->kmViews()['persetujuan']);
        $approval = (string) file_get_contents(resource_path('js/km/approval.js'));

        $this->assertStringContainsString('route(\'km.approvals.bulk\')', $view);
        $this->assertStringContainsString('data-km-bulk-select-all', $view);
        $this->assertStringContainsString('data-km-bulk-category', $view);
        $this->assertStringContainsString('modal-fullscreen-sm-down', $view);
        $this->assertStringContainsString('<noscript>', $view);
        $this->assertStringContainsString('bulkSubmitting', $approval);
        $this->assertStringContainsString('window.confirm', $approval);
    }
}
