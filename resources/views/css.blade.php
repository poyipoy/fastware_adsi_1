@yield('css');

<!-- Favicons -->
<link href="{{ asset('assets/img/logo-menu.png') }}" rel="icon">
<link href="{{ asset('assets/img/logo-menu.png') }}" rel="apple-touch-icon">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

<!-- Google Fonts -->
<link href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

<!-- Vendor CSS Files -->
<link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">

<!-- Template Main CSS File -->
<link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">


 {{-- datatable --}}
 <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

 {{-- sweet alert --}}
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@10">
 <!-- *Note: You must have internet connection on your laptop or pc other wise below code is not working -->
 <!-- CSS for full calender -->
 <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.css" rel="stylesheet" />

 {{-- DatePicker --}}
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
 {{-- Excel --}}
 <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
 {{-- PDF --}}
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
 {{-- ChartJS --}}
 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 {{-- Select Search --}}
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/js/standalone/selectize.min.js" integrity="sha256-+C0A5Ilqmu4QcSPxrlGpaZxJ04VjsRjKu+G82kl5UJk=" crossorigin="anonymous"></script>
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css" integrity="sha256-ze/OEYGcFbPRmvCnrSeKbRTtjG4vGLHXgOqsyLFTRjg=" crossorigin="anonymous" />


<!-- Buat Header -->
<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
        <a href="" class="logo d-flex align-items-center">
            <img src="assets/img/AdasiLogo.png" style="margin-left: 60px" alt="">
            <span class="d-none d-lg-block"></span>
        </a>
        <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->
    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">
            <li class="nav-item d-block d-lg-none">
                <a class="nav-link nav-icon search-bar-toggle " href="#">
                    <i class="bi bi-search"></i>
                </a>
            </li><!-- End Search Icon-->

            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <img src="assets/img/user.png" alt="Profile" class="rounded-circle">
                    <span class="d-none d-md-block ps-2">{{ Auth::user()->name }} <br>
                        {{ Auth::user()->roles->role }}</span>
                </a><!-- End Profile Image Icon -->
            </li><!-- End Profile Nav -->
        </ul>
    </nav><!-- End Icons Navigation -->

