<?php

namespace App\Http\Controllers\Warehouse;

use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\ScanWarehouseItemRequest;
use App\Http\Requests\Warehouse\ScanWarehouseUserRequest;
use App\Services\Warehouse\WarehouseIdentityResolver;
use Illuminate\Http\JsonResponse;

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

        return response()->json(['data' => [
            'id' => $item->getKey(), 'item_code' => $item->item_code, 'barcode' => $item->barcode,
            'item_name' => $item->item_name, 'category' => $item->category?->name, 'unit' => $item->unit,
            'allow_fraction' => (bool) $item->allow_fraction, 'current_stock' => (string) $item->current_stock,
            'minimum_stock' => (string) $item->minimum_stock, 'storage_location' => $item->storage_location,
            'stock_status' => $item->stock_status,
        ]]);
    }

    public function scanUser(ScanWarehouseUserRequest $request, WarehouseIdentityResolver $resolver): JsonResponse
    {
        $code = (string) $request->input('code');
        try {
            $user = $resolver->resolveUserForDirection($code, (string) $request->input('type'));
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
