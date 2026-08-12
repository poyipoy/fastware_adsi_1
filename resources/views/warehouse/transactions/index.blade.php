@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-history-title">
        <x-warehouse.page-header title="Riwayat Transaksi" subtitle="Snapshot pergerakan bersifat tetap; tidak ada pengubahan atau penghapusan.">
            <a class="btn btn-primary" href="{{ route('warehouse.transactions.create') }}">Formulir Stock In/Out</a>
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        @php($historyFilterActive = collect(['date_from','date_to','transaction_type','transaction_number','reference_number','category_id','consumable_id','section','verified_user_id'])->contains(fn($key) => request()->filled($key)))
        <details class="warehouse-filter-disclosure warehouse-panel" @if ($historyFilterActive || $errors->any()) open @endif>
            <summary><span>Filter riwayat</span><span class="warehouse-filter-summary-hint">Tanggal, tipe, barang, karyawan verifikator, referensi</span></summary>
            <form class="warehouse-filter-card" method="GET" aria-label="Filter riwayat transaksi">
                <div class="warehouse-filter-grid">
                    <div class="warehouse-filter-field"><label class="form-label" for="history-from">Dari</label><input class="form-control" id="history-from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
                    <div class="warehouse-filter-field"><label class="form-label" for="history-to">Sampai</label><input class="form-control" id="history-to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
                    <div class="warehouse-filter-field"><label class="form-label" for="history-type">Tipe</label><select class="form-select" id="history-type" name="transaction_type"><option value="">Semua</option>@foreach (['IN','OUT','ADJUSTMENT','REVERSAL'] as $type)<option value="{{ $type }}" @selected(request('transaction_type') === $type)>{{ match ($type) { 'IN' => 'Stock In', 'OUT' => 'Stock Out', 'ADJUSTMENT' => 'Penyesuaian', default => 'Pembatalan' } }}</option>@endforeach</select></div>
                    <div class="warehouse-filter-field warehouse-filter-field-wide"><label class="form-label" for="history-number">Nomor transaksi</label><input class="form-control" id="history-number" name="transaction_number" value="{{ request('transaction_number') }}"></div>
                    <div class="warehouse-filter-field warehouse-filter-field-wide"><label class="form-label" for="history-reference">Referensi</label><input class="form-control" id="history-reference" name="reference_number" value="{{ request('reference_number') }}"></div>
                    <div class="warehouse-filter-field"><label class="form-label" for="history-category">Kategori</label><select class="form-select" id="history-category" name="category_id"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                    <div class="warehouse-filter-field"><label class="form-label" for="history-item">Barang</label><select class="form-select" id="history-item" name="consumable_id"><option value="">Semua barang</option>@foreach ($consumables as $item)<option value="{{ $item->id }}" @selected((string) request('consumable_id') === (string) $item->id)>{{ $item->item_name }}</option>@endforeach</select></div>
                    <div class="warehouse-filter-field"><label class="form-label" for="history-section">Bagian</label><input class="form-control" id="history-section" name="section" value="{{ request('section') }}"></div>
                    <div class="warehouse-filter-field warehouse-filter-field-wide"><label class="form-label" for="history-user">Karyawan terverifikasi</label><select class="form-select" id="history-user" name="verified_user_id"><option value="">Semua karyawan</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) request('verified_user_id') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                    <x-warehouse.filter-actions :reset="route('warehouse.transactions.index')" submit="Terapkan" />
                </div>
            </form>
        </details>

        <x-warehouse.panel title="Daftar transaksi" class="warehouse-table-panel">
            <div class="warehouse-table-wrap">
                <table class="table warehouse-table align-middle" aria-label="Riwayat transaksi">
                    <thead><tr><th scope="col">Waktu</th><th scope="col">Nomor transaksi</th><th scope="col">Tipe</th><th scope="col">Barang</th><th scope="col">Jumlah</th><th scope="col">Stok sebelum</th><th scope="col">Stok sesudah</th><th scope="col">Karyawan verifikator</th><th scope="col">Bagian</th><th scope="col">Dibuat oleh</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr><td>{{ optional($transaction->transaction_at)->format('Y-m-d H:i') }}</td><td class="font-monospace">{{ $transaction->transaction_number }}</td><td><x-warehouse.status-badge :status="$transaction->transaction_type?->value" context="transaction" /></td><td>{{ $transaction->consumable?->item_name }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_before) }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_after) }}</td><td>{{ $transaction->verified_user_name }}</td><td>{{ $transaction->verified_user_section ?: '—' }}</td><td>{{ $transaction->creator?->name ?: '—' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.transactions.show', $transaction) }}">Detail</a></td></tr>
                        @empty
                            <tr><td colspan="11"><x-warehouse.empty-state title="Tidak ada transaksi" message="Persempit atau ubah filter untuk mencoba lagi." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="warehouse-card-list">
                @forelse ($transactions as $transaction)
                    <article class="warehouse-mobile-record"><div class="warehouse-mobile-record-heading"><strong>{{ $transaction->transaction_number }}</strong><x-warehouse.status-badge :status="$transaction->transaction_type?->value" context="transaction" /></div><dl><dt>Waktu</dt><dd>{{ optional($transaction->transaction_at)->format('Y-m-d H:i') }}</dd><dt>Barang</dt><dd>{{ $transaction->consumable?->item_name }}</dd><dt>Jumlah</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</dd><dt>Stok</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_before) }} → {{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_after) }}</dd><dt>Karyawan verifikator</dt><dd>{{ $transaction->verified_user_name }} ({{ $transaction->verified_user_section ?: '—' }})</dd></dl><a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.transactions.show', $transaction) }}">Detail</a></article>
                @empty
                    <x-warehouse.empty-state title="Tidak ada transaksi" />
                @endforelse
            </div>
            {{ $transactions->links('pagination::warehouse-bootstrap-5') }}
        </x-warehouse.panel>
    </div>
@endsection
