@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            .container {
                margin-top: 20px;
            }

            .card {
                background-color: #f8f9fa;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                width: 100%;
                /* Lebar card 100% */
                box-sizing: border-box;
                /* Memastikan padding tidak menambah lebar card */
            }

            .card-content {
                display: flex;
                flex-direction: column;
            }

            .form-group {
                margin-bottom: 10px;
            }

            label {
                font-weight: bold;
            }

            .styled-table {
                width: 100%;
                border-collapse: collapse;
                margin: 25px 0;
                font-size: 14px;
                text-align: left;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
            }

            .styled-table thead tr {
                background-color: #1072a0;
                color: #ffffff;
                text-align: left;
            }

            .styled-table th,
            .styled-table td {
                padding: 12px 15px;
            }

            .styled-table tbody tr {
                border-bottom: 1px solid #dddddd;
            }

            .styled-table tbody tr:nth-of-type(even) {
                background-color: #f3f3f3;
            }

            .styled-table tbody tr:last-of-type {
                border-bottom: 2px solid #009879;
            }

            .styled-table tbody tr.active-row {
                font-weight: bold;
                color: #009879;
            }

            select.status-dropdown {
                color: white;
                /* Default text color */
            }
        </style>
        <div class="pagetitle">
            <h1>Halaman Form View Data Training HRGA</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu View Form Training</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <!-- Card untuk Departemen dan Nama -->
            <div class="card">
                <!-- Tabel di dalam card -->
                <div class="table-container" style="overflow-x:auto;">
                    <table id="table" class="styled-table" style="width:100%;">
                        <thead>
                            <tr style="background-color: #4f83e4;">
                                <th scope="col" rowspan="2">NO</th>
                                <th scope="col" rowspan="2">Section</th>
                                <th scope="col" rowspan="2">Job Position</th>
                                <th scope="col" rowspan="2">Nama Karyawan</th>
                                <th scope="col" rowspan="2">Program Training</th>
                                <th scope="col" rowspan="2">Kategori Competency</th>
                                <th scope="col" rowspan="2">Competency</th>
                                <th scope="col" rowspan="2" style="width: 10%">Due Date</th>
                                <th scope="col" rowspan="2" style="width: 10%">Budget</th>
                                <th scope="col" rowspan="2">Lembaga</th>
                                <th scope="col" rowspan="2">Keterangan Tujuan</th>
                            </tr>
                            <tr style="background-color: #f0ad4e;">
                                <th scope="col">Nama Program</th>
                                <th scope="col">Date Actual</th>
                                <th scope="col" style="width: 15%">Biaya Actual</th>
                                <th scope="col">Lembaga</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <!-- Tampilkan data yang tidak memiliki tahun_usulan -->
                            @foreach ($data as $index => $item)
                                @if (is_null($item->tahun_usulan))
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->section ?? '-' }}</td>
                                        <td>{{ $item->id_job_position ?? '-' }}</td>
                                        <td>{{ $item->user->name ?? '-' }}</td>
                                        <td>{{ $item->program_training ?? '-' }}</td>
                                        <td>{{ $item->kategori_competency ?? '-' }}</td>
                                        <td>{{ $item->competency ?? '-' }}</td>
                                        <td>{{ $item->due_date ?? '-' }}</td>
                                        <td>
                                            {{ $item->biaya ? 'Rp ' . number_format((float) str_replace(['Rp', '.', ' '], '', $item->biaya), 0, ',', '.') : '-' }}
                                        </td>
                                        <td>{{ $item->lembaga ?? '-' }}</td>
                                        <td>{{ $item->keterangan_tujuan ?? '-' }}</td>
                                        <td>{{ $item->program_training_plan ?? '-' }}</td>
                                        <td>{{ $item->due_date_plan ?? '-' }}</td>
                                        <td>
                                            {{ $item->biaya_plan ? 'Rp ' . number_format((float) str_replace(['Rp', '.', ' '], '', $item->biaya_plan), 0, ',', '.') : '-' }}
                                        </td>
                                        <td>{{ $item->lembaga_plan ?? '-' }}</td>
                                        <td>{{ $item->keterangan_plan ?? '-' }}</td>
                                        <td>
                                            @php
                                                $statusColor = '';
                                                switch ($item->status_2) {
                                                    case 'Mencari Vendor':
                                                        $statusColor = 'background-color: blue; color: white;';
                                                        break;
                                                    case 'Proses Pendaftaran':
                                                        $statusColor = 'background-color: orange; color: white;';
                                                        break;
                                                    case 'On Progress':
                                                        $statusColor = 'background-color: yellow; color: black;';
                                                        break;
                                                    case 'Done':
                                                        $statusColor = 'background-color: green; color: white;';
                                                        break;
                                                    case 'Pending':
                                                        $statusColor = 'background-color: gray; color: white;';
                                                        break;
                                                    case 'Ditolak':
                                                        $statusColor = 'background-color: red; color: white;';
                                                        break;
                                                }
                                            @endphp
                                            <span
                                                style="display: inline-block; padding: 5px 10px; border-radius: 5px; {{ $statusColor }}">
                                                {{ $item->status_2 ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                            <!-- Tambahkan subtotal setelah iterasi data yang tidak memiliki tahun_usulan -->
                            @php
                                // Hitung total budget hanya jika tahun_usulan kosong
                                $totalBudget = $data
                                    ->filter(function ($item) {
                                        return empty($item->tahun_usulan);
                                    })
                                    ->sum(function ($item) {
                                        // Konversi biaya ke float
                                        return (float) str_replace(['Rp', '.', ' '], '', $item->biaya);
                                    });

                                $totalBudget2 = $data
                                    ->filter(function ($item) {
                                        return empty($item->tahun_usulan);
                                    })
                                    ->sum(function ($item) {
                                        // Konversi biaya_plan ke float
                                        return (float) str_replace(['Rp', '.', ' '], '', $item->biaya_plan);
                                    });
                            @endphp
                            <tr>
                                <td></td>
                                <td colspan="8" style="text-align:right; font-weight:bold;">Sub Total 1: Rp
                                    {{ number_format($totalBudget, 0, ',', '.') }}</td>
                                <td colspan="5" style="text-align:right; font-weight:bold;">Sub Total Actual 1: Rp
                                    {{ number_format($totalBudget2, 0, ',', '.') }}</td>
                            </tr>

                            <!-- Tampilkan judul "ADDITIONAL" sebelum data yang memiliki tahun_usulan -->
                            <tr>
                                <td colspan="17" style="text-align:left; font-weight:bold;">
                                    <h3><b>ADDITIONAL</b> <i class="fas fa-chevron-down"></i></h3>
                                </td>
                            </tr>

                            <!-- Tampilkan data yang memiliki tahun_usulan -->
                            @foreach ($data as $index => $item)
                                @if (!is_null($item->tahun_usulan))
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ !empty($item->section) ? $item->section : '-' }}</td>
                                        <td>{{ !empty($item->id_job_position) ? $item->id_job_position : '-' }}</td>
                                        <td>{{ !empty($item->user->name) ? $item->user->name : '-' }}</td>
                                        <td>{{ !empty($item->program_training) ? $item->program_training : '-' }}</td>
                                        <td>{{ !empty($item->kategori_competency) ? $item->kategori_competency : '-' }}
                                        </td>
                                        <td>{{ !empty($item->competency) ? $item->competency : '-' }}</td>
                                        <td>{{ !empty($item->due_date) ? $item->due_date : '-' }}</td>
                                        <td>{{ !empty($item->biaya) ? 'Rp ' . number_format($item->biaya, 0, ',', '.') : '-' }}
                                        </td>
                                        <td>{{ !empty($item->lembaga) ? $item->lembaga : '-' }}</td>
                                        <td>{{ !empty($item->keterangan_tujuan) ? $item->keterangan_tujuan : '-' }}</td>
                                        <td>{{ !empty($item->program_training_plan) ? $item->program_training_plan : '-' }}
                                        </td>
                                        <td>{{ !empty($item->due_date_plan) ? $item->due_date_plan : '-' }}</td>
                                        <td>{{ !empty($item->biaya_plan) ? 'Rp ' . number_format($item->biaya_plan, 0, ',', '.') : '-' }}
                                        </td>
                                        <td>{{ !empty($item->lembaga_plan) ? $item->lembaga_plan : '-' }}</td>
                                        <td>{{ !empty($item->keterangan_plan) ? $item->keterangan_plan : '-' }}</td>
                                        <td>
                                            @php
                                                $statusColor = '';
                                                switch ($item->status_2) {
                                                    case 'Mencari Vendor':
                                                        $statusColor = 'background-color: blue; color: white;';
                                                        break;
                                                    case 'Proses Pendaftaran':
                                                        $statusColor = 'background-color: orange; color: white;';
                                                        break;
                                                    case 'On Progress':
                                                        $statusColor = 'background-color: yellow; color: black;';
                                                        break;
                                                    case 'Done':
                                                        $statusColor = 'background-color: green; color: white;';
                                                        break;
                                                    case 'Pending':
                                                        $statusColor = 'background-color: gray; color: white;';
                                                        break;
                                                    case 'Ditolak':
                                                        $statusColor = 'background-color: red; color: white;';
                                                        break;
                                                }
                                            @endphp
                                            <span
                                                style="display: inline-block; padding: 5px 10px; border-radius: 5px; {{ $statusColor }}">
                                                {{ !empty($item->status_2) ? $item->status_2 : '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                            <!-- Tambahkan subtotal setelah iterasi data yang memiliki tahun_usulan -->
                            @php
                                // Filter data yang memiliki tahun_usulan
                                $filteredData = $data->filter(function ($item) {
                                    return !is_null($item->tahun_usulan);
                                });

                                // Hitung total budget dan biaya_plan untuk data yang memiliki tahun_usulan
                                $totalBudgetTabelKedua = $filteredData->sum(function ($item) {
                                    return (float) str_replace(['Rp', '.', ' '], '', $item->biaya); // Konversi ke float
                                });

                                $totalBudgetTabelKeduasub2 = $filteredData->sum(function ($item) {
                                    return (float) str_replace(['Rp', '.', ' '], '', $item->biaya_plan); // Konversi ke float
                                });

                                // Total budget dari tabel pertama (data tanpa tahun_usulan)
                                $totalBudgetTabelPertama = $data
                                    ->filter(function ($item) {
                                        return is_null($item->tahun_usulan);
                                    })
                                    ->sum(function ($item) {
                                        return (float) str_replace(['Rp', '.', ' '], '', $item->biaya); // Konversi ke float
                                    });

                                $totalBudgetTabelPertama2 = $data
                                    ->filter(function ($item) {
                                        return is_null($item->tahun_usulan);
                                    })
                                    ->sum(function ($item) {
                                        return (float) str_replace(['Rp', '.', ' '], '', $item->biaya_plan); // Konversi ke float
                                    });

                                // Hitung total biaya keseluruhan
                                $totalBiayaPlan = $totalBudgetTabelPertama + $totalBudgetTabelKedua;
                                $totalBiayaPlan2 = $totalBudgetTabelPertama2 + $totalBudgetTabelKeduasub2;
                            @endphp

                            <!-- Baris Subtotal untuk data dengan tahun_usulan -->
                            <tr>
                                <td></td>
                                <td colspan="8" style="text-align:right; font-weight:bold;">Sub Total 2: Rp
                                    {{ number_format($totalBudgetTabelKedua, 0, ',', '.') }}</td>
                                <td colspan="5" style="text-align:right; font-weight:bold;">Sub Total Actual 2: Rp
                                    {{ number_format($totalBudgetTabelKeduasub2, 0, ',', '.') }}</td>
                            </tr>

                            <!-- Baris Total Keseluruhan -->
                            <tr>
                                <td></td>
                                <td colspan="8" style="text-align:right; font-weight:bold;">Total: Rp
                                    {{ number_format($totalBiayaPlan, 0, ',', '.') }}</td>
                                <td colspan="5" style="text-align:right; font-weight:bold;">Total Actual: Rp
                                    {{ number_format($totalBiayaPlan2, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px;">
                    <strong>Keterangan Status:</strong>
                    <ul
                        style="list-style-type: none; padding-left: 0; margin-top: 10px; display: flex; gap: 15px; align-items: center;">
                        <li style="display: flex; align-items: center;">
                            <span
                                style="background-color: blue; color: white; padding: 5px 15px; border-radius: 5px; margin-right: 5px;"><b>Biru</b></span>
                            - Mencari Vendor
                        </li>
                        <li style="display: flex; align-items: center;">
                            <span
                                style="background-color: orange; color: white; padding: 5px 15px; border-radius: 5px; margin-right: 5px;"><b>Orange</b></span>
                            - Proses Pendaftaran
                        </li>
                        <li style="display: flex; align-items: center;">
                            <span
                                style="background-color: yellow; color: black; padding: 5px 15px; border-radius: 5px; margin-right: 5px;"><b>Kuning</b></span>
                            - On Progress
                        </li>
                        <li style="display: flex; align-items: center;">
                            <span
                                style="background-color: green; color: white; padding: 5px 15px; border-radius: 5px; margin-right: 5px;"><b>Hijau</b></span>
                            - Done
                        </li>
                        <li style="display: flex; align-items: center;">
                            <span
                                style="background-color: rgb(154, 150, 150); color: rgb(251, 251, 251); padding: 5px 15px; border-radius: 5px; margin-right: 5px;"><b>Abu</b></span>
                            - Pending
                        </li>
                        <li style="display: flex; align-items: center;">
                            <span
                                style="background-color: red; color: white; padding: 5px 15px; border-radius: 5px; margin-right: 5px;"><b>Merah</b></span>
                            - Ditolak
                        </li>
                    </ul>
                </div>
                <div style="margin-top: 3%">
                    <a href="{{ route('indexPD2') }}" class="btn btn-secondary">Close</a>
                    <button onclick="exportExcel()" class="btn btn-primary">
                        <i class="bi bi-printer-fill"></i>Export Excel
                    </button>
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
        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script>
            function exportExcel() {
                // Dapatkan elemen tabel
                var table = document.getElementById("table");

                // Buat elemen dengan tag <table> sebagai string
                var html = table.outerHTML;

                // Buat URI untuk format data Excel
                var uri = 'data:application/vnd.ms-excel;base64,';

                // Fungsi untuk encoding string ke base64
                function base64(s) {
                    return window.btoa(unescape(encodeURIComponent(s)));
                }

                // Format sederhana untuk file Excel
                var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" ' +
                    'xmlns:x="urn:schemas-microsoft-com:office:excel" ' +
                    'xmlns="http://www.w3.org/TR/REC-html40"><head>' +
                    '<meta charset="UTF-8"><style>table, td {border: 1px solid black;}</style></head><body>' +
                    '<table>{table}</table></body></html>';

                // Gantikan placeholder dengan isi tabel
                var excelHtml = template.replace(/{table}/g, html);

                // Buat elemen <a> untuk download
                var downloadLink = document.createElement("a");

                // Buat nama file untuk Excel
                var fileName = "Report_People Development.xls";

                // Buat link download
                downloadLink.href = uri + base64(excelHtml);

                // Set nama file yang akan didownload
                downloadLink.download = fileName;

                // Simulasikan klik untuk mulai download
                downloadLink.click();
            }
        </script>

    </main><!-- End #main -->
@endsection
