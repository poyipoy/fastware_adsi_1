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
        $shell = (string) file_get_contents(resource_path('js/km/shell.js'));
        $insights = (string) file_get_contents(resource_path('js/km/insights.js'));

        $this->assertDoesNotMatchRegularExpression('/\bdata-(?:toggle|target|dismiss)=/', $view);
        $this->assertStringNotContainsString('assets/files/', $view);
        $this->assertSame(1, substr_count($view, 'id="km-viewer-modal"'));
        $this->assertSame(1, substr_count($view, 'id="km-insight-modal"'));
        $this->assertStringContainsString('data-preview-url="{{ $hasFile ? route(\'km.documents.preview\'', $view);
        $this->assertStringNotContainsString('data-download-url', $view);
        $this->assertStringNotContainsString('km-viewer-download-link', $view);
        $this->assertStringNotContainsString("route('km.documents.download'", $view);
        $this->assertStringContainsString("@vite(['resources/css/km/foundation.css', 'resources/css/km/dashboard.css'])", $view);
        $this->assertStringContainsString("@vite('resources/js/km/dashboard.js')", $view);
        $this->assertStringNotContainsString("@vite('resources/js/km/pdf-viewer.js')", $view);
        $this->assertStringContainsString('title="Knowledge Workspace"', $view);
        $this->assertStringContainsString('$'.'workspaceSummary[\'reading_count\']', $view);
        $this->assertStringContainsString(' class="km-summary-card"', $view);
        $this->assertStringContainsString('class="km-filter-chip"', $view);
        $this->assertStringContainsString('data-km-advanced-filter', $view);
        $this->assertStringContainsString('data-km-tag-filter', $view);
        $this->assertStringContainsString('data-km-tag-search', $view);
        $this->assertStringContainsString('name="tag_ids[]"', $view);
        $this->assertStringContainsString('class="km-filter-actions"', $view);
        $this->assertStringContainsString('name="mandatory"', $view);
        $this->assertStringContainsString('$'.'mandatorySummary[\'active_count\']', $view);
        $this->assertStringContainsString('$'.'mandatoryMaterials->isNotEmpty()', $view);
        $this->assertStringContainsString('id="km-mandatory-title"', $view);
        $this->assertStringContainsString('km-catalog-mandatory-meta', $view);
        $this->assertStringNotContainsString('<select id="km-tags"', $view);
        $this->assertStringContainsString('$'.'pengajuan->tags->take(2)', $view);
        $this->assertStringContainsString('data-km-insight-count', $view);
        $this->assertStringContainsString('data-km-insight-mention-search', $view);
        $this->assertStringContainsString('data-km-insight-mention-status', $view);
        $this->assertStringContainsString('maximumMentions:', $view);
        $this->assertStringContainsString('$leader->leaderboard_rank', $view);
        $this->assertStringContainsString("@can('create', \\App\\Models\\KmPengajuan::class)", $view);
        $this->assertStringContainsString('bootstrap.Modal.getOrCreateInstance', $viewer);
        $this->assertStringContainsString('trigger.dataset.previewUrl', $viewer);
        $this->assertStringNotContainsString('trigger.dataset.downloadUrl', $viewer);
        $this->assertStringContainsString('payload.already_completed', $dashboard);
        $this->assertStringContainsString('function initAdvancedTagFilter()', $dashboard);
        $this->assertStringContainsString('initAdvancedTagFilter();', $dashboard);
        $this->assertStringContainsString("url.searchParams.set('q', normalizedQuery)", $insights);
        $this->assertStringContainsString('new AbortController()', $insights);
        $this->assertStringContainsString('const selectedMentions = new Map()', $insights);
        $this->assertStringContainsString('}, 300);', $insights);
        $this->assertStringContainsString('flushProgress', $viewer);
        $this->assertStringContainsString('data-resume-page', $view);
        $this->assertStringContainsString('acknowledged: true', $dashboard);
        $this->assertStringContainsString("url.searchParams.set('assignment'", $shell);
    }

    public function test_km_shell_is_single_column_and_button_states_have_explicit_contrast(): void
    {
        $shell = (string) file_get_contents(resource_path('views/components/km/shell.blade.php'));
        $pageHeader = (string) file_get_contents(resource_path('views/components/km/page-header.blade.php'));
        $dashboard = (string) file_get_contents($this->kmViews()['dashboard']);
        $foundation = (string) file_get_contents(resource_path('css/km/foundation.css'));

        foreach (['kmWorkspaceNavigation', 'km-sidebar', 'data-bs-toggle="offcanvas"'] as $removedNavigation) {
            $this->assertStringNotContainsString($removedNavigation, $shell);
            $this->assertStringNotContainsString($removedNavigation, $foundation);
        }

        $this->assertStringContainsString('id="km-main-content"', $shell);
        $this->assertStringNotContainsString('km-shell-utility', $shell);
        $this->assertStringNotContainsString('km-notification-trigger', $shell);
        $this->assertMatchesRegularExpression(
            '/<div class="km-page-header__actions">.*km-notification-trigger.*<\/div>\s*<\/header>/s',
            $pageHeader,
        );
        $this->assertStringContainsString('.km-app a:not(.btn):not(.page-link)', $foundation);
        $this->assertMatchesRegularExpression(
            '/\.km-app \.btn-primary\s*\{[^}]*--bs-btn-color:\s*var\(--km-white\)/s',
            $foundation,
        );
        $this->assertMatchesRegularExpression(
            '/\.km-app \.btn-outline-primary\s*\{[^}]*--bs-btn-hover-color:\s*var\(--km-white\)/s',
            $foundation,
        );
        $this->assertMatchesRegularExpression(
            '/\.km-app \.page-item\.active \.page-link\s*\{[^}]*color:\s*var\(--km-white\)/s',
            $foundation,
        );

        foreach (['create', 'bulkApprove', 'viewPopularAnalytics'] as $ability) {
            $this->assertStringContainsString("@can('$ability', \\App\\Models\\KmPengajuan::class)", $dashboard);
        }
    }

    public function test_authoring_forms_are_the_scrollable_modal_content(): void
    {
        $view = (string) file_get_contents($this->kmViews()['pengajuan']);
        $authoringStyles = (string) file_get_contents(resource_path('css/km/authoring.css'));
        $authoring = (string) file_get_contents(resource_path('js/km/authoring.js'));

        $this->assertSame(2, substr_count($view, 'modal-dialog-scrollable'));
        $this->assertSame(3, substr_count($view, 'class="modal-content"'));
        $this->assertSame(2, substr_count($view, 'data-km-tag-feedback'));
        $this->assertSame(2, substr_count($view, 'aria-live="polite" hidden'));

        foreach (['km-create-form', 'km-draft-form'] as $formId) {
            $this->assertMatchesRegularExpression(
                '/<div class="modal-dialog[^"]*modal-dialog-scrollable[^"]*">\s*<form\b(?=[^>]*class="modal-content")(?=[^>]*id="'.preg_quote($formId, '/').'")[^>]*>/s',
                $view,
            );
        }

        $this->assertStringContainsString('.km-app .modal-dialog-scrollable .modal-body', $authoringStyles);
        $this->assertStringContainsString('overflow-y: auto;', $authoringStyles);
        $this->assertStringContainsString('overscroll-behavior: contain;', $authoringStyles);
        $this->assertStringContainsString('.split(/[,\r\n]+/)', $authoring);
        $this->assertStringContainsString("input.addEventListener('input'", $authoring);
        $this->assertStringContainsString("addEventListener('submit', () => commit())", $authoring);
        $this->assertStringContainsString('sudah ditambahkan. Gunakan tag yang berbeda.', $authoring);
        $this->assertStringContainsString("input.setAttribute('aria-invalid', 'true')", $authoring);
    }

    public function test_edit_view_requires_a_new_upload_when_private_metadata_is_missing(): void
    {
        $view = (string) file_get_contents($this->kmViews()['pengajuan']);
        $authoring = (string) file_get_contents(resource_path('js/km/authoring.js'));

        $this->assertStringContainsString('id="editFile"', $view);
        $this->assertStringContainsString('fileInput.required = ! data.has_file', $authoring);
        $this->assertStringContainsString("fileInput.value = '';", $authoring);
        $this->assertStringNotContainsString('data.download_url', $authoring);
        $this->assertStringNotContainsString('Unduh file tersimpan', $view);
        $this->assertStringContainsString('Unggah PDF, PPT, atau PPTX untuk menyimpan draf.', $authoring);
        $this->assertStringContainsString('PPT/PPTX dapat dipratinjau setelah konversi selesai; file asli tidak dapat diunduh.', $view);
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
        $this->assertStringContainsString('confirmAction', $approval);
        $this->assertStringNotContainsString('window.confirm', $approval);
    }
}
