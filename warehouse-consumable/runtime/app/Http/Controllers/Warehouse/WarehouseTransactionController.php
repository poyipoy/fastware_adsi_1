<?php

namespace App\Http\Controllers\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\ReverseWarehouseTransactionRequest;
use App\Http\Requests\Warehouse\StoreWarehouseTransactionRequest;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Models\Warehouse\WarehouseLocationShipment;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseStockService;
use App\Services\Warehouse\WarehouseVerifierPolicy;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class WarehouseTransactionController extends Controller
{
    public function create(Request $request, WarehouseAccessService $access)
    {
        return $this->renderCreate($request, $access, WarehouseItemCondition::NEW);
    }

    public function createUsed(Request $request, WarehouseAccessService $access)
    {
        return $this->renderCreate($request, $access, WarehouseItemCondition::USED);
    }

    public function store(
        StoreWarehouseTransactionRequest $request,
        WarehouseIdentityResolver $identity,
        WarehouseStockService $stockService,
    ) {
        try {
            $type = WarehouseTransactionType::from((string) $request->input('type'));
            $condition = WarehouseItemCondition::from((string) $request->input('item_condition'));
            $item = $identity->resolveItem((string) $request->input('item_barcode'));
            $verifiedUser = $identity->resolveUserForDirection((string) $request->input('verified_code'), $type->value);

            if ($item === null) {
                throw new WarehouseDomainException('Barcode item tidak ditemukan atau tidak aktif.', 422);
            }
            if ($verifiedUser === null) {
                throw new WarehouseDomainException('NPK karyawan tidak ditemukan atau tidak aktif.', 422);
            }

            $operationKey = $request->boolean('return_used') ? (string) $request->input('idempotency_key') : null;
            $command = new WarehouseStockCommand(
                type: $type,
                consumableId: (int) $item->getKey(),
                quantity: (string) $request->input('quantity'),
                verifiedUserId: (int) $verifiedUser->getKey(),
                storageLocation: $type === WarehouseTransactionType::IN ? $request->input('location') : null,
                idempotencyKey: (string) $request->input('idempotency_key'),
                createdBy: (int) $request->user()->getKey(),
                verificationCodeHash: $identity->hash((string) $request->input('verified_code')),
                itemCondition: $condition,
                sourceLocation: $type === WarehouseTransactionType::OUT ? $request->input('location') : null,
                operationKey: $operationKey,
            );

            if ($request->boolean('return_used')) {
                $usedReturnItem = $identity->resolveItem((string) $request->input('used_return_item_barcode'));
                if ($usedReturnItem === null) {
                    throw new WarehouseDomainException('Barang bekas yang dikembalikan tidak ditemukan atau tidak aktif.', 422);
                }

                $returnIdempotencyKey = Uuid::uuid5(
                    (string) $request->input('idempotency_key'),
                    'warehouse-used-return',
                )->toString();
                $result = $stockService->executeWithUsedReturn($command, new WarehouseStockCommand(
                    type: WarehouseTransactionType::IN,
                    consumableId: (int) $usedReturnItem->getKey(),
                    quantity: (string) $request->input('used_return_quantity'),
                    verifiedUserId: (int) $verifiedUser->getKey(),
                    storageLocation: (string) $request->input('used_return_location'),
                    idempotencyKey: $returnIdempotencyKey,
                    createdBy: (int) $request->user()->getKey(),
                    itemCondition: WarehouseItemCondition::USED,
                    operationKey: $operationKey,
                ));
            } else {
                $result = $stockService->execute($command);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $this->transactionPayload($result->transaction),
                    'related_transactions' => array_map(fn ($transaction): array => $this->transactionPayload($transaction), $result->relatedTransactions),
                    'idempotent_replay' => $result->idempotentReplay,
                ], $result->idempotentReplay ? 200 : 201);
            }

            return redirect()->route('warehouse.transactions.show', $result->transaction)
                ->with('status', $result->idempotentReplay ? 'Transaksi sebelumnya dikembalikan.' : 'Transaksi berhasil dicatat.');
        } catch (WarehouseDomainException $exception) {
            return $this->domainError($request, $exception);
        } catch (\InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['transaction' => $exception->getMessage()]);
        }
    }

    public function show(Request $request, WarehouseStockTransaction $transaction, WarehouseAccessService $access)
    {
        abort_unless($access->canViewTransaction($request->user(), $transaction), 403);
        $alreadyReversed = WarehouseStockTransaction::query()->where('reversal_of_id', $transaction->getKey())->exists();

        return view('warehouse.transactions.show', [
            'transaction' => $transaction->load(['consumable', 'verifiedUser', 'creator', 'reversalOf', 'reversal', 'locationShipment']),
            'canReverse' => $transaction->transaction_type !== WarehouseTransactionType::TRANSFER
                && $access->can($request->user(), 'warehouse.transaction.reverse')
                && ! $alreadyReversed,
        ]);
    }

    public function reverseForm(Request $request, WarehouseStockTransaction $transaction, WarehouseAccessService $access)
    {
        abort_unless($access->can($request->user(), 'warehouse.transaction.reverse'), 403);
        abort_if($transaction->transaction_type === WarehouseTransactionType::TRANSFER, 422, 'Pengiriman Antar Lokasi dikoreksi dengan pengiriman balik.');

        return view('warehouse.transactions.reverse', [
            'transaction' => $transaction,
            'requiresLegacyLocation' => $transaction->from_location === null && $transaction->to_location === null,
        ]);
    }

    public function reverse(
        ReverseWarehouseTransactionRequest $request,
        WarehouseStockTransaction $transaction,
        WarehouseIdentityResolver $identity,
        WarehouseStockService $stockService,
        WarehouseVerifierPolicy $verifierPolicy,
    ) {
        try {
            $restricted = $transaction->transaction_type === WarehouseTransactionType::ADJUSTMENT;
            $verifiedUser = $identity->resolveUserForDirection(
                (string) $request->input('verified_code'),
                $verifierPolicy->directionForReversal($transaction),
                $restricted,
            );
            if ($verifiedUser === null) {
                throw new WarehouseDomainException('NPK karyawan untuk pembatalan tidak ditemukan atau tidak aktif.', 422);
            }

            $result = $stockService->reverse(
                original: $transaction,
                actorId: (int) $request->user()->getKey(),
                verifiedUserId: (int) $verifiedUser->getKey(),
                reason: (string) $request->input('reason'),
                idempotencyKey: (string) $request->input('idempotency_key'),
                legacyLocation: $request->input('legacy_location'),
            );

            if ($request->expectsJson()) {
                return response()->json(['data' => $this->transactionPayload($result->transaction)], 201);
            }

            return redirect()->route('warehouse.transactions.show', $result->transaction)->with('status', 'Reversal berhasil dicatat.');
        } catch (WarehouseDomainException $exception) {
            return $this->domainError($request, $exception, 'reversal');
        } catch (\InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['reversal' => $exception->getMessage()]);
        }
    }

    private function renderCreate(Request $request, WarehouseAccessService $access, WarehouseItemCondition $condition)
    {
        $canStockIn = $access->can($request->user(), 'warehouse.stock-in.create');
        $canStockOut = $access->can($request->user(), 'warehouse.stock-out.create');
        $availableTypes = array_values(array_filter([
            $canStockIn ? WarehouseTransactionType::IN->value : null,
            $canStockOut ? WarehouseTransactionType::OUT->value : null,
        ]));
        $requestedType = strtoupper(trim((string) $request->query('type', '')));
        $initialType = in_array($requestedType, $availableTypes, true) ? $requestedType : ($availableTypes[0] ?? 'OUT');
        $initialBarcode = substr(trim(preg_replace('/[\x00-\x1F\x7F]/', '', (string) $request->query('barcode', ''))), 0, 120);

        return view('warehouse.transactions.create', [
            'canStockIn' => $canStockIn,
            'canStockOut' => $canStockOut,
            'availableTypes' => $availableTypes,
            'initialType' => $initialType,
            'initialBarcode' => $initialBarcode,
            'itemCondition' => $condition,
            'transactionRequirements' => [
                'storageLocationForIn' => true,
                'sourceLocationForOut' => true,
                'usedReturnAvailable' => $condition === WarehouseItemCondition::NEW,
            ],
            'pendingShipments' => WarehouseLocationShipment::query()
                ->with(['consumable', 'sender'])
                ->waitingValidation()
                ->latest('sent_at')
                ->limit(5)
                ->get(),
            'pendingShipmentCount' => WarehouseLocationShipment::query()->waitingValidation()->count(),
        ]);
    }

    private function transactionPayload(WarehouseStockTransaction $transaction): array
    {
        $transaction->loadMissing('consumable');

        return [
            'id' => $transaction->getKey(),
            'transaction_number' => $transaction->transaction_number,
            'transaction_type' => $transaction->transaction_type?->value,
            'item_condition' => $transaction->item_condition?->value,
            'item' => $transaction->consumable?->item_name,
            'quantity' => (string) $transaction->quantity,
            'stock_before' => (string) $transaction->stock_before,
            'stock_after' => (string) $transaction->stock_after,
            'from_location' => $transaction->from_location,
            'to_location' => $transaction->to_location,
            'operation_key' => $transaction->operation_key,
            'location_shipment_id' => $transaction->location_shipment_id,
            'storage_location' => $transaction->to_location ?? $transaction->from_location,
            'verified_user_name' => $transaction->verified_user_name,
            'verified_user_section' => $transaction->verified_user_section,
            'transaction_at' => optional($transaction->transaction_at)->toIso8601String(),
        ];
    }

    private function domainError(Request $request, WarehouseDomainException $exception, string $key = 'transaction')
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors], $exception->status);
        }

        return back()->withInput()->withErrors([$key => $exception->getMessage()]);
    }
}
