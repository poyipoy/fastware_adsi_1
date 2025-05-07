@extends('layout')

@section('content')
    <main id="main" class="main">
        <style>
            /* Gaya dasar untuk tabel */
            table {
                width: 100%;
                border-collapse: collapse;
                font-family: Arial, sans-serif;
                margin-bottom: 20px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            }

            /* Gaya untuk header tabel */
            thead th {
                background-color: #00d9ff;
                color: black;
                text-transform: uppercase;
                font-weight: bold;
                padding: 12px;
                border: 2px solid #000000;
            }

            /* Gaya untuk sel tabel */
            td {
                padding: 10px;
                border: 2px solid #000000;
                vertical-align: middle;
            }

            /* Gaya untuk baris ganjil */
            tbody tr:nth-child(odd) {
                background-color: #f8f8f8;
            }

            /* Gaya untuk baris genap */
            tbody tr:nth-child(even) {
                background-color: #ffffff;
            }

            /* Efek hover pada baris */
            tbody tr:hover {
                background-color: #f0f0f0;
                transition: background-color 0.3s ease;
            }

            /* Gaya untuk footer tabel */
            tfoot tr {
                background-color: #e8e8e8;
                font-weight: bold;
            }

            /* Gaya untuk input di dalam tabel */
            input[type="text"] {
                width: 100%;
                padding: 6px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            /* Gaya untuk tombol di dalam tabel */
            .btn {
                padding: 6px 12px;
                margin: 2px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }

            .btn-sm {
                padding: 4px 8px;
                font-size: 12px;
            }

            .btn-primary {
                background-color: #007bff;
                color: white;
            }

            .btn-success {
                background-color: #28a745;
                color: white;
            }

            .btn-info {
                background-color: #17a2b8;
                color: white;
            }

            .btn-danger {
                background-color: #dc3545;
                color: white;
            }

            /* Gaya untuk badge */
            .badge {
                padding: 6px 10px;
                border-radius: 20px;
                font-size: 14px !important;
                font-weight: normal;
            }

            .bg-info {
                background-color: #17a2b8;
            }

            .bg-danger {
                background-color: #dc3545;
            }

            /* Gaya untuk sel yang dinonaktifkan */
            .disabled-cell {
                opacity: 0.6;
                background-color: #f0f0f0;
            }

            /* Responsivitas */
            @media screen and (max-width: 600px) {
                table {
                    font-size: 14px;
                }

                th,
                td {
                    padding: 8px;
                }

                .btn-sm {
                    padding: 3px 6px;
                    font-size: 11px;
                }
            }

            /* Sembunyikan bagian tanda tangan secara default */
            #signature-section {
                display: none;
            }

            /* Set the page to landscape orientation for printing */
            @page {
                size: A4 landscape;
                /* Ukuran A4 dalam orientasi lanskap */
                margin: 20mm;
                /* Atur margin untuk halaman cetak */
            }

            /* Desain untuk tampilan cetak */
            @media print {
                #signature-section {
                    display: block;
                }

                body * {
                    visibility: hidden;
                }

                .hide-on-print {
                    display: none;
                }

                #print-area,
                #print-area * {
                    visibility: visible;
                }

                #print-area {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    margin-top: 0;
                }

                body {
                    margin: 0;
                    padding: 0;
                }

                @page {
                    margin: 1;
                }

                /* Mengatur lebar tabel agar tidak terpotong */
                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                table,
                th,
                td {
                    border: 2px solid black;
                    font-size: 10px;
                }

                th,
                td {
                    padding: 2px;
                    text-align: left;
                }

                /* Sembunyikan kolom File dan Aksi pada cetak */
                .no-print {
                    display: none;
                }

                /* Show the Subcon-only columns if they are present */
                .subcon-only {
                    display: table-cell;
                }


                /* Mengatur lebar khusus untuk kolom No PO */
                th:nth-child(1),
                td:nth-child(1) {
                    width: 3%;
                }

                th:nth-child(3),
                td:nth-child(3) {
                    width: 11%;
                }

                /* Atur lebar untuk kolom lain yang lebih kecil */
                th:nth-child(4),
                td:nth-child(4) {
                    width: 10%;
                }

                th:nth-child(5),
                td:nth-child(5) {
                    width: 7%;
                }

                th:nth-child(6),
                td:nth-child(6) {
                    width: 15%;
                }

                th:nth-child(7),
                td:nth-child(7) {
                    width: 15%;
                }

                th:nth-child(14),
                td:nth-child(14) {
                    width: 15%;
                }

                tfoot td[colspan="4"] {
                    colspan: 2;
                }

            }

            .disabled-cell {
                background-color: #b1b1b1;
                /* Light gray background */
                color: #555454;
                /* Darker text color for better contrast */
            }

            .disabled-cell input[type="text"]

            /* Ensure the action column remains interactive */
            td:last-child {
                background-color: initial;
                color: initial;
            }
        </style>
        <div class="pagetitle">
            <h1>Halaman Tindak Lanjut</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Halaman Tindak Lanjut FPB</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-body">
                        <div id="print-area">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <!-- Text FORM PERMINTAAN BARANG (FPB) -->
                                <h4 style="margin-top: 3%;"><b>FORM PERMINTAAN BARANG (FPB)</b></h4>
                                <!-- Gambar logo -->
                                <img id="signature-section" src="{{ asset('assets/pre_order/logo-adasi.png') }}"
                                    alt="Logo Adasi" style="width: 330px; height: auto;">
                            </div>
                            <!-- Menyimpan no_fpb di atribut data pada div -->

                            <!-- Ambil no_fpb dari item pertama dalam koleksi -->
                            @if ($mstPoPengajuans->isNotEmpty())
                                <p>PIC : {{ $mstPoPengajuans->first()->modified_at }}</p>

                                <p>NO FPB : {{ $mstPoPengajuans->first()->no_fpb }}</p>
                            @else
                                <p>NO FPB : Data not found</p>
                            @endif
                            <!-- Container untuk tabel -->
                            <table border="1" cellpadding="10" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th class="no-print">No PO</th>
                                        <th>Nama Barang</th>
                                        <th>Spesifikasi</th>
                                        <th>PCS</th>
                                        <th style="width: 10%">Harga Satuan</th>
                                        <th style="width: 10%">Total Harga</th>
                                        @if ($mstPoPengajuans->first()->kategori_po == 'Subcont')
                                            <!-- Tambahkan kolom baru jika kategori_po adalah Subcon -->
                                            <th style="width: 10%">Target Cost</th>
                                            <th style="width: 10%">Lead Time</th>
                                            <th>Rekomendasi</th>
                                            <th>Nama Customer</th>
                                            <th>Nama Project</th>
                                            <th>NO SO</th>
                                        @endif
                                        <th class="no-print">File</th>
                                        <th>Tgl Dibuat</th>
                                        <th class="no-print">Status</th>
                                        <th class="no-print">Aksi</th> <!-- Kolom baru untuk aksi -->
                                        <th class="no-print">Unggah Quotation</th> <!-- Kolom baru untuk upload -->
                                        <th class="no-print">Unduh</th> <!-- Kolom baru untuk download -->
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $counter = 1; // Inisialisasi counter manual
                                    @endphp
                                    @forelse ($mstPoPengajuans as $item)
                                        @if ($item->status_2 != 8)
                                            <!-- Tampilkan hanya jika status bukan 8 -->
                                            <tr class="{{ in_array($item->status_2, [2, 8]) ? 'hide-on-print' : '' }}"
                                                style="{{ $item->status_2 == 8 ? 'display: none;' : '' }}">

                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                    {{ $counter++ }} <!-- Increment counter -->
                                                </td>
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} no-print">
                                                    <input type="text" class="form-control form-control-sm"
                                                        name="no_po_{{ $item->id }}" data-id="{{ $item->id }}"
                                                        value="{{ $item->no_po ?? '' }}" onblur="this.form.submit()"
                                                        placeholder="Masukkan No PO"
                                                        {{ $item->status_2 == 8 ? 'disabled' : '' }} maxlength="12">
                                                    <input type="hidden" name="id" value="{{ $item->id }}">
                                                </td>
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                    {{ $item->nama_barang }}</td>
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                    {{ $item->spesifikasi }}</td>
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                    {{ $item->pcs }}</td>
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">Rp
                                                    {{ number_format($item->price_list, 0, ',', '.') }}</td>
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">Rp
                                                    {{ number_format($item->total_harga, 0, ',', '.') }}</td>
                                                @if ($item->kategori_po == 'Subcont')
                                                    <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">Rp
                                                        {{ number_format($item->target_cost, 0, ',', '.') }}</td>
                                                    <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                        {{ $item->lead_time }} hari</td>
                                                    <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                        {{ $item->rekomendasi }}</td>
                                                    <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                        {{ $item->nama_customer }}</td>
                                                    <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                        {{ $item->nama_project }}</td>
                                                    <td
                                                        class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} Subcont-only">
                                                        {{ $item->no_so }}</td>
                                                @endif
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} no-print">
                                                    @if ($item->file_name)
                                                        <a href="{{ route('download.file', $item->id) }}"
                                                            class="btn btn-sm btn-primary" title="Download File"
                                                            {{ $item->status_2 == 8 ? 'disabled' : '' }}>
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    @else
                                                        <span class="text-muted">No File</span>
                                                    @endif
                                                </td>
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                                    {{ $item->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} no-print">
                                                    @if ($item->status_2 == 6)
                                                        <span class="badge bg-info align-items-center"
                                                            style="font-size: 18px;">PO Confirm</span>
                                                    @elseif($item->status_2 == 7)
                                                        <span class="badge bg-info align-items-center"
                                                            style="font-size: 18px;">PO Release</span>
                                                    @elseif($item->status_2 == 8)
                                                        <span class="badge bg-danger align-items-center"
                                                            style="font-size: 18px;">Reject</span>
                                                    @elseif($item->status_2 == 10)
                                                        <span class="badge bg-danger align-items-center"
                                                            style="font-size: 18px;">Pengajuan Reject</span>
                                                    @endif
                                                </td>
                                                <td class="no-print">
                                                    @if (
                                                        ($item->status_1 == 6 || $item->status_1 == 7 || $item->status_1 == 8 || $item->status_1 == 10) &&
                                                            $item->status_2 != 8)
                                                        <div class="d-flex align-items-center gap-3">

                                                            @if (in_array(auth()->user()->role_id, [1, 14, 41, 54]))
                                                                <button type="button"
                                                                    class="btn btn-success btn-sm btn-save ml-2"
                                                                    title="Save" data-id="{{ $item->id }}">
                                                                    <i class="fas fa-save"></i> Save
                                                                </button>
                                                            @endif
                                                            @if (in_array(auth()->user()->role_id, [1, 14, 41]))
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm btn-cancel ml-2"
                                                                    title="Reject Item" data-id="{{ $item->id }}">
                                                                    <i class="fas fa-close"></i> Reject Item
                                                                </button>
                                                            @endif

                                                            <button type="button" class="btn btn-info btn-sm btn-view ml-2"
                                                                title="View Details" data-id="{{ $item->id }}">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                        </div>
                                                    @endif
                                                    @if ($item->status_2 == 8 || $item->status_1 == 9)
                                                        <button type="button" class="btn btn-info btn-sm btn-view ml-2"
                                                            title="View Details" data-id="{{ $item->id }}">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    @endif
                                                </td>
                                                <td class="no-print">
                                                    @if (empty($item->quotation_file) || $item->konfirmasi_quotation == 'Ditolak')
                                                        <input type="file" name="quotation_file_{{ $item->id }}"
                                                            class="form-control form-control-sm">
                                                    @endif
                                                </td>
                                                <td class="no-print"
                                                    @if ($item->konfirmasi_quotation == 'Ditolak') style="background-color: #e74c3c; color: white;"
                                                        @elseif ($item->konfirmasi_quotation == 'Dikonfirmasi')
                                                            style="background-color: #2ecc71; color: white;" @endif>
                                                    @if ($item->quotation_file)
                                                        <p>
                                                            <a href="{{ asset($item->quotation_file) }}" target="_blank">
                                                                @php
                                                                    // Mendapatkan ekstensi file
                                                                    $fileExtension = pathinfo(
                                                                        $item->quotation_file,
                                                                        PATHINFO_EXTENSION,
                                                                    );
                                                                @endphp

                                                                @if ($fileExtension == 'pdf')
                                                                    <!-- Ikon untuk file PDF -->
                                                                    <i class="fas fa-file-pdf fa-2x"
                                                                        style="color: #0026ff;"></i>
                                                                @elseif(in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']))
                                                                    <!-- Ikon untuk file gambar -->
                                                                    <i class="fas fa-file-image fa-2x"
                                                                        style="color: #00e1ff;"></i>
                                                                @else
                                                                    <!-- Ikon untuk file umum -->
                                                                    <i class="fas fa-file-alt fa-2x"
                                                                        style="color: #2ecc71;"></i>
                                                                @endif
                                                            </a>
                                                        </p>
                                                    @else
                                                        <span class="text-muted">No Quotation File</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="5">No data available</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" style="text-align: right; font-weight: bold;">Jumlah Total:
                                        </td>
                                        <td id="total-pcs"></td>
                                        <td></td>
                                        <td id="total-price_list"></td>
                                        <td colspan="11"></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <!-- Bagian Tanda Tangan -->
                            <div id="signature-section">
                                <br>
                                <table border="1" cellspacing="0" cellpadding="5"
                                    style="width: 100%; border-collapse: collapse; text-align: center;">
                                    <thead>
                                        <tr>
                                            <th colspan="1">PEMBUAT</th>
                                            <th colspan="3" style="text-align: center">PERSETUJUAN PEMBELIAN</th>
                                            <th>MENGETAHUI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width: 15%">Pemohon</td>
                                            <td style="width: 15%">Dept. Head</td>
                                            <td style="width: 15%">{{ $userAccHeader }}</td>
                                            <!-- Display the userAccHeader -->
                                            <td style="width: 15%">Finance</td>
                                            <td style="width: 15%">Purchasing</td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align: top; height: 60px;">
                                                @if ($mstPoPengajuans->first()->status_1 >= 2 && $mstPoPengajuans->first()->status_1 <= 13)
                                                    <p><b>SUBMITTED by {{ $mstPoPengajuans->first()->modified_at }}</b></p>
                                                @else
                                                    <p>&nbsp;</p>
                                                @endif
                                            </td>
                                            <td style="vertical-align: top; height: 60px;">
                                                @if ($mstPoPengajuans->first()->status_1 >= 3 && $mstPoPengajuans->first()->status_1 <= 13)
                                                    <p><b>APPROVED by {{ $deptHead }}</b></p>
                                                @else
                                                    <p>&nbsp;</p>
                                                @endif
                                            </td>
                                            <td style="vertical-align: top; height: 60px;">
                                                @if ($mstPoPengajuans->first()->status_1 >= 4 && $mstPoPengajuans->first()->status_1 <= 13)
                                                    <p><b>APPROVED by {{ $userAccbody }}</b></p>
                                                @else
                                                    <p>&nbsp;</p>
                                                @endif
                                            </td>
                                            <td style="vertical-align: top; height: 60px;">
                                                @if ($mstPoPengajuans->first()->status_1 >= 5 && $mstPoPengajuans->first()->status_1 <= 13)
                                                    <p>
                                                        <b>APPROVED by&nbsp;
                                                            @if ($trsPoPengajuanStatus4)
                                                                {{ $trsPoPengajuanStatus4->modified_at }}
                                                            @else
                                                                &nbsp;
                                                            @endif
                                                        </b>
                                                    </p>
                                                @else
                                                    <p>&nbsp;</p>
                                                @endif
                                            </td>
                                            <td style="vertical-align: top; height: 60px;">
                                                @if ($mstPoPengajuans->first()->status_1 >= 6 && $mstPoPengajuans->first()->status_1 <= 13)
                                                    <p><b>APPROVED by VIVIAN ANGELIKA</b></p>
                                                @else
                                                    <p>&nbsp;</p>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Tgl:
                                                {{ optional($matchingTrsPoPengajuans->firstWhere('status', 2))->created_at ? optional($matchingTrsPoPengajuans->firstWhere('status', 2))->created_at->format('d/m/y') : '' }}
                                            </td>
                                            <td>Tgl:
                                                {{ optional($matchingTrsPoPengajuans->firstWhere('status', 3))->created_at ? optional($matchingTrsPoPengajuans->firstWhere('status', 3))->created_at->format('d/m/y') : '' }}
                                            </td>
                                            <td>Tgl:
                                                {{ optional($matchingTrsPoPengajuans->firstWhere('status', 4))->created_at ? optional($matchingTrsPoPengajuans->firstWhere('status', 4))->created_at->format('d/m/y') : '' }}
                                            </td>
                                            <td>Tgl:
                                                {{ optional($matchingTrsPoPengajuans->firstWhere('status', 5))->created_at ? optional($matchingTrsPoPengajuans->firstWhere('status', 5))->created_at->format('d/m/y') : '' }}
                                            </td>
                                            <td>Tgl:
                                                {{ optional($matchingTrsPoPengajuans->firstWhere('status', 6))->created_at ? optional($matchingTrsPoPengajuans->firstWhere('status', 6))->created_at->format('d/m/y') : '' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3" style="margin-top: 2%;">
                        <a href="{{ route('index.PO.procurement') }}" class="btn btn-secondary btn-sm" title="Back">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        @if ($item->status_1 == 5)
                            @if (in_array(auth()->user()->role_id, [1, 14, 41]))
                                <button type="button" class="btn btn-primary btn-sm btn-kirim ml-2"
                                    data-no_fpb="{{ $item->no_fpb }}" title="Kirim">
                                    <i class="fas fa-check"></i> Confirm
                                </button>
                            @endif
                        @endif
                        @if ($item->status_1 != 9)
                            @if (in_array(auth()->user()->role_id, [1, 14, 41]))
                                <button type="button" class="btn btn-success btn-sm btn-finish ml-2"
                                    data-no_fpb="{{ $item->no_fpb }}" title="Finish">
                                    <i class="fas fa-thumbs-up"></i> Finish
                                </button>
                            @endif
                        @endif
                        <button type="button" class="btn btn-primary" onclick="printTable()">
                            <i class="fas fa-print"></i>Print PDF</button>
                    </div>

                    <h4 style="margin-top: 3%"> <b>HISTORI PERMINTAAN BARANG (FPB)</b></h4>
                    <table id="historyTable" class="table table-bordered table-hover"
                        style="width: 100%; overflow: hidden;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No FPB</th>
                                <th>No PO</th>
                                <th>Nama Barang</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                                <th>Tanggal Diajukan</th>
                                <th>PIC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Tabel akan terisi oleh JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </section>
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            $(document).ready(function() {
                // Hover function for dropdowns
                $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });
        </script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.btn-kirim').forEach(button => {
                    button.addEventListener('click', function() {
                        // Mengambil no_fpb dari data attribute dan mengganti "/" dengan "-"
                        var no_fpb = this.getAttribute('data-no_fpb').replace(/\//g, '-');
                        console.log("FPB Number to send:", no_fpb); // Log FPB number yang diambil

                        Swal.fire({
                            title: 'Apakah anda ingin konfirmasi data?',
                            text: "Data yang telah dikonfirmasi tidak dapat dirubah!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, kirim!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Jika konfirmasi, lakukan AJAX POST request
                                $.ajax({
                                    url: "{{ route('kirim.fpb.procurement', ':no_fpb') }}"
                                        .replace(':no_fpb', no_fpb),
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}', // CSRF Token Laravel
                                    },
                                    success: function(response) {
                                        console.log("Response from server:",
                                            response); // Log response dari server

                                        Swal.fire(
                                            'Terkirim!',
                                            'Data berhasil dikirim.',
                                            'success'
                                        ).then(() => {
                                            location
                                                .reload(); // Refresh halaman setelah sukses
                                        });
                                    },
                                    error: function(xhr) {
                                        console.log("Error occurred:", xhr
                                            .responseText
                                        ); // Log error jika terjadi kesalahan

                                        Swal.fire(
                                            'Gagal!',
                                            'Terjadi kesalahan saat mengirim data.',
                                            'error'
                                        );
                                    }
                                });
                            }
                        });
                    });
                });

                document.querySelectorAll('.btn-save').forEach(button => {
                    button.addEventListener('click', function() {
                        var id = this.getAttribute('data-id');
                        var no_po = document.querySelector(`input[name="no_po_${id}"]`).value;
                        console.log("ID to save:", id, "No PO:", no_po);

                        // Cek apakah ada quotation_file
                        var quotationFileInput = document.querySelector(
                            `input[name="quotation_file_${id}"]`);
                        var hasQuotationFile = quotationFileInput && quotationFileInput.files.length >
                            0;

                        if (hasQuotationFile) {
                            // Jika ada quotation_file, ambil file dan log informasi
                            var quotationFile = quotationFileInput.files[0];
                            console.log("File akan dikirim:", quotationFile.name);

                            // Buat FormData untuk mengirim file dan data lainnya
                            var formData = new FormData();
                            formData.append('quotation_file', quotationFile);
                            formData.append('_token', '{{ csrf_token() }}');
                            formData.append('no_po', no_po);

                            // Lakukan AJAX POST request
                            $.ajax({
                                url: "{{ route('kirim.fpb.progres', ':id') }}".replace(':id',
                                    id),
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,

                                success: function(response) {
                                    console.log("Response from server:", response);

                                    Swal.fire(
                                        'Tersimpan!',
                                        'Data berhasil disimpan.',
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                },
                                error: function(xhr) {
                                    console.log("Error occurred:", xhr.responseText);

                                    Swal.fire(
                                        'Gagal!',
                                        'Terjadi kesalahan saat menyimpan data.',
                                        'error'
                                    );
                                }
                            });
                        } else {
                            // SweetAlert untuk mengisi keterangan
                            Swal.fire({
                                title: 'Masukkan Keterangan',
                                input: 'textarea',
                                inputPlaceholder: 'Tuliskan keterangan Update Progres di sini...',
                                showCancelButton: true,
                                confirmButtonText: 'Save',
                                cancelButtonText: 'Batal',
                                preConfirm: (keterangan) => {
                                    if (!keterangan) {
                                        Swal.showValidationMessage(
                                            'Keterangan tidak boleh kosong');
                                    }
                                    return keterangan;
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    var keterangan = result.value;
                                    console.log("Keterangan:", keterangan);

                                    // Jika konfirmasi, lakukan AJAX POST request
                                    $.ajax({
                                        url: "{{ route('kirim.fpb.progres', ':id') }}"
                                            .replace(':id', id),
                                        type: 'POST',
                                        data: {
                                            _token: '{{ csrf_token() }}',
                                            keterangan: keterangan,
                                            no_po: no_po
                                        },
                                        success: function(response) {
                                            console.log("Response from server:",
                                                response);

                                            Swal.fire(
                                                'Tersimpan!',
                                                'Data berhasil disimpan dan status diubah.',
                                                'success'
                                            ).then(() => {
                                                location.reload();
                                            });
                                        },
                                        error: function(xhr) {
                                            console.log("Error occurred:", xhr
                                                .responseText);

                                            Swal.fire(
                                                'Gagal!',
                                                'Terjadi kesalahan saat menyimpan data.',
                                                'error'
                                            );
                                        }
                                    });
                                }
                            });
                        }
                    });
                });

                document.querySelectorAll('.btn-view').forEach(button => {
                    button.addEventListener('click', function() {
                        var id = this.getAttribute('data-id');
                        console.log("Viewing details for ID:", id);

                        // Lakukan AJAX request
                        $.ajax({
                            url: '{{ route('po.history', ':id') }}'.replace(':id', id),
                            type: 'GET',
                            success: function(response) {
                                let tbody = document.querySelector('#historyTable tbody');
                                tbody.innerHTML =
                                    ''; // Kosongkan tbody sebelum menambahkan data baru

                                response.data.forEach((item, index) => {
                                    console.log(item
                                        .status); // Log status untuk pengecekan

                                    let statusBadge = '';
                                    switch (item.status) {
                                        case 1:
                                            statusBadge =
                                                '<span class="badge bg-secondary">Draf</span>';
                                            break;
                                        case 2:
                                            statusBadge =
                                                '<span class="badge bg-warning">Di ajukan Oleh Pemohon</span>';
                                            break;
                                        case 3:
                                            statusBadge =
                                                '<span class="badge bg-warning">Telah di Approved Dept. Head</span>';
                                            break;
                                        case 4:
                                            let modifiedLabel =
                                                ''; // Label yang akan digunakan

                                            // Cek modified_at dan kategori_po, kemudian tampilkan label sesuai
                                            if (['NURSALIM', 'RANGGA FADILLAH']
                                                .includes(item.modified_at) && [
                                                    'Consumable', 'Spareparts',
                                                    'Indirect Material'
                                                ].includes(item.kategori_po)) {
                                                modifiedLabel = 'Warehouse';
                                            } else if (['MEDI KRISNANTO',
                                                    'JESSICA PAUNE'
                                                ].includes(item.modified_at) && [
                                                    'IT'
                                                ].includes(item.kategori_po)) {
                                                modifiedLabel = 'IT';
                                            } else if (['MUHAMMAD DINAR FARISI',
                                                    'MARTINUS CAHYO RAHASTO',
                                                    'JESSICA PAUNE'
                                                ].includes(item.modified_at) && [
                                                    'GA'
                                                ].includes(item.kategori_po)) {
                                                modifiedLabel = 'GA';
                                            } else {
                                                modifiedLabel = item
                                                    .modified_at; // Tampilkan nama asli jika tidak cocok
                                            }

                                            statusBadge =
                                                `<span class="badge bg-warning">Telah di Approved ${modifiedLabel}</span>`;
                                            break;
                                        case 5:
                                            statusBadge =
                                                '<span class="badge bg-warning">Telah di Approved Finance</span>';
                                            break;
                                        case 6:
                                            statusBadge =
                                                '<span class="badge bg-success">FPB Telah di Confirm Procurment</span>';
                                            break;
                                        case 7:
                                            statusBadge =
                                                '<span class="badge bg-success">PO Release</span>';
                                            break;
                                        case 8:
                                            statusBadge =
                                                '<span class="badge bg-success">Item Reject</span>';
                                            break;
                                        case 9:
                                            statusBadge =
                                                '<span class="badge bg-info">Finish</span>';
                                            break;
                                        case 10:
                                            statusBadge =
                                                '<span class="badge bg-danger">Pengajuan Reject</span>';
                                            break;
                                        default:
                                            statusBadge =
                                                '<span class="badge bg-secondary">Tidak terdapat Informasi</span>'; // Default jika tidak cocok
                                            break;
                                    }

                                    tbody.innerHTML += `
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td>${item.no_fpb || '-'}</td>
                                            <td>${item.no_po || '-'}</td>
                                            <td>${item.nama_barang || '-'}</td>
                                            <td>${item.keterangan || '-'}</td>
                                            <td>${statusBadge}</td>
                                            <td>${new Date(item.created_at).toLocaleDateString('id-ID')} ${new Date(item.created_at).toLocaleTimeString('id-ID')}</td>
                                            <td>${item.modified_at || '-'}</td>
                                        </tr>
                                    `;
                                });
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                            }
                        });
                    });
                });

                document.querySelectorAll('.btn-cancel').forEach(button => {
                    button.addEventListener('click', function() {
                        var id = this.getAttribute('data-id'); // Mengambil id dari tombol
                        console.log("ID to cancel:", id); // Log id yang diambil

                        // SweetAlert untuk memilih keterangan pembatalan
                        Swal.fire({
                            title: 'Pilih Reject',
                            html: `
                                <select id="keterangan-select" class="swal2-input" style="width: 300px; font-size: 16px;">
                                    <option value=""> ------- Pilih Reject ------</option>
                                    <option value="Pembatalan oleh User">Pembatalan oleh User</option>
                                    <option value="Pembatalan karena Proses Indent">Pembatalan karena Proses Indent</option>
                                    <option value="Pembatalan karena Stok">Pembatalan karena Stok</option>
                                    <option value="Others">Others</option>
                                </select>
                                <textarea id="textarea-keterangan" class="swal2-input" placeholder="Masukkan keterangan lainnya" style="display: none; width: 300px; font-size: 16px;"></textarea>
                            `,
                            focusConfirm: false,
                            didOpen: () => {
                                // Add event listener to show/hide textarea based on the select option
                                const select = document.getElementById('keterangan-select');
                                const textarea = document.getElementById(
                                    'textarea-keterangan');
                                select.addEventListener('change', function() {
                                    if (select.value === 'Others') {
                                        textarea.style.display = 'block';
                                    } else {
                                        textarea.style.display = 'none';
                                    }
                                });
                            },
                            preConfirm: () => {
                                const keterangan = document.getElementById(
                                    'keterangan-select').value;
                                const textareaValue = document.getElementById(
                                    'textarea-keterangan').value;

                                if (!keterangan) {
                                    Swal.showValidationMessage(
                                        'Keterangan tidak boleh kosong');
                                }

                                if (keterangan === 'Others' && !textareaValue) {
                                    Swal.showValidationMessage(
                                        'Silakan masukkan keterangan untuk opsi "Others"'
                                    );
                                }

                                return keterangan === 'Others' ? textareaValue : keterangan;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                var keterangan = result.value;
                                console.log("Keterangan pembatalan:",
                                    keterangan); // Log keterangan pembatalan yang dipilih

                                // Jika konfirmasi, lakukan AJAX POST request untuk membatalkan item
                                $.ajax({
                                    url: "{{ route('kirim.fpb.cancel', ':id') }}"
                                        .replace(':id',
                                            id), // Menggunakan id yang diambil
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}', // CSRF Token Laravel
                                        keterangan: keterangan // Data keterangan yang dipilih
                                    },
                                    success: function(response) {
                                        console.log("Response from server:",
                                            response); // Log response dari server

                                        Swal.fire(
                                            'Dibatalkan!',
                                            'Item berhasil dibatalkan.',
                                            'success'
                                        ).then(() => {
                                            location
                                                .reload(); // Refresh halaman setelah sukses
                                        });
                                    },
                                    error: function(xhr) {
                                        console.log("Error occurred:", xhr
                                            .responseText
                                        ); // Log error jika terjadi kesalahan

                                        Swal.fire(
                                            'Gagal!',
                                            'Terjadi kesalahan saat membatalkan item.',
                                            'error'
                                        );
                                    }
                                });
                            }
                        });
                    });
                });

                document.querySelectorAll('.btn-finish').forEach(button => {
                    button.addEventListener('click', function() {
                        // Mengambil no_fpb dari data attribute dan mengganti "/" dengan "-"
                        var no_fpb = this.getAttribute('data-no_fpb').replace(/\//g, '-');
                        console.log("FPB Number to send:", no_fpb); // Log FPB number yang diambil

                        Swal.fire({
                            title: 'Apakah anda ingin Finish Form ini?',
                            text: "Data yang telah Finish tidak dapat dirubah!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, finish!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Jika konfirmasi, lakukan AJAX POST request
                                $.ajax({
                                    url: "{{ route('update.PoPengajuan.finish', ':no_fpb') }}"
                                        .replace(':no_fpb', no_fpb),
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}', // CSRF Token Laravel
                                    },
                                    success: function(response) {
                                        console.log("Response from server:",
                                            response); // Log response dari server

                                        Swal.fire(
                                            'Terkirim!',
                                            'Data berhasil dikirim.',
                                            'success'
                                        ).then(() => {
                                            location
                                                .reload(); // Refresh halaman setelah sukses
                                        });
                                    },
                                    error: function(xhr) {
                                        console.log("Error occurred:", xhr
                                            .responseText
                                        ); // Log error jika terjadi kesalahan

                                        Swal.fire(
                                            'Gagal!',
                                            'Terjadi kesalahan saat mengirim data.',
                                            'error'
                                        );
                                    }
                                });
                            }
                        });
                    });
                });

            });

            function getDataFromTable() {
                var data = [];
                var tableRows = document.querySelectorAll('table tbody tr');
                tableRows.forEach(function(row) {
                    var item = {};
                    var cells = row.querySelectorAll('td');
                    item.no = cells[0].innerText;
                    var noPoInput = cells[1].querySelector('input');
                    item.no_po = noPoInput ? noPoInput.value : '';
                    item.nama_barang = cells[2].innerText;
                    item.spesifikasi = cells[3].innerText;
                    item.pcs = cells[4].innerText;
                    item.price_list = cells[5].innerText;
                    // Jika ada kolom 'Subcon', cek panjang cells
                    if (cells.length > 6) {
                        item.target_cost = cells[6].innerText;
                        item.lead_time = cells[7].innerText;
                        item.rekomendasi = cells[8].innerText;
                        item.nama_customer = cells[9].innerText;
                        item.nama_project = cells[10].innerText;
                        item.no_so = cells[11].innerText;
                    }
                    data.push(item);
                });
                return data;
            }

            function createPdfFile(data) {
                // Inisialisasi jsPDF
                const {
                    jsPDF
                } = window.jspdf;
                var doc = new jsPDF();

                // Header PDF
                doc.setFontSize(12);
                doc.text("FORM PERMINTAAN BARANG (FPB)", 14, 10);
                var tableContainer = document.getElementById('table-container');
                var no_fpb = tableContainer.getAttribute('data-no_fpb') || '';
                doc.text("NO FPB: " + no_fpb, 14, 20);

                // Persiapan data untuk tabel
                var headers = ["No", "No PO", "Nama Barang", "Spesifikasi", "PCS", "Price List"];

                // Jika ada kolom 'Subcon', tambahkan kolom tambahan
                if (data.length > 0 && data[0].hasOwnProperty('target_cost')) {
                    headers = headers.concat(["Target Cost", "Lead Time", "Rekomendasi", "Nama Customer", "Nama Project"]);
                }

                var tableData = data.map(function(item) {
                    var row = [
                        item.no,
                        item.no_po,
                        item.nama_barang,
                        item.spesifikasi,
                        item.pcs,
                        item.price_list
                    ];

                    // Jika ada data tambahan Subcon, tambahkan ke baris
                    if (item.hasOwnProperty('target_cost')) {
                        row = row.concat([item.target_cost, item.lead_time, item.rekomendasi, item.nama_customer, item
                            .nama_project, item.no_so
                        ]);
                    }

                    return row;
                });

                // Panggil autoTable untuk membuat tabel
                doc.autoTable({
                    head: [headers],
                    body: tableData,
                    startY: 30 // Atur posisi di mana tabel dimulai di halaman
                });

                // Simpan file PDF
                doc.save('FPB_' + no_fpb + '.pdf');
            }

            function printTable() {
                window.print(); // Memanggil dialog cetak browser
            }

            document.addEventListener('DOMContentLoaded', function() {
                var totalPcs = 0;
                var totalPrice = 0;

                document.querySelectorAll('tbody tr').forEach(function(row) {
                    // Check if the row has status 'Cancel'
                    var statusCell = row.querySelector('td.no-print span.badge');
                    var isCancelled = statusCell && statusCell.textContent.trim() === 'Reject';

                    if (!isCancelled) {
                        // Get PCS (kolom ke-5, index 4)
                        var pcsCell = row.cells[4];
                        var pcs = parseInt(pcsCell ? pcsCell.innerText.replace(/,/g, '') : 0, 10);
                        totalPcs += isNaN(pcs) ? 0 : pcs;

                        // Get Total Harga (kolom ke-7, index 6)
                        var totalHargaCell = row.cells[6];
                        var totalHarga = parseFloat(totalHargaCell ? totalHargaCell.innerText.replace(
                            /Rp|\.|,/g, '').trim() : 0);
                        totalPrice += isNaN(totalHarga) ? 0 : totalHarga;
                    }
                });

                // Update total PCS
                document.getElementById('total-pcs').innerText = totalPcs.toLocaleString();

                // Update total Total Harga
                document.getElementById('total-price_list').innerText = `Rp ${totalPrice.toLocaleString()}`;
            });

            window.addEventListener('beforeprint', function() {
                document.querySelectorAll('tfoot td[colspan="4"]').forEach(function(td) {
                    td.setAttribute('colspan', '3');
                });
            });

            window.addEventListener('afterprint', function() {
                document.querySelectorAll('tfoot td[colspan="3"]').forEach(function(td) {
                    td.setAttribute('colspan', '4');
                });
            });
        </script>

    </main><!-- End #main -->
@endsection
