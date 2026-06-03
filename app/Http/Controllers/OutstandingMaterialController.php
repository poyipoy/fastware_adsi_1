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

    public function index(): View
    {
        return view('outstanding_materials.index', [
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
            'filterOptions' => $this->filterOptions(),
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
        return view('outstanding_materials.form', [
            'material' => new OutstandingMaterial(),
            'isEdit' => false,
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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
        ]);
    }

    public function edit(OutstandingMaterial $outstandingMaterial): View
    {
        return view('outstanding_materials.form', [
            'material' => $outstandingMaterial,
            'isEdit' => true,
            'statusOptions' => OutstandingMaterial::statusOptions(),
            'keteranganOptions' => OutstandingMaterial::keteranganOptions(),
        ]);
    }

    public function update(Request $request, OutstandingMaterial $outstandingMaterial): RedirectResponse
    {
        $oldAttachment = $outstandingMaterial->attachment_path;
        $data = $this->validatedPayload($request, $outstandingMaterial);
        $data['updated_by'] = Auth::id();

        $outstandingMaterial->update($data);

        if ($request->hasFile('attachment') && $oldAttachment !== $outstandingMaterial->attachment_path) {
            $this->deleteStoredAttachment($oldAttachment);
        }

        return redirect()
            ->route('outstanding-materials.show', $outstandingMaterial)
            ->with('success', 'Data Outstanding Material berhasil diperbarui.');
    }

    public function destroy(OutstandingMaterial $outstandingMaterial): RedirectResponse
    {
        $oldAttachment = $outstandingMaterial->attachment_path;

        if ($this->isStoredAttachment($oldAttachment)) {
            $this->deleteStoredAttachment($oldAttachment);
            $outstandingMaterial->forceFill([
                'attachment_path' => null,
                'updated_by' => Auth::id(),
            ])->save();
        }

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
        return Excel::download(
            new OutstandingMaterialTemplateExport(),
            'template_import_outstanding_material.xlsx',
            ExcelFormat::XLSX
        );
    }

    public function attachment(OutstandingMaterial $outstandingMaterial)
    {
        $path = $outstandingMaterial->attachment_path;

        if (!$this->isStoredAttachment($path)) {
            abort(404, 'File attachment tidak ditemukan.');
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $fileName = basename($path);
        $fullPath = Storage::disk('public')->path($path);
        $mimeType = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';

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
            'number_invoice' => 'nullable|string|max:255',
            'status' => ['required', 'string', Rule::in(OutstandingMaterial::statusOptions())],
            'estimasi_eta_port' => 'nullable|date',
            'estimasi_eta_warehouse' => 'nullable|date',
            'estimasi_bulan_eta' => 'nullable|string|max:255',
            'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
            'estimasi_delay_eta_port' => 'nullable|date',
            'estimasi_delay_eta_warehouse' => 'nullable|date',
            'attachment' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png|max:10240',
        ], [], $this->validationAttributes());

        $data['attachment_path'] = $material?->attachment_path;

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store(self::ATTACHMENT_DIRECTORY, 'public');
        }

        unset($data['attachment']);

        return $data;
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        foreach (['supplier', 'type', 'status', 'keterangan', 'estimasi_bulan_eta'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $etaPortFrom = $this->filterDate($request->input('eta_port_from'));
        $etaPortTo = $this->filterDate($request->input('eta_port_to'));
        $etaWarehouseFrom = $this->filterDate($request->input('eta_warehouse_from'));
        $etaWarehouseTo = $this->filterDate($request->input('eta_warehouse_to'));

        if ($etaPortFrom) {
            $query->whereDate('estimasi_eta_port', '>=', $etaPortFrom);
        }

        if ($etaPortTo) {
            $query->whereDate('estimasi_eta_port', '<=', $etaPortTo);
        }

        if ($etaWarehouseFrom) {
            $query->whereDate('estimasi_eta_warehouse', '>=', $etaWarehouseFrom);
        }

        if ($etaWarehouseTo) {
            $query->whereDate('estimasi_eta_warehouse', '<=', $etaWarehouseTo);
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
                ->orWhere('attachment_path', 'like', '%' . $search . '%');
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
            17 => 'attachment_path',
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
            'attachment' => $this->attachmentDisplay($material),
            'actions' => $this->actionButtons($material),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'suppliers' => $this->distinctValues('supplier'),
            'types' => $this->distinctValues('type'),
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

    private function attachmentDisplay(OutstandingMaterial $material): string
    {
        $path = $material->attachment_path;

        if (!$path) {
            return '-';
        }

        if ($this->isStoredAttachment($path)) {
            return sprintf(
                '<a href="%s" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>',
                e(route('outstanding-materials.attachment', $material))
            );
        }

        return '<span class="text-muted" title="' . e($path) . '">' . e(str($path)->limit(28)) . '</span>';
    }

    private function actionButtons(OutstandingMaterial $material): string
    {
        $showUrl = route('outstanding-materials.show', $material);
        $editUrl = route('outstanding-materials.edit', $material);
        $deleteUrl = route('outstanding-materials.destroy', $material);
        $supplier = e($material->supplier ?: '-');
        $type = e($material->type ?: '-');
        $invoice = e($material->number_invoice ?: '-');
        $csrf = csrf_field();
        $method = method_field('DELETE');

        return <<<HTML
            <div class="d-flex gap-1 justify-content-center">
                <a href="{$showUrl}" class="btn btn-sm btn-outline-info">Detail</a>
                <a href="{$editUrl}" class="btn btn-sm btn-outline-warning">Edit</a>
                <form action="{$deleteUrl}" method="POST" class="d-inline js-outstanding-delete-form" data-supplier="{$supplier}" data-type="{$type}" data-invoice="{$invoice}">
                    {$csrf}
                    {$method}
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        HTML;
    }

    private function statusBadge(?string $status): string
    {
        if (!$status) {
            return '-';
        }

        $class = match ($status) {
            OutstandingMaterial::STATUS_RECEIVED => 'success',
            OutstandingMaterial::STATUS_ON_PRODUCTION => 'warning',
            OutstandingMaterial::STATUS_ON_SHIPMENT => 'primary',
            default => 'secondary',
        };

        return '<span class="badge bg-' . $class . '">' . e($status) . '</span>';
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
        ];
    }
}
