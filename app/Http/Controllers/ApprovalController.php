<?php

namespace App\Http\Controllers;

use App\Exports\ItemCodeExport;
use App\Models\ItemCode;
use App\Services\ItemCodeHistoryService;
use App\Enums\ProcurementMenuAccessGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $userName = $this->currentUserName();
        $isApprover1 = ProcurementMenuAccessGroup::ITEM_CODE_APPROVER_1->hasAccess($userName);
        $isApprover2 = ProcurementMenuAccessGroup::ITEM_CODE_APPROVER_2->hasAccess($userName);
        $isFinisher  = ProcurementMenuAccessGroup::ITEM_CODE_FINISHER->hasAccess($userName);
        abort_if(!$isApprover1 && !$isApprover2 && !$isFinisher, 403, 'Unauthorized');

        $isApprover = $isApprover1 || $isApprover2;

        $activeTab = $this->resolveActiveTab($request->query('tab'));
        $perPage   = $this->resolvePerPage($request->query('per_page'));
        $filters   = $this->resolveFilters($request);

        $baseQuery   = $this->buildBaseApprovalQuery($isApprover1, $isApprover2, $isFinisher);
        $statsByType = $this->buildStatsByType(clone $baseQuery);

        $itemsQuery = (clone $baseQuery)
            ->withCount([
                'histories as rejected_histories_count' => fn (Builder $builder) => $builder->where('action', ItemCodeHistoryService::ACTION_REJECTED),
            ])
            ->with(['creator', 'approver', 'approver2', 'finisher'])
            ->where('type', $activeTab);

        $this->applyFilters($itemsQuery, $filters);

        $items = $itemsQuery
            ->orderByDesc('id')
            ->simplePaginate($perPage)
            ->appends($request->query());

        return view('item_code.persetujuan.index', [
            'itemsNewProduct'  => $activeTab === ItemCode::TYPE_NEW_PRODUCT  ? $items : collect(),
            'itemsUpdatePrice' => $activeTab === ItemCode::TYPE_UPDATE_PRICE ? $items : collect(),
            'activeTab'        => $activeTab,
            'perPage'          => $perPage,
            'filters'          => $filters,
            'statsByType'      => $statsByType,
            'canApprove'       => $isApprover,   // untuk tombol reject (salah satu approver)
            'canApprove1'      => $isApprover1,  // untuk tombol Approve 1 (Jessica)
            'canApprove2'      => $isApprover2,  // untuk tombol Approve 2 (Martinus)
            'canFinish'        => $isFinisher,
        ]);
    }

    /**
     * Approve 1 — Jessica Paune
     * Transisi: submitted → approved_1
     */
    public function approve(int $id): RedirectResponse
    {
        $this->ensureApprover1();

        $itemCode = ItemCode::findOrFail($id);

        abort_if(
            !$itemCode->canTransitionTo(ItemCode::STATUS_APPROVED_1),
            403,
            'Transisi status tidak valid: hanya Submitted yang bisa di-approve 1.'
        );

        $statusFrom = $itemCode->status;
        $itemCode->update([
            'status'      => ItemCode::STATUS_APPROVED_1,
            'approved_by' => Auth::id(),
            'finished_by' => null,
        ]);

        $itemCode->refresh();

        $this->historyService()->record(
            $itemCode,
            ItemCodeHistoryService::ACTION_APPROVED,
            'Data item code di-approve (Approve 1).',
            $statusFrom,
            $itemCode->status,
            [[
                'field' => 'status',
                'label' => 'Status',
                'old'   => ucfirst($statusFrom),
                'new'   => ucfirst($itemCode->status),
            ]]
        );

        return redirect()
            ->route('item-code.approval', ['tab' => $itemCode->type])
            ->with('success', 'Data berhasil di-approve (Approve 1).');
    }

    /**
     * Approve All 1 — Jessica Paune
     * Bulk transisi: submitted → approved_1
     */
    public function approveAll(Request $request): RedirectResponse
    {
        $this->ensureApprover1();

        $validated = $request->validate([
            'tab' => 'required|in:' . implode(',', ItemCode::typeList()),
        ]);

        $tab = $this->resolveActiveTab($validated['tab']);

        $items = ItemCode::query()
            ->where('type', $tab)
            ->where('status', ItemCode::STATUS_SUBMITTED)
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('item-code.approval', ['tab' => $tab])
                ->with('warning', 'Tidak ada data Submitted untuk di-approve pada tab ini.');
        }

        DB::transaction(function () use ($items) {
            foreach ($items as $itemCode) {
                $statusFrom = $itemCode->status;

                if (!$itemCode->canTransitionTo(ItemCode::STATUS_APPROVED_1)) {
                    continue;
                }

                $itemCode->update([
                    'status'      => ItemCode::STATUS_APPROVED_1,
                    'approved_by' => Auth::id(),
                    'finished_by' => null,
                ]);

                $this->historyService()->record(
                    $itemCode,
                    ItemCodeHistoryService::ACTION_APPROVED,
                    'Data item code di-approve (Approve 1).',
                    $statusFrom,
                    $itemCode->status,
                    [[
                        'field' => 'status',
                        'label' => 'Status',
                        'old'   => ucfirst($statusFrom),
                        'new'   => ucfirst($itemCode->status),
                    ]]
                );
            }
        });

        return redirect()
            ->route('item-code.approval', ['tab' => $tab])
            ->with('success', $items->count() . ' data Submitted berhasil di-approve (Approve 1).');
    }

    /**
     * Approve 2 — Martinus Cahyo Rahasto
     * Transisi: approved_1 → approved_2
     */
    public function approve2(int $id): RedirectResponse
    {
        $this->ensureApprover2();

        $itemCode = ItemCode::findOrFail($id);

        abort_if(
            !$itemCode->canTransitionTo(ItemCode::STATUS_APPROVED_2),
            403,
            'Transisi status tidak valid: hanya Approved 1 yang bisa di-approve 2.'
        );

        $statusFrom = $itemCode->status;
        $itemCode->update([
            'status'       => ItemCode::STATUS_APPROVED_2,
            'approved2_by' => Auth::id(),
            'finished_by'  => null,
        ]);

        $itemCode->refresh();

        $this->historyService()->record(
            $itemCode,
            ItemCodeHistoryService::ACTION_APPROVED,
            'Data item code di-approve (Approve 2).',
            $statusFrom,
            $itemCode->status,
            [[
                'field' => 'status',
                'label' => 'Status',
                'old'   => ucfirst($statusFrom),
                'new'   => ucfirst($itemCode->status),
            ]]
        );

        return redirect()
            ->route('item-code.approval', ['tab' => $itemCode->type])
            ->with('success', 'Data berhasil di-approve (Approve 2).');
    }

    /**
     * Approve All 2 — Martinus Cahyo Rahasto
     * Bulk transisi: approved_1 → approved_2
     */
    public function approve2All(Request $request): RedirectResponse
    {
        $this->ensureApprover2();

        $validated = $request->validate([
            'tab' => 'required|in:' . implode(',', ItemCode::typeList()),
        ]);

        $tab = $this->resolveActiveTab($validated['tab']);

        $items = ItemCode::query()
            ->where('type', $tab)
            ->where('status', ItemCode::STATUS_APPROVED_1)
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('item-code.approval', ['tab' => $tab])
                ->with('warning', 'Tidak ada data Approved 1 untuk di-approve pada tab ini.');
        }

        DB::transaction(function () use ($items) {
            foreach ($items as $itemCode) {
                $statusFrom = $itemCode->status;

                if (!$itemCode->canTransitionTo(ItemCode::STATUS_APPROVED_2)) {
                    continue;
                }

                $itemCode->update([
                    'status'       => ItemCode::STATUS_APPROVED_2,
                    'approved2_by' => Auth::id(),
                    'finished_by'  => null,
                ]);

                $this->historyService()->record(
                    $itemCode,
                    ItemCodeHistoryService::ACTION_APPROVED,
                    'Data item code di-approve (Approve 2).',
                    $statusFrom,
                    $itemCode->status,
                    [[
                        'field' => 'status',
                        'label' => 'Status',
                        'old'   => ucfirst($statusFrom),
                        'new'   => ucfirst($itemCode->status),
                    ]]
                );
            }
        });

        return redirect()
            ->route('item-code.approval', ['tab' => $tab])
            ->with('success', $items->count() . ' data Approved 1 berhasil di-approve (Approve 2).');
    }

    /**
     * Reject — bisa dilakukan Approver 1 atau Approver 2
     * Transisi: submitted / approved_1 / approved_2 → draft
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $this->ensureApprover();

        $itemCode = ItemCode::findOrFail($id);

        abort_if(
            !$itemCode->canTransitionTo(ItemCode::STATUS_DRAFT),
            403,
            'Transisi status tidak valid: hanya Submitted, Approved 1, atau Approved 2 yang bisa di-reject.'
        );

        $rejectReason = trim((string) $request->input('reject_reason', ''));
        if ($rejectReason === '' || strlen($rejectReason) < 3) {
            return redirect()
                ->route('item-code.approval', ['tab' => $itemCode->type])
                ->with('warning', 'Alasan reject wajib diisi minimal 3 karakter.');
        }

        if (strlen($rejectReason) > 500) {
            $rejectReason = substr($rejectReason, 0, 500);
        }

        $statusFrom = $itemCode->status;
        $itemCode->update([
            'status'       => ItemCode::STATUS_DRAFT,
            'approved_by'  => null,
            'approved2_by' => null,
            'finished_by'  => null,
        ]);

        $itemCode->refresh();

        $this->historyService()->record(
            $itemCode,
            ItemCodeHistoryService::ACTION_REJECTED,
            'Data item code di-reject dan dikembalikan ke Draft.',
            $statusFrom,
            $itemCode->status,
            [
                [
                    'field' => 'status',
                    'label' => 'Status',
                    'old'   => ucfirst($statusFrom),
                    'new'   => ucfirst($itemCode->status),
                ],
                [
                    'field' => 'reject_reason',
                    'label' => 'Catatan Reject',
                    'old'   => '-',
                    'new'   => $rejectReason,
                ],
            ]
        );

        return redirect()
            ->route('item-code.approval', ['tab' => $itemCode->type])
            ->with('success', 'Data berhasil di-reject dan dikembalikan ke Draft.');
    }

    /**
     * Finish — Adhi Prasetiyo
     * Transisi: approved_2 → finished
     */
    public function finish(int $id): RedirectResponse
    {
        $this->ensureFinisher();

        $itemCode = ItemCode::findOrFail($id);

        abort_if(
            !$itemCode->canTransitionTo(ItemCode::STATUS_FINISHED),
            403,
            'Transisi status tidak valid: hanya Approved 2 yang bisa di-finish.'
        );

        $statusFrom = $itemCode->status;
        $itemCode->update([
            'status'      => ItemCode::STATUS_FINISHED,
            'finished_by' => Auth::id(),
        ]);

        $itemCode->refresh();

        $this->historyService()->record(
            $itemCode,
            ItemCodeHistoryService::ACTION_FINISHED,
            'Data item code di-finish.',
            $statusFrom,
            $itemCode->status,
            [[
                'field' => 'status',
                'label' => 'Status',
                'old'   => ucfirst($statusFrom),
                'new'   => ucfirst($itemCode->status),
            ]]
        );

        return redirect()
            ->route('item-code.approval', ['tab' => $itemCode->type])
            ->with('success', 'Data berhasil di-finish.');
    }

    public function finishAll(Request $request): RedirectResponse
    {
        $this->ensureFinisher();

        $validated = $request->validate([
            'tab' => 'required|in:' . implode(',', ItemCode::typeList()),
        ]);

        $tab = $this->resolveActiveTab($validated['tab']);

        $items = ItemCode::query()
            ->where('type', $tab)
            ->where('status', ItemCode::STATUS_APPROVED_2)
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('item-code.approval', ['tab' => $tab])
                ->with('warning', 'Tidak ada data Approved 2 untuk di-finish pada tab ini.');
        }

        DB::transaction(function () use ($items) {
            foreach ($items as $itemCode) {
                $statusFrom = $itemCode->status;

                if (!$itemCode->canTransitionTo(ItemCode::STATUS_FINISHED)) {
                    continue;
                }

                $itemCode->update([
                    'status'      => ItemCode::STATUS_FINISHED,
                    'finished_by' => Auth::id(),
                ]);

                $this->historyService()->record(
                    $itemCode,
                    ItemCodeHistoryService::ACTION_FINISHED,
                    'Data item code di-finish.',
                    $statusFrom,
                    $itemCode->status,
                    [[
                        'field' => 'status',
                        'label' => 'Status',
                        'old'   => ucfirst($statusFrom),
                        'new'   => ucfirst($itemCode->status),
                    ]]
                );
            }
        });

        return redirect()
            ->route('item-code.approval', ['tab' => $tab])
            ->with('success', $items->count() . ' data Approved 2 berhasil di-finish.');
    }

    public function export(Request $request)
    {
        $userName = $this->currentUserName();
        $isApprover1 = ProcurementMenuAccessGroup::ITEM_CODE_APPROVER_1->hasAccess($userName);
        $isApprover2 = ProcurementMenuAccessGroup::ITEM_CODE_APPROVER_2->hasAccess($userName);
        $isFinisher  = ProcurementMenuAccessGroup::ITEM_CODE_FINISHER->hasAccess($userName);
        abort_if(!$isApprover1 && !$isApprover2 && !$isFinisher, 403, 'Unauthorized');

        $tab     = $this->resolveActiveTab($request->query('tab'));
        $filters = $this->resolveFilters($request);

        $query = $this->buildBaseApprovalQuery($isApprover1, $isApprover2, $isFinisher)
            ->withCount([
                'histories as rejected_histories_count' => fn (Builder $builder) => $builder->where('action', ItemCodeHistoryService::ACTION_REJECTED),
            ])
            ->with(['creator', 'approver', 'approver2', 'finisher'])
            ->where('type', $tab);

        $this->applyFilters($query, $filters);

        $items = $query->orderByDesc('id')->get();

        $fileName = sprintf(
            'item_code_persetujuan_%s_%s.xlsx',
            $tab,
            now()->format('Ymd_His')
        );

        return Excel::download(
            new ItemCodeExport($items),
            $fileName,
            ExcelFormat::XLSX
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function ensureApprover(): void
    {
        if (!ProcurementMenuAccessGroup::ITEM_CODE_APPROVER->hasAccess($this->currentUserName())) {
            abort(403, 'Unauthorized');
        }
    }

    private function ensureApprover1(): void
    {
        if (!ProcurementMenuAccessGroup::ITEM_CODE_APPROVER_1->hasAccess($this->currentUserName())) {
            abort(403, 'Unauthorized: hanya Approver 1 yang dapat melakukan aksi ini.');
        }
    }

    private function ensureApprover2(): void
    {
        if (!ProcurementMenuAccessGroup::ITEM_CODE_APPROVER_2->hasAccess($this->currentUserName())) {
            abort(403, 'Unauthorized: hanya Approver 2 yang dapat melakukan aksi ini.');
        }
    }

    private function ensureFinisher(): void
    {
        if (!ProcurementMenuAccessGroup::ITEM_CODE_FINISHER->hasAccess($this->currentUserName())) {
            abort(403, 'Unauthorized');
        }
    }

    private function currentUserName(): string
    {
        return (string) Auth::user()->getAttribute('name');
    }

    private function resolveActiveTab(?string $tab): string
    {
        return in_array($tab, ItemCode::typeList(), true) ? $tab : ItemCode::TYPE_NEW_PRODUCT;
    }

    private function resolveFilters(Request $request): array
    {
        $q         = trim((string) $request->query('q', ''));
        $status    = strtolower(trim((string) $request->query('status', '')));
        $startDate = $this->normalizeFilterDate($request->query('start_date'));
        $endDate   = $this->normalizeFilterDate($request->query('end_date'));

        $validStatuses = [
            ItemCode::STATUS_SUBMITTED,
            ItemCode::STATUS_APPROVED_1,
            ItemCode::STATUS_APPROVED_2,
            ItemCode::STATUS_FINISHED,
        ];

        if (!in_array($status, $validStatuses, true)) {
            $status = null;
        }

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'q'          => $q !== '' ? $q : null,
            'status'     => $status,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];
    }

    private function resolvePerPage(mixed $perPage): int
    {
        $allowed = [10, 20, 50];
        $value   = is_numeric($perPage) ? (int) $perPage : 20;

        return in_array($value, $allowed, true) ? $value : 20;
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

    private function buildBaseApprovalQuery(bool $isApprover1, bool $isApprover2, bool $isFinisher): Builder
    {
        // Default: tampilkan semua status kecuali draft
        $query = ItemCode::query()
            ->whereIn('status', [
                ItemCode::STATUS_SUBMITTED,
                ItemCode::STATUS_APPROVED_1,
                ItemCode::STATUS_APPROVED_2,
                ItemCode::STATUS_FINISHED,
            ]);

        // Finisher yang bukan approver hanya perlu lihat approved_2 & finished
        if ($isFinisher && !$isApprover1 && !$isApprover2) {
            $query->whereIn('status', [
                ItemCode::STATUS_APPROVED_2,
                ItemCode::STATUS_FINISHED,
            ]);
        }

        return $query;
    }

    private function buildStatsByType(Builder $baseQuery): array
    {
        $stats = [
            ItemCode::TYPE_NEW_PRODUCT => [
                'total'                     => 0,
                ItemCode::STATUS_SUBMITTED  => 0,
                ItemCode::STATUS_APPROVED_1 => 0,
                ItemCode::STATUS_APPROVED_2 => 0,
                ItemCode::STATUS_FINISHED   => 0,
            ],
            ItemCode::TYPE_UPDATE_PRICE => [
                'total'                     => 0,
                ItemCode::STATUS_SUBMITTED  => 0,
                ItemCode::STATUS_APPROVED_1 => 0,
                ItemCode::STATUS_APPROVED_2 => 0,
                ItemCode::STATUS_FINISHED   => 0,
            ],
        ];

        $rows = $baseQuery
            ->select('type', 'status', DB::raw('count(*) as aggregate'))
            ->groupBy('type', 'status')
            ->get();

        foreach ($rows as $row) {
            $type   = (string) $row->type;
            $status = (string) $row->status;
            $count  = (int) $row->aggregate;

            if (!isset($stats[$type]) || !array_key_exists($status, $stats[$type])) {
                continue;
            }

            $stats[$type][$status]  = $count;
            $stats[$type]['total'] += $count;
        }

        return $stats;
    }

    private function historyService(): ItemCodeHistoryService
    {
        return app(ItemCodeHistoryService::class);
    }
}