@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    @php($photoDisk = \Illuminate\Support\Facades\Storage::disk(config('warehouse.photos.disk', 'public')))
    <div class="warehouse-management-page" aria-labelledby="warehouse-consumables-title">
        <x-warehouse.page-header title="Master Consumable" subtitle="Kelola identitas, foto, tipe mesin, batas, dan saldo per lokasi."><a class="btn btn-primary" href="{{ route('warehouse.consumables.create') }}">Tambah Barang</a></x-warehouse.page-header>
        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

        @php($masterFilterActive = request()->filled('search') || request()->filled('status'))
        <details class="warehouse-filter-disclosure warehouse-panel" @if ($masterFilterActive || $errors->any()) open @endif>
            <summary><span>Filter master</span><span class="warehouse-filter-summary-hint">Cari Item Code, nama, tipe mesin, atau status</span></summary>
            <form class="warehouse-filter-card" method="GET" aria-label="Filter Master Consumable"><div class="warehouse-filter-grid"><div class="warehouse-filter-field warehouse-filter-field-wide"><label class="form-label" for="warehouse-search">Cari barang</label><input id="warehouse-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Item Code, nama, atau tipe mesin"></div><div class="warehouse-filter-field"><label class="form-label" for="warehouse-status">Status</label><select id="warehouse-status" class="form-select" name="status"><option value="">Semua status</option><option value="ACTIVE" @selected(request('status') === 'ACTIVE')>Aktif</option><option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Tidak aktif</option></select></div><x-warehouse.filter-actions :reset="route('warehouse.consumables.index')" submit="Terapkan" /></div></form>
        </details>

        <x-warehouse.panel class="warehouse-table-panel">
            <div class="warehouse-table-wrap mobile-card-source">
                <table class="table warehouse-table warehouse-consumable-table align-middle" aria-label="Master barang habis pakai">
                    <thead><tr><th scope="col">Foto</th><th scope="col">Consumable</th><th scope="col">Tipe Mesin</th><th scope="col">Stok</th><th scope="col">Min / Max</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($consumables as $consumable)
                            @php($photoUrl = $consumable->photo_path ? $photoDisk->url($consumable->photo_path) : null)
                            <tr>
                                <td><span class="warehouse-item-thumbnail">@if($photoUrl)<img src="{{ $photoUrl }}" alt="Foto {{ $consumable->item_name }}" width="76" height="56">@else<span aria-hidden="true">WH</span>@endif</span></td>
                                <td><div class="warehouse-consumable-cell"><strong>{{ $consumable->item_name }}</strong><small class="font-monospace">{{ $consumable->item_code }}</small></div></td>
                                <td>{{ $consumable->machine_type ?: '—' }}</td>
                                <td><div class="warehouse-stock-cell"><strong>Total {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->current_stock) }} {{ $consumable->unit }}</strong><span><b>DS8</b> Baru {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->availableAt('DS8', \App\Enums\Warehouse\WarehouseItemCondition::NEW)) }} · Bekas {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->stock_used_ds8) }}</span><span><b>Deltamas</b> Baru {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->availableAt('Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW)) }} · Bekas {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->stock_used_deltamas) }}</span></div></td>
                                <td><div class="warehouse-minmax-cell"><span>Min <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->minimum_stock) }}</strong></span><span>Max <strong>{{ $consumable->maximum_stock !== null ? \App\Services\Warehouse\WarehouseQuantity::display($consumable->maximum_stock) : '—' }}</strong></span></div></td>
                                <td><x-warehouse.status-badge :status="$consumable->stock_status" context="stock" /><span class="d-block small warehouse-muted mt-1">{{ $consumable->is_active ? 'Aktif' : 'Tidak aktif' }}</span></td>
                                <td><div class="d-flex flex-wrap gap-1"><a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.consumables.show', $consumable) }}">Detail</a><a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.consumables.edit', $consumable) }}">Ubah</a><form method="POST" action="{{ route('warehouse.consumables.status', $consumable) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary" type="submit">{{ $consumable->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-warehouse.empty-state title="Belum ada barang habis pakai" message="Tambahkan barang pertama untuk memulai master." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="warehouse-card-list">@forelse ($consumables as $consumable)@php($photoUrl = $consumable->photo_path ? $photoDisk->url($consumable->photo_path) : null)<article class="warehouse-mobile-record"><div class="warehouse-mobile-record-heading warehouse-consumable-mobile-heading"><div class="d-flex align-items-center gap-2"><span class="warehouse-item-thumbnail warehouse-item-thumbnail-small">@if($photoUrl)<img src="{{ $photoUrl }}" alt="" width="48" height="40">@else<span aria-hidden="true">WH</span>@endif</span><div><strong>{{ $consumable->item_name }}</strong><small class="d-block font-monospace warehouse-muted">{{ $consumable->item_code }}</small></div></div><x-warehouse.status-badge :status="$consumable->stock_status" context="stock" /></div><dl><dt>Tipe mesin</dt><dd>{{ $consumable->machine_type ?: '—' }}</dd><dt>Stok total</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->current_stock) }} {{ $consumable->unit }}</dd><dt>DS8</dt><dd>Baru {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->availableAt('DS8', \App\Enums\Warehouse\WarehouseItemCondition::NEW)) }} · Bekas {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->stock_used_ds8) }}</dd><dt>Deltamas</dt><dd>Baru {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->availableAt('Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW)) }} · Bekas {{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->stock_used_deltamas) }}</dd><dt>Status master</dt><dd>{{ $consumable->is_active ? 'Aktif' : 'Tidak aktif' }}</dd></dl><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.consumables.show', $consumable) }}">Detail</a><a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.consumables.edit', $consumable) }}">Ubah</a></div></article>@empty<x-warehouse.empty-state title="Belum ada barang habis pakai" />@endforelse</div>
            {{ $consumables->links('pagination::warehouse-bootstrap-5') }}
        </x-warehouse.panel>
    </div>
@endsection
