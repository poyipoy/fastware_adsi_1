@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/dashboard.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-dashboard-page" aria-labelledby="warehouse-dashboard-title">
        <x-warehouse.page-header
            title="Warehouse Dashboard — {{ $activeCondition === 'USED' ? 'Stok Bekas' : 'Stok Baru' }}"
            subtitle="{{ $activeCondition === 'USED' ? 'Pantau ketersediaan stok bekas, pemakaian mesin, dan aktivitas daur ulang consumable.' : 'Pantau kondisi stok baru, pemakaian mesin, dan aktivitas warehouse.' }}"
        >
            @if ($activeCondition === 'USED')
                @if ($canStockIn || $canStockOut)
                    <a class="btn btn-warning text-dark fw-bold" href="{{ route('warehouse.transactions-used.create') }}">Stock In / Out Bekas</a>
                @endif
                @if ($canStockIn)
                    <a class="btn btn-outline-primary" href="{{ route('warehouse.transactions.create', ['type' => 'IN']) }}">Stock In Baru</a>
                @endif
                @if ($canStockOut)
                    <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.create', ['type' => 'OUT']) }}">Stock Out Baru</a>
                @endif
            @else
                @if ($canStockIn)
                    <a class="btn btn-primary" href="{{ route('warehouse.transactions.create', ['type' => 'IN']) }}">Stock In Baru</a>
                @endif
                @if ($canStockOut)
                    <a class="btn btn-outline-primary" href="{{ route('warehouse.transactions.create', ['type' => 'OUT']) }}">Stock Out Baru</a>
                @endif
                @if ($canStockIn || $canStockOut)
                    <a class="btn btn-outline-warning" href="{{ route('warehouse.transactions-used.create') }}">Stock In/Out Bekas</a>
                @endif
            @endif

            @if ($canValidateStock)
                <a class="btn btn-outline-success" href="{{ route('warehouse.validations.index') }}">Validasi Stok</a>
            @endif
            @if ($canManageMaster)
                <a class="btn btn-outline-secondary" href="{{ route('warehouse.consumables.index') }}">Master Consumable</a>
            @endif
            @if ($canViewTransactions)
                <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.index') }}">Riwayat</a>
            @endif
            @if ($canViewReport)
                <a class="btn btn-outline-secondary" href="{{ route('warehouse.reports.index') }}">Reporting</a>
            @endif
            @if ($canAdjust)
                <a class="btn btn-outline-danger" href="{{ route('warehouse.adjustments.create') }}">Penyesuaian</a>
            @endif
            @if ($canExport)
                <a class="btn btn-outline-secondary" href="{{ route('warehouse.exports.transactions') }}">Export</a>
            @endif
        </x-warehouse.page-header>

        {{-- TOP SEGMENTED SWITCHER: DASHBOARD STOK BARU VS DASHBOARD STOK BEKAS --}}
        <div class="warehouse-dashboard-switcher-wrap">
            <div class="warehouse-dashboard-switcher" role="tablist" aria-label="Pilih Dashboard Stok">
                <a href="{{ route('warehouse.dashboard', array_merge(request()->except(['page', 'low_page', 'used_page']), ['condition' => 'NEW'])) }}"
                   class="warehouse-switcher-btn switcher-baru {{ $activeCondition === 'NEW' ? 'active' : '' }}">
                    <i class="bi bi-box-seam-fill"></i>
                    <span>Dashboard Stok Baru</span>
                </a>
                <a href="{{ route('warehouse.dashboard', array_merge(request()->except(['page', 'low_page', 'used_page']), ['condition' => 'USED'])) }}"
                   class="warehouse-switcher-btn switcher-bekas {{ $activeCondition === 'USED' ? 'active' : '' }}">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Dashboard Stok Bekas</span>
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        {{-- KPI SECTION --}}
        @if ($activeCondition === 'USED')
            <section class="warehouse-kpi-grid" aria-label="KPI Warehouse Stok Bekas">
                <article class="warehouse-kpi-card warehouse-kpi-card-amber">
                    <div class="warehouse-kpi-header">
                        <span class="warehouse-kpi-label">Total Saldo Bekas</span>
                        <div class="warehouse-kpi-icon" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></div>
                    </div>
                    <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['total_used_stock'] ?? 0) }}</strong>
                    <small>Total akumulasi pcs stok bekas di warehouse.</small>
                </article>
                <article class="warehouse-kpi-card warehouse-kpi-card-teal">
                    <div class="warehouse-kpi-header">
                        <span class="warehouse-kpi-label">Item Bekas Aktif</span>
                        <div class="warehouse-kpi-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></div>
                    </div>
                    <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['active_used_items'] ?? 0) }}</strong>
                    <small>Item consumable dengan saldo bekas &gt; 0 pcs.</small>
                </article>
                <article class="warehouse-kpi-card warehouse-kpi-card-success">
                    <div class="warehouse-kpi-header">
                        <span class="warehouse-kpi-label">Stock In Bekas</span>
                        <div class="warehouse-kpi-icon" aria-hidden="true"><i class="bi bi-box-arrow-in-down"></i></div>
                    </div>
                    <div class="warehouse-kpi-metrics" aria-label="Ringkasan Stock In Bekas">
                        <div><span>Hari ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_in_today']['quantity']) }}</strong></div>
                        <div><span>Bulan ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_in_month']['quantity']) }}</strong></div>
                    </div>
                </article>
                <article class="warehouse-kpi-card warehouse-kpi-card-navy">
                    <div class="warehouse-kpi-header">
                        <span class="warehouse-kpi-label">Stock Out Bekas</span>
                        <div class="warehouse-kpi-icon" aria-hidden="true"><i class="bi bi-box-arrow-up"></i></div>
                    </div>
                    <div class="warehouse-kpi-metrics" aria-label="Ringkasan Stock Out Bekas">
                        <div><span>Hari ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_out_today']['quantity']) }}</strong></div>
                        <div><span>Bulan ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_out_month']['quantity']) }}</strong></div>
                    </div>
                </article>
            </section>
        @else
            <section class="warehouse-kpi-grid" aria-label="KPI Warehouse Stok Baru">
                <article class="warehouse-kpi-card warehouse-kpi-card-danger">
                    <div class="warehouse-kpi-header">
                        <span class="warehouse-kpi-label">Stok Habis</span>
                        <div class="warehouse-kpi-icon" aria-hidden="true"><i class="bi bi-exclamation-octagon-fill"></i></div>
                    </div>
                    <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['out_of_stock_items']) }}</strong>
                    <small>Item memerlukan tindak lanjut pengisian.</small>
                </article>
                <article class="warehouse-kpi-card warehouse-kpi-card-warning">
                    <div class="warehouse-kpi-header">
                        <span class="warehouse-kpi-label">Stok Menipis</span>
                        <div class="warehouse-kpi-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    </div>
                    <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['low_stock_items']) }}</strong>
                    <small>Item berada di bawah batas minimum.</small>
                </article>
                <article class="warehouse-kpi-card warehouse-kpi-card-success">
                    <div class="warehouse-kpi-header">
                        <span class="warehouse-kpi-label">Stock In Baru</span>
                        <div class="warehouse-kpi-icon" aria-hidden="true"><i class="bi bi-box-arrow-in-down"></i></div>
                    </div>
                    <div class="warehouse-kpi-metrics" aria-label="Ringkasan Stock In Baru">
                        <div><span>Hari ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_in_today']['quantity']) }}</strong></div>
                        <div><span>Bulan ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_in_month']['quantity']) }}</strong></div>
                    </div>
                </article>
                <article class="warehouse-kpi-card warehouse-kpi-card-navy">
                    <div class="warehouse-kpi-header">
                        <span class="warehouse-kpi-label">Stock Out Baru</span>
                        <div class="warehouse-kpi-icon" aria-hidden="true"><i class="bi bi-box-arrow-up"></i></div>
                    </div>
                    <div class="warehouse-kpi-metrics" aria-label="Ringkasan Stock Out Baru">
                        <div><span>Hari ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_out_today']['quantity']) }}</strong></div>
                        <div><span>Bulan ini</span><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($summary['stock_out_month']['quantity']) }}</strong></div>
                    </div>
                </article>
            </section>
        @endif

        {{-- INVENTORY STATUS TABLE SECTION --}}
        @if ($activeCondition === 'USED')
            <section class="warehouse-data-grid warehouse-data-grid-single" aria-label="Status Inventori Stok Bekas">
                <x-warehouse.panel title="Status Inventori Bekas" subtitle="Saldo fisik dan ketersediaan consumable kondisi bekas per lokasi penyimpanan.">
                    <x-slot:header>
                        @if ($canStockIn || $canStockOut)
                            <a href="{{ route('warehouse.transactions-used.create') }}" class="btn btn-sm btn-outline-warning">Stock In/Out Bekas</a>
                        @endif
                    </x-slot:header>
                    <div class="warehouse-table-wrap">
                        <table class="table warehouse-table align-middle" aria-label="Tabel inventori stok bekas">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Total Stok Bekas</th>
                                    <th>DS8 Bekas</th>
                                    <th>Deltamas Bekas</th>
                                    <th>Rata-rata Pemakaian Bekas</th>
                                    @if ($canStockOut)<th>Aksi</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($usedStock as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->item_name }}</strong>
                                            <small class="d-block text-muted font-monospace">{{ $item->item_code }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ \App\Services\Warehouse\WarehouseQuantity::display(($item->stock_used_ds8 ?? 0) + ($item->stock_used_deltamas ?? 0)) }}</span>
                                            <span class="text-muted small">{{ $item->unit }}</span>
                                        </td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->stock_used_ds8 ?? 0) }}</td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->stock_used_deltamas ?? 0) }}</td>
                                        <td>{{ $item->average_consume_used ?? 0 }} pcs/bulan</td>
                                        @if ($canStockOut)
                                            <td>
                                                <a class="btn btn-sm btn-outline-warning text-nowrap" href="{{ route('warehouse.transactions-used.create') }}">Stock Out Bekas</a>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canStockOut ? 6 : 5 }}">
                                            <x-warehouse.empty-state title="Belum ada stok bekas" message="Semua item saat ini memiliki saldo bekas 0 pcs." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($usedStock->hasPages())
                        <div class="warehouse-panel-pagination">{{ $usedStock->links('pagination::warehouse-bootstrap-5') }}</div>
                    @endif
                </x-warehouse.panel>
            </section>
        @else
            <section class="warehouse-data-grid warehouse-data-grid-single" aria-label="Prioritas Warehouse Stok Baru">
                <x-warehouse.panel title="Status Stok Baru" subtitle="Prioritaskan pengisian ulang untuk item dengan stok menipis atau habis. Catatan tersimpan tetap tersedia saat stok kembali sehat.">
                    <x-slot:header>
                        @if ($canStockIn)
                            <a href="{{ route('warehouse.transactions.create', ['type' => 'IN']) }}" class="btn btn-sm btn-outline-primary">Stock In</a>
                        @endif
                    </x-slot:header>
                    <div class="warehouse-table-wrap">
                        <table class="table warehouse-table align-middle" aria-label="Stok rendah dan kosong">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Stok total</th>
                                    <th>DS8</th>
                                    <th>Deltamas</th>
                                    <th>Minimum</th>
                                    <th>Maximum</th>
                                    <th>Average Consume</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                    @if ($canStockIn)<th>Aksi</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStock as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->item_name }}</strong>
                                            <small class="d-block text-muted font-monospace">{{ $item->item_code }}</small>
                                        </td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->current_stock) }}</td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->stock_ds8) }}</td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->stock_deltamas) }}</td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($item->minimum_stock) }}</td>
                                        <td>
                                            @if ($item->maximum_stock !== null && $item->maximum_stock !== '')
                                                {{ \App\Services\Warehouse\WarehouseQuantity::display($item->maximum_stock) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $item->average_consume ?? 0 }} pcs/bulan</td>
                                        <td><x-warehouse.status-badge :status="$item->stock_status" context="stock" /></td>
                                        <td>
                                            @if ($canUpdateStockAttention)
                                                <form method="POST" action="{{ route('warehouse.dashboard.stock-attention.update', $item) }}" class="d-flex align-items-start gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="visually-hidden" for="stock-attention-note-{{ $item->id }}">Catatan perhatian {{ $item->item_name }}</label>
                                                    <input class="form-control form-control-sm" id="stock-attention-note-{{ $item->id }}" name="stock_attention_note" value="{{ $item->stock_attention_note }}" maxlength="255" placeholder="Tulis catatan">
                                                    <button class="btn btn-sm btn-outline-secondary text-nowrap" type="submit">Simpan</button>
                                                </form>
                                            @else
                                                {{ $item->stock_attention_note ?: '—' }}
                                            @endif
                                        </td>
                                        @if ($canStockIn)
                                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.transactions.create', ['type' => 'IN', 'barcode' => $item->barcode]) }}">Stock In</a></td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canStockIn ? 10 : 9 }}">
                                            <x-warehouse.empty-state title="Stok terkendali" message="Semua item aktif berada di atas minimum." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($lowStock->hasPages())
                        <div class="warehouse-panel-pagination">{{ $lowStock->links('pagination::warehouse-bootstrap-5') }}</div>
                    @endif
                </x-warehouse.panel>
            </section>
        @endif

        {{-- MOVEMENT TREND SECTION --}}
        @php
            $trendFilterIsActive = request()->filled('trend_date_from') || request()->filled('trend_date_to') || request()->filled('movement_machine') || request()->filled('movement_consumable_id');
            $trendFilterHasErrors = $errors->has('trend_date_from') || $errors->has('trend_date_to') || $errors->has('movement_machine') || $errors->has('movement_consumable_id');
            $stockoutPreserved = array_merge(['condition' => $activeCondition], request()->only(['stockout_month', 'stockout_machine']));
            $movementPreserved = array_merge(['condition' => $activeCondition], request()->only(['trend_date_from', 'trend_date_to', 'movement_machine', 'movement_consumable_id']));
        @endphp
        <x-warehouse.panel
            class="warehouse-trend-panel"
            title="Pergerakan stok {{ $activeCondition === 'USED' ? 'Bekas' : 'Baru' }}"
            subtitle="Batang menunjukkan transaksi <b>harian {{ $activeCondition === 'USED' ? 'bekas' : 'baru' }}</b>; garis putus-putus adalah rata-rata pemakaian 7 hari."
        >
            <x-slot:header>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="warehouse-movement-legend">
                        <div class="legend-item"><span class="legend-swatch swatch-masuk"></span>Masuk</div>
                        <div class="legend-item"><span class="legend-swatch swatch-keluar"></span>Keluar</div>
                        <div class="legend-item"><span class="legend-line"></span>Rata-rata 7 hari</div>
                    </div>
                    <form class="warehouse-movement-header-form d-flex align-items-center gap-2 flex-wrap" method="GET" action="{{ route('warehouse.dashboard') }}" aria-label="Filter cepat pergerakan stok">
                        <input type="hidden" name="condition" value="{{ $activeCondition }}">
                        @foreach ($stockoutPreserved as $key => $val)
                            @if ($key !== 'condition' && $val !== null && $val !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                            @endif
                        @endforeach
                        <div class="warehouse-movement-quick-filters d-flex align-items-center gap-2 flex-wrap">
                            <div>
                                <label class="visually-hidden" for="movement-machine">Mesin</label>
                                <select class="form-select form-select-sm" id="movement-machine" name="movement_machine" onchange="const c = this.form.querySelector('[name=movement_consumable_id]'); if (c) c.value = ''; this.form.submit()" aria-label="Filter Mesin">
                                    <option value="">Semua Mesin</option>
                                    @foreach ($movementMachineOptions as $machine)
                                        <option value="{{ $machine }}" @selected(request('movement_machine') === $machine)>{{ $machine }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="visually-hidden" for="movement-consumable">Insert</label>
                                <select class="form-select form-select-sm" id="movement-consumable" name="movement_consumable_id" onchange="this.form.submit()" aria-label="Filter Insert">
                                    <option value="">Semua Insert</option>
                                    @foreach ($movementInsertOptions as $insert)
                                        <option value="{{ $insert->id }}" @selected((string) request('movement_consumable_id') === (string) $insert->id)>{{ $insert->item_name }} ({{ $insert->item_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-sm {{ $trendFilterIsActive ? 'btn-primary' : 'btn-outline-primary' }} warehouse-trend-filter-toggle" type="button" data-warehouse-trend-filter-toggle data-bs-target="#warehouse-trend-filter" aria-controls="warehouse-trend-filter" aria-expanded="{{ $trendFilterHasErrors ? 'true' : 'false' }}" aria-label="{{ $trendFilterIsActive ? 'Filter tren aktif' : 'Filter analitik Warehouse' }}"><i class="bi bi-funnel" aria-hidden="true"></i><span>Filter periode</span></button>
                            @if ($trendFilterIsActive)
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.dashboard', $stockoutPreserved) }}">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>
            </x-slot:header>
            <div @class(['collapse', 'show' => $trendFilterHasErrors]) id="warehouse-trend-filter">
                <form class="warehouse-trend-filter" method="GET" action="{{ route('warehouse.dashboard') }}" aria-label="Filter rentang tanggal pergerakan">
                    <input type="hidden" name="condition" value="{{ $activeCondition }}">
                    @foreach ($stockoutPreserved as $key => $val)
                        @if ($key !== 'condition' && $val !== null && $val !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                        @endif
                    @endforeach
                    @if (request()->filled('movement_machine'))
                        <input type="hidden" name="movement_machine" value="{{ request('movement_machine') }}">
                    @endif
                    @if (request()->filled('movement_consumable_id'))
                        <input type="hidden" name="movement_consumable_id" value="{{ request('movement_consumable_id') }}">
                    @endif
                    <div class="warehouse-trend-filter-grid">
                        <div><label class="form-label" for="trend-date-from">Dari tanggal</label><input class="form-control @error('trend_date_from') is-invalid @enderror" id="trend-date-from" type="date" name="trend_date_from" value="{{ request('trend_date_from', $trendFilter->from->toDateString()) }}">@error('trend_date_from')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror</div>
                        <div><label class="form-label" for="trend-date-to">Sampai tanggal</label><input class="form-control @error('trend_date_to') is-invalid @enderror" id="trend-date-to" type="date" name="trend_date_to" value="{{ request('trend_date_to', $trendFilter->to->toDateString()) }}">@error('trend_date_to')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror</div>
                        <div class="warehouse-trend-filter-actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard', array_merge($stockoutPreserved, request()->only(['movement_machine', 'movement_consumable_id']))) }}">Atur ulang</a></div>
                    </div>
                </form>
            </div>
            <div class="warehouse-analytics-grid warehouse-analytics-grid-trend">
                <section class="warehouse-analytics-block warehouse-movement-chart-block" aria-labelledby="warehouse-movement-chart-title">
                    <h3 id="warehouse-movement-chart-title" class="visually-hidden">Grafik Pergerakan Stok {{ $activeCondition === 'USED' ? 'Bekas' : 'Baru' }}</h3>
                    @if ($movementChart['has_data'])
                        <div class="warehouse-chart-scroll-wrap" data-warehouse-movement-scroll>
                            <div class="warehouse-chart-frame warehouse-movement-chart-frame">
                                <canvas data-warehouse-movement-chart data-source="warehouse-movement-chart-data" role="img" aria-label="Grafik pergerakan stok masuk, keluar, dan rata-rata 7 hari"></canvas>
                            </div>
                        </div>
                    @else
                        <x-warehouse.empty-state title="Belum ada pergerakan" message="Tidak ada transaksi Stock In atau Stock Out pada periode dan filter yang dipilih." />
                    @endif
                    <script type="application/json" id="warehouse-movement-chart-data">{!! json_encode($movementChart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                    <details class="warehouse-chart-data mt-3">
                        <summary>Lihat tabel data pergerakan</summary>
                        <div class="warehouse-table-wrap">
                            <table class="table warehouse-table" aria-label="Data pergerakan stok harian">
                                <thead><tr><th scope="col">Tanggal</th><th scope="col">Masuk</th><th scope="col">Keluar</th><th scope="col">Rata-rata 7 Hari</th></tr></thead>
                                <tbody>
                                    @foreach ($movementChart['dates'] as $index => $date)
                                        <tr>
                                            <td>{{ $date }}</td>
                                            <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($movementChart['stock_in'][$index] ?? 0) }}</td>
                                            <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($movementChart['stock_out'][$index] ?? 0) }}</td>
                                            <td>{{ (int) ceil((float) ($movementChart['average_7_days'][$index] ?? 0)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                </section>
            </div>
        </x-warehouse.panel>

        {{-- AGREGASI STOCK OUT SECTION --}}
        <x-warehouse.panel
            class="warehouse-top-stock-out-panel"
            title="Agregasi Stock Out {{ $activeCondition === 'USED' ? 'Bekas' : 'Baru' }}"
            subtitle="Total Stock Out {{ $activeCondition === 'USED' ? 'bekas' : 'baru' }} dan pemakaian per mesin (M1–M13) berdasarkan alokasi transaksi."
        >
            <x-slot:header>
                <form class="warehouse-stockout-filter" method="GET" action="{{ route('warehouse.dashboard') }}" aria-label="Filter bulan dan mesin Stock Out">
                    <input type="hidden" name="condition" value="{{ $activeCondition }}">
                    @foreach ($movementPreserved as $key => $val)
                        @if ($key !== 'condition' && $val !== null && $val !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                        @endif
                    @endforeach
                    <div class="warehouse-stockout-filter-row">
                        <div class="warehouse-stockout-filter-field">
                            <label class="form-label visually-hidden" for="stockout-month">Bulan Stock Out</label>
                            <input
                                class="form-control form-control-sm @error('stockout_month') is-invalid @enderror"
                                id="stockout-month"
                                type="month"
                                name="stockout_month"
                                value="{{ request('stockout_month', $stockOutFilter->from->format('Y-m')) }}"
                                max="{{ \Carbon\CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'))->format('Y-m') }}"
                                aria-label="Bulan Stock Out"
                            >
                        </div>
                        <div class="warehouse-stockout-filter-field">
                            <label class="form-label visually-hidden" for="stockout-machine">Mesin Stock Out</label>
                            <select class="form-select form-select-sm" id="stockout-machine" name="stockout_machine" aria-label="Filter Mesin Stock Out">
                                <option value="">Semua Mesin</option>
                                @foreach ($stockOutMachineOptions as $machine)
                                    <option value="{{ $machine }}" @selected(request('stockout_machine') === $machine)>{{ $machine }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="warehouse-stockout-filter-actions">
                            <button class="btn btn-sm btn-primary" type="submit">Terapkan</button>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.dashboard', $movementPreserved) }}">Atur ulang</a>
                        </div>
                    </div>
                    @error('stockout_month')
                        <div class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @enderror
                </form>
            </x-slot:header>

            {{-- TOTAL STOCK OUT & MACHINE CHIPS --}}
            <div class="warehouse-stockout-overview-grid mb-4">
                <div class="warehouse-stockout-total-card">
                    <span class="warehouse-stockout-total-label">Total Stock Out {{ $activeCondition === 'USED' ? 'Bekas' : 'Baru' }}</span>
                    <strong class="warehouse-stockout-total-val">
                        {{ \App\Services\Warehouse\WarehouseQuantity::display($stockOutMetrics['total_stock_out']) }}
                        <small class="fs-6 fw-bold text-muted">pcs</small>
                    </strong>
                    <span class="small text-muted">Total pemakaian mesin periode ini</span>
                </div>
                <div class="warehouse-stockout-machines-card">
                    <span class="warehouse-stockout-machines-title">Alokasi Per Mesin (M1–M13)</span>
                    <div class="warehouse-machine-chips-grid">
                        @forelse ($stockOutMetrics['machine_breakdown'] as $machineCode => $qty)
                            <div class="warehouse-machine-chip">
                                <strong>{{ $machineCode }}</strong>
                                <span>{{ \App\Services\Warehouse\WarehouseQuantity::display($qty) }} pcs</span>
                            </div>
                        @empty
                            <span class="text-muted small">Belum ada pemakaian mesin pada periode ini.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- TOP USAGE CHARTS & TABLES --}}
            <div class="warehouse-analytics-grid warehouse-analytics-grid-top-stock-out">
                <section class="warehouse-analytics-block warehouse-analytics-block-top-stock-out" aria-labelledby="warehouse-top-item-title">
                    <h3 id="warehouse-top-item-title">Top Item Stock Out {{ $activeCondition === 'USED' ? 'Bekas' : 'Baru' }}{{ request('stockout_machine') ? ' ('.request('stockout_machine').')' : '' }}</h3>
                    <div class="warehouse-chart-frame" style="--warehouse-chart-rows: {{ max(1, $topUsage->count()) }};">
                        <canvas data-warehouse-bar-chart data-source="warehouse-top-item-data" role="img" aria-label="Grafik item dengan Stock Out terbanyak"></canvas>
                    </div>
                    <script type="application/json" id="warehouse-top-item-data">{!! json_encode(['labels' => $topUsage->map(fn ($usage) => (string) $usage->item_name)->values(), 'values' => $topUsage->map(fn ($usage) => (float) $usage->quantity)->values()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                    <details class="warehouse-chart-data">
                        <summary>Lihat data tabel</summary>
                        <div class="warehouse-table-wrap">
                            <table class="table warehouse-table" aria-label="Data Top Item Stock Out">
                                <thead>
                                    <tr>
                                        <th scope="col">Nama Item</th>
                                        <th scope="col">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topUsage as $usage)
                                        <tr>
                                            <td>{{ $usage->item_name }}</td>
                                            <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($usage->quantity) }} {{ $usage->unit ?? 'pcs' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2">Belum ada data Stock Out item pada periode ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </details>
                </section>

                <section class="warehouse-analytics-block warehouse-analytics-block-top-stock-out" aria-labelledby="warehouse-top-machine-title">
                    <h3 id="warehouse-top-machine-title">Top Tipe Mesin Stock Out {{ $activeCondition === 'USED' ? 'Bekas' : 'Baru' }}</h3>
                    <div class="warehouse-chart-frame" style="--warehouse-chart-rows: {{ max(1, $topMachineUsage->count()) }};">
                        <canvas data-warehouse-bar-chart data-source="warehouse-top-machine-data" role="img" aria-label="Grafik tipe mesin dengan Stock Out terbanyak"></canvas>
                    </div>
                    <script type="application/json" id="warehouse-top-machine-data">{!! json_encode(['labels' => $topMachineUsage->map(fn ($usage) => (string) $usage->machine_type)->values(), 'values' => $topMachineUsage->map(fn ($usage) => (float) $usage->quantity)->values()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                    <details class="warehouse-chart-data">
                        <summary>Lihat data tabel</summary>
                        <div class="warehouse-table-wrap">
                            <table class="table warehouse-table" aria-label="Data Top Tipe Mesin Stock Out">
                                <thead>
                                    <tr>
                                        <th scope="col">Tipe Mesin</th>
                                        <th scope="col">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topMachineUsage as $usage)
                                        <tr>
                                            <td>{{ $usage->machine_type }}</td>
                                            <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($usage->quantity) }} pcs</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2">Belum ada data Stock Out tipe mesin pada periode ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </details>
                </section>
            </div>
        </x-warehouse.panel>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/dashboard.js')
@endpush
