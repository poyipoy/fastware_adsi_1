@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/stock-in.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-stock-in-page" aria-labelledby="warehouse-stock-in-title">
        <x-warehouse.page-header title="Stock In" subtitle="Catat penerimaan barang terlebih dahulu. Saldo berubah setelah validasi fisik oleh verifikator restricted.">
            @can('warehouse.stock-in.create')<a class="btn btn-primary" href="{{ route('warehouse.stock-in.create') }}">Buat Stock In</a>@endcan
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

        <section class="warehouse-panel warehouse-stock-in-list-card">
            <div class="warehouse-panel-header">
                <div><h2 id="warehouse-stock-in-title">Daftar Stock In</h2><p>Pending tidak dihitung sebagai pergerakan stok.</p></div>
            </div>
            <nav class="warehouse-stock-in-tabs px-3 pt-3" aria-label="Filter status Stock In">
                @foreach(['' => 'Semua', 'WAITING_VALIDATION' => 'Menunggu Validasi', 'VALIDATED' => 'Tervalidasi', 'CANCELLED' => 'Dibatalkan'] as $value => $label)
                    <a class="btn btn-sm {{ $activeStatus === $value ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('warehouse.stock-in.index', $value === '' ? [] : ['status' => $value]) }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="warehouse-table-wrap">
                <table class="table warehouse-table warehouse-stock-in-table" aria-label="Daftar Stock In">
                    <thead><tr><th scope="col">Nomor</th><th scope="col">Item</th><th scope="col">Qty Input</th><th scope="col">Lokasi</th><th scope="col">Dibuat Oleh</th><th scope="col">Tanggal</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($stockIns as $stockIn)
                            <tr>
                                <td class="warehouse-stock-in-number font-monospace">{{ $stockIn->stock_in_number }}</td>
                                <td><strong>{{ $stockIn->consumable?->item_name }}</strong><small class="d-block warehouse-muted">{{ $stockIn->consumable?->item_code }} · {{ $stockIn->item_condition?->label() }}</small></td>
                                <td class="warehouse-stock-in-quantity">{{ \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_expected) }} {{ $stockIn->consumable?->unit }}</td>
                                <td>{{ $stockIn->source_location ? $stockIn->source_location.' → ' : '' }}{{ $stockIn->destination_location }}</td>
                                <td>{{ $stockIn->creator_name_snapshot }}</td>
                                <td>{{ optional($stockIn->created_at)->format('Y-m-d H:i') }}</td>
                                <td><x-warehouse.status-badge :status="$stockIn->status?->value" context="stock-in" /></td>
                                <td><div class="warehouse-stock-in-actions"><a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.stock-in.show', $stockIn) }}">Detail</a>@if($stockIn->canValidate()) @can('warehouse.stock-in.validate')<a class="btn btn-sm btn-primary" href="{{ route('warehouse.stock-in.validate-form', $stockIn) }}">Validasi</a>@endcan @endif</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-warehouse.empty-state title="Belum ada Stock In" message="Buat Stock In ketika ada penerimaan barang baru." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="warehouse-card-list px-3 pb-3">
                @forelse($stockIns as $stockIn)
                    <article class="warehouse-mobile-record">
                        <div class="warehouse-mobile-record-heading"><strong class="font-monospace">{{ $stockIn->stock_in_number }}</strong><x-warehouse.status-badge :status="$stockIn->status?->value" context="stock-in" /></div>
                        <dl><dt>Item</dt><dd>{{ $stockIn->consumable?->item_name }}</dd><dt>Qty input</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($stockIn->quantity_expected) }} {{ $stockIn->consumable?->unit }}</dd><dt>Lokasi</dt><dd>{{ $stockIn->source_location ? $stockIn->source_location.' → ' : '' }}{{ $stockIn->destination_location }}</dd><dt>Dibuat oleh</dt><dd>{{ $stockIn->creator_name_snapshot }}</dd><dt>Tanggal</dt><dd>{{ optional($stockIn->created_at)->format('Y-m-d H:i') }}</dd></dl>
                        <div class="warehouse-stock-in-actions"><a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.stock-in.show', $stockIn) }}">Detail</a>@if($stockIn->canValidate()) @can('warehouse.stock-in.validate')<a class="btn btn-sm btn-primary" href="{{ route('warehouse.stock-in.validate-form', $stockIn) }}">Validasi</a>@endcan @endif</div>
                    </article>
                @empty
                    <x-warehouse.empty-state title="Belum ada Stock In" message="Buat Stock In ketika ada penerimaan barang baru." />
                @endforelse
            </div>
            @if($stockIns->hasPages())<div class="warehouse-panel-pagination">{{ $stockIns->links('pagination::warehouse-bootstrap-5') }}</div>@endif
        </section>
    </div>
@endsection
