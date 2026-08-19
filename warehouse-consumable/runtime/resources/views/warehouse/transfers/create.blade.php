@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-transfer-title">
        <x-warehouse.page-header title="Transfer Stok Antar Lokasi" subtitle="Pindahkan stok Baru atau Bekas tanpa mengubah stok total.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert"><strong>Transfer belum dapat diproses.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form class="warehouse-panel warehouse-transfer-form" method="POST" action="{{ route('warehouse.transfers.store') }}" data-warehouse-transfer-form data-scan-user-url="{{ route('warehouse.scans.user') }}">
            @csrf
            <div class="warehouse-panel-body">
                <div class="warehouse-detail-grid">
                    <div class="warehouse-form-field warehouse-detail-full">
                        <label class="form-label warehouse-required" for="transfer-item">Barang</label>
                        <select class="form-select" id="transfer-item" name="consumable_id" required data-transfer-item>
                            <option value="">Pilih barang</option>
                            @foreach($consumables as $item)
                                <option
                                    value="{{ $item->id }}"
                                    data-unit="{{ $item->unit }}"
                                    data-allow-fraction="{{ $item->allow_fraction ? '1' : '0' }}"
                                    data-total-ds8="{{ $item->stock_ds8 }}"
                                    data-new-ds8="{{ $item->availableAt('DS8', \App\Enums\Warehouse\WarehouseItemCondition::NEW) }}"
                                    data-used-ds8="{{ $item->stock_used_ds8 }}"
                                    data-total-deltamas="{{ $item->stock_deltamas }}"
                                    data-new-deltamas="{{ $item->availableAt('Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW) }}"
                                    data-used-deltamas="{{ $item->stock_used_deltamas }}"
                                    @selected((string) old('consumable_id') === (string) $item->id)
                                >{{ $item->item_name }} · {{ $item->item_code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="warehouse-form-field"><label class="form-label warehouse-required" for="transfer-condition">Kondisi</label><select class="form-select" id="transfer-condition" name="item_condition" required data-transfer-condition><option value="NEW" @selected(old('item_condition', 'NEW') === 'NEW')>Baru</option><option value="USED" @selected(old('item_condition') === 'USED')>Bekas</option></select></div>
                    <div class="warehouse-form-field"><label class="form-label warehouse-required" for="transfer-quantity">Jumlah</label><input class="form-control" id="transfer-quantity" name="quantity" type="number" min="0.001" step="0.001" value="{{ old('quantity') }}" required data-transfer-quantity></div>
                    <div class="warehouse-form-field"><label class="form-label warehouse-required" for="transfer-from">Lokasi asal</label><select class="form-select" id="transfer-from" name="from_location" required data-transfer-from>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}" @selected(old('from_location', 'DS8') === $location)>{{ $location }}</option>@endforeach</select></div>
                    <div class="warehouse-form-field"><label class="form-label warehouse-required" for="transfer-to">Lokasi tujuan</label><select class="form-select" id="transfer-to" name="to_location" required data-transfer-to>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}" @selected(old('to_location', 'Deltamas') === $location)>{{ $location }}</option>@endforeach</select></div>

                    <section class="warehouse-transfer-balance warehouse-detail-full" aria-labelledby="warehouse-transfer-balance-title" data-transfer-balance>
                        <div class="warehouse-transfer-balance-heading"><div><h2 id="warehouse-transfer-balance-title">Saldo per lokasi</h2><p>Pilih barang untuk melihat saldo Baru dan Bekas yang tersedia.</p></div><span data-transfer-unit>—</span></div>
                        <div class="warehouse-transfer-location-grid">
                            <article><h3>DS8</h3><dl><div><dt>Baru</dt><dd data-transfer-new-ds8>—</dd></div><div><dt>Bekas</dt><dd data-transfer-used-ds8>—</dd></div><div><dt>Total</dt><dd data-transfer-total-ds8>—</dd></div></dl></article>
                            <article><h3>Deltamas</h3><dl><div><dt>Baru</dt><dd data-transfer-new-deltamas>—</dd></div><div><dt>Bekas</dt><dd data-transfer-used-deltamas>—</dd></div><div><dt>Total</dt><dd data-transfer-total-deltamas>—</dd></div></dl></article>
                        </div>
                        <div class="warehouse-transfer-projection" data-transfer-projection aria-live="polite"><div><span>Tersedia di asal</span><strong data-transfer-available>—</strong></div><div><span>Setelah transfer</span><strong data-transfer-after>—</strong></div><p data-transfer-message>Pilih barang dan masukkan jumlah transfer.</p></div>
                    </section>

                    <div class="warehouse-form-field warehouse-detail-full"><label class="form-label" for="transfer-notes">Catatan</label><textarea class="form-control" id="transfer-notes" name="notes" rows="3">{{ old('notes') }}</textarea></div>
                    <div class="warehouse-form-field warehouse-detail-full">
                        <label class="form-label warehouse-required" for="transfer-verifier">Pindai barcode NPK verifikator</label>
                        <div class="input-group warehouse-scan-group"><input class="form-control font-monospace" id="transfer-verifier" name="verified_code" inputmode="numeric" autocomplete="off" required data-transfer-verifier><button class="btn btn-outline-primary" type="button" data-transfer-verify>Verifikasi</button></div>
                        <div class="warehouse-help">Hanya RAGIL ISHA RAHMANTO atau ARY RODJO PRASETYO yang dapat memverifikasi.</div>
                        <div class="warehouse-transfer-verifier-result" data-transfer-verifier-result role="status" aria-live="polite">Lengkapi rincian transfer sebelum memindai NPK.</div>
                    </div>
                    <label class="warehouse-confirm-check warehouse-detail-full"><input type="checkbox" data-transfer-confirm><span>Saya telah memeriksa barang, kondisi, lokasi, jumlah, dan verifikator.</span></label>
                </div>
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) str()->uuid()) }}">
            </div>
            <div class="card-footer d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit" data-transfer-submit>Simpan Transfer</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Batal</a></div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/transfer-form.js')
@endpush
