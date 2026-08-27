<?php

namespace App\Http\Controllers\Warehouse;

use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\CancelWarehouseStockInRequest;
use App\Http\Requests\Warehouse\StoreWarehouseStockInRequest;
use App\Http\Requests\Warehouse\ValidateWarehouseStockInRequest;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockIn;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseQuantity;
use App\Services\Warehouse\WarehouseStockInService;
use App\Services\Warehouse\WarehouseVerifierPolicy;
use Illuminate\Http\Request;

class WarehouseStockInController extends Controller
{
    public function index()
    {
        return redirect()->route('warehouse.transactions.create');
    }

    public function create()
    {
        return redirect()->route('warehouse.transactions.create', request()->query());
    }

    public function store(
        StoreWarehouseStockInRequest $request,
        WarehouseIdentityResolver $identity,
        WarehouseStockInService $service,
    ) {
        try {
            $item = null;
            if ($request->filled('item_barcode')) {
                $item = $identity->resolveItem((string) $request->input('item_barcode'));
            } elseif ($request->filled('consumable_id')) {
                $item = WarehouseConsumable::query()->where('is_active', true)->find((int) $request->input('consumable_id'));
            }
            if ($item === null) {
                throw new WarehouseDomainException('Item Stock In tidak ditemukan atau tidak aktif.', 422);
            }

            $stockIn = $service->create(
                actorId: (int) $request->user()->getKey(),
                consumableId: (int) $item->getKey(),
                itemCondition: (string) $request->input('item_condition'),
                quantityExpected: (string) $request->input('quantity_expected'),
                destinationLocation: (string) $request->input('destination_location'),
                sourceLocation: $request->input('source_location'),
                notes: $request->input('notes'),
                idempotencyKey: (string) $request->input('idempotency_key'),
            );
            $replay = (bool) $stockIn->getAttribute('_idempotent_replay');

            if ($request->expectsJson()) {
                return response()->json(['data' => $this->payload($stockIn), 'idempotent_replay' => $replay], $replay ? 200 : 201);
            }

            return redirect()->route('warehouse.stock-in.show', $stockIn)
                ->with('status', $replay ? 'Stock In sebelumnya sudah dibuat.' : 'Stock In berhasil dibuat dan menunggu Validasi. Stok belum berubah.');
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return $this->error($request, $exception, 'stock_in');
        }
    }

    public function show(Request $request, WarehouseStockIn $stockIn, WarehouseVerifierPolicy $verifierPolicy)
    {
        return view('warehouse.stock-in.show', [
            'stockIn' => $stockIn->load(['consumable', 'creator', 'validator', 'stockTransaction']),
            'canValidateStockIn' => $verifierPolicy->canAccessValidationWorkspace($request->user()),
        ]);
    }

    public function validateForm(Request $request, WarehouseStockIn $stockIn, WarehouseVerifierPolicy $verifierPolicy)
    {
        abort_unless(
            $verifierPolicy->canAccessValidationWorkspace($request->user()),
            403,
            'Akun ini tidak terdaftar sebagai validator Stock In.',
        );
        abort_unless($stockIn->canValidate(), 409, 'Stock In sudah tidak menunggu Validasi.');

        return view('warehouse.stock-in.validate', [
            'stockIn' => $stockIn->load(['consumable', 'creator']),
        ]);
    }

    public function validateStockIn(
        ValidateWarehouseStockInRequest $request,
        WarehouseStockIn $stockIn,
        WarehouseIdentityResolver $identity,
        WarehouseStockInService $service,
    ) {
        try {
            $receivedConsumableId = null;
            if ($request->filled('received_item_barcode')) {
                $received = $identity->resolveItem((string) $request->input('received_item_barcode'));
                if ($received === null) {
                    throw new WarehouseDomainException('Barcode item fisik tidak ditemukan atau tidak aktif.', 422);
                }
                $receivedConsumableId = (int) $received->getKey();
            }

            $result = $service->validate(
                actorId: (int) $request->user()->getKey(),
                stockIn: $stockIn,
                quantityReceived: (string) $request->input('quantity_received'),
                validationResult: $request->input('validation_result'),
                validationNotes: $request->input('validation_notes'),
                receivedConsumableId: $receivedConsumableId,
                idempotencyKey: (string) $request->input('idempotency_key'),
            );
            $replay = (bool) $result->getAttribute('_idempotent_replay');

            if ($request->expectsJson()) {
                return response()->json(['data' => $this->payload($result), 'idempotent_replay' => $replay], $replay ? 200 : 201);
            }

            return redirect()->route('warehouse.stock-in.show', $result)->with(
                'status',
                $replay ? 'Validasi Stock In sebelumnya sudah tersimpan.' : 'Stock In tervalidasi dan saldo berhasil diperbarui.',
            );
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return $this->error($request, $exception, 'validation');
        }
    }

    public function cancel(
        CancelWarehouseStockInRequest $request,
        WarehouseStockIn $stockIn,
        WarehouseStockInService $service,
    ) {
        try {
            $result = $service->cancel(
                actorId: (int) $request->user()->getKey(),
                stockIn: $stockIn,
                reason: (string) $request->input('reason'),
                idempotencyKey: (string) $request->input('idempotency_key'),
            );

            if ($request->expectsJson()) {
                return response()->json(['data' => $this->payload($result)], 200);
            }

            return redirect()->route('warehouse.stock-in.show', $result)->with('status', 'Stock In dibatalkan.');
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return $this->error($request, $exception, 'cancellation');
        }
    }

    private function payload(WarehouseStockIn $stockIn): array
    {
        $stockIn->loadMissing(['consumable', 'creator', 'validator', 'stockTransaction']);

        return [
            'id' => $stockIn->getKey(),
            'stock_in_number' => $stockIn->stock_in_number,
            'status' => $stockIn->status?->value,
            'validation_result' => $stockIn->validation_result?->value,
            'item' => $stockIn->consumable?->item_name,
            'item_code' => $stockIn->consumable?->item_code,
            'item_condition' => $stockIn->item_condition?->value,
            'quantity_expected' => (string) $stockIn->quantity_expected,
            'quantity_received' => $stockIn->quantity_received === null ? null : (string) $stockIn->quantity_received,
            'difference' => $stockIn->quantity_received === null
                ? null
                : $this->signedQuantityDifference(
                    WarehouseQuantity::toMilli((string) $stockIn->quantity_received)
                    - WarehouseQuantity::toMilli((string) $stockIn->quantity_expected),
                ),
            'destination_location' => $stockIn->destination_location,
            'source_location' => $stockIn->source_location,
            'created_by' => $stockIn->creator_name_snapshot,
            'created_at' => optional($stockIn->created_at)->toIso8601String(),
            'validator' => $stockIn->validator_name_snapshot,
            'validator_npk' => $stockIn->validator_npk_snapshot,
            'validation_notes' => $stockIn->validation_notes,
            'validated_at' => optional($stockIn->validated_at)->toIso8601String(),
            'stock_transaction_id' => $stockIn->stock_transaction_id,
        ];
    }

    private function error(Request $request, \Throwable $exception, string $key)
    {
        $status = $exception instanceof WarehouseDomainException ? $exception->status : 422;
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage()], $status);
        }

        return back()->withInput()->withErrors([$key => $exception->getMessage()]);
    }

    private function signedQuantityDifference(int $differenceMilli): string
    {
        if ($differenceMilli === 0) {
            return '0';
        }

        $absolute = WarehouseQuantity::display(WarehouseQuantity::fromMilli(abs($differenceMilli)));

        return $differenceMilli < 0 ? '-'.$absolute : $absolute;
    }
}
