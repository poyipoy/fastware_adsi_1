<?php

namespace App\Http\Controllers;

use App\Exports\OutstandingMaterialExport;
use App\Exports\OutstandingMaterialTemplateExport;
use App\Imports\OutstandingMaterialImport;
use App\Models\OutstandingMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class OutstandingMaterialController extends Controller
{
    private const ATTACHMENT_DIRECTORY = 'outstanding-materials';
    private const MANAGER_NAMES = [
        'ADMINISTRATOR',
        'ADMINSTRATOR',
        'ILYAS NOOR FIRDAUS',
    ];

    public function index(): View
    {
        return view('outstanding_materials.index', [
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'filterOptions' => $this->filterOptions(),
            'canManageOutstandingMaterials' => $this->canManageOutstandingMaterials(),
            'summary' => $this->summaryStats(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
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
            ->map(fn (OutstandingMaterial $material): array => $this->dataTableRow($material))
            ->values()
            ->all();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create(): View
    {
        $this->authorizeOutstandingMaterialManagement();

        return view('outstanding_materials.form', [
            'material' => new OutstandingMaterial(),
            'isEdit' => false,
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeOutstandingMaterialManagement();

        $data = $this->validatedPayload($request);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = null;

        $material = OutstandingMaterial::create($data);

        return redirect()
            ->route('outstanding-materials.show', $material)
            ->with('success', 'Data Outstanding Material berhasil ditambahkan.');
    }

    public function show(OutstandingMaterial $outstandingMaterial): View
    {
        $outstandingMaterial->load(['creator', 'updater']);

        return view('outstanding_materials.show', [
            'material' => $outstandingMaterial,
            'canManageOutstandingMaterials' => $this->canManageOutstandingMaterials(),
        ]);
    }

    public function edit(OutstandingMaterial $outstandingMaterial): View
    {
        $this->authorizeOutstandingMaterialManagement();

        return view('outstanding_materials.form', [
            'material' => $outstandingMaterial,
            'isEdit' => true,
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
        ]);
    }

    public function update(Request $request, OutstandingMaterial $outstandingMaterial): RedirectResponse
    {
        $this->authorizeOutstandingMaterialManagement();

        $oldPaths = [
            $outstandingMaterial->attachment_path,
            $outstandingMaterial->packing_list_path,
            $outstandingMaterial->mtc_path,
        ];

        $data = $this->validatedPayload($request, $outstandingMaterial);
        $data['updated_by'] = Auth::id();

        $outstandingMaterial->update($data);

        $this->deleteReplacedStoredAttachments($oldPaths, [
            $outstandingMaterial->attachment_path,
            $outstandingMaterial->packing_list_path,
            $outstandingMaterial->mtc_path,
        ]);

        return redirect()
            ->route('outstanding-materials.show', $outstandingMaterial)
            ->with('success', 'Data Outstanding Material berhasil diperbarui.');
    }

    public function destroy(OutstandingMaterial $outstandingMaterial): RedirectResponse
    {
        $this->authorizeOutstandingMaterialManagement();

        $this->deleteStoredAttachments([
            $outstandingMaterial->attachment_path,
            $outstandingMaterial->packing_list_path,
            $outstandingMaterial->mtc_path,
        ]);

        $outstandingMaterial->forceFill([
            'attachment_path' => null,
            'packing_list_path' => null,
            'mtc_path' => null,
            'updated_by' => Auth::id(),
        ])->save();

        $outstandingMaterial->delete();

        return redirect()
            ->route('outstanding-materials.index')
            ->with('success', 'Data Outstanding Material berhasil dihapus.');
    }

    public function export(Request $request)
    {
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
        $this->authorizeOutstandingMaterialManagement();

        $request->validate([
            'import_file' => [
                'required',
                'file',
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

        $import = new OutstandingMaterialImport((int) Auth::id());

        try {
            Excel::import($import, $request->file('import_file'));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('outstanding-materials.index')
                ->withErrors([
                    'import_file' => 'File tidak dapat diproses: ' . $exception->getMessage(),
                ]);
        }

        $rows = $import->rows();
        $errors = $import->errors();
        $warnings = $import->warnings();

        if (count($rows) === 0) {
            $message = 'Import gagal. Tidak ada baris valid untuk disimpan.';
            if (count($errors) > 0) {
                $message .= ' ' . implode(' | ', array_slice($errors, 0, 3));
            }

            return redirect()
                ->route('outstanding-materials.index')
                ->withErrors(['import_file' => $message]);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                OutstandingMaterial::create($row);
            }
        });

        $redirect = redirect()
            ->route('outstanding-materials.index')
            ->with('success', count($rows) . ' data Outstanding Material berhasil diimport.');

        if (count($errors) > 0 || count($warnings) > 0) {
            $warning = count($errors) . ' baris dilewati karena tidak valid.';
            $preview = implode(' | ', array_slice($errors, 0, 3));

            if (count($errors) === 0) {
                $warning = 'Import berhasil dengan catatan.';
                $preview = '';
            }

            $warningPreview = implode(' | ', array_slice($warnings, 0, 3));
            $combinedPreview = trim($preview . ($preview !== '' && $warningPreview !== '' ? ' | ' : '') . $warningPreview);

            if ($combinedPreview !== '') {
                $warning .= ' ' . $combinedPreview;
            }

            if (count($errors) > 3) {
                $warning .= ' ...';
            }
            if (count($warnings) > 3) {
                $warning .= ' ...';
            }

            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function template()
    {
        $this->authorizeOutstandingMaterialManagement();

        return Excel::download(
            new OutstandingMaterialTemplateExport(),
            'template_import_outstanding_material.xlsx',
            ExcelFormat::XLSX
        );
    }

    public function invoiceIndex(): View
    {
        $this->authorizeOutstandingMaterialManagement();

        return view('outstanding_materials.invoice', [
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
        ]);
    }

    public function invoiceData(Request $request): JsonResponse
    {
        $this->authorizeOutstandingMaterialManagement();

        $baseQuery = $this->invoiceBaseQuery();
        $recordsTotal = $this->countGroupedQuery($baseQuery);

        $filteredQuery = clone $baseQuery;
        $search = trim((string) ($request->input('search.value') ?: $request->input('q', '')));

        if ($search !== '') {
            $filteredQuery->where(function (Builder $query) use ($search): void {
                $query->where('number_invoice', 'like', '%' . $search . '%')
                    ->orWhere('supplier', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('keterangan', 'like', '%' . $search . '%');
            });
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
            ->map(fn (object $invoice): array => $this->invoiceDataTableRow($invoice))
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
        $this->authorizeOutstandingMaterialManagement();
        $invoice = $request->query('invoice');
        
        $materials = OutstandingMaterial::where('number_invoice', $invoice)
            ->select('id', 'supplier', 'type', 'thickness', 'width', 'diameter', 'length', 'qty_pcs', 'est_qty_kg', 'status', 'keterangan')
            ->get();
            
        return response()->json($materials);
    }

    public function updateInvoiceFields(Request $request): JsonResponse
    {
        $this->authorizeOutstandingMaterialManagement();

        $data = $request->validate([
            'invoice' => 'required|string',
            'material_ids' => 'required|array',
            'material_ids.*' => 'integer|exists:outstanding_materials,id',
            'status' => ['nullable', 'string', Rule::in(OutstandingMaterial::statusOptions())],
            'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
        ]);

        $updates = ['updated_by' => Auth::id()];
        
        if (array_key_exists('keterangan', $data)) {
            $updates['keterangan'] = $data['keterangan'];
        }

        if (!empty($data['status'])) {
            $updates['status'] = $data['status'];
        }

        if (count($updates) > 1) { // more than just updated_by
            OutstandingMaterial::whereIn('id', $data['material_ids'])
                ->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => count($data['material_ids']) . ' material(s) updated successfully.'
        ]);
    }

    public function attachment(OutstandingMaterial $outstandingMaterial, ?string $type = null)
    {
        $path = $this->attachmentPathForType($outstandingMaterial, $type);

        if (!$this->isStoredAttachment($path)) {
            abort(404, 'File attachment tidak ditemukan.');
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $fileName = basename($path);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $fullPath = $disk->path($path);
        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

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
        $attachmentRules = 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png|max:10240';

        $data = $request->validate([
            'supplier' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'thickness' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'diameter' => 'nullable|numeric',
            'length' => 'nullable|string|max:255',
            'qty_pcs' => 'nullable|numeric',
            'est_qty_kg' => 'nullable|numeric',
            'number_invoice' => 'nullable|string|max:255',
            'status' => ['required', 'string', Rule::in(OutstandingMaterial::statusOptions())],
            'estimasi_eta_port' => 'nullable|date',
            'estimasi_eta_warehouse' => 'nullable|date',
            'estimasi_bulan_eta' => 'nullable|string|max:255',
            'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
            'estimasi_delay_eta_port' => 'nullable|date',
            'estimasi_delay_eta_warehouse' => 'nullable|date',
            'attachment' => $attachmentRules,
            'packing_list' => $attachmentRules,
            'mtc' => $attachmentRules,
        ], [], $this->validationAttributes());

        $data['attachment_path'] = $material?->attachment_path;
        $data['packing_list_path'] = $material?->packing_list_path;
        $data['mtc_path'] = $material?->mtc_path;

        if ($request->hasFile('packing_list')) {
            $path = $request->file('packing_list')->store(self::ATTACHMENT_DIRECTORY . '/packing-list', 'public');
            $data['packing_list_path'] = $path;
            $data['attachment_path'] = $path;
        }

        if ($request->hasFile('mtc')) {
            $data['mtc_path'] = $request->file('mtc')->store(self::ATTACHMENT_DIRECTORY . '/mtc', 'public');
        }

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store(self::ATTACHMENT_DIRECTORY, 'public');
            $data['attachment_path'] = $path;

            if (!$request->hasFile('packing_list')) {
                $data['packing_list_path'] = $path;
            }
        }

        unset($data['attachment']);
        unset($data['packing_list']);
        unset($data['mtc']);

        return $data;
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        foreach (['supplier', 'type', 'number_invoice', 'status', 'keterangan', 'estimasi_bulan_eta'] as $field) {
            if ($request->filled($field)) {
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

    private function dataTableRow(OutstandingMaterial $material): array
    {
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
            'packing_list' => $this->attachmentDisplay($material, 'packing_list_path', 'packing-list'),
            'mtc' => $this->attachmentDisplay($material, 'mtc_path', 'mtc'),
            'actions' => $this->actionButtons($material),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'suppliers' => $this->distinctValues('supplier'),
            'types' => $this->distinctValues('type'),
            'invoices' => $this->distinctValues('number_invoice'),
            'months' => $this->distinctValues('estimasi_bulan_eta'),
        ];
    }

    private function distinctValues(string $field): array
    {
        return OutstandingMaterial::query()
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->orderBy($field)
            ->pluck($field)
            ->all();
    }

    private function attachmentDisplay(OutstandingMaterial $material, string $field, string $type): string
    {
        $path = $material->{$field};

        if (!$path) {
            return '-';
        }

        if ($this->isStoredAttachment($path)) {
            return sprintf(
                '<a href="%s" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>',
                e(route('outstanding-materials.attachment', [
                    'outstandingMaterial' => $material,
                    'type' => $type,
                ]))
            );
        }

        return '<span class="text-muted" title="' . e($path) . '">' . e(str($path)->limit(28)) . '</span>';
    }

    private function actionButtons(OutstandingMaterial $material): string
    {
        $showUrl = route('outstanding-materials.show', $material);
        $manageButtons = '';

        if ($this->canManageOutstandingMaterials()) {
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
            <div class="om-actions">
                <a href="{$showUrl}" class="om-action-btn om-action-btn--detail" title="Detail" data-bs-toggle="tooltip">
                    <i class="bi bi-eye"></i>
                </a>
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

    private function summaryStats(): array
    {
        $counts = OutstandingMaterial::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'total' => array_sum($counts),
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
            ->select([
                'number_invoice',
                DB::raw('COUNT(*) as material_count'),
                DB::raw('MIN(supplier) as supplier_sample'),
                DB::raw('GROUP_CONCAT(DISTINCT status ORDER BY status SEPARATOR \',\') as status'),
                DB::raw('GROUP_CONCAT(DISTINCT keterangan ORDER BY keterangan SEPARATOR \',\') as keterangan'),
                DB::raw('MAX(estimasi_eta_warehouse) as latest_eta_warehouse'),
            ])
            ->whereNotNull('number_invoice')
            ->where('number_invoice', '!=', '')
            ->groupBy('number_invoice');
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

    private function invoiceDataTableRow(object $invoice): array
    {
        $invoiceValue = (string) $invoice->number_invoice;
        $formId = 'invoice-update-form-' . md5($invoiceValue);

        $statusHtml = collect(explode(',', (string) $invoice->status))->filter()->map(fn($s) => '<span class="om-badge ' . $this->statusBadgeClass(trim($s)) . '">' . e(trim($s)) . '</span>')->implode(' ');
        $keteranganHtml = collect(explode(',', (string) $invoice->keterangan))->filter()->map(fn($k) => '<span class="om-badge ' . $this->keteranganBadgeClass(trim($k)) . '">' . e(trim($k)) . '</span>')->implode(' ');

        return [
            'number_invoice' => e($invoice->number_invoice ?: '-'),
            'supplier' => e($invoice->supplier_sample ?: '-'),
            'material_count' => number_format((int) $invoice->material_count),
            'keterangan' => $keteranganHtml ?: '<span class="om-badge">-</span>',
            'status' => $statusHtml ?: '<span class="om-badge">-</span>',
            'latest_eta_warehouse' => $this->formatDate($invoice->latest_eta_warehouse),
            'actions' => $this->invoiceActionForm($formId, $invoiceValue),
        ];
    }

    private function invoiceStatusSelect(string $formId, ?string $currentStatus, ?string $statusSummary): string
    {
        $formId = e($formId);
        $title = $statusSummary ? ' title="Status saat ini: ' . e($statusSummary) . '"' : '';
        $options = '<option value="">Pilih Status</option>';

        foreach (OutstandingMaterial::statusOptions() as $option) {
            $selected = $currentStatus === $option ? ' selected' : '';
            $escapedOption = e($option);
            $options .= '<option value="' . $escapedOption . '"' . $selected . '>' . $escapedOption . '</option>';
        }

        return <<<HTML
            <select name="status" form="{$formId}" class="form-select form-select-sm"{$title}>
                {$options}
            </select>
        HTML;
    }

    private function invoiceKeteranganSelect(string $formId, ?string $currentKeterangan): string
    {
        $formId = e($formId);
        $options = '<option value="">Pilih Keterangan</option>';

        foreach (OutstandingMaterial::keteranganOptions() as $option) {
            $selected = $currentKeterangan === $option ? ' selected' : '';
            $escapedOption = e($option);
            $options .= '<option value="' . $escapedOption . '"' . $selected . '>' . $escapedOption . '</option>';
        }

        return <<<HTML
            <select name="keterangan" form="{$formId}" class="form-select form-select-sm">
                {$options}
            </select>
        HTML;
    }

    private function statusBadgeClass(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'production' => 'om-badge--production',
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

    private function invoiceActionForm(string $formId, string $invoice): string
    {
        $invoiceValue = e($invoice);

        return <<<HTML
            <button type="button" class="btn btn-sm btn-primary js-open-invoice-modal" data-invoice="{$invoiceValue}">
                <i class="bi bi-pencil-square me-1"></i> Update Materials
            </button>
        HTML;
    }

    private function attachmentPathForType(OutstandingMaterial $material, ?string $type): ?string
    {
        return match ($type) {
            'packing-list' => $material->packing_list_path ?: $material->attachment_path,
            'mtc' => $material->mtc_path,
            default => $material->attachment_path ?: $material->packing_list_path,
        };
    }

    private function isStoredAttachment(?string $path): bool
    {
        if (!$path || !str_starts_with($path, self::ATTACHMENT_DIRECTORY . '/')) {
            return false;
        }

        return Storage::disk('public')->exists($path);
    }

    private function deleteStoredAttachment(?string $path): void
    {
        if ($this->isStoredAttachment($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteStoredAttachments(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            $this->deleteStoredAttachment((string) $path);
        }
    }

    private function deleteReplacedStoredAttachments(array $oldPaths, array $newPaths): void
    {
        $newPaths = array_unique(array_filter($newPaths));

        foreach (array_unique(array_filter($oldPaths)) as $oldPath) {
            if (!in_array($oldPath, $newPaths, true)) {
                $this->deleteStoredAttachment((string) $oldPath);
            }
        }
    }

    private function syncInvoiceFields(?string $invoice, array $updates): int
    {
        $invoice = trim((string) $invoice);

        $updates = array_intersect_key($updates, array_flip(['status', 'keterangan']));

        if ($invoice === '' || empty($updates)) {
            return 0;
        }

        return DB::table('outstanding_materials')
            ->where('number_invoice', $invoice)
            ->whereNull('deleted_at')
            ->update($updates);
    }

    private function canManageOutstandingMaterials(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if (isset($user->role_id) && (int) $user->role_id === 1) {
            return true;
        }

        return in_array(strtoupper(trim((string) $user->name)), self::MANAGER_NAMES, true);
    }

    private function authorizeOutstandingMaterialManagement(): void
    {
        abort_unless($this->canManageOutstandingMaterials(), 403, 'Unauthorized');
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
            'attachment' => 'DOKUMEN PACKING LIST DAN MTC',
            'packing_list' => 'Packing List',
            'mtc' => 'MTC',
        ];
    }
}
