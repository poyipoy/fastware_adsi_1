@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page" data-warehouse-validation-workspace aria-labelledby="warehouse-validation-title">
        <x-warehouse.page-header title="Validasi Stok" subtitle="Periksa penerimaan barang dan pengiriman antarlokasi yang menunggu pemeriksaan fisik.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="status">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="warehouse-history-totals" aria-label="Ringkasan validasi stok">
            <article><span>Menunggu Validasi</span><strong>{{ $pending->count() }}</strong></article>
            <article><span>Sudah Divalidasi</span><strong>{{ $validated->count() }}</strong></article>
        </div>

        <x-warehouse.panel title="Menunggu Validasi" subtitle="Pemeriksaan fisik dicatat menggunakan akun Anda yang sedang aktif.">
            <div class="warehouse-table-wrap">
                <table class="table warehouse-table align-middle" aria-label="Transaksi menunggu validasi">
                    <thead><tr><th>Jenis</th><th>No. Referensi</th><th>Dibuat</th><th>Barang</th><th>Kondisi</th><th>Qty</th><th>Lokasi / Rute</th><th>Pembuat</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($pending as $record)
                            <tr>
                                <td><span class="warehouse-status-badge warehouse-status-badge-warning"><span class="warehouse-status-dot" aria-hidden="true"></span><span>{{ $record['kind'] }}</span></span></td>
                                <td class="font-monospace">{{ $record['reference'] }}</td>
                                <td>{{ optional($record['created_at'])->format('Y-m-d H:i') }}</td>
                                <td><strong>{{ $record['item'] }}</strong><small class="d-block warehouse-muted font-monospace">{{ $record['item_code'] }}</small></td>
                                <td>{{ $record['condition'] }}</td>
                                <td>{{ \App\Services\Warehouse\WarehouseQuantity::display($record['quantity']) }} {{ $record['unit'] }}</td>
                                <td>{{ $record['location'] }}</td>
                                <td>{{ $record['actor'] }}</td>
                                <td class="text-nowrap">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <form method="POST" action="{{ $record['approve_url'] }}" class="d-inline" onsubmit="return confirm('Apakah Anda yakin menyetujui Stock In {{ $record['reference'] }} sejumlah {{ \App\Services\Warehouse\WarehouseQuantity::display($record['quantity']) }} {{ $record['unit'] }}?');">
                                            @csrf
                                            <input type="hidden" name="quantity_received" value="{{ $record['quantity'] }}">
                                            <input type="hidden" name="validation_result" value="MATCH">
                                            <input type="hidden" name="validation_notes" value="Disetujui langsung oleh validator">
                                            <input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}">
                                            <input type="hidden" name="redirect_to" value="validations">
                                            <button type="submit" class="btn btn-sm btn-success text-nowrap">Setujui</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $loop->index }}">Tolak</button>
                                        <a class="btn btn-sm btn-outline-secondary text-nowrap" href="{{ $record['detail_url'] }}">Detail</a>
                                    </div>

                                    <div class="modal fade" id="rejectModal-{{ $loop->index }}" tabindex="-1" aria-labelledby="rejectModalLabel-{{ $loop->index }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ $record['reject_url'] }}">
                                                    @csrf
                                                    <input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}">
                                                    <input type="hidden" name="redirect_to" value="validations">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="rejectModalLabel-{{ $loop->index }}">Tolak Stock In {{ $record['reference'] }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-2">Barang: <strong>{{ $record['item'] }}</strong> ({{ $record['condition'] }})</p>
                                                        <p class="mb-3">Jumlah: <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($record['quantity']) }} {{ $record['unit'] }}</strong></p>
                                                        <div class="mb-3 text-start">
                                                            <label for="reject-reason-{{ $loop->index }}" class="form-label warehouse-required">Alasan Penolakan</label>
                                                            <textarea class="form-control" id="reject-reason-{{ $loop->index }}" name="reason" rows="3" required placeholder="Tuliskan alasan penolakan..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger">Tolak Stock In</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
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
                                <td><span class="warehouse-status-badge warehouse-status-badge-success"><span class="warehouse-status-dot" aria-hidden="true"></span><span>{{ $record['kind'] }}</span></span></td>
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
