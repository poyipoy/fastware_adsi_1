@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" data-warehouse-validation-workspace aria-labelledby="warehouse-validation-title">
        <x-warehouse.page-header title="Validasi Stok" subtitle="Periksa penerimaan Stock In dan transfer internal yang menunggu validasi.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        <div class="warehouse-history-totals" aria-label="Ringkasan validasi stok">
            <article><span>Menunggu Validasi</span><strong>{{ $pending->count() }}</strong></article>
            <article><span>Sudah Divalidasi</span><strong>{{ $validated->count() }}</strong></article>
        </div>

        <x-warehouse.panel title="Menunggu Validasi" subtitle="Validator adalah akun login yang sedang membuka workspace ini.">
            <div class="warehouse-table-wrap">
                <table class="table warehouse-table align-middle" aria-label="Transaksi menunggu validasi">
                    <thead><tr><th>Jenis</th><th>No. Referensi</th><th>Dibuat</th><th>Barang</th><th>Kondisi</th><th>Qty</th><th>Lokasi / Rute</th><th>Pembuat</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($pending as $record)
                            <tr>
                                <td><span class="badge text-bg-light">{{ $record['kind'] }}</span></td>
                                <td class="font-monospace">{{ $record['reference'] }}</td>
                                <td>{{ optional($record['created_at'])->format('Y-m-d H:i') }}</td>
                                <td><strong>{{ $record['item'] }}</strong><small class="d-block warehouse-muted font-monospace">{{ $record['item_code'] }}</small></td>
                                <td>{{ $record['condition'] }}</td>
                                <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($record['quantity']) }} {{ $record['unit'] }}</td>
                                <td>{{ $record['location'] }}</td>
                                <td>{{ $record['actor'] }}</td>
                                <td class="text-nowrap"><a class="btn btn-sm btn-primary" href="{{ $record['validation_url'] }}">Validasi</a><a class="btn btn-sm btn-outline-secondary ms-1" href="{{ $record['detail_url'] }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><x-warehouse.empty-state title="Tidak ada transaksi menunggu validasi" message="Semua penerimaan dan transfer internal sudah diproses." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-warehouse.panel>

        <x-warehouse.panel class="mt-3" title="Sudah Divalidasi" subtitle="Riwayat singkat validasi terbaru untuk rekonsiliasi operasional.">
            <div class="warehouse-table-wrap">
                <table class="table warehouse-table align-middle" aria-label="Transaksi sudah divalidasi">
                    <thead><tr><th>Jenis</th><th>No. Referensi</th><th>Waktu Validasi</th><th>Barang</th><th>Kondisi</th><th>Qty Aktual</th><th>Lokasi / Rute</th><th>Validator</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($validated as $record)
                            <tr>
                                <td><span class="badge text-bg-success">{{ $record['kind'] }}</span></td>
                                <td class="font-monospace">{{ $record['reference'] }}</td>
                                <td>{{ optional($record['created_at'])->format('Y-m-d H:i') }}</td>
                                <td><strong>{{ $record['item'] }}</strong><small class="d-block warehouse-muted font-monospace">{{ $record['item_code'] }}</small></td>
                                <td>{{ $record['condition'] }}</td>
                                <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($record['quantity']) }} {{ $record['unit'] }}</td>
                                <td>{{ $record['location'] }}</td>
                                <td>{{ $record['actor'] }}</td>
                                <td><a class="btn btn-sm btn-outline-secondary" href="{{ $record['detail_url'] }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><x-warehouse.empty-state title="Belum ada validasi" message="Validasi yang selesai akan muncul di sini." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-warehouse.panel>
    </div>
@endsection
