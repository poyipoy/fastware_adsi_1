<?php

namespace App\Http\Controllers;

use App\Exports\OutstandingMaterialExport;
use App\Exports\OutstandingMaterialTemplateExport;
use App\Models\OutstandingMaterial;
use App\Models\OutstandingMaterialInvoice;
use App\Services\OutstandingMaterialAccessService;
use App\Services\OutstandingMaterialBatchService;
use App\Services\OutstandingMaterialDocumentService;
use App\Services\OutstandingMaterialIdentityService;
use App\Services\OutstandingMaterialImportPreviewService;
use App\Services\OutstandingMaterialInvoiceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class OutstandingMaterialController extends Controller
{
    public function __construct(
        ?OutstandingMaterialAccessService $access = null,
        ?OutstandingMaterialDocumentService $documents = null,
        ?OutstandingMaterialIdentityService $identity = null,
        ?OutstandingMaterialBatchService $batches = null,
        ?OutstandingMaterialImportPreviewService $importPreviews = null,
        ?OutstandingMaterialInvoiceService $invoices = null,
    ) {
        $this->access = $access ?? new OutstandingMaterialAccessService();
        $this->documents = $documents ?? new OutstandingMaterialDocumentService();
        $this->identity = $identity ?? new OutstandingMaterialIdentityService();
        $this->batches = $batches ?? new OutstandingMaterialBatchService($this->identity, $this->documents);
        $this->importPreviews = $importPreviews ?? new OutstandingMaterialImportPreviewService($this->identity, $this->documents);
        $this->invoices = $invoices ?? new OutstandingMaterialInvoiceService($this->identity);
    }

    private readonly OutstandingMaterialAccessService $access;

    private readonly OutstandingMaterialDocumentService $documents;

    private readonly OutstandingMaterialIdentityService $identity;

    private readonly OutstandingMaterialBatchService $batches;

    private readonly OutstandingMaterialImportPreviewService $importPreviews;

    private readonly OutstandingMaterialInvoiceService $invoices;

    public function index(): View
    {
        $this->authorizeView();

        return view('outstanding_materials.index', [
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'portOptions' => OutstandingMaterial::portOptions(),
            'filterOptions' => $this->filterOptions(),
            'canManageOutstandingMaterials' => $this->access->canManage(Auth::user()),
            'canExportOutstandingMaterials' => $this->access->canExport(Auth::user()),
            'summary' => $this->summaryStats(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizeView();

        $baseQuery = OutstandingMaterial::query()->unarchived();
        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        $this->applyFilters($filteredQuery, $request);

        $search = trim((string) ($request->input('search.value') ?: $request->input('q', '')));
        if ($search !== '') {
            $this->applySearch($filteredQuery, $search);
        }

        $recordsFiltered = (clone $filteredQuery)->count();

        $this->applyOrdering($filteredQuery, $request);

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        if ($length !== -1) {
            $filteredQuery->skip($start)->take(max($length, 1));
        } elseif ($start > 0) {
            $filteredQuery->skip($start);
        }

        $data = $filteredQuery
            ->get()
            ->map(fn (OutstandingMaterial $material): array => $this->dataTableRow($material, 'index'))
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Return the material rows for the invoice represented by the bound
     * anchor material.  The anchor, rather than a request parameter, owns the
     * mandatory scope so that a caller cannot switch invoices in the browser.
     */
    public function invoiceDetailData(
        Request $request,
        OutstandingMaterial $outstandingMaterial,
    ): JsonResponse {
        $this->authorizeView();

        $baseQuery = $this->invoiceScope($outstandingMaterial);
        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        $this->applyFilters($filteredQuery, $request, ['number_invoice']);

        $search = trim((string) ($request->input('search.value') ?: $request->input('q', '')));
        if ($search !== '') {
            $this->applySearch($filteredQuery, $search);
        }

        $recordsFiltered = (clone $filteredQuery)->count();
        $this->applyOrdering($filteredQuery, $request);

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        if ($length !== -1) {
            $filteredQuery->skip($start)->take(max($length, 1));
        } elseif ($start > 0) {
            $filteredQuery->skip($start);
        }

        $rowContext = $this->isAnchorArchived($outstandingMaterial) ? 'archived' : 'detail';

        $data = $filteredQuery
            ->get()
            ->map(fn (OutstandingMaterial $material): array => $this->dataTableRow($material, $rowContext))
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Export only the invoice represented by the bound anchor material.
     */
    public function invoiceDetailExport(
        Request $request,
        OutstandingMaterial $outstandingMaterial,
    ) {
        $this->authorizeExport();

        $query = $this->invoiceScope($outstandingMaterial);
        $this->applyFilters($query, $request, ['number_invoice']);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $this->applySearch($query, $search);
        }

        $materials = $query->orderByDesc('id')->get();
        $invoice = $this->displayInvoiceNumber($outstandingMaterial) ?: 'unassigned';

        return Excel::download(
            new OutstandingMaterialExport($materials),
            'outstanding_material_invoice_'.preg_replace('/[^A-Za-z0-9_-]+/', '_', $invoice).'_'.now()->format('Ymd_His').'.xlsx',
            ExcelFormat::XLSX,
        );
    }

    public function create(Request $request): View
    {
        $this->authorizeManage();

        $invoiceContextAnchor = null;
        $invoiceContextId = $request->query('invoice_context');
        if ($invoiceContextId !== null && $invoiceContextId !== '') {
            validator(
                ['invoice_context' => $invoiceContextId],
                ['invoice_context' => 'integer|exists:outstanding_materials,id'],
            )->validate();

            $invoiceContextAnchor = OutstandingMaterial::query()->findOrFail((int) $invoiceContextId);
        }

        return view('outstanding_materials.form-batch', [
            'material' => new OutstandingMaterial(),
            'isEdit' => false,
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'portOptions' => OutstandingMaterial::portOptions(),
            'invoiceContextAnchor' => $invoiceContextAnchor,
            'invoiceContext' => $invoiceContextAnchor?->number_invoice,
            'detailReturnAnchor' => $invoiceContextAnchor,
            'invoiceDefaults' => $invoiceContextAnchor ? [
                'status' => $invoiceContextAnchor->status,
                'estimasi_eta_port' => $invoiceContextAnchor->estimasi_eta_port,
                'estimasi_eta_warehouse' => $invoiceContextAnchor->estimasi_eta_warehouse,
                'estimasi_bulan_eta' => $invoiceContextAnchor->estimasi_bulan_eta,
                'keterangan' => $invoiceContextAnchor->keterangan,
                'estimasi_delay_eta_port' => $invoiceContextAnchor->estimasi_delay_eta_port,
                'estimasi_delay_eta_warehouse' => $invoiceContextAnchor->estimasi_delay_eta_warehouse,
                'port' => $invoiceContextAnchor->port,
                'number_po' => $invoiceContextAnchor->number_po,
                'month_order' => $invoiceContextAnchor->month_order,
                'remarks' => $invoiceContextAnchor->remarks,
            ] : [],
        ]);
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $data = $request->validate([
            'supplier' => 'required|string|max:255',
            'number_invoice' => 'required|string|max:255',
            'status' => ['required', 'string', Rule::in(OutstandingMaterial::statusOptions())],
            'estimasi_eta_port' => 'nullable|date',
            'estimasi_eta_warehouse' => 'nullable|date',
            'estimasi_bulan_eta' => 'nullable|string|max:255',
            'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
            'estimasi_delay_eta_port' => 'nullable|date',
            'estimasi_delay_eta_warehouse' => 'nullable|date',
            'port' => ['nullable', 'string', Rule::in(OutstandingMaterial::portOptions())],
            'number_po' => 'nullable|string|max:255',
            'month_order' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:2000',
            'invoice_context_id' => 'nullable|integer|exists:outstanding_materials,id',
            'materials' => 'required|array|min:1|max:'.OutstandingMaterialBatchService::MAX_ROWS,
            'materials.*.type' => 'required|string|max:255',
            'materials.*.thickness' => 'nullable|numeric',
            'materials.*.width' => 'nullable|numeric',
            'materials.*.diameter' => 'nullable|numeric',
            'materials.*.length' => 'nullable|string|max:255',
            'materials.*.qty_pcs' => 'nullable|numeric',
            'materials.*.est_qty_kg' => 'nullable|numeric',
            'materials.*.port' => ['nullable', 'string', Rule::in(OutstandingMaterial::portOptions())],
            'materials.*.number_po' => 'nullable|string|max:255',
            'materials.*.month_order' => 'nullable|string|max:255',
            'materials.*.remarks' => 'nullable|string|max:2000',
        ], [], $this->validationAttributes());

        $contextAnchor = null;
        if (! empty($data['invoice_context_id'])) {
            $contextAnchor = OutstandingMaterial::query()->findOrFail((int) $data['invoice_context_id']);
            if ($this->identity->invoiceKey($contextAnchor->supplier, $contextAnchor->number_invoice) === null) {
                throw ValidationException::withMessages([
                    'invoice_context_id' => 'Material anchor belum memiliki Supplier dan Number Invoice yang valid.',
                ]);
            }
        }

        $rows = $data['materials'];
        unset($data['materials'], $data['invoice_context_id']);

        $targetKey = $this->identity->invoiceKey(
            (string) ($contextAnchor?->supplier ?? $data['supplier'] ?? ''),
            (string) ($contextAnchor?->number_invoice ?? $data['number_invoice'] ?? ''),
        );
        $this->invoices->assertNotArchivedByIdentityKey($targetKey, 'penambahan material');

        $created = $this->batches->create(
            $data,
            $rows,
            $contextAnchor,
            Auth::id() ? (int) Auth::id() : null,
        );

        $anchor = $contextAnchor ?: $created->first();

        return redirect()
            ->route('outstanding-materials.show', $anchor)
            ->with('success', $created->count().' material berhasil ditambahkan ke invoice.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $data = $this->validatedPayload($request);
        $contextId = $data['invoice_context_id'] ?? null;
        unset($data['invoice_context_id']);

        $contextAnchor = $contextId
            ? OutstandingMaterial::query()->findOrFail((int) $contextId)
            : null;

        $targetKey = $this->identity->invoiceKey(
            (string) ($contextAnchor?->supplier ?? $data['supplier'] ?? ''),
            (string) ($contextAnchor?->number_invoice ?? $data['number_invoice'] ?? ''),
        );
        $this->invoices->assertNotArchivedByIdentityKey($targetKey, 'penambahan material');

        $header = [
            'supplier' => $data['supplier'],
            'number_invoice' => $data['number_invoice'],
            'status' => $data['status'],
            'estimasi_eta_port' => $data['estimasi_eta_port'] ?? null,
            'estimasi_eta_warehouse' => $data['estimasi_eta_warehouse'] ?? null,
            'estimasi_bulan_eta' => $data['estimasi_bulan_eta'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'estimasi_delay_eta_port' => $data['estimasi_delay_eta_port'] ?? null,
            'estimasi_delay_eta_warehouse' => $data['estimasi_delay_eta_warehouse'] ?? null,
        ];
        $row = [
            'type' => $data['type'],
            'thickness' => $data['thickness'] ?? null,
            'width' => $data['width'] ?? null,
            'diameter' => $data['diameter'] ?? null,
            'length' => $data['length'] ?? null,
            'qty_pcs' => $data['qty_pcs'] ?? null,
            'est_qty_kg' => $data['est_qty_kg'] ?? null,
            'port' => $data['port'] ?? null,
            'number_po' => $data['number_po'] ?? null,
            'month_order' => $data['month_order'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ];

        $created = $this->batches->create($header, [$row], $contextAnchor, Auth::id() ? (int) Auth::id() : null);
        $material = $created->first();

        return redirect()
            ->route('outstanding-materials.show', $material)
            ->with('success', 'Data Outstanding Material berhasil ditambahkan.');
    }

    public function show(OutstandingMaterial $outstandingMaterial): View
    {
        $this->authorizeView();

        $invoiceScope = $this->invoiceScope($outstandingMaterial);
        $isArchived = $this->isAnchorArchived($outstandingMaterial);

        return view('outstanding_materials.show', [
            'material' => $outstandingMaterial->load(['creator', 'updater']),
            'anchorMaterial' => $outstandingMaterial,
            'invoiceNumber' => $this->displayInvoiceNumber($outstandingMaterial),
            'summary' => $this->summaryStats($invoiceScope),
            'filterOptions' => $this->filterOptionsForQuery($invoiceScope),
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'portOptions' => OutstandingMaterial::portOptions(),
            'canManageOutstandingMaterials' => ! $isArchived && $this->access->canManage(Auth::user()),
            'canView' => true,
            'canExportInvoice' => $this->access->canExport(Auth::user()),
            'canDownloadDocuments' => $this->access->canDownloadInvoiceDocuments(Auth::user()),
        ]);
    }

    public function edit(OutstandingMaterial $outstandingMaterial): View
    {
        $this->authorizeManage();

        return view('outstanding_materials.form', [
            'material' => $outstandingMaterial,
            'isEdit' => true,
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'portOptions' => OutstandingMaterial::portOptions(),
            'invoiceContextAnchor' => null,
            'invoiceContext' => null,
            'detailReturnAnchor' => $outstandingMaterial,
        ]);
    }

    public function update(Request $request, OutstandingMaterial $outstandingMaterial): RedirectResponse
    {
        $this->authorizeManage();

        $oldPaths = [];
        $oldInvoiceHeaderPaths = [];

        $data = $this->validatedPayload($request, $outstandingMaterial);
        unset($data['invoice_context_id']);

        $canonical = $this->identity->canonicalizeInvoice(
            $data['supplier'],
            $data['number_invoice'],
        );
        $data = array_merge($data, $canonical);

        DB::transaction(function () use (&$outstandingMaterial, &$oldPaths, &$oldInvoiceHeaderPaths, $data): void {
            $lockedMaterial = OutstandingMaterial::query()
                ->whereKey($outstandingMaterial->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $currentHeader = $lockedMaterial->invoice_id
                ? OutstandingMaterialInvoice::query()->whereKey($lockedMaterial->invoice_id)->lockForUpdate()->first()
                : ($lockedMaterial->invoice_identity_key ? OutstandingMaterialInvoice::query()->where('invoice_identity_key', $lockedMaterial->invoice_identity_key)->lockForUpdate()->first() : null);
            $this->invoices->assertNotArchived($currentHeader, 'pengeditan material');
            $this->invoices->assertMaterialNotArchived($lockedMaterial, 'pengeditan material');

            $oldPaths = [
                $lockedMaterial->attachment_path,
                $lockedMaterial->packing_list_path,
                $lockedMaterial->mtc_path,
            ];
            $oldInvoiceId = $lockedMaterial->invoice_id;

            $invoiceChanged = (string) ($lockedMaterial->invoice_identity_key ?? '')
                !== (string) ($data['invoice_identity_key'] ?? '');
            if ($invoiceChanged) {
                $targetHeader = OutstandingMaterialInvoice::query()
                    ->where('invoice_identity_key', $data['invoice_identity_key'])
                    ->lockForUpdate()
                    ->first();
                $this->invoices->assertNotArchived($targetHeader, 'pemindahan material ke invoice');

                $inheritance = $this->documents->inheritanceForIdentityKey($data['invoice_identity_key'] ?? null, true);
                $this->logInheritanceWarnings($data['number_invoice'] ?? null, $inheritance['warnings']);
                $data['packing_list_path'] = $inheritance['packing_list_path'];
                $data['mtc_path'] = $inheritance['mtc_path'];
                $data['attachment_path'] = null;
            }

            if ($invoiceChanged || ! $lockedMaterial->invoice_id) {
                $invoiceHeader = $this->invoices->ensureForIdentityKey(
                    $data['invoice_identity_key'],
                    $data['supplier'],
                    $data['number_invoice'],
                    (int) Auth::id(),
                );
                $data['invoice_id'] = $invoiceHeader->getKey();
            }

            $data['updated_by'] = Auth::id();
            $lockedMaterial->update($data);
            $outstandingMaterial = $lockedMaterial->fresh();

            if ($invoiceChanged && $oldInvoiceId && $oldInvoiceId !== $data['invoice_id']) {
                $oldHeader = OutstandingMaterialInvoice::query()
                    ->whereKey($oldInvoiceId)
                    ->lockForUpdate()
                    ->first();
                if ($oldHeader && ! OutstandingMaterial::query()->where('invoice_id', $oldInvoiceId)->exists()) {
                    $oldInvoiceHeaderPaths = [$oldHeader->packing_list_path, $oldHeader->mtc_path];
                    $oldHeader->delete();
                }
            }
        });

        $this->documents->deleteIfUnreferenced(array_merge($oldPaths, $oldInvoiceHeaderPaths));

        return redirect()
            ->route('outstanding-materials.show', $outstandingMaterial)
            ->with('success', 'Data Outstanding Material berhasil diperbarui.');
    }

    public function destroy(OutstandingMaterial $outstandingMaterial): RedirectResponse
    {
        $this->authorizeManage();

        $oldPaths = [];
        $deletedInvoiceHeaderPaths = [];

        DB::transaction(function () use ($outstandingMaterial, &$oldPaths, &$deletedInvoiceHeaderPaths): void {
            $lockedMaterial = OutstandingMaterial::query()
                ->whereKey($outstandingMaterial->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $header = $lockedMaterial->invoice_id
                ? OutstandingMaterialInvoice::query()->whereKey($lockedMaterial->invoice_id)->lockForUpdate()->first()
                : ($lockedMaterial->invoice_identity_key ? OutstandingMaterialInvoice::query()->where('invoice_identity_key', $lockedMaterial->invoice_identity_key)->lockForUpdate()->first() : null);
            $this->invoices->assertNotArchived($header, 'penghapusan material');
            $this->invoices->assertMaterialNotArchived($lockedMaterial, 'penghapusan material');

            $oldPaths = [
                $lockedMaterial->attachment_path,
                $lockedMaterial->packing_list_path,
                $lockedMaterial->mtc_path,
            ];
            $invoiceId = $lockedMaterial->invoice_id;

            $lockedMaterial->forceFill([
                'attachment_path' => null,
                'packing_list_path' => null,
                'mtc_path' => null,
                'updated_by' => Auth::id(),
            ])->save();
            $lockedMaterial->delete();

            if ($invoiceId) {
                $header = OutstandingMaterialInvoice::query()
                    ->whereKey($invoiceId)
                    ->lockForUpdate()
                    ->first();
                if ($header && ! OutstandingMaterial::query()->where('invoice_id', $invoiceId)->exists()) {
                    $deletedInvoiceHeaderPaths = [$header->packing_list_path, $header->mtc_path];
                    $header->delete();
                }
            }
        });

        $this->documents->deleteIfUnreferenced(array_merge($oldPaths, $deletedInvoiceHeaderPaths));

        return redirect()
            ->route('outstanding-materials.index')
            ->with('success', 'Data Outstanding Material berhasil dihapus.');
    }

    /**
     * Permanently remove the invoice represented by the bound anchor and every
     * material assigned to that exact invoice scope.  The anchor is never used
     * as a request-controlled invoice identifier, so equal invoice numbers from
     * different suppliers cannot be removed together.
     */
    public function destroyInvoice(OutstandingMaterial $outstandingMaterial): RedirectResponse
    {
        $this->authorizeManage();

        $invoice = $this->displayInvoiceNumber($outstandingMaterial) ?: 'Unassigned';
        $deletedMaterialCount = 0;
        $documentPaths = [];

        DB::transaction(function () use (
            $outstandingMaterial,
            &$invoice,
            &$deletedMaterialCount,
            &$documentPaths,
        ): void {
            $lockedAnchor = OutstandingMaterial::query()
                ->whereKey($outstandingMaterial->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $invoice = $this->displayInvoiceNumber($lockedAnchor) ?: 'Unassigned';
            $materials = $this->invoiceDeletionScope($lockedAnchor)
                ->lockForUpdate()
                ->get([
                    'id',
                    'attachment_path',
                    'packing_list_path',
                    'mtc_path',
                ]);
            $invoiceHeader = $this->invoiceHeaderDeletionScope($lockedAnchor)
                ->lockForUpdate()
                ->first();
            $this->invoices->assertNotArchived($invoiceHeader, 'penghapusan invoice');

            $documentPaths = $materials
                ->flatMap(fn (OutstandingMaterial $material): array => [
                    $material->attachment_path,
                    $material->packing_list_path,
                    $material->mtc_path,
                ])
                ->filter()
                ->map(fn (mixed $path): string => (string) $path)
                ->values()
                ->all();

            if ($invoiceHeader) {
                $documentPaths[] = $invoiceHeader->packing_list_path;
                $documentPaths[] = $invoiceHeader->mtc_path;
            }

            $deletedMaterialCount = $materials->count();
            $materials->each(static function (OutstandingMaterial $material): void {
                $material->forceDelete();
            });
            $invoiceHeader?->delete();
        });

        // The database transaction has committed before any physical file is
        // considered for cleanup.  Shared paths remain protected by the
        // service's withTrashed reference check.
        $this->documents->deleteIfUnreferenced($documentPaths);

        return redirect()
            ->route('outstanding-materials.invoice.index')
            ->with(
                'success',
                'Invoice '.$invoice.' dan '.$deletedMaterialCount.' material terkait berhasil dihapus permanen.',
            );
    }

    public function archiveInvoice(OutstandingMaterial $outstandingMaterial): RedirectResponse|JsonResponse
    {
        $this->authorizeManage();

        try {
            $header = $this->invoices->archiveInvoiceGroup($outstandingMaterial, Auth::user());
            $message = sprintf('Invoice "%s" berhasil diarsipkan.', $header->number_invoice ?: $outstandingMaterial->number_invoice);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()
                ->route('outstanding-materials.invoice.index')
                ->with('success', $message);
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors()->all();
            $message = implode(' ', $errors) ?: $exception->getMessage();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('outstanding-materials.invoice.index')
                ->withErrors(['invoice' => $message]);
        } catch (\Throwable $exception) {
            report($exception);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengarsipkan invoice: '.$exception->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('outstanding-materials.invoice.index')
                ->withErrors(['invoice' => 'Gagal mengarsipkan invoice: '.$exception->getMessage()]);
        }
    }

    public function restoreInvoice(OutstandingMaterial $outstandingMaterial): RedirectResponse|JsonResponse
    {
        $this->authorizeManage();

        try {
            $header = $this->invoices->restoreInvoiceGroup($outstandingMaterial, Auth::user());
            $message = sprintf('Invoice "%s" berhasil dikembalikan ke daftar aktif.', $header->number_invoice ?: $outstandingMaterial->number_invoice);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()
                ->route('outstanding-materials.invoice.archived.index')
                ->with('success', $message);
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors()->all();
            $message = implode(' ', $errors) ?: $exception->getMessage();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('outstanding-materials.invoice.archived.index')
                ->withErrors(['invoice' => $message]);
        } catch (\Throwable $exception) {
            report($exception);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengembalikan invoice: '.$exception->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('outstanding-materials.invoice.archived.index')
                ->withErrors(['invoice' => 'Gagal mengembalikan invoice: '.$exception->getMessage()]);
        }
    }

    public function export(Request $request)
    {
        $this->authorizeExport();

        $query = OutstandingMaterial::query()->unarchived();
        $this->applyFilters($query, $request);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $this->applySearch($query, $search);
        }

        $materials = $query
            ->orderByDesc('id')
            ->get();

        return Excel::download(
            new OutstandingMaterialExport($materials),
            'outstanding_materials_'.now()->format('Ymd_His').'.xlsx',
            ExcelFormat::XLSX
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $mode = $request->input('mode') ?: $request->input('import_mode', 'add');
        if (! in_array($mode, ['add', 'replace'], true)) {
            $mode = 'add';
        }
        $request->merge(['mode' => $mode, 'import_mode' => $mode]);

        $request->validate([
            'import_file' => [
                'required',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof \Illuminate\Http\UploadedFile) {
                        $fail('File import tidak valid.');

                        return;
                    }

                    $allowedExtensions = ['xlsx', 'xls', 'csv'];
                    $extension = strtolower((string) $value->getClientOriginalExtension());

                    if (! in_array($extension, $allowedExtensions, true)) {
                        $fail('File import harus berekstensi xlsx, xls, atau csv.');
                    }
                },
            ],
            'mode' => ['nullable', 'string', Rule::in(['add', 'replace'])],
            'import_mode' => ['nullable', 'string', Rule::in(['add', 'replace'])],
        ]);

        try {
            $preview = $this->importPreviews->preview(
                $request->file('import_file'),
                (int) Auth::id(),
                $mode,
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withErrors([
                    'import_file' => 'File tidak dapat diproses: '.$exception->getMessage(),
                ]);
        }

        return redirect()->route('outstanding-materials.import.preview', $preview['token']);
    }

    public function importPreview(string $token): View
    {
        $this->authorizeManage();
        $preview = $this->importPreviews->get($token, (int) Auth::id());
        abort_unless($preview !== null, 404, 'Import preview tidak ditemukan atau sudah kedaluwarsa.');

        return view('outstanding_materials.import-preview', [
            'preview' => $preview,
        ]);
    }

    public function importExecute(Request $request, string $token): RedirectResponse
    {
        $this->authorizeManage();

        try {
            $counts = $this->importPreviews->execute($token, (int) Auth::id(), (int) Auth::id());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('outstanding-materials.index')
                ->withErrors(['import_file' => 'Import tidak dijalankan: '.$exception->getMessage()]);
        }

        $mode = $counts['mode'] ?? 'add';
        $message = $mode === 'replace'
            ? sprintf('Import selesai: %d material berhasil diperbarui.', $counts['replaced'] ?? 0)
            : sprintf('Import selesai: %d material berhasil ditambahkan.', $counts['inserted']);

        return redirect()
            ->route('outstanding-materials.index')
            ->with('success', $message);
    }

    public function template()
    {
        $this->authorizeManage();

        return Excel::download(
            new OutstandingMaterialTemplateExport(),
            'template_import_outstanding_material.xlsx',
            ExcelFormat::XLSX
        );
    }

    public function invoiceIndex(): View
    {
        $this->authorizeView();

        return view('outstanding_materials.invoice', [
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'canManageOutstandingMaterials' => $this->access->canManage(Auth::user()),
            'canUploadInvoiceDocuments' => $this->access->canUploadInvoiceDocuments(Auth::user()),
            'canDownloadDocuments' => $this->access->canDownloadInvoiceDocuments(Auth::user()),
        ]);
    }

    public function invoiceData(Request $request): JsonResponse
    {
        $this->authorizeView();

        $baseQuery = $this->invoiceBaseQuery();
        $recordsTotal = $this->countGroupedQuery($baseQuery);

        $filteredQuery = clone $baseQuery;
        $search = trim((string) ($request->input('search.value') ?: $request->input('q', '')));

        if ($search !== '') {
            $like = '%'.$search.'%';
            $filteredQuery->havingRaw(
                <<<'SQL'
                    (
                        MAX(CASE WHEN outstanding_materials.number_invoice LIKE ?
                            OR outstanding_materials.supplier LIKE ?
                            OR outstanding_materials.status LIKE ?
                            OR outstanding_materials.keterangan LIKE ?
                        THEN 1 ELSE 0 END) = 1
                        OR MAX(outstanding_materials.estimasi_eta_warehouse) LIKE ?
                    )
                SQL,
                [$like, $like, $like, $like, $like],
            );
        }

        $recordsFiltered = $this->countGroupedQuery($filteredQuery);
        $this->applyInvoiceOrdering($filteredQuery, $request);

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        if ($length !== -1) {
            $filteredQuery->skip($start)->take(max($length, 1));
        } elseif ($start > 0) {
            $filteredQuery->skip($start);
        }

        $data = $filteredQuery
            ->get()
            ->map(fn (object $invoice): array => $this->invoiceDataTableRow(
                $invoice,
                $this->access->canManage(Auth::user()),
                $this->access->canUploadInvoiceDocuments(Auth::user()),
                $this->access->canDownloadInvoiceDocuments(Auth::user()),
            ))
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function archivedInvoiceIndex(): View
    {
        $this->authorizeView();

        return view('outstanding_materials.archived_invoices', [
            'canManageOutstandingMaterials' => $this->access->canManage(Auth::user()),
            'canDownloadDocuments' => $this->access->canDownloadInvoiceDocuments(Auth::user()),
        ]);
    }

    public function archivedInvoiceData(Request $request): JsonResponse
    {
        $this->authorizeView();

        $baseQuery = $this->archivedInvoiceBaseQuery();
        $recordsTotal = $this->countGroupedQuery($baseQuery);

        $filteredQuery = clone $baseQuery;
        $search = trim((string) ($request->input('search.value') ?: $request->input('q', '')));

        if ($search !== '') {
            $like = '%'.$search.'%';
            $filteredQuery->havingRaw(
                <<<'SQL'
                    (
                        MAX(CASE WHEN outstanding_materials.number_invoice LIKE ?
                            OR outstanding_materials.supplier LIKE ?
                            OR outstanding_materials.status LIKE ?
                            OR outstanding_materials.keterangan LIKE ?
                        THEN 1 ELSE 0 END) = 1
                        OR MAX(outstanding_materials.estimasi_eta_warehouse) LIKE ?
                    )
                SQL,
                [$like, $like, $like, $like, $like],
            );
        }

        $recordsFiltered = $this->countGroupedQuery($filteredQuery);
        $this->applyArchivedInvoiceOrdering($filteredQuery, $request);

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        if ($length !== -1) {
            $filteredQuery->skip($start)->take(max($length, 1));
        } elseif ($start > 0) {
            $filteredQuery->skip($start);
        }

        $data = $filteredQuery
            ->get()
            ->map(fn (object $invoice): array => $this->archivedInvoiceDataTableRow(
                $invoice,
                $this->access->canManage(Auth::user()),
                $this->access->canDownloadInvoiceDocuments(Auth::user()),
            ))
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function archivedMaterialIndex(): View
    {
        $this->authorizeView();

        $archivedScope = $this->archivedMaterialsBaseQuery();

        return view('outstanding_materials.archived_materials', [
            'filterOptions' => $this->filterOptionsForQuery($archivedScope),
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'portOptions' => OutstandingMaterial::portOptions(),
            'canExportOutstandingMaterials' => $this->access->canExport(Auth::user()),
            'canDownloadDocuments' => $this->access->canDownloadInvoiceDocuments(Auth::user()),
            'summary' => $this->summaryStats($archivedScope),
        ]);
    }

    public function archivedMaterialData(Request $request): JsonResponse
    {
        $this->authorizeView();

        $baseQuery = $this->archivedMaterialsBaseQuery();
        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        $this->applyFilters($filteredQuery, $request);

        $search = trim((string) ($request->input('search.value') ?: $request->input('q', '')));
        if ($search !== '') {
            $this->applySearch($filteredQuery, $search);
        }

        $recordsFiltered = (clone $filteredQuery)->count();
        $this->applyOrdering($filteredQuery, $request);

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        if ($length !== -1) {
            $filteredQuery->skip($start)->take(max($length, 1));
        } elseif ($start > 0) {
            $filteredQuery->skip($start);
        }

        $data = $filteredQuery
            ->get()
            ->map(fn (OutstandingMaterial $material): array => $this->dataTableRow($material, 'archived'))
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function invoiceMaterials(Request $request): JsonResponse
    {
        $this->authorizeManage();
        $data = $request->validate(['anchor_id' => 'required|integer|exists:outstanding_materials,id']);

        return $this->invoiceMaterialsForAnchor(
            OutstandingMaterial::query()->findOrFail((int) $data['anchor_id']),
        );
    }

    public function invoiceMaterialsForAnchor(OutstandingMaterial $outstandingMaterial): JsonResponse
    {
        $this->authorizeManage();

        return response()->json(
            $this->invoiceScope($outstandingMaterial)
                ->unarchived()
                ->select([
                    'id', 'supplier', 'type', 'thickness', 'width', 'diameter', 'length',
                    'qty_pcs', 'est_qty_kg', 'status', 'keterangan',
                    'estimasi_eta_port', 'estimasi_eta_warehouse', 'estimasi_bulan_eta',
                    'estimasi_delay_eta_port', 'estimasi_delay_eta_warehouse',
                ])
                ->orderBy('id')
                ->get(),
        );
    }

    public function archivedInvoiceMaterialsForAnchor(OutstandingMaterial $outstandingMaterial): JsonResponse
    {
        $this->authorizeManage();

        return response()->json(
            $this->invoiceScope($outstandingMaterial)
                ->archived()
                ->select('id', 'supplier', 'type', 'thickness', 'width', 'diameter', 'length', 'qty_pcs', 'est_qty_kg', 'status', 'keterangan', 'archived_at')
                ->orderBy('id')
                ->get(),
        );
    }

    public function archiveMaterials(Request $request): JsonResponse
    {
        $this->authorizeManage();

        $data = $request->validate([
            'material_ids' => 'required|array|min:1',
            'material_ids.*' => 'integer|exists:outstanding_materials,id',
        ]);

        $result = $this->invoices->archiveMaterials($data['material_ids'], Auth::id());

        return response()->json([
            'success' => true,
            'message' => $result['archived_count'].' material berhasil diarsipkan.',
            'archived_ids' => $result['archived_ids'],
        ]);
    }

    public function restoreMaterials(Request $request): JsonResponse
    {
        $this->authorizeManage();

        $data = $request->validate([
            'material_ids' => 'required|array|min:1',
            'material_ids.*' => 'integer|exists:outstanding_materials,id',
        ]);

        $result = $this->invoices->restoreMaterials($data['material_ids'], Auth::id());

        return response()->json([
            'success' => true,
            'message' => $result['restored_count'].' material berhasil dipulihkan.',
            'restored_ids' => $result['restored_ids'],
        ]);
    }

    public function updateInvoiceFields(Request $request): JsonResponse
    {
        $this->authorizeManage();

        $data = $request->validate([
            'anchor_id' => 'required|integer|exists:outstanding_materials,id',
            'material_ids' => 'required|array|min:1',
            'material_ids.*' => 'integer|exists:outstanding_materials,id',
            'status' => ['nullable', 'string', Rule::in(OutstandingMaterial::statusOptions())],
            'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
            'estimasi_eta_port' => ['nullable', 'date_format:Y-m-d'],
            'estimasi_eta_warehouse' => ['nullable', 'date_format:Y-m-d'],
            'estimasi_bulan_eta' => ['nullable', 'string', 'max:255'],
            'estimasi_delay_eta_port' => ['nullable', 'date_format:Y-m-d'],
            'estimasi_delay_eta_warehouse' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $anchor = OutstandingMaterial::query()->findOrFail((int) $data['anchor_id']);
        $request->merge([
            'material_ids' => $data['material_ids'],
            'status' => $data['status'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'estimasi_eta_port' => $data['estimasi_eta_port'] ?? null,
            'estimasi_eta_warehouse' => $data['estimasi_eta_warehouse'] ?? null,
            'estimasi_bulan_eta' => $data['estimasi_bulan_eta'] ?? null,
            'estimasi_delay_eta_port' => $data['estimasi_delay_eta_port'] ?? null,
            'estimasi_delay_eta_warehouse' => $data['estimasi_delay_eta_warehouse'] ?? null,
        ]);

        return $this->updateInvoiceFieldsForAnchor($request, $anchor);
    }

    public function updateInvoiceFieldsForAnchor(
        Request $request,
        OutstandingMaterial $outstandingMaterial,
    ): JsonResponse {
        $this->authorizeManage();

        $data = $request->validate([
            'material_ids' => 'required|array|min:1',
            'material_ids.*' => 'integer|exists:outstanding_materials,id',
            'status' => ['nullable', 'string', Rule::in(OutstandingMaterial::statusOptions())],
            'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
            'estimasi_eta_port' => ['nullable', 'date_format:Y-m-d'],
            'estimasi_eta_warehouse' => ['nullable', 'date_format:Y-m-d'],
            'estimasi_bulan_eta' => ['nullable', 'string', 'max:255'],
            'estimasi_delay_eta_port' => ['nullable', 'date_format:Y-m-d'],
            'estimasi_delay_eta_warehouse' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $materialIds = array_values(array_unique(array_map('intval', $data['material_ids'])));
        $updates = ['updated_by' => Auth::id()];

        foreach ([
            'status', 'keterangan',
            'estimasi_eta_port', 'estimasi_eta_warehouse', 'estimasi_bulan_eta',
            'estimasi_delay_eta_port', 'estimasi_delay_eta_warehouse',
        ] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && trim((string) $data[$field]) !== '') {
                $updates[$field] = $data[$field];
            }
        }

        if (count($updates) === 1) {
            throw ValidationException::withMessages([
                'update_fields' => 'Pilih atau isi setidaknya satu kolom untuk diperbarui.',
            ]);
        }

        DB::transaction(function () use ($outstandingMaterial, $materialIds, $updates): void {
            $lockedAnchor = OutstandingMaterial::query()
                ->whereKey($outstandingMaterial->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $invoiceHeader = $this->invoiceHeaderDeletionScope($lockedAnchor)
                ->lockForUpdate()
                ->first();
            $this->invoices->assertNotArchived($invoiceHeader, 'pembaruan material invoice');

            $scope = $this->invoiceScope($lockedAnchor);
            $ownedIds = (clone $scope)
                ->whereIn('id', $materialIds)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            sort($ownedIds);
            $expectedIds = $materialIds;
            sort($expectedIds);
            if ($ownedIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'material_ids' => 'Every selected material must belong to the submitted invoice.',
                ]);
            }

            (clone $scope)
                ->whereIn('id', $materialIds)
                ->update($updates);
        });

        return response()->json([
            'success' => true,
            'message' => count($materialIds).' material(s) updated successfully.',
        ]);
    }

    public function uploadInvoiceDocuments(Request $request): JsonResponse
    {
        $this->authorizeDocumentUpload();

        $data = $request->validate([
            'anchor_id' => 'required|integer|exists:outstanding_materials,id',
            'packing_list' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png|max:10240',
            'mtc' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        return $this->uploadInvoiceDocumentsForAnchor(
            $request,
            OutstandingMaterial::query()->findOrFail((int) $data['anchor_id']),
        );
    }

    public function uploadInvoiceDocumentsForAnchor(
        Request $request,
        OutstandingMaterial $outstandingMaterial,
    ): JsonResponse {
        $this->authorizeDocumentUpload();

        $request->validate([
            'packing_list' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png|max:10240',
            'mtc' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if (! $request->hasFile('packing_list') && ! $request->hasFile('mtc')) {
            throw ValidationException::withMessages([
                'packing_list' => 'Upload at least one document.',
            ]);
        }

        $identityKey = $this->identity->invoiceKeyForMaterial($outstandingMaterial);
        if ($identityKey === null) {
            throw ValidationException::withMessages([
                'anchor_id' => 'Material anchor belum memiliki invoice yang valid.',
            ]);
        }

        $this->invoices->assertNotArchivedByIdentityKey($identityKey, 'upload dokumen');

        $result = $this->documents->uploadForIdentityKey(
            $identityKey,
            $request->file('packing_list'),
            $request->file('mtc'),
            (int) Auth::id(),
        );

        return response()->json([
            'success' => true,
            'message' => $result['updated'].' material(s) synchronized successfully.',
        ]);
    }

    public function attachment(OutstandingMaterial $outstandingMaterial, ?string $type = null)
    {
        $type = $type ?: 'attachment';
        if (in_array($type, ['packing-list', 'mtc'], true)) {
            $this->authorizeDocumentDownload();
        } else {
            $this->authorizeView();
        }

        $path = match ($type) {
            'packing-list' => $outstandingMaterial->packing_list_path ?: $outstandingMaterial->attachment_path,
            'mtc' => $outstandingMaterial->mtc_path,
            // Generic legacy attachments retain the old read rule but never
            // fall back to invoice documents, which are Sales-only.
            default => $outstandingMaterial->attachment_path,
        };
        $resolved = $this->documents->resolvePath($path);

        if (! $resolved || ! $this->documents->isStored($path)) {
            abort(404, 'File attachment tidak ditemukan.');
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $fileName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($path)) ?: 'document';
        $disk = Storage::disk($resolved['disk']);
        $fullPath = $disk->path($resolved['path']);
        $mimeType = $disk->mimeType($resolved['path']) ?: 'application/octet-stream';

        // Invoice documents must always be downloaded.  In particular, PDF
        // files should not be opened by the browser's inline viewer.
        if (in_array($type, ['packing-list', 'mtc'], true)) {
            return response()->download($fullPath, $fileName, [
                'Content-Type' => $mimeType,
            ]);
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], true)) {
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ]);
        }

        return response()->download($fullPath, $fileName, [
            'Content-Type' => $mimeType,
        ]);
    }

    private function validatedPayload(Request $request, ?OutstandingMaterial $material = null): array
    {
        $data = $request->validate([
            'supplier' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'thickness' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'diameter' => 'nullable|numeric',
            'length' => 'nullable|string|max:255',
            'qty_pcs' => 'nullable|numeric',
            'est_qty_kg' => 'nullable|numeric',
            'number_invoice' => 'required|string|max:255',
            'status' => ['required', 'string', Rule::in(OutstandingMaterial::statusOptions())],
            'estimasi_eta_port' => 'nullable|date',
            'estimasi_eta_warehouse' => 'nullable|date',
            'estimasi_bulan_eta' => 'nullable|string|max:255',
            'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
            'estimasi_delay_eta_port' => 'nullable|date',
            'estimasi_delay_eta_warehouse' => 'nullable|date',
            'port' => ['nullable', 'string', Rule::in(OutstandingMaterial::portOptions())],
            'number_po' => 'nullable|string|max:255',
            'month_order' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:2000',
            'invoice_context_id' => 'nullable|integer|exists:outstanding_materials,id',
        ], [], $this->validationAttributes());

        $data['attachment_path'] = $material?->attachment_path;
        $data['packing_list_path'] = $material?->packing_list_path;
        $data['mtc_path'] = $material?->mtc_path;

        return $data;
    }

    /** @param list<string> $excludedFields */
    private function applyFilters(Builder $query, Request $request, array $excludedFields = []): void
    {
        foreach (['supplier', 'type', 'number_invoice', 'status', 'keterangan', 'estimasi_bulan_eta', 'port', 'month_order'] as $field) {
            if (! in_array($field, $excludedFields, true) && $request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        foreach (['thickness', 'width', 'diameter', 'qty_pcs', 'est_qty_kg'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%'.$request->input($field).'%');
            }
        }

        if ($request->filled('material_length')) {
            $query->where('length', 'like', '%'.$request->input('material_length').'%');
        }

        foreach (['number_po', 'remarks'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%'.$request->input($field).'%');
            }
        }

        $this->applyDateRangeFilter($query, 'estimasi_eta_port', $request->input('eta_port_from'), $request->input('eta_port_to'));
        $this->applyDateRangeFilter($query, 'estimasi_eta_warehouse', $request->input('eta_warehouse_from'), $request->input('eta_warehouse_to'));
        $this->applyDateRangeFilter($query, 'estimasi_delay_eta_port', $request->input('delay_eta_port_from'), $request->input('delay_eta_port_to'));
        $this->applyDateRangeFilter($query, 'estimasi_delay_eta_warehouse', $request->input('delay_eta_warehouse_from'), $request->input('delay_eta_warehouse_to'));
    }

    private function applyDateRangeFilter(Builder $query, string $field, mixed $from, mixed $to): void
    {
        $fromDate = $this->filterDate($from);
        $toDate = $this->filterDate($to);

        if ($fromDate) {
            $query->whereDate($field, '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate($field, '<=', $toDate);
        }
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $builder) use ($search): void {
            $builder->where('supplier', 'like', '%'.$search.'%')
                ->orWhere('type', 'like', '%'.$search.'%')
                ->orWhere('number_invoice', 'like', '%'.$search.'%')
                ->orWhere('status', 'like', '%'.$search.'%')
                ->orWhere('estimasi_eta_warehouse', 'like', '%'.$search.'%')
                ->orWhere('estimasi_bulan_eta', 'like', '%'.$search.'%')
                ->orWhere('keterangan', 'like', '%'.$search.'%')
                ->orWhere('port', 'like', '%'.$search.'%')
                ->orWhere('number_po', 'like', '%'.$search.'%')
                ->orWhere('month_order', 'like', '%'.$search.'%')
                ->orWhere('remarks', 'like', '%'.$search.'%')
                ->orWhere('attachment_path', 'like', '%'.$search.'%')
                ->orWhere('packing_list_path', 'like', '%'.$search.'%')
                ->orWhere('mtc_path', 'like', '%'.$search.'%');
        });
    }

    private function sqlValidDate(string $column): string
    {
        return "(CASE WHEN {$column} REGEXP '^[12][0-9]{3}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$' AND CAST(SUBSTRING({$column}, 9, 2) AS UNSIGNED) <= DAY(LAST_DAY(CONCAT(SUBSTRING({$column}, 1, 7), '-01'))) AND STR_TO_DATE({$column}, '%Y-%m-%d') IS NOT NULL THEN STR_TO_DATE({$column}, '%Y-%m-%d') ELSE NULL END)";
    }

    private function applyOrdering(Builder $query, Request $request): void
    {
        // Column indices match the dashboard column order:
        // 0=NO, 1=Supplier, 2=TYPE, 3=Thickness, 4=Width, 5=Diameter,
        // 6=Length, 7=QTY PCS, 8=Est QTY KG, 9=Number Invoice, 10=Status,
        // 11=ETA Port, 12=ETA Warehouse, 13=Estimasi Bulan ETA, 14=Keterangan,
        // 15=Delay ETA Port, 16=Delay ETA Warehouse, 17=Port, 18=Nomor PO,
        // 19=Month Order, 20=Remarks, 21=Packing List, 22=MTC
        $columnOrderMap = [
            1 => 'supplier',
            2 => 'type',
            3 => 'thickness',
            4 => 'width',
            5 => 'diameter',
            6 => 'length',
            7 => 'qty_pcs',
            8 => 'est_qty_kg',
            9 => 'number_invoice',
            10 => 'status',
            11 => 'estimasi_eta_port',
            12 => 'estimasi_eta_warehouse',
            13 => 'estimasi_bulan_eta',
            14 => 'keterangan',
            15 => 'estimasi_delay_eta_port',
            16 => 'estimasi_delay_eta_warehouse',
            17 => 'port',
            18 => 'number_po',
            19 => 'month_order',
            20 => 'remarks',
            21 => 'packing_list_path',
            22 => 'mtc_path',
        ];

        $orders = (array) $request->input('order', []);
        $appliedOrder = false;

        foreach ($orders as $order) {
            $columnIndex = isset($order['column']) ? (int) $order['column'] : null;
            if ($columnIndex === null || ! array_key_exists($columnIndex, $columnOrderMap)) {
                continue;
            }

            $columnName = $columnOrderMap[$columnIndex];
            $direction = strtolower((string) ($order['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

            if ($columnName === 'estimasi_eta_warehouse' && $direction === 'asc') {
                $query->orderByRaw("CASE WHEN estimasi_eta_warehouse IS NULL OR TRIM(estimasi_eta_warehouse) = '' THEN 1 ELSE 0 END ASC")
                    ->orderBy('estimasi_eta_warehouse', 'asc');
            } else {
                $query->orderBy($columnName, $direction);
            }
            $appliedOrder = true;
        }

        if (! $appliedOrder) {
            $vEta = $this->sqlValidDate('estimasi_eta_warehouse');
            $vDelay = $this->sqlValidDate('estimasi_delay_eta_warehouse');
            $today = now('Asia/Jakarta')->toDateString();

            $query->orderByRaw("
                /* 1. Primary Priority Tier (1 to 5) */
                CASE
                    WHEN status = 'Received' THEN 5
                    WHEN status != 'Received' AND {$vEta} < ? AND ({$vDelay} IS NULL OR {$vDelay} < ?) THEN 1
                    WHEN status != 'Received' AND {$vEta} < ? AND {$vDelay} >= ? THEN 2
                    WHEN status != 'Received' AND ({$vEta} >= ? OR ({$vEta} IS NULL AND {$vDelay} >= ?)) THEN 3
                    ELSE 4
                END ASC,

                /* 2. Chronological sub-sort for Tier 1 (Oldest overdue first) */
                CASE 
                    WHEN status != 'Received' AND {$vEta} < ? AND ({$vDelay} IS NULL OR {$vDelay} < ?)
                    THEN COALESCE({$vDelay}, {$vEta})
                    ELSE NULL
                END ASC,

                /* 3. Chronological sub-sort for Tier 2 (Nearest future delay arrival) */
                CASE 
                    WHEN status != 'Received' AND {$vEta} < ? AND {$vDelay} >= ?
                    THEN {$vDelay}
                    ELSE NULL
                END ASC,

                /* 4. Chronological sub-sort for Tier 3 (Nearest future arrival) */
                CASE 
                    WHEN status != 'Received' AND ({$vEta} >= ? OR ({$vEta} IS NULL AND {$vDelay} >= ?))
                    THEN COALESCE({$vEta}, {$vDelay})
                    ELSE NULL
                END ASC,

                /* 5. Chronological sub-sort for Tier 5 (Most recently received first) */
                CASE 
                    WHEN status = 'Received'
                    THEN COALESCE({$vDelay}, {$vEta})
                    ELSE NULL
                END DESC,

                /* 6. Deterministic Tie-Breakers */
                number_invoice ASC,
                supplier ASC,
                id ASC
            ", [
                $today, $today, // Tier 1 check (placeholders 1 & 2)
                $today, $today, // Tier 2 check (placeholders 3 & 4)
                $today, $today, // Tier 3 check (placeholders 5 & 6)
                $today, $today, // Tier 1 sub-sort (placeholders 7 & 8)
                $today, $today, // Tier 2 sub-sort (placeholders 9 & 10)
                $today, $today, // Tier 3 sub-sort (placeholders 11 & 12)
            ]);
        }
    }

    private function dataTableRow(OutstandingMaterial $material, string $context = 'index'): array
    {
        $allowManage = $context === 'detail' && $this->access->canManage(Auth::user());
        $allowDownload = in_array($context, ['detail', 'archived'], true)
            && $this->access->canDownloadInvoiceDocuments(Auth::user());

        $remarksRaw = $material->remarks ?: '';
        $remarksTruncated = mb_strlen($remarksRaw) > 80
            ? mb_substr($remarksRaw, 0, 80).'…'
            : $remarksRaw;

        return [
            'id' => $material->id,
            'supplier' => e($material->supplier),
            'type' => e($material->type),
            'thickness' => $this->formatNumber($material->thickness),
            'width' => $this->formatNumber($material->width),
            'diameter' => $this->formatNumber($material->diameter),
            'length' => e($material->length ?: '-'),
            'qty_pcs' => $this->formatNumber($material->qty_pcs),
            'est_qty_kg' => $this->formatNumber($material->est_qty_kg),
            'number_invoice' => e($material->number_invoice ?: '-'),
            'status' => $this->statusBadge($material->status),
            'estimasi_eta_port' => $this->formatDate($material->estimasi_eta_port),
            'estimasi_eta_warehouse' => $this->formatDate($material->estimasi_eta_warehouse),
            'estimasi_bulan_eta' => e($material->estimasi_bulan_eta ?: '-'),
            'keterangan' => e($material->keterangan ?: '-'),
            'estimasi_delay_eta_port' => $this->formatDate($material->estimasi_delay_eta_port),
            'estimasi_delay_eta_warehouse' => $this->formatDate($material->estimasi_delay_eta_warehouse),
            'port' => e($material->port ?: '-'),
            'number_po' => e($material->number_po ?: '-'),
            'month_order' => e($material->month_order ?: '-'),
            'remarks' => $remarksRaw
                ? '<span title="'.e($remarksRaw).'">'.e($remarksTruncated).'</span>'
                : '-',
            'packing_list' => $this->attachmentDisplay($material, 'packing_list_path', 'packing-list', $allowDownload),
            'mtc' => $this->attachmentDisplay($material, 'mtc_path', 'mtc', $allowDownload),
            'actions' => $this->actionButtons($material, $context, $allowManage),
        ];
    }

    private function filterOptions(): array
    {
        return $this->filterOptionsForQuery(OutstandingMaterial::query()->unarchived());
    }

    private function distinctValues(string $field): array
    {
        return $this->distinctValuesForQuery(OutstandingMaterial::query()->unarchived(), $field);
    }

    private function filterOptionsForQuery(Builder $query): array
    {
        return [
            'suppliers' => $this->distinctValuesForQuery($query, 'supplier'),
            'types' => $this->distinctValuesForQuery($query, 'type'),
            'invoices' => $this->distinctValuesForQuery($query, 'number_invoice'),
            'months' => $this->distinctValuesForQuery($query, 'estimasi_bulan_eta'),
            'month_orders' => $this->distinctValuesForQuery($query, 'month_order'),
        ];
    }

    private function distinctValuesForQuery(Builder $query, string $field): array
    {
        return (clone $query)
            ->reorder()
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->select($field)
            ->distinct()
            ->orderBy($field)
            ->pluck($field)
            ->all();
    }

    private function invoiceScope(OutstandingMaterial $anchor): Builder
    {
        $identityKey = $this->identity->invoiceKeyForMaterial($anchor);

        if ($anchor->invoice_id && $identityKey !== null) {
            return OutstandingMaterial::query()->where(function (Builder $query) use ($anchor, $identityKey): void {
                $query->where('invoice_id', $anchor->invoice_id)
                    ->orWhere('invoice_identity_key', $identityKey);
            });
        }

        if ($anchor->invoice_id) {
            return OutstandingMaterial::query()->where('invoice_id', $anchor->invoice_id);
        }

        if ($identityKey !== null) {
            return OutstandingMaterial::query()->where('invoice_identity_key', $identityKey);
        }

        $invoice = trim((string) ($anchor->number_invoice ?? ''));
        $supplier = trim((string) ($anchor->supplier ?? ''));

        return $invoice === ''
            ? OutstandingMaterial::query()->whereKey($anchor->getKey())
            : OutstandingMaterial::query()
                ->where('supplier', $supplier)
                ->where('number_invoice', $invoice);
    }

    /**
     * Include trashed materials so an invoice deletion permanently removes the
     * complete history tied to its scoped header, not only visible rows.
     */
    private function invoiceDeletionScope(OutstandingMaterial $anchor): Builder
    {
        if ($anchor->invoice_id) {
            return OutstandingMaterial::withTrashed()->where('invoice_id', $anchor->invoice_id);
        }

        $identityKey = $this->identity->invoiceKeyForMaterial($anchor);
        if ($identityKey !== null) {
            return OutstandingMaterial::withTrashed()->where('invoice_identity_key', $identityKey);
        }

        $invoice = trim((string) ($anchor->number_invoice ?? ''));
        $supplier = trim((string) ($anchor->supplier ?? ''));

        return $invoice === ''
            ? OutstandingMaterial::withTrashed()->whereKey($anchor->getKey())
            : OutstandingMaterial::withTrashed()
                ->where('supplier', $supplier)
                ->where('number_invoice', $invoice);
    }

    /**
     * Resolve the matching invoice header using the same precedence as the
     * material scope.  This protects legacy rows without an invoice_id.
     */
    private function invoiceHeaderDeletionScope(OutstandingMaterial $anchor): Builder
    {
        if ($anchor->invoice_id) {
            return OutstandingMaterialInvoice::query()->whereKey($anchor->invoice_id);
        }

        $identityKey = $this->identity->invoiceKeyForMaterial($anchor);
        if ($identityKey !== null) {
            return OutstandingMaterialInvoice::query()->where('invoice_identity_key', $identityKey);
        }

        $invoice = trim((string) ($anchor->number_invoice ?? ''));
        $supplier = trim((string) ($anchor->supplier ?? ''));
        if ($invoice === '') {
            return OutstandingMaterialInvoice::query()->whereRaw('1 = 0');
        }

        return OutstandingMaterialInvoice::query()
            ->where('supplier', $supplier)
            ->where('number_invoice', $invoice);
    }

    private function isAnchorArchived(OutstandingMaterial $material): bool
    {
        if ($material->invoiceHeader !== null) {
            return $material->invoiceHeader->isArchived();
        }

        $header = $this->invoiceHeaderDeletionScope($material)->first();

        return $header ? $header->isArchived() : false;
    }

    private function archivedMaterialsBaseQuery(): Builder
    {
        return OutstandingMaterial::query()
            ->where(function (Builder $query): void {
                $query->whereHas('invoiceHeader', function (Builder $headerQuery): void {
                    $headerQuery->whereNotNull('archived_at');
                })->orWhere(function (Builder $fallbackQuery): void {
                    $fallbackQuery->whereNull('invoice_id')
                        ->whereNotNull('invoice_identity_key')
                        ->whereExists(function ($subQuery): void {
                            $subQuery->select(DB::raw(1))
                                ->from('outstanding_material_invoices')
                                ->whereColumn('outstanding_material_invoices.invoice_identity_key', 'outstanding_materials.invoice_identity_key')
                                ->whereNotNull('outstanding_material_invoices.archived_at');
                        });
                });
            });
    }

    private function displayInvoiceNumber(OutstandingMaterial $material): ?string
    {
        $invoice = trim((string) ($material->number_invoice ?? ''));

        return $invoice === '' ? null : $invoice;
    }

    /** @param list<string> $warnings */
    private function logInheritanceWarnings(?string $invoice, array $warnings): void
    {
        if ($warnings === []) {
            return;
        }

        Log::warning('Outstanding Material invoice document inheritance is inconsistent.', [
            'invoice' => $invoice,
            'warnings' => $warnings,
        ]);
    }

    private function authorizeView(): void
    {
        abort_unless($this->access->canView(Auth::user()), 403, 'Unauthorized');
    }

    private function authorizeExport(): void
    {
        abort_unless($this->access->canExport(Auth::user()), 403, 'Unauthorized');
    }

    private function authorizeManage(): void
    {
        abort_unless($this->access->canManage(Auth::user()), 403, 'Unauthorized');
    }

    private function authorizeDocumentUpload(): void
    {
        abort_unless($this->access->canUploadInvoiceDocuments(Auth::user()), 403, 'Unauthorized');
    }

    private function authorizeDocumentDownload(): void
    {
        abort_unless($this->access->canDownloadInvoiceDocuments(Auth::user()), 403, 'Unauthorized');
    }

    private function attachmentDisplay(
        OutstandingMaterial $material,
        string $field,
        string $type,
        bool $allowDownload = false,
    ): string {
        $path = $field === 'packing_list_path'
            ? ($material->packing_list_path ?: $material->attachment_path)
            : $material->{$field};

        if (! $path) {
            return '<span class="text-muted">Not Available</span>';
        }

        if (! $this->documents->isStored($path)) {
            return '<span class="text-warning">Unavailable</span>';
        }

        if (! $allowDownload) {
            return '<span class="text-success">Available</span>';
        }

        $label = $type === 'packing-list' ? 'Download Packing List' : 'Download MTC';

        return sprintf(
            '<a href="%s" download class="btn btn-sm btn-outline-primary" title="%s" aria-label="%s"><i class="bi bi-download" aria-hidden="true"></i><span class="visually-hidden">%s</span></a>',
            e(route('outstanding-materials.attachment', [
                'outstandingMaterial' => $material,
                'type' => $type,
            ])),
            e($label),
            e($label),
            e($label),
        );
    }

    private function actionButtons(
        OutstandingMaterial $material,
        string $context = 'index',
        bool $allowManage = false,
    ): string {
        $showUrl = route('outstanding-materials.show', $material);
        $detailButton = in_array($context, ['index', 'archived'], true)
            ? <<<HTML
                <a href="{$showUrl}" class="om-action-btn om-action-btn--detail" title="Detail" data-bs-toggle="tooltip">
                    <i class="bi bi-eye"></i>
                </a>
            HTML
            : '';
        $manageButtons = '';

        if ($allowManage) {
            $editUrl = route('outstanding-materials.edit', $material);
            $deleteUrl = route('outstanding-materials.destroy', $material);
            $supplier = e($material->supplier ?: '-');
            $type = e($material->type ?: '-');
            $invoice = e($material->number_invoice ?: '-');
            $csrf = csrf_field();
            $method = method_field('DELETE');

            $manageButtons = <<<HTML
                <a href="{$editUrl}" class="om-action-btn om-action-btn--edit" title="Edit" data-bs-toggle="tooltip">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <form action="{$deleteUrl}" method="POST" class="d-inline js-outstanding-delete-form" data-supplier="{$supplier}" data-type="{$type}" data-invoice="{$invoice}">
                    {$csrf}
                    {$method}
                    <button type="submit" class="om-action-btn om-action-btn--delete" title="Delete" data-bs-toggle="tooltip">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            HTML;
        }

        return <<<HTML
            <div class="om-actions" data-action-context="{$context}">
                {$detailButton}
                {$manageButtons}
            </div>
        HTML;
    }

    private function statusBadge(?string $status): string
    {
        if (! $status) {
            return '-';
        }

        $class = match ($status) {
            OutstandingMaterial::STATUS_RECEIVED => 'om-badge--received',
            OutstandingMaterial::STATUS_ON_PRODUCTION => 'om-badge--production',
            OutstandingMaterial::STATUS_ON_SHIPMENT => 'om-badge--shipment',
            default => 'om-badge--default',
        };

        return '<span class="om-badge '.$class.'">'.e($status).'</span>';
    }

    private function summaryStats(?Builder $scope = null): array
    {
        $baseQuery = $scope ? clone $scope : OutstandingMaterial::query()->unarchived();
        $counts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'total' => array_sum($counts),
            'qty_pcs' => (float) ((clone $baseQuery)->sum('qty_pcs') ?? 0),
            'est_qty_kg' => (float) ((clone $baseQuery)->sum('est_qty_kg') ?? 0),
            'on_production' => $counts[OutstandingMaterial::STATUS_ON_PRODUCTION] ?? 0,
            'on_shipment' => $counts[OutstandingMaterial::STATUS_ON_SHIPMENT] ?? 0,
            'received' => $counts[OutstandingMaterial::STATUS_RECEIVED] ?? 0,
        ];
    }

    private function formatNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d-m-Y');
        }

        return $value ? (string) $value : '-';
    }

    private function filterDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function invoiceBaseQuery(): Builder
    {
        $today = now('Asia/Jakarta')->toDateString();
        $vEta = $this->sqlValidDate('outstanding_materials.estimasi_eta_warehouse');
        $vDelay = $this->sqlValidDate('outstanding_materials.estimasi_delay_eta_warehouse');

        // Tier calculation expression (exactly 6 bound parameters)
        $tierExpression = "
            MIN(
                CASE
                    WHEN outstanding_materials.status = 'Received' THEN 5
                    WHEN outstanding_materials.status != 'Received' AND {$vEta} < ? AND ({$vDelay} IS NULL OR {$vDelay} < ?) THEN 1
                    WHEN outstanding_materials.status != 'Received' AND {$vEta} < ? AND {$vDelay} >= ? THEN 2
                    WHEN outstanding_materials.status != 'Received' AND ({$vEta} >= ? OR ({$vEta} IS NULL AND {$vDelay} >= ?)) THEN 3
                    ELSE 4
                END
            )
        ";

        // Operative date for Tier 1 sub-sorting: oldest overdue arrival date (exactly 2 bound parameters)
        $overdueDateExpression = "
            MIN(
                CASE
                    WHEN outstanding_materials.status != 'Received' AND {$vEta} < ? AND ({$vDelay} IS NULL OR {$vDelay} < ?)
                    THEN COALESCE({$vDelay}, {$vEta})
                    ELSE NULL
                END
            )
        ";

        // Operative date for Tier 2 sub-sorting: nearest valid Delay ETA Warehouse date (exactly 2 bound parameters)
        $tier2DelayDateExpression = "
            MIN(
                CASE
                    WHEN outstanding_materials.status != 'Received' AND {$vEta} < ? AND {$vDelay} >= ?
                    THEN {$vDelay}
                    ELSE NULL
                END
            )
        ";

        // Operative date for Tier 3 sub-sorting: nearest valid ETA Warehouse date (exactly 2 bound parameters)
        $tier3EtaDateExpression = "
            MIN(
                CASE
                    WHEN outstanding_materials.status != 'Received' AND ({$vEta} >= ? OR ({$vEta} IS NULL AND {$vDelay} >= ?))
                    THEN COALESCE({$vEta}, {$vDelay})
                    ELSE NULL
                END
            )
        ";

        // Overdue items count for UI urgency badge (exactly 2 bound parameters)
        $overdueCountExpression = "
            SUM(
                CASE
                    WHEN outstanding_materials.status != 'Received' AND {$vEta} < ? AND ({$vDelay} IS NULL OR {$vDelay} < ?)
                    THEN 1 ELSE 0
                END
            )
        ";

        // Archive eligibility flag (0 bound parameters)
        $archivableExpression = "
            CASE
                WHEN COUNT(outstanding_materials.id) > 0
                     AND SUM(CASE WHEN outstanding_materials.status = 'Received' AND outstanding_materials.keterangan = 'Closed' THEN 1 ELSE 0 END) = COUNT(outstanding_materials.id)
                THEN 1 ELSE 0
            END
        ";

        return OutstandingMaterial::query()
            ->unarchived()
            ->leftJoin('outstanding_material_invoices as invoice_headers', 'invoice_headers.id', '=', 'outstanding_materials.invoice_id')
            ->select([
                DB::raw("COALESCE(invoice_headers.invoice_identity_key, outstanding_materials.invoice_identity_key, CONCAT('legacy:', outstanding_materials.supplier, ':', COALESCE(outstanding_materials.number_invoice, ''))) as invoice_group_key"),
                DB::raw('MIN(COALESCE(invoice_headers.number_invoice, outstanding_materials.number_invoice)) as number_invoice'),
                DB::raw('MIN(outstanding_materials.id) as representative_id'),
                DB::raw('COUNT(outstanding_materials.id) as material_count'),
                DB::raw('MIN(COALESCE(invoice_headers.supplier, outstanding_materials.supplier)) as supplier_sample'),
                DB::raw("GROUP_CONCAT(DISTINCT outstanding_materials.status ORDER BY outstanding_materials.status SEPARATOR ',') as status"),
                DB::raw("GROUP_CONCAT(DISTINCT outstanding_materials.keterangan ORDER BY outstanding_materials.keterangan SEPARATOR ',') as keterangan"),
                DB::raw('MAX(outstanding_materials.estimasi_eta_warehouse) as latest_eta_warehouse'),
                DB::raw("MIN(CASE WHEN COALESCE(NULLIF(invoice_headers.packing_list_path, ''), NULLIF(outstanding_materials.packing_list_path, ''), NULLIF(outstanding_materials.attachment_path, '')) IS NOT NULL THEN outstanding_materials.id END) as packing_list_material_id"),
                DB::raw("MIN(CASE WHEN COALESCE(NULLIF(invoice_headers.mtc_path, ''), NULLIF(outstanding_materials.mtc_path, '')) IS NOT NULL THEN outstanding_materials.id END) as mtc_material_id"),
                DB::raw("COUNT(DISTINCT NULLIF(COALESCE(NULLIF(invoice_headers.packing_list_path, ''), NULLIF(outstanding_materials.packing_list_path, ''), NULLIF(outstanding_materials.attachment_path, '')), '')) as packing_list_variant_count"),
                DB::raw("COUNT(DISTINCT NULLIF(COALESCE(NULLIF(invoice_headers.mtc_path, ''), NULLIF(outstanding_materials.mtc_path, '')), '')) as mtc_variant_count"),
                DB::raw("{$archivableExpression} as is_archivable"),
            ])
            ->selectRaw("{$tierExpression} as invoice_priority_tier", [
                $today, $today,
                $today, $today,
                $today, $today,
            ])
            ->selectRaw("{$overdueDateExpression} as tier1_overdue_date", [
                $today, $today,
            ])
            ->selectRaw("{$tier2DelayDateExpression} as tier2_delay_date", [
                $today, $today,
            ])
            ->selectRaw("{$tier3EtaDateExpression} as tier3_eta_date", [
                $today, $today,
            ])
            ->selectRaw("{$overdueCountExpression} as overdue_material_count", [
                $today, $today,
            ])
            ->whereNotNull('outstanding_materials.number_invoice')
            ->where('outstanding_materials.number_invoice', '!=', '')
            ->whereNull('invoice_headers.archived_at')
            ->groupBy('invoice_group_key');
    }

    private function countGroupedQuery(Builder $query): int
    {
        return (int) DB::query()
            ->fromSub(clone $query, 'invoice_groups')
            ->count();
    }

    private function applyInvoiceOrdering(Builder $query, Request $request): void
    {
        $columnOrderMap = [
            0 => 'number_invoice',
            1 => 'supplier_sample',
            2 => 'material_count',
            3 => 'status',
            4 => 'keterangan',
            5 => 'latest_eta_warehouse',
            6 => 'packing_list_variant_count',
            7 => 'mtc_variant_count',
        ];

        $orders = (array) $request->input('order', []);
        $appliedOrder = false;

        foreach ($orders as $order) {
            $columnIndex = isset($order['column']) ? (int) $order['column'] : null;
            if ($columnIndex === null || ! array_key_exists($columnIndex, $columnOrderMap)) {
                continue;
            }

            $columnName = $columnOrderMap[$columnIndex];
            $direction = strtolower((string) ($order['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

            if ($columnName === 'latest_eta_warehouse' && $direction === 'asc') {
                $query->orderByRaw("CASE WHEN latest_eta_warehouse IS NULL OR TRIM(latest_eta_warehouse) = '' THEN 1 ELSE 0 END ASC")
                    ->orderBy('latest_eta_warehouse', 'asc');
            } else {
                $query->orderBy($columnName, $direction);
            }
            $appliedOrder = true;
        }

        if (! $appliedOrder) {
            $query->orderBy('invoice_priority_tier', 'asc')
                ->orderByRaw('CASE WHEN tier1_overdue_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('tier1_overdue_date', 'asc')
                ->orderByRaw('CASE WHEN tier2_delay_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('tier2_delay_date', 'asc')
                ->orderByRaw('CASE WHEN tier3_eta_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('tier3_eta_date', 'asc')
                ->orderByRaw("CASE WHEN latest_eta_warehouse IS NULL OR TRIM(latest_eta_warehouse) = '' THEN 1 ELSE 0 END ASC")
                ->orderBy('latest_eta_warehouse', 'desc')
                ->orderBy('number_invoice', 'asc')
                ->orderBy('supplier_sample', 'asc');
        }
    }

    private function invoiceDataTableRow(
        object $invoice,
        bool $canManage,
        bool $canUpload,
        bool $canDownload,
    ): array {
        $invoiceValue = (string) $invoice->number_invoice;
        $representative = $invoice->representative_id
            ? OutstandingMaterial::query()->find((int) $invoice->representative_id)
            : null;
        $packingAnchor = $invoice->packing_list_material_id
            ? OutstandingMaterial::query()->find((int) $invoice->packing_list_material_id)
            : null;
        $mtcAnchor = $invoice->mtc_material_id
            ? OutstandingMaterial::query()->find((int) $invoice->mtc_material_id)
            : null;

        $statusHtml = collect(explode(',', (string) $invoice->status))
            ->filter()
            ->map(fn (string $status): string => '<span class="om-badge '.$this->statusBadgeClass(trim($status)).'">'.e(trim($status)).'</span>')
            ->implode(' ');
        $keteranganHtml = collect(explode(',', (string) $invoice->keterangan))
            ->filter()
            ->map(fn (string $keterangan): string => '<span class="om-badge '.$this->keteranganBadgeClass(trim($keterangan)).'">'.e(trim($keterangan)).'</span>')
            ->implode(' ');

        $overdueBadge = ((int) ($invoice->overdue_material_count ?? 0) > 0)
            ? ' <span class="om-badge om-badge--delay" title="'.(int) $invoice->overdue_material_count.' material overdue"><i class="bi bi-exclamation-circle me-1"></i>Overdue</span>'
            : '';

        $isEligibleForArchive = ((int) ($invoice->is_archivable ?? 0) === 1);

        return [
            'number_invoice' => e($invoiceValue ?: '-'),
            'supplier' => e($invoice->supplier_sample ?: '-'),
            'material_count' => number_format((int) $invoice->material_count),
            'keterangan' => $keteranganHtml ?: '<span class="om-badge">-</span>',
            'status' => $statusHtml ?: '<span class="om-badge">-</span>',
            'latest_eta_warehouse' => $this->formatDate($invoice->latest_eta_warehouse),
            'packing_list' => $this->invoiceDocumentDisplay(
                $packingAnchor,
                'packing-list',
                (int) $invoice->packing_list_variant_count,
                $canDownload,
            ),
            'mtc' => $this->invoiceDocumentDisplay(
                $mtcAnchor,
                'mtc',
                (int) $invoice->mtc_variant_count,
                $canDownload,
            ),
            'actions' => $this->invoiceActionCell(
                $representative,
                $invoiceValue,
                (int) $invoice->material_count,
                (string) $invoice->supplier_sample,
                $canManage,
                $canUpload,
                $isEligibleForArchive,
            ),
        ];
    }

    private function invoiceDocumentDisplay(
        ?OutstandingMaterial $anchor,
        string $type,
        int $variantCount,
        bool $canDownload,
    ): string {
        if (! $anchor) {
            return '<span class="text-muted">Not Available</span>';
        }

        $path = $type === 'packing-list'
            ? ($anchor->packing_list_path ?: $anchor->attachment_path)
            : $anchor->mtc_path;
        if (! $path || ! $this->documents->isStored($path)) {
            return '<span class="text-warning">Unavailable</span>';
        }

        $warning = $variantCount > 1
            ? '<span class="om-document-warning" title="Legacy document paths differ across this invoice"><i class="bi bi-exclamation-triangle-fill"></i></span>'
            : '';

        if (! $canDownload) {
            return '<span class="text-success">Available</span> '.$warning;
        }

        $label = $type === 'packing-list' ? 'Download Packing List' : 'Download MTC';

        return $warning.sprintf(
            '<a href="%s" download class="btn btn-sm btn-outline-primary" title="%s" aria-label="%s"><i class="bi bi-download" aria-hidden="true"></i><span class="visually-hidden">%s</span></a>',
            e(route('outstanding-materials.attachment', [
                'outstandingMaterial' => $anchor,
                'type' => $type,
            ])),
            e($label),
            e($label),
            e($label),
        );
    }

    private function statusBadgeClass(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'production', 'on production' => 'om-badge--production',
            'on shipment', 'shipment' => 'om-badge--shipment',
            'received' => 'om-badge--received',
            default => 'om-badge--default',
        };
    }

    private function keteranganBadgeClass(?string $keterangan): string
    {
        return match (strtolower((string) $keterangan)) {
            'on schedule' => 'om-badge--on-schedule',
            'delay' => 'om-badge--delay',
            'closed' => 'om-badge--closed',
            default => 'om-badge--default',
        };
    }

    private function invoiceActionCell(
        ?OutstandingMaterial $representative,
        string $invoice,
        int $materialCount,
        string $supplier,
        bool $canManage,
        bool $canUpload,
        bool $isEligibleForArchive = false,
    ): string {
        $buttons = [];
        if ($representative) {
            $buttons[] = sprintf(
                '<a href="%s" class="om-action-btn om-action-btn--detail" title="Detail" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>',
                e(route('outstanding-materials.show', $representative)),
            );
        }

        if ($canManage && $representative) {
            $invoiceAttribute = e($invoice);
            $supplierAttribute = e($supplier);

            if ($isEligibleForArchive) {
                $archiveUrl = e(route('outstanding-materials.invoice.archive', $representative));
                $csrf = csrf_field();
                $buttons[] = <<<HTML
                    <form action="{$archiveUrl}" method="POST" class="d-inline js-outstanding-invoice-archive-form" data-invoice="{$invoiceAttribute}">
                        {$csrf}
                        <button type="submit" class="om-action-btn om-action-btn--archive text-warning" title="Arsipkan Invoice" aria-label="Arsipkan Invoice" data-bs-toggle="tooltip"><i class="bi bi-archive"></i></button>
                    </form>
                HTML;
            } else {
                $buttons[] = '<button type="button" class="om-action-btn om-action-btn--archive text-muted" disabled title="Invoice hanya dapat diarsipkan jika semua material Received dan Closed" aria-label="Arsipkan Invoice (Belum Memenuhi Syarat)" data-bs-toggle="tooltip"><i class="bi bi-archive"></i></button>';
            }

            $buttons[] = '<button type="button" class="om-action-btn om-action-btn--edit js-open-invoice-modal" data-anchor-id="'.e((string) $representative->getKey()).'" data-invoice="'.$invoiceAttribute.'" title="Update Materials" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';

            $deleteUrl = e(route('outstanding-materials.invoice.destroy', $representative));
            $csrf = csrf_field();
            $method = method_field('DELETE');
            $buttons[] = <<<HTML
                <form action="{$deleteUrl}" method="POST" class="d-inline js-outstanding-invoice-delete-form" data-invoice="{$invoiceAttribute}" data-supplier="{$supplierAttribute}" data-material-count="{$materialCount}">
                    {$csrf}
                    {$method}
                    <button type="submit" class="om-action-btn om-action-btn--delete" title="Hapus Invoice Permanen" aria-label="Hapus Invoice Permanen" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>
                </form>
            HTML;
        }

        if ($canUpload && $representative) {
            $buttons[] = '<button type="button" class="om-action-btn om-action-btn--upload js-open-invoice-upload" data-anchor-id="'.e((string) $representative->getKey()).'" data-invoice="'.e($invoice).'" title="Upload / Replace Documents" aria-label="Upload / Replace Documents" data-bs-toggle="tooltip"><i class="bi bi-cloud-arrow-up"></i></button>';
        }

        return '<div class="om-actions om-invoice-actions">'.implode('', $buttons).'</div>';
    }

    private function archivedInvoiceBaseQuery(): Builder
    {
        return OutstandingMaterial::query()
            ->archived()
            ->leftJoin('outstanding_material_invoices as invoice_headers', 'invoice_headers.id', '=', 'outstanding_materials.invoice_id')
            ->select([
                DB::raw("COALESCE(invoice_headers.invoice_identity_key, outstanding_materials.invoice_identity_key, CONCAT('legacy:', outstanding_materials.supplier, ':', COALESCE(outstanding_materials.number_invoice, ''))) as invoice_group_key"),
                DB::raw('MIN(COALESCE(invoice_headers.number_invoice, outstanding_materials.number_invoice)) as number_invoice'),
                DB::raw('MIN(outstanding_materials.id) as representative_id'),
                DB::raw('COUNT(outstanding_materials.id) as material_count'),
                DB::raw('MIN(COALESCE(invoice_headers.supplier, outstanding_materials.supplier)) as supplier_sample'),
                DB::raw("GROUP_CONCAT(DISTINCT outstanding_materials.status ORDER BY outstanding_materials.status SEPARATOR ',') as status"),
                DB::raw("GROUP_CONCAT(DISTINCT outstanding_materials.keterangan ORDER BY outstanding_materials.keterangan SEPARATOR ',') as keterangan"),
                DB::raw('MAX(outstanding_materials.estimasi_eta_warehouse) as latest_eta_warehouse'),
                DB::raw('MAX(COALESCE(outstanding_materials.archived_at, invoice_headers.archived_at)) as archived_at'),
                DB::raw("MIN(CASE WHEN COALESCE(NULLIF(invoice_headers.packing_list_path, ''), NULLIF(outstanding_materials.packing_list_path, ''), NULLIF(outstanding_materials.attachment_path, '')) IS NOT NULL THEN outstanding_materials.id END) as packing_list_material_id"),
                DB::raw("MIN(CASE WHEN COALESCE(NULLIF(invoice_headers.mtc_path, ''), NULLIF(outstanding_materials.mtc_path, '')) IS NOT NULL THEN outstanding_materials.id END) as mtc_material_id"),
                DB::raw("COUNT(DISTINCT NULLIF(COALESCE(NULLIF(invoice_headers.packing_list_path, ''), NULLIF(outstanding_materials.packing_list_path, ''), NULLIF(outstanding_materials.attachment_path, '')), '')) as packing_list_variant_count"),
                DB::raw("COUNT(DISTINCT NULLIF(COALESCE(NULLIF(invoice_headers.mtc_path, ''), NULLIF(outstanding_materials.mtc_path, '')), '')) as mtc_variant_count"),
            ])
            ->whereNotNull('outstanding_materials.number_invoice')
            ->where('outstanding_materials.number_invoice', '!=', '')
            ->where(function (Builder $q): void {
                $q->whereNotNull('outstanding_materials.archived_at')
                    ->orWhereNotNull('invoice_headers.archived_at');
            })
            ->groupBy('invoice_group_key');
    }

    private function applyArchivedInvoiceOrdering(Builder $query, Request $request): void
    {
        $columnOrderMap = [
            0 => 'number_invoice',
            1 => 'supplier_sample',
            2 => 'material_count',
            3 => 'status',
            4 => 'keterangan',
            5 => 'latest_eta_warehouse',
            6 => 'archived_at',
        ];

        $orders = (array) $request->input('order', []);
        $appliedOrder = false;

        foreach ($orders as $order) {
            $columnIndex = isset($order['column']) ? (int) $order['column'] : null;
            if ($columnIndex === null || ! array_key_exists($columnIndex, $columnOrderMap)) {
                continue;
            }

            $columnName = $columnOrderMap[$columnIndex];
            $direction = strtolower((string) ($order['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

            $query->orderBy($columnName, $direction);
            $appliedOrder = true;
        }

        if (! $appliedOrder) {
            $query->orderBy('archived_at', 'desc')
                ->orderBy('latest_eta_warehouse', 'desc')
                ->orderBy('number_invoice', 'asc');
        }
    }

    private function archivedInvoiceDataTableRow(
        object $invoice,
        bool $canManage,
        bool $canDownload,
    ): array {
        $invoiceValue = (string) $invoice->number_invoice;
        $representative = $invoice->representative_id
            ? OutstandingMaterial::query()->find((int) $invoice->representative_id)
            : null;
        $packingAnchor = $invoice->packing_list_material_id
            ? OutstandingMaterial::query()->find((int) $invoice->packing_list_material_id)
            : null;
        $mtcAnchor = $invoice->mtc_material_id
            ? OutstandingMaterial::query()->find((int) $invoice->mtc_material_id)
            : null;

        $statusHtml = collect(explode(',', (string) $invoice->status))
            ->filter()
            ->map(fn (string $status): string => '<span class="om-badge '.$this->statusBadgeClass(trim($status)).'">'.e(trim($status)).'</span>')
            ->implode(' ');
        $keteranganHtml = collect(explode(',', (string) $invoice->keterangan))
            ->filter()
            ->map(fn (string $keterangan): string => '<span class="om-badge '.$this->keteranganBadgeClass(trim($keterangan)).'">'.e(trim($keterangan)).'</span>')
            ->implode(' ');

        return [
            'number_invoice' => e($invoiceValue ?: '-'),
            'supplier' => e($invoice->supplier_sample ?: '-'),
            'material_count' => number_format((int) $invoice->material_count),
            'status' => $statusHtml ?: '<span class="om-badge">-</span>',
            'keterangan' => $keteranganHtml ?: '<span class="om-badge">-</span>',
            'latest_eta_warehouse' => $this->formatDate($invoice->latest_eta_warehouse),
            'archived_at' => $invoice->archived_at ? Carbon::parse($invoice->archived_at)->format('d-m-Y H:i') : '-',
            'packing_list' => $this->invoiceDocumentDisplay(
                $packingAnchor,
                'packing-list',
                (int) $invoice->packing_list_variant_count,
                $canDownload,
            ),
            'mtc' => $this->invoiceDocumentDisplay(
                $mtcAnchor,
                'mtc',
                (int) $invoice->mtc_variant_count,
                $canDownload,
            ),
            'actions' => $this->archivedInvoiceActionCell(
                $representative,
                $invoiceValue,
                $canManage,
            ),
        ];
    }

    private function archivedInvoiceActionCell(
        ?OutstandingMaterial $representative,
        string $invoice,
        bool $canManage,
    ): string {
        $buttons = [];
        if ($representative) {
            $buttons[] = sprintf(
                '<a href="%s" class="om-action-btn om-action-btn--detail" title="Detail" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>',
                e(route('outstanding-materials.show', $representative)),
            );
        }

        if ($canManage && $representative) {
            $invoiceAttribute = e($invoice);
            $buttons[] = '<button type="button" class="om-action-btn om-action-btn--edit js-open-archived-material-modal" data-anchor-id="'.e((string) $representative->getKey()).'" data-invoice="'.$invoiceAttribute.'" title="Lihat &amp; Kelola Material Arsip" aria-label="Lihat &amp; Kelola Material Arsip" data-bs-toggle="tooltip"><i class="bi bi-list-check"></i></button>';

            $restoreUrl = e(route('outstanding-materials.invoice.restore', $representative));
            $csrf = csrf_field();
            $buttons[] = <<<HTML
                <form action="{$restoreUrl}" method="POST" class="d-inline js-outstanding-invoice-restore-form" data-invoice="{$invoiceAttribute}">
                    {$csrf}
                    <button type="submit" class="om-action-btn om-action-btn--restore text-success" title="Restore Semua Material Invoice ke Aktif" aria-label="Restore Semua Material Invoice ke Aktif" data-bs-toggle="tooltip"><i class="bi bi-arrow-counterclockwise"></i></button>
                </form>
            HTML;
        }

        return '<div class="om-actions om-invoice-actions">'.implode('', $buttons).'</div>';
    }

    private function validationAttributes(): array
    {
        return [
            'supplier' => 'Supplier',
            'type' => 'TYPE',
            'thickness' => 'Thickness',
            'width' => 'Width',
            'diameter' => 'Diameter',
            'length' => 'Length',
            'qty_pcs' => 'QTY (PCS)',
            'est_qty_kg' => 'Est QTY (KG)',
            'number_invoice' => 'Number Invoice',
            'status' => 'Status',
            'estimasi_eta_port' => 'Estimasi ETA Port',
            'estimasi_eta_warehouse' => 'Estimasi ETA Warehouse',
            'estimasi_bulan_eta' => 'Estimasi Bulan ETA',
            'keterangan' => 'Keterangan',
            'estimasi_delay_eta_port' => 'Estimasi Delay ETA Port',
            'estimasi_delay_eta_warehouse' => 'Estimasi Delay ETA Warehouse',
            'port' => 'Port',
            'number_po' => 'Nomor PO',
            'month_order' => 'Month Order',
            'remarks' => 'Remarks',
            'invoice_context_id' => 'Invoice Context',
            'import_mode' => 'Import Mode',
        ];
    }
}
