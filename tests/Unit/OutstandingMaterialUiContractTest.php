<?php

namespace Tests\Unit;

use Tests\TestCase;

class OutstandingMaterialUiContractTest extends TestCase
{
    public function test_outstanding_tables_use_high_specificity_sticky_rules(): void
    {
        foreach (['index.blade.php', 'show.blade.php', 'invoice.blade.php'] as $view) {
            $blade = file_get_contents(resource_path('views/outstanding_materials/' . $view));

            $this->assertStringContainsString('table.om-table.dataTable thead', $blade, $view);
            $this->assertStringContainsString('position:sticky', str_replace(' ', '', $blade), $view);
            $this->assertStringContainsString('scrollX:false', str_replace(' ', '', $blade), $view);
        }
    }

    public function test_outstanding_table_vertical_scroll_chains_to_page_at_boundary(): void
    {
        foreach (['index.blade.php', 'show.blade.php', 'invoice.blade.php', 'form.blade.php'] as $view) {
            $blade = str_replace(' ', '', file_get_contents(resource_path('views/outstanding_materials/' . $view)));

            $this->assertStringContainsString('overscroll-behavior-x:contain', $blade, $view);
            $this->assertStringContainsString('overscroll-behavior-y:auto', $blade, $view);
            $this->assertStringNotContainsString('overscroll-behavior:contain', $blade, $view);
        }
    }

    public function test_sticky_helper_remeasures_after_layout_and_datatables_changes(): void
    {
        $script = file_get_contents(public_path('assets/js/outstanding-materials/sticky-table.js'));

        $this->assertStringContainsString('ResizeObserver', $script);
        $this->assertStringContainsString('document.fonts.ready', $script);
        $this->assertStringContainsString('init.dt draw.dt column-sizing.dt', $script);
        $this->assertStringContainsString('.om-table-wrap', $script);
        $this->assertStringContainsString('om-column-header', $script);
        $this->assertStringContainsString('--om-table-filter-height', $script);
        $this->assertStringContainsString('pinCells(rows.columnHeaderRow, filterHeight, 5)', $script);
        $this->assertStringContainsString("cell.style.setProperty('position', 'sticky', 'important')", $script);
    }

    public function test_material_filters_precede_sticky_column_headers(): void
    {
        foreach (['index.blade.php', 'show.blade.php'] as $view) {
            $blade = file_get_contents(resource_path('views/outstanding_materials/' . $view));
            $filterPosition = strpos($blade, '<tr class="om-filter-row">');
            $headerPosition = strpos($blade, '<tr class="om-column-header">');

            $this->assertNotFalse($filterPosition, $view);
            $this->assertNotFalse($headerPosition, $view);
            $this->assertLessThan($headerPosition, $filterPosition, $view);
            $this->assertStringContainsString('--om-table-filter-height', $blade, $view);
            $this->assertStringContainsString('orderCellsTop:false', str_replace(' ', '', $blade), $view);
        }

        $invoice = file_get_contents(resource_path('views/outstanding_materials/invoice.blade.php'));
        $this->assertStringContainsString('<tr class="om-column-header">', $invoice);

        foreach (['index.blade.php', 'show.blade.php', 'invoice.blade.php'] as $view) {
            $blade = file_get_contents(resource_path('views/outstanding_materials/' . $view));

            $this->assertStringContainsString("sticky-table.js') }}?v={{ filemtime", $blade, $view);
        }
    }

    public function test_invoice_modal_selection_contract_supports_rows_and_select_all(): void
    {
        $view = file_get_contents(resource_path('views/outstanding_materials/invoice.blade.php'));
        $script = file_get_contents(public_path('assets/js/outstanding-materials/invoice-update-selection.js'));

        $this->assertStringContainsString('invoice-update-selection.js', $view);
        $this->assertStringContainsString('selectAllInvoiceMaterials', $script);
        $this->assertStringContainsString('MutationObserver', $script);
        $this->assertStringContainsString("event.target.closest('tr')", $script);
        $this->assertStringContainsString('indeterminate', $script);
        $this->assertStringContainsString('confirmInvoiceUpload', $view);
        $this->assertStringContainsString("icon:'warning'", $view);
        $this->assertStringContainsString('Yes, upload', $view);
    }

