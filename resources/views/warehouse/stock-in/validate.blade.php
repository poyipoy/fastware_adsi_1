@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/stock-in.css')
@endpush

@section('warehouse-content')
    @php
        $stockInPhotoUrl = $stockIn->consumable?->photo_path
            ? \Illuminate\Support\Facades\Storage::disk(config('warehouse.photos.disk', 'public'))->url($stockIn->consumable->photo_path)
            : null;
        $quantityStep = $stockIn->consumable?->allow_fraction ? '0.001' : '1';
    @endphp
    <div class="warehouse-stock-in-page" aria-labelledby="warehouse-stock-in-validate-title" data-warehouse-stock-in-validation>
        <x-warehouse.page-header title="Validasi Stock In" subtitle="Cocokkan data pengajuan dengan barang fisik yang telah tiba di gudang.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.stock-in.show', $stockIn) }}">Kembali ke Detail</a>
        </x-warehouse.page-header>

        @if ($errors->any())<div class="alert alert-danger" role="alert"><strong>Validasi belum dapat disimpan.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="warehouse-stock-in-validation-grid">
            <section class="warehouse-panel">
                <div class="warehouse-panel-header"><div><h2 id="warehouse-stock-in-validate-title">Data Penerimaan Tercatat</h2><p>Data ini ditampilkan sebagai acuan pengecekan fisik dan tidak dapat diubah di sini.</p></div></div>
                <div class="warehouse-panel-body">
                    <div class="warehouse-stock-in-item-identity warehouse-stock-in-item-identity-compact">
                        <span class="warehouse-stock-in-item-photo">@if($stockInPhotoUrl)<img src="{{ $stockInPhotoUrl }}" alt="Foto {{ $stockIn->consumable?->item_name }}" width="128" height="88">@else<span aria-hidden="true">WH</span>@endif</span>
                        <div><span class="warehouse-eyebrow">Barang</span><h3>{{ $stockIn->consumable?->item_name ?? '—' }}</h3><p class="warehouse-stock-in-item-code mb-0">{{ $stockIn->consumable?->item_code ?? '—' }}</p></div>
                    </div>
                    <dl class="warehouse-stock-in-readonly">
                        <div><dt>Nomor Stock In</dt><dd class="font-monospace">{{ $stockIn->stock_in_number }}</dd></div>
                        <div><dt>User pembuat</dt><dd>{{ $stockIn->creator_name_snapshot }}{{ $stockIn->creator_npk_snapshot ? ' · NPK '.$stockIn->creator_npk_snapshot : '' }}</dd></div>
                        <div><dt>Kondisi</dt><dd>{{ $stockIn->item_condition?->label() }}</dd></div>
                        <div><dt>Qty input</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_expected) }} {{ $stockIn->consumable?->unit }}</dd></div>
                        <div><dt>Lokasi tujuan</dt><dd>{{ $stockIn->destination_location }}</dd></div>
                        <div><dt>Sumber</dt><dd>{{ $stockIn->source_location ?: 'Supplier / eksternal' }}</dd></div>
                        <div><dt>Tanggal dibuat</dt><dd>{{ optional($stockIn->created_at)->format('Y-m-d H:i') }}</dd></div>
                        <div class="warehouse-stock-in-form-field-full"><dt>Catatan</dt><dd>{{ $stockIn->notes ?: 'Tidak ada catatan.' }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="warehouse-panel">
                <div class="warehouse-panel-header"><div><h2>Pemeriksaan Fisik Barang</h2><p>Pemeriksaan fisik akan dicatat atas nama akun yang sedang aktif.</p></div></div>
                <form method="POST" action="{{ route('warehouse.stock-in.validate', $stockIn) }}" class="warehouse-panel-body" data-warehouse-stock-in-validation-form data-expected-quantity="{{ \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_expected) }}" data-scan-item-url="{{ route('warehouse.scans.item') }}">
                    @csrf
                    <div class="warehouse-form-field mb-3"><label class="form-label" for="stock-in-validator-account">Petugas Pemeriksa (Validator)</label><input class="form-control" id="stock-in-validator-account" value="{{ auth()->user()?->name }}{{ auth()->user()?->npk ? ' · NPK '.auth()->user()->npk : '' }}" readonly aria-readonly="true"><div class="warehouse-help">Menggunakan akun Anda yang sedang aktif dan memiliki hak validasi.</div></div>
                    <div class="warehouse-form-field mb-3"><label class="form-label warehouse-required" for="stock-in-received-item">Scan Item Code barang fisik</label><input class="form-control" id="stock-in-received-item" name="received_item_barcode" value="{{ old('received_item_barcode', $stockIn->consumable?->barcode ?: $stockIn->consumable?->item_code) }}" autocomplete="off" required><div class="warehouse-help">Barang fisik yang dipindai harus sesuai dengan data barang yang diajukan.</div></div>
                    <fieldset class="warehouse-form-field mb-3"><legend class="form-label warehouse-required">Kesesuaian Jumlah</legend><div class="warehouse-stock-in-result-options"><label class="warehouse-stock-in-result-option"><input type="radio" name="validation_result" value="MATCH" @checked(old('validation_result', 'MATCH') === 'MATCH' ) data-warehouse-validation-result><span><strong>Sesuai</strong><small class="d-block warehouse-muted">Jumlah fisik sama persis dengan yang diajukan.</small></span></label><label class="warehouse-stock-in-result-option"><input type="radio" name="validation_result" value="MANUAL_ADJUSTMENT" @checked(old('validation_result') === 'MANUAL_ADJUSTMENT') data-warehouse-validation-result><span><strong>Ada Selisih / Input Manual</strong><small class="d-block warehouse-muted">Jumlah fisik berbeda dan perlu disesuaikan.</small></span></label></div></fieldset>
                    <div class="warehouse-form-field mb-3">
                        <label class="form-label warehouse-required" for="stock-in-received-quantity">Qty aktual diterima</label>
                        <input class="form-control" id="stock-in-received-quantity" name="quantity_received" type="number" min="{{ $quantityStep }}" step="{{ $quantityStep }}" value="{{ \App\Services\Warehouse\WarehouseQuantity::display(old('validation_result', 'MATCH') === 'MATCH' ? $stockIn->quantity_expected : old('quantity_received', $stockIn->quantity_expected)) }}" required inputmode="{{ $stockIn->consumable?->allow_fraction ? 'decimal' : 'numeric' }}" data-warehouse-validation-quantity @readonly(old('validation_result', 'MATCH') === 'MATCH')>
                        <div class="warehouse-help" data-warehouse-validation-quantity-help>{{ old('validation_result', 'MATCH') === 'MATCH' ? 'Terkunci sesuai data pengajuan. Pilih "Ada Selisih / Input Manual" untuk mengubah.' : 'Masukkan jumlah fisik aktual yang diterima.' }}</div>
                        <div class="warehouse-stock-in-difference mt-2" aria-live="polite"><span>Selisih</span><strong data-warehouse-validation-difference>0</strong></div>
                    </div>
                    <div class="warehouse-form-field mb-3" data-warehouse-validation-notes-wrap>
                        <label class="form-label {{ old('validation_result') === 'MANUAL_ADJUSTMENT' ? 'warehouse-required' : '' }}" for="stock-in-validation-notes" data-warehouse-validation-notes-label>Catatan Validasi <span class="warehouse-muted">(wajib jika ada selisih)</span></label>
                        <textarea class="form-control" id="stock-in-validation-notes" name="validation_notes" rows="4" maxlength="65535" {{ old('validation_result') === 'MANUAL_ADJUSTMENT' ? 'required' : '' }}>{{ old('validation_notes') }}</textarea>
                    </div>
                    <div class="warehouse-stock-in-actions"><input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) str()->uuid()) }}"><button class="btn btn-primary" type="submit">Validasi Stock In</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.stock-in.show', $stockIn) }}">Batal</a></div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/stock-in.js')
@endpush
