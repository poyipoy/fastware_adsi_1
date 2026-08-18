<?php

namespace Tests\Feature;

use App\Models\OutstandingMaterial;
use App\Models\User;
use App\Services\OutstandingMaterialAccessService;
use App\Services\OutstandingMaterialDocumentService;
use App\Http\Controllers\OutstandingMaterialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OutstandingMaterialControllerContractTest extends TestCase
{
    public function test_index_action_contract_is_read_only_even_for_manager(): void
    {
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $controller = new OutstandingMaterialController(
            new ContractAccessService(true),
            new OutstandingMaterialDocumentService(),
        );
        $material = new OutstandingMaterial([
            'supplier' => 'Supplier',
            'type' => 'TYPE',
            'number_invoice' => 'INV-1',
            'status' => OutstandingMaterial::STATUS_RECEIVED,
        ]);
        $material->id = 1;

        $method = new ReflectionMethod($controller, 'dataTableRow');
        $method->setAccessible(true);

        $index = $method->invoke($controller, $material, 'index');
        $detail = $method->invoke($controller, $material, 'detail');

        $this->assertStringContainsString('bi-eye', $index['actions']);
        $this->assertStringNotContainsString('/outstanding-materials/1/edit', $index['actions']);
        $this->assertStringNotContainsString('js-outstanding-delete-form', $index['actions']);
        $this->assertStringContainsString('/outstanding-materials/1/edit', $detail['actions']);
        $this->assertStringNotContainsString('bi-eye', $detail['actions']);
        $this->assertStringContainsString('js-outstanding-delete-form', $detail['actions']);
    }

    public function test_detail_action_is_empty_for_read_only_viewer(): void
    {
        Auth::setUser(new User(['name' => 'Sales Viewer']));
        $controller = new OutstandingMaterialController(
            new ContractAccessService(false),
            new OutstandingMaterialDocumentService(),
        );
        $material = new OutstandingMaterial([
            'supplier' => 'Supplier',
            'type' => 'TYPE',
            'number_invoice' => 'INV-1',
            'status' => OutstandingMaterial::STATUS_RECEIVED,
        ]);
        $material->id = 2;

        $method = new ReflectionMethod($controller, 'dataTableRow');
        $method->setAccessible(true);
        $detail = $method->invoke($controller, $material, 'detail');

        $this->assertStringNotContainsString('bi-eye', $detail['actions']);
        $this->assertStringNotContainsString('bi-pencil-square', $detail['actions']);
        $this->assertStringNotContainsString('js-outstanding-delete-form', $detail['actions']);
    }

    public function test_edit_form_returns_to_detail_without_locking_invoice_context(): void
    {
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $controller = new OutstandingMaterialController(
            new ContractAccessService(true),
            new OutstandingMaterialDocumentService(),
        );
        $material = new OutstandingMaterial([
            'supplier' => 'Supplier',
            'type' => 'TYPE',
            'number_invoice' => 'INV-1',
            'status' => OutstandingMaterial::STATUS_RECEIVED,
        ]);
        $material->id = 3;

        $view = $controller->edit($material);
        $data = $view->getData();

        $this->assertSame($material, $data['detailReturnAnchor']);
        $this->assertNull($data['invoiceContextAnchor']);
        $this->assertNull($data['invoiceContext']);
    }

    public function test_direct_create_form_keeps_index_as_back_fallback(): void
    {
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $controller = new OutstandingMaterialController(
            new ContractAccessService(true),
            new OutstandingMaterialDocumentService(),
        );

        $view = $controller->create(Request::create('/outstanding-materials/create', 'GET'));
        $data = $view->getData();

        $this->assertNull($data['detailReturnAnchor']);
        $this->assertNull($data['invoiceContextAnchor']);
    }

    public function test_invoice_document_renderers_use_download_icons_without_new_tabs(): void
    {
        Storage::fake('local');
        $packingPath = 'private/outstanding-materials/packing-list/packing-list.pdf';
        $mtcPath = 'private/outstanding-materials/mtc/mtc.pdf';
        Storage::disk('local')->put($packingPath, 'packing list');
        Storage::disk('local')->put($mtcPath, 'mtc');

        $controller = new OutstandingMaterialController(
            new DocumentDownloadAccessService(true),
            new OutstandingMaterialDocumentService(),
        );
        $material = new OutstandingMaterial([
            'packing_list_path' => $packingPath,
            'mtc_path' => $mtcPath,
        ]);
        $material->id = 4;

        $attachmentDisplay = new ReflectionMethod($controller, 'attachmentDisplay');
        $attachmentDisplay->setAccessible(true);
        $packingLink = $attachmentDisplay->invoke(
            $controller,
            $material,
            'packing_list_path',
            'packing-list',
            true,
        );

        $invoiceDocumentDisplay = new ReflectionMethod($controller, 'invoiceDocumentDisplay');
        $invoiceDocumentDisplay->setAccessible(true);
        $mtcLink = $invoiceDocumentDisplay->invoke($controller, $material, 'mtc', 1, true);

        foreach ([$packingLink, $mtcLink] as $link) {
            $this->assertStringContainsString('bi bi-download', $link);
            $this->assertStringContainsString(' download ', ' ' . $link . ' ');
            $this->assertStringNotContainsString('target="_blank"', $link);
            $this->assertStringNotContainsString('>View<', $link);
        }

        $this->assertStringContainsString('aria-label="Download Packing List"', $packingLink);
        $this->assertStringContainsString('aria-label="Download MTC"', $mtcLink);
    }

    public function test_invoice_document_endpoint_forces_attachment_downloads(): void
    {
        Storage::fake('local');
        $packingPath = 'private/outstanding-materials/packing-list/packing-list.pdf';
        $mtcPath = 'private/outstanding-materials/mtc/mtc.xlsx';
        Storage::disk('local')->put($packingPath, 'packing list');
        Storage::disk('local')->put($mtcPath, 'mtc');

        Auth::setUser(new User(['name' => 'Sales Viewer']));
        $controller = new OutstandingMaterialController(
            new DocumentDownloadAccessService(true),
            new OutstandingMaterialDocumentService(),
        );
        $material = new OutstandingMaterial([
            'packing_list_path' => $packingPath,
            'mtc_path' => $mtcPath,
        ]);
        $material->id = 5;

        foreach (['packing-list', 'mtc'] as $type) {
            $response = $controller->attachment($material, $type);
            $disposition = (string) $response->headers->get('Content-Disposition');

            $this->assertStringStartsWith('attachment;', $disposition);
            $this->assertStringContainsString('filename=', $disposition);
        }
    }

    public function test_invoice_action_exposes_permanent_delete_only_to_managers(): void
    {
        Auth::setUser(new User(['name' => 'ADMINISTRATOR']));
        $controller = new OutstandingMaterialController(
            new ContractAccessService(true),
            new OutstandingMaterialDocumentService(),
        );
        $material = new OutstandingMaterial([
            'supplier' => 'Supplier <A>',
            'number_invoice' => 'INV-DELETE',
            'status' => OutstandingMaterial::STATUS_RECEIVED,
        ]);
        $material->id = 6;

        $method = new ReflectionMethod($controller, 'invoiceActionCell');
        $method->setAccessible(true);
        $managerActions = $method->invoke($controller, $material, 'INV-DELETE', 3, 'Supplier <A>', true, false);
        $viewerActions = $method->invoke($controller, $material, 'INV-DELETE', 3, 'Supplier <A>', false, false);

        $this->assertStringContainsString('js-outstanding-invoice-delete-form', $managerActions);
        $this->assertStringContainsString('data-material-count="3"', $managerActions);
        $this->assertStringContainsString('/outstanding-materials/show-based-on-invoice/6', $managerActions);
        $this->assertStringContainsString('Supplier &lt;A&gt;', $managerActions);
        $this->assertStringNotContainsString('js-outstanding-invoice-delete-form', $viewerActions);
    }

    public function test_invoice_delete_requires_manage_access(): void
    {
        Auth::setUser(new User(['name' => 'Sales Viewer']));
        $controller = new OutstandingMaterialController(
            new ContractAccessService(false),
            new OutstandingMaterialDocumentService(),
        );
        $material = new OutstandingMaterial(['number_invoice' => 'INV-DELETE']);
        $material->id = 7;

        try {
            $controller->destroyInvoice($material);
            $this->fail('Invoice deletion must require manage access.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}

class ContractAccessService extends OutstandingMaterialAccessService
{
    public function __construct(private readonly bool $manager)
    {
    }

    public function canManage(?User $user): bool
    {
        return $this->manager;
    }

    public function isSales(?User $user): bool
    {
        return false;
    }
}

class DocumentDownloadAccessService extends ContractAccessService
{
    public function canDownloadInvoiceDocuments(?User $user): bool
    {
        return true;
    }
}
