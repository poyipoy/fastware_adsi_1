<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseAdjustmentRequest;
use App\Http\Requests\Warehouse\WarehouseAdjustmentHistoryRequest;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseStockService;
use App\Services\Warehouse\WarehouseTransactionQueryService;

class WarehouseStockAdjustmentController extends Controller
{
    public function create(
        WarehouseAdjustmentHistoryRequest $request,
        WarehouseTransactionQueryService $queryService,
    ) {
        $filters = [
            'transaction_type' => WarehouseTransactionType::ADJUSTMENT->value,
            'date_from' => $request->input('adjustment_date_from'),
            'date_to' => $request->input('adjustment_date_to'),
            'consumable_id' => $request->input('adjustment_consumable_id'),
            'item_condition' => $request->input('adjustment_condition'),
            'verified_user_id' => $request->input('adjustment_verified_user_id'),
        ];

        $historyQuery = $queryService->build($filters)
            ->with(['reversal:id,reversal_of_id', 'verifiedUser:id,name,npk,section']);

        $direction = strtoupper(trim((string) $request->input('adjustment_direction')));
        if ($direction === 'IN') {
            $historyQuery->whereColumn('stock_after', '>', 'stock_before');
        } elseif ($direction === 'OUT') {
            $historyQuery->whereColumn('stock_after', '<', 'stock_before');
        }

        $adjustments = $historyQuery->paginate(20, ['*'], 'adj_page')->withQueryString();

        return view('warehouse.adjustments-create', [
            'consumables' => WarehouseConsumable::query()->where('is_active', true)->orderBy('item_name')->get(),
            'adjustments' => $adjustments,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'npk']),
        ]);
    }

    public function store(StoreWarehouseAdjustmentRequest $request, WarehouseIdentityResolver $identity, WarehouseStockService $stockService)
    {
        $item = WarehouseConsumable::query()->whereKey((int) $request->integer('consumable_id'))->where('is_active', true)->first();
        $direction = strtoupper((string) $request->input('direction'));

        try {
            $verified = $identity->resolveUserForDirection(
                (string) $request->input('verified_code'),
                $direction,
                true,
            );

            if ($item === null || $verified === null) {
                return back()->withInput()->withErrors(['adjustment' => 'Barang atau NPK karyawan tidak ditemukan/aktif.']);
            }

            $result = $stockService->adjust(
                actorId: (int) $request->user()->getKey(),
                verifiedUserId: (int) $verified->getKey(),
                consumableId: (int) $item->getKey(),
                quantity: (string) $request->input('quantity'),
                direction: $direction,
                reasonCategory: (string) $request->input('reason_category'),
                reason: (string) $request->input('reason'),
                idempotencyKey: (string) $request->input('idempotency_key'),
                itemCondition: (string) $request->input('item_condition'),
                storageLocation: (string) $request->input('storage_location'),
            );
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return redirect()->route('warehouse.adjustments.create')->with('status', 'Penyesuaian stok berhasil dicatat. Nomor transaksi: '.$result->transaction->transaction_number.'.');
    }
}
