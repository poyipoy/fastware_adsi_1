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

        <form method="POST" enctype="multipart/form-data" action="{{ $consumable->exists ? route('warehouse.consumables.update', $consumable) : route('warehouse.consumables.store') }}" class="warehouse-panel warehouse-form-card" aria-label="Formulir Master Consumable">
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
                        <div class="warehouse-form-field">
                            <label class="form-label" for="machine_type">Tipe mesin</label>
                            <input class="form-control" id="machine_type" name="machine_type" value="{{ old('machine_type', $consumable->machine_type) }}" maxlength="120" placeholder="Contoh: Cutting, Press, Welding">
                        </div>
                        <div class="warehouse-form-field">
                            <label class="form-label" for="photo">Foto barang</label>
                            <input class="form-control" id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="photo-help">
                            <div class="warehouse-help" id="photo-help">JPG, JPEG, PNG, atau WebP. Maksimal 5 MB. Kosongkan untuk mempertahankan foto saat ini.</div>
                            @if($consumable->photo_path)<img class="warehouse-master-photo-preview mt-2" src="{{ \Illuminate\Support\Facades\Storage::disk(config('warehouse.photos.disk', 'public'))->url($consumable->photo_path) }}" alt="Foto {{ $consumable->item_name }}" width="240" height="165">@endif
                        </div>
                    </div>
                </section>

                <section class="warehouse-form-section" aria-labelledby="warehouse-stock-rule-heading">
                    <div class="warehouse-form-section-heading">
                        <h2 id="warehouse-stock-rule-heading">Aturan stok</h2>
                        <p>Jumlah menggunakan satuan pcs tanpa desimal. Lokasi dipilih saat transaksi, bukan pada master. Stok saat ini tidak dapat diedit dari master.</p>
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
