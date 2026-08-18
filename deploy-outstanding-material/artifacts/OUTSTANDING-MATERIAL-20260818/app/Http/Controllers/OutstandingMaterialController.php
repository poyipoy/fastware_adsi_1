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
            'filterOptions' => $this->filterOptions(),
            'canManageOutstandingMaterials' => $this->access->canManage(Auth::user()),
            'canExportOutstandingMaterials' => $this->access->canExport(Auth::user()),
            'summary' => $this->summaryStats(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizeView();

        $baseQuery = OutstandingMaterial::query();
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

        $data = $filteredQuery
            ->get()
            ->map(fn (OutstandingMaterial $material): array => $this->dataTableRow($material, 'detail'))
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
            'outstanding_material_invoice_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $invoice) . '_' . now()->format('Ymd_His') . '.xlsx',
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
            'invoice_context_id' => 'nullable|integer|exists:outstanding_materials,id',
            'materials' => 'required|array|min:1|max:' . OutstandingMaterialBatchService::MAX_ROWS,
            'materials.*.type' => 'required|string|max:255',
            'materials.*.thickness' => 'nullable|numeric',
            'materials.*.width' => 'nullable|numeric',
            'materials.*.diameter' => 'nullable|numeric',
            'materials.*.length' => 'nullable|string|max:255',
            'materials.*.qty_pcs' => 'nullable|numeric',
            'materials.*.est_qty_kg' => 'nullable|numeric',
        ], [], $this->validationAttributes());

        $contextAnchor = null;
        if (!empty($data['invoice_context_id'])) {
            $contextAnchor = OutstandingMaterial::query()->findOrFail((int) $data['invoice_context_id']);
            if ($this->identity->invoiceKey($contextAnchor->supplier, $contextAnchor->number_invoice) === null) {
                throw ValidationException::withMessages([
                    'invoice_context_id' => 'Material anchor belum memiliki Supplier dan Number Invoice yang valid.',
                ]);
            }
        }

        $rows = $data['materials'];
        unset($data['materials'], $data['invoice_context_id']);

        $created = $this->batches->create(
            $data,
            $rows,
            $contextAnchor,
            (int) Auth::id(),
        );

        $anchor = $contextAnchor ?: $created->first();

        return redirect()
            ->route('outstanding-materials.show', $anchor)
            ->with('success', $created->count() . ' material berhasil ditambahkan ke invoice.');
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
        ];

        $created = $this->batches->create($header, [$row], $contextAnchor, (int) Auth::id());
        $material = $created->first();

        return redirect()
            ->route('outstanding-materials.show', $material)
            ->with('success', 'Data Outstanding Material berhasil ditambahkan.');
    }

    public function show(OutstandingMaterial $outstandingMaterial): View
    {
        $this->authorizeView();

        $invoiceScope = $this->invoiceScope($outstandingMaterial);

        return view('outstanding_materials.show', [
            'material' => $outstandingMaterial->load(['creator', 'updater']),
            'anchorMaterial' => $outstandingMaterial,
            'invoiceNumber' => $this->displayInvoiceNumber($outstandingMaterial),
            'summary' => $this->summaryStats($invoiceScope),
            'filterOptions' => $this->filterOptionsForQuery($invoiceScope),
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'canManageOutstandingMaterials' => $this->access->canManage(Auth::user()),
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

            $oldPaths = [
                $lockedMaterial->attachment_path,
                $lockedMaterial->packing_list_path,
                $lockedMaterial->mtc_path,
            ];
            $oldInvoiceId = $lockedMaterial->invoice_id;

            $invoiceChanged = (string) ($lockedMaterial->invoice_identity_key ?? '')
                !== (string) ($data['invoice_identity_key'] ?? '');
            if ($invoiceChanged) {
                $inheritance = $this->documents->inheritanceForIdentityKey($data['invoice_identity_key'] ?? null, true);
                $this->logInheritanceWarnings($data['number_invoice'] ?? null, $inheritance['warnings']);
                $data['packing_list_path'] = $inheritance['packing_list_path'];
                $data['mtc_path'] = $inheritance['mtc_path'];
                $data['attachment_path'] = null;
            }

            if ($invoiceChanged || !$lockedMaterial->invoice_id) {
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
                if ($oldHeader && !OutstandingMaterial::query()->where('invoice_id', $oldInvoiceId)->exists()) {
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
                if ($header && !OutstandingMaterial::query()->where('invoice_id', $invoiceId)->exists()) {
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
                'Invoice ' . $invoice . ' dan ' . $deletedMaterialCount . ' material terkait berhasil dihapus permanen.',
            );
    }

    public function export(Request $request)
    {
        $this->authorizeExport();

        $query = OutstandingMaterial::query();
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
            'outstanding_materials_' . now()->format('Ymd_His') . '.xlsx',
            ExcelFormat::XLSX
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $request->validate([
            'import_file' => [
                'required',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!$value instanceof \Illuminate\Http\UploadedFile) {
                        $fail('File import tidak valid.');
                        return;
                    }

                    $allowedExtensions = ['xlsx', 'xls', 'csv'];
                    $extension = strtolower((string) $value->getClientOriginalExtension());

                    if (!in_array($extension, $allowedExtensions, true)) {
                        $fail('File import harus berekstensi xlsx, xls, atau csv.');
                    }
                },
            ],
        ]);

        try {
            $preview = $this->importPreviews->preview(
                $request->file('import_file'),
                (int) Auth::id(),
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withErrors([
                    'import_file' => 'File tidak dapat diproses: ' . $exception->getMessage(),
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
                ->withErrors(['import_file' => 'Import tidak dijalankan: ' . $exception->getMessage()]);
        }

        return redirect()
            ->route('outstanding-materials.index')
            ->with('success', sprintf('Import selesai: %d material berhasil ditambahkan.', $counts['inserted']));
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
            $like = '%' . $search . '%';
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
                ->select('id', 'supplier', 'type', 'thickness', 'width', 'diameter', 'length', 'qty_pcs', 'est_qty_kg', 'status', 'keterangan')
                ->orderBy('id')
                ->get(),
        );
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
        ]);

        $anchor = OutstandingMaterial::query()->findOrFail((int) $data['anchor_id']);
        $request->merge(['material_ids' => $data['material_ids'], 'status' => $data['status'] ?? null, 'keterangan' => $data['keterangan'] ?? null]);

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
        ]);

        $materialIds = array_values(array_unique(array_map('intval', $data['material_ids'])));
        $updates = ['updated_by' => Auth::id()];

        if (!empty($data['keterangan'])) {
            $updates['keterangan'] = $data['keterangan'];
        }

        if (!empty($data['status'])) {
            $updates['status'] = $data['status'];
        }

        if (count($updates) === 1) {
            throw ValidationException::withMessages([
                'status' => 'Choose a Status or Keterangan before updating materials.',
            ]);
        }

        DB::transaction(function () use ($outstandingMaterial, $materialIds, $updates): void {
            $lockedAnchor = OutstandingMaterial::query()
                ->whereKey($outstandingMaterial->getKey())
                ->lockForUpdate()
                ->firstOrFail();
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
            'message' => count($materialIds) . ' material(s) updated successfully.',
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

        if (!$request->hasFile('packing_list') && !$request->hasFile('mtc')) {
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

        $result = $this->documents->uploadForIdentityKey(
            $identityKey,
            $request->file('packing_list'),
            $request->file('mtc'),
            (int) Auth::id(),
        );

        return response()->json([
            'success' => true,
            'message' => $result['updated'] . ' material(s) synchronized successfully.',
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

        if (!$resolved || !$this->documents->isStored($path)) {
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
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
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
        foreach (['supplier', 'type', 'number_invoice', 'status', 'keterangan', 'estimasi_bulan_eta'] as $field) {
            if (!in_array($field, $excludedFields, true) && $request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        foreach (['thickness', 'width', 'diameter', 'qty_pcs', 'est_qty_kg'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%' . $request->input($field) . '%');
            }
        }
        
        if ($request->filled('material_length')) {
            $query->where('length', 'like', '%' . $request->input('material_length') . '%');
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
            $builder->where('supplier', 'like', '%' . $search . '%')
                ->orWhere('type', 'like', '%' . $search . '%')
                ->orWhere('number_invoice', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%')
                ->orWhere('estimasi_eta_warehouse', 'like', '%' . $search . '%')
                ->orWhere('estimasi_bulan_eta', 'like', '%' . $search . '%')
                ->orWhere('keterangan', 'like', '%' . $search . '%')
                ->orWhere('attachment_path', 'like', '%' . $search . '%')
                ->orWhere('packing_list_path', 'like', '%' . $search . '%')
                ->orWhere('mtc_path', 'like', '%' . $search . '%');
        });
    }

    private function applyOrdering(Builder $query, Request $request): void
    {
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
            17 => 'packing_list_path',
            18 => 'mtc_path',
        ];

        $orders = (array) $request->input('order', []);
        $appliedOrder = false;

        foreach ($orders as $order) {
            $columnIndex = isset($order['column']) ? (int) $order['column'] : null;
            if ($columnIndex === null || !array_key_exists($columnIndex, $columnOrderMap)) {
                continue;
            }

            $direction = strtolower((string) ($order['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($columnOrderMap[$columnIndex], $direction);
            $appliedOrder = true;
        }

        if (!$appliedOrder) {
            $query->orderByDesc('id');
        }
    }

    private function dataTableRow(OutstandingMaterial $material, string $context = 'index'): array
    {
        $allowManage = $context === 'detail' && $this->access->canManage(Auth::user());
        $allowDownload = $context === 'detail'
            && $this->access->canDownloadInvoiceDocuments(Auth::user());

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
            'packing_list' => $this->attachmentDisplay($material, 'packing_list_path', 'packing-list', $allowDownload),
            'mtc' => $this->attachmentDisplay($material, 'mtc_path', 'mtc', $allowDownload),
            'actions' => $this->actionButtons($material, $context, $allowManage),
        ];
    }

    private function filterOptions(): array
    {
        return $this->filterOptionsForQuery(OutstandingMaterial::query());
    }

    private function distinctValues(string $field): array
    {
        return $this->distinctValuesForQuery(OutstandingMaterial::query(), $field);
    }

    private function filterOptionsForQuery(Builder $query): array
    {
        return [
            'suppliers' => $this->distinctValuesForQuery($query, 'supplier'),
            'types' => $this->distinctValuesForQuery($query, 'type'),
            'invoices' => $this->distinctValuesForQuery($query, 'number_invoice'),
            'months' => $this->distinctValuesForQuery($query, 'estimasi_bulan_eta'),
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
        if ($anchor->invoice_id) {
            return OutstandingMaterial::query()->where('invoice_id', $anchor->invoice_id);
        }

        $identityKey = $this->identity->invoiceKeyForMaterial($anchor);
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
    ): string
    {
        $path = $field === 'packing_list_path'
            ? ($material->packing_list_path ?: $material->attachment_path)
            : $material->{$field};

        if (!$path) {
            return '<span class="text-muted">Not Available</span>';
        }

        if (!$this->documents->isStored($path)) {
            return '<span class="text-warning">Unavailable</span>';
        }

        if (!$allowDownload) {
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
    ): string
    {
        $showUrl = route('outstanding-materials.show', $material);
        $detailButton = $context === 'index'
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
        if (!$status) {
            return '-';
        }

        $class = match ($status) {
            OutstandingMaterial::STATUS_RECEIVED => 'om-badge--received',
            OutstandingMaterial::STATUS_ON_PRODUCTION => 'om-badge--production',
            OutstandingMaterial::STATUS_ON_SHIPMENT => 'om-badge--shipment',
            default => 'om-badge--default',
        };

        return '<span class="om-badge ' . $class . '">' . e($status) . '</span>';
    }

    private function summaryStats(?Builder $scope = null): array
    {
        $baseQuery = $scope ? clone $scope : OutstandingMaterial::query();
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
        return OutstandingMaterial::query()
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
            ])
            ->whereNotNull('outstanding_materials.number_invoice')
            ->where('outstanding_materials.number_invoice', '!=', '')
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
            if ($columnIndex === null || !array_key_exists($columnIndex, $columnOrderMap)) {
                continue;
            }

            $direction = strtolower((string) ($order['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($columnOrderMap[$columnIndex], $direction);
            $appliedOrder = true;
        }

        if (!$appliedOrder) {
            $query->orderBy('number_invoice');
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
            ->map(fn (string $status): string => '<span class="om-badge ' . $this->statusBadgeClass(trim($status)) . '">' . e(trim($status)) . '</span>')
            ->implode(' ');
        $keteranganHtml = collect(explode(',', (string) $invoice->keterangan))
            ->filter()
            ->map(fn (string $keterangan): string => '<span class="om-badge ' . $this->keteranganBadgeClass(trim($keterangan)) . '">' . e(trim($keterangan)) . '</span>')
            ->implode(' ');

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
            ),
        ];
    }

    private function invoiceDocumentDisplay(
        ?OutstandingMaterial $anchor,
        string $type,
        int $variantCount,
        bool $canDownload,
    ): string {
        if (!$anchor) {
            return '<span class="text-muted">Not Available</span>';
        }

        $path = $type === 'packing-list'
            ? ($anchor->packing_list_path ?: $anchor->attachment_path)
            : $anchor->mtc_path;
        if (!$path || !$this->documents->isStored($path)) {
            return '<span class="text-warning">Unavailable</span>';
        }

        $warning = $variantCount > 1
            ? '<span class="om-document-warning" title="Legacy document paths differ across this invoice"><i class="bi bi-exclamation-triangle-fill"></i></span>'
            : '';

        if (!$canDownload) {
            return '<span class="text-success">Available</span> ' . $warning;
        }

        $label = $type === 'packing-list' ? 'Download Packing List' : 'Download MTC';

        return $warning . sprintf(
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
            $buttons[] = '<button type="button" class="om-action-btn om-action-btn--edit js-open-invoice-modal" data-anchor-id="' . e((string) $representative->getKey()) . '" data-invoice="' . $invoiceAttribute . '" title="Update Materials" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';

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
            $buttons[] = '<button type="button" class="om-action-btn om-action-btn--upload js-open-invoice-upload" data-anchor-id="' . e((string) $representative->getKey()) . '" data-invoice="' . e($invoice) . '" title="Upload / Replace Documents" aria-label="Upload / Replace Documents" data-bs-toggle="tooltip"><i class="bi bi-cloud-arrow-up"></i></button>';
        }

        return '<div class="om-actions om-invoice-actions">' . implode('', $buttons) . '</div>';
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
            'invoice_context_id' => 'Invoice Context',
        ];
    }
}
