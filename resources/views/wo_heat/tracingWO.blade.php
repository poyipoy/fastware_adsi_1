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
                    <li class="breadcrumb-item active">Tracing WO</li>
                </ol>
            </nav>

        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div>
                        <div>
                            <label>From:</label>
                            <input type="date" id="fromDate">
                            <label>To:</label>
                            <input type="date" id="toDate">
                            <button onclick="updateCards()">Filter</button>
                        </div>
                    </div>
                    <div class="row mt-3" id="woCards">
                        <!-- Cards will be dynamically updated here -->
                        @foreach (['Draft', 'Ready', 'Finished', 'Cancelled'] as $status)
                            <div class="col-xxl-3 col-md-6 mb-4">
                                <div class="card info-card sales-card">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $status }}</h5>
                                        <div class="d-flex align-items-center">
                                            <div class="ps-3">
                                                <h6>WO: <span
                                                        id="{{ $status }}-wo">{{ $counts[$status]['wo'] ?? '0' }}</span>
                                                </h6>
                                                <h6>PCS: <span
                                                        id="{{ $status }}-pcs">{{ $counts[$status]['pcs'] ?? '0' }}</span>
                                                </h6>
                                                <h6>KG: <span
                                                        id="{{ $status }}-kg">{{ $counts[$status]['kg'] ?? '0' }}</span>
                                                </h6>
                                                <h6>Persentase: <span
                                                        id="{{ $status }}-percentage">{{ $counts[$status]['percentage'] ?? '0' }}%</span>
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-center">Trace By Customer</h5>

                            <!-- No. WO Search Textbox -->
                            <div class="form-group d-flex justify-content-between">
                                <div class="p-2 flex-fill">
                                    <label for="searchWO">Nama Customer</label>
                                    <input type="text" class="form-control" id="searchWO"
                                        placeholder="Cari Nama Customer....">
                                </div>
                                <div class="p-2 flex-fill">
                                    <label for="searchKodeOrNoWO">Kode / No. WO</label>
                                    <input type="text" class="form-control" id="searchKodeOrNoWO"
                                        placeholder="Cari Kode atau No. WO....">
                                </div>
                                <div class="p-2 flex-fill">
                                    <label for="searchStatusWO">Status WO</label>
                                    <select class="form-control" id="searchStatusWO">
                                        <option value="">All</option>
                                        <option value="Cancelled">Cancelled</option>
                                        <option value="Draft">Draft</option>
                                        <option value="Finished">Finished</option>
                                        <option value="Ready">Ready</option>
                                    </select>
                                </div>
                                <div class="p-2 flex-fill">
                                    <label for="searchStatusDO">Status DO</label>
                                    <select class="form-control" id="searchStatusDO">
                                        <option value="">All</option>
                                        <option value="Cancelled">Cancelled</option>
                                        <option value="Delivered">Delivered</option>
                                        <option value="Ready to Deliver">Ready to Deliver</option>
                                        <option value="Waiting Availability">Waiting Availability</option>
                                    </select>
                                </div>
                                <!-- Month Dropdowns -->
                                <div class="form-group d-flex justify-content-between">
                                    <!-- Other filters remain unchanged -->
                                    <div class="p-2 flex-fill">
                                        <label for="startMonth">Bulan Mulai</label>
                                        <select class="form-control" id="startMonth">
                                            <option value="All">Pilih Bulan</option>
                                            <option value="01">Januari</option>
                                            <option value="02">Februari</option>
                                            <option value="03">Maret</option>
                                            <option value="04">April</option>
                                            <option value="05">Mei</option>
                                            <option value="06">Juni</option>
                                            <option value="07">Juli</option>
                                            <option value="08">Agustus</option>
                                            <option value="09">September</option>
                                            <option value="10">Oktober</option>
                                            <option value="11">November</option>
                                            <option value="12">Desember</option>
                                        </select>
                                    </div>
                                    <div class="p-2 flex-fill">
                                        <label for="endMonth">Bulan Akhir</label>
                                        <select class="form-control" id="endMonth">
                                            <option value="All">Pilih Bulan</option>
                                            <option value="01">Januari</option>
                                            <option value="02">Februari</option>
                                            <option value="03">Maret</option>
                                            <option value="04">April</option>
                                            <option value="05">Mei</option>
                                            <option value="06">Juni</option>
                                            <option value="07">Juli</option>
                                            <option value="08">Agustus</option>
                                            <option value="09">September</option>
                                            <option value="10">Oktober</option>
                                            <option value="11">November</option>
                                            <option value="12">Desember</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <br>

                            <div id="tableContainer"></div> <!-- Kontainer untuk tabel -->

                            <hr>

                            <!-- Detail Status Proses -->
                            <h5 class="card-title text-center">Detail Status Proses</h5>

                            <!-- Table 2 (Detail Status Proses) -->
                            <div class="table-responsive">
                                <table class="table" id="table2">
                                    <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th scope="col">HEATING</th>
                                            <th scope="col">TEMPER 1</th>
                                            <th scope="col">TEMPER 2</th>
                                            <th scope="col">TEMPER 3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Batch</td>
                                            <td id="batchHeating" class="clickable-cell" data-type="heating"></td>
                                            <td id="batchTemper1" class="clickable-cell" data-type="temper1"></td>
                                            <td id="batchTemper2" class="clickable-cell" data-type="temper2"></td>
                                            <td id="batchTemper3" class="clickable-cell" data-type="temper3"></td>
                                        </tr>
                                        <tr>
                                            <td>Mesin</td>
                                            <td id="mesinHeating"></td>
                                            <td id="mesinTemper1"></td>
                                            <td id="mesinTemper2"></td>
                                            <td id="mesinTemper3"></td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal</td>
                                            <td id="tanggalHeating"></td>
                                            <td id="tanggalTemper1"></td>
                                            <td id="tanggalTemper2"></td>
                                            <td id="tanggalTemper3"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <hr>

                            <!-- Detail Status DO -->
                            <h5 class="card-title text-center">Detail Status DO</h5>

                            <!-- Table 3 (Detail Status DO) -->
                            <div class="table-responsive">
                                <table class="table" id="table3">
                                    <thead></thead>
                                    <tbody>
                                        <!-- Tambah Disini-->
                                        <tr>
                                            <td>No. WO</td>
                                            <td id="detailNoWO"></td>
                                        </tr>
                                        <tr>
                                            <td>No. DO</td>
                                            <td id="detailNoDO"></td>
                                        </tr>
                                        <tr>
                                            <td>Tgl. ST</td>
                                            <td id="detailTglST"></td>
                                        </tr>
                                        <tr>
                                            <td>Pengirim</td>
                                            <td id="detailSupir"></td>
                                        </tr>
                                        <tr>
                                            <td>Tgl. Terima</td>
                                            <td id="detailTglTerima"></td>
                                        </tr>
                                        <tr>
                                            <td>Penerima</td>
                                            <td id="detailPenerima"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-center">Detail Proses</h5>
                            <!-- Date filters -->
                            {{-- <div class="form-group row">
                                    <label for="startDate" class="col-sm-2 col-form-label">Start Date</label>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control" id="startDate" placeholder="dd-mm">
                                    </div>
                                    <label for="endDate" class="col-sm-2 col-form-label">End Date</label>
                                    <div class="col-sm-4">
                                        <input type="text" class="form-control" id="endDate" placeholder="dd-mm">
                                    </div>
                                </div> --}}
                            <br>
                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table" id="detailProsesTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">No.</th>
                                            <th scope="col">WO</th>
                                            <th scope="col">Nama Customer</th>
                                            <th scope="col">Mesin</th>
                                            <th scope="col">Deskripsi</th>
                                            <th scope="col">PCS</th>
                                            <th scope="col">Tonase (QTY)</th>
                                            <th scope="col">Tgl. WO</th>
                                            <th scope="col">Status WO</th>
                                            <th scope="col">Status DO</th>
                                            <th scope="col">Proses</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be populated here -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="5" class="text-right">Total:</th>
                                            <th id="grandTotalPcs">0</th>
                                            <th id="grandTotalTonase">0</th>
                                            <th colspan="4"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
        <script>
            //Filter Tracing WO
            function updateCards() {
                const fromDateInput = document.getElementById('fromDate').value;
                const toDateInput = document.getElementById('toDate').value;

                // Format dates from YYYY-MM-DD to DD-MM-YY
                const fromDate = formatDate(fromDateInput);
                const toDate = formatDate(toDateInput);

                $.ajax({
                    url: `{{ url('filter-wo') }}`, // Ensure this route URL is correct
                    type: 'GET',
                    data: {
                        fromDate: fromDate,
                        toDate: toDate
                    },
                    success: function(data) {
                        const counts = data.counts;

                        // Update each card with the returned counts
                        for (let status in counts) {
                            // Update text content for WO, PCS, KG
                            document.getElementById(`${status}-wo`).textContent = counts[status]['wo'];
                            document.getElementById(`${status}-pcs`).textContent = counts[status]['pcs'];
                            document.getElementById(`${status}-kg`).textContent = counts[status]['kg'];

                            // Update percentage
                            const percentageElement = document.getElementById(`${status}-percentage`);
                            const percentage = counts[status]['percentage'];
                            percentageElement.textContent = `${percentage}%`;
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                        console.log('Response text:', xhr.responseText); // Log the raw response text
                    }
                });
            }

            function formatDate(dateString) {
                if (!dateString) return '';
                const parts = dateString.split('-');
                const year = parts[0].slice(-2); // Get the last two digits of the year
                return `${parts[2]}-${parts[1]}-${year}`; // Format as DD-MM-YY
            }
            //end


            var globalData = {};

            $(document).ready(function() {
                // Event listeners for search fields
                $('#searchWO, #searchDeskripsi, #searchKodeOrNoWO, #searchStatusWO, #searchStatusDO, #startMonth, #endMonth')
                    .on(
                        'keyup change',
                        function() {
                            populateTables();
                        });

                // Function to populate tables based on search criteria
                function populateTables() {
                    var searchWO = $('#searchWO').val();
                    var searchKodeOrNoWO = $('#searchKodeOrNoWO').val();
                    var searchDeskripsi = $('#searchDeskripsi').val();
                    var searchStatusWO = $('#searchStatusWO').val();
                    var searchStatusDO = $('#searchStatusDO').val();
                    var startMonth = $('#startMonth').val();
                    var endMonth = $('#endMonth').val();

                    // Convert "All" selection to empty string
                    if (startMonth === 'All') startMonth = '';
                    if (endMonth === 'All') endMonth = '';

                    $.ajax({
                        url: '{{ route('searchWO') }}',
                        type: 'GET',
                        data: {
                            'searchWO': searchWO,
                            'searchDeskripsi': searchDeskripsi,
                            'searchKodeOrNoWO': searchKodeOrNoWO,
                            'searchStatusWO': searchStatusWO,
                            'searchStatusDO': searchStatusDO,
                            'startMonth': startMonth,
                            'endMonth': endMonth
                        },
                        success: function(data) {
                            globalData = data;
                            populateTable1(data);
                            if (data.length > 0) {
                                populateTable2(data[0]);
                                populateTable3(data[0]);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                        }
                    });
                }

                // Function to populate the main table
                function populateTable1(data) {
                    $('#tableContainer').empty();

                    let tableHtml = `
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No. WO</th>
                                        <th>Kode</th>
                                        <th>Customer</th>
                                        <th>Deskripsi</th>
                                        <th>Tgl. WO</th>
                                        <th>Status WO</th>
                                        <th>Status DO</th>
                                        <th>Proses</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                </tbody>
                            </table>
                        </div>
                    `;

                    $('#tableContainer').append(tableHtml);

                    let $tableBody = $('#tableBody');

                    $.each(data, function(customer, workOrders) {
                        workOrders.forEach(wo => {
                            let rowHtml = `
                                <tr data-no-wo="${wo.no_wo}">
                                    <td class="no-wo clickable-cell">${wo.no_wo}</td>
                                    <td>${wo.kode}</td>
                                    <td>${wo.cust}</td>
                                    <td>${wo.deskripsi}</td>
                                    <td>${wo.tgl_wo}</td>
                                    <td>${wo.status_wo}</td>
                                    <td>${wo.status_do}</td>
                                    <td>${wo.proses}</td>
                                </tr>
                            `;
                            $tableBody.append(rowHtml);
                        });
                    });

                    $('#tableBody').on('click', '.clickable-cell', function() {
                        let no_wo = $(this).text();
                        let selectedWO = null;

                        $.each(globalData, function(customer, workOrders) {
                            workOrders.forEach(wo => {
                                if (wo.no_wo === no_wo) {
                                    selectedWO = wo;
                                    return false;
                                }
                            });
                            if (selectedWO) {
                                return false;
                            }
                        });

                        if (selectedWO) {
                            populateTable2(selectedWO);
                            populateTable3(selectedWO);
                        }
                    });
                }

                // Function to populate the heating and tempering table
                function populateTable2(data) {
                    $('#batchHeating').text('');
                    $('#mesinHeating').text('');
                    $('#tanggalHeating').text('');
                    $('#batchTemper1').text('');
                    $('#mesinTemper1').text('');
                    $('#tanggalTemper1').text('');
                    $('#batchTemper2').text('');
                    $('#mesinTemper2').text('');
                    $('#tanggalTemper2').text('');
                    $('#batchTemper3').text('');
                    $('#mesinTemper3').text('');
                    $('#tanggalTemper3').text('');

                    $('#batchHeating').text(data.batch || '');
                    $('#mesinHeating').text(data.mesin_heating || '');
                    $('#tanggalHeating').text(data.tgl_heating || '');
                    $('#batchTemper1').text(data.batch_temper1 || '');
                    $('#mesinTemper1').text(data.mesin_temper1 || '');
                    $('#tanggalTemper1').text(data.tgl_temper1 || '');
                    $('#batchTemper2').text(data.batch_temper2 || '');
                    $('#mesinTemper2').text(data.mesin_temper2 || '');
                    $('#tanggalTemper2').text(data.tgl_temper2 || '');
                    $('#batchTemper3').text(data.batch_temper3 || '');
                    $('#mesinTemper3').text(data.mesin_temper3 || '');
                    $('#tanggalTemper3').text(data.tgl_temper3 || '');
                }

                // Function to populate the details table
                function populateTable3(data) {
                    $('#detailNoWO').text('');
                    $('#detailNoDO').text('');
                    $('#detailTglST').text('');
                    $('#detailSupir').text('');
                    $('#detailPenerima').text('');
                    $('#detailTglTerima').text('');

                    $('#detailNoWO').text(data.no_wo || '');
                    $('#detailNoDO').text(data.no_do || '');
                    $('#detailTglST').text(data.tgl_st || '');
                    $('#detailSupir').text(data.supir || '');
                    $('#detailPenerima').text(data.penerima || '');
                    $('#detailTglTerima').text(data.tgl_terima || '');
                }

                // Event listener for detailed batch information
                $('#table2').on('click', '.clickable-cell', function() {
                    let batch = $(this).text().trim();
                    let type = $(this).data('type');

                    $.ajax({
                        url: '{{ route('getBatchData') }}',
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            batch: batch
                        },
                        success: function(response) {
                            populateDetailProses(batch, response, type);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                        }
                    });
                });

                // Function to populate the detailed process information
                function populateDetailProses(batch, workOrders, type) {
                    $('#detailProsesTable tbody').empty();
                    $('#selectedBatch').text(batch);

                    let totalPcs = 0;
                    let totalTonase = 0;

                    workOrders.forEach((wo, index) => {
                        totalPcs += parseInt(wo.pcs || 0);
                        totalTonase += parseFloat(wo.kg || 0);

                        let rowHtml = `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${wo.no_wo}</td>
                                <td>${wo.cust}</td>
                                <td>${wo.mesin_heating || wo.mesin_temper1 || wo.mesin_temper2 || wo.mesin_temper3}</td>
                                <td>${wo.deskripsi}</td>
                                <td>${wo.pcs || ''}</td>
                                <td>${wo.kg || ''}</td>
                                <td>${wo.tgl_wo || ''}</td>
                                <td>${wo.status_wo || ''}</td>
                                <td>${wo.status_do || ''}</td>
                                <td>${wo.proses || ''}</td>
                            </tr>
                        `;
                        $('#detailProsesTable tbody').append(rowHtml);
                    });

                    // Update grand total values
                    $('#grandTotalPcs').text(totalPcs);
                    $('#grandTotalTonase').text(totalTonase.toFixed(2));

                    // Other existing code for populating batch and type details
                    switch (type) {
                        case 'heating':
                            $('#mesinHeating').text(workOrders[0].mesin_heating);
                            $('#tanggalHeating').text(workOrders[0].tgl_wo);
                            break;
                        case 'temper1':
                            $('#mesinTemper1').text(workOrders[0].mesin_temper1);
                            $('#tanggalTemper1').text(workOrders[0].tgl_wo);
                            break;
                        case 'temper2':
                            $('#mesinTemper2').text(workOrders[0].mesin_temper2);
                            $('#tanggalTemper2').text(workOrders[0].tgl_wo);
                            break;
                        case 'temper3':
                            $('#mesinTemper3').text(workOrders[0].mesin_temper3);
                            $('#tanggalTemper3').text(workOrders[0].tgl_wo);
                            break;
                    }
                }
            });
        </script>

        <style>
            #detailProcessTable td {
                padding: 8px;
                border: 1px solid #ccc;
                text-align: center;
                vertical-align: middle;
            }

            #table3 td div {
                border-bottom: 1px solid #dee2e6;
                padding: 8px 0;
            }

            #table3 td div:last-child {
                border-bottom: none;
            }

            .table {
                border-collapse: collapse;
                width: 100%;
            }

            .table th,
            .table td {
                border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;
            }

            .table th {
                background-color: #f2f2f2;
            }
        </style>
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
