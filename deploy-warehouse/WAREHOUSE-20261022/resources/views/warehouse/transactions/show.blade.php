@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-receipt-title">
        <x-warehouse.page-header title="Detail Transaksi" subtitle="Rincian resmi data transaksi yang tersimpan permanen dalam sistem.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.index') }}">Riwayat</a>
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>
        @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

        <div class="warehouse-transaction-detail-grid" aria-labelledby="warehouse-receipt-title">
            <x-warehouse.panel title="Transaksi">
                <dl class="warehouse-definition-list">
                    <dt>Nomor Transaksi</dt><dd class="font-monospace">{{ $transaction->transaction_number }}</dd>
                    <dt>Tipe</dt><dd><x-warehouse.status-badge :status="$transaction->transaction_type?->value" context="transaction" /></dd>
                    <dt>Kondisi</dt><dd>{{ $transaction->item_condition?->label() ?? 'Baru' }}</dd>
                    <dt>Waktu</dt><dd>{{ optional($transaction->transaction_at)->format('Y-m-d H:i:s') }}</dd>
                </dl>
            </x-warehouse.panel>

            <x-warehouse.panel title="Barang">
                <dl class="warehouse-definition-list">
                    <dt>Barang</dt><dd>{{ $transaction->consumable?->item_name }}</dd>
                    <dt>Item Code</dt><dd class="font-monospace">{{ $transaction->consumable?->item_code }}</dd>
                    <dt>Jumlah</dt><dd><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</strong></dd>
                    @if($transaction->transaction_type?->value === 'OUT')
                        <dt>Alokasi Mesin</dt>
                        <dd>
                            @if($transaction->machineUsages->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($transaction->machineUsages as $usage)
                                        <span class="badge bg-light text-dark border">
                                            <strong>{{ $usage->machine_code }}</strong>: {{ \App\Services\Warehouse\WarehouseQuantity::display($usage->quantity) }} pcs
                                        </span>
                                    @endforeach
                                </div>
                            @elseif($transaction->machine_type_used)
                                <span>{{ $transaction->machine_type_used }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>
                    @endif
                </dl>
            </x-warehouse.panel>

            <x-warehouse.panel title="Perubahan Stok">
                <dl class="warehouse-definition-list">
                    <dt>Lokasi</dt><dd>{{ $transaction->display_location ?: '—' }}</dd>
                    <dt>Stok Awal</dt><dd><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_before) }}</strong></dd>
                    <dt>Stok Akhir</dt><dd><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_after) }}</strong></dd>
                </dl>
            </x-warehouse.panel>

            <x-warehouse.panel title="Karyawan">
                <dl class="warehouse-definition-list">
                    <dt>Karyawan</dt><dd>{{ $transaction->verified_user_name }}</dd>
                    <dt>NPK</dt><dd>{{ $transaction->verified_user_npk ?: '—' }}</dd>
                    <dt>Bagian</dt><dd>{{ $transaction->verified_user_section ?: '—' }}</dd>
                </dl>
            </x-warehouse.panel>

            <x-warehouse.panel title="Informasi Tambahan & Riwayat" class="warehouse-detail-full">
                <dl class="warehouse-definition-list">
                    <dt>Dibuat Oleh</dt><dd>{{ $transaction->creator?->name ?: '—' }}</dd>
                    <dt>Catatan</dt><dd>{{ $transaction->notes ?: '—' }}</dd>
                    @if($transaction->locationShipment)<dt>Arsip Transfer Antar Lokasi</dt><dd>{{ $transaction->locationShipment->shipment_number }} · Pengirim {{ $transaction->locationShipment->sender_name_snapshot }} · Validator {{ $transaction->locationShipment->validator_name_snapshot ?: '—' }}</dd>@endif
                    @if($transaction->reversalOf)<dt>Membatalkan transaksi</dt><dd><a href="{{ route('warehouse.transactions.show', $transaction->reversalOf) }}">{{ $transaction->reversalOf->transaction_number }}</a></dd>@endif
                    @if($transaction->reversal)<dt>Dibatalkan oleh</dt><dd><a href="{{ route('warehouse.transactions.show', $transaction->reversal) }}">{{ $transaction->reversal->transaction_number }}</a></dd>@endif
                </dl>
            </x-warehouse.panel>
        </div>

        @if($transaction->transaction_type?->value === 'TRANSFER')<div class="alert alert-info mt-3" role="note">Perpindahan lokasi ini berasal dari arsip transfer antar lokasi terdahulu. Data ini disimpan sebagai bagian dari riwayat sistem.</div>@endif
        <div class="warehouse-action-bar mt-3">
            <a class="btn btn-primary" href="{{ route('warehouse.transactions.create') }}">Transaksi Baru</a>
            @if($canReverse)<a class="btn btn-outline-danger" href="{{ route('warehouse.transactions.reverse-form', $transaction) }}">Buat Pembatalan</a>@endif
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </div>
    </div>
@endsection
