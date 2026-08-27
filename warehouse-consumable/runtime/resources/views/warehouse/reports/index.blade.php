@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/reporting.css')
@endpush

@section('warehouse-content')
    @php
        $reportConditions = ['ALL' => 'ALL', 'NEW' => 'BARU', 'USED' => 'BEKAS'];
        $activeConditionLabel = $reportConditions[$condition] ?? 'BARU';
    @endphp
    <div class="warehouse-report-page" aria-labelledby="warehouse-report-title">
        <x-warehouse.page-header title="Reporting Stok Tahunan" subtitle="Saldo akhir bulanan kondisi {{ $activeConditionLabel }} sampai bulan terakhir yang memiliki transaksi.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        <nav class="warehouse-report-tabs" aria-label="Kondisi reporting" role="tablist">
            @foreach ($reportConditions as $value => $label)
                <a class="warehouse-report-tab {{ $condition === $value ? 'is-active' : '' }}" href="{{ route('warehouse.reports.index', ['year' => $year, 'condition' => $value]) }}" role="tab" aria-selected="{{ $condition === $value ? 'true' : 'false' }}" aria-controls="warehouse-report-matrix">{{ $label }}</a>
            @endforeach
        </nav>

        <form class="warehouse-panel warehouse-report-filter" method="GET" action="{{ route('warehouse.reports.index') }}">
            <div class="warehouse-panel-body"><input type="hidden" name="condition" value="{{ $condition }}"><div class="warehouse-report-filter-heading"><span class="warehouse-eyebrow">Periode</span><label class="form-label" for="report-year">Tahun kalender</label></div><div class="warehouse-report-filter-row"><input class="form-control" id="report-year" name="year" type="number" min="2000" max="{{ now()->year + 1 }}" value="{{ $year }}"><button class="btn btn-primary" type="submit">Tampilkan</button></div></div>
        </form>

        @if ($report['cutoff'])
            <p class="warehouse-report-context" role="status">Kondisi: {{ $activeConditionLabel }}. Periode laporan: Januari-{{ $report['cutoff']->locale('id')->translatedFormat('F Y') }}. Total dan rata-rata dihitung dari saldo akhir bulanan.</p>
        @else
            <div class="alert alert-info" role="status">Belum ada transaksi Warehouse kondisi {{ $activeConditionLabel }} pada tahun {{ $year }}. Semua item tetap ditampilkan dengan Total dan Average nol.</div>
        @endif

        <section class="warehouse-report-matrix-card" id="warehouse-report-matrix" aria-labelledby="warehouse-report-matrix-title">
            <header class="warehouse-report-matrix-header">
                <div>
                    <span class="warehouse-eyebrow">Warehouse</span>
                    <h2 id="warehouse-report-matrix-title">Saldo bulanan per barang</h2>
                </div>
            </header>

            @if ($report['items']->isNotEmpty())
                <p class="warehouse-report-matrix-hint" id="warehouse-report-matrix-hint"><span aria-hidden="true">↔</span> Geser tabel secara horizontal untuk melihat semua bulan.</p>
                <div class="warehouse-table-wrap warehouse-report-matrix-wrap">
                    <table class="table warehouse-table warehouse-report-matrix" aria-describedby="warehouse-report-matrix-hint" aria-label="Matrix saldo Warehouse tahun {{ $year }}">
                        <caption class="visually-hidden">Saldo awal, mutasi masuk, mutasi keluar, saldo akhir, total, dan average setiap barang per bulan.</caption>
                        <thead>
                            <tr>
                                <th class="warehouse-report-item-column" scope="col" rowspan="{{ $report['months']->isNotEmpty() ? 2 : 1 }}">Nama Barang</th>
                                <th class="warehouse-report-summary-column warehouse-report-minmax-column" scope="col" rowspan="{{ $report['months']->isNotEmpty() ? 2 : 1 }}">Minimum</th>
                                <th class="warehouse-report-summary-column warehouse-report-minmax-column" scope="col" rowspan="{{ $report['months']->isNotEmpty() ? 2 : 1 }}">Maksimum</th>
                                @foreach ($report['months'] as $month)
                                    <th class="warehouse-report-month-heading" scope="colgroup" colspan="4">{{ $month['label'] }}</th>
                                @endforeach
                                <th class="warehouse-report-summary-column" scope="col" rowspan="{{ $report['months']->isNotEmpty() ? 2 : 1 }}">Total</th>
                                <th class="warehouse-report-summary-column" scope="col" rowspan="{{ $report['months']->isNotEmpty() ? 2 : 1 }}">Average</th>
                            </tr>
                            @if ($report['months']->isNotEmpty())
                                <tr>
                                    @foreach ($report['months'] as $month)
                                        <th class="warehouse-report-month-start" scope="col">Stok Awal</th>
                                        <th scope="col">Mutasi (+)</th>
                                        <th scope="col">Mutasi (-)</th>
                                        <th class="warehouse-report-ending-column" scope="col">Stok Akhir</th>
                                    @endforeach
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @foreach ($report['items'] as $item)
                                @php($itemMonths = $item['months']->keyBy('key'))
                                <tr>
                                    <th class="warehouse-report-item-column" scope="row">{{ $item['item_name'] }}</th>
                                    <td class="warehouse-report-summary-column warehouse-report-minmax-column">{{ \App\Services\Warehouse\WarehouseQuantity::display($item['minimum_stock']) }}</td>
                                    <td class="warehouse-report-summary-column warehouse-report-minmax-column">{{ $item['maximum_stock'] === null ? '—' : \App\Services\Warehouse\WarehouseQuantity::display($item['maximum_stock']) }}</td>
                                    @foreach ($report['months'] as $month)
                                        @php($monthData = $itemMonths->get($month['key']))
                                        <td class="warehouse-report-month-start">{{ \App\Services\Warehouse\WarehouseQuantity::display($monthData['opening'] ?? '0.000') }}</td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($monthData['incoming'] ?? '0.000') }}</td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($monthData['outgoing'] ?? '0.000') }}</td>
                                        <td class="warehouse-report-ending-column"><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($monthData['ending'] ?? '0.000') }}</strong></td>
                                    @endforeach
                                    <td class="warehouse-report-summary-column"><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($item['total']) }}</strong></td>
                                    <td class="warehouse-report-summary-column"><strong>{{ $item['average'] }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="warehouse-report-matrix-empty"><x-warehouse.empty-state title="Master consumable belum tersedia" /></div>
            @endif
        </section>

        @if ($report['items']->isNotEmpty())
            <section class="warehouse-card-list warehouse-report-mobile-cards" aria-label="Ringkasan reporting mobile">
                @foreach ($report['items'] as $item)
                    <article class="warehouse-report-mobile-card">
                        <header><div><span class="warehouse-eyebrow">Barang</span><h2>{{ $item['item_name'] }}</h2></div><div class="warehouse-report-mobile-summary"><span>Minimum <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($item['minimum_stock']) }}</strong></span><span>Maksimum <strong>{{ $item['maximum_stock'] === null ? '—' : \App\Services\Warehouse\WarehouseQuantity::display($item['maximum_stock']) }}</strong></span></div></header>
                        <div class="warehouse-report-mobile-months">
                            @forelse ($item['months'] as $month)
                                <div class="warehouse-report-mobile-month"><h3>{{ $month['label'] }}</h3><dl><div><dt>Stok Awal</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($month['opening']) }}</dd></div><div><dt>Mutasi (+)</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($month['incoming']) }}</dd></div><div><dt>Mutasi (-)</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($month['outgoing']) }}</dd></div><div class="warehouse-report-mobile-ending"><dt>Stok Akhir</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($month['ending']) }}</dd></div></dl></div>
                            @empty
                                <p class="warehouse-muted mb-0">Belum ada transaksi pada tahun {{ $year }}.</p>
                            @endforelse
                        </div>
                        <footer><span>Total <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($item['total']) }}</strong></span><span>Average <strong>{{ $item['average'] }}</strong></span></footer>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
@endsection
