@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/management.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-management-page warehouse-adjustment-split-page" aria-labelledby="warehouse-adjustment-title">
        <x-warehouse.page-header title="Penyesuaian Stok" subtitle="Sesuaikan jumlah stok fisik per lokasi dan kondisi barang, serta pantau riwayat perubahannya.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Kembali ke Dashboard</a>
        </x-warehouse.page-header>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
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

        <div class="warehouse-adjustment-layout-grid">
            <!-- Left Column: Form Penyesuaian Stok -->
            <div class="warehouse-adjustment-form-column">
                <div class="alert warehouse-critical-warning" role="alert">
                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i><strong>Verifikasi Khusus Diperlukan:</strong> Penyesuaian stok hanya dapat disetujui oleh PIC atau atasan yang berwenang.
                </div>
                <form class="warehouse-panel" method="POST" action="{{ route('warehouse.adjustments.store') }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ (string) str()->uuid() }}">
                    <div class="warehouse-panel-header">
                        <h2 class="h5 m-0 font-weight-bold">Form Penyesuaian</h2>
                    </div>
                    <div class="warehouse-panel-body">
                        <section class="warehouse-critical-section">
                            <div class="warehouse-form-section-heading">
                                <h3>Barang & Lokasi</h3>
                                <p>Pilih barang, kondisi, dan lokasi gudang yang akan disesuaikan.</p>
                            </div>
                            <div class="warehouse-detail-grid">
                                <div class="warehouse-form-field warehouse-detail-full">
                                    <label class="form-label warehouse-required" for="adjust-item">Barang</label>
                                    <select class="form-select" id="adjust-item" name="consumable_id" required>
                                        <option value="">Pilih barang aktif</option>
                                        @foreach ($consumables as $item)
                                            <option value="{{ $item->id }}" @selected((string) old('consumable_id') === (string) $item->id)>{{ $item->item_code }} — {{ $item->item_name }} ({{ \App\Services\Warehouse\WarehouseQuantity::display($item->current_stock) }} {{ $item->unit }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="warehouse-form-field">
                                    <label class="form-label warehouse-required" for="adjust-condition">Kondisi</label>
                                    <select class="form-select" id="adjust-condition" name="item_condition" required>
                                        <option value="NEW" @selected(old('item_condition', 'NEW') === 'NEW')>Baru</option>
                                        <option value="USED" @selected(old('item_condition') === 'USED')>Bekas</option>
                                    </select>
                                </div>
                                <div class="warehouse-form-field">
                                    <label class="form-label warehouse-required" for="adjust-location">Lokasi</label>
                                    <select class="form-select" id="adjust-location" name="storage_location" required>
                                        @foreach(config('warehouse.storage_locations') as $location)
                                            <option value="{{ $location }}" @selected(old('storage_location', 'DS8') === $location)>{{ $location }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </section>
                        <section class="warehouse-critical-section">
                            <div class="warehouse-form-section-heading">
                                <h3>Rincian Penyesuaian</h3>
                                <p>Tentukan penambahan atau pengurangan beserta jumlah barang fisiknya.</p>
                            </div>
                            <div class="warehouse-detail-grid">
                                <div class="warehouse-form-field">
                                    <label class="form-label warehouse-required" for="adjust-direction">Arah</label>
                                    <select class="form-select" id="adjust-direction" name="direction" required>
                                        <option value="IN" @selected(old('direction', 'IN') === 'IN')>Stock In (Tambah)</option>
                                        <option value="OUT" @selected(old('direction') === 'OUT')>Stock Out (Kurang)</option>
                                    </select>
                                </div>
                                <div class="warehouse-form-field">
                                    <label class="form-label warehouse-required" for="adjust-quantity">Jumlah</label>
                                    <input class="form-control" id="adjust-quantity" name="quantity" type="number" min="0.001" step="0.001" inputmode="decimal" value="{{ old('quantity') }}" required>
                                </div>
                            </div>
                        </section>
                        <section class="warehouse-critical-section">
                            <div class="warehouse-form-section-heading">
                                <h3>Alasan & Verifikasi</h3>
                                <p>Alasan penyesuaian dan identitas pemeriksa akan dicatat resmi dalam riwayat sistem.</p>
                            </div>
                            <div class="warehouse-detail-grid">
                                <div class="warehouse-form-field">
                                    <label class="form-label warehouse-required" for="adjust-category">Kategori alasan</label>
                                    <input class="form-control" id="adjust-category" name="reason_category" value="{{ old('reason_category') }}" required>
                                </div>
                                <div class="warehouse-form-field">
                                    <label class="form-label warehouse-required" for="adjust-code">Pindai barcode NPK Atasan / PIC Berwenang</label>
                                    <input class="form-control font-monospace" id="adjust-code" name="verified_code" inputmode="numeric" autocomplete="off" required>
                                </div>
                                <div class="warehouse-form-field warehouse-detail-full">
                                    <label class="form-label warehouse-required" for="adjust-reason">Alasan / Keterangan Penyesuaian</label>
                                    <textarea class="form-control" id="adjust-reason" name="reason" rows="3" required>{{ old('reason') }}</textarea>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Konfirmasi Penyesuaian</button>
                        <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Batal</a>
                    </div>
                </form>
            </div>

            <!-- Right Column: Riwayat Log Penyesuaian Stok -->
            <div class="warehouse-adjustment-history-column">
                <x-warehouse.panel class="warehouse-adjustment-history-panel" title="Riwayat Log Penyesuaian Stok" subtitle="Daftar seluruh riwayat penyesuaian stok yang telah tersimpan di sistem.">
                    <x-slot:header>
                        @php
                            $filterActive = request()->filled('adjustment_date_from') || request()->filled('adjustment_date_to') || request()->filled('adjustment_consumable_id') || request()->filled('adjustment_condition') || request()->filled('adjustment_direction') || request()->filled('adjustment_verified_user_id');
                        @endphp
                        <button class="btn btn-sm {{ $filterActive ? 'btn-primary' : 'btn-outline-primary' }}" type="button" data-bs-toggle="collapse" data-bs-target="#warehouse-adj-history-filter" aria-expanded="{{ $filterActive ? 'true' : 'false' }}" aria-controls="warehouse-adj-history-filter">
                            <i class="bi bi-funnel" aria-hidden="true"></i> Filter Riwayat
                        </button>
                    </x-slot:header>

                    <div @class(['collapse', 'show' => $filterActive]) id="warehouse-adj-history-filter">
                        <form class="p-3 border-bottom bg-light" method="GET" action="{{ route('warehouse.adjustments.create') }}" aria-label="Filter riwayat penyesuaian">
                            <div class="row g-2">
                                <div class="col-sm-6 col-md-4">
                                    <label class="form-label small" for="adj-filter-date-from">Dari tanggal</label>
                                    <input class="form-control form-control-sm" id="adj-filter-date-from" type="date" name="adjustment_date_from" value="{{ request('adjustment_date_from') }}">
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <label class="form-label small" for="adj-filter-date-to">Sampai tanggal</label>
                                    <input class="form-control form-control-sm" id="adj-filter-date-to" type="date" name="adjustment_date_to" value="{{ request('adjustment_date_to') }}">
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <label class="form-label small" for="adj-filter-direction">Arah</label>
                                    <select class="form-select form-select-sm" id="adj-filter-direction" name="adjustment_direction">
                                        <option value="">Semua Arah</option>
                                        <option value="IN" @selected(request('adjustment_direction') === 'IN')>Stock In (Tambah)</option>
                                        <option value="OUT" @selected(request('adjustment_direction') === 'OUT')>Stock Out (Kurang)</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-6">
                                    <label class="form-label small" for="adj-filter-consumable">Barang</label>
                                    <select class="form-select form-select-sm" id="adj-filter-consumable" name="adjustment_consumable_id">
                                        <option value="">Semua Barang</option>
                                        @foreach ($consumables as $item)
                                            <option value="{{ $item->id }}" @selected((string) request('adjustment_consumable_id') === (string) $item->id)>{{ $item->item_name }} ({{ $item->item_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label small" for="adj-filter-condition">Kondisi</label>
                                    <select class="form-select form-select-sm" id="adj-filter-condition" name="adjustment_condition">
                                        <option value="">Semua Kondisi</option>
                                        <option value="NEW" @selected(request('adjustment_condition') === 'NEW')>Baru</option>
                                        <option value="USED" @selected(request('adjustment_condition') === 'USED')>Bekas</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <label class="form-label small" for="adj-filter-verifier">Verifikator</label>
                                    <select class="form-select form-select-sm" id="adj-filter-verifier" name="adjustment_verified_user_id">
                                        <option value="">Semua Verifikator</option>
                                        @foreach ($users as $u)
                                            <option value="{{ $u->id }}" @selected((string) request('adjustment_verified_user_id') === (string) $u->id)>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                    <button class="btn btn-sm btn-primary" type="submit">Terapkan</button>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('warehouse.adjustments.create') }}">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="warehouse-table-wrap mobile-card-source">
                        <table class="table warehouse-table warehouse-history-table align-middle" aria-label="Riwayat log penyesuaian stok">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>No. Transaksi</th>
                                    <th>Arah</th>
                                    <th>Kondisi</th>
                                    <th>Barang</th>
                                    <th>Jumlah</th>
                                    <th>Stok Awal → Akhir</th>
                                    <th>Karyawan</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($adjustments as $adj)
                                    @php
                                        $isPositive = $adj->movementDelta() >= 0;
                                        $isCancelled = $adj->reversal !== null;
                                    @endphp
                                    <tr @class(['table-light text-muted' => $isCancelled])>
                                        <td class="text-nowrap">{{ optional($adj->transaction_at)->format('d/m/Y H:i') }}</td>
                                        <td><span class="font-monospace small">{{ $adj->transaction_number }}</span></td>
                                        <td>
                                            @if ($isPositive)
                                                <span class="badge bg-success">IN</span>
                                            @else
                                                <span class="badge bg-danger">OUT</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $adj->item_condition?->label() ?? 'Baru' }}</span></td>
                                        <td>
                                            <div class="warehouse-history-item">
                                                <strong>{{ $adj->consumable?->item_name }}</strong>
                                                <small class="font-monospace d-block text-muted">{{ $adj->consumable?->item_code }}</small>
                                            </div>
                                        </td>
                                        <td class="text-nowrap font-weight-bold">
                                            {{ \App\Services\Warehouse\WarehouseQuantity::display($adj->quantity) }} {{ $adj->consumable?->unit }}
                                        </td>
                                        <td class="text-nowrap small">
                                            {{ \App\Services\Warehouse\WarehouseQuantity::display($adj->stock_before) }} → <strong>{{ \App\Services\Warehouse\WarehouseQuantity::display($adj->stock_after) }}</strong>
                                        </td>
                                        <td>
                                            <span class="small">{{ $adj->verified_user_name }}</span>
                                            @if ($adj->verified_user_section)
                                                <small class="d-block text-muted">{{ $adj->verified_user_section }}</small>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if ($adj->reason_category)
                                                <span class="badge bg-info text-dark">{{ $adj->reason_category }}</span>
                                            @endif
                                            <span class="d-block text-truncate" style="max-width: 150px;" title="{{ $adj->notes }}">{{ $adj->notes ?: '—' }}</span>
                                        </td>
                                        <td>
                                            @if ($isCancelled)
                                                <span class="badge bg-warning text-dark">Dibatalkan</span>
                                            @else
                                                <span class="badge bg-primary">Tercatat</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: .78rem;" href="{{ route('warehouse.transactions.show', $adj) }}">Detail</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11">
                                            <x-warehouse.empty-state title="Belum ada penyesuaian" message="Tidak ada transaksi penyesuaian stok yang cocok dengan filter." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="warehouse-card-list">
                        @forelse ($adjustments as $adj)
                            @php
                                $isPositive = $adj->movementDelta() >= 0;
                                $isCancelled = $adj->reversal !== null;
                            @endphp
                            <article @class(['warehouse-mobile-record', 'opacity-75' => $isCancelled])>
                                <div class="warehouse-mobile-record-heading">
                                    <strong>{{ $adj->transaction_number }}</strong>
                                    <div>
                                        <span @class(['badge me-1', 'bg-success' => $isPositive, 'bg-danger' => ! $isPositive])>{{ $isPositive ? 'IN' : 'OUT' }}</span>
                                        @if ($isCancelled)
                                            <span class="badge bg-warning text-dark">Dibatalkan</span>
                                        @endif
                                    </div>
                                </div>
                                <dl>
                                    <dt>Waktu</dt><dd>{{ optional($adj->transaction_at)->format('d/m/Y H:i') }}</dd>
                                    <dt>Barang</dt><dd>{{ $adj->consumable?->item_name }} <small class="font-monospace">({{ $adj->consumable?->item_code }})</small></dd>
                                    <dt>Jumlah</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($adj->quantity) }} {{ $adj->consumable?->unit }}</dd>
                                    <dt>Stok</dt><dd>{{ \App\Services\Warehouse\WarehouseQuantity::display($adj->stock_before) }} → {{ \App\Services\Warehouse\WarehouseQuantity::display($adj->stock_after) }}</dd>
                                    <dt>Karyawan</dt><dd>{{ $adj->verified_user_name }}</dd>
                                    <dt>Alasan</dt><dd>{{ $adj->notes ?: ($adj->reason_category ?: '—') }}</dd>
                                </dl>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('warehouse.transactions.show', $adj) }}">Detail</a>
                            </article>
                        @empty
                            <x-warehouse.empty-state title="Belum ada penyesuaian" />
                        @endforelse
                    </div>

                    <div class="p-3">
                        {{ $adjustments->links('pagination::warehouse-bootstrap-5') }}
                    </div>
                </x-warehouse.panel>
            </div>
        </div>
    </div>
@endsection
