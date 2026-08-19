<?php

namespace App\Http\Controllers\Warehouse;

use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseTransferRequest;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseStockService;

class WarehouseTransferController extends Controller
{
    public function create()
    {
        return view('warehouse.transfers.create', [
            'consumables' => WarehouseConsumable::query()
                ->where('is_active', true)
                ->orderBy('item_name')
                ->get(),
        ]);
    }

    public function store(
        StoreWarehouseTransferRequest $request,
        WarehouseIdentityResolver $identity,
        WarehouseStockService $stockService,
    ) {
        try {
            $verified = $identity->resolveUserForDirection(
                (string) $request->input('verified_code'),
                'OUT',
                true,
            );
            if ($verified === null) {
                throw new WarehouseDomainException('NPK verifikator Transfer tidak ditemukan atau tidak aktif.', 422);
            }

            $result = $stockService->transfer(
                actorId: (int) $request->user()->getKey(),
                verifiedUserId: (int) $verified->getKey(),
                consumableId: (int) $request->integer('consumable_id'),
                quantity: (string) $request->input('quantity'),
                itemCondition: (string) $request->input('item_condition'),
                fromLocation: (string) $request->input('from_location'),
                toLocation: (string) $request->input('to_location'),
                notes: $request->input('notes'),
                idempotencyKey: (string) $request->input('idempotency_key'),
            );

            return redirect()->route('warehouse.transactions.show', $result->transaction)
                ->with('status', 'Transfer stok berhasil dicatat.');
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['transfer' => $exception->getMessage()]);
        }
    }
}
