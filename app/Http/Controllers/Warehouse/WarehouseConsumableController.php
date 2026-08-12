<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseConsumableRequest;
use App\Http\Requests\Warehouse\StoreWarehouseOpeningBalanceRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseConsumableRequest;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseStockService;
use Illuminate\Http\Request;

class WarehouseConsumableController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseConsumable::query()->orderBy('item_name');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('item_code', 'like', '%'.$search.'%')
                    ->orWhere('item_name', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $status = strtoupper((string) $request->query('status'));
            if ($status === 'ACTIVE') {
                $query->where('is_active', true);
            } elseif ($status === 'INACTIVE') {
                $query->where('is_active', false);
            }
        }

        return view('warehouse.consumables.index', [
            'consumables' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('warehouse.consumables.form', [
            'consumable' => new WarehouseConsumable(['is_active' => true, 'allow_fraction' => false]),
        ]);
    }

    public function store(StoreWarehouseConsumableRequest $request)
    {
        $data = $request->safe()->only([
            'item_code',
            'item_name',
            'minimum_stock',
            'maximum_stock',
            'storage_location',
        ]);

        WarehouseConsumable::query()->create($data + [
            'barcode' => $data['item_code'],
            'unit' => 'pcs',
            'allow_fraction' => false,
            'category_id' => null,
            'description' => null,
            'is_active' => true,
            'current_stock' => '0.000',
            'created_by' => $request->user()->getKey(),
            'updated_by' => $request->user()->getKey(),
        ]);

        return redirect()->route('warehouse.consumables.index')->with('status', 'Barang berhasil dibuat dengan stok awal 0.');
    }

    public function edit(WarehouseConsumable $consumable)
    {
        return view('warehouse.consumables.form', [
            'consumable' => $consumable,
        ]);
    }

    public function show(WarehouseConsumable $consumable)
    {
        return view('warehouse.consumables.show', ['consumable' => $consumable]);
    }

    public function update(UpdateWarehouseConsumableRequest $request, WarehouseConsumable $consumable)
    {
        $data = $request->safe()->only([
            'item_code',
            'item_name',
            'minimum_stock',
            'maximum_stock',
            'storage_location',
        ]);
        if ((string) $consumable->barcode === (string) $consumable->item_code) {
            $data['barcode'] = $data['item_code'];
        }
        $data['updated_by'] = $request->user()->getKey();
        $consumable->fill($data)->save();

        return redirect()->route('warehouse.consumables.index')->with('status', 'Master consumable diperbarui. Stok berjalan tidak diubah.');
    }

    public function toggleStatus(Request $request, WarehouseConsumable $consumable)
    {
        $consumable->forceFill([
            'is_active' => ! $consumable->is_active,
            'updated_by' => $request->user()->getKey(),
        ])->save();

        return back()->with('status', $consumable->is_active ? 'Consumable diaktifkan.' : 'Consumable dinonaktifkan.');
    }

    public function openingBalance(StoreWarehouseOpeningBalanceRequest $request, WarehouseConsumable $consumable, WarehouseStockService $stockService, WarehouseIdentityResolver $identity)
    {
        try {
            $verified = $identity->resolveUserForDirection(
                (string) $request->input('verified_code'),
                'IN',
            );
            if ($verified === null) {
                return back()->withInput()->withErrors(['verified_code' => 'NPK karyawan tidak ditemukan atau tidak aktif.']);
            }

            $result = $stockService->adjust(
                actorId: (int) $request->user()->getKey(),
                verifiedUserId: (int) $verified->getKey(),
                consumableId: (int) $consumable->getKey(),
                quantity: (string) $request->input('quantity'),
                direction: 'IN',
                reasonCategory: 'opening_balance',
                reason: (string) $request->input('reason'),
                idempotencyKey: $request->input('idempotency_key'),
            );
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['verified_code' => $exception->getMessage()]);
        }

        return redirect()->route('warehouse.consumables.index')->with('status', 'Opening balance dicatat sebagai movement '.$result->transaction->transaction_number.'.');
    }
}
