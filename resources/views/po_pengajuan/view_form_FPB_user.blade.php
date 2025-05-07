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

        #signature-section {
            display: none;
        }

        /* Styles for signature section */
        #signature-section table {
            counter-reset: none;
        }

        #signature-section td::before {
            content: none !important;
        }

        #signature-section td {
            counter-increment: none;
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

            .signature-table td::before {
                content: none;
                /* Disable any content from other table */
            }

            .hide-on-print {
                display: none;
            }

            body * {
                visibility: hidden;
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

            /* Show the Subcont-only columns if they are present */
            .Subcont-only {
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
                colspan: 3;
            }

            #signature-section table {
                page-break-inside: avoid;
                margin-top: 20px;
            }

            #signature-section th,
            #signature-section td {
                font-size: 10px;
                padding: 5px;
            }

            #signature-section th {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            #signature-section td::before {
                display: none !important;
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
        <h1>Halaman View Form </h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Halaman View Form FPB</li>
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

                        <!-- Ambil no_fpb dari item pertama dalam koleksi -->
                        @if ($mstPoPengajuans->isNotEmpty())
                        <p>PIC : {{ $mstPoPengajuans->first()->modified_at }}</p>
                        <p>NO FPB : {{ $mstPoPengajuans->first()->no_fpb }}</p>
                        @else
                        <p>NO FPB : Data not found</p>
                        @endif

                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 2%">No</th>
                                        <th class="no-print" style="width: 20%">No PO</th>
                                        <th style="width: 10%">Nama Barang</th>
                                        <th style="width: 10%">Spesifikasi</th>
                                        <th style="width: 2%">PCS</th>
                                        <th style="width: 10%">Harga Satuan</th>
                                        <th style="width: 10%">Total Harga</th>
                                        @if ($mstPoPengajuans->first()->kategori_po == 'Subcont')
                                        <!-- Tambahkan kolom baru jika kategori_po adalah Subcont -->
                                        <th style="width: 10%; text-align: center;">Target Cost / Unit</th>
                                        <th style="width: 10%">Lead Time</th>
                                        <th style="width: 10%; text-align: center;">Rekomendasi (Jika Ada)</th>
                                        <th style="width: 10%">Nama Customer</th>
                                        <th style="width: 10%">Nama Project</th>
                                        <th style="width: 10%">NO SO</th>
                                        @endif
                                        <th class="no-print">File</th>
                                        <th>Tgl Dibuat</th>
                                        <th class="no-print">Status</th>
                                        <th class="no-print">Aksi</th> <!-- Kolom baru untuk aksi -->
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($mstPoPengajuans as $index => $item)
                                    <tr class="{{ in_array($item->status_2, [8]) ? 'hide-on-print' : '' }}">
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}"
                                            style="text-align: center;">
                                            {{ $index + 1 }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} no-print">
                                            {{ $item->no_po }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}"
                                            style="text-align: center;">
                                            {{ $item->nama_barang }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}"
                                            style="text-align: center;">
                                            {{ $item->spesifikasi }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                            {{ $item->pcs }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">Rp
                                            {{ number_format($item->price_list, 0, ',', '.') }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">Rp
                                            {{ number_format(is_numeric($item->total_harga) ? (float) $item->total_harga
                                            : 0, 0, ',', '.') }}
                                        </td>

                                        @if ($item->kategori_po == 'Subcont')
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} Subcont-only">
                                            Rp {{ number_format($item->target_cost, 0, ',', '.') }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} Subcont-only">
                                            {{ $item->lead_time }} hari</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} Subcont-only">
                                            {{ $item->rekomendasi }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} Subcont-only">
                                            {{ $item->nama_customer }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} Subcont-only">
                                            {{ $item->nama_project }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} Subcont-only">
                                            {{ $item->no_so }}</td>
                                        @endif

                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} no-print" style="text-align: center;">
                                            @if (!empty(json_decode($item->file_name, true)))  
                                                <a href="{{ route('download.file', $item->id) }}" 
                                                   class="btn btn-sm btn-primary" title="Download All Files">
                                                    <i class="fas fa-download"></i> Download All
                                                </a>
                                            @else
                                                <span class="text-muted">No File</span>
                                            @endif
                                        </td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }}">
                                            {{ $item->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="{{ $item->status_2 == 8 ? 'disabled-cell' : '' }} no-print">
                                            @if ($item->status_2 == 6)
                                            <span class="badge bg-info align-items-center" style="font-size: 18px;">PO
                                                Confirm</span>
                                            @elseif($item->status_2 == 7)
                                            <span class="badge bg-info align-items-center" style="font-size: 18px;">PO
                                                Release</span>
                                            @elseif($item->status_2 == 8)
                                            <span class="badge bg-danger align-items-center"
                                                style="font-size: 18px;">Reject</span>
                                            @elseif($item->status_2 == 10)
                                            <span class="badge bg-danger align-items-center"
                                                style="font-size: 18px;">Pengajuan Reject</span>
                                            @endif
                                        </td>
                                        <td class="no-print">
                                            <button type="button" class="btn btn-info btn-sm btn-view ml-2"
                                                title="View Details" data-id="{{ $item->id }}">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            @if (
                                            $item->status_1 != 1 &&
                                            $item->status_1 != 8 &&
                                            $item->status_1 != 9 &&
                                            $item->status_1 != 10 &&
                                            $item->status_2 != 8 &&
                                            $item->modified_at === auth()->user()->name)
                                            <button type="button" class="btn btn-danger btn-sm btn-cancel ml-2"
                                                title="Pengajuan Reject" data-id="{{ $item->id }}">
                                                <i class="fas fa-close"></i> Pengajuan Reject
                                            </button>
                                            @endif
                                            @if (in_array(auth()->user()->role_id, [1, 5, 11, 2, 7, 40, 50, 30, 39, 12,
                                            14]))
                                            @if ($item->status_2 != 8 && $item->status_1 != 9)
                                            <button type="button" class="btn btn-danger btn-sm btn-cancel-2 ml-2"
                                                title="Reject Item" data-id="{{ $item->id }}">
                                                <i class="fas fa-close"></i> Reject Item
                                            </button>
                                            @endif
                                            @endif
                                        </td>
                                    </tr>
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
                                        <td colspan="12"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
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
                                        <td style="width: 15%">Finance</td>
                                        <td style="width: 15%">Purchasing</td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: top; height: 60px;">
                                            @if ($mstPoPengajuans->first()->status_1 >= 2 &&
                                            $mstPoPengajuans->first()->status_1 <= 13) <p><b>SUBMITTED by {{
                                                    $mstPoPengajuans->first()->modified_at }}</b></p>
                                                @else
                                                <p>&nbsp;</p>
                                                @endif
                                        </td>
                                        <td style="vertical-align: top; height: 60px;">
                                            @if ($mstPoPengajuans->first()->status_1 >= 3 &&
                                            $mstPoPengajuans->first()->status_1 <= 13) <p><b>APPROVED by {{ $deptHead
                                                    }}</b></p>
                                                @else
                                                <p>&nbsp;</p>
                                                @endif
                                        </td>
                                        <td style="vertical-align: top; height: 60px;">
                                            @if ($mstPoPengajuans->first()->status_1 >= 4 &&
                                            $mstPoPengajuans->first()->status_1 <= 13) <p><b>APPROVED by {{ $userAccbody
                                                    }}</b></p>
                                                @else
                                                <p>&nbsp;</p>
                                                @endif
                                        </td>
                                        <td style="vertical-align: top; height: 60px;">
                                            @if ($mstPoPengajuans->first()->status_1 >= 5 &&
                                            $mstPoPengajuans->first()->status_1 <= 13) <p>
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
                                            @if ($mstPoPengajuans->first()->status_1 >= 6 &&
                                            $mstPoPengajuans->first()->status_1 <= 13) <p><b>APPROVED by VIVIAN
                                                    ANGELIKA</b></p>
                                                @else
                                                <p>&nbsp;</p>
                                                @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tgl:
                                            {{ optional($matchingTrsPoPengajuans->firstWhere('status', 2))->created_at ?
                                            optional($matchingTrsPoPengajuans->firstWhere('status',
                                            2))->created_at->format('d/m/y') : '' }}
                                        </td>
                                        <td>Tgl:
                                            {{ optional($matchingTrsPoPengajuans->firstWhere('status', 3))->created_at ?
                                            optional($matchingTrsPoPengajuans->firstWhere('status',
                                            3))->created_at->format('d/m/y') : '' }}
                                        </td>
                                        <td>Tgl:
                                            {{ optional($matchingTrsPoPengajuans->firstWhere('status', 4))->created_at ?
                                            optional($matchingTrsPoPengajuans->firstWhere('status',
                                            4))->created_at->format('d/m/y') : '' }}
                                        </td>
                                        <td>Tgl:
                                            {{ optional($matchingTrsPoPengajuans->firstWhere('status', 5))->created_at ?
                                            optional($matchingTrsPoPengajuans->firstWhere('status',
                                            5))->created_at->format('d/m/y') : '' }}
                                        </td>
                                        <td>Tgl:
                                            {{ optional($matchingTrsPoPengajuans->firstWhere('status', 6))->created_at ?
                                            optional($matchingTrsPoPengajuans->firstWhere('status',
                                            6))->created_at->format('d/m/y') : '' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3" style="margin-top: 2%;">
                        <a href="{{ route('index.PO.user') }}" class="btn btn-secondary btn-sm" style="margin-top: 3%;"
                            title="Back">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <a href="javascript:void(0);" class="btn btn-success btn-sm" style="margin-top: 3%;"
                            onclick="printTable()">
                            <i class="fas fa-print"></i>Print PDF
                        </a>
                    </div>

                    <h4 style="margin-top: 3%">
                        <i class="fas fa-eye" style="margin-left: 10px;"></i>
                        <b>HISTORI PERMINTAAN BARANG (FPB)</b>
                    </h4>
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
            <!-- Wadah untuk tabel yang akan dibuat -->
            <div id="tableContainer" hidden></div>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

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
                                                '<span class="badge bg-danger">Item Reject</span>';
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

                        // SweetAlert untuk memasukkan keterangan pembatalan
                        Swal.fire({
                            title: 'Masukkan Keterangan Pengajuan Reject',
                            html: `
                                <textarea id="textarea-keterangan" class="swal2-input" placeholder="Masukkan keterangan Pengajuan" style="width: 300px; font-size: 16px;"></textarea>
                            `,
                            focusConfirm: false,
                            preConfirm: () => {
                                const textareaValue = document.getElementById(
                                    'textarea-keterangan').value;

                                if (!textareaValue) {
                                    Swal.showValidationMessage(
                                        'Keterangan tidak boleh kosong'
                                    );
                                }

                                return textareaValue;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                var keterangan = result.value;
                                console.log("Keterangan pembatalan:",
                                    keterangan); // Log keterangan pembatalan yang dipilih

                                // Jika konfirmasi, lakukan AJAX POST request untuk membatalkan item
                                $.ajax({
                                    url: "{{ route('kirim.fpb.cancel2', ':id') }}"
                                        .replace(':id',
                                            id), // Menggunakan id yang diambil
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}', // CSRF Token Laravel
                                        keterangan: keterangan // Data keterangan yang dimasukkan
                                    },
                                    success: function(response) {
                                        console.log("Response from server:",
                                            response); // Log response dari server

                                        Swal.fire(
                                            'Diajukan!',
                                            'Item berhasil Diajukan.',
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
                                            'Terjadi kesalahan saat Diajukan item.',
                                            'error'
                                        );
                                    }
                                });
                            }
                        });
                    });
                });

                document.querySelectorAll('.btn-cancel-2').forEach(button => {
                    button.addEventListener('click', function() {
                        var id = this.getAttribute('data-id'); // Mengambil id dari tombol
                        console.log("ID to cancel:", id); // Log id yang diambil

                        // SweetAlert untuk memasukkan keterangan pembatalan
                        Swal.fire({
                            title: 'Masukkan Keterangan Reject Item',
                            html: `
                                <textarea id="textarea-keterangan" class="swal2-input" placeholder="Masukkan Keterangan Reject Item" style="width: 300px; height: 100px; font-size: 16px;"></textarea>
                            `,
                            focusConfirm: false,
                            preConfirm: () => {
                                const keterangan = document.getElementById(
                                    'textarea-keterangan').value;

                                if (!keterangan) {
                                    Swal.showValidationMessage(
                                        'Keterangan tidak boleh kosong');
                                }

                                return keterangan;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                var keterangan = result.value;
                                console.log("Keterangan pembatalan:",
                                    keterangan); // Log keterangan pembatalan yang dipilih

                                // Jika konfirmasi, lakukan AJAX POST request untuk membatalkan item
                                $.ajax({
                                    url: "{{ route('kirim.fpb.reject', ':id') }}"
                                        .replace(':id',
                                            id), // Menggunakan id yang diambil
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}', // CSRF Token Laravel
                                        keterangan: keterangan // Data keterangan yang dimasukkan
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

            });

            function renumberVisibleRows() {
                // Ambil semua baris yang terlihat dalam tabel, kecuali yang memiliki class 'hide-on-print'
                const tableRows = document.querySelectorAll("tbody tr:not(.hide-on-print)");
                let index = 1;
                // Loop setiap baris yang terlihat
                tableRows.forEach(row => {
                    // Periksa apakah baris ini bukan bagian dari #signature-section
                    if (!row.closest('#signature-section')) {
                        const noCell = row.querySelector('td:first-child');
                        if (noCell) {
                            noCell.textContent = index; // Setel nomor urut baru
                            index++;
                        }
                    }
                });
            }

            // Tambahkan event listener sebelum halaman dicetak
            window.addEventListener('beforeprint', () => {
                renumberVisibleRows(); // Panggil fungsi penomoran ulang sebelum mencetak
            });


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