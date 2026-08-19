<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseQuantity;
use App\Services\Warehouse\WarehouseStockReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WarehouseCatalogController extends Controller
{
    public function index(Request $request, WarehouseAccessService $access, WarehouseStockReservationService $reservations): JsonResponse
    {
        abort_unless(
            $access->can($request->user(), 'warehouse.stock-in.create')
            || $access->can($request->user(), 'warehouse.stock-in.validate')
            || $access->can($request->user(), 'warehouse.stock-out.create')
            || $access->can($request->user(), 'warehouse.location-shipment.create')
            || $access->can($request->user(), 'warehouse.location-shipment.validate'),
            403,
        );

        $search = trim((string) $request->query('search', ''));
        $query = WarehouseConsumable::query()->with('category:id,name')->where('is_active', true);
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('item_name', 'like', '%'.$search.'%')
                    ->orWhere('item_code', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%')
                    ->orWhere('machine_type', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->orderBy('item_name')->paginate((int) config('warehouse.catalog.per_page', 16));
        $disk = Storage::disk((string) config('warehouse.photos.disk', 'public'));

        return response()->json([
            'data' => $paginator->getCollection()->map(static function (WarehouseConsumable $item) use ($disk, $reservations): array {
                $locationStock = static function (string $location, WarehouseItemCondition $condition) use ($item, $reservations): array {
                    $physical = $item->availableAt($location, $condition);
                    $reserved = $reservations->reserved((int) $item->getKey(), $location, $condition);

                    return [
                        'physical_stock' => $physical,
                        'reserved_stock' => $reserved,
                        'available_stock' => $reservations->available($item, $location, $condition),
                    ];
                };
                $newDs8 = $locationStock('DS8', WarehouseItemCondition::NEW);
                $newDeltamas = $locationStock('Deltamas', WarehouseItemCondition::NEW);
                $usedDs8 = $locationStock('DS8', WarehouseItemCondition::USED);
                $usedDeltamas = $locationStock('Deltamas', WarehouseItemCondition::USED);

                return [
                    'id' => $item->getKey(),
                    'item_code' => $item->item_code,
                    'barcode' => $item->barcode,
                    'item_name' => $item->item_name,
                    'category' => $item->category?->name,
                    'machine_type' => $item->machine_type,
                    'unit' => $item->unit,
                    'allow_fraction' => (bool) $item->allow_fraction,
                    'photo_url' => $item->photo_path ? $disk->url($item->photo_path) : null,
                    'stock_status' => $item->stock_status,
                    'current_stock' => (string) $item->current_stock,
                    'physical_stock' => (string) $item->current_stock,
                    'reserved_stock' => WarehouseQuantity::add(
                        WarehouseQuantity::add($newDs8['reserved_stock'], $newDeltamas['reserved_stock']),
                        WarehouseQuantity::add($usedDs8['reserved_stock'], $usedDeltamas['reserved_stock']),
                    ),
                    'available_stock' => WarehouseQuantity::add($newDs8['available_stock'], $newDeltamas['available_stock']),
                    'locations' => [
                        'DS8' => [
                            'total' => (string) $item->stock_ds8,
                            'new' => $newDs8['available_stock'],
                            'used' => (string) $item->stock_used_ds8,
                            'new_physical_stock' => $newDs8['physical_stock'],
                            'new_reserved_stock' => $newDs8['reserved_stock'],
                            'new_available_stock' => $newDs8['available_stock'],
                            'used_physical_stock' => $usedDs8['physical_stock'],
                            'used_reserved_stock' => $usedDs8['reserved_stock'],
                            'used_available_stock' => $usedDs8['available_stock'],
                        ],
                        'Deltamas' => [
                            'total' => (string) $item->stock_deltamas,
                            'new' => $newDeltamas['available_stock'],
                            'used' => (string) $item->stock_used_deltamas,
                            'new_physical_stock' => $newDeltamas['physical_stock'],
                            'new_reserved_stock' => $newDeltamas['reserved_stock'],
                            'new_available_stock' => $newDeltamas['available_stock'],
                            'used_physical_stock' => $usedDeltamas['physical_stock'],
                            'used_reserved_stock' => $usedDeltamas['reserved_stock'],
                            'used_available_stock' => $usedDeltamas['available_stock'],
                        ],
                    ],
                ];
            })->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
