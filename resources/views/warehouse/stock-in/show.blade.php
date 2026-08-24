@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/stock-in.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-stock-in-page" aria-labelledby="warehouse-stock-in-detail-title">
        <x-warehouse.page-header title="Detail Stock In" subtitle="Audit penerimaan, validasi fisik, dan tautan mutasi ledger.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.create') }}">Kembali ke Stock In/Out Baru</a>
            @if($stockIn->canValidate() && $canValidateStockIn)<a class="btn btn-primary" href="{{ route('warehouse.stock-in.validate-form', $stockIn) }}">Validasi</a>@endif
        </x-warehouse.page-header>

        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

        <section class="warehouse-panel">
            <div class="warehouse-panel-header"><div><h2 id="warehouse-stock-in-detail-title">{{ $stockIn->stock_in_number }}</h2><p>Stock In dibuat {{ optional($stockIn->created_at)->format('Y-m-d H:i') }}</p></div><x-warehouse.status-badge :status="$stockIn->status?->value" context="stock-in" /></div>
            <div class="warehouse-panel-body">
                <dl class="warehouse-stock-in-readonly">
                    <div><dt>Item</dt><dd>{{ $stockIn->consumable?->item_name }} · {{ $stockIn->consumable?->item_code }}</dd></div>
                    <div><dt>Condition</dt><dd>{{ $stockIn->item_condition?->label() }}</dd></div>
                    <div><dt>Qty expected</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_expected) }} {{ $stockIn->consumable?->unit }}</dd></div>
                    <div><dt>Qty actual</dt><dd>{{ $stockIn->quantity_received === null ? 'Belum divalidasi' : \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_received).' '.$stockIn->consumable?->unit }}</dd></div>
                    <div><dt>Lokasi tujuan</dt><dd>{{ $stockIn->destination_location }}</dd></div>
                    <div><dt>Sumber internal</dt><dd>{{ $stockIn->source_location ?: 'Supplier / eksternal' }}</dd></div>
                    <div><dt>Dibuat oleh</dt><dd>{{ $stockIn->creator_name_snapshot }}{{ $stockIn->creator_npk_snapshot ? ' · NPK '.$stockIn->creator_npk_snapshot : '' }}</dd></div>
                    <div><dt>Hasil validasi</dt><dd>{{ $stockIn->validation_result?->label() ?: 'Belum divalidasi' }}</dd></div>
                    <div><dt>Validator</dt><dd>{{ $stockIn->validator_name_snapshot ? $stockIn->validator_name_snapshot.' · NPK '.$stockIn->validator_npk_snapshot : 'Belum divalidasi' }}</dd></div>
                    <div><dt>Ledger</dt><dd>{{ $stockIn->stock_transaction_id ? 'Transaksi #'.$stockIn->stock_transaction_id : 'Belum ada mutasi' }}</dd></div>
                    <div class="warehouse-stock-in-form-field-full"><dt>Catatan awal</dt><dd>{{ $stockIn->notes ?: 'Tidak ada catatan.' }}</dd></div>
                    <div class="warehouse-stock-in-form-field-full"><dt>Catatan validasi</dt><dd>{{ $stockIn->validation_notes ?: 'Belum ada catatan validasi.' }}</dd></div>
                </dl>
            </div>
            @if($stockIn->canCancel())
                @can('warehouse.stock-in.create')
                    <div class="card-footer"><details><summary class="btn btn-outline-danger">Batalkan Stock In</summary><form method="POST" action="{{ route('warehouse.stock-in.cancel', $stockIn) }}" class="mt-3"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}"><label class="form-label warehouse-required" for="stock-in-cancel-reason">Alasan pembatalan</label><textarea class="form-control" id="stock-in-cancel-reason" name="reason" rows="3" required></textarea><button class="btn btn-danger mt-2" type="submit">Konfirmasi Pembatalan</button></form></details></div>
                @endcan
            @endif
        </section>
    </div>
@endsection
