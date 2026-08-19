<?php

namespace App\Http\Controllers\Warehouse;

use App\Exceptions\WarehouseDomainException;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\ScanWarehouseItemRequest;
use App\Http\Requests\Warehouse\ScanWarehouseUserRequest;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseQuantity;
use App\Services\Warehouse\WarehouseStockReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class WarehouseScanController extends Controller
{
    public function scanItem(ScanWarehouseItemRequest $request, WarehouseIdentityResolver $resolver, WarehouseStockReservationService $reservations): JsonResponse
    {
        $code = (string) $request->input('code');
        try {
            $item = $resolver->resolveItem($code);
        } catch (\InvalidArgumentException $exception) {
            $resolver->logFailure($code, 'Invalid item scan', $request->ip(), $request->userAgent());

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($item === null) {
            $resolver->logFailure($code, 'Unknown or inactive item barcode', $request->ip(), $request->userAgent());

            return response()->json(['message' => 'Barcode item tidak ditemukan atau tidak aktif.'], 404);
        }

        $photoUrl = $item->photo_path
            ? Storage::disk((string) config('warehouse.photos.disk', 'public'))->url($item->photo_path)
            : null;
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

        return response()->json(['data' => [
            'id' => $item->getKey(), 'item_code' => $item->item_code, 'barcode' => $item->barcode,
            'item_name' => $item->item_name, 'category' => $item->category?->name, 'unit' => $item->unit,
            'machine_type' => $item->machine_type, 'photo_url' => $photoUrl,
            'allow_fraction' => (bool) $item->allow_fraction, 'current_stock' => (string) $item->current_stock,
            'physical_stock' => (string) $item->current_stock,
            'reserved_stock' => WarehouseQuantity::add(
                WarehouseQuantity::add($newDs8['reserved_stock'], $newDeltamas['reserved_stock']),
                WarehouseQuantity::add($usedDs8['reserved_stock'], $usedDeltamas['reserved_stock']),
            ),
            'available_stock' => WarehouseQuantity::add($newDs8['available_stock'], $newDeltamas['available_stock']),
            'minimum_stock' => (string) $item->minimum_stock,
            'stock_ds8' => (string) $item->stock_ds8, 'stock_deltamas' => (string) $item->stock_deltamas,
            'stock_new_ds8' => $newDs8['available_stock'],
            'stock_new_deltamas' => $newDeltamas['available_stock'],
            'stock_used_ds8' => (string) $item->stock_used_ds8, 'stock_used_deltamas' => (string) $item->stock_used_deltamas,
                'stock' => [
                'DS8' => [
                    'new' => $newDs8['available_stock'],
                    'used' => (string) $item->stock_used_ds8,
                    'total' => (string) $item->stock_ds8,
                    'new_physical_stock' => $newDs8['physical_stock'],
                    'new_reserved_stock' => $newDs8['reserved_stock'],
                    'new_available_stock' => $newDs8['available_stock'],
                    'used_physical_stock' => $usedDs8['physical_stock'],
                    'used_reserved_stock' => $usedDs8['reserved_stock'],
                    'used_available_stock' => $usedDs8['available_stock'],
                ],
                'Deltamas' => [
                    'new' => $newDeltamas['available_stock'],
                    'used' => (string) $item->stock_used_deltamas,
                    'total' => (string) $item->stock_deltamas,
                    'new_physical_stock' => $newDeltamas['physical_stock'],
                    'new_reserved_stock' => $newDeltamas['reserved_stock'],
                    'new_available_stock' => $newDeltamas['available_stock'],
                    'used_physical_stock' => $usedDeltamas['physical_stock'],
                    'used_reserved_stock' => $usedDeltamas['reserved_stock'],
                    'used_available_stock' => $usedDeltamas['available_stock'],
                ],
            ],
            'stock_status' => $item->stock_status,
        ]]);
    }

    public function scanUser(ScanWarehouseUserRequest $request, WarehouseIdentityResolver $resolver): JsonResponse
    {
        $code = (string) $request->input('code');
        try {
            $type = (string) $request->input('type');
            $restrictedStockIn = $type === 'STOCK_IN_VALIDATE';
            $user = $resolver->resolveUserForDirection(
                $code,
                in_array($type, ['ADJUSTMENT', 'STOCK_IN_VALIDATE'], true) ? ($type === 'STOCK_IN_VALIDATE' ? 'IN' : 'OUT') : $type,
                $type === 'ADJUSTMENT' || $restrictedStockIn,
            );
        } catch (\InvalidArgumentException $exception) {
            $resolver->logFailure($code, 'Invalid employee scan', $request->ip(), $request->userAgent());

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (WarehouseDomainException $exception) {
            $resolver->logFailure($code, $exception->getMessage(), $request->ip(), $request->userAgent());

            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        if ($user === null) {
            $resolver->logFailure($code, 'Unknown or inactive employee NPK', $request->ip(), $request->userAgent());

            return response()->json(['message' => 'NPK karyawan tidak ditemukan atau tidak aktif.'], 404);
        }

        return response()->json(['data' => [
            'id' => $user->getKey(), 'name' => $user->name, 'npk' => $user->npk,
            'section' => $user->section, 'status' => 'ACTIVE',
            'verification_token' => $resolver->hash($code),
        ]]);
    }
}
