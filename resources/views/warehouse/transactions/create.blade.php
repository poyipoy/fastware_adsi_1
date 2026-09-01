@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/transaction-form.css')
    @if($itemCondition->value === 'NEW')
        @vite('resources/css/warehouse/stock-in.css')
    @endif
@endpush

@section('warehouse-content')
    <div class="warehouse-transaction-page" aria-labelledby="warehouse-transaction-title">
        <x-warehouse.page-header :title="$itemCondition->value === 'USED' ? 'Stock In/Out Bekas' : 'Stock In/Out Baru'" :subtitle="$itemCondition->value === 'USED' ? 'Catat Stock In atau Stock Out barang bekas; transfer internal menunggu validasi.' : 'Pilih Stock In untuk penerimaan pending atau Stock Out untuk pemakaian barang baru.'">
            @if($itemCondition->value === 'USED')<a class="btn btn-outline-primary" href="{{ route('warehouse.transactions.create') }}">Buka Barang Baru</a>@else<a class="btn btn-outline-warning" href="{{ route('warehouse.transactions-used.create') }}">Buka Barang Bekas</a>@endif
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Dashboard</a>
        </x-warehouse.page-header>

        @if ($errors->any())<div class="alert alert-danger warehouse-form-alert" role="alert"><strong>Transaksi belum dapat diproses.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if (session('status'))<div class="alert alert-success warehouse-form-alert" role="status">{{ session('status') }}</div>@endif

        <div class="warehouse-transaction-layout">
            <form id="warehouse-stock-in-form" class="warehouse-transaction-card" method="POST" action="{{ route('warehouse.transactions.store') }}" data-warehouse-transaction-form data-warehouse-initial-type="{{ $initialType }}" data-warehouse-initial-barcode="{{ $initialBarcode }}" data-warehouse-requirements='@json($transactionRequirements)' data-catalog-url="{{ route('warehouse.catalog.index') }}" data-scan-item-url="{{ route('warehouse.scans.item') }}" data-scan-user-url="{{ route('warehouse.scans.user') }}" data-validation-workspace-url="{{ route('warehouse.validations.index') }}" novalidate>
                @csrf
                <input type="hidden" name="type" data-warehouse-type-value value="{{ $initialType }}">
                <input type="hidden" name="item_condition" value="{{ $itemCondition->value }}" data-warehouse-condition>
                <input type="hidden" name="idempotency_key" data-warehouse-idempotency-key>

                <div class="warehouse-transaction-body">
                    <ol class="warehouse-stepper" aria-label="Progress transaksi" data-warehouse-stepper>
                        <li class="is-active" data-warehouse-step-indicator="1" aria-current="step"><span>1</span><strong>Pilih barang</strong></li>
                        <li data-warehouse-step-indicator="2"><span>2</span><strong>Rincian transaksi</strong></li>
                        <li data-warehouse-step-indicator="3"><span>3</span><strong>Receipt</strong></li>
                    </ol>

                    <section class="warehouse-flow-step is-active" data-warehouse-step="1" aria-labelledby="warehouse-step-one-title">
                        <div class="warehouse-form-section-heading"><h2 id="warehouse-step-one-title" tabindex="-1">Pilih Barang {{ $itemCondition->label() }}</h2><p>Cari melalui katalog sebagai cara utama memilih barang. Maksimal 16 item ditampilkan per halaman.</p></div>
                        <div class="warehouse-catalog" data-warehouse-catalog="primary">
                            <label class="form-label" for="warehouse-catalog-search">Cari katalog barang</label>
                            <input class="form-control" id="warehouse-catalog-search" type="search" placeholder="Nama, Item Code, barcode, atau tipe mesin" data-warehouse-catalog-search autocomplete="off">
                            <div class="warehouse-catalog-status" data-warehouse-catalog-status role="status" aria-live="polite"></div>
                            <div class="warehouse-catalog-grid" data-warehouse-catalog-grid></div>
                            <button class="btn btn-outline-primary warehouse-catalog-more" type="button" data-warehouse-catalog-more hidden>Muat lebih banyak</button>
                        </div>
                        <div class="warehouse-item-result warehouse-selected-item" data-warehouse-item-result aria-live="polite" aria-atomic="true"><span class="warehouse-muted">Belum ada barang dipilih.</span></div>
                        <div class="warehouse-scan-alternative">
                            <div class="warehouse-scan-alternative-heading"><span>Atau gunakan Item Code / scanner</span><small>Scanner tetap dapat digunakan dengan fokus pada bidang ini lalu tekan Enter.</small></div>
                            <div class="warehouse-form-field">
                                <label class="form-label warehouse-required" for="warehouse-item-barcode">Item Code atau barcode</label>
                                <div class="input-group warehouse-scan-group"><input class="form-control warehouse-scan-input" id="warehouse-item-barcode" name="item_barcode" value="{{ old('item_barcode', $initialBarcode) }}" autocomplete="off" required data-warehouse-item-input><button class="btn btn-outline-primary" type="button" data-warehouse-scan-item>Cari</button></div>
                            </div>
                        </div>

                        <div class="warehouse-step-actions"><button class="btn btn-primary" type="button" data-warehouse-next-detail disabled>Lanjut ke rincian</button></div>
                    </section>

                    <section class="warehouse-flow-step" data-warehouse-step="2" aria-labelledby="warehouse-step-two-title" hidden>
                        <div class="warehouse-form-section-heading"><h2 id="warehouse-step-two-title" tabindex="-1">Rincian transaksi</h2><p>Saldo yang dapat digunakan mengikuti kondisi barang dan lokasi yang dipilih.</p></div>
                        <div class="warehouse-type-row"><div class="warehouse-type-switch" role="group" aria-label="Tipe transaksi">@if($canStockIn)<button type="button" class="warehouse-type-button" data-warehouse-type="IN" aria-pressed="{{ $initialType === 'IN' ? 'true' : 'false' }}"><span class="warehouse-type-icon" aria-hidden="true">↓</span><span><strong>Stock In</strong><small>Tambah stok</small></span></button>@endif @if($canStockOut)<button type="button" class="warehouse-type-button" data-warehouse-type="OUT" aria-pressed="{{ $initialType === 'OUT' ? 'true' : 'false' }}"><span class="warehouse-type-icon" aria-hidden="true">↑</span><span><strong>Stock Out</strong><small>Kurangi stok</small></span></button>@endif</div><span class="warehouse-type-caption" data-warehouse-type-caption></span></div>

                        <div class="warehouse-detail-grid warehouse-transaction-fields">
                            <div class="warehouse-form-field">
                                <label class="form-label warehouse-required" for="warehouse-quantity">Jumlah</label>
                                <div class="warehouse-quantity-control">
                                    <button class="btn btn-outline-secondary" type="button" data-warehouse-quantity-down aria-label="Kurangi jumlah">−</button>
                                    <input class="form-control" id="warehouse-quantity" name="quantity" type="number" min="{{ $itemCondition->value === 'NEW' ? 1 : '0.001' }}" step="{{ $itemCondition->value === 'NEW' ? 1 : '0.001' }}" value="{{ old('quantity', 1) }}" required inputmode="{{ $itemCondition->value === 'NEW' ? 'numeric' : 'decimal' }}" data-warehouse-quantity>
                                    <button class="btn btn-outline-secondary" type="button" data-warehouse-quantity-up aria-label="Tambah jumlah">+</button>
                                </div>
                            </div>
                            <div class="warehouse-form-field">
                                <label class="form-label warehouse-required" for="warehouse-location">Lokasi</label>
                                <select class="form-select" id="warehouse-location" name="location" data-warehouse-location>
                                    @foreach(config('warehouse.storage_locations') as $location)
                                        <option value="{{ $location }}" @selected(old('location', 'DS8') === $location)>{{ $location }}</option>
                                    @endforeach
                                </select>
                                <div class="warehouse-help">Pilih lokasi tempat barang masuk atau dipakai.</div>
                            </div>
                            <div class="warehouse-form-field warehouse-detail-full" data-warehouse-source-location-wrap hidden>
                                <label class="form-label" for="warehouse-source-location">Sumber internal <span class="warehouse-muted">(opsional)</span></label>
                                <select class="form-select" id="warehouse-source-location" name="source_location" data-warehouse-source-location>
                                    <option value="">Supplier / eksternal</option>
                                    @foreach(config('warehouse.storage_locations') as $location)
                                        <option value="{{ $location }}" @selected(old('source_location') === $location)>{{ $location }}</option>
                                    @endforeach
                                </select>
                                <div class="warehouse-help">Jika diisi, stok sumber di-reserve sampai Stock In divalidasi. Tidak ada Stock Out pasangan manual.</div>
                            </div>
                            <div class="warehouse-form-field warehouse-detail-full" data-warehouse-machine-type-wrap hidden>
                                <label class="form-label warehouse-required" for="warehouse-machine-type-used">Tipe Mesin</label>
                                <div class="warehouse-machine-buttons" data-warehouse-machine-type-container></div>
                                <input type="hidden" name="machine_type_used" id="warehouse-machine-type-used" data-warehouse-machine-type-input>
                                <div class="warehouse-help">Pilih mesin yang akan menggunakan barang ini.</div>
                            </div>
                        </div>

                        <div class="warehouse-projection" data-warehouse-projection aria-live="polite">
                            <div class="warehouse-projection-heading">
                                <span>Proyeksi stok</span>
                                <strong data-warehouse-projection-location>DS8</strong>
                            </div>
                            <div class="warehouse-projection-grid">
                                <div>
                                    <span class="projection-label">Saat ini</span>
                                    <strong class="projection-value" data-warehouse-projection-before>—</strong>
                                </div>
                                <div>
                                    <span class="projection-label">Perubahan</span>
                                    <strong class="projection-value" data-warehouse-projection-change>—</strong>
                                </div>
                                <div class="warehouse-projection-after">
                                    <span class="projection-label">Setelah transaksi</span>
                                    <strong class="projection-value" data-warehouse-projection-after>—</strong>
                                </div>
                            </div>
                        </div>

                        @if($itemCondition->value === 'NEW')
                            <div class="warehouse-form-field warehouse-notes-field">
                                <label class="form-label" for="warehouse-notes">Catatan <span class="warehouse-muted">(opsional)</span></label>
                                <textarea class="form-control" id="warehouse-notes" name="notes" rows="3" maxlength="65535" data-warehouse-stock-in-notes>{{ old('notes') }}</textarea>
                            </div>
                            <div class="warehouse-used-return" data-warehouse-used-return-wrap>
                                <label class="warehouse-confirm-check"><input type="checkbox" name="return_used" value="1" data-warehouse-return-used><span>Barang baru keluar disertai pengembalian barang bekas</span></label>
                                <div class="warehouse-used-return-panel" data-warehouse-used-return-panel hidden>
                                    <div class="warehouse-form-section-heading"><h3>Barang bekas yang kembali</h3><p>Boleh item yang sama atau item berbeda. Pencatatan dilakukan atomik bersama Stock Out.</p></div>

                                    <div class="warehouse-catalog warehouse-return-catalog" data-warehouse-catalog="return">
                                        <label class="form-label fw-bold" for="warehouse-return-catalog-search">Cari barang bekas dari katalog</label>
                                        <input class="form-control" id="warehouse-return-catalog-search" type="search" placeholder="Cari barang yang kembali (nama, kode, mesin)" data-warehouse-catalog-search autocomplete="off">
                                        <div class="warehouse-catalog-status" data-warehouse-catalog-status role="status" aria-live="polite"></div>
                                        <div class="warehouse-catalog-grid" data-warehouse-catalog-grid></div>
                                        <button class="btn btn-outline-primary warehouse-catalog-more" type="button" data-warehouse-catalog-more hidden>Muat lebih banyak</button>
                                    </div>

                                    <div class="warehouse-item-result mt-3" data-warehouse-return-item-result aria-live="polite"><span class="warehouse-muted">Belum ada barang bekas dipilih.</span></div>

                                    <div class="warehouse-form-field mt-3">
                                        <label class="form-label" for="warehouse-used-item">Atau pindai Item Code barang bekas</label>
                                        <div class="input-group warehouse-scan-group">
                                            <input class="form-control font-monospace" id="warehouse-used-item" name="used_return_item_barcode" placeholder="Item Code atau barcode" autocomplete="off" data-warehouse-return-item-input>
                                            <button class="btn btn-outline-primary" type="button" data-warehouse-scan-return-item>Cari</button>
                                        </div>
                                    </div>

                                    <div class="warehouse-detail-grid warehouse-transaction-fields mt-3">
                                        <div class="warehouse-form-field"><label class="form-label warehouse-required" for="warehouse-used-quantity">Jumlah kembali</label><input class="form-control" id="warehouse-used-quantity" name="used_return_quantity" type="number" min="0.001" step="0.001" data-warehouse-return-quantity></div>
                                        <div class="warehouse-form-field"><label class="form-label warehouse-required" for="warehouse-used-location">Lokasi penerimaan</label><select class="form-select" id="warehouse-used-location" name="used_return_location" data-warehouse-return-location>@foreach(config('warehouse.storage_locations') as $location)<option value="{{ $location }}">{{ $location }}</option>@endforeach</select></div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <section class="warehouse-verifier-section" data-warehouse-verifier-panel aria-labelledby="warehouse-verifier-title"><div class="warehouse-verifier-heading"><h3 id="warehouse-verifier-title">Karyawan Pengambil</h3><p class="warehouse-muted mb-0" data-warehouse-verifier-copy>Pindai barcode NPK karyawan yang mengambil barang.</p></div><div class="warehouse-verifier-lock-message" data-warehouse-verifier-lock-message>Lengkapi rincian transaksi sebelum memilih karyawan.</div><div class="warehouse-form-field"><label class="form-label warehouse-required" for="warehouse-user-code">Pindai barcode NPK karyawan pengambil</label><div class="input-group warehouse-scan-group"><input class="form-control font-monospace" id="warehouse-user-code" name="verified_code" inputmode="numeric" autocomplete="off" data-warehouse-user-input><button class="btn btn-outline-primary" type="button" data-warehouse-scan-user>Pilih karyawan</button></div></div><div class="warehouse-user-result" data-warehouse-user-result aria-live="polite" aria-atomic="true"><span class="warehouse-muted">Belum ada karyawan dipilih.</span></div></section>

                        <label class="warehouse-confirm-check"><input type="checkbox" data-warehouse-confirm-check><span data-warehouse-confirm-copy>Saya telah memeriksa barang, kondisi, lokasi, jumlah, dan karyawan pengambil.</span></label>
                        <div class="warehouse-step-actions"><button class="btn btn-outline-secondary" type="button" data-warehouse-back-item>Kembali</button><button class="btn btn-primary" type="submit" data-warehouse-submit disabled>Simpan transaksi</button></div>
                    </section>

                    <section class="warehouse-flow-step warehouse-receipt-step" data-warehouse-step="3" aria-labelledby="warehouse-step-three-title" hidden><div class="warehouse-receipt-icon" aria-hidden="true">✓</div><h2 id="warehouse-step-three-title" tabindex="-1">Transaksi berhasil dicatat</h2><p class="warehouse-muted">Receipt utama dan transaksi terkait tersimpan sebagai audit yang tidak dapat diedit.</p><dl class="warehouse-receipt-details" data-warehouse-receipt-details></dl><div class="warehouse-step-actions"><a class="btn btn-primary" href="{{ $itemCondition->value === 'USED' ? route('warehouse.transactions-used.create') : route('warehouse.transactions.create') }}">Transaksi baru</a><a class="btn btn-outline-secondary" href="{{ route('warehouse.transactions.index') }}">Lihat riwayat</a></div></section>
                </div>
            </form>

            <aside class="warehouse-panel warehouse-transaction-summary" data-warehouse-summary aria-labelledby="warehouse-summary-title">
                <div class="warehouse-panel-header">
                    <div>
                        <h2 id="warehouse-summary-title">Ringkasan</h2>
                        <p>Diperbarui otomatis</p>
                    </div>
                </div>
                <div class="warehouse-panel-body">
                    <div class="warehouse-summary-row">
                        <span>Barang</span>
                        <div class="warehouse-summary-value warehouse-summary-item-value">
                            <strong data-warehouse-summary-item>—</strong>
                            <small class="font-monospace text-muted" data-warehouse-summary-item-code></small>
                            <small class="font-monospace text-muted" data-warehouse-summary-item-barcode hidden></small>
                            <span data-warehouse-summary-item-barcode-visual hidden><svg data-warehouse-summary-item-barcode-svg></svg></span>
                        </div>
                    </div>
                    <div class="warehouse-summary-row">
                        <span>Kondisi</span>
                        <strong>{{ $itemCondition->label() }}</strong>
                    </div>
                    <div class="warehouse-summary-row">
                        <span>Stok total</span>
                        <strong data-warehouse-summary-current-stock>—</strong>
                    </div>
                    <div class="warehouse-summary-row">
                        <span>Status stok</span>
                        <span class="warehouse-summary-stock-status" data-warehouse-summary-stock-status>—</span>
                    </div>
                    <div class="warehouse-summary-row">
                        <span>Tipe</span>
                        <strong data-warehouse-summary-type>—</strong>
                    </div>
                    <div class="warehouse-summary-row">
                        <span>Lokasi</span>
                        <strong data-warehouse-summary-location>—</strong>
                    </div>
                    <div class="warehouse-summary-row">
                        <span>Jumlah</span>
                        <strong data-warehouse-summary-quantity>—</strong>
                    </div>
                    <div class="warehouse-summary-row">
                        <span>Karyawan</span>
                        <div class="warehouse-summary-value">
                            <strong data-warehouse-summary-user>—</strong>
                            <small class="text-muted" data-warehouse-summary-user-meta hidden></small>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/transaction-form.js')
@endpush
