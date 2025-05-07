@extends('layout')
@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.20.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>PIC</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">PIC</li>
                    <li class="breadcrumb-item active">List Safety Patrol</li>
                </ol>
            </nav>

        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">List Form Safety Patrol</h5>
                            {{-- <div class="text-end">
                                <a class="btn btn-success float-right" href="{{ route('patrol.export') }}">
                                    <i class="bi bi-filetype-xlsx"></i> Export Data
                                    Safety Patrol</a>
                            </div> --}}
                            {{-- <div class="text-end mt-3">
                                <button class="btn btn-success float-right" data-bs-toggle="modal"
                                    data-bs-target="#areaModal">
                                    <i class="bi bi-filetype-xlsx"></i> Export Data Row Safety Patrol
                                </button>
                            </div> --}}
                            <form method="POST" action="{{ url('export-patrol-data') }}">
                                @csrf
                                <div class="form-group">
                                    <label for="area_patrol">Area Patrol:</label>
                                    <span>
                                        <select class="form-control" id="area_patrol" name="area_patrol"
                                            style="width: auto; display: inline-block;">
                                            <option value="All">All</option>
                                            @foreach ($areas as $area)
                                                <option value="{{ $area }}">{{ $area }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-success" type="submit"
                                            style="display: inline-block; margin-left: 10px;">
                                            <i class="bi bi-filetype-xlsx"></i> Export Data
                                        </button>
                                    </span>
                                </div>
                            </form>


                            <br>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive">
                                <table class="datatables datatable" style="table-layout: responsive;">
                                    <thead>
                                        <tr>
                                            <th scope="col" rowspan="2">No</th>
                                            <th scope="col" rowspan="2">Tgl</th>
                                            <th scope="col" rowspan="2">Area</th>
                                            <th scope="col" rowspan="2">PIC</th>
                                            <th scope="col" rowspan="2">Petugas Patrol</th>
                                            <!-- Kelompok Kategori 5S/5R -->
                                            <th scope="colgroup" colspan="6">Kategori 5S/5R</th>
                                            <!-- Kelompok Safety -->
                                            <th scope="colgroup" colspan="6">Safety</th>
                                            <!-- Kelompok Lingkungan -->
                                            <th scope="colgroup" colspan="5">Lingkungan</th>
                                            <th scope="col" rowspan="2">Modifikasi Terakhir</th>
                                            <th scope="col" rowspan="2">Aksi</th>
                                        </tr>
                                        <tr>
                                            <!-- Kategori 5S/5R -->
                                            <th scope="col">Kelengkapan Alat</th>
                                            <th scope="col">Kerapihan Area Kerja</th>
                                            <th scope="col">Kondisi Lingkungan Kerja</th>
                                            <th scope="col">Penempatan Alat/Barang</th>
                                            <th scope="col">Praktik 5S/5R</th>
                                            <th scope="col">Catatan Kategori 5S/5R</th>
                                            <!-- Safety -->
                                            <th scope="col">Checksheet APAR</th>
                                            <th scope="col">Penggunaan APD</th>
                                            <th scope="col">Potensi Bahaya</th>
                                            <th scope="col">Pemeliharaan APD</th>
                                            <th scope="col">Kelengkapan APD</th>
                                            <th scope="col">Catatan Safety</th>
                                            <!-- Lingkungan -->
                                            <th scope="col">Pengelolaan Jenis & Kriteria Limbah</th>
                                            <th scope="col">Kebersihan Lingkungan</th>
                                            <th scope="col">Penyimpanan Limbah</th>
                                            <th scope="col">Tempat Sampah</th>
                                            <th scope="col">Catatan Lingkungan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($patrols as $patrol)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>{{ $patrol->tanggal_patrol }}</td>
                                                <td>{{ $patrol->area_patrol }}</td>
                                                <td>{{ $patrol->pic_area }}</td>
                                                <td>{{ $patrol->petugas_patrol }}</td>
                                                <td>{{ $patrol->kategori_1 }}</td>
                                                <td>{{ $patrol->kategori_2 }}</td>
                                                <td>{{ $patrol->kategori_3 }}</td>
                                                <td>{{ $patrol->kategori_4 }}</td>
                                                <td>{{ $patrol->kategori_5 }}</td>
                                                <td>{{ $patrol->kategori_catatan }}</td>
                                                <td>{{ $patrol->safety_1 }}</td>
                                                <td>{{ $patrol->safety_2 }}</td>
                                                <td>{{ $patrol->safety_3 }}</td>
                                                <td>{{ $patrol->safety_4 }}</td>
                                                <td>{{ $patrol->safety_5 }}</td>
                                                <td>{{ $patrol->safety_catatan }}</td>
                                                <td>{{ $patrol->lingkungan_1 }}</td>
                                                <td>{{ $patrol->lingkungan_2 }}</td>
                                                <td>{{ $patrol->lingkungan_3 }}</td>
                                                <td>{{ $patrol->lingkungan_4 }}</td>
                                                <td>{{ $patrol->lingkungan_catatan }}</td>
                                                <td>{{ $patrol->updated_at }}</td>
                                                <td>
                                                    <a class="btn btn-warning"
                                                        href="{{ route('patrols.detailPatrol', $patrol->id) }}">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>


                            </div>

                        </div>
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

        <script>
            function exportData() {
                var selectedArea = document.getElementById("areaSelection").value;
                var table = document.getElementById("tableToExport");
                if (!table) {
                    console.error("Tabel dengan ID 'tableToExport' tidak ditemukan.");
                    return;
                }
                var worksheet = XLSX.utils.table_to_sheet(table);

                // Filter data berdasarkan area yang dipilih
                var filteredData = {};
                if (selectedArea !== "ALL") {
                    for (var cellAddress in worksheet) {
                        if (cellAddress[0] === 'A' && worksheet[cellAddress].v === selectedArea) {
                            var rowNumber = parseInt(cellAddress.substring(1));
                            filteredData[cellAddress] = worksheet[cellAddress];
                            var categoryStartRow = rowNumber + 1;
                            var categoryEndRow = categoryStartRow + 5; // Jumlah kategori
                            for (var i = categoryStartRow; i < categoryEndRow; i++) {
                                var categoryCellAddress = 'B' + i;
                                var categoryName = worksheet[categoryCellAddress].v;
                                if (!filteredData[categoryCellAddress]) {
                                    filteredData[categoryCellAddress] = worksheet[categoryCellAddress];
                                }
                                for (var j = i + 1; j < categoryEndRow; j++) {
                                    var itemCellAddress = 'C' + j;
                                    if (!filteredData[itemCellAddress]) {
                                        filteredData[itemCellAddress] = worksheet[itemCellAddress];
                                    }
                                    var value = worksheet[itemCellAddress].v;
                                    if (!filteredData[categoryName]) {
                                        filteredData[categoryName] = {};
                                        filteredData[categoryName].total = 0;
                                        filteredData[categoryName].count = 0;
                                    }
                                    filteredData[categoryName].total += value;
                                    filteredData[categoryName].count++;
                                }
                            }
                        }
                    }
                    for (var category in filteredData) {
                        if (filteredData.hasOwnProperty(category) && typeof filteredData[category] === 'object') {
                            var average = filteredData[category].total / filteredData[category].count;
                            var averageCellAddress = 'D' + (categoryEndRow + 1); // Setelah data kategori selesai
                            filteredData[averageCellAddress] = {
                                v: average
                            };
                            // Kolom bulan apa saja yang ada sesuai dengan kolom B (kategori)
                            // Misalnya: Januari, Februari, Maret, dll.
                            // Hitung rata-rata per section sesuai dengan templatenya
                        }
                    }
                    worksheet = filteredData;
                }

                // Code untuk menyesuaikan lebar kolom, tinggi baris, dll. tetap seperti sebelumnya

                var workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");
                var excelBuffer = XLSX.write(workbook, {
                    bookType: 'xlsx',
                    type: 'array'
                });
                var blob = new Blob([excelBuffer], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8'
                });
                var excelFileURL = URL.createObjectURL(blob);
                var today = new Date();
                var day = today.getDate();
                var month = today.toLocaleString('default', {
                    month: 'long'
                });
                var year = today.getFullYear();
                var formattedDate = day + ' ' + month + ' ' + year;
                var a = document.createElement("a");
                a.href = excelFileURL;
                a.download = "Safety-Patrol-" + formattedDate + ".xlsx";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        </script>
        <!-- Add jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- Add jQuery DataTables -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    </main><!-- End #main -->
@endsection
