<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Astra Daido Steel Indonesia</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="assets/img/logo-menu.png" rel="icon">
    <link href="assets/img/logo-menu.png" rel="apple-touch-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">


    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">

    {{-- jadwal kunjungan calender --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.css" rel="stylesheet" />

    <style>
        .logo-img {
            width: 5%;
            height: 5%;
        }

        .font-si {
            font-family: 'Cambria', serif;
            color: aliceblue;
        }


        .navbar {
            background: rgb(0, 0, 114);
        }

        .nav-link {
            transition: color 0.3s;
            color: #f8f9fa;
        }

        .nav-link:hover {
            color: aqua;
        }

        .dropdown-menu {
            transition: all 0.3s ease;
            font-family: 'Cambria', serif;
            font-size: 6pt;
            color: #f8f9fa;
        }

        .sidebar1 {
            display: none;
            /* Sembunyikan sidebar di perangkat mobile */
        }

        .sidebar1.active {
            display: block;
            /* Tampilkan sidebar jika aktif */
        }

        .nav-item {
            font-family: 'Cambria', serif;
            font-size: 11pt;
        }

        .navbar-nav {
            margin-left: 10px;
        }

        .nav-content {
            display: none;
            /* Menyembunyikan elemen secara default */
        }

        .collapse.show {
            display: block;
            /* Menampilkan ketika dalam keadaan collapse show */
        }

        @media (max-width: 768px) {
            .navbar .dropdown-menu {
                position: static;
                /* Agar dropdown ditampilkan dengan baik di bawah */
            }
        }
    </style>

<body>
    <header id="header" class="fixed-top">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
            <!-- CSRF token untuk keamanan -->
        </form>
        <nav class="navbar navbar-expand-lg">
            {{-- <br><br><br> --}}
            <div class="container-fluid">
                <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon bg-light"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <!-- Dropdown Kelola Akun -->
                        @if (in_array(Auth::user()->role_id, [1, 14, 15]))
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown1" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Kelola Data
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown1">
                                <li><a class="dropdown-item" href="#">Akun Users</a></li>
                                <li><a class="dropdown-item" href="#">Customers</a></li>
                                {{-- <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li> --}}
                            </ul>
                        </li>
                        @endif
                        <!-- Dropdown Dashboard -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown2" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Dashboard
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown2">
                                <li><a class="dropdown-item" href="{{ route('dashboardMaintenance') }}">Maintenance</a>
                                </li>
                                <li><a class="dropdown-item" href="{{ route('dshandling') }}">Handling Klaim dan
                                        Komplain</a></li>
                                <li><a class="dropdown-item" href="{{ route('dsCompetency') }}">People Development</a>
                                </li>
                                <li><a class="dropdown-item" href="{{ route('dashboardSS') }}">Sumbang Saran</a></li>
                                <li><a class="dropdown-item" href="{{ route('dsKnowlege') }}">Knowledge Management</a>
                                </li>
                                {{-- <li><a class="dropdown-item" href="{{ route('reportpatrol') }}">Safety Patrol</a>
                                </li> --}}
                            </ul>
                        </li>
                        <!-- Dropdown Sales -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Sales
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Form Permintaan Perbaikan
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('sales.index') }}">Data Form
                                                Perbaikan</a></li>
                                        <li><a class="dropdown-item" href="{{ route('fpps.history') }}">Riwayat Form
                                                Perbaikan</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Handling Klaim dan Komplain
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('index') }}">Form Pengajuan
                                                Klaim/Komplain</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('showHistoryCLaimComplain') }}">Riwayat
                                                Klaim/Komplain</a></li>
                                        <li><a class="dropdown-item" href="{{ route('scheduleVisit') }}">Jadwal
                                                Kunjungan</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Inquiry Status
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('createinquiry') }}">Form Inquiry
                                                Material</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <!-- Dropdown Productions -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Productions
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="{{ route('fpps.index') }}">
                                        Form Permintaan Perbaikan</a></li>
                                <li><a class="dropdown-item" href="{{ route('fpps.history') }}">
                                        Riwayat Permintaan Perbaikan</a></li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Bagian Maintenance
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('dashboardmesins') }}">
                                                Kelola DMI</a></li>
                                        <li><a class="dropdown-item" href="{{ route('deptmtce.index') }}">
                                                Persetujuan FPP</a></li>
                                        <li><a class="dropdown-item" href="{{ route('fpps.history') }}">
                                                Riwayat Form Perbaikan</a></li>
                                        <li><a class="dropdown-item" href="{{ route('dashboardPreventive') }}">
                                                Tabel Preventif</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Bagian Engineering
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('submission') }}">
                                                Form Tindak Lanjut</a></li>
                                        <li><a class="dropdown-item" href="{{ route('showHistoryCLaimComplain') }}">
                                                Riwayat Klaim & Komplain</a></li>
                                        <li><a class="dropdown-item" href="{{ route('scheduleVisit') }}">
                                                Jadwal Kunjungan</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Maintenance Korektif
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ asset('dashboardmaintenance') }}">Terima
                                                Form Perbaikan</a></li>
                                        <li><a class="dropdown-item" href="{{ route('fpps.history') }}">Riwayat Form
                                                Perbaikan</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Maintenance Preventif
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item"
                                                href="{{ route('dashboardPreventiveMaintenance') }}">Jadwal
                                                Preventif</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <!-- Dropdown Procurement -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Permintaan Barang Jasa
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="{{ route('index.PO') }}">Form Pengajuan
                                        Barang/Jasa</a></li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Persetujuan Form
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('index.PO.user') }}">User
                                                Section</a></li>
                                        <li><a class="dropdown-item" href="{{ route('index.PO.Dept') }}">Ka. Dept</a>
                                        </li>
                                        <li><a class="dropdown-item" href="{{ route('index.PO.finance') }}">Finance
                                                Section</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('index.PO.procurement') }}">Procurement Menu 1</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('index.PO.procurement2') }}">Procurement Menu 2</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Panawaran Subcont Project Sales
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('indexSales') }}">
                                                Form Penawaran Subcont</a></li>
                                        <li><a class="dropdown-item" href="{{ route('indexProc') }}">
                                                Persetujuan Subcont</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <!-- Dropdown Human Resource -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                People Development
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Pengajuan Form Knowledge Management
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('pengajuanKM') }}">
                                                Form Knowledge Management</a></li>
                                        <li><a class="dropdown-item" href="{{ route('persetujuanKM') }}">
                                                Persetujuan Knowledge Management</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Base Competency
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('jobShow') }}">
                                                Form Job Position</a></li>
                                        <li><a class="dropdown-item" href="{{ route('tcShow') }}">
                                                Form Pengajuan Competency</a></li>
                                        <li><a class="dropdown-item" href="{{ route('penilaian.index') }}">
                                                Penilaian Technical Competency Ka. Sie</a></li>
                                        <li><a class="dropdown-item" href="{{ route('penilaian.index') }}">
                                                Penilaian Technical Competency Ka. Dept</a></li>
                                        <li><a class="dropdown-item" href="{{ route('penilaian.index2') }}">
                                                History Development</a></li>
                                        <li><a class="dropdown-item" href="{{ route('job.positions.index') }}">
                                                Summary Competency</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Training Development
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('indexPD') }}">
                                                Form Pengajuan Training</a></li>
                                        <li><a class="dropdown-item" href="{{ route('indexPD2') }}">
                                                Persetujuan Development</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <!-- Dropdown Sumbang Saran -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Sumbang Saran
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="{{ route('showSS') }}">Form Sumbang Saran</a></li>
                                <li><a class="dropdown-item" href="{{ route('forumSS') }}">Overview Sumbang Saran</a>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Persetujuan Atasan
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('showKonfirmasiForeman') }}">Ka.
                                                Sie</a></li>
                                        <li><a class="dropdown-item" href="{{ route('showKonfirmasiDeptHead') }}">Ka.
                                                Dept</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Penilaian
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item"
                                                href="{{ route('showKonfirmasiKomite') }}">Penilaian Komite</a></li>
                                        <li><a class="dropdown-item" href="{{ route('showKonfirmasiHRGA') }}">Penilaian
                                                HRGA</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <!-- Search Bar -->
                    <form class="d-flex justify-content-end">
                        {{-- <input class="form-control me-2" disabled> --}}
                        {{-- <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                        --}}
                        <a class="btn btn-outline-warning me-3" style="color: rgb(0, 0, 0)"
                            href="{{ route('showDataDiri') }}" title="Profile">
                            <i class="bx bxs-user-circle"></i>
                        </a>
                        <a class="btn btn-outline-warning" style="color: rgb(31, 0, 0)" href="#" title="Keluar"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="bi bi-arrow-right-square-fill"></i>
                        </a>
                    </form>
                </div>
            </div>
        </nav>
    </header>
    @yield('content');

    <!-- ======= Footer ======= -->
    <footer id="footer" class="footer">
        <div class="copyright">
            &copy; Copyright <strong><span>ADASI</span></strong>. All Rights Reserved
        </div>
    </footer><!-- End Footer -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
                const toggler = document.querySelector('.navbar-toggler');
                const collapseElement = document.getElementById('navbarSupportedContent');
                
                toggler.addEventListener('click', function() {
                if (collapseElement.classList.contains('show')) {
                // Menyembunyikan elemen
                collapseElement.classList.remove('show');
                collapseElement.style.display = 'none'; // Sembunyikan
                toggler.setAttribute('aria-expanded', 'false'); // Update status
                } else {
                // Menampilkan elemen
                collapseElement.classList.add('show');
                collapseElement.style.display = 'block'; // Tampilkan
                toggler.setAttribute('aria-expanded', 'true'); // Update status
                }
                });
                });
    </script>

    <script>
        $(document).ready(function() {
            // Hover function for dropdowns
            $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                },function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                }
            );
        });
    </script>


    {{-- <script>
        $(document).ready(function() {
        $('.toggle-sidebar-btn').on('click', function() {
            $('.sidebar1').toggleClass('active'); // Menambahkan atau menghapus class active di sidebar
        });
    });
    </script> --}}

    <!-- Vendor JS Files -->
    <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/chart.js/chart.umd.js') }}"></script>
    <script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
    <script src="{{ asset('assets/vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/php-email-form/validate.js') }}"></script>

    <!-- Template Main JS File -->
    <script src="{{ asset('assets/js/main.js') }}"></script>

    {{-- JS Search DropDown --}}
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
    {{-- datatable --}}
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    {{-- searchdropdownJS --}}
    <!-- Tambahkan library Select2 -->
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script> --}}
    {{-- JSSweetAlert --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    {{-- Datatble --}}
    <script src="js/datatables-simple-demo.js"></script>

    {{-- DateRangePicker --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JS for jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <!-- JS for full calender -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.js"></script>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- Menghubungkan ke jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>

    @if (View::hasSection('scripts'))
    @yield('scripts')
    <script>
        //datepickerExcel
            // Fungsi untuk mendapatkan nilai tanggal dari input dan mengatur tautan tombol eksport
            // Mengambil nilai tanggal mulai dan tanggal selesai
            function exportToExcel() {
                // Mengambil nilai tanggal mulai dan tanggal selesai
                var startDate = document.getElementById("start-date").value;
                var endDate = document.getElementById("end-date").value;

                // Memeriksa apakah kedua tanggal sudah dipilih
                if (!startDate || !endDate) {
                    // Menampilkan pesan SweetAlert untuk validasi
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Select the start date and end date first'
                    });
                    return; // Berhenti dari fungsi jika salah satu atau kedua tanggal belum dipilih
                }

                // Mengambil semua baris data dari tabel
                var tableRows = document.querySelectorAll("tbody tr");

                // Membuat objek workbook Excel
                var wb = XLSX.utils.book_new();
                var ws_name = "Data"; // Nama sheet Excel

                // Membuat array untuk menyimpan data
                var data = [];

                // Mengambil header dari tabel untuk judul kolom
                var tableHeader = [];
                document.querySelectorAll("thead th").forEach(function(th) {
                    tableHeader.push(th.textContent.trim());
                });
                data.push(tableHeader);

                // Melakukan iterasi melalui setiap baris tabel
                tableRows.forEach(function(row) {
                    // Mengambil tanggal due_date dari kolom 'Due Date'
                    var dueDate = row.cells[19].innerText.trim();

                    // Memeriksa apakah tanggal 'Due Date' berada dalam rentang yang dipilih
                    if (dueDate >= startDate && dueDate <= endDate) {
                        // Jika dalam rentang tanggal, tambahkan data baris ke dalam array
                        var rowData = [];
                        row.querySelectorAll('td').forEach(function(cell) {
                            rowData.push(cell.innerText.trim());
                        });
                        data.push(rowData);
                    }
                });

                // Membuat worksheet Excel dari data yang dipilih
                var ws = XLSX.utils.aoa_to_sheet(data);

                // Menambahkan header autofilter
                ws['!autofilter'] = {
                    ref: XLSX.utils.encode_range(XLSX.utils.decode_range(ws['!ref']))
                };

                // Menambahkan worksheet ke dalam workbook
                XLSX.utils.book_append_sheet(wb, ws, ws_name);

                // Membuat file Excel dari workbook
                XLSX.writeFile(wb, 'History_Claim_Complain.xlsx');
            }

            // Menentukan objek jsPDF di window
            window.jsPDF = window.jspdf.jsPDF;

            // EksportPDF
            document.addEventListener('DOMContentLoaded', function() {
                // Kode JavaScript Anda di sini akan dijalankan setelah seluruh dokumen HTML telah dimuat

                // Misalnya, Anda dapat menambahkan kode untuk menangani klik tombol export PDF di sini:
                document.querySelectorAll('.export-pdf-button').forEach(function(button) {
                    button.addEventListener('click', function() {
                        var rowId = this.getAttribute(
                            'data-row-id'); // Mendapatkan ID baris dari atribut data-row-id tombol
                        exportRowToPDF(
                            rowId); // Memanggil fungsi exportRowToPDF dengan ID baris yang diberikan
                    });
                });
            });

            function exportRowToPDF(rowId) {
                // Logika untuk mengekstrak data dari baris yang ditentukan menggunakan rowId
                var rowData = getRowDataById(rowId);

                // Logika untuk memformat rowData dan membuat dokumen PDF
                var doc = new jsPDF();

                // Menambahkan judul
                doc.setFontSize(22);
                doc.setTextColor(44, 62, 80); // Warna judul
                doc.text("History Claim & Complain", 105, 20, {
                    align: "center"
                });

                // Menambahkan garis pembatas
                doc.setLineWidth(0.5);
                doc.setDrawColor(44, 62, 80); // Warna garis pembatas
                doc.line(20, 25, 190, 25);

                // Menambahkan data ke dokumen PDF
                doc.setFontSize(12);
                doc.setTextColor(44, 62, 80); // Warna teks
                var startY = 35;
                var lineHeight = 10;
                var marginLeft = 20;
                rowData.forEach(function(data, index) {
                    doc.text(marginLeft, startY + index * lineHeight, data);
                });

                // Simpan dokumen PDF
                doc.save("invoice.pdf");
            }

            function getRowDataById(rowId) {
                console.log("Row ID:", rowId); // Tambah consol log untuk rowId
                // Logika untuk mengekstrak data dari baris yang ditentukan berdasarkan ID-nya
                // Anda dapat menggunakan metode manipulasi DOM untuk mengambil data dari baris tabel dengan ID yang diberikan
                // Contoh:
                var row = document.getElementById("row_" + rowId);
                console.log("Row Element:", row); // Tambah consol log untuk row
                var rowData = [];
                // Ekstrak data dari setiap sel dalam baris
                row.querySelectorAll('td').forEach(function(cell) {
                    rowData.push(cell.innerText.trim());
                });
                return rowData;
            }


            // imageModal
            $(document).ready(function() {
                $('.clickable-image').click(function() {
                    var imageUrl = $(this).attr('src');
                    $('#modalImage').attr('src', imageUrl);
                    $('#imageModal').modal('show');
                });
            });

            function SaveAndUpdate() {
                // Lakukan sesuatu saat tombol "Save" atau "Finish" ditekan
                // Contoh: Validasi form, kemudian kirimkan data melalui AJAX jika valid
                // Untuk contoh sederhana, saya hanya menampilkan pesan
                alert('Save or Finish button clicked');
            }

            function FinishAndUpdate() {
                // Lakukan sesuatu saat tombol "Back" ditekan
                // Contoh: Kembali ke halaman sebelumnya atau lakukan navigasi lainnya
                // Untuk contoh sederhana, saya hanya menampilkan pesan
                alert('Back button clicked');
            }
            //sweetalertSave
            function validateAndSubmit() {
                var formData = new FormData(document.getElementById('formInputHandling'));

                var no_wo = formData.get('no_wo');
                var image = formData.get('image');
                var customerName = formData.get('name_customer');
                var customerCode = formData.get('customer_id');
                var area = formData.get('area');
                var qty = formData.get('qty');
                var pcs = formData.get('pcs');
                var category = formData.get('category');
                var process_type = formData.get('process_type');
                var type_1 = formData.getAll('type_1');

                // Memeriksa apakah ada input yang kosong
                if (!no_wo || !image || !customerName || !customerCode || !area || !qty || !pcs || !category || !process_type ||
                    type_1.length === 0) {
                    // Menampilkan sweet alert error jika ada input yang kosong
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Please fill all the fields before saving.',
                    });
                } else {
                    // Simulasi validasi
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Data has been saved successfully.',
                        showConfirmButton: false
                    });
                }
            }

            //datatabelSales
            $(document).ready(function() {
                new DataTable('#viewSales');

            });

            //datatableSubmision
            $(document).ready(function() {
                new DataTable('table.display');
            });

            //COnfrimDelete
            document.addEventListener('DOMContentLoaded', function() {
                // Menggunakan event listener untuk menangkap klik pada tombol delete
                document.querySelectorAll('.delete-btn').forEach(function(button) {
                    button.addEventListener('click', function() {
                        // Menampilkan SweetAlert untuk konfirmasi penghapusan
                        Swal.fire({
                            title: 'Are you sure?',
                            text: 'You sure delete this data?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Jika pengguna menekan tombol konfirmasi pada SweetAlert,
                                // maka arahkan ke rute penghapusan
                                window.location.href = button.getAttribute(
                                    'data-url');
                            }
                        });
                    });
                });
            });

            //RefreshFromPageInputCreateHandling
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form');

                // Check if the page was accessed from the index page
                const fromIndex = document.referrer.includes("index");

                if (fromIndex) {
                    // Set initial values for form elements if coming from index page
                    resetFormValues();
                }

                const resetButton = document.querySelector('button[type="reset"]');

                resetButton.addEventListener('click', function() {
                    // Reset values to default or empty
                    resetFormValues();

                    // Hide cancel upload button and error message
                    document.getElementById('cancelUpload').style.display = 'none';
                    document.getElementById('fileError').style.display = 'none';
                });

                function resetFormValues() {
                    // Reset values to default or empty for text inputs
                    const textInputs = form.querySelectorAll('input[type="text"]');
                    textInputs.forEach(input => {
                        input.value = '';
                    });

                    // Reset selected values for dropdowns
                    const selects = form.querySelectorAll('select');
                    selects.forEach(select => {
                        select.value = '';
                    });

                    // Reset checkboxes to default state (checked or unchecked)
                    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = checkbox.defaultChecked;
                    });
                }
            });
            // readimageform
            function previewImage(event) {
                var reader = new FileReader();
                reader.onload = function() {
                    var output = document.getElementById('preview');
                    output.src = reader.result;
                };
                reader.readAsDataURL(event.target.files[0]);

                // Show the preview image div
                document.getElementById('imagePreview').style.display = 'block';
            }

            //upload fileJS
            document.addEventListener('DOMContentLoaded', function() {
                const fileInput = document.getElementById('formFile');
                const cancelUploadButton = document.getElementById('cancelUpload');
                const fileError = document.getElementById('fileError');

                fileInput.addEventListener('change', handleFileSelection);
                cancelUploadButton.addEventListener('click', cancelFileUpload);

                function handleFileSelection() {
                    const allowedFormats = ['image/jpeg', 'image/png',
                        'image/gif'
                    ]; // Add more formats if needed
                    const selectedFile = fileInput.files[0];

                    if (selectedFile) {
                        if (allowedFormats.includes(selectedFile.type)) {
                            // Valid image format
                            fileError.style.display = 'none';
                            cancelUploadButton.style.display = 'inline-block';
                        } else {
                            // Invalid image format
                            fileError.style.display = 'block';
                            cancelUploadButton.style.display = 'none';
                            resetFileInput();
                        }
                    }
                }


                function cancelFileUpload() {
                    resetFileInput();
                    fileError.style.display = 'none';
                    cancelUploadButton.style.display = 'none';

                    // Hide the preview image div
                    document.getElementById('imagePreview').style.display = 'none';
                    // Hide the cancel upload button
                    document.getElementById('cancelUpload').style.display = 'none';
                    // Clear the file input value
                    document.getElementById('formFile').value = '';
                }

                function resetFileInput() {
                    // Reset file input by cloning and replacing it
                    const newFileInput = fileInput.cloneNode(true);
                    fileInput.parentNode.replaceChild(newFileInput, fileInput);
                    newFileInput.addEventListener('change', handleFileSelection);
                }
            });

            //reset
            document.addEventListener('DOMContentLoaded', function() {
                const resetButton = document.querySelector('button[type="submit"][name="reset"]');
                const form = document.querySelector('form');

                resetButton.addEventListener('click', function() {
                    // Reset values to default or empty
                    form.reset();

                    // Reset checkboxes to default state (checked or unchecked)
                    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = checkbox.defaultChecked;
                    });

                    // Hide cancel upload button and error message
                    document.getElementById('cancelUpload').style.display = 'none';
                    document.getElementById('fileError').style.display = 'none';
                });
            });

            //backButonSales
            function goToIndex() {
                window.location.href = "{{ route('index') }}"; // Ganti 'index' dengan nama rute halaman index Anda
            }

            // searchdropdown
            // Inisialisasi Select2 pada semua dropdown dengan class "select2"
            // $(document).ready(function() {
            //     $('.select2').select2();
            // });

            //backButonDeptMan
            function goToSubmission() {
                window.location.href =
                    "{{ route('submission') }}"; // Ganti 'index' dengan nama rute halaman index Anda
            }

            // searchdropdown
            // Inisialisasi Select2 pada semua dropdown dengan class "select2"
            // $(document).ready(function() {
            //     $('.select2').select2();
            // });
    </script>
    @endif
    <style>
        #footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            padding: 10px;
            text-align: center;
            box-shadow: 0px -5px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        #footer .copyright,
        #footer .credits {
            color: #343a40;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all the accordion elements
            var accordions = document.querySelectorAll('.accordion');

            // Add click event listener to each accordion
            accordions.forEach(function(accordion) {
                // Toggle the 'show' class on collapse element when the accordion title is clicked
                accordion.querySelector('.card-title').addEventListener('click', function() {
                    accordion.querySelector('.collapse').classList.toggle('show');
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>