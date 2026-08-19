@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/location-shipments.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-shipment-page" aria-labelledby="warehouse-shipment-title">
        <x-warehouse.page-header title="Pengiriman Antar Lokasi" subtitle="Pantau pengiriman, serah terima, dan hasil Validasi tanpa mengubah saldo sebelum barang diterima.">
            @can('warehouse.location-shipment.create')<a class="btn btn-primary" href="{{ route('warehouse.location-shipments.create') }}">Buat Pengiriman</a>@endcan
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.create', ['type' => 'IN']) }}">Kembali ke Stock In</a>
        </x-warehouse.page-header>

        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

        <section class="warehouse-panel warehouse-shipment-list-card">
            <div class="warehouse-panel-header"><div><h2>Riwayat Pengiriman</h2><p>Status Menunggu Validasi dan Discrepancy tetap menahan reservation di lokasi asal.</p></div></div>
            <div class="warehouse-table-wrap warehouse-shipment-table-wrap"><table class="table warehouse-table warehouse-shipment-table" aria-label="Daftar Pengiriman Antar Lokasi"><thead><tr><th scope="col">Nomor</th><th scope="col">Barang</th><th scope="col">Rute</th><th scope="col">Jumlah</th><th scope="col">Pengirim</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
                @forelse($shipments as $shipment)
                    <tr><td class="font-monospace">{{ $shipment->shipment_number }}</td><td><strong>{{ $shipment->consumable?->item_name }}</strong><small class="d-block warehouse-muted">{{ $shipment->item_condition?->label() }} · {{ $shipment->consumable?->item_code }}</small></td><td>{{ $shipment->from_location }} → {{ $shipment->to_location }}</td><td>{{ \App\Services\Warehouse\WarehouseQuantity::display($shipment->quantity_sent) }} {{ $shipment->consumable?->unit }}</td><td>{{ $shipment->sender_name_snapshot }}</td><td><x-warehouse.status-badge :status="$shipment->status?->value" context="shipment" /></td><td><div class="d-flex flex-wrap gap-1"><a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.location-shipments.show', $shipment) }}">Detail</a>@if($shipment->canValidate()) @can('warehouse.location-shipment.validate')<a class="btn btn-sm btn-primary" href="{{ route('warehouse.location-shipments.validate-form', $shipment) }}">Validasi</a>@endcan @endif</div></td></tr>
                @empty
                    <tr><td colspan="7"><x-warehouse.empty-state title="Belum ada Pengiriman Antar Lokasi" message="Buat pengiriman dari konteks Stock In untuk memulai serah terima." /></td></tr>
                @endforelse
            </tbody></table></div>
            @if($shipments->hasPages())<div class="warehouse-panel-pagination">{{ $shipments->links('pagination::warehouse-bootstrap-5') }}</div>@endif
        </section>
    </div>
@endsection
