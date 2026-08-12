@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-receipt-title">
        <x-warehouse.page-header title="Detail Transaksi" subtitle="Receipt bersifat tetap untuk pergerakan yang sudah tercatat.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.index') }}">Riwayat</a>
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        <div class="alert alert-success" role="status"><strong>Transaksi berhasil dicatat</strong>@if (session('status'))<span class="d-block mt-1">{{ session('status') }}</span>@endif</div>
        <x-warehouse.panel title="Metadata Receipt" class="warehouse-detail-grid warehouse-detail-full">
            <dl class="warehouse-definition-list">
                <dt>Nomor transaksi</dt><dd class="font-monospace">{{ $transaction->transaction_number }}</dd>
                <dt>Tipe</dt><dd><x-warehouse.status-badge :status="$transaction->transaction_type?->value" context="transaction" /></dd>
                <dt>Barang</dt><dd>{{ $transaction->consumable?->item_name }}</dd>
                <dt>Jumlah</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</dd>
                <dt>Lokasi penyimpanan</dt><dd>{{ $transaction->usage_location ?: ($transaction->consumable?->storage_location ?: '—') }}</dd>
                <dt>Stok sebelum → sesudah</dt><dd><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_before) }} → {{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_after) }}</strong></dd>
                <dt>Karyawan verifikator</dt><dd>{{ $transaction->verified_user_name }} ({{ $transaction->verified_user_section ?: '—' }})</dd>
                <dt>Waktu transaksi</dt><dd>{{ optional($transaction->transaction_at)->format('Y-m-d H:i:s') }}</dd>
                <dt>Dibuat oleh</dt><dd>{{ $transaction->creator?->name ?: '—' }}</dd>
            </dl>
        </x-warehouse.panel>

        <div class="warehouse-action-bar mt-3">
            <a class="btn btn-primary" href="{{ route('warehouse.transactions.create') }}">Transaksi Baru</a>
            @if ($canReverse)<a class="btn btn-outline-danger" href="{{ route('warehouse.transactions.reverse-form', $transaction) }}">Buat Pembatalan</a>@endif
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Kembali ke Dashboard</a>
        </div>
    </div>
@endsection
