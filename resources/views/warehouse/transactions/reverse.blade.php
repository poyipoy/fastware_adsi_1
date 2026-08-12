@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page warehouse-critical-operation" aria-labelledby="warehouse-reverse-title">
        <x-warehouse.page-header title="Pembatalan Transaksi" subtitle="Transaksi asal tetap utuh; pembatalan membuat pergerakan lawan.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.show', $transaction) }}">Batal</a>
        </x-warehouse.page-header>

        @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="alert warehouse-critical-warning" role="alert"><strong>Operasi kritis.</strong> Pastikan transaksi dan alasan pembatalan sudah benar sebelum mengonfirmasi.</div>
        @php($transactionLabel = match ($transaction->transaction_type?->value) { 'IN' => 'Stock In', 'OUT' => 'Stock Out', 'ADJUSTMENT' => 'Penyesuaian', 'REVERSAL' => 'Pembatalan', default => $transaction->transaction_type?->value })
        <div class="warehouse-summary" aria-label="Ringkasan transaksi yang akan dibatalkan"><strong>{{ $transaction->transaction_number }}</strong><span class="d-block mt-1">{{ $transactionLabel }} · {{ $transaction->consumable?->item_name }} · {{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</span><span class="d-block mt-1 warehouse-muted">Stok {{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_before) }} → {{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_after) }}</span></div>
        <form class="warehouse-panel mt-3" method="POST" action="{{ route('warehouse.transactions.reverse', $transaction) }}">
            @csrf
            <div class="warehouse-panel-body">
                <div class="warehouse-form-field mb-3"><label class="form-label warehouse-required" for="reverse-reason">Alasan wajib</label><textarea class="form-control" id="reverse-reason" name="reason" rows="3" required>{{ old('reason') }}</textarea></div>
                <div class="warehouse-form-field"><label class="form-label warehouse-required" for="reverse-code">Pindai barcode NPK karyawan</label><input class="form-control font-monospace" id="reverse-code" name="verified_code" inputmode="numeric" autocomplete="off" required><div class="warehouse-help">Nama, NPK, bagian, dan akses Warehouse akan diverifikasi oleh sistem.</div></div>
                <input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}">
            </div>
            <div class="card-footer d-flex flex-wrap gap-2"><button class="btn btn-danger" type="submit">Konfirmasi Pembatalan</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.show', $transaction) }}">Batal</a></div>
        </form>
    </div>
@endsection
