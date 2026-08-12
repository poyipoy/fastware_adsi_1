<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseAdjustmentRequest;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseStockService;

class WarehouseStockAdjustmentController extends Controller
{
    public function create()
    {
        return view('warehouse.adjustments.create', [
            'consumables' => WarehouseConsumable::query()->where('is_active', true)->orderBy('item_name')->get(),
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
            );
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['adjustment' => $exception->getMessage()]);
        }

        return redirect()->route('warehouse.dashboard')->with('status', 'Penyesuaian stok berhasil dicatat. Nomor transaksi: '.$result->transaction->transaction_number.'.');
    }
}
