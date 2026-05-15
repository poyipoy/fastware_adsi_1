<div class="modal fade itemcode-modal itemcode-modal-compact-headings" id="modalImportItemCode" tabindex="-1" aria-labelledby="modalImportItemCodeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('item-code.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="import_type" id="import_type" value="new_product">
                <input type="hidden" name="tab" id="import_tab" value="new_product">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="import_modal_title">Import Item Code</h5>
                        <p class="text-muted small mb-0" id="import_modal_hint">
                            Upload file import sesuai format kolom untuk isi data sekaligus.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="itemcode-section-title">
                        <h6 class="mb-0 itemcode-section-heading">Panduan Template</h6>
                    </div>

                    <div class="alert alert-info small mb-3">
                        <div><strong>Kolom wajib persis:</strong> <span id="import_columns_text">nomor_pengajuan, tanggal, creator, category, supplier, product_code, description, qty, unit, currency, price</span></div>
                        <div class="mt-2"><strong>Info:</strong><span class="d-block" id="import_columns_note">
                            Kolom nomor_pengajuan boleh dikosongkan untuk auto-generate. Untuk mode Update Harga, urutan wajib: nomor_pengajuan, tanggal, creator, category, supplier, product_code, description, qty, unit, currency, effective_date_current, current_price, effective_date_new, new_price, reason_new_price, selisih.
                        </span></div>
                        <div class="mt-2"><strong>Catatan:</strong><span class="d-block">
                            Jika kombinasi nomor_pengajuan dan product_code sama (duplikat), baris import akan ditolak.
                        </span></div>
                    </div>

                    <div class="itemcode-section-title">
                        <h6 class="mb-0 itemcode-section-heading">Upload File</h6>
                    </div>

                    <div class="itemcode-field-wrap mb-3">
                        <label for="import_file" class="form-label">File Import</label>
                        <input type="file" class="form-control" id="import_file" name="import_file"
                            accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">
                            Format yang didukung: XLSX, XLS, CSV.
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <small class="text-muted">Data valid akan masuk sebagai status Draft.</small>
                    <div class="d-flex gap-2">
                        <a href="{{ route('item-code.importTemplate', ['type' => 'new_product']) }}" id="import_template_link" class="btn btn-outline-success">Download Template</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Import Data</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('item_code.partials.item-code-modal-tokens')
