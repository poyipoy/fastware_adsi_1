<?php

namespace App\Http\Controllers\Warehouse;

use App\Exceptions\WarehouseDomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseConsumableRequest;
use App\Http\Requests\Warehouse\StoreWarehouseOpeningBalanceRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseConsumableRequest;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseIdentityResolver;
use App\Services\Warehouse\WarehouseStockService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WarehouseConsumableController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseConsumable::query()->orderBy('item_name');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('item_code', 'like', '%'.$search.'%')
                    ->orWhere('item_name', 'like', '%'.$search.'%')
                    ->orWhere('machine_type', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $status = strtoupper((string) $request->query('status'));
            if ($status === 'ACTIVE') {
                $query->where('is_active', true);
            } elseif ($status === 'INACTIVE') {
                $query->where('is_active', false);
            }
        }

        return view('warehouse.consumables.index', [
            'consumables' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('warehouse.consumables.form', [
            'consumable' => new WarehouseConsumable(['is_active' => true, 'allow_fraction' => false]),
        ]);
    }

    public function store(StoreWarehouseConsumableRequest $request)
    {
        $data = $request->safe()->only([
            'item_code',
            'item_name',
            'machine_type',
            'minimum_stock',
            'maximum_stock',
        ]);

        $diskName = (string) config('warehouse.photos.disk', 'public');
        $photoPath = null;
        try {
            if ($request->hasFile('photo')) {
                $photoPath = $this->storePhoto($request->file('photo'), $diskName);
            }

            WarehouseConsumable::query()->create($data + [
                'barcode' => $data['item_code'],
                'unit' => 'pcs',
                'allow_fraction' => false,
                'category_id' => null,
                'description' => null,
                'photo_path' => $photoPath,
                'is_active' => true,
                'current_stock' => '0.000',
                'stock_deltamas' => '0.000',
                'stock_ds8' => '0.000',
                'stock_used_deltamas' => '0.000',
                'stock_used_ds8' => '0.000',
                'created_by' => $request->user()->getKey(),
                'updated_by' => $request->user()->getKey(),
            ]);
        } catch (\Throwable $exception) {
            if ($photoPath !== null) {
                Storage::disk($diskName)->delete($photoPath);
            }

            throw $exception;
        }

        return redirect()->route('warehouse.consumables.index')->with('status', 'Barang berhasil dibuat dengan stok awal 0.');
    }

    public function edit(WarehouseConsumable $consumable)
    {
        return view('warehouse.consumables.form', [
            'consumable' => $consumable,
        ]);
    }

    public function show(WarehouseConsumable $consumable)
    {
        return view('warehouse.consumables.show', ['consumable' => $consumable]);
    }

    public function update(UpdateWarehouseConsumableRequest $request, WarehouseConsumable $consumable)
    {
        $data = $request->safe()->only([
            'item_code',
            'item_name',
            'machine_type',
            'minimum_stock',
            'maximum_stock',
        ]);
        if ((string) $consumable->barcode === (string) $consumable->item_code) {
            $data['barcode'] = $data['item_code'];
        }
        $data['updated_by'] = $request->user()->getKey();
        $diskName = (string) config('warehouse.photos.disk', 'public');
        $oldPhotoPath = $consumable->photo_path;
        $newPhotoPath = null;
        try {
            if ($request->hasFile('photo')) {
                $newPhotoPath = $this->storePhoto($request->file('photo'), $diskName);
                $data['photo_path'] = $newPhotoPath;
            }
            DB::transaction(function () use ($consumable, $data): void {
                $consumable->fill($data)->save();
            });
        } catch (\Throwable $exception) {
            if ($newPhotoPath !== null) {
                Storage::disk($diskName)->delete($newPhotoPath);
            }

            throw $exception;
        }
        if ($newPhotoPath !== null && $oldPhotoPath !== null && $oldPhotoPath !== $newPhotoPath) {
            Storage::disk($diskName)->delete($oldPhotoPath);
        }

        return redirect()->route('warehouse.consumables.index')->with('status', 'Master consumable diperbarui. Stok berjalan tidak diubah.');
    }

    private function storePhoto(UploadedFile $photo, string $diskName): string
    {
        if (! $photo->isValid()) {
            throw ValidationException::withMessages([
                'photo' => 'File foto tidak dapat dibaca. Silakan pilih ulang file foto.',
            ]);
        }

        $sourcePath = $photo->getRealPath();
        if (! is_string($sourcePath) || $sourcePath === '' || ! is_file($sourcePath) || ! is_readable($sourcePath)) {
            $sourcePath = $photo->getPathname();
        }

        if (! is_string($sourcePath) || $sourcePath === '' || ! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw ValidationException::withMessages([
                'photo' => 'File foto tidak tersedia untuk disimpan. Silakan pilih ulang file foto.',
            ]);
        }

        $directory = trim((string) config('warehouse.photos.directory', 'warehouse/consumables'), '/');
        if ($directory === '') {
            throw new \RuntimeException('Direktori foto Warehouse belum dikonfigurasi.');
        }

        $storedPath = $directory.'/'.Str::random(40);
        if ($extension = $photo->guessExtension()) {
            $storedPath .= '.'.$extension;
        }

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw ValidationException::withMessages([
                'photo' => 'File foto tidak dapat dibuka. Silakan pilih ulang file foto.',
            ]);
        }

        try {
            if (! Storage::disk($diskName)->put($storedPath, $stream)) {
                throw new \RuntimeException('Foto Warehouse gagal disimpan.');
            }
        } finally {
            fclose($stream);
        }

        return $storedPath;
    }

    public function toggleStatus(Request $request, WarehouseConsumable $consumable)
    {
        $consumable->forceFill([
            'is_active' => ! $consumable->is_active,
            'updated_by' => $request->user()->getKey(),
        ])->save();

        return back()->with('status', $consumable->is_active ? 'Consumable diaktifkan.' : 'Consumable dinonaktifkan.');
    }

    public function openingBalance(StoreWarehouseOpeningBalanceRequest $request, WarehouseConsumable $consumable, WarehouseStockService $stockService, WarehouseIdentityResolver $identity)
    {
        try {
            $verified = $identity->resolveUserForDirection(
                (string) $request->input('verified_code'),
                'IN',
                true,
            );
            if ($verified === null) {
                return back()->withInput()->withErrors(['verified_code' => 'NPK karyawan tidak ditemukan atau tidak aktif.']);
            }

            $result = $stockService->adjust(
                actorId: (int) $request->user()->getKey(),
                verifiedUserId: (int) $verified->getKey(),
                consumableId: (int) $consumable->getKey(),
                quantity: (string) $request->input('quantity'),
                direction: 'IN',
                reasonCategory: 'opening_balance',
                reason: (string) $request->input('reason'),
                idempotencyKey: $request->input('idempotency_key'),
                itemCondition: \App\Enums\Warehouse\WarehouseItemCondition::NEW,
                storageLocation: (string) $request->input('storage_location'),
            );
        } catch (WarehouseDomainException|\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['verified_code' => $exception->getMessage()]);
        }

        return redirect()->route('warehouse.consumables.index')->with('status', 'Opening balance dicatat sebagai movement '.$result->transaction->transaction_number.'.');
    }
}
