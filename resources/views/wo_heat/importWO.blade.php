<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Astra Daido Steel Indonesia</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="csrf-token">

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

<body>

    <!-- Buat Header -->
    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">

        <div class="d-flex align-items-center justify-content-between p-3">
            <h3 class="fw-bold mt-2 ps-3 fs-4">DMS Adasi DS8</h3>
            <i class="bi bi-list toggle-sidebar-btn mx-3 fs-2"></i>
        </div><!-- End Logo -->
        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <img src="assets/img/user.png" alt="Profile" class="rounded-circle">
                        <span class="d-none d-md-block ps-2">{{ Auth::user()->name }}
                            <br>{{ Auth::user()->roles->role }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile mt-3">
                        <li class="dropdown-header">
                            <h6>{{ Auth::user()->name }}</h6>
                            <span>{{ Auth::user()->roles->role }}</span>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        {{-- Test Akun --}}
                        <li>
                            <a class="dropdown-item d-flex align-items-center" style="color: rgb(15, 0, 97)"
                                href="{{ route('showDataDiri') }}">
                                <i class="bi bi-person me-2"></i>
                                <span>Profile</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" style="color: rgb(136, 0, 0)"
                                href="#"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

    </header><!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <aside id="sidebar" class="sidebar">
        <ul class="sidebar-nav" id="sidebar-nav">
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
                <!-- CSRF token untuk keamanan -->
            </form>

            <li class="nav-heading">Dashboard</li>
            <li class="nav-item">
            </li><!-- End Logout Nav -->
            @if (Auth::user()->role_id == 1)
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#dashboard-admin-nav">
                        <i class="bi bi-person-circle"></i>
                        <span>SP-Administrasi</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <ul id="dashboard-admin-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                        <li>
                            <a class="nav-link collapsed" href="{{ route('dashboardusers') }}">
                                <i class="bi bi-list-check fs-6"></i>
                                <span>Kelola Akun</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link collapsed" href="{{ route('dashboardcustomers') }}">
                                <i class="bi bi-list-check fs-6"></i>
                                <span>Kelola Customer</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-toggle="collapse" href="#dashboard-menu-nav">
                    <i class="bi bi-gear"></i>
                    <span>Dashboard</span>
                    <i class="bi bi-chevron-down ms-auto fs-6"></i>
                </a>
                <ul id="dashboard-menu-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="nav-link collapsed" href="{{ route('dashboardHandling') }}">
                            <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            <span>Maintenance</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="{{ route('dshandling') }}">
                            <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            <span>Handling Klaim & Komplain</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('dashboardSS') }}">
                            <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            <span>Sumbang Saran</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('reportpatrol') }}">
                            <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            <span>Safety Patrol</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Maintenance dan Handling --}}

            @if (Auth::check())
                @if (Auth::user()->role_id == 7 ||
                        Auth::user()->role_id == 8 ||
                        Auth::user()->role_id == 9 ||
                        Auth::user()->role_id == 1)
                    <li class="nav-heading">Productions</li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-toggle="collapse" href="#prod-forms-nav">
                            <i class="bi bi-journal-text fs-6"></i>
                            <span>Form Permintaan Perbaikan</span>
                            <i class="bi bi-chevron-down ms-auto fs-6"></i>
                        </a>
                        <ul id="prod-forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                            <li>
                                <a class="nav-link collapsed" href="{{ route('fpps.index') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Data Form Perbaikan</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Riwayat Form Perbaikan</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if (Auth::user()->role_id == 14)
                    <li class="nav-heading">Productions</li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-toggle="collapse" href="#prod-forms-nav">
                            <i class="bi bi-journal-text"></i>
                            <span>Form Permintaan Perbaikan</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="prod-forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                            <li>
                                <a class="nav-link collapsed" href="{{ route('sales.index') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Data Form Perbaikan</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Riwayat Form Perbaikan</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if (Auth::user()->role_id == 6 || Auth::user()->role_id == 1)
                    <li class="nav-heading">Maintenance</li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-target="#maint-korektif-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Tindakan Korektif</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="maint-korektif-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ asset('dashboardmaintenance') }}">
                                    <i class="bi bi-file-earmark-text-fill fs-6"></i>
                                    <span>Terima Form Perbaikan</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Riwayat Form Perbaikan</span>
                                </a>
                            </li>
                        </ul>
                        <a class="nav-link collapsed" data-bs-target="#maint-received-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Tindakan Preventif</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="maint-received-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('dashboardPreventiveMaintenance') }}">
                                    <i class="bi bi-check2 fs-6"></i>
                                    <span>Tabel Preventif</span>
                                </a>
                            </li>
                        </ul>
                    </li><!-- End Maint Received Nav -->
                @endif
                @if (Auth::user()->role_id == 14)
                    <li class="nav-heading">Maintenance</li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-target="#maint-received-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Tindakan Korektif</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="maint-received-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('ga.dashboardga') }}">
                                    <i class="bi bi-file-earmark-text-fill fs-6"></i>
                                    <span>Terima Form Perbaikan</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Riwayat Form Perbaikan</span>
                                </a>
                            </li>
                        </ul>
                        <a class="nav-link collapsed" data-bs-target="#maint-received-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Tindakan Preventif</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="maint-received-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('dashboardPreventiveMaintenanceGA') }}">
                                    <i class="bi bi-check2 fs-6"></i>
                                    <span>Tindakan Preventif</span>
                                </a>
                            </li>
                        </ul>
                    </li><!-- End Maint Received Nav -->
                @endif
                <!-- End Prod Forms Nav -->
                @if (Auth::user()->role_id == 5 || Auth::user()->role_id == 1)
                    {{-- Role ID untuk Maintenance --}}
                    {{-- Tampilkan sidebar untuk Maintenance --}}
                    <li class="nav-heading">Engineering</li>

                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-target="#dept-maint-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Bag. Maintenance</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="dept-maint-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('dashboardmesins') }}">
                                    <i class="bi bi-gear fs-6"></i>
                                    <span>Kelola DMI</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('deptmtce.index') }}">
                                    <i class="bi bi-check2 fs-6"></i>
                                    <span>Data Approved FPP</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Riwayat Form Perbaikan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('dashboardPreventive') }}">
                                    <i class="bi bi-check2 fs-6"></i>
                                    <span>Tabel Preventif</span>
                                </a>
                            </li>
                        </ul>
                    </li><!-- End Dept Maint Nav -->
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-target="#dept-complain-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Bag. Engineering</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="dept-complain-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li>
                                <a href="{{ route('submission') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Form Tindak Lanjut</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('showHistoryCLaimComplain') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Riwayat Klaim & Komplain</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('scheduleVisit') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Jadwal Kunjungan</span>
                                </a>
                            </li>
                        </ul>
                    </li><!-- End Dept Complain & Claim Nav -->
                @endif
                @if (Auth::user()->role_id == 14)
                    {{-- Role ID untuk GA --}}
                    {{-- Tampilkan sidebar untuk Engineering --}}
                    <li class="nav-heading">Engineering</li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-target="#dept-maint-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text fs-6"></i><span>Bag. Maintenance</span>
                        </a>
                        <ul id="dept-maint-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('dashboardgamesin') }}">
                                    <i class="bi bi-gear fs-6"></i>
                                    <span>Data Mesin</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('ga.approvedfpp') }}">
                                    <i class="bi bi-check2 fs-6"></i>
                                    <span>Data Approved FPP</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Riwayat FPP</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('dashboardPreventiveMaintenanceGA') }}">
                                    <i class="bi bi-check2 fs-6"></i>
                                    <span>Tabel Preventif</span>
                                </a>
                            </li>
                        </ul>
                    </li><!-- End Dept Maint Nav -->
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-target="#dept-complain-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Bag. Engineering</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="dept-complain-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li>
                                <a href="{{ route('submission') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Form Tindak Lanjut</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('showHistoryCLaimComplain') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Riwayat Klaim & Komplain</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('scheduleVisit') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Jadwal Kunjungan</span>
                                </a>
                            </li>
                        </ul>
                    </li><!-- End Dept Complain & Claim Nav -->
                @endif
                @if (Auth::user()->role_id == 1 ||
                        Auth::user()->role_id == 2 ||
                        Auth::user()->role_id == 3 ||
                        Auth::user()->role_id == 4 ||
                        Auth::user()->role_id == 11 ||
                        Auth::user()->role_id == 12 ||
                        Auth::user()->role_id == 13 ||
                        Auth::user()->role_id == 14)
                    {{-- Role ID untuk Sales --}}
                    <li class="nav-heading">Sales</li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-target="#sales-fpp-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Form Permintaan Perbaikan</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="sales-fpp-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                            <li class="nav-item">
                                <a class="nav-link collapsed" href="{{ route('sales.index') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Data Form Perbaikan</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link collapsed" href="{{ route('sales.history') }}">
                                    <i class="bi bi-list-check fs-6"></i>
                                    <span>Riwayat Form Perbaikan</span>
                                </a>
                            </li>
                        </ul>

                        <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Handling Klaim dan Komplain</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                            <li>
                                <a href="{{ route('index') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Form Pengajuan Klaim dan
                                        Komplain</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('showHistoryCLaimComplain') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Riwayat Klaim dan Komplain</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('scheduleVisit') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Jadwal Kunjungan</span>
                                </a>
                            </li>
                        </ul>
                        <a class="nav-link collapsed" data-bs-target="#forms-nav-inquiry" data-bs-toggle="collapse"
                            href="#">
                            <i class="bi bi-journal-text"></i><span>Inquiry Sales</span><i
                                class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <ul id="forms-nav-inquiry" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                            <li>
                                <a href="{{ route('createinquiry') }}">
                                    <i class="bi bi-list-check fs-6"></i><span>Buat Inquiry Sales</span>
                                </a>
                            </li>
                            <li>
                                <a href="#">
                                    <i class="bi bi-list-check fs-6"></i><span>Approve Inquiry</span>
                                </a>
                            </li>
                        </ul>
                    </li><!-- End Forms Nav -->
                @endif
            @endif

            {{-- SS, Safety Patrol dan Trace WO --}}
            <li class="nav-heading">Suggestion System</li>
            {{-- Form Sumbang Saran --}}
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#nav-ss" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-layout-text-window-reverse"></i><span>Form Sumbang Saran</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="nav-ss" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('showSS') }}">
                            <i class="bi bi-journal-text fs-6"></i>
                            <span>Buat Form</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('forumSS') }}">
                            <i class="bi bi-chat-square-dots-fill fs-6"></i>
                            <span>Overview Sumbang Saran</span>
                        </a>
                    </li>
                </ul>
                @if (Auth::user()->role_id == 1 ||
                        Auth::user()->role_id == 2 ||
                        Auth::user()->role_id == 3 ||
                        Auth::user()->role_id == 5 ||
                        Auth::user()->role_id == 7 ||
                        Auth::user()->role_id == 9 ||
                        Auth::user()->role_id == 11 ||
                        Auth::user()->role_id == 14 ||
                        Auth::user()->role_id == 21 ||
                        Auth::user()->role_id == 22 ||
                        Auth::user()->role_id == 12 ||
                        Auth::user()->role_id == 14 ||
                        Auth::user()->role_id == 20)
                    <a class="nav-link collapsed" data-bs-target="#nav-conf" data-bs-toggle="collapse"
                        href="#">
                        <i class="bi bi-layout-wtf"></i><span>Persetujuan</span><i
                            class="bi bi-chevron-down ms-auto"></i>
                    </a>
                @endif
                <ul id="nav-conf" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    @if (Auth::user()->role_id == 1 ||
                            Auth::user()->role_id == 2 ||
                            Auth::user()->role_id == 3 ||
                            Auth::user()->role_id == 7 ||
                            Auth::user()->role_id == 9 ||
                            Auth::user()->role_id == 21 ||
                            Auth::user()->role_id == 22 ||
                            Auth::user()->role_id == 12 ||
                            Auth::user()->role_id == 14 ||
                            Auth::user()->role_id == 20)
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="{{ route('showKonfirmasiForeman') }}">
                                <i class="bi bi-kanban fs-6"></i>
                                <span>By Sect. Head</span>
                            </a>
                        </li>
                    @endif
                    @if (Auth::user()->role_id == 1 ||
                            Auth::user()->role_id == 2 ||
                            Auth::user()->role_id == 5 ||
                            Auth::user()->role_id == 7 ||
                            Auth::user()->role_id == 11 ||
                            Auth::user()->role_id == 14 ||
                            Auth::user()->role_id == 20)
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="{{ route('showKonfirmasiDeptHead') }}">
                                <i class="bi bi-kanban-fill fs-6"></i>
                                <span>By Dept. Head</span>
                            </a>
                        </li>
                    @endif
                </ul>
                @if (Auth::user()->role_id == 1 ||
                        Auth::user()->role_id == 5 ||
                        Auth::user()->role_id == 14 ||
                        Auth::user()->role_id == 15 ||
                        Auth::user()->role_id == 20)
                    <a class="nav-link collapsed" data-bs-target="#nav-pic" data-bs-toggle="collapse"
                        href="#">
                        <i class="bi bi-layout-wtf"></i><span>PIC Penilaian</span><i
                            class="bi bi-chevron-down ms-auto"></i>
                    </a>
                @endif
                <ul id="nav-pic" class="nav-content collapse " data-bs-parent="#sidebar-nav">

                    @if (Auth::user()->role_id == 1 ||
                            Auth::user()->role_id == 5 ||
                            Auth::user()->role_id == 14 ||
                            Auth::user()->role_id == 20)
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="{{ route('showKonfirmasiKomite') }}">
                                <i class="bi-person-lines-fill fs-6"></i>
                                <span>PIC Penilai SS | Komite</span>
                            </a>
                        </li>
                    @endif
                    @if (Auth::user()->role_id == 1 ||
                            Auth::user()->role_id == 14 ||
                            Auth::user()->role_id == 15 ||
                            Auth::user()->role_id == 20)
                        <li class="nav-item">
                            <a class="nav-link collapsed" href="{{ route('showKonfirmasiHRGA') }}">
                                <i class="bi-person-lines-fill fs-6"></i>
                                <span>PIC Penilai SS | HRGA</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li><!-- End Form Sumbang Saran -->
            {{-- <li class="nav-heading">Suggestion System</li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#sales-fpp-nav" data-bs-toggle="collapse"
                    href="#">
                    <i class="bi bi-journal-text"></i><span>Kelola SS</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="ss-fpp-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('forumSS') }}">
                            <i class="bi bi-chat-square-dots-fill"></i>
                            <span>Forum SS</span>
                        </a>
                    </li><!-- End Profile Page Nav -->
                    <li>
                        <a class="nav-link collapsed" href="{{ route('fpps.history') }}">
                            <i class="bi bi-list-check fs-6"></i>
                            <span>Riwayat FPP</span>
                        </a>
                    </li>
                </ul>

                <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse"
                    href="#">
                    <i class="bi bi-journal-text"></i><span>Handling Klaim dan Komplain</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="forms-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('index') }}">
                            <i class="bi bi-list-check fs-6"></i><span>Form Pengajuan Klaim dan
                                Komplain</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('showHistoryCLaimComplain') }}">
                            <i class="bi bi-list-check fs-6"></i><span>Riwayat Klaim dan Komplain</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('scheduleVisit') }}">
                            <i class="bi bi-list-check fs-6"></i><span>Jadwal Kunjungan</span>
                        </a>
                    </li>
                </ul>
            </li><!-- End Forms Nav -->
           
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('showSS') }}">
                    <i class="bi bi-journal-text fs-6"></i>
                    <span>Form SS</span>
                </a>
            </li><!-- End Profile Page Nav -->
            @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2 || Auth::user()->role_id == 3 || Auth::user()->role_id == 7 || Auth::user()->role_id == 9 || Auth::user()->role_id == 21 || Auth::user()->role_id == 22 || Auth::user()->role_id == 12 || Auth::user()->role_id == 14 || Auth::user()->role_id == 20)
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('showKonfirmasiForeman') }}">
                        <i class="bi bi-clipboard-check-fill"></i>
                        <span>Konfirmasi SS by Sec. Head</span>
                    </a>
                </li><!-- End Profile Page Nav -->
            @endif
            @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2 || Auth::user()->role_id == 5 || Auth::user()->role_id == 7 || Auth::user()->role_id == 11 || Auth::user()->role_id == 14 || Auth::user()->role_id == 20)
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('showKonfirmasiDeptHead') }}">
                        <i class="bi bi-clipboard-check-fill"></i>
                        <span>Konfirmasi SS by Dept. Head</span>
                    </a>
                </li><!-- End Profile Page Nav -->
            @endif
            @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 5 || Auth::user()->role_id == 14 || Auth::user()->role_id == 20)
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('showKonfirmasiKomite') }}">
                        <i class="bi-person-lines-fill"></i>
                        <span>PIC Penilai SS | Komite</span>
                    </a>
                </li><!-- End Profile Page Nav -->
            @endif
            @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 14 || Auth::user()->role_id == 15 || Auth::user()->role_id == 20)
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('showKonfirmasiHRGA') }}">
                        <i class="bi-person-lines-fill"></i>
                        <span>PIC Penilai SS | HRGA</span>
                    </a>
                </li><!-- End Profile Page Nav -->
            @endif --}}
            @if (Auth::user()->role_id == 1)
                <li class="nav-heading">Safety Patrol</li>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('listpatrol') }}">
                        <i class="bi bi-person"></i>
                        <span>Form Safety Patrol</span>
                    </a>
                </li><!-- End Profile Page Nav -->
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('listpatrolpic') }}">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Data Safety Patrol</span>
                    </a>
                </li><!-- End Profile Page Nav -->
                {{-- <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('reportpatrol') }}">
                    <i class="bi bi-bar-chart-steps"></i>
                    <span>Report Form Safety Patrol</span>
                </a>
            </li><!-- End Profile Page Nav --> --}}
                {{-- Menu Inventory-PPC --}}
                <li class="nav-heading">PPIC</li>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#">
                        <i class="bi bi-cloud-upload"></i>
                        <span>Validasi Sales</span>
                    </a>
                </li><!-- End Profile Page Nav -->
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#">
                        <i class="bi bi-search"></i>
                        <span>Approval Sales</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#">
                        <i class="bi bi-search"></i>
                        <span>Incoming Shipment</span>
                    </a>
                </li>
                {{-- @endif --}}
            @endif
            @if (Auth::user()->role_id == 1 ||
                    Auth::user()->role_id == 5 ||
                    Auth::user()->role_id == 14 ||
                    Auth::user()->role_id == 22 ||
                    Auth::user()->role_id == 26)
                <li class="nav-heading">WO Heat Treatment</li>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('dashboardImportWO') }}">
                        <i class="bi bi-cloud-upload"></i>
                        <span>Import WO</span>
                    </a>
                </li><!-- End Profile Page Nav -->
            @endif
            @if (Auth::user()->role_id == 1 ||
                    Auth::user()->role_id == 5 ||
                    Auth::user()->role_id == 14 ||
                    Auth::user()->role_id == 22 ||
                    Auth::user()->role_id == 26 ||
                    Auth::user()->role_id == 2 ||
                    Auth::user()->role_id == 3 ||
                    Auth::user()->role_id == 4 ||
                    Auth::user()->role_id == 28 ||
                    Auth::user()->role_id == 30)
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('dashboardTracingWO') }}">
                        <i class="bi bi-search"></i>
                        <span>Tracing WO</span>
                    </a>
                </li>
                {{-- <hr> --}}
            @endif
        </ul>
        </ul>

        <!-- Footer Sidebar -->
        <ul class="sidebar-nav fixed-bottom ps-3">
        </ul>
        <!-- End Footer Sidebar -->
    </aside><!-- End Sidebar-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.20.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0"></script>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Karyawan</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">WO Heat Treatment</li>
                    <li class="breadcrumb-item active">Import WO</li>
                </ol>
            </nav>

        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-center">Import WO Heat Treatment</h5>

                            <form id="importForm" action="{{ route('importWO') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <input type="file" name="excelFile" class="form-control" required>
                                </div>
                                <button type="button" class="btn btn-danger mt-3" id="submitBtn">
                                    <i class="bi bi-upload"></i> Import Data
                                </button>
                            </form>


                            @if (isset($data))
                                <!-- Table for imported data -->
                                <div class="table-responsive">
                                    <table class="datatables datatable" style="table-layout: responsive;">
                                        <thead>
                                            <tr>
                                                @foreach ($data[0] as $cell)
                                                    <th scope="col">{{ $cell['value'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data as $rowIndex => $row)
                                                @if ($rowIndex > 0)
                                                    <tr>
                                                        @foreach ($row as $cell)
                                                            <td colspan="{{ $cell['colspan'] }}"
                                                                rowspan="{{ $cell['rowspan'] }}">
                                                                {{ $cell['value'] }}
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            <!-- Table for heat treatments -->
                            <div class="table-responsive table-responsive-sm">
                                <table class="datatables datatable"
                                    style="table-layout: responsive; white-space: nowrap; overflow-x: auto;">
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">No.WO</th>
                                            <th scope="col">No.SO</th>
                                            <th scope="col">Tgl. WO</th>
                                            <th scope="col">Deskripsi</th>
                                            <th scope="col">Area</th>
                                            <th scope="col">Kode</th>
                                            <th scope="col">Customer</th>
                                            <th scope="col">Proses</th>
                                            <th scope="col">Pcs</th>
                                            <th scope="col">Kg</th>
                                            <th scope="col">Status WO</th>
                                            <th scope="col">No.DO</th>
                                            <th scope="col">Status DO</th>
                                            <th scope="col">Tgl.ST</th>
                                            <th scope="col">Supir</th>
                                            <th scope="col">Penerima</th>
                                            <th scope="col">Tgl.Terima</th>
                                            <th scope="col">Modifikasi Terakhir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($heattreatments as $index => $data)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $data->no_wo }}</td>
                                                <td>{{ $data->no_so }}</td>
                                                <td>{{ $data->tgl_wo }}</td>
                                                <td>{{ $data->deskripsi }}</td>
                                                <td>{{ $data->area }}</td>
                                                <td>{{ $data->kode }}</td>
                                                <td>{{ $data->cust }}</td>
                                                <td>{{ $data->proses }}</td>
                                                <td>{{ $data->pcs }}</td>
                                                <td>{{ $data->kg }}</td>
                                                <td>{{ $data->status_wo }}</td>
                                                <td>{{ $data->no_do }}</td>
                                                <td>{{ $data->status_do }}</td>
                                                <td>{{ $data->tgl_st }}</td>
                                                <td>{{ $data->supir }}</td>
                                                <td>{{ $data->penerima }}</td>
                                                <td>{{ $data->tgl_terima }}</td>
                                                <td>{{ $data->updated_at }}</td>
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
    </main>
    <!-- ======= Footer ======= -->
    <footer id="footer" class="footer">
        <div class="copyright">
            &copy; Copyright <strong><span>ADASI</span></strong>. All Rights Reserved
        </div>
    </footer><!-- End Footer -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $('#submitBtn').on('click', function(event) {
            event.preventDefault(); // Prevent default form submission

            let formData = new FormData($('#importForm')[0]); // Mengambil form data dari form

            // Show the SweetAlert loading with progress bar and percentage
            Swal.fire({
                title: "Importing WO...",
                html: '<div id="progress-container" style="margin-top: 20px;"><div id="progress-bar" style="width: 0%; height: 20px; background-color: #3085d6;"></div><div id="progress-percent" style="margin-top: 10px;">0%</div></div><br>Please wait while we process your data.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('importWO') }}", // Mengarahkan ke route yang mendukung POST
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();

                    // Upload progress
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total * 100;
                            updateProgressBar(percentComplete);
                        }
                    }, false);

                    return xhr;
                },
                success: function(response) {
                    updateProgressBar(100);

                    if (response.success) {
                        setTimeout(function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href =
                                    "{{ route('dashboardImportWO') }}"; // Redirect ke dashboardImportWO setelah sukses
                            });
                        }, 500); // Small delay to show 100% completion
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close(); // Close the loading alert
                    let errorMessage = xhr.status + ': ' + xhr.statusText;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error - ' + errorMessage
                    });
                }
            });

            let currentPercent = 0;

            function updateProgressBar(percentComplete) {
                let interval = setInterval(function() {
                    if (currentPercent >= percentComplete) {
                        clearInterval(interval);
                    } else {
                        currentPercent++;
                        $('#progress-bar').css('width', currentPercent + '%');
                        $('#progress-percent').text(currentPercent + '%');
                    }
                }, 10); // Update interval for smooth progress
            }
        });
    </script>

</body>

</html>
