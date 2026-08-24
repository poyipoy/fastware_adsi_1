@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/stock-in.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-stock-in-page" aria-labelledby="warehouse-stock-in-validate-title" data-warehouse-stock-in-validation>
        <x-warehouse.page-header title="Validasi Stock In" subtitle="Bandingkan data awal dengan barang fisik yang telah datang. Validator menggunakan akun masing-masing.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.stock-in.show', $stockIn) }}">Kembali ke Detail</a>
        </x-warehouse.page-header>

        @if ($errors->any())<div class="alert alert-danger" role="alert"><strong>Validasi belum dapat disimpan.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="warehouse-stock-in-validation-grid">
            <section class="warehouse-panel">
                <div class="warehouse-panel-header"><div><h2 id="warehouse-stock-in-validate-title">Data Stock In (read-only)</h2><p>Quantity expected tidak dapat diubah saat validasi.</p></div></div>
                <div class="warehouse-panel-body">
                    <dl class="warehouse-stock-in-readonly">
                        <div><dt>Nomor Stock In</dt><dd class="font-monospace">{{ $stockIn->stock_in_number }}</dd></div>
                        <div><dt>User pembuat</dt><dd>{{ $stockIn->creator_name_snapshot }}{{ $stockIn->creator_npk_snapshot ? ' · NPK '.$stockIn->creator_npk_snapshot : '' }}</dd></div>
                        <div><dt>Item</dt><dd>{{ $stockIn->consumable?->item_name }} · {{ $stockIn->consumable?->item_code }}</dd></div>
                        <div><dt>Condition</dt><dd>{{ $stockIn->item_condition?->label() }}</dd></div>
                        <div><dt>Qty input</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_expected) }} {{ $stockIn->consumable?->unit }}</dd></div>
                        <div><dt>Lokasi tujuan</dt><dd>{{ $stockIn->destination_location }}</dd></div>
                        <div><dt>Sumber</dt><dd>{{ $stockIn->source_location ?: 'Supplier / eksternal' }}</dd></div>
                        <div><dt>Tanggal dibuat</dt><dd>{{ optional($stockIn->created_at)->format('Y-m-d H:i') }}</dd></div>
                        <div class="warehouse-stock-in-form-field-full"><dt>Catatan</dt><dd>{{ $stockIn->notes ?: 'Tidak ada catatan.' }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="warehouse-panel">
                <div class="warehouse-panel-header"><div><h2>Hasil pengecekan fisik</h2><p>Validator ditentukan dari akun yang sedang login.</p></div></div>
                <form method="POST" action="{{ route('warehouse.stock-in.validate', $stockIn) }}" class="warehouse-panel-body" data-warehouse-stock-in-validation-form data-expected-quantity="{{ \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_expected) }}" data-scan-item-url="{{ route('warehouse.scans.item') }}">
                    @csrf
                    <div class="warehouse-form-field mb-3"><label class="form-label" for="stock-in-validator-account">Validator</label><input class="form-control" id="stock-in-validator-account" value="{{ auth()->user()?->name }}{{ auth()->user()?->npk ? ' · NPK '.auth()->user()->npk : '' }}" readonly aria-readonly="true"><div class="warehouse-help">Akun validator restricted yang sedang login digunakan otomatis.</div></div>
                    <div class="warehouse-form-field mb-3"><label class="form-label warehouse-required" for="stock-in-received-item">Scan Item Code barang fisik</label><input class="form-control" id="stock-in-received-item" name="received_item_barcode" value="{{ old('received_item_barcode', $stockIn->consumable?->barcode ?: $stockIn->consumable?->item_code) }}" autocomplete="off" required><div class="warehouse-help">Item fisik harus sama dengan item Stock In.</div></div>
                    <fieldset class="warehouse-form-field mb-3"><legend class="form-label warehouse-required">Hasil quantity</legend><div class="warehouse-stock-in-result-options"><label class="warehouse-stock-in-result-option"><input type="radio" name="validation_result" value="MATCH" @checked(old('validation_result', 'MATCH') === 'MATCH' ) data-warehouse-validation-result><span><strong>Sesuai</strong><small class="d-block warehouse-muted">Gunakan bila quantity fisik sama.</small></span></label><label class="warehouse-stock-in-result-option"><input type="radio" name="validation_result" value="MANUAL_ADJUSTMENT" @checked(old('validation_result') === 'MANUAL_ADJUSTMENT') data-warehouse-validation-result><span><strong>Input Manual</strong><small class="d-block warehouse-muted">Gunakan quantity aktual.</small></span></label></div></fieldset>
                    <div class="warehouse-form-field mb-3"><label class="form-label warehouse-required" for="stock-in-received-quantity">Qty aktual diterima</label><input class="form-control" id="stock-in-received-quantity" name="quantity_received" type="number" min="1" step="1" value="{{ \App\Services\Warehouse\WarehouseQuantity::display(old('quantity_received', $stockIn->quantity_expected)) }}" required inputmode="numeric" data-warehouse-validation-quantity><div class="warehouse-stock-in-difference mt-2" aria-live="polite"><span>Selisih</span><strong data-warehouse-validation-difference>0</strong></div></div>
                    <div class="warehouse-form-field mb-3" data-warehouse-validation-notes-wrap><label class="form-label" for="stock-in-validation-notes">Catatan Validasi <span class="warehouse-muted">(wajib jika ada selisih)</span></label><textarea class="form-control" id="stock-in-validation-notes" name="validation_notes" rows="4" maxlength="65535">{{ old('validation_notes') }}</textarea></div>
                    <div class="warehouse-stock-in-actions"><input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) str()->uuid()) }}"><button class="btn btn-primary" type="submit">Validasi Stock In</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.stock-in.show', $stockIn) }}">Batal</a></div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/stock-in.js')
@endpush
