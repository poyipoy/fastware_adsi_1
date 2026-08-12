@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" aria-labelledby="warehouse-consumable-detail-title">
        <x-warehouse.page-header title="Detail Barang Habis Pakai" subtitle="Informasi master dan stok yang tercatat melalui pergerakan.">
            <a class="btn btn-outline-primary" href="{{ route('warehouse.consumables.edit', $consumable) }}">Ubah Master Consumable</a>
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.consumables.index') }}">Kembali</a>
        </x-warehouse.page-header>

        <div class="warehouse-detail-grid">
            <x-warehouse.panel title="Informasi barang" class="warehouse-detail-full">
                <dl class="warehouse-definition-list">
                    <dt>Item Code</dt><dd class="font-monospace">{{ $consumable->item_code }}</dd>
                    <dt>Nama barang</dt><dd>{{ $consumable->item_name }}</dd>
                    <dt>Stok saat ini</dt><dd><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->current_stock) }} {{ $consumable->unit }}</strong></dd>
                    <dt>Stok minimum</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->minimum_stock) }} {{ $consumable->unit }}</dd>
                    <dt>Stok maksimum</dt><dd>{{ $consumable->maximum_stock !== null ? \App\Services\Warehouse\WarehouseQuantity::display($consumable->maximum_stock).' '.$consumable->unit : '—' }}</dd>
                    <dt>Lokasi penyimpanan</dt><dd>{{ $consumable->storage_location ?: '—' }}</dd>
                    <dt>Status stok</dt><dd><x-warehouse.status-badge :status="$consumable->stock_status" context="stock" /></dd>
                    <dt>Status master</dt><dd><x-warehouse.status-badge :status="$consumable->is_active ? 'ACTIVE' : 'INACTIVE'" context="activity" /></dd>
                </dl>
            </x-warehouse.panel>

            <section class="warehouse-panel warehouse-detail-full" aria-labelledby="warehouse-opening-balance-title">
                <div class="warehouse-panel-header">
                    <div>
                        <h2 id="warehouse-opening-balance-title">Atur saldo awal</h2>
                        <p>Saldo awal tercatat sebagai pergerakan Stock In dan memerlukan karyawan dengan akses Warehouse.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('warehouse.consumables.opening-balance', $consumable) }}" class="warehouse-panel-body">
                    @csrf
                    <div class="warehouse-detail-grid">
                        <div class="warehouse-form-field">
                            <label class="form-label warehouse-required" for="opening-quantity">Jumlah</label>
                            <input class="form-control" id="opening-quantity" name="quantity" type="number" min="1" step="1" inputmode="numeric" required>
                        </div>
                        <div class="warehouse-form-field">
                            <label class="form-label warehouse-required" for="opening-code">Pindai barcode NPK karyawan</label>
                            <input class="form-control font-monospace" id="opening-code" name="verified_code" inputmode="numeric" autocomplete="off" required>
                        </div>
                        <div class="warehouse-form-field warehouse-detail-full">
                            <label class="form-label warehouse-required" for="opening-reason">Alasan</label>
                            <input class="form-control" id="opening-reason" name="reason" required>
                        </div>
                    </div>
                    <input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}">
                    <button class="btn btn-primary mt-3" type="submit">Catat Saldo Awal</button>
                </form>
            </section>
        </div>
    </div>
@endsection
