@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/dashboard.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-dashboard-page" aria-labelledby="warehouse-dashboard-title">
        <x-warehouse.page-header title="Dashboard Barang Habis Pakai" subtitle="Pantau ketersediaan dan penggunaan barang berdasarkan periode yang dipilih.">
            @if ($canStockIn)<a class="btn btn-success" href="{{ route('warehouse.transactions.create', ['type' => 'IN']) }}">Stock In Baru</a>@endif
            @if ($canStockOut)<a class="btn btn-warning" href="{{ route('warehouse.transactions.create', ['type' => 'OUT']) }}">Stock Out Baru</a>@endif
            @if ($canStockOut)<a class="btn btn-outline-warning" href="{{ route('warehouse.transactions-used.create', ['type' => 'OUT']) }}">Transaksi Bekas</a>@endif
            @if ($canTransfer)<a class="btn btn-outline-primary" href="{{ route('warehouse.transfers.create') }}">Transfer Lokasi</a>@endif
            @if ($canViewReport)<a class="btn btn-outline-primary" href="{{ route('warehouse.reports.index') }}">Reporting</a>@endif
            @if ($canManageMaster)<a class="btn btn-outline-primary" href="{{ route('warehouse.consumables.index') }}">Master Consumable</a>@endif
            @if ($canViewTransactions)<a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.index') }}">Riwayat</a>@endif
            @if ($canExport)<a class="btn btn-outline-secondary" href="{{ route('warehouse.exports.transactions') }}">Ekspor</a>@endif
            @if ($canAdjust)<a class="btn btn-outline-secondary" href="{{ route('warehouse.adjustments.create') }}">Penyesuaian</a>@endif
        </x-warehouse.page-header>

        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

        <section class="warehouse-kpi-grid" aria-label="KPI Warehouse">
            <article class="warehouse-kpi-card"><span class="warehouse-kpi-label">Barang aktif</span><strong>{{ $summary['active_items'] }}</strong></article>
            <article class="warehouse-kpi-card"><span class="warehouse-kpi-label">Stok aman</span><strong>{{ $summary['healthy_stock_items'] }}</strong></article>
            <article class="warehouse-kpi-card warehouse-kpi-card-success"><span class="warehouse-kpi-label">Stock In Bulan Ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_in_month']['quantity']) }}</strong><small>{{ $currentMonthLabel }} · {{ $summary['stock_in_month']['transaction_count'] }} transaksi</small></article>
            <article class="warehouse-kpi-card warehouse-kpi-card-warning"><span class="warehouse-kpi-label">Stock Out Bulan Ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_out_month']['quantity']) }}</strong><small>{{ $currentMonthLabel }} · {{ $summary['stock_out_month']['transaction_count'] }} transaksi</small></article>
            <article class="warehouse-kpi-card warehouse-kpi-card-warning"><span class="warehouse-kpi-label">Stok menipis</span><strong>{{ $summary['low_stock_items'] }}</strong></article>
            <article class="warehouse-kpi-card warehouse-kpi-card-danger"><span class="warehouse-kpi-label">Stok habis</span><strong>{{ $summary['out_of_stock_items'] }}</strong></article>
        </section>

        @php
            $trendFilterIsActive = request()->filled('trend_date_from') || request()->filled('trend_date_to');
            $trendFilterHasErrors = $errors->has('trend_date_from') || $errors->has('trend_date_to');
        @endphp
        <x-warehouse.panel class="warehouse-trend-panel" title="Analitik Pergerakan" subtitle="Satu periode untuk tren, item terpakai, dan tipe mesin.">
            <x-slot:header>
                <button class="btn btn-sm {{ $trendFilterIsActive ? 'btn-primary' : 'btn-outline-primary' }} warehouse-trend-filter-toggle" type="button" data-warehouse-trend-filter-toggle data-bs-target="#warehouse-trend-filter" aria-controls="warehouse-trend-filter" aria-expanded="{{ $trendFilterHasErrors ? 'true' : 'false' }}" aria-label="{{ $trendFilterIsActive ? 'Filter tren aktif' : 'Filter analitik Warehouse' }}">
                    <i class="bi bi-funnel" aria-hidden="true"></i><span>Filter periode</span>
                </button>
            </x-slot:header>
            <div @class(['collapse', 'show' => $trendFilterHasErrors]) id="warehouse-trend-filter">
                <form class="warehouse-trend-filter" method="GET" action="{{ route('warehouse.dashboard') }}" aria-label="Filter analitik Warehouse">
                    <div class="warehouse-trend-filter-grid">
                        <div><label class="form-label" for="trend-date-from">Dari tanggal</label><input class="form-control @error('trend_date_from') is-invalid @enderror" id="trend-date-from" type="date" name="trend_date_from" value="{{ request('trend_date_from', $trendFilter->from->toDateString()) }}">@error('trend_date_from')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror</div>
                        <div><label class="form-label" for="trend-date-to">Sampai tanggal</label><input class="form-control @error('trend_date_to') is-invalid @enderror" id="trend-date-to" type="date" name="trend_date_to" value="{{ request('trend_date_to', $trendFilter->to->toDateString()) }}">@error('trend_date_to')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror</div>
                        <div class="warehouse-trend-filter-actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Atur ulang</a></div>
                    </div>
                </form>
            </div>
            @php
                $topStockOut = $topUsage
                    ->map(static fn ($usage): array => [
                        'category' => 'Item',
                        'name' => (string) $usage->item_name,
                        'label' => 'Item · '.(string) $usage->item_name,
                        'quantity' => (float) $usage->quantity,
                        'unit' => (string) $usage->unit,
                    ])
                    ->concat($topMachineUsage->map(static fn ($usage): array => [
                        'category' => 'Tipe Mesin',
                        'name' => (string) $usage->machine_type,
                        'label' => 'Tipe Mesin · '.(string) $usage->machine_type,
                        'quantity' => (float) $usage->quantity,
                        'unit' => '',
                    ]))
                    ->sort(static function (array $left, array $right): int {
                        $quantityOrder = $right['quantity'] <=> $left['quantity'];

                        return $quantityOrder !== 0 ? $quantityOrder : strcasecmp($left['label'], $right['label']);
                    })
                    ->values();
            @endphp
            <div class="warehouse-analytics-grid">
                <section class="warehouse-analytics-block" aria-labelledby="warehouse-trend-table-title">
                    <h3 id="warehouse-trend-table-title">Tren Stock In/Out</h3>
                    <div class="warehouse-table-wrap"><table class="table warehouse-table" aria-label="Tren Stock In dan Stock Out"><thead><tr><th scope="col">Tanggal</th><th scope="col">Masuk</th><th scope="col">Keluar</th></tr></thead><tbody>
                        @forelse ($trend as $date => $rows)<tr><td>{{ $date }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($rows->get('IN')?->quantity) }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($rows->get('OUT')?->quantity) }}</td></tr>@empty<tr><td colspan="3"><x-warehouse.empty-state title="Belum ada pergerakan" message="Tidak ada data pada periode ini." /></td></tr>@endforelse
                    </tbody></table></div>
                </section>
                <section class="warehouse-analytics-block warehouse-analytics-block-top-stock-out" aria-labelledby="warehouse-top-stock-out-title">
                    <h3 id="warehouse-top-stock-out-title">Top Item Stock Out &amp; Top Tipe Mesin Stock Out</h3>
                    <div class="warehouse-chart-frame warehouse-chart-frame-combined" style="--warehouse-chart-rows: {{ max(1, $topStockOut->count()) }};"><canvas data-warehouse-bar-chart data-source="warehouse-top-stock-out-data" role="img" aria-label="Grafik gabungan item dan tipe mesin dengan Stock Out terbanyak"></canvas></div>
                    <script type="application/json" id="warehouse-top-stock-out-data">{!! json_encode(['labels' => $topStockOut->pluck('label')->values(), 'values' => $topStockOut->pluck('quantity')->values()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                    <details class="warehouse-chart-data"><summary>Lihat data tabel</summary><div class="warehouse-table-wrap"><table class="table warehouse-table warehouse-table-combined" aria-label="Data gabungan item dan tipe mesin Stock Out"><thead><tr><th scope="col">Kategori</th><th scope="col">Nama</th><th scope="col">Jumlah</th></tr></thead><tbody>@forelse($topStockOut as $usage)<tr><td>{{ $usage['category'] }}</td><td>{{ $usage['name'] }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($usage['quantity']) }}@if($usage['unit']) {{ $usage['unit'] }}@endif</td></tr>@empty<tr><td colspan="3">Belum ada data Stock Out pada periode ini.</td></tr>@endforelse</tbody></table></div></details>
                </section>
            </div>
        </x-warehouse.panel>

        <section class="warehouse-data-grid warehouse-data-grid-single" aria-label="Prioritas Warehouse">
            <x-warehouse.panel title="Stok Menipis / Habis" subtitle="Prioritas pengisian ulang berdasarkan stok total.">
                <x-slot:header>@if ($canStockIn)<a href="{{ route('warehouse.transactions.create', ['type' => 'IN']) }}" class="btn btn-sm btn-outline-primary">Stock In</a>@endif</x-slot:header>
                <div class="warehouse-table-wrap"><table class="table warehouse-table align-middle" aria-label="Stok rendah dan kosong"><thead><tr><th>Barang</th><th>Stok total</th><th>DS8</th><th>Deltamas</th><th>Minimum</th><th>Status</th>@if($canStockIn)<th>Aksi</th>@endif</tr></thead><tbody>
                    @forelse ($lowStock as $item)<tr><td>{{ $item->item_name }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->current_stock) }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->stock_ds8) }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->stock_deltamas) }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->minimum_stock) }}</td><td><x-warehouse.status-badge :status="$item->stock_status" context="stock" /></td>@if ($canStockIn)<td><a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.transactions.create', ['type' => 'IN', 'barcode' => $item->barcode]) }}">Stock In</a></td>@endif</tr>@empty<tr><td colspan="{{ $canStockIn ? 7 : 6 }}"><x-warehouse.empty-state title="Stok terkendali" message="Semua item aktif berada di atas minimum." /></td></tr>@endforelse
                </tbody></table></div>
                @if ($lowStock->hasPages())<div class="warehouse-panel-pagination">{{ $lowStock->links('pagination::warehouse-bootstrap-5') }}</div>@endif
            </x-warehouse.panel>
        </section>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/dashboard.js')
@endpush
