<div class="modal fade itemcode-modal itemcode-modal-compact-headings" id="modalViewDetail" tabindex="-1" aria-labelledby="modalViewDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalViewDetailLabel">Detail Item Code</h5>
                    <p class="text-muted small mb-0">Ringkasan lengkap data dan histori approval.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="itemcode-section-title">
                    <h6 class="mb-0 itemcode-section-heading">Ringkasan Data</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0 itemcode-detail-table">
                        <tbody>
                            <tr>
                                <th class="bg-light text-muted" width="35%">Jenis</th>
                                <td id="detail_type">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Nomor Pengajuan</th>
                                <td id="detail_nomor_pengajuan">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Category</th>
                                <td id="detail_category">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Supplier</th>
                                <td id="detail_supplier">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Product Code</th>
                                <td id="detail_product_code">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Description</th>
                                <td id="detail_description">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Qty</th>
                                <td id="detail_qty">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Unit</th>
                                <td id="detail_unit">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted" id="detail_price_label">Price/Pcs</th>
                                <td id="detail_price_per_pcs">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Currency</th>
                                <td id="detail_currency">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Tanggal</th>
                                <td id="detail_tanggal">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Effective Date (Current Price)</th>
                                <td id="detail_tanggal_lama">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">New Price</th>
                                <td id="detail_harga_baru">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Reason</th>
                                <td id="detail_reason_new_price">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Attachment</th>
                                <td id="detail_attachment">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Selisih</th>
                                <td id="detail_selisih">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Effective Date (New Price)</th>
                                <td id="detail_tanggal_harga_baru">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Status</th>
                                <td><span class="badge text-bg-secondary" id="detail_status">-</span></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Dibuat Oleh</th>
                                <td id="detail_creator">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Di-approve Oleh</th>
                                <td id="detail_approver">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Di-approve 2 Oleh</th>
                                <td id="detail_approver2">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Di-finish Oleh</th>
                                <td id="detail_finisher">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Dibatalkan Oleh</th>
                                <td id="detail_canceller">-</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Waktu Pembatalan</th>
                                <td id="detail_cancelled_at">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="btnOpenItemHistory" onclick="openItemHistoryFromDetail()">
                    Lihat Histori
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade itemcode-modal itemcode-modal-compact-headings" id="modalItemHistory" tabindex="-1" aria-labelledby="modalItemHistoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="modalItemHistoryLabel">Histori Item Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="itemcode-section-title">
                    <h6 class="mb-0 itemcode-section-heading">Riwayat Perubahan</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0 history-table itemcode-history-table">
                        <thead>
                            <tr>
                                <th class="history-col-no">No</th>
                                <th class="history-col-keterangan">Keterangan</th>
                                <th>Status</th>
                                <th>Oleh</th>
                                <th id="itemHistoryDateHeader" style="cursor: pointer; user-select: none;" onclick="toggleItemHistorySort()">
                                    Tanggal <span id="itemHistoryDateSortIcon">↓</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="itemHistoryTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada histori</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@include('item_code.partials.item-code-modal-tokens')
