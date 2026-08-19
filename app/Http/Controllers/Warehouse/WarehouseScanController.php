<?php

namespace App\Http\Controllers\Warehouse;

use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\ScanWarehouseItemRequest;
use App\Http\Requests\Warehouse\ScanWarehouseUserRequest;
use App\Services\Warehouse\WarehouseIdentityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class WarehouseScanController extends Controller
{
    public function scanItem(ScanWarehouseItemRequest $request, WarehouseIdentityResolver $resolver): JsonResponse
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

        return response()->json(['data' => [
            'id' => $item->getKey(), 'item_code' => $item->item_code, 'barcode' => $item->barcode,
            'item_name' => $item->item_name, 'category' => $item->category?->name, 'unit' => $item->unit,
            'machine_type' => $item->machine_type, 'photo_url' => $photoUrl,
            'allow_fraction' => (bool) $item->allow_fraction, 'current_stock' => (string) $item->current_stock,
            'minimum_stock' => (string) $item->minimum_stock,
            'stock_ds8' => (string) $item->stock_ds8, 'stock_deltamas' => (string) $item->stock_deltamas,
            'stock_new_ds8' => $item->availableAt('DS8', \App\Enums\Warehouse\WarehouseItemCondition::NEW),
            'stock_new_deltamas' => $item->availableAt('Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW),
            'stock_used_ds8' => (string) $item->stock_used_ds8, 'stock_used_deltamas' => (string) $item->stock_used_deltamas,
            'stock' => [
                'DS8' => [
                    'new' => $item->availableAt('DS8', \App\Enums\Warehouse\WarehouseItemCondition::NEW),
                    'used' => (string) $item->stock_used_ds8,
                    'total' => (string) $item->stock_ds8,
                ],
                'Deltamas' => [
                    'new' => $item->availableAt('Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW),
                    'used' => (string) $item->stock_used_deltamas,
                    'total' => (string) $item->stock_deltamas,
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
            $user = $resolver->resolveUserForDirection(
                $code,
                $type === 'ADJUSTMENT' ? 'OUT' : $type,
                $type === 'ADJUSTMENT',
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
