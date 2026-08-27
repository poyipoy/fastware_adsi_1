<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\Warehouse\WarehouseStockInStatus;
use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseStockIn;
use App\Services\Warehouse\WarehouseVerifierPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class WarehouseStockValidationController extends Controller
{
    public function index(Request $request, WarehouseVerifierPolicy $verifierPolicy)
    {
        abort_unless($verifierPolicy->canAccessValidationWorkspace($request->user()), 403);

        $pendingStockIns = WarehouseStockIn::query()
            ->with(['consumable', 'creator'])
            ->where('status', WarehouseStockInStatus::WAITING_VALIDATION->value)
            ->latest('created_at')
            ->limit(50)
            ->get();

        $validatedStockIns = WarehouseStockIn::query()
            ->with(['consumable', 'creator', 'validator'])
            ->where('status', WarehouseStockInStatus::VALIDATED->value)
            ->latest('validated_at')
            ->limit(50)
            ->get();

        return view('warehouse.validations.index', [
            'pending' => $this->records($pendingStockIns, true),
            'validated' => $this->records($validatedStockIns, false),
        ]);
    }

    private function records(Collection $stockIns, bool $pending): Collection
    {
        return $stockIns
            ->map(function (WarehouseStockIn $stockIn) use ($pending): array {
                $isInternalTransfer = trim((string) $stockIn->source_location) !== '';

                return [
                    'kind' => $isInternalTransfer ? 'Transfer Antar Lokasi' : 'Stock In',
                    'reference' => $stockIn->stock_in_number,
                    'created_at' => $pending ? $stockIn->created_at : ($stockIn->validated_at ?? $stockIn->created_at),
                    'item' => $stockIn->consumable?->item_name,
                    'item_code' => $stockIn->consumable?->item_code,
                    'condition' => $stockIn->item_condition?->label() ?? 'Baru',
                    'quantity' => (string) ($pending ? $stockIn->quantity_expected : ($stockIn->quantity_received ?? $stockIn->quantity_expected)),
                    'unit' => $stockIn->consumable?->unit,
                    'location' => $isInternalTransfer
                        ? $stockIn->source_location.' → '.$stockIn->destination_location
                        : $stockIn->destination_location,
                    'actor' => $pending ? $stockIn->creator_name_snapshot : ($stockIn->validator_name_snapshot ?? '—'),
                    'status' => $pending ? 'Menunggu Validasi' : 'Tervalidasi',
                    'detail_url' => route('warehouse.stock-in.show', $stockIn),
                    'validation_url' => $pending ? route('warehouse.stock-in.validate-form', $stockIn) : null,
                ];
            })
            ->sortByDesc(static fn (array $record): int => optional($record['created_at'])->getTimestamp() ?? 0)
            ->values();
    }
}
