<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WarehouseCatalogController extends Controller
{
    public function index(Request $request, WarehouseAccessService $access): JsonResponse
    {
        abort_unless(
            $access->can($request->user(), 'warehouse.stock-in.create')
            || $access->can($request->user(), 'warehouse.stock-out.create')
            || $access->can($request->user(), 'warehouse.transfer.create'),
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
            'data' => $paginator->getCollection()->map(static function (WarehouseConsumable $item) use ($disk): array {
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
                    'locations' => [
                        'DS8' => [
                            'total' => (string) $item->stock_ds8,
                            'new' => $item->availableAt('DS8', \App\Enums\Warehouse\WarehouseItemCondition::NEW),
                            'used' => (string) $item->stock_used_ds8,
                        ],
                        'Deltamas' => [
                            'total' => (string) $item->stock_deltamas,
                            'new' => $item->availableAt('Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW),
                            'used' => (string) $item->stock_used_deltamas,
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
