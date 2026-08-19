@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/reporting.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-report-page" aria-labelledby="warehouse-report-title">
        <x-warehouse.page-header title="Reporting Stok Tahunan" subtitle="Saldo akhir bulanan seluruh item sampai bulan terakhir yang memiliki transaksi.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        <form class="warehouse-panel warehouse-report-filter" method="GET" action="{{ route('warehouse.reports.index') }}">
            <div class="warehouse-panel-body"><label class="form-label" for="report-year">Tahun kalender</label><div class="warehouse-report-filter-row"><input class="form-control" id="report-year" name="year" type="number" min="2000" max="{{ now()->year + 1 }}" value="{{ $year }}"><button class="btn btn-primary" type="submit">Tampilkan</button></div></div>
        </form>

        @if ($report['cutoff'])
            <p class="warehouse-report-context" role="status">Periode laporan: Januari–{{ $report['cutoff']->locale('id')->translatedFormat('F Y') }}. Total dan rata-rata dihitung dari saldo akhir bulanan.</p>
        @else
            <div class="alert alert-info" role="status">Belum ada transaksi Warehouse pada tahun {{ $year }}. Semua item tetap ditampilkan dengan Total dan Average nol.</div>
        @endif

        <section class="warehouse-report-matrix-card" aria-labelledby="warehouse-report-matrix-title">
            <header class="warehouse-report-matrix-header">
                <div>
                    <span class="warehouse-eyebrow">Warehouse</span>
                    <h2 id="warehouse-report-matrix-title">Saldo bulanan per barang</h2>
                </div>
            </header>

            @if ($report['items']->isNotEmpty())
                <div class="warehouse-table-wrap warehouse-report-matrix-wrap">
                    <table class="table warehouse-table warehouse-report-matrix" aria-label="Matrix saldo Warehouse tahun {{ $year }}">
                        <caption class="visually-hidden">Saldo awal, mutasi masuk, mutasi keluar, saldo akhir, total, dan average setiap barang per bulan.</caption>
                        <thead>
                            <tr>
                                <th class="warehouse-report-item-column" scope="col" rowspan="{{ $report['months']->isNotEmpty() ? 2 : 1 }}">Nama Barang</th>
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
                                    @foreach ($report['months'] as $month)
                                        @php($monthData = $itemMonths->get($month['key']))
                                        <td class="warehouse-report-month-start">{{ \App\Services\Warehouse\WarehouseQuantity::display($monthData['opening'] ?? '0.000') }}</td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($monthData['incoming'] ?? '0.000') }}</td>
                                        <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($monthData['outgoing'] ?? '0.000') }}</td>
                                        <td class="warehouse-report-ending-column"><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($monthData['ending'] ?? '0.000') }}</strong></td>
                                    @endforeach
                                    <td class="warehouse-report-summary-column"><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($item['total']) }}</strong></td>
                                    <td class="warehouse-report-summary-column"><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($item['average']) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="warehouse-report-matrix-empty"><x-warehouse.empty-state title="Master consumable belum tersedia" /></div>
            @endif
        </section>
    </div>
@endsection
