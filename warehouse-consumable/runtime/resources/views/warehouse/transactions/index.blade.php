@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-history-title">
        <x-warehouse.page-header title="Riwayat Transaksi" subtitle="Audit pergerakan lengkap dengan nomor transaksi, kondisi, lokasi, saldo, dan karyawan.">
            <a class="btn btn-primary" href="{{ route('warehouse.transactions.create') }}">Transaksi Baru</a>
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.exports.transactions', request()->query()) }}">Ekspor hasil filter</a>
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        <nav class="warehouse-workspace-tabs" aria-label="Workspace riwayat">
            <a @class(['is-active' => request('workspace', 'all') === 'all']) href="{{ route('warehouse.transactions.index', array_merge(request()->except('page', 'workspace', 'verified_user_id'), ['workspace' => 'all'])) }}">Semua</a>
            @foreach($workspaces as $key => $workspace)
                <a @class(['is-active' => request('workspace') === $key]) href="{{ route('warehouse.transactions.index', array_merge(request()->except('page', 'workspace', 'verified_user_id'), ['workspace' => $key])) }}">{{ $workspace['label'] }}</a>
            @endforeach
        </nav>

        @php($advancedHistoryFilterActive = collect(['transaction_number', 'reference_number', 'category_id', 'consumable_id', 'section', 'verified_user_id'])->contains(fn ($key) => request()->filled($key)))
        <form class="warehouse-history-filter-form" method="GET" aria-label="Filter riwayat transaksi">
            <input type="hidden" name="workspace" value="{{ request('workspace', 'all') }}">
            <section class="warehouse-panel warehouse-history-primary-filters">
                <div class="warehouse-panel-body">
                    <div class="warehouse-section-heading">
                        <div>
                            <h2>Filter riwayat</h2>
                            <p>Gunakan rentang tanggal, tipe, dan kondisi untuk pencarian operasional.</p>
                        </div>
                    </div>
                    <div class="warehouse-filter-grid">
                        <div class="warehouse-filter-field"><label class="form-label" for="history-from">Dari</label><input class="form-control" id="history-from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
                        <div class="warehouse-filter-field"><label class="form-label" for="history-to">Sampai</label><input class="form-control" id="history-to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
                        <div class="warehouse-filter-field"><label class="form-label" for="history-type">Tipe</label><select class="form-select" id="history-type" name="transaction_type"><option value="">Semua</option>@foreach(['IN' => 'Stock In', 'OUT' => 'Stock Out', 'ADJUSTMENT' => 'Penyesuaian', 'REVERSAL' => 'Pembatalan', 'TRANSFER' => 'Transfer Antar Lokasi (Arsip)'] as $value => $label)<option value="{{ $value }}" @selected(request('transaction_type') === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div class="warehouse-filter-field"><label class="form-label" for="history-condition">Kondisi</label><select class="form-select" id="history-condition" name="item_condition"><option value="">Semua</option><option value="NEW" @selected(request('item_condition') === 'NEW')>Baru</option><option value="USED" @selected(request('item_condition') === 'USED')>Bekas</option></select></div>
                    </div>
                </div>
            </section>

            <details class="warehouse-history-advanced" @if($advancedHistoryFilterActive || $errors->any()) open @endif>
                <summary><span>Filter lanjutan</span><span>Nomor transaksi, kategori, barang, dan karyawan</span></summary>
                <div class="warehouse-history-advanced-body">
                    <div class="warehouse-filter-grid">
                        <div class="warehouse-filter-field warehouse-filter-field-wide"><label class="form-label" for="history-number">Nomor transaksi</label><input class="form-control" id="history-number" name="transaction_number" value="{{ request('transaction_number') }}"></div>
                        <div class="warehouse-filter-field"><label class="form-label" for="history-category">Kategori</label><select class="form-select" id="history-category" name="category_id"><option value="">Semua</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                        <div class="warehouse-filter-field"><label class="form-label" for="history-item">Barang</label><select class="form-select" id="history-item" name="consumable_id"><option value="">Semua</option>@foreach($consumables as $item)<option value="{{ $item->id }}" @selected((string) request('consumable_id') === (string) $item->id)>{{ $item->item_name }}</option>@endforeach</select></div>
                        <div class="warehouse-filter-field warehouse-filter-field-wide"><label class="form-label" for="history-user">Karyawan</label><select class="form-select" id="history-user" name="verified_user_id" @disabled(request('workspace', 'all') !== 'all')><option value="">Semua</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) request('verified_user_id') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                        <div class="warehouse-filter-field warehouse-filter-field-wide"><label class="form-label" for="history-reference">Referensi</label><input class="form-control" id="history-reference" name="reference_number" value="{{ request('reference_number') }}"></div>
                    </div>
                </div>
            </details>

            <div class="warehouse-action-bar"><x-warehouse.filter-actions :reset="route('warehouse.transactions.index', ['workspace' => request('workspace', 'all')])" submit="Terapkan filter" /></div>
        </form>

        <x-warehouse.panel title="Daftar transaksi" class="warehouse-table-panel">
            <div class="warehouse-table-wrap mobile-card-source">
                <table class="table warehouse-table warehouse-history-table align-middle" aria-label="Riwayat transaksi">
                    <thead><tr><th>Waktu</th><th>Nomor Transaksi</th><th>Tipe</th><th>Kondisi</th><th>Barang</th><th>Jumlah</th><th>Lokasi</th><th>Stok Awal</th><th>Stok Akhir</th><th>Karyawan</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ optional($transaction->transaction_at)->format('Y-m-d H:i') }}</td>
                                <td><span class="font-monospace">{{ $transaction->transaction_number }}</span></td>
                                <td><x-warehouse.status-badge :status="$transaction->transaction_type?->value" context="transaction" /></td>
                                <td>{{ $transaction->item_condition?->label() ?? 'Baru' }}</td>
                                <td><div class="warehouse-history-item"><strong>{{ $transaction->consumable?->item_name }}</strong><small class="font-monospace">{{ $transaction->consumable?->item_code }}</small></div></td>
                                <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</td>
                                <td>{{ $transaction->display_location ?: '—' }}</td>
                                <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_before) }}</td>
                                <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_after) }}</td>
                                <td>{{ $transaction->verified_user_name }}<small class="d-block warehouse-muted">{{ $transaction->verified_user_section ?: '—' }}</small></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.transactions.show', $transaction) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="11"><x-warehouse.empty-state title="Tidak ada transaksi" message="Ubah filter untuk mencoba lagi." /></td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><td colspan="11"><section class="warehouse-history-totals warehouse-history-totals-footer" aria-label="Total seluruh hasil filter"><article><span>Transaksi</span><strong>{{ (int) ($totals->transaction_count ?? 0) }}</strong></article><article><span>Stock In</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($totals->stock_in_quantity ?? 0) }}</strong></article><article><span>Stock Out</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($totals->stock_out_quantity ?? 0) }}</strong></article><article><span>Adjustment</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($totals->adjustment_quantity ?? 0) }}</strong></article></section></td></tr></tfoot>
                </table>
            </div>
            <div class="warehouse-card-list">
                @forelse($transactions as $transaction)
                    <article class="warehouse-mobile-record">
                        <div class="warehouse-mobile-record-heading"><strong>{{ $transaction->transaction_number }}</strong><x-warehouse.status-badge :status="$transaction->transaction_type?->value" context="transaction" /></div>
                        <dl>
                            <dt>Waktu</dt><dd>{{ optional($transaction->transaction_at)->format('Y-m-d H:i') }}</dd>
                            <dt>Tipe</dt><dd>{{ $transaction->transaction_type?->value }}</dd>
                            <dt>Kondisi</dt><dd>{{ $transaction->item_condition?->label() ?? 'Baru' }}</dd>
                            <dt>Barang</dt><dd>{{ $transaction->consumable?->item_name }}<small class="d-block font-monospace warehouse-muted">{{ $transaction->consumable?->item_code }}</small></dd>
                            <dt>Jumlah</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->quantity) }} {{ $transaction->consumable?->unit }}</dd>
                            <dt>Lokasi</dt><dd>{{ $transaction->display_location ?: '—' }}</dd>
                            <dt>Stok Awal</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_before) }}</dd>
                            <dt>Stok Akhir</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($transaction->stock_after) }}</dd>
                            <dt>Karyawan</dt><dd>{{ $transaction->verified_user_name }}</dd>
                        </dl>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.transactions.show', $transaction) }}">Detail</a>
                    </article>
                @empty
                    <x-warehouse.empty-state title="Tidak ada transaksi" />
                @endforelse
                <section class="warehouse-history-totals warehouse-history-totals-mobile" aria-label="Total seluruh hasil filter"><article><span>Transaksi</span><strong>{{ (int) ($totals->transaction_count ?? 0) }}</strong></article><article><span>Stock In</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($totals->stock_in_quantity ?? 0) }}</strong></article><article><span>Stock Out</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($totals->stock_out_quantity ?? 0) }}</strong></article><article><span>Adjustment</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($totals->adjustment_quantity ?? 0) }}</strong></article></section>
            </div>
            {{ $transactions->links('pagination::warehouse-bootstrap-5') }}
        </x-warehouse.panel>
    </div>
@endsection
