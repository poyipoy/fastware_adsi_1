@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    @php($photoUrl = $consumable->photo_path ? \Illuminate\Support\Facades\Storage::disk(config('warehouse.photos.disk', 'public'))->url($consumable->photo_path) : null)
    <div class="warehouse-management-page" aria-labelledby="warehouse-consumable-detail-title">
        <x-warehouse.page-header title="Detail Barang Habis Pakai" subtitle="Identitas master dan saldo terpisah per lokasi serta kondisi."><a class="btn btn-outline-primary" href="{{ route('warehouse.consumables.edit', $consumable) }}">Ubah Master</a><a class="btn btn-outline-secondary" href="{{ route('warehouse.consumables.index') }}">Kembali</a></x-warehouse.page-header>

        <section class="warehouse-panel warehouse-consumable-hero" aria-labelledby="warehouse-consumable-detail-title">
            <div class="warehouse-panel-body">
                <div class="warehouse-consumable-hero-content">
                    <span class="warehouse-consumable-hero-photo">@if($photoUrl)<img src="{{ $photoUrl }}" alt="Foto {{ $consumable->item_name }}" width="320" height="220">@else<span aria-hidden="true">WH</span>@endif</span>
                    <div class="warehouse-consumable-hero-copy">
                        <span class="warehouse-eyebrow">Consumable</span>
                        <h2 id="warehouse-consumable-detail-title">{{ $consumable->item_name }}</h2>
                        <p class="warehouse-consumable-hero-code">{{ $consumable->item_code }}</p>
                        <p class="warehouse-muted">{{ $consumable->machine_type ?: 'Tipe mesin belum diisi.' }}</p>
                        <div class="d-flex flex-wrap gap-2"><x-warehouse.status-badge :status="$consumable->stock_status" context="stock" /><x-warehouse.status-badge :status="$consumable->is_active ? 'ACTIVE' : 'INACTIVE'" context="activity" /></div>
                    </div>
                </div>
            </div>
        </section>

        <div class="warehouse-detail-grid warehouse-consumable-detail-grid">
            <x-warehouse.panel title="Stock Overview" subtitle="Saldo Baru dan Bekas tetap dipisahkan per lokasi." class="warehouse-detail-full">
                <div class="warehouse-table-wrap">
                    <table class="table warehouse-table warehouse-consumable-overview-table" aria-label="Stock Overview {{ $consumable->item_name }}">
                        <thead><tr><th>Lokasi</th><th class="text-end">Baru</th><th class="text-end">Bekas</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            <tr><td>DS8</td><td class="text-end">{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->availableAt('DS8', \App\Enums\Warehouse\WarehouseItemCondition::NEW)) }}</td><td class="text-end">{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->stock_used_ds8) }}</td><td class="text-end"><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->stock_ds8) }}</strong></td></tr>
                            <tr><td>Deltamas</td><td class="text-end">{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->availableAt('Deltamas', \App\Enums\Warehouse\WarehouseItemCondition::NEW)) }}</td><td class="text-end">{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->stock_used_deltamas) }}</td><td class="text-end"><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->stock_deltamas) }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </x-warehouse.panel>

            <x-warehouse.panel title="Aturan dan status" subtitle="Batas stok dipakai untuk memantau kebutuhan pengisian ulang." class="warehouse-detail-full">
                <dl class="warehouse-definition-list">
                    <dt>Stok global</dt><dd><strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->current_stock) }} {{ $consumable->unit }}</strong></dd>
                    <dt>Stok minimum</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($consumable->minimum_stock) }}</dd>
                    <dt>Stok maksimum</dt><dd>{{ $consumable->maximum_stock !== null ? \App\Services\Warehouse\WarehouseQuantity::display($consumable->maximum_stock) : '—' }}</dd>
                    <dt>Status stok</dt><dd><x-warehouse.status-badge :status="$consumable->stock_status" context="stock" /></dd>
                    <dt>Status master</dt><dd><x-warehouse.status-badge :status="$consumable->is_active ? 'ACTIVE' : 'INACTIVE'" context="activity" /></dd>
                </dl>
            </x-warehouse.panel>

            <section class="warehouse-panel warehouse-detail-full warehouse-opening-balance-panel" aria-labelledby="warehouse-opening-balance-title">
                <div class="warehouse-panel-header"><div><h2 id="warehouse-opening-balance-title">Atur saldo awal</h2><p>Saldo awal dicatat sebagai movement barang Baru dan memerlukan verifikator.</p></div></div>
                <form method="POST" action="{{ route('warehouse.consumables.opening-balance', $consumable) }}" class="warehouse-panel-body">@csrf<div class="alert warehouse-critical-warning mb-3" role="note"><strong>Perhatian.</strong> Saldo awal memengaruhi saldo Warehouse dan jejak audit.</div><div class="warehouse-detail-grid"><div class="warehouse-form-field"><label class="form-label warehouse-required" for="opening-quantity">Jumlah</label><input class="form-control" id="opening-quantity" name="quantity" type="number" min="1" step="1" inputmode="numeric" required></div><div class="warehouse-form-field"><label class="form-label warehouse-required" for="opening-location">Lokasi</label><select class="form-select" id="opening-location" name="storage_location" required>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}">{{ $location }}</option>@endforeach</select></div><div class="warehouse-form-field"><label class="form-label warehouse-required" for="opening-code">Pindai barcode NPK verifikator</label><input class="form-control font-monospace" id="opening-code" name="verified_code" inputmode="numeric" autocomplete="off" required></div><div class="warehouse-form-field warehouse-detail-full"><label class="form-label warehouse-required" for="opening-reason">Alasan</label><input class="form-control" id="opening-reason" name="reason" required></div></div><input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}"><button class="btn btn-primary mt-3" type="submit">Catat Saldo Awal</button></form>
            </section>
        </div>
    </div>
@endsection
