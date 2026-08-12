<?php

namespace App\Http\Controllers\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\ReverseWarehouseTransactionRequest;
use App\Http\Requests\Warehouse\StoreWarehouseTransactionRequest;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseStockService;
use App\Services\Warehouse\WarehouseVerifierPolicy;
use Illuminate\Http\Request;

class WarehouseTransactionController extends Controller
{
    public function create(Request $request, WarehouseAccessService $access)
    {
        $canStockIn = $access->can($request->user(), 'warehouse.stock-in.create');
        $canStockOut = $access->can($request->user(), 'warehouse.stock-out.create');
        $availableTypes = array_values(array_filter([
            $canStockIn ? WarehouseTransactionType::IN->value : null,
            $canStockOut ? WarehouseTransactionType::OUT->value : null,
        ]));
        $requestedType = strtoupper(trim((string) $request->query('type', '')));
        $initialType = in_array($requestedType, $availableTypes, true)
            ? $requestedType
            : ($availableTypes[0] ?? WarehouseTransactionType::OUT->value);
        $initialBarcode = preg_replace('/[\x00-\x1F\x7F]/', '', (string) $request->query('barcode', ''));
        $initialBarcode = substr(trim($initialBarcode), 0, 120);

        return view('warehouse.transactions.create', [
            'canStockIn' => $canStockIn,
            'canStockOut' => $canStockOut,
            'availableTypes' => $availableTypes,
            'initialType' => $initialType,
            'initialBarcode' => $initialBarcode,
            'transactionRequirements' => [
                'storageLocationForIn' => (bool) config('warehouse.transaction.require_storage_location_for_in', true),
            ],
        ]);
    }

    public function store(
        StoreWarehouseTransactionRequest $request,
        WarehouseIdentityResolver $identity,
        WarehouseStockService $stockService,
    ) {
        try {
            $type = WarehouseTransactionType::from(strtoupper((string) $request->input('type')));
            $item = $identity->resolveItem((string) $request->input('item_barcode'));
            $verifiedUser = $identity->resolveUserForDirection(
                (string) $request->input('verified_code'),
                $type->value,
            );

            if ($item === null) {
                throw new WarehouseDomainException('Barcode item tidak ditemukan atau tidak aktif.', 422);
            }
            if ($verifiedUser === null) {
                throw new WarehouseDomainException('NPK karyawan tidak ditemukan atau tidak aktif.', 422);
            }

            $result = $stockService->execute(new WarehouseStockCommand(
                type: $type,
                consumableId: (int) $item->getKey(),
                quantity: (string) $request->input('quantity'),
                verifiedUserId: (int) $verifiedUser->getKey(),
                storageLocation: $request->input('storage_location'),
                idempotencyKey: (string) $request->input('idempotency_key'),
                createdBy: (int) $request->user()->getKey(),
                verificationCodeHash: $identity->hash((string) $request->input('verified_code')),
            ));

            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $this->transactionPayload($result->transaction),
                    'idempotent_replay' => $result->idempotentReplay,
                ], $result->idempotentReplay ? 200 : 201);
            }

            return redirect()->route('warehouse.transactions.show', $result->transaction)
                ->with('status', $result->idempotentReplay ? 'Transaksi sebelumnya dikembalikan.' : 'Transaksi berhasil dicatat.');
        } catch (WarehouseDomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors], $exception->status);
            }

            return back()->withInput()->withErrors(['transaction' => $exception->getMessage()]);
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

        return view('warehouse.transactions.show', [
            'transaction' => $transaction->load(['consumable', 'verifiedUser', 'creator', 'reversalOf']),
            'canReverse' => $access->can($request->user(), 'warehouse.transaction.reverse') && ! WarehouseStockTransaction::query()->where('reversal_of_id', $transaction->getKey())->exists(),
        ]);
    }

    public function reverseForm(Request $request, WarehouseStockTransaction $transaction, WarehouseAccessService $access)
    {
        abort_unless($access->can($request->user(), 'warehouse.transaction.reverse'), 403);

        return view('warehouse.transactions.reverse', ['transaction' => $transaction]);
    }

    public function reverse(
        ReverseWarehouseTransactionRequest $request,
        WarehouseStockTransaction $transaction,
        WarehouseIdentityResolver $identity,
        WarehouseStockService $stockService,
        WarehouseVerifierPolicy $verifierPolicy,
    ) {
        try {
            $verifiedUser = $identity->resolveUserForDirection(
                (string) $request->input('verified_code'),
                $verifierPolicy->directionForReversal($transaction),
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
            );

            if ($request->expectsJson()) {
                return response()->json(['data' => ['transaction_number' => $result->transaction->transaction_number, 'stock_after' => (string) $result->transaction->stock_after]], 201);
            }

            return redirect()->route('warehouse.transactions.show', $result->transaction)->with('status', 'Reversal berhasil dicatat.');
        } catch (WarehouseDomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], $exception->status);
            }

            return back()->withInput()->withErrors(['reversal' => $exception->getMessage()]);
        } catch (\InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['reversal' => $exception->getMessage()]);
        }
    }

    private function transactionPayload(WarehouseStockTransaction $transaction): array
    {
        return [
            'id' => $transaction->getKey(),
            'transaction_number' => $transaction->transaction_number,
            'transaction_type' => $transaction->transaction_type?->value,
            'item' => $transaction->consumable?->item_name,
            'quantity' => (string) $transaction->quantity,
            'stock_before' => (string) $transaction->stock_before,
            'stock_after' => (string) $transaction->stock_after,
            'storage_location' => $transaction->usage_location ?: $transaction->consumable?->storage_location,
            'verified_user_name' => $transaction->verified_user_name,
            'verified_user_section' => $transaction->verified_user_section,
            'transaction_at' => optional($transaction->transaction_at)->toIso8601String(),
        ];
    }
}
