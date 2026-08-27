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
        <section class="warehouse-critical-summary" aria-label="Ringkasan transaksi yang akan dibatalkan">
            <h2>Transaksi asal</h2>
            <div class="warehouse-critical-summary-grid">
                <div><span>Nomor</span><strong class="font-monospace">{{ $transaction->transaction_number }}</strong></div>
                <div><span>Tipe</span><strong>{{ $transactionLabel }}</strong></div>
                <div><span>Barang</span><strong>{{ $transaction->consumable?->item_name }}</strong></div>
                <div><span>Jumlah</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</strong></div>
                <div><span>Stok</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_before) }} → {{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_after) }}</strong></div>
                <div><span>Lokasi</span><strong>{{ $transaction->from_location ?: '—' }} → {{ $transaction->to_location ?: '—' }}</strong></div>
            </div>
        </section>
        <form class="warehouse-panel mt-3" method="POST" action="{{ route('warehouse.transactions.reverse', $transaction) }}">
            @csrf
            <div class="warehouse-panel-body">
                <section class="warehouse-critical-section">
                    <div class="warehouse-form-section-heading"><h2>Konfirmasi pembatalan</h2><p>Alasan wajib disimpan pada audit pembatalan.</p></div>
                    <div class="warehouse-form-field"><label class="form-label warehouse-required" for="reverse-reason">Alasan wajib</label><textarea class="form-control" id="reverse-reason" name="reason" rows="3" required>{{ old('reason') }}</textarea></div>
                </section>
                <section class="warehouse-critical-section">
                    <div class="warehouse-form-section-heading"><h2>Verifikasi</h2><p>Identitas dan akses Warehouse diperiksa sebelum pembatalan dicatat.</p></div>
                    <div class="warehouse-form-field"><label class="form-label warehouse-required" for="reverse-code">Pindai barcode NPK karyawan</label><input class="form-control font-monospace" id="reverse-code" name="verified_code" inputmode="numeric" autocomplete="off" required><div class="warehouse-help">Nama, NPK, bagian, dan akses Warehouse akan diverifikasi oleh sistem.</div></div>
                    @if($requiresLegacyLocation)<div class="warehouse-form-field mt-3"><label class="form-label warehouse-required" for="reverse-legacy-location">Lokasi transaksi lama</label><select class="form-select" id="reverse-legacy-location" name="legacy_location" required><option value="">Pilih lokasi saldo yang dikoreksi</option>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}" @selected(old('legacy_location') === $location)>{{ $location }}</option>@endforeach</select><div class="warehouse-help">Transaksi ini dibuat sebelum audit lokasi tersedia; pilih lokasi yang benar.</div></div>@endif
                </section>
                <input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}">
            </div>
            <div class="card-footer"><button class="btn btn-danger" type="submit">Konfirmasi Pembatalan</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.show', $transaction) }}">Batal</a></div>
        </form>
    </div>
@endsection