    public function test_form_and_detail_templates_expose_revised_navigation_and_export(): void
    {
        $form = file_get_contents(resource_path('views/outstanding_materials/form.blade.php'));
        $batch = file_get_contents(resource_path('views/outstanding_materials/form-batch.blade.php'));
        $detail = file_get_contents(resource_path('views/outstanding_materials/show.blade.php'));

        $this->assertStringContainsString('$detailReturnAnchor', $form);
        $this->assertStringContainsString("route('outstanding-materials.show', " . '$detailReturnAnchor' . ")", $form);
        $this->assertStringContainsString("route('outstanding-materials.bulk-store')", $batch);
        $this->assertStringContainsString('materials[', $batch);
        $this->assertStringNotContainsString('Line Ref', $batch);
        $this->assertStringNotContainsString('line_ref', $batch);
        $this->assertStringContainsString('Import Multi-Invoice', $detail);
        $this->assertStringNotContainsString('Line Ref', $detail);
        $this->assertStringNotContainsString('line_ref', $detail);
        $this->assertStringContainsString('@if ($canExportInvoice)', $detail);
        $this->assertStringContainsString('Export Invoice', $detail);

        $index = file_get_contents(resource_path('views/outstanding_materials/index.blade.php'));
        $invoice = file_get_contents(resource_path('views/outstanding_materials/invoice.blade.php'));
        $preview = file_get_contents(resource_path('views/outstanding_materials/import-preview.blade.php'));
        foreach ([$index, $invoice, $preview] as $viewContent) {
            $this->assertStringNotContainsString('Line Ref', $viewContent);
            $this->assertStringNotContainsString('line_ref', $viewContent);
        }
        $this->assertStringNotContainsString('update_existing', $index);
        $this->assertStringNotContainsString('insert_only', $index);
        $this->assertStringContainsString('btnOutstandingExport', $index);
        $this->assertStringContainsString("route('outstanding-materials.export')", $index);
        $this->assertStringContainsString("route('outstanding-materials.create')", $index);
        $this->assertStringContainsString('importOutstandingMaterialModal', $index);
    }

    public function test_detail_delete_uses_delegated_warning_confirmation(): void
    {
        $view = file_get_contents(resource_path('views/outstanding_materials/show.blade.php'));
        $script = file_get_contents(public_path('assets/js/outstanding-materials/delete-confirmation.js'));

        $this->assertStringContainsString('delete-confirmation.js', $view);
        $this->assertStringContainsString('js-outstanding-delete-form', $script);
        $this->assertStringContainsString("icon: 'warning'", $script);
        $this->assertStringContainsString('showCancelButton', $script);
        $this->assertStringContainsString('HTMLFormElement.prototype.submit.call(form)', $script);
        $this->assertStringContainsString('js-outstanding-invoice-delete-form', $script);
        $this->assertStringContainsString('Hapus Invoice Permanen?', $script);
        $this->assertStringContainsString(".js-outstanding-invoice-delete-form [type=\"submit\"]", $script);
        $this->assertStringContainsString('confirmationPending', $script);

        foreach (['invoice.blade.php', 'show.blade.php'] as $view) {
            $blade = file_get_contents(resource_path('views/outstanding_materials/' . $view));

            $this->assertStringContainsString("delete-confirmation.js') }}?v={{ filemtime", $blade, $view);
        }
    }

    public function test_total_material_kpi_includes_kg_without_a_duplicate_detail_card(): void
    {
        $index = file_get_contents(resource_path('views/outstanding_materials/index.blade.php'));
        $detail = file_get_contents(resource_path('views/outstanding_materials/show.blade.php'));

        foreach ([$index, $detail] as $view) {
            $this->assertStringContainsString('Total KG:', $view);
            $this->assertStringContainsString('om-summary-meta', $view);
        }

        $this->assertStringNotContainsString('Estimated KG', $detail);
    }
}
