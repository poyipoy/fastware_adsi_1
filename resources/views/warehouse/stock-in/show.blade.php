@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/stock-in.css')
@endpush

@section('warehouse-content')
    @php
        $stockInPhotoUrl = $stockIn->consumable?->photo_path
            ? \Illuminate\Support\Facades\Storage::disk(config('warehouse.photos.disk', 'public'))->url($stockIn->consumable->photo_path)
            : null;
    @endphp
    <div class="warehouse-stock-in-page" aria-labelledby="warehouse-stock-in-detail-title">
        <x-warehouse.page-header title="Detail Stock In" subtitle="Audit penerimaan, validasi fisik, dan tautan mutasi ledger.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.create') }}">Kembali ke Stock In/Out Baru</a>
            @if($stockIn->canValidate() && $canValidateStockIn)<a class="btn btn-primary" href="{{ route('warehouse.stock-in.validate-form', $stockIn) }}">Validasi</a>@endif
        </x-warehouse.page-header>

        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

        <section class="warehouse-panel warehouse-stock-in-identity-panel">
            <div class="warehouse-panel-header">
                <div><h2 id="warehouse-stock-in-detail-title">{{ $stockIn->stock_in_number }}</h2><p>Stock In dibuat {{ optional($stockIn->created_at)->format('Y-m-d H:i') }}</p></div>
                <x-warehouse.status-badge :status="$stockIn->status?->value" context="stock-in" />
            </div>
            <div class="warehouse-panel-body">
                <div class="warehouse-stock-in-item-identity">
                    <span class="warehouse-stock-in-item-photo">@if($stockInPhotoUrl)<img src="{{ $stockInPhotoUrl }}" alt="Foto {{ $stockIn->consumable?->item_name }}" width="160" height="110">@else<span aria-hidden="true">WH</span>@endif</span>
                    <div>
                        <span class="warehouse-eyebrow">Barang</span>
                        <h3>{{ $stockIn->consumable?->item_name ?? '—' }}</h3>
                        <p class="warehouse-stock-in-item-code">{{ $stockIn->consumable?->item_code ?? '—' }}</p>
                        @if($stockIn->consumable?->machine_type)<p class="warehouse-muted mb-0">{{ $stockIn->consumable->machine_type }}</p>@endif
                    </div>
                </div>
            </div>
        </section>

        <div class="warehouse-stock-in-detail-layout">
            <x-warehouse.panel title="Rincian penerimaan" subtitle="Data yang dicatat saat Stock In dibuat.">
                <dl class="warehouse-stock-in-readonly">
                    <div><dt>Kondisi</dt><dd>{{ $stockIn->item_condition?->label() }}</dd></div>
                    <div><dt>Qty input</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_expected) }} {{ $stockIn->consumable?->unit }}</dd></div>
                    <div><dt>Qty aktual</dt><dd>{{ $stockIn->quantity_received === null ? 'Belum divalidasi' : \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_received).' '.$stockIn->consumable?->unit }}</dd></div>
                    <div><dt>Lokasi tujuan</dt><dd>{{ $stockIn->destination_location }}</dd></div>
                    <div><dt>Sumber</dt><dd>{{ $stockIn->source_location ?: 'Supplier / eksternal' }}</dd></div>
                    <div><dt>Dibuat pada</dt><dd>{{ optional($stockIn->created_at)->format('Y-m-d H:i') }}</dd></div>
                </dl>
            </x-warehouse.panel>

            <x-warehouse.panel title="Validasi & audit" subtitle="Informasi pemeriksaan fisik dan jejak pencatatan.">
                <dl class="warehouse-stock-in-readonly">
                    <div><dt>Dibuat oleh</dt><dd>{{ $stockIn->creator_name_snapshot }}{{ $stockIn->creator_npk_snapshot ? ' · NPK '.$stockIn->creator_npk_snapshot : '' }}</dd></div>
                    <div><dt>Hasil validasi</dt><dd>{{ $stockIn->validation_result?->label() ?: 'Belum divalidasi' }}</dd></div>
                    <div><dt>Validator</dt><dd>{{ $stockIn->validator_name_snapshot ? $stockIn->validator_name_snapshot.' · NPK '.$stockIn->validator_npk_snapshot : 'Belum divalidasi' }}</dd></div>
                    <div><dt>Ledger</dt><dd>{{ $stockIn->stock_transaction_id ? 'Transaksi #'.$stockIn->stock_transaction_id : 'Belum ada mutasi' }}</dd></div>
                    <div class="warehouse-stock-in-form-field-full"><dt>Catatan awal</dt><dd>{{ $stockIn->notes ?: 'Tidak ada catatan.' }}</dd></div>
                    <div class="warehouse-stock-in-form-field-full"><dt>Catatan validasi</dt><dd>{{ $stockIn->validation_notes ?: 'Belum ada catatan validasi.' }}</dd></div>
                </dl>
            </x-warehouse.panel>
        </div>

        @if($stockIn->canCancel())
            @can('warehouse.stock-in.create')
                <section class="warehouse-panel warehouse-stock-in-cancel-panel">
                    <div class="warehouse-panel-header"><div><h2>Batalkan Stock In</h2><p>Pembatalan hanya tersedia sebelum Stock In tervalidasi.</p></div></div>
                    <div class="warehouse-panel-body">
                        <details><summary class="btn btn-outline-danger">Batalkan Stock In</summary><form method="POST" action="{{ route('warehouse.stock-in.cancel', $stockIn) }}" class="mt-3"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}"><label class="form-label warehouse-required" for="stock-in-cancel-reason">Alasan pembatalan</label><textarea class="form-control" id="stock-in-cancel-reason" name="reason" rows="3" required></textarea><button class="btn btn-danger mt-2" type="submit">Konfirmasi Pembatalan</button></form></details>
                    </div>
                </section>
            @endcan
        @endif
    </div>
@endsection
