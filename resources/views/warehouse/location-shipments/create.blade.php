@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/location-shipments.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-shipment-page" aria-labelledby="warehouse-shipment-create-title">
        <x-warehouse.page-header title="Buat Pengiriman Antar Lokasi" subtitle="Stok asal belum berubah. Quantity akan di-reserve sampai serah terima selesai."><a class="btn btn-outline-secondary" href="{{ route('warehouse.location-shipments.index') }}">Riwayat Pengiriman</a></x-warehouse.page-header>
        @if ($errors->any())<div class="alert alert-danger" role="alert"><strong>Pengiriman belum dapat dibuat.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form class="warehouse-panel warehouse-shipment-form" method="POST" action="{{ route('warehouse.location-shipments.store') }}">
            @csrf
            <div class="warehouse-panel-body warehouse-shipment-form-grid">
                <div class="warehouse-form-field warehouse-detail-full"><label class="form-label warehouse-required" for="shipment-item">Barang</label><select class="form-select" id="shipment-item" name="consumable_id" required><option value="">Pilih barang</option>@foreach($consumables as $item)<option value="{{ $item->getKey() }}" @selected((string) old('consumable_id') === (string) $item->getKey())>{{ $item->item_name }} · {{ $item->item_code }}</option>@endforeach</select></div>
                <div class="warehouse-form-field"><label class="form-label warehouse-required" for="shipment-condition">Kondisi dikirim</label><select class="form-select" id="shipment-condition" name="item_condition" required><option value="NEW" @selected(old('item_condition', 'NEW') === 'NEW')>Baru (NEW)</option><option value="USED" @selected(old('item_condition') === 'USED')>Bekas (USED)</option></select></div>
                <div class="warehouse-form-field"><label class="form-label warehouse-required" for="shipment-quantity">Qty dikirim</label><input class="form-control" id="shipment-quantity" name="quantity" type="number" min="0.001" step="0.001" value="{{ old('quantity') }}" required></div>
                <div class="warehouse-form-field"><label class="form-label warehouse-required" for="shipment-from">Lokasi asal</label><select class="form-select" id="shipment-from" name="from_location" required>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}" @selected(old('from_location', 'Deltamas') === $location)>{{ $location }}</option>@endforeach</select></div>
                <div class="warehouse-form-field"><label class="form-label warehouse-required" for="shipment-to">Lokasi tujuan</label><select class="form-select" id="shipment-to" name="to_location" required>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}" @selected(old('to_location', 'DS8') === $location)>{{ $location }}</option>@endforeach</select></div>
                <div class="warehouse-form-field warehouse-detail-full"><label class="form-label" for="shipment-notes">Catatan Pengirim</label><textarea class="form-control" id="shipment-notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea></div>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2"><input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) str()->uuid()) }}"><button class="btn btn-primary" type="submit">Buat Pengiriman</button><a class="btn btn-outline-secondary" href="{{ route('warehouse.location-shipments.index') }}">Batal</a></div>
        </form>
    </div>
@endsection
