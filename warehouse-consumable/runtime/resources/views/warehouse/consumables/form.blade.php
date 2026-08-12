@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-consumable-form-title">
        <x-warehouse.page-header title="{{ $consumable->exists ? 'Ubah Barang Habis Pakai' : 'Tambah Barang Habis Pakai' }}" subtitle="Isi identitas barang, batas stok, dan lokasi penyimpanan.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.consumables.index') }}">Kembali ke Master Consumable</a>
        </x-warehouse.page-header>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <strong>Periksa kembali data barang.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $consumable->exists ? route('warehouse.consumables.update', $consumable) : route('warehouse.consumables.store') }}" class="warehouse-panel warehouse-form-card" aria-label="Formulir Master Consumable">
            @csrf
            @if ($consumable->exists)
                @method('PUT')
            @endif

            <div class="warehouse-panel-body">
                <section class="warehouse-form-section" aria-labelledby="warehouse-identity-heading">
                    <div class="warehouse-form-section-heading">
                        <h2 id="warehouse-identity-heading">Identitas barang</h2>
                        <p>Item Code adalah nilai yang dibaca scanner dari barcode barang.</p>
                    </div>
                    <div class="warehouse-detail-grid">
                        <div class="warehouse-form-field">
                            <label class="form-label warehouse-required" for="item_code">Item Code</label>
                            <input class="form-control font-monospace" id="item_code" name="item_code" autocomplete="off" value="{{ old('item_code', $consumable->item_code) }}" required aria-describedby="item-code-help" @error('item_code') aria-invalid="true" @enderror>
                            <div class="warehouse-help" id="item-code-help">Masukkan tepat seperti hasil scan, termasuk huruf kapital, tanda hubung, dan angka nol.</div>
                        </div>
                        <div class="warehouse-form-field">
                            <label class="form-label warehouse-required" for="item_name">Nama barang</label>
                            <input class="form-control" id="item_name" name="item_name" value="{{ old('item_name', $consumable->item_name) }}" required @error('item_name') aria-invalid="true" @enderror>
                        </div>
                    </div>
                </section>

                <section class="warehouse-form-section" aria-labelledby="warehouse-stock-rule-heading">
                    <div class="warehouse-form-section-heading">
                        <h2 id="warehouse-stock-rule-heading">Aturan stok dan lokasi</h2>
                        <p>Jumlah menggunakan satuan pcs tanpa desimal. Stok saat ini tidak dapat diedit dari master.</p>
                    </div>
                    <div class="warehouse-detail-grid">
                        <div class="warehouse-form-field">
                            <label class="form-label warehouse-required" for="minimum_stock">Stok minimum</label>
                            <input class="form-control" id="minimum_stock" type="number" min="0" step="1" inputmode="numeric" name="minimum_stock" value="{{ old('minimum_stock', \App\Services\Warehouse\WarehouseQuantity::display($consumable->minimum_stock ?? '0')) }}" required @error('minimum_stock') aria-invalid="true" @enderror>
                        </div>
                        <div class="warehouse-form-field">
                            <label class="form-label" for="maximum_stock">Stok maksimum</label>
                            <input class="form-control" id="maximum_stock" type="number" min="0" step="1" inputmode="numeric" name="maximum_stock" value="{{ old('maximum_stock', $consumable->maximum_stock !== null ? \App\Services\Warehouse\WarehouseQuantity::display($consumable->maximum_stock) : '') }}" aria-describedby="maximum-stock-help" @error('maximum_stock') aria-invalid="true" @enderror>
                            <div class="warehouse-help" id="maximum-stock-help">Opsional. Jika diisi, nilainya tidak boleh di bawah stok minimum.</div>
                        </div>
                        <div class="warehouse-form-field warehouse-detail-full">
                            <label class="form-label" for="storage_location">Lokasi penyimpanan</label>
                            @php($selectedStorageLocation = old('storage_location', $consumable->storage_location))
                            <select class="form-select" id="storage_location" name="storage_location" aria-describedby="storage-location-help" @error('storage_location') aria-invalid="true" @enderror>
                                <option value="">Pilih lokasi penyimpanan</option>
                                @foreach ((array) config('warehouse.storage_locations', ['DS8', 'Deltamas']) as $storageLocation)
                                    <option value="{{ $storageLocation }}" @selected((string) $selectedStorageLocation === (string) $storageLocation)>{{ $storageLocation }}</option>
                                @endforeach
                            </select>
                            <div class="warehouse-help" id="storage-location-help">Opsional. Pilih DS8 atau Deltamas; lokasi akan menjadi lokasi aktif setelah Stock In berhasil.</div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="card-footer">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <a class="btn btn-outline-secondary" href="{{ route('warehouse.consumables.index') }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