</header><!-- End Header -->

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
            <!-- CSRF token untuk keamanan -->
        </form>
        <b>
            <li class="nav-label">DMS Menu</h5>
        </b>
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li><!-- End Logout Nav -->
        @if (Auth::user()->role_id == 1)
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#dashboard-admin-nav">
                <i class="bi bi-person-circle"></i>
                <span>Dashboard Admin</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="dashboard-admin-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a class="nav-link collapsed" href="{{ route('dashboardusers') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Daftar Pengguna</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link collapsed" href="{{ route('dashboardcustomers') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Daftar Pelanggan</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#dashboard-menu-nav">
                <i class="bi bi-gear"></i>
                <span>Dashboard Menu</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="dashboard-menu-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a class="nav-link collapsed" href="{{ route('dashboardHandling') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Dashboard DMS ADASI</span>
                    </a>
                </li>
            </ul>
        </li>
        @if (Auth::check())
        @if (Auth::user()->role_id == 7 || Auth::user()->role_id == 8 ||Auth::user()->role_id == 9 || Auth::user()->role_id == 1)
        <li class="nav-label">Production</li>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#prod-forms-nav">
                <i class="bi bi-journal-text"></i>
                <span>Form Permintaan Perbaikan</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="prod-forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a class="nav-link collapsed" href="{{ route('fpps.index') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Data Form FPP</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Riwayat FPP</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif
        @if (Auth::user()->role_id == 14)
        <li class="nav-label">Production</li>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#prod-forms-nav">
                <i class="bi bi-journal-text"></i>
                <span>Form Permintaan Perbaikan</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="prod-forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a class="nav-link collapsed" href="{{ route('sales.index') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Data Form FPP</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Riwayat FPP</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif
        @if (Auth::user()->role_id == 6 || Auth::user()->role_id == 1)
        <li class="nav-label">Maintenance</li>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#maint-received-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-journal-text"></i><span>Received FPP & Jadwal Preventive</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="maint-received-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ asset('dashboardmaintenance') }}">
                        <i class="bi bi-file-earmark-text-fill"></i>
                        <span>Data Received FPP</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Riwayat FPP</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('dashboardPreventiveMaintenance') }}">
                        <i class="bi bi-check2"></i>
                        <span>Tabel Preventif</span>
                    </a>
                </li>
            </ul>
        </li><!-- End Maint Received Nav -->
        @endif
        @if (Auth::user()->role_id == 14)
        <li class="nav-label">Maintenance</li>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#maint-received-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-journal-text"></i><span>Received FPP & Jadwal Preventive</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="maint-received-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('ga.dashboardga') }}">
                        <i class="bi bi-file-earmark-text-fill"></i>
                        <span>Data Received FPP</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                        <i class="bi bi-list-check"></i>
                        <span>Riwayat FPP</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('dashboardPreventiveMaintenanceGA') }}">
                        <i class="bi bi-check2"></i>
                        <span>Tabel Preventif</span>
                    </a>
                </li>
            </ul>
        </li><!-- End Maint Received Nav -->
        @endif
        <!-- End Prod Forms Nav -->
        <ul class="sidebar-nav">
            @if (Auth::user()->role_id == 5 || Auth::user()->role_id == 1)
            {{-- Role ID untuk Maintenance --}}
            {{-- Tampilkan sidebar untuk Maintenance --}}
            <li class="nav-label">Engineering</li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#dept-maint-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>Mesin & Approve FPP</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="dept-maint-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('dashboardmesins') }}">
                            <i class="bi bi-gear"></i>
                            <span>Data Mesin</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('deptmtce.index') }}">
                            <i class="bi bi-check2"></i>
                            <span>Data Approved FPP</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                            <i class="bi bi-list-check"></i>
                            <span>Riwayat FPP</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('dashboardPreventive') }}">
                            <i class="bi bi-check2"></i>
                            <span>Tabel Preventif</span>
                        </a>
                    </li>
                </ul>
            </li><!-- End Dept Maint Nav -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#dept-complain-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>Handling Klaim dan Komplain</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="dept-complain-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('submission') }}">
                            <i class="bi bi-circle"></i><span>Form Tindak Lanjut</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('showHistoryCLaimComplain') }}">
                            <i class="bi bi-circle"></i><span>Riwayat Klaim & Komplain</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('scheduleVisit') }}">
                            <i class="bi bi-circle"></i><span>Jadwal Kunjungan</span>
                        </a>
                    </li>
                </ul>
            </li><!-- End Dept Complain & Claim Nav -->
            @endif
            @if (Auth::user()->role_id == 14)
            {{-- Role ID untuk GA --}}
            {{-- Tampilkan sidebar untuk Engineering --}}
            <li class="nav-label">Engineering</li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#dept-maint-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>Mesin & Approve FPP</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="dept-maint-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('dashboardgamesin') }}">
                            <i class="bi bi-gear"></i>
                            <span>Data Mesin</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('ga.approvedfpp') }}">
                            <i class="bi bi-check2"></i>
                            <span>Data Approved FPP</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                            <i class="bi bi-list-check"></i>
                            <span>Riwayat FPP</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('dashboardPreventiveMaintenanceGA') }}">
                            <i class="bi bi-check2"></i>
                            <span>Tabel Preventif</span>
                        </a>
                    </li>
                </ul>
            </li><!-- End Dept Maint Nav -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#dept-complain-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>Handling Klaim dan Komplain</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="dept-complain-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('submission') }}">
                            <i class="bi bi-circle"></i><span>Form Tindak Lanjut</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('showHistoryCLaimComplain') }}">
                            <i class="bi bi-circle"></i><span>Riwayat Klaim & Komplain</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('scheduleVisit') }}">
                            <i class="bi bi-circle"></i><span>Jadwal Kunjungan</span>
                        </a>
                    </li>
                </ul>
            </li><!-- End Dept Complain & Claim Nav -->
            @endif
            @if (Auth::user()->role_id == 1 ||Auth::user()->role_id == 2 || Auth::user()->role_id == 3 || Auth::user()->role_id == 4 || Auth::user()->role_id == 11 || Auth::user()->role_id == 12 || Auth::user()->role_id == 13 || Auth::user()->role_id == 14)
            {{-- Role ID untuk Sales --}}
            <li class="nav-label">Sales</li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#sales-fpp-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>Form Permintaan Perbaikan</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="sales-fpp-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('sales.index') }}">
                            <i class="bi bi-list-check"></i>
                            <span>Data Form FPP</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                            <i class="bi bi-list-check"></i>
                            <span>Riwayat FPP</span>
                        </a>
                    </li>
                </ul>
            </li><!-- End Sales FPP Nav -->
            <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-journal-text"></i><span>Handling Klaim dan Komplain</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('index') }}">
                        <i class="bi bi-circle"></i><span>Form Pengajuan Klaim dan Komplain</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('showHistoryCLaimComplain') }}">
                        <i class="bi bi-circle"></i><span>Riwayat Klaim dan Komplain</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('scheduleVisit') }}">
                        <i class="bi bi-circle"></i><span>Jadwal Kunjungan</span>
                    </a>
                </li>
            </ul>
            </li><!-- End Forms Nav -->
            @endif
            @endif
        </ul>
    </ul>
    <!-- Footer Sidebar -->
    <ul class="sidebar-nav fixed-bottom ps-3">

    </ul>
    <!-- End Footer Sidebar -->
</aside><!-- End Sidebar-->

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
