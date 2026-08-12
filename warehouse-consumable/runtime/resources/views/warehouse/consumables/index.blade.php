@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-consumables-title">
        <x-warehouse.page-header title="Master Consumable" subtitle="Kelola identitas barang, batas stok, dan lokasi penyimpanan.">
            <a class="btn btn-primary" href="{{ route('warehouse.consumables.create') }}">Tambah Barang</a>
        </x-warehouse.page-header>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @php($masterFilterActive = request()->filled('search') || request()->filled('status'))
        <details class="warehouse-filter-disclosure warehouse-panel" @if ($masterFilterActive || $errors->any()) open @endif>
            <summary><span>Filter master</span><span class="warehouse-filter-summary-hint">Cari Item Code, nama, atau status</span></summary>
            <form class="warehouse-filter-card" method="GET" aria-label="Filter Master Consumable">
                <div class="warehouse-filter-grid">
                    <div class="warehouse-filter-field warehouse-filter-field-wide">
                        <label class="form-label" for="warehouse-search">Cari barang</label>
                        <input id="warehouse-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Item Code atau nama barang">
                    </div>
                    <div class="warehouse-filter-field">
                        <label class="form-label" for="warehouse-status">Status</label>
                        <select id="warehouse-status" class="form-select" name="status">
                            <option value="">Semua status</option>
                            <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>Aktif</option>
                            <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Tidak aktif</option>
                        </select>
                    </div>
                    <x-warehouse.filter-actions :reset="route('warehouse.consumables.index')" submit="Terapkan" />
                </div>
            </form>
        </details>

        <x-warehouse.panel class="warehouse-table-panel">
            <div class="warehouse-table-wrap mobile-card-source">
                <table class="table warehouse-table align-middle" aria-label="Master barang habis pakai">
                    <thead>
                        <tr>
                            <th scope="col">Item Code</th>
                            <th scope="col">Nama barang</th>
                            <th scope="col">Stok saat ini</th>
                            <th scope="col">Minimum</th>
                            <th scope="col">Maksimum</th>
                            <th scope="col">Lokasi</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($consumables as $consumable)
                            <tr>
                                <td class="font-monospace">{{ $consumable->item_code }}</td>
                                <td>{{ $consumable->item_name }}</td>
                                <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->current_stock) }} {{ $consumable->unit }}</td>
                                <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->minimum_stock) }} {{ $consumable->unit }}</td>
                                <td>{{ $consumable->maximum_stock !== null ? \App\Services\Warehouse\WarehouseQuantity::display($consumable->maximum_stock).' '.$consumable->unit : '—' }}</td>
                                <td>{{ $consumable->storage_location ?: '—' }}</td>
                                <td>
                                    <x-warehouse.status-badge :status="$consumable->stock_status" context="stock" />
                                    <span class="d-block small warehouse-muted mt-1">{{ $consumable->is_active ? 'Aktif' : 'Tidak aktif' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.consumables.show', $consumable) }}">Detail</a>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.consumables.edit', $consumable) }}">Ubah</a>
                                        <form method="POST" action="{{ route('warehouse.consumables.status', $consumable) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $consumable->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-warehouse.empty-state title="Belum ada barang habis pakai" message="Tambahkan barang pertama untuk memulai master." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="warehouse-card-list">
                @forelse ($consumables as $consumable)
                    <article class="warehouse-mobile-record">
                        <div class="warehouse-mobile-record-heading">
                            <strong>{{ $consumable->item_name }}</strong>
                            <x-warehouse.status-badge :status="$consumable->stock_status" context="stock" />
                        </div>
                        <dl>
                            <dt>Item Code</dt><dd class="font-monospace">{{ $consumable->item_code }}</dd>
                            <dt>Stok saat ini</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->current_stock) }} {{ $consumable->unit }}</dd>
                            <dt>Minimum</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->minimum_stock) }} {{ $consumable->unit }}</dd>
                            <dt>Maksimum</dt><dd>{{ $consumable->maximum_stock !== null ? \App\Services\Warehouse\WarehouseQuantity::display($consumable->maximum_stock).' '.$consumable->unit : '—' }}</dd>
                            <dt>Lokasi</dt><dd>{{ $consumable->storage_location ?: '—' }}</dd>
                            <dt>Status master</dt><dd>{{ $consumable->is_active ? 'Aktif' : 'Tidak aktif' }}</dd>
                        </dl>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.consumables.show', $consumable) }}">Detail</a>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.consumables.edit', $consumable) }}">Ubah</a>
                            <form method="POST" action="{{ route('warehouse.consumables.status', $consumable) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $consumable->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <x-warehouse.empty-state title="Belum ada barang habis pakai" />
                @endforelse
            </div>

            {{ $consumables->links('pagination::warehouse-bootstrap-5') }}
        </x-warehouse.panel>
    </div>
@endsection
