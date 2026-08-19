<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\CancelWarehouseLocationShipmentRequest;
use App\Http\Requests\Warehouse\StoreWarehouseLocationShipmentRequest;
use App\Http\Requests\Warehouse\ValidateWarehouseLocationShipmentRequest;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseLocationShipment;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseLocationShipmentService;
use Illuminate\Http\Request;

class WarehouseLocationShipmentController extends Controller
{
    public function index(Request $request)
    {
        $shipments = WarehouseLocationShipment::query()
            ->with(['consumable', 'sender', 'validator'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', strtoupper((string) $request->query('status'))))
            ->latest('sent_at')
            ->paginate(20)
            ->withQueryString();

        return view('warehouse.location-shipments.index', compact('shipments'));
    }

    public function create()
    {
        return view('warehouse.location-shipments.create', [
            'consumables' => WarehouseConsumable::query()
                ->where('is_active', true)
                ->orderBy('item_name')
                ->get(),
        ]);
    }

    public function store(
        StoreWarehouseLocationShipmentRequest $request,
        WarehouseLocationShipmentService $service,
    ) {
        try {
            $alreadyCreated = WarehouseLocationShipment::query()
                ->where('creation_idempotency_key', (string) $request->input('idempotency_key'))
                ->exists();
            $shipment = $service->createShipment(
                sender: $request->user(),
                consumableId: (int) $request->integer('consumable_id'),
                itemCondition: (string) $request->input('item_condition'),
                quantity: (string) $request->input('quantity'),
                fromLocation: (string) $request->input('from_location'),
                toLocation: (string) $request->input('to_location'),
                notes: $request->input('notes'),
                idempotencyKey: (string) $request->input('idempotency_key'),
            );

            if ($request->expectsJson()) {
                return response()->json(['data' => $this->payload($shipment)], $alreadyCreated ? 200 : 201);
            }

            return redirect()->route('warehouse.location-shipments.show', $shipment)
                ->with('status', 'Pengiriman Antar Lokasi dibuat dan menunggu Validasi.');
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return $this->error($request, $exception, 'shipment');
        }
    }

    public function show(WarehouseLocationShipment $shipment)
    {
        return view('warehouse.location-shipments.show', [
            'shipment' => $shipment->load(['consumable', 'sender', 'validator', 'validationActor', 'stockTransaction', 'cancelledBy']),
        ]);
    }

    public function validateForm(WarehouseLocationShipment $shipment)
    {
        abort_unless($shipment->canValidate(), 409, 'Pengiriman sudah tidak menunggu Validasi.');

        return view('warehouse.location-shipments.validate', [
            'shipment' => $shipment->load(['consumable', 'sender']),
        ]);
    }

    public function validateShipment(
        ValidateWarehouseLocationShipmentRequest $request,
        WarehouseLocationShipment $shipment,
        WarehouseIdentityResolver $identity,
        WarehouseLocationShipmentService $service,
    ) {
        try {
            $alreadyValidated = $shipment->status?->value === 'VALIDATED';
            $validator = $identity->resolveUserForDirection((string) $request->input('validator_code'), 'IN', false);
            if ($validator === null) {
                throw new WarehouseDomainException('NPK Validator tidak ditemukan atau tidak aktif.', 422);
            }

            $scannedConsumableId = null;
            if ($request->filled('received_item_barcode')) {
                $scanned = $identity->resolveItem((string) $request->input('received_item_barcode'));
                if ($scanned === null) {
                    throw new WarehouseDomainException('Barcode item fisik tidak ditemukan atau tidak aktif.', 422);
                }
                $scannedConsumableId = (int) $scanned->getKey();
            }

            $result = $service->validateShipment(
                actor: $request->user(),
                shipment: $shipment,
                validator: $validator,
                receivedQuantity: (string) $request->input('received_quantity'),
                receivedCondition: (string) $request->input('received_condition'),
                validationNotes: $request->input('validation_notes'),
                idempotencyKey: (string) $request->input('idempotency_key'),
                scannedConsumableId: $scannedConsumableId,
            );

            if ($request->expectsJson()) {
                return response()->json(
                    ['data' => $this->payload($result)],
                    $result->status?->value === 'VALIDATED' && ! $alreadyValidated ? 201 : 200,
                );
            }

            return redirect()->route('warehouse.location-shipments.show', $result)->with(
                'status',
                $result->status?->value === 'VALIDATED'
                    ? 'Serah terima sesuai. Saldo lokasi berhasil dipindahkan.'
                    : 'Hasil Validasi Tidak Sesuai disimpan sebagai Discrepancy; saldo belum dipindahkan.',
            );
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return $this->error($request, $exception, 'validation');
        }
    }

    public function cancel(
        CancelWarehouseLocationShipmentRequest $request,
        WarehouseLocationShipment $shipment,
        WarehouseLocationShipmentService $service,
    ) {
        try {
            $result = $service->cancelShipment(
                actor: $request->user(),
                shipment: $shipment,
                reason: (string) $request->input('reason'),
                idempotencyKey: (string) $request->input('idempotency_key'),
            );

            if ($request->expectsJson()) {
                return response()->json(['data' => $this->payload($result)], 200);
            }

            return redirect()->route('warehouse.location-shipments.show', $result)->with('status', 'Pengiriman dibatalkan. Reservation telah dilepas.');
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return $this->error($request, $exception, 'cancellation');
        }
    }

    private function payload(WarehouseLocationShipment $shipment): array
    {
        $shipment->loadMissing(['consumable', 'sender', 'validator', 'stockTransaction']);

        return [
            'id' => $shipment->getKey(),
            'shipment_number' => $shipment->shipment_number,
            'status' => $shipment->status?->value,
            'item' => $shipment->consumable?->item_name,
            'item_code' => $shipment->consumable?->item_code,
            'item_condition' => $shipment->item_condition?->value,
            'quantity_sent' => (string) $shipment->quantity_sent,
            'from_location' => $shipment->from_location,
            'to_location' => $shipment->to_location,
            'sender' => $shipment->sender_name_snapshot,
            'sender_npk' => $shipment->sender_npk_snapshot,
            'sender_notes' => $shipment->sender_notes,
            'sent_at' => optional($shipment->sent_at)->toIso8601String(),
            'received_quantity' => $shipment->received_quantity === null ? null : (string) $shipment->received_quantity,
            'received_condition' => $shipment->received_condition?->value,
            'validator' => $shipment->validator_name_snapshot,
            'validator_npk' => $shipment->validator_npk_snapshot,
            'validation_notes' => $shipment->validation_notes,
            'validated_at' => optional($shipment->validated_at)->toIso8601String(),
            'stock_transaction_id' => $shipment->stock_transaction_id,
        ];
    }

    private function error(Request $request, \Throwable $exception, string $key)
    {
        if ($request->expectsJson()) {
            $status = $exception instanceof WarehouseDomainException ? $exception->status : 422;

            return response()->json(['message' => $exception->getMessage()], $status);
        }

        return back()->withInput()->withErrors([$key => $exception->getMessage()]);
    }
}
