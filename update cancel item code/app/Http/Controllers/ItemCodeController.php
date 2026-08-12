<?php

namespace App\Http\Controllers;

use App\Exports\ItemCodeExport;
use App\Exports\ItemCodeImportTemplateExport;
use App\Imports\ItemCodeImport;
use App\Models\ItemCode;
use App\Models\User;
use App\Services\ItemCodeCancellationService;
use App\Services\ItemCodeHistoryService;
use App\Enums\ProcurementMenuAccessGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class ItemCodeController extends Controller
{
    private const ATTACHMENT_DIRECTORY = 'item-code-attachments';

    public function index(Request $request): View
    {
        $this->ensureFormUser();

        $activeTab = $this->resolveActiveTab($request->query('tab'));
        $perPage = $this->resolvePerPage($request->query('per_page'));
        $filters = $this->resolveFilters($request);
        $sorting = $this->resolveSorting($request);
        $statsByType = $this->buildStatsByType();

        $itemsQuery = ItemCode::with(['creator', 'approver', 'approver2', 'finisher', 'canceller'])
            ->withCount([
                'histories as rejected_histories_count' => fn(Builder $builder) => $builder->where('action', ItemCodeHistoryService::ACTION_REJECTED),
            ])
            ->where('type', $activeTab);

        $this->applyFilters($itemsQuery, $filters);
        $itemsTotal = (clone $itemsQuery)->count();
        $this->applySorting($itemsQuery, $sorting);

        $items = $itemsQuery
            ->simplePaginate($perPage)
            ->appends($request->query());

        return view('item_code.form-item-code.index', [
            'itemsNewProduct' => $activeTab === 'new_product' ? $items : collect(),
            'itemsUpdatePrice' => $activeTab === 'update_price' ? $items : collect(),
            'activeTab' => $activeTab,
            'perPage' => $perPage,
            'filters' => $filters,
            'sorting' => $sorting,
            'itemsTotal' => $itemsTotal,
            'currencyOptions' => ItemCode::currencyList(),
            'statsByType' => $statsByType,
            'canCancel' => ProcurementMenuAccessGroup::ITEM_CODE_CANCELLER->hasAccess($this->currentUserName()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureFormUser();

        $data = $this->validatedPayload($request);
        $type = $data['type'];

        DB::transaction(function () use ($data): void {
            $data['status'] = ItemCode::STATUS_DRAFT;
            $data['created_by'] = Auth::id();

            $itemCode = ItemCode::create($data);

            $this->historyService()->record(
                $itemCode,
                ItemCodeHistoryService::ACTION_CREATED,
                'Data item code dibuat sebagai Draft.',
                null,
                $itemCode->status,
                []
            );
        });

        return redirect()
            ->route('item-code.form', ['tab' => $type])
            ->with('success', 'Data item code berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->ensureFormUser();

        $itemCode = ItemCode::findOrFail($id);

        abort_if($itemCode->status !== ItemCode::STATUS_DRAFT, 403, 'Data hanya bisa diubah saat status Draft');

        $before = $this->snapshotItemCode($itemCode);
        $oldAttachment = $itemCode->attachment;
        $data = $this->validatedPayload($request, $itemCode);
        $itemCode->update($data);

        if ($oldAttachment && $oldAttachment !== $itemCode->attachment) {
            Storage::disk('local')->delete($oldAttachment);
        }

        $itemCode->refresh();

        $this->historyService()->record(
            $itemCode,
            ItemCodeHistoryService::ACTION_UPDATED,
            'Data item code diperbarui.',
            $before['status'] ?? null,
            $itemCode->status,
            $this->buildUpdateChangeSet($before, $this->snapshotItemCode($itemCode))
        );

        return redirect()
            ->route('item-code.form', ['tab' => $itemCode->type])
            ->with('success', 'Data item code berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->ensureFormUser();

        $itemCode = ItemCode::findOrFail($id);

        abort_if($itemCode->status !== ItemCode::STATUS_DRAFT, 403, 'Data hanya bisa dihapus saat status Draft');

        $tab = $itemCode->type;
        $before = $this->snapshotItemCode($itemCode);

        $this->historyService()->record(
            $itemCode,
            ItemCodeHistoryService::ACTION_DELETED,
            'Data item code dihapus (soft delete).',
            $itemCode->status,
            $itemCode->status,
            $this->buildDeletionChangeSet($before)
        );

        $itemCode->delete();

        return redirect()
            ->route('item-code.form', ['tab' => $tab])
            ->with('success', 'Data item code berhasil dihapus.');
    }

    public function submit(int $id): RedirectResponse
    {
        $this->ensureFormUser();

        $itemCode = ItemCode::findOrFail($id);

        abort_if(!$itemCode->canTransitionTo(ItemCode::STATUS_SUBMITTED), 403, 'Transisi status tidak valid: hanya Draft yang bisa disubmit.');

        $statusFrom = $itemCode->status;
        DB::transaction(function () use ($itemCode, $statusFrom): void {
            $itemCode->update($this->buildSubmissionPayload($itemCode));

            $itemCode->refresh();

            $this->historyService()->record(
                $itemCode,
                ItemCodeHistoryService::ACTION_SUBMITTED,
                'Data item code disubmit untuk approval.',
                $statusFrom,
                $itemCode->status,
                $this->buildStatusChangeSet($statusFrom, $itemCode->status)
            );
        });

        return redirect()
            ->route('item-code.form', ['tab' => $itemCode->type])
            ->with('success', 'Data item code berhasil disubmit.');
    }

    public function submitAll(Request $request): RedirectResponse
    {
        $this->ensureFormUser();

        $validated = $request->validate([
            'tab' => 'required|in:' . implode(',', ItemCode::typeList()),
            'selected_ids' => 'nullable|array',
            'selected_ids.*' => 'integer',
        ]);

        $tab = $this->resolveActiveTab($validated['tab']);
        $selectedIds = array_values(array_unique(array_map('intval', $validated['selected_ids'] ?? [])));

        if ($selectedIds === []) {
            return redirect()
                ->route('item-code.form', ['tab' => $tab])
                ->with('warning', 'Pilih minimal 1 data Draft untuk disubmit.');
        }

        $draftItems = ItemCode::query()
            ->whereIn('id', $selectedIds)
            ->where('type', $tab)
            ->where('status', ItemCode::STATUS_DRAFT)
            ->orderBy('id')
            ->get();

        if ($draftItems->isEmpty()) {
            return redirect()
                ->route('item-code.form', ['tab' => $tab])
                ->with('warning', 'Pilih minimal 1 data Draft yang valid untuk disubmit.');
        }

        DB::transaction(function () use ($draftItems) {
            foreach ($draftItems as $itemCode) {
                $statusFrom = $itemCode->status;

                if (!$itemCode->canTransitionTo(ItemCode::STATUS_SUBMITTED)) {
                    continue;
                }

                $itemCode->update($this->buildSubmissionPayload($itemCode));

                $itemCode->refresh();

                $this->historyService()->record(
                    $itemCode,
                    ItemCodeHistoryService::ACTION_SUBMITTED,
                    'Data item code disubmit untuk approval.',
                    $statusFrom,
                    $itemCode->status,
                    $this->buildStatusChangeSet($statusFrom, $itemCode->status)
                );
            }
        });

        return redirect()
            ->route('item-code.form', ['tab' => $tab])
            ->with('success', $draftItems->count() . ' data Draft berhasil disubmit.');
    }

    public function cancel(int $id, ItemCodeCancellationService $cancellationService): RedirectResponse
    {
        $this->ensureFormUser();

        $itemCode = $cancellationService->cancel($id, Auth::user());

        return redirect()
            ->route('item-code.form', ['tab' => $itemCode->type])
            ->with('success', 'Data item code berhasil dibatalkan secara permanen.');
    }

    public function show(int $id): View
    {
        $itemCode = ItemCode::with(['creator', 'approver', 'approver2', 'finisher', 'canceller'])->findOrFail($id);

        return view('item_code.show', compact('itemCode'));
    }

    public function history(int $id): JsonResponse
    {
        $itemCode = ItemCode::withTrashed()->findOrFail($id);
        $this->ensureHistoryAccess($itemCode, true);

        $historyData = $itemCode->histories()
            ->with('actor')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($history) {
                $statusValue = $history->status_to ?: $history->status_from;

                return [
                    'keterangan' => $this->buildHistoryDescription((string) $history->summary, (array) ($history->change_set ?? [])),
                    'status' => $statusValue ? $this->statusLabel((string) $statusValue) : '-',
                    'modified_at' => $history->actor_name ?: optional($history->actor)->name ?: '-',
                    'created_at' => $history->created_at ? $history->created_at->format('d-m-Y H:i:s') : '',
                ];
            });

        return response()->json($historyData);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->ensureFormUser();

        $validated = $request->validate([
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
            'import_type' => 'required|in:' . implode(',', ItemCode::typeList()),
            'tab' => 'nullable|in:' . implode(',', ItemCode::typeList()),
        ]);

        $tab = $this->resolveActiveTab($validated['tab'] ?? $validated['import_type']);
        $import = new ItemCodeImport((int) Auth::id(), $validated['import_type']);

        try {
            Excel::import($import, $request->file('import_file'));
        } catch (\Throwable $exception) {
            \Log::error('ItemCode import error', [
                'exception' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return redirect()
                ->route('item-code.form', ['tab' => $tab])
                ->withErrors([
                    'import_file' => 'File tidak dapat diproses: ' . $exception->getMessage(),
                ]);
        }

        $rows = $import->rows();
        $errors = $import->errors();

        if (count($rows) === 0) {
            $message = 'Import gagal. Tidak ada baris valid untuk disimpan.';

            if (count($errors) > 0) {
                $message .= ' ' . implode(' | ', array_slice($errors, 0, 3));
            }

            return redirect()
                ->route('item-code.form', ['tab' => $tab])
                ->withErrors(['import_file' => $message]);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $row['nomor_pengajuan'] = $this->normalizeNomorPengajuanInput($row['nomor_pengajuan'] ?? null)
                    ?: $this->generateNomorPengajuan((string) ($row['type'] ?? ItemCode::TYPE_NEW_PRODUCT));
                $row['product_code'] = trim((string) ($row['product_code'] ?? ''));

                $this->ensureNomorPengajuanProductCodeUnique(
                    (string) $row['nomor_pengajuan'],
                    $row['product_code']
                );

                $row['attachment'] = $row['attachment'] ?? null;
                $itemCode = ItemCode::create($row);

                $this->historyService()->record(
                    $itemCode,
                    ItemCodeHistoryService::ACTION_IMPORTED,
                    'Data item code diimport sebagai Draft.',
                    null,
                    $itemCode->status,
                    $this->buildCreationChangeSet($itemCode)
                );
            }
        });

        $successMessage = count($rows) . ' data berhasil diimport sebagai Draft.';
        $redirect = redirect()
            ->route('item-code.form', ['tab' => $tab])
            ->with('success', $successMessage);

        if (count($errors) > 0) {
            $warning = count($errors) . ' baris dilewati karena tidak valid.';
            $preview = implode(' | ', array_slice($errors, 0, 3));

            if ($preview !== '') {
                $warning .= ' ' . $preview;
            }

            if (count($errors) > 3) {
                $warning .= ' ...';
            }

            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function export(Request $request)
    {
        $this->ensureFormUser();

        $tab = $this->resolveActiveTab($request->query('tab'));
        $filters = $this->resolveFilters($request);
        $sorting = $this->resolveSorting($request);

        $itemsQuery = ItemCode::with(['creator', 'approver', 'finisher'])
            ->withCount([
                'histories as rejected_histories_count' => fn(Builder $builder) => $builder->where('action', ItemCodeHistoryService::ACTION_REJECTED),
            ])
            ->where('type', $tab);

        $this->applyFilters($itemsQuery, $filters);
        $this->applySorting($itemsQuery, $sorting);

        if ($request->has('selected_ids') && is_array($request->input('selected_ids'))) {
            $itemsQuery->whereIn('id', $request->input('selected_ids'));
        }

        $items = $itemsQuery
            ->get();

        $fileName = sprintf(
            'item_code_form_%s_%s.xlsx',
            $tab,
            now()->format('Ymd_His')
        );

        return Excel::download(
            new ItemCodeExport($items),
            $fileName,
            ExcelFormat::XLSX
        );
    }

    public function importTemplate(Request $request)
    {
        $this->ensureFormUser();

        $type = $this->resolveActiveTab($request->query('type'));
        $fileName = sprintf('item_code_import_template_%s.xlsx', $type);

        return Excel::download(
            new ItemCodeImportTemplateExport($type),
            $fileName,
            ExcelFormat::XLSX
        );
    }

    public function nextNomor(Request $request): JsonResponse
    {
        $this->ensureFormUser();

        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', ItemCode::typeList()),
        ]);

        $nomorPengajuan = DB::transaction(function () use ($validated): string {
            return $this->generateNomorPengajuan($validated['type']);
        });

        return response()->json([
            'nomor_pengajuan' => $nomorPengajuan,
        ]);
    }

    public function attachment(int $id)
    {
        if (!ProcurementMenuAccessGroup::ITEM_CODE_ACCESS->hasAccess($this->currentUserName())) {
            abort(403, 'Unauthorized');
        }

        $itemCode = ItemCode::withTrashed()->findOrFail($id);

        if (!$itemCode->attachment) {
            abort(404, 'File attachment tidak tersedia.');
        }

        if (!Storage::disk('local')->exists($itemCode->attachment)) {
            abort(404, 'File attachment tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($itemCode->attachment);
        $extension = strtolower((string) pathinfo($itemCode->attachment, PATHINFO_EXTENSION));
        $fileName = basename($itemCode->attachment);
        $mimeType = $this->resolveAttachmentMimeType($extension);

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        }

        return response()->download($fullPath, $fileName, [
            'Content-Type' => $mimeType,
        ]);
    }

    private function validatedPayload(Request $request, ?ItemCode $existingItem = null): array
    {
        $data = $request->validate([
            'nomor_pengajuan' => 'nullable|string|max:255',
            'type' => 'required|in:' . implode(',', ItemCode::typeList()),
            'category' => 'required|in:Material,Non Material',
            'supplier' => 'required|string|max:255',
            'product_code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'qty' => 'required|integer|gt:0',
            'unit' => 'required|string|max:50',
            'currency' => 'required|' . ItemCode::currencyValidationRule(),
            'tanggal' => 'required|date',
            'tanggal_lama' => 'nullable|date|required_if:type,update_price',
            'price_per_pcs' => 'required|numeric|min:0',
            'tanggal_harga_baru' => 'nullable|date|required_if:type,update_price',
            'harga_baru' => 'required_if:type,update_price|numeric|min:0',
            'reason_new_price' => 'nullable|string|max:2000|required_if:type,update_price',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx|max:5120',
        ]);

        $data['product_code'] = trim((string) $data['product_code']);
        $data['nomor_pengajuan'] = $this->normalizeNomorPengajuanInput($data['nomor_pengajuan'] ?? null);

        if ($data['nomor_pengajuan'] === null) {
            $data['nomor_pengajuan'] = $existingItem?->nomor_pengajuan
                ?: $this->generateNomorPengajuan($data['type']);
        }

        $this->ensureNomorPengajuanProductCodeUnique(
            $data['nomor_pengajuan'],
            $data['product_code'],
            $existingItem?->id
        );

        $data['price_per_pcs'] = (float) $data['price_per_pcs'];
        if (array_key_exists('harga_baru', $data) && $data['harga_baru'] !== null) {
            $data['harga_baru'] = (float) $data['harga_baru'];
        }

        $currentAttachment = $existingItem?->attachment;

        if ($request->hasFile('attachment')) {
            $currentAttachment = $this->storeAttachmentFile($request->file('attachment'));
        }

        $data['attachment'] = $currentAttachment;

        if ($data['type'] === ItemCode::TYPE_NEW_PRODUCT) {
            $data['tanggal_lama'] = null;
            $data['harga_baru'] = null;
            $data['tanggal_harga_baru'] = null;
            $data['selisih'] = null;
        } else {
            $data['selisih'] = $this->calculateSelisih($data['price_per_pcs'], $data['harga_baru']);
        }

        return $data;
    }

    private function calculateSelisih(mixed $hargaLama, mixed $hargaBaru): ?float
    {
        if ($hargaLama === null || $hargaBaru === null) {
            return null;
        }

        return (float) $hargaLama - (float) $hargaBaru;
    }

    private function resolveFilters(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $status = strtolower(trim((string) $request->query('status', '')));
        $startDate = $this->normalizeFilterDate($request->query('start_date'));
        $endDate = $this->normalizeFilterDate($request->query('end_date'));

        if (!in_array($status, ItemCode::statusList(), true)) {
            $status = null;
        }

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'q' => $q !== '' ? $q : null,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function resolvePerPage(mixed $perPage): int
    {
        $allowed = [10, 20, 50, 100, 500];
        $value = is_numeric($perPage) ? (int) $perPage : 20;

        return in_array($value, $allowed, true) ? $value : 20;
    }

    private function resolveSorting(Request $request): array
    {
        $sort = $this->normalizeSingleSortingInput($request->query('sort'));
        $direction = $this->normalizeSingleSortingInput($request->query('direction'));
        $sortableColumns = $this->sortableColumns();

        if ($sort === '' || !array_key_exists($sort, $sortableColumns)) {
            return [
                'sort' => null,
                'direction' => 'desc',
                'column' => 'id',
            ];
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
            'column' => $sortableColumns[$sort],
        ];
    }

    private function normalizeSingleSortingInput(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return strtolower(trim((string) $value));
    }

    private function sortableColumns(): array
    {
        return [
            'no' => 'id',
            'nomor_pengajuan' => 'nomor_pengajuan',
            'tanggal' => 'tanggal',
            'nama' => 'creator_name',
            'material' => 'category',
            'category' => 'category',
            'supplier' => 'supplier',
            'item_code' => 'product_code',
            'item_name' => 'description',
            'qty' => 'qty',
            'unit' => 'unit',
            'currency' => 'currency',
            'price' => 'price_per_pcs',
            'current_price' => 'price_per_pcs',
            'new_price' => 'harga_baru',
            'effective_date_current' => 'tanggal_lama',
            'effective_date_new' => 'tanggal_harga_baru',
            'reason' => 'reason_new_price',
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('tanggal', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('tanggal', '<=', $filters['end_date']);
        }

        if (!empty($filters['q'])) {
            $keyword = $filters['q'];

            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('nomor_pengajuan', 'like', '%' . $keyword . '%')
                    ->orWhere('supplier', 'like', '%' . $keyword . '%')
                    ->orWhere('product_code', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%')
                    ->orWhere('category', 'like', '%' . $keyword . '%')
                    ->orWhere('unit', 'like', '%' . $keyword . '%')
                    ->orWhere('reason_new_price', 'like', '%' . $keyword . '%')
                    ->orWhereHas('creator', function (Builder $creatorQuery) use ($keyword) {
                        $creatorQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }
    }

    private function applySorting(Builder $query, array $sorting): void
    {
        $sort = $sorting['sort'] ?? null;
        $column = $sorting['column'] ?? 'id';
        $direction = $sorting['direction'] ?? 'desc';

        if ($sort === null) {
            $query->orderByDesc('id');
            return;
        }

        if ($column === 'creator_name') {
            $query->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'item_codes.created_by')
                    ->limit(1),
                $direction
            )->orderByDesc('id');

            return;
        }

        $query->orderBy($column, $direction)
            ->orderByDesc('id');
    }

    private function buildStatsByType(): array
    {
        $stats = [
            'new_product' => [
                'total' => 0,
                'draft' => 0,
                'submitted' => 0,
                'approved_1' => 0,
                'approved_2' => 0,
                'finished' => 0,
                'cancelled' => 0,
            ],
            'update_price' => [
                'total' => 0,
                'draft' => 0,
                'submitted' => 0,
                'approved_1' => 0,
                'approved_2' => 0,
                'finished' => 0,
                'cancelled' => 0,
            ],
        ];

        $rows = ItemCode::query()
            ->select('type', 'status', DB::raw('count(*) as aggregate'))
            ->groupBy('type', 'status')
            ->get();

        foreach ($rows as $row) {
            $type = (string) $row->type;
            $status = (string) $row->status;
            $count = (int) $row->aggregate;

            if (!isset($stats[$type]) || !array_key_exists($status, $stats[$type])) {
                continue;
            }

            $stats[$type][$status] = $count;
            $stats[$type]['total'] += $count;
        }

        return $stats;
    }

    private function buildOwnDraftCounts(int $userId): array
    {
        $counts = [
            'new_product' => 0,
            'update_price' => 0,
        ];

        $rows = ItemCode::query()
            ->select('type', DB::raw('count(*) as aggregate'))
            ->where('created_by', $userId)
            ->where('status', ItemCode::STATUS_DRAFT)
            ->groupBy('type')
            ->get();

        foreach ($rows as $row) {
            $type = (string) $row->type;

            if (!array_key_exists($type, $counts)) {
                continue;
            }

            $counts[$type] = (int) $row->aggregate;
        }

        return $counts;
    }

    private function ensureFormUser(): void
    {
        if (!ProcurementMenuAccessGroup::ITEM_CODE_FORM->hasAccess($this->currentUserName())) {
            abort(403, 'Unauthorized');
        }
    }

    private function ensureHistoryAccess(ItemCode $itemCode, bool $json = false): void
    {
        $isOwner = (int) $itemCode->created_by === (int) Auth::id();
        $hasApprovalAccess = ProcurementMenuAccessGroup::ITEM_CODE_APPROVAL->hasAccess($this->currentUserName());
        $hasFormAccess = ProcurementMenuAccessGroup::ITEM_CODE_FORM->hasAccess($this->currentUserName());

        if ($isOwner || $hasApprovalAccess || $hasFormAccess) {
            return;
        }

        if ($json) {
            abort(response()->json(['message' => 'Anda tidak memiliki akses ke histori item ini.'], 403));
        }

        abort(403, 'Anda tidak memiliki akses ke histori item ini.');
    }

    private function statusLabel(string $status): string
    {
        return match (strtolower($status)) {
            ItemCode::STATUS_DRAFT => 'Draft',
            ItemCode::STATUS_SUBMITTED => 'Submitted',
            ItemCode::STATUS_APPROVED_1 => 'Approved 1',
            ItemCode::STATUS_APPROVED_2 => 'Approved 2',
            ItemCode::STATUS_FINISHED => 'Finished',
            ItemCode::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($status),
        };
    }

    private function historyService(): ItemCodeHistoryService
    {
        return app(ItemCodeHistoryService::class);
    }

    private function currentUserName(): string
    {
        return (string) Auth::user()->getAttribute('name');
    }

    private function historyFieldLabels(): array
    {
        return [
            'nomor_pengajuan' => 'Nomor Pengajuan',
            'type' => 'Jenis',
            'category' => 'Category',
            'supplier' => 'Supplier',
            'product_code' => 'Product Code',
            'description' => 'Description',
            'qty' => 'Qty',
            'unit' => 'Unit',
            'price_per_pcs' => 'Current Price',
            'currency' => 'Currency',
            'tanggal' => 'Tanggal',
            'tanggal_lama' => 'Effective Date (Current Price)',
            'harga_baru' => 'New Price',
            'reason_new_price' => 'Reason',
            'attachment' => 'Attachment',
            'selisih' => 'Selisih',
            'tanggal_harga_baru' => 'Effective Date (New Price)',
            'status' => 'Status',
        ];
    }

    private function snapshotItemCode(ItemCode $itemCode): array
    {
        return [
            'nomor_pengajuan' => $this->normalizeNullableString($itemCode->nomor_pengajuan),
            'type' => $this->normalizeNullableString($itemCode->type),
            'category' => $this->normalizeNullableString($itemCode->category),
            'supplier' => $this->normalizeNullableString($itemCode->supplier),
            'product_code' => $this->normalizeNullableString($itemCode->product_code),
            'description' => $this->normalizeNullableString($itemCode->description),
            'qty' => $this->normalizeDecimal($itemCode->qty),
            'unit' => $this->normalizeNullableString($itemCode->unit),
            'price_per_pcs' => $this->normalizeDecimal($itemCode->price_per_pcs),
            'currency' => $this->normalizeNullableString($itemCode->currency),
            'tanggal' => $this->normalizeDateValue($itemCode->tanggal),
            'tanggal_lama' => $this->normalizeDateValue($itemCode->tanggal_lama),
            'harga_baru' => $this->normalizeDecimal($itemCode->harga_baru),
            'reason_new_price' => $this->normalizeNullableString($itemCode->reason_new_price),
            'attachment' => $this->normalizeNullableString($itemCode->attachment),
            'selisih' => $this->normalizeDecimal($itemCode->selisih),
            'tanggal_harga_baru' => $this->normalizeDateValue($itemCode->tanggal_harga_baru),
            'status' => $this->normalizeNullableString($itemCode->status),
        ];
    }

    private function buildCreationChangeSet(ItemCode $itemCode): array
    {
        $snapshot = $this->snapshotItemCode($itemCode);
        $changes = [];

        foreach ($this->historyFieldLabels() as $field => $label) {
            $newValue = $snapshot[$field] ?? null;

            if ($newValue === null || $newValue === '') {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'old' => null,
                'new' => $this->formatHistoryFieldValue($field, $newValue),
            ];
        }

        return $changes;
    }

    private function buildUpdateChangeSet(array $before, array $after): array
    {
        $changes = [];

        foreach ($this->historyFieldLabels() as $field => $label) {
            $oldValue = $before[$field] ?? null;
            $newValue = $after[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'old' => $this->formatHistoryFieldValue($field, $oldValue),
                'new' => $this->formatHistoryFieldValue($field, $newValue),
            ];
        }

        return $changes;
    }

    private function buildDeletionChangeSet(array $before): array
    {
        $changes = [];

        foreach ($this->historyFieldLabels() as $field => $label) {
            $oldValue = $before[$field] ?? null;

            if ($oldValue === null || $oldValue === '') {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'old' => $this->formatHistoryFieldValue($field, $oldValue),
                'new' => '-',
            ];
        }

        return $changes;
    }

    private function buildStatusChangeSet(?string $statusFrom, ?string $statusTo): array
    {
        if ($statusFrom === $statusTo) {
            return [];
        }

        return [
            [
                'field' => 'status',
                'label' => 'Status',
                'old' => $this->formatHistoryFieldValue('status', $statusFrom),
                'new' => $this->formatHistoryFieldValue('status', $statusTo),
            ]
        ];
    }

    private function buildHistoryDescription(string $summary, array $changeSet): string
    {
        $segments = [$summary];

        foreach ($changeSet as $change) {
            if (!is_array($change)) {
                continue;
            }

            $label = trim((string) ($change['label'] ?? $change['field'] ?? 'Field'));
            $oldValue = $this->sanitizeHistoryValue($change['old'] ?? null);
            $newValue = $this->sanitizeHistoryValue($change['new'] ?? null);

            if ($oldValue === $newValue) {
                continue;
            }

            $segments[] = $label . ': ' . $oldValue . ' -> ' . $newValue;
        }

        return implode(' | ', $segments);
    }

    private function sanitizeHistoryValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return str_replace([';', "\r", "\n"], [',', ' ', ' '], (string) $value);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return $this->normalizeNullableString($value);
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $date = date_create((string) $value);

        if ($date) {
            return $date->format('Y-m-d');
        }

        return $this->normalizeNullableString($value);
    }

    private function formatHistoryFieldValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $dateFields = ['tanggal', 'tanggal_lama', 'tanggal_harga_baru'];
        $numericFields = ['price_per_pcs', 'harga_baru', 'selisih'];

        if ($field === 'attachment') {
            return basename((string) $value);
        }

        if (in_array($field, $dateFields, true)) {
            $date = date_create((string) $value);
            return $date ? $date->format('d-m-Y') : $this->sanitizeHistoryValue($value);
        }

        if ($field === 'qty' && is_numeric($value)) {
            return number_format((float) $value, 0, '.', '');
        }

        if ($field === 'status') {
            return $this->statusLabel((string) $value);
        }

        if ($field === 'type') {
            return (string) $value === 'update_price' ? 'Update Harga' : 'Produk Baru';
        }

        if (in_array($field, $numericFields, true) && is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return $this->sanitizeHistoryValue($value);
    }

    private function normalizeFilterDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dateValue = trim((string) $value);
        if ($dateValue === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $dateValue)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function generateNomorPengajuan(string $type, ?Carbon $referenceDate = null): string
    {
        $createdAt = $referenceDate ? $referenceDate->copy() : now();
        $typeCode = $type === ItemCode::TYPE_UPDATE_PRICE ? 'NP' : 'IC';
        $month = $createdAt->format('m');
        $year = $createdAt->format('y');

        $pattern = '%/' . $typeCode . '/PROC/' . $month . '/' . $year;
        $existingNumbers = ItemCode::withTrashed()
            ->where('type', $type)
            ->where('nomor_pengajuan', 'like', $pattern)
            ->lockForUpdate()
            ->pluck('nomor_pengajuan');

        $maxSequence = 0;
        foreach ($existingNumbers as $nomorPengajuan) {
            $sequenceValue = $this->extractNomorPengajuanSequence((string) $nomorPengajuan, $typeCode, $month, $year);
            if ($sequenceValue !== null && $sequenceValue > $maxSequence) {
                $maxSequence = $sequenceValue;
            }
        }

        $sequence = $maxSequence + 1;

        $nomorPengajuan = $this->buildNomorPengajuan($sequence, $typeCode, $month, $year);

        while (ItemCode::withTrashed()->where('nomor_pengajuan', $nomorPengajuan)->lockForUpdate()->exists()) {
            $sequence++;
            $nomorPengajuan = $this->buildNomorPengajuan($sequence, $typeCode, $month, $year);
        }

        return $nomorPengajuan;
    }

    private function extractNomorPengajuanSequence(string $nomorPengajuan, string $typeCode, string $month, string $year): ?int
    {
        $pattern = '/^(\d+)\/' . preg_quote($typeCode, '/') . '\/PROC\/' . preg_quote($month, '/') . '\/' . preg_quote($year, '/') . '$/';

        if (!preg_match($pattern, $nomorPengajuan, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function buildNomorPengajuan(int $sequence, string $typeCode, string $month, string $year): string
    {
        return sprintf('%04d/%s/PROC/%s/%s', $sequence, $typeCode, $month, $year);
    }

    private function storeAttachmentFile(\Illuminate\Http\UploadedFile $file): string
    {
        return $file->store(self::ATTACHMENT_DIRECTORY, 'local');
    }

    private function resolveAttachmentMimeType(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }

    private function normalizeNomorPengajuanInput(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function ensureNomorPengajuanProductCodeUnique(string $nomorPengajuan, string $productCode, ?int $ignoreItemId = null): void
    {
        $normalizedProductCode = trim($productCode);

        $query = ItemCode::query()
            ->where('nomor_pengajuan', $nomorPengajuan)
            ->where('product_code', $normalizedProductCode);

        if ($ignoreItemId !== null) {
            $query->where('id', '!=', $ignoreItemId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'product_code' => 'Product Code ini sudah digunakan pada Nomor Pengajuan yang sama. Gunakan Product Code berbeda atau ganti Nomor Pengajuan.',
            ]);
        }
    }

    private function buildSubmissionPayload(ItemCode $itemCode): array
    {
        $nomorPengajuan = $this->normalizeNomorPengajuanInput($itemCode->nomor_pengajuan)
            ?: $this->generateNomorPengajuan((string) $itemCode->type);

        $productCode = trim((string) $itemCode->product_code);

        $this->ensureNomorPengajuanProductCodeUnique(
            $nomorPengajuan,
            $productCode,
            $itemCode->id
        );

        return [
            'nomor_pengajuan' => $nomorPengajuan,
            'product_code' => $productCode,
            'status' => ItemCode::STATUS_SUBMITTED,
            'approved_by' => null,
            'finished_by' => null,
        ];
    }

    private function resolveActiveTab(?string $tab): string
    {
        return in_array($tab, ItemCode::typeList(), true) ? $tab : ItemCode::TYPE_NEW_PRODUCT;
    }
}
