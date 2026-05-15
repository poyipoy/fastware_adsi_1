<div class="modal fade itemcode-modal" id="itemcode_modal_update_price" tabindex="-1" aria-labelledby="updatePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form id="itemcode_form_update_price" method="POST" action="{{ route('item-code.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="itemcode_update_price_method" name="_method" disabled>
                <input type="hidden" id="itemcode_update_price_type" name="type" value="update_price">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="itemcode_update_price_modal_title">Tambah Update Harga</h5>
                        <p class="text-muted small mb-0">Perbarui harga item code yang sudah ada dengan data historis.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-light border small mb-3">
                        Isi Effective Date dan New Price dengan teliti. Field <strong>Reason</strong> wajib diisi untuk perubahan harga. <strong>Attachment bersifat opsional</strong>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 itemcode-section-title">
                            <h6 class="mb-0 itemcode-section-heading">Informasi Pengajuan</h6>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_nomor_pengajuan" class="form-label">Nomor Pengajuan</label>
                            <input type="text" class="form-control" id="itemcode_update_nomor_pengajuan" name="nomor_pengajuan" placeholder="Masukkan No. Pengajuan: 00XX/NP/PROC/MM/YY">
                            <div class="form-text">Kosongkan untuk auto-generate, atau isi nomor yang sama untuk Product Code berbeda.</div>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="itemcode_update_tanggal" name="tanggal" required>
                        </div>

                        <div class="col-12 itemcode-section-title">
                            <h6 class="mb-0 itemcode-section-heading">Informasi Produk</h6>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_category" class="form-label">Category</label>
                            <select class="form-select" id="itemcode_update_category" name="category" required>
                                <option value="Material" selected>Material</option>
                                <option value="Non Material">Non Material</option>
                            </select>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_supplier" class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="itemcode_update_supplier" name="supplier" placeholder="Masukkan nama supplier" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_product_code" class="form-label">Product Code</label>
                            <input type="text" class="form-control" id="itemcode_update_product_code" name="product_code" placeholder="Masukkan Product Code" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="itemcode_update_description" name="description" placeholder="Nama atau deskripsi produk" required>
                        </div>

                        <div class="col-12 itemcode-section-title">
                            <h6 class="mb-0 itemcode-section-heading">Perhitungan Dasar</h6>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_qty" class="form-label">Qty</label>
                            <input type="number" step="1" min="1" class="form-control" id="itemcode_update_qty" name="qty" value="1" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_unit" class="form-label">Unit</label>
                            <input type="text" class="form-control" id="itemcode_update_unit" name="unit" placeholder="Contoh: PCS" maxlength="50" required>
                        </div>

                        <div class="col-12 itemcode-section-title">
                            <h6 class="mb-0 itemcode-section-heading">Konteks Harga</h6>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_currency" class="form-label">Currency</label>
                            <select class="form-select" id="itemcode_update_currency" name="currency" required>
                                <option value="IDR">IDR</option>
                                <option value="CNY">CNY</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_tanggal_lama" class="form-label">Effective Date (Current Price)</label>
                            <input type="date" class="form-control" id="itemcode_update_tanggal_lama" name="tanggal_lama" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_price_per_pcs" class="form-label">Current Price</label>
                            <input type="number" step="1" min="0" class="form-control fw-semibold" id="itemcode_update_price_per_pcs" name="price_per_pcs" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_tanggal_harga_baru" class="form-label">Effective Date (New Price)</label>
                            <input type="date" class="form-control" id="itemcode_update_tanggal_harga_baru" name="tanggal_harga_baru" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_harga_baru" class="form-label">New Price</label>
                            <input type="number" step="1" min="0" class="form-control" id="itemcode_update_harga_baru" name="harga_baru" required>
                        </div>

                        <div class="col-12 itemcode-section-title">
                            <h6 class="mb-0 itemcode-section-heading">Alasan Perubahan Harga</h6>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_reason_new_price" class="form-label">Reason</label>
                            <textarea class="form-control" id="itemcode_update_reason_new_price" name="reason_new_price" rows="3" placeholder="Jelaskan alasan perubahan harga" required></textarea>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_update_attachment" class="form-label">Attachment</label>
                            <input type="file" class="form-control" id="itemcode_update_attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.xlsx">
                            <div class="form-text">Opsional saat simpan dan submit. Jika tidak diisi, data tetap bisa lanjut proses submit. Format: PDF/JPG/PNG/XLSX, maksimal 5MB.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <small class="text-muted me-auto">Data ini akan mengikuti alur submit-approve-finish.</small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="itemcode_update_price_submit_label">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('item_code.partials.item-code-modal-tokens')
