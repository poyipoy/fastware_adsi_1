@extends('warehouse.layout')

@push('styles')
    @vite('resources/css/warehouse/transaction-form.css')
@endpush

@section('warehouse-content')
    <div class="warehouse-transaction-page" aria-labelledby="warehouse-transaction-title">
        <x-warehouse.page-header title="Formulir Stock In/Out" subtitle="Pindai barang, cek perkiraan stok, verifikasi karyawan, lalu konfirmasi pergerakan.">
            <a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Kembali ke Dashboard</a>
        </x-warehouse.page-header>

        @if ($errors->any())
            <div class="alert alert-danger warehouse-form-alert" role="alert"><strong>Transaksi belum dapat diproses.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="warehouse-transaction-layout">
            <form class="warehouse-transaction-card" method="POST" action="{{ route('warehouse.transactions.store') }}" data-warehouse-transaction-form data-warehouse-initial-type="{{ $initialType }}" data-warehouse-initial-barcode="{{ $initialBarcode }}" data-warehouse-requirements='@json($transactionRequirements)' novalidate>
                @csrf
                <input type="hidden" name="type" data-warehouse-type-value value="{{ $initialType }}">
                <input type="hidden" name="idempotency_key" data-warehouse-idempotency-key value="">

                <div class="warehouse-transaction-body">
                    <div class="warehouse-type-row">
                        <div class="warehouse-type-switch" role="group" aria-label="Tipe transaksi">
                            @if ($canStockIn)<button type="button" class="warehouse-type-button" data-warehouse-type="IN" aria-pressed="{{ $initialType === 'IN' ? 'true' : 'false' }}">Stock In</button>@endif
                            @if ($canStockOut)<button type="button" class="warehouse-type-button" data-warehouse-type="OUT" aria-pressed="{{ $initialType === 'OUT' ? 'true' : 'false' }}">Stock Out</button>@endif
                        </div>
                        <span class="warehouse-type-caption" data-warehouse-type-caption>{{ $initialType === 'IN' ? 'Penambahan stok' : 'Pengeluaran stok' }}</span>
                    </div>
                    <div class="warehouse-inline-confirmation" data-warehouse-type-warning hidden role="alert">
                        <span>Mengganti tipe akan mereset barang, rincian, dan karyawan verifikator.</span>
                        <button type="button" class="btn btn-sm btn-primary" data-warehouse-type-confirm>Ganti tipe</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-warehouse-type-cancel>Batal</button>
                    </div>

                    <ol class="warehouse-stepper" aria-label="Progress transaksi" data-warehouse-stepper>
                        <li class="is-active" data-warehouse-step-indicator="1"><span>1</span><strong>Pindai barang</strong></li>
                        <li data-warehouse-step-indicator="2"><span>2</span><strong>Rincian & verifikasi</strong></li>
                        <li data-warehouse-step-indicator="3"><span>3</span><strong>Receipt</strong></li>
                    </ol>

                    <section class="warehouse-flow-step is-active" data-warehouse-step="1" aria-labelledby="warehouse-step-one-title">
                        <div class="warehouse-form-section-heading"><h2 id="warehouse-step-one-title">Pindai barcode barang</h2><p>Tekan Enter setelah pemindaian. Nol di depan dipertahankan.</p></div>
                        <div class="warehouse-form-field">
                            <label class="form-label warehouse-required" for="warehouse-item-barcode">Barcode barang</label>
                            <div class="input-group warehouse-scan-group"><input class="form-control warehouse-scan-input" id="warehouse-item-barcode" name="item_barcode" value="{{ $initialBarcode }}" autocomplete="off" required data-warehouse-item-input><button class="btn btn-outline-primary" type="button" data-warehouse-scan-item><span data-warehouse-lookup-label>Cari</span><span class="warehouse-spinner" data-warehouse-spinner hidden aria-hidden="true"></span></button></div>
                            <div class="warehouse-help">Gunakan pencarian manual bila pemindai tidak mengirim terminator Enter.</div>
                            <div class="warehouse-field-error" aria-live="polite" data-warehouse-item-error></div>
                        </div>
                        <div class="warehouse-item-result" data-warehouse-item-summary aria-live="polite" aria-atomic="true"><span class="warehouse-muted">Barang belum dipilih.</span></div>
                        <div class="warehouse-step-actions"><button class="btn btn-primary" type="button" data-warehouse-next-item disabled>Lanjut ke rincian</button></div>
                    </section>

                    <section class="warehouse-flow-step" data-warehouse-step="2" aria-labelledby="warehouse-step-two-title" hidden>
                        <div class="warehouse-form-section-heading"><h2 id="warehouse-step-two-title">Rincian dan perkiraan stok</h2><p>Lengkapi kolom sesuai tipe pergerakan.</p></div>
                        <div class="warehouse-detail-grid warehouse-transaction-fields">
                            <div class="warehouse-form-field"><label class="form-label warehouse-required" for="warehouse-quantity">Jumlah</label><div class="warehouse-quantity-control"><button type="button" class="btn btn-outline-secondary" data-warehouse-quantity-step="-1" aria-label="Kurangi jumlah">−</button><input class="form-control" id="warehouse-quantity" name="quantity" type="number" min="0.001" step="1" required data-warehouse-quantity><button type="button" class="btn btn-outline-secondary" data-warehouse-quantity-step="1" aria-label="Tambah jumlah">+</button></div><div class="warehouse-field-error" aria-live="polite" data-warehouse-quantity-error></div></div>
                            <div class="warehouse-form-field" data-warehouse-storage-location-field><label class="form-label warehouse-required" for="warehouse-storage-location">Lokasi penyimpanan <span data-warehouse-storage-location-required></span></label><select class="form-select" id="warehouse-storage-location" name="storage_location" data-warehouse-storage-location><option value="">Pilih lokasi penyimpanan</option>@foreach ((array) config('warehouse.storage_locations', ['DS8', 'Deltamas']) as $storageLocation)<option value="{{ $storageLocation }}">{{ $storageLocation }}</option>@endforeach</select><div class="warehouse-help">Pilih DS8 atau Deltamas. Lokasi ini menjadi lokasi aktif item setelah Stock In berhasil.</div><div class="warehouse-field-error" aria-live="polite" data-warehouse-storage-location-error></div></div>
                        </div>
                        <div class="warehouse-projection" aria-live="polite" data-warehouse-projection><span class="warehouse-muted">Perkiraan stok akan tampil setelah jumlah valid.</span></div>
                        <div class="warehouse-verifier-section is-locked" data-warehouse-verifier-panel aria-disabled="true">
                        <div class="warehouse-form-section-heading warehouse-verifier-heading"><h3>Verifikasi dan konfirmasi</h3><p>Pindai barcode NPK karyawan setelah rincian transaksi valid.</p></div>
                            <div class="warehouse-verifier-lock-message" data-warehouse-verifier-lock-message role="status">Lengkapi jumlah dan lokasi Stock In untuk membuka verifikasi.</div>
                            <div class="warehouse-form-field">
                                <label class="form-label warehouse-required" for="warehouse-user-code">Pindai barcode NPK karyawan</label>
                                <div class="input-group warehouse-scan-group"><input class="form-control warehouse-scan-input" id="warehouse-user-code" name="verified_code" inputmode="numeric" autocomplete="off" required disabled data-warehouse-user-input><button class="btn btn-outline-primary" type="button" disabled data-warehouse-scan-user><span data-warehouse-user-lookup-label>Cari</span><span class="warehouse-spinner" data-warehouse-user-spinner hidden aria-hidden="true"></span></button></div>
                                <div class="warehouse-help">Nama, NPK, dan bagian ditampilkan setelah NPK ditemukan dan memiliki akses Warehouse.</div>
                                <div class="warehouse-field-error" aria-live="polite" data-warehouse-user-error></div>
                            </div>
                            <div class="warehouse-user-result" aria-live="polite" aria-atomic="true" data-warehouse-user-summary><span class="warehouse-muted">Karyawan belum diverifikasi.</span></div>
                            <div class="warehouse-confirmation-summary" aria-live="polite" aria-atomic="true" data-warehouse-confirmation-summary><span class="warehouse-muted">Ringkasan akan tampil setelah barang dan karyawan verifikator valid.</span></div>
                            <label class="warehouse-confirm-check"><input type="checkbox" disabled data-warehouse-confirm-check> <span>Saya sudah memeriksa barang, jumlah, lokasi bila Stock In, dan karyawan verifikator.</span></label>
                            <div class="warehouse-field-error" aria-live="polite" data-warehouse-submit-error></div>
                        </div>
                        <div class="warehouse-step-actions"><button class="btn btn-outline-secondary" type="button" data-warehouse-back-step="1">Kembali</button><button class="btn btn-primary" type="submit" data-warehouse-submit><span data-warehouse-submit-label>Konfirmasi Stock {{ $initialType === 'IN' ? 'In' : 'Out' }}</span><span class="warehouse-spinner" data-warehouse-submit-spinner hidden aria-hidden="true"></span></button></div>
                    </section>

                    <section class="warehouse-flow-step warehouse-receipt-step" data-warehouse-step="3" aria-labelledby="warehouse-step-three-title" hidden>
                        <div class="warehouse-receipt-icon" aria-hidden="true">✓</div>
                        <h2 id="warehouse-step-three-title">Transaksi berhasil dicatat</h2>
                         <p class="warehouse-page-subtitle">Pergerakan tersimpan dan dapat dilacak dari riwayat.</p>
                        <dl class="warehouse-definition-list warehouse-receipt-details" data-warehouse-receipt-details></dl>
                        <div class="warehouse-step-actions"><a class="btn btn-primary" href="{{ route('warehouse.transactions.create') }}">Transaksi Baru</a><a class="btn btn-outline-secondary" href="{{ route('warehouse.dashboard') }}">Kembali ke Dashboard</a></div>
                    </section>
                </div>
            </form>

            <aside class="warehouse-transaction-summary warehouse-panel" aria-label="Ringkasan transaksi" data-warehouse-summary>
                <div class="warehouse-panel-header"><div><h2>Ringkasan</h2><p>Terus diperbarui saat data valid.</p></div></div>
                <div class="warehouse-panel-body">
                    <div class="warehouse-summary-row"><span>Tipe</span><strong data-warehouse-summary-type>{{ $initialType === 'IN' ? 'Stock In' : 'Stock Out' }}</strong></div>
                    <div class="warehouse-summary-row warehouse-summary-item-row">
                         <span>Barang</span>
                        <div class="warehouse-summary-value warehouse-summary-item-value" data-warehouse-summary-item-wrap>
                            <strong data-warehouse-summary-item>Belum dipilih</strong>
                            <span class="warehouse-summary-subvalue" data-warehouse-summary-item-code hidden></span>
                            <span class="warehouse-summary-subvalue" data-warehouse-summary-item-category hidden></span>
                            <span class="warehouse-summary-subvalue font-monospace warehouse-summary-barcode-text" data-warehouse-summary-item-barcode hidden></span>
                            <div class="warehouse-summary-barcode" data-warehouse-summary-item-barcode-wrap hidden>
                                <div class="warehouse-summary-barcode-visual" data-warehouse-summary-item-barcode-visual hidden aria-hidden="true"></div>
                                <svg data-warehouse-summary-item-barcode-svg hidden role="img" aria-label="Barcode barang"></svg>
                                <span class="warehouse-summary-barcode-fallback" data-warehouse-summary-item-barcode-fallback hidden></span>
                            </div>
                        </div>
                    </div>
                    <div class="warehouse-summary-row"><span>Stok saat ini</span><div class="warehouse-summary-value"><strong data-warehouse-summary-current-stock>—</strong><span class="warehouse-summary-subvalue" data-warehouse-summary-stock-status hidden></span></div></div>
                    <div class="warehouse-summary-row"><span>Jumlah</span><strong data-warehouse-summary-quantity>—</strong></div>
                    <div class="warehouse-summary-row"><span>Perkiraan stok</span><strong data-warehouse-summary-stock>—</strong></div>
                    <div class="warehouse-summary-row"><span>Lokasi</span><strong data-warehouse-summary-location>Belum diatur</strong></div>
                    <div class="warehouse-summary-row warehouse-summary-verifier-row"><span>Karyawan verifikator</span><div class="warehouse-summary-value"><strong data-warehouse-summary-user>Belum diverifikasi</strong><span class="warehouse-summary-subvalue" data-warehouse-summary-user-meta hidden></span></div></div>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/warehouse/transaction-form.js')
@endpush
