@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/stock-in.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-stock-in-page" aria-labelledby="warehouse-stock-in-create-title">
        <x-warehouse.page-header title="Buat Stock In" subtitle="Simpan quantity yang diharapkan terlebih dahulu. Saldo belum berubah sampai barang divalidasi secara fisik.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.stock-in.index') }}">Riwayat Stock In</a>
        </x-warehouse.page-header>

        @if ($errors->any())<div class="alert alert-danger" role="alert"><strong>Stock In belum dapat disimpan.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="warehouse-panel">
            <div class="warehouse-panel-header"><div><h2 id="warehouse-stock-in-create-title">Data penerimaan</h2><p>Lokasi dipilih pada transaksi dan bukan atribut permanen master barang.</p></div></div>
            <form method="POST" action="{{ route('warehouse.stock-in.store') }}" class="warehouse-panel-body" data-warehouse-stock-in-create-form>
                @csrf
                <div class="warehouse-stock-in-form-note mb-3" role="status">Stock In berhasil dibuat dengan status <strong>Menunggu Validasi</strong>. Stok tidak berubah sebelum Ragil atau Rodjo melakukan validasi.</div>
                <div class="warehouse-stock-in-form-grid">
                    <div class="warehouse-form-field warehouse-stock-in-form-field-full">
                        <label class="form-label warehouse-required" for="stock-in-item">Item</label>
                        <select class="form-select" id="stock-in-item" name="consumable_id" required data-warehouse-stock-in-item>
                            <option value="">Pilih item</option>
                            @foreach($consumables as $consumable)
                                <option value="{{ $consumable->id }}" data-item-barcode="{{ $consumable->barcode ?: $consumable->item_code }}" @selected((string) old('consumable_id', '') === (string) $consumable->id || ($initialBarcode !== '' && $initialBarcode === (string) ($consumable->barcode ?: $consumable->item_code)))>{{ $consumable->item_name }} · {{ $consumable->item_code }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="item_barcode" value="{{ old('item_barcode', $initialBarcode) }}" data-warehouse-stock-in-item-barcode>
                        <div class="warehouse-help">Pilih dari master atau pindai Item Code pada perangkat scanner sebelum menyimpan.</div>
                    </div>
                    <div class="warehouse-form-field">
                        <label class="form-label warehouse-required" for="stock-in-condition">Condition</label>
                        <select class="form-select" id="stock-in-condition" name="item_condition" required><option value="NEW" @selected(old('item_condition', 'NEW') === 'NEW')>Baru (NEW)</option><option value="USED" @selected(old('item_condition') === 'USED')>Bekas (USED)</option></select>
                    </div>
                    <div class="warehouse-form-field">
                        <label class="form-label warehouse-required" for="stock-in-quantity">Jumlah yang diharapkan</label>
                        <input class="form-control" id="stock-in-quantity" name="quantity_expected" type="number" min="0.001" step="0.001" value="{{ old('quantity_expected', 1) }}" required inputmode="decimal">
                    </div>
                    <div class="warehouse-form-field">
                        <label class="form-label warehouse-required" for="stock-in-destination">Lokasi tujuan</label>
                        <select class="form-select" id="stock-in-destination" name="destination_location" required>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}" @selected(old('destination_location', 'DS8') === $location)>{{ $location }}</option>@endforeach</select>
                    </div>
                    <div class="warehouse-form-field">
                        <label class="form-label" for="stock-in-source">Sumber internal <span class="warehouse-muted">(opsional)</span></label>
                        <select class="form-select" id="stock-in-source" name="source_location"><option value="">Supplier / eksternal</option>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}" @selected(old('source_location') === $location)>{{ $location }}</option>@endforeach</select>
                        <div class="warehouse-help">Isi hanya jika barang berasal dari lokasi Warehouse lain. Quantity akan di-reserve sampai validasi.</div>
                    </div>
                    <div class="warehouse-form-field warehouse-stock-in-form-field-full">
                        <label class="form-label" for="stock-in-notes">Catatan</label>
                        <textarea class="form-control" id="stock-in-notes" name="notes" rows="4" maxlength="65535">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="warehouse-stock-in-actions mt-3"><input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) str()->uuid()) }}"><button class="btn btn-primary" type="submit">Simpan Stock In</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.stock-in.index') }}">Batal</a></div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/stock-in.js')
@endpush
