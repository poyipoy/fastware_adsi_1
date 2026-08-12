@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/dashboard.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-dashboard-page" aria-labelledby="warehouse-dashboard-title">
        <x-warehouse.page-header title="Dashboard Barang Habis Pakai" subtitle="Pantau ketersediaan stok dan pergerakan barang habis pakai.">
            @if ($canStockIn)
                <a class="btn btn-success" href="{{ route('warehouse.transactions.create', ['type' => 'IN']) }}">Stock In</a>
            @endif
            @if ($canStockOut)
                <a class="btn btn-warning" href="{{ route('warehouse.transactions.create', ['type' => 'OUT']) }}">Stock Out</a>
            @endif
            @if ($canManageMaster)
                <a class="btn btn-outline-primary" href="{{ route('warehouse.consumables.index') }}">Master Consumable</a>
            @endif
            @if ($canViewTransactions)
                <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.index') }}">Riwayat</a>
            @endif
            @if ($canExport)
                <a class="btn btn-outline-secondary" href="{{ url('/warehouse/exports/transactions') }}">Ekspor</a>
            @endif
            @if ($canAdjust)
                <a class="btn btn-outline-secondary" href="{{ route('warehouse.adjustments.create') }}">Penyesuaian</a>
            @endif
        </x-warehouse.page-header>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <section class="warehouse-kpi-grid" aria-label="KPI Warehouse">
            <article class="warehouse-kpi-card"><span class="warehouse-kpi-label">Barang aktif</span><strong>{{ $summary['active_items'] }}</strong></article>
            <article class="warehouse-kpi-card"><span class="warehouse-kpi-label">Stok aman</span><strong>{{ $summary['healthy_stock_items'] }}</strong></article>
            <article class="warehouse-kpi-card warehouse-kpi-card-success"><span class="warehouse-kpi-label">Stock In Bulan Ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_in_month']['quantity']) }}</strong><small>{{ $currentMonthLabel }} · {{ $summary['stock_in_month']['transaction_count'] }} transaksi</small></article>
            <article class="warehouse-kpi-card warehouse-kpi-card-warning"><span class="warehouse-kpi-label">Stock Out Bulan Ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_out_month']['quantity']) }}</strong><small>{{ $currentMonthLabel }} · {{ $summary['stock_out_month']['transaction_count'] }} transaksi</small></article>
            <article class="warehouse-kpi-card warehouse-kpi-card-warning"><span class="warehouse-kpi-label">Stok menipis</span><strong>{{ $summary['low_stock_items'] }}</strong></article>
            <article class="warehouse-kpi-card warehouse-kpi-card-danger"><span class="warehouse-kpi-label">Stok habis</span><strong>{{ $summary['out_of_stock_items'] }}</strong></article>
        </section>

        <section class="warehouse-insights-grid" aria-label="Ringkasan Warehouse">
            @php
                $trendFilterIsActive = request()->filled('trend_date_from') || request()->filled('trend_date_to');
                $trendFilterHasErrors = $errors->has('trend_date_from') || $errors->has('trend_date_to');
            @endphp
            <x-warehouse.panel class="warehouse-trend-panel" title="Tren Stock In/Out">
                <x-slot:header>
                    <button
                        class="btn btn-sm {{ $trendFilterIsActive ? 'btn-primary' : 'btn-outline-primary' }} warehouse-trend-filter-toggle"
                        type="button"
                        data-warehouse-trend-filter-toggle
                        data-bs-target="#warehouse-trend-filter"
                        aria-controls="warehouse-trend-filter"
                        aria-expanded="{{ $trendFilterHasErrors ? 'true' : 'false' }}"
                        aria-label="{{ $trendFilterIsActive ? 'Filter tren aktif' : 'Filter tren Stock In dan Stock Out' }}"
                    >
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span>Filter</span>
                    </button>
                </x-slot:header>
                <div @class(['collapse', 'show' => $trendFilterHasErrors]) id="warehouse-trend-filter">
                    <form class="warehouse-trend-filter" method="GET" action="{{ route('warehouse.dashboard') }}" aria-label="Filter Tren Stock In dan Stock Out">
                        <div class="warehouse-trend-filter-grid">
                            <div>
                                <label class="form-label" for="trend-date-from">Dari tanggal</label>
                                <input class="form-control @error('trend_date_from') is-invalid @enderror" id="trend-date-from" type="date" name="trend_date_from" value="{{ request('trend_date_from', $trendFilter->from->toDateString()) }}" @if ($errors->has('trend_date_from')) aria-invalid="true" aria-describedby="trend-date-from-error" @endif>
                                @error('trend_date_from')<div class="invalid-feedback d-block" id="trend-date-from-error" role="alert">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="trend-date-to">Sampai tanggal</label>
                                <input class="form-control @error('trend_date_to') is-invalid @enderror" id="trend-date-to" type="date" name="trend_date_to" value="{{ request('trend_date_to', $trendFilter->to->toDateString()) }}" @if ($errors->has('trend_date_to')) aria-invalid="true" aria-describedby="trend-date-to-error" @endif>
                                @error('trend_date_to')<div class="invalid-feedback d-block" id="trend-date-to-error" role="alert">{{ $message }}</div>@enderror
                            </div>
                            <div class="warehouse-trend-filter-actions">
                                <button class="btn btn-primary" type="submit">Terapkan</button>
                                <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Atur ulang</a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="warehouse-table-wrap">
                    <table class="table warehouse-table" aria-label="Tren Stock In dan Stock Out">
                        <thead><tr><th scope="col">Tanggal</th><th scope="col">Masuk</th><th scope="col">Keluar</th></tr></thead>
                        <tbody>
                            @forelse ($trend as $date => $rows)
                                <tr><td>{{ $date }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($rows->get('IN')?->quantity) }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($rows->get('OUT')?->quantity) }}</td></tr>
                            @empty
                                <tr><td colspan="3"><x-warehouse.empty-state title="Belum ada pergerakan" message="Tidak ada pergerakan pada periode ini." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-warehouse.panel>

            <x-warehouse.panel title="Penggunaan Terbanyak" subtitle="Stock Out terbesar dalam 30 hari terakhir.">
                @php($topUsageMax = max(1, (float) $topUsage->max('quantity')))
                <div class="warehouse-usage-list">
                    @forelse ($topUsage as $usage)
                        @php($usagePercent = min(100, ((float) $usage->quantity / $topUsageMax) * 100))
                        <div class="warehouse-usage-row">
                            <div class="warehouse-usage-copy"><span>{{ $usage->item_name }}</span><small>{{ \App\Services\Warehouse\WarehouseQuantity::display($usage->quantity) }} {{ $usage->unit }}</small></div>
                            <div class="warehouse-usage-bar" aria-hidden="true"><span style="width: {{ $usagePercent }}%"></span></div>
                        </div>
                    @empty
                        <x-warehouse.empty-state title="Belum ada Stock Out" message="Data penggunaan akan muncul setelah transaksi." />
                    @endforelse
                </div>
            </x-warehouse.panel>
        </section>

        <section class="warehouse-data-grid" aria-label="Warehouse detail tables">
            <x-warehouse.panel title="Stok Menipis / Habis" subtitle="Prioritas pengisian ulang.">
                <x-slot:header>
                    @if ($canStockIn)<a href="{{ route('warehouse.transactions.create', ['type' => 'IN']) }}" class="btn btn-sm btn-outline-primary">Stock In</a>@endif
                </x-slot:header>
                <div class="warehouse-table-wrap">
                    <table class="table warehouse-table align-middle" aria-label="Stok rendah dan kosong">
                        <thead><tr><th scope="col">Barang</th><th scope="col">Stok saat ini</th><th scope="col">Minimum</th><th scope="col">Satuan</th><th scope="col">Lokasi</th><th scope="col">Status</th>@if($canStockIn)<th scope="col">Aksi</th>@endif</tr></thead>
                        <tbody>
                            @forelse ($lowStock as $item)
                                <tr>
                                    <td>{{ $item->item_name }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->current_stock) }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->minimum_stock) }}</td><td>{{ $item->unit }}</td><td>{{ $item->storage_location ?: '—' }}</td>
                                    <td><x-warehouse.status-badge :status="$item->stock_status" context="stock" /></td>
                                    @if ($canStockIn)<td><a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.transactions.create', ['type' => 'IN', 'barcode' => $item->barcode]) }}">Stock In</a></td>@endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $canStockIn ? 7 : 6 }}"><x-warehouse.empty-state title="Stok terkendali" message="Semua item aktif berada di atas minimum." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($lowStock->hasPages())<div class="warehouse-panel-pagination">{{ $lowStock->links('pagination::warehouse-bootstrap-5') }}</div>@endif
            </x-warehouse.panel>

            <x-warehouse.panel title="Transaksi Terbaru" subtitle="Pergerakan bulan berjalan." :tag="$currentMonthLabel">
                @include('warehouse.dashboard.partials.recent-transactions', ['recentTransactions' => $recentTransactions])
            </x-warehouse.panel>
        </section>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/dashboard.js')
@endpush
