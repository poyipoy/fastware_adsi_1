<div class="modal fade itemcode-modal" id="itemcode_modal_new_product" tabindex="-1" aria-labelledby="newProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form id="itemcode_form_new_product" method="POST" action="{{ route('item-code.store') }}">
                @csrf
                <input type="hidden" id="itemcode_new_product_method" name="_method" disabled>
                <input type="hidden" id="itemcode_new_product_type" name="type" value="new_product">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="itemcode_new_product_modal_title">Tambah Produk Baru</h5>
                        <p class="text-muted small mb-0">Isi data item code baru sebelum diajukan ke approval.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-light border small mb-3">
                        Lengkapi data utama terlebih dahulu.
                    </div>

                    <div class="row g-3">
                        <div class="col-12 itemcode-section-title">
                            <h6 class="mb-0 itemcode-section-heading">Informasi Pengajuan</h6>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_nomor_pengajuan" class="form-label">Nomor Pengajuan</label>
                            <input type="text" class="form-control" id="itemcode_new_nomor_pengajuan" name="nomor_pengajuan" placeholder="Masukkan No. Pengajuan: 00XX/IC/PROC/MM/YY">
                            <div class="form-text">Kosongkan untuk auto-generate, atau isi nomor yang sama untuk Product Code berbeda.</div>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="itemcode_new_tanggal" name="tanggal" required>
                        </div>

                        <div class="col-12 itemcode-section-title">
                            <h6 class="mb-0 itemcode-section-heading">Informasi Produk</h6>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_category" class="form-label">Category</label>
                            <select class="form-select" id="itemcode_new_category" name="category" required>
                                <option value="Material" selected>Material</option>
                                <option value="Non Material">Non Material</option>
                            </select>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_supplier" class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="itemcode_new_supplier" name="supplier" placeholder="Masukkan nama supplier" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_product_code" class="form-label">Product Code</label>
                            <input type="text" class="form-control" id="itemcode_new_product_code" name="product_code" placeholder="Masukkan Product Code" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="itemcode_new_description" name="description" placeholder="Nama atau deskripsi produk" required>
                        </div>

                        <div class="col-12 itemcode-section-title">
                            <h6 class="mb-0 itemcode-section-heading">Perhitungan Harga</h6>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_qty" class="form-label">Qty</label>
                            <input type="number" step="1" min="1" class="form-control" id="itemcode_new_qty" name="qty" value="1" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_unit" class="form-label">Unit</label>
                            <input type="text" class="form-control" id="itemcode_new_unit" name="unit" placeholder="Contoh: PCS" maxlength="50" required>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_currency" class="form-label">Currency</label>
                            <select class="form-select" id="itemcode_new_currency" name="currency" required>
                                <option value="IDR">IDR</option>
                                <option value="CNY">CNY</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>

                        <div class="col-12 itemcode-field-wrap">
                            <label for="itemcode_new_price_per_pcs" class="form-label">Price</label>
                            <input type="number" step="1" min="0" class="form-control fw-semibold" id="itemcode_new_price_per_pcs" name="price_per_pcs" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <small class="text-muted me-auto">Pastikan data sudah benar sebelum disimpan.</small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="itemcode_new_product_submit_label">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('item_code.partials.item-code-modal-tokens')
