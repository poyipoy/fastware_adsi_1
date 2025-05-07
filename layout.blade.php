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
            width: 40px;
            /* Sesuaikan ukuran sesuai kebutuhan */
            height: auto;
            /* Agar proporsi gambar tetap */
        }

        .font-si {
            font-family: 'Cambria', serif;
        }
    </style>

<body>

    <!-- Buat Header -->
    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">

        <div class="d-flex align-items-center justify-content-between p-3">
            <img src="{{ asset('assets/foto/logo-adasi.png') }}" alt="Adasi Logo" class="logo-img mt-1">
            <p class="font-si fw-bold ms-3 mt-4">ASTRA DAIDO</p>
            <i class="bi bi-list toggle-sidebar-btn mx-3 fs-2"></i>
        </div><!-- End Logo -->
        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <img src="{{ Auth::user()->file ? asset('assets/data_diri/' . Auth::user()->file) : asset('assets/img/user.png') }}"
                            alt="Profile" class="rounded-circle">
                        <span class="d-none d-md-block ps-2">{{ Auth::user()->name }}
                            <br>{{ Auth::user()->roles->role }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile mt-3">
                        <li class="dropdown-header">
                            <h6>{{ Auth::user()->name }} - {{ Auth::user()->km_total_poin }} Poin</h6>
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
                            <a class="dropdown-item d-flex align-items-center" style="color: rgb(136, 0, 0)" href="#"
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
            @if (in_array(Auth::user()->role_id, [1, 14, 15]))
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-toggle="collapse" href="#dashboard-admin-nav">
                    <i class="bi bi-person-circle"></i>
                    <span>Kelola Data</span>
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
                @if (Auth::check())
                <ul id="dashboard-menu-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    {{-- @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2 || Auth::user()->role_id == 3 ||
                    Auth::user()->role_id == 4 || Auth::user()->role_id == 5 || Auth::user()->role_id == 6 ||
                    Auth::user()->role_id == 7 || Auth::user()->role_id == 9 || Auth::user()->role_id == 11 ||
                    Auth::user()->role_id == 12 || Auth::user()->role_id == 13 || Auth::user()->role_id == 14 ||
                    Auth::user()->role_id == 16 || Auth::user()->role_id == 17 || Auth::user()->role_id == 22 ||
                    Auth::user()->role_id == 30 || Auth::user()->role_id == 31) --}}
                    @php
                    $acsrole = [1, 2, 3, 4, 5, 6, 7, 9, 11, 12, 13, 14, 15, 16, 17, 22, 30, 31];
                    @endphp
                    @if (in_array(Auth::user()->role_id, $acsrole))
                    <li>
                        <a class="nav-link collapsed" href="{{ route('dashboardMaintenance') }}">
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
                        <a class="nav-link collapsed" href="{{ route('reportpatrol') }}">
                            <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            <span>Safety Patrol</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('dsCompetency') }}">
                            <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            <span>People Development</span>
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('dashboardSS') }}">
                            <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            <span>Sumbang Saran</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link collapsed" href="{{ route('dsKnowlege') }}">
                            <i class="bi bi-bar-chart-line-fill fs-6"></i>
                            <span>Knowledge System</span>
                        </a>
                    </li>
                </ul>
                @endif
            </li>
            @php
            $acsrole = [1, 5, 8, 9, 14, 22, 30, 31, 42, 45, 48, 51, 58];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
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
            @php
            $acsrole = [6, 5, 14, 22, 1];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
            <li class="nav-heading">Maintenance</li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#maint-korektif-nav" data-bs-toggle="collapse" href="#">
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
                <a class="nav-link collapsed" data-bs-target="#maint-received-nav" data-bs-toggle="collapse" href="#">
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
            @php
            $acsrole = [1, 22, 5, 14];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
            <li class="nav-heading">Engineering</li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#dept-maint-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>Bagian Maintenance</span><i
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
                <a class="nav-link collapsed" data-bs-target="#dept-complain-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-journal-text"></i><span>Bagian Engineering</span><i
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
            @php
            $acsrole = [1, 2, 3, 4, 11, 12, 13, 14];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
            {{-- Role ID untuk Sales --}}
            <li class="nav-heading">Sales</li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#sales-fpp-nav" data-bs-toggle="collapse" href="#">
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
                <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
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
                <a class="nav-link collapsed" data-bs-target="#forms-nav-inquiry" data-bs-toggle="collapse" href="#">
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
                        <a href="{{ route('konfirmInquiry') }}">
                            <i class="bi bi-list-check fs-6"></i><span>Approve Inquiry</span>
                        </a>
                    </li>
                </ul>

            </li><!-- End Forms Nav -->
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
            </li>
            @php
            $acsrole = [1, 2, 3, 5, 7, 9, 11, 12, 14, 15, 16, 20, 22, 30, 31, 32];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
            <a class="nav-link collapsed" data-bs-target="#nav-approval-ss" data-bs-toggle="collapse" href="#">
                <i class="bi bi-layout-wtf"></i><span>Persetujuan</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            @endif
            <ul id="nav-approval-ss" class="nav-content collapse " data-bs-parent="#sidebar-nav">

                @php
                $acsrole = [1, 3, 9, 12, 14, 15, 22, 30, 31, 32];
                @endphp
                @if (in_array(Auth::user()->role_id, $acsrole))
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('showKonfirmasiForeman') }}">
                        <i class="bi bi-kanban fs-6"></i>
                        <span>By Sect. Head</span>
                    </a>
                </li>
                @endif
                {{-- @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 2 || Auth::user()->role_id == 3 ||
                Auth::user()->role_id == 5 || Auth::user()->role_id == 9 || Auth::user()->role_id == 11 ||
                Auth::user()->role_id == 12 || Auth::user()->role_id == 14 || Auth::user()->role_id == 22 ||
                Auth::user()->role_id == 30 || Auth::user()->role_id == 31 || Auth::user()->role_id == 31) --}}
                @php
                $acsrole = [1, 2, 3, 5, 7, 9, 11, 12, 14, 15, 22, 30, 31];
                @endphp
                @if (in_array(Auth::user()->role_id, $acsrole))
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('showKonfirmasiDeptHead') }}">
                        <i class="bi bi-kanban-fill fs-6"></i>
                        <span>By Dept. Head</span>
                    </a>
                </li>
                @endif
            </ul>
            @php
            $acsrole = [1, 5, 14, 15, 20];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
            <a class="nav-link collapsed" data-bs-target="#nav-pic" data-bs-toggle="collapse" href="#">
                <i class="bi bi-layout-wtf"></i><span>PIC Penilaian</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            @endif
            <ul id="nav-pic" class="nav-content collapse " data-bs-parent="#sidebar-nav">

                @php
                $acsrole = [1, 5, 14, 15, 16, 20];
                @endphp
                @if (in_array(Auth::user()->role_id, $acsrole))
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('showKonfirmasiKomite') }}">
                        <i class="bi-person-lines-fill fs-6"></i>
                        <span>PIC Penilai SS | Komite</span>
                    </a>
                </li>
                @endif

                @php
                $acsrole = [1, 5, 14, 15, 16, 20];
                @endphp
                @if (in_array(Auth::user()->role_id, $acsrole))
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('showKonfirmasiHRGA') }}">
                        <i class="bi-person-lines-fill fs-6"></i>
                        <span>PIC Penilai SS | HRGA</span>
                    </a>
                </li>
                @endif
            </ul>

            @php
            $acsrole = [1, 2, 3, 5, 7, 9, 11, 12, 14, 15, 22, 30, 31];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
            <li class="nav-heading">Knowledge Management</li>
            <a class="nav-link collapsed" data-bs-target="#nav-km-pengajuan" data-bs-toggle="collapse" href="#">
                <i class="bi bi-layout-wtf"></i><span>Pengajuan Knowledge Management</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            @endif
            <ul id="nav-km-pengajuan" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                @php
                $acsrole = [1, 2, 3, 5, 7, 9, 11, 12, 14, 15, 22, 30, 31];
                @endphp
                @if (in_array(Auth::user()->role_id, $acsrole))
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('pengajuanKM') }}">
                        <i class="bi bi-kanban fs-6"></i>
                        <span>Pengajuan Form Knowledge Management</span>
                    </a>
                </li>
                @endif
                @php
                $acsrole = [1, 14, 15];
                @endphp
                @if (in_array(Auth::user()->role_id, $acsrole))
                <li class="nav-item">
                    <a class="nav-link collapsed" href="{{ route('persetujuanKM') }}">
                        <i class="bi bi-kanban-fill fs-6"></i>
                        <span>Persetujuan Knowledge Management</span>
                    </a>
                </li>
                @endif
            </ul>

            @php
            // Roles for accessing Technical Competency Management
            $hrgarole = [1, 14, 15];
            // Roles for accessing Technical Competencyby Sec. Head
            $secHeadRoles = [1, 3, 9, 2, 5, 11, 7, 31, 22, 30, 12, 14, 15];
            // Roles for accessing Technical Competency byDept. Head
            $deptHeadRoles = [1, 2, 5, 11, 7, 14, 15, 3, 12, 22, 30, 31];
            @endphp

            @if (in_array(Auth::user()->role_id, $hrgarole) ||
            in_array(Auth::user()->role_id, $secHeadRoles) ||
            in_array(Auth::user()->role_id, $deptHeadRoles))
            <li class="nav-heading">People Development</li>
            <a class="nav-link collapsed" data-bs-target="#nav-tech-competency" data-bs-toggle="collapse" href="#">
                <i class="bi bi-tools"></i><span>Base Competency</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>

            <ul id="nav-tech-competency" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('historiDept') }}">
                        <i class="bi bi-hourglass-bottom fs-6"></i>
                        <span>Histori Development</span>
                    </a>

                    <a class="nav-link" href="{{ route('job.positions.index') }}">
                        <i class="bi bi-clipboard fs-6"></i>
                        <span>Summary Competency</span>
                    </a>
                </li>
                <li class="nav-item">
                    @if (in_array(Auth::user()->role_id, $deptHeadRoles) || in_array(Auth::user()->role_id, $hrgarole))
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#formSubsectionOne">
                        <i class="bi bi-file-earmark-text fs-6"></i>
                        <span>Forms Pengajuan Competency</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    @endif

                    <ul id="formSubsectionOne" class="nav-content collapse" data-bs-parent="#nav-tech-competency">
                        @if (in_array(Auth::user()->role_id, $hrgarole))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('jobShow') }}">
                                <i class="bi bi-briefcase fs-6"></i>
                                <span>Form Job Position</span>
                            </a>
                        </li>
                        @endif
                        @if (in_array(Auth::user()->role_id, $deptHeadRoles))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tcShow') }}">
                                <i class="bi bi-check2-circle fs-6"></i>
                                <span>Form Competency</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                <li class="nav-item">
                    @if (in_array(Auth::user()->role_id, $deptHeadRoles) || in_array(Auth::user()->role_id,
                    $secHeadRoles))
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#evaluationSubsectionTwo">
                        <i class="bi bi-check-circle-fill fs-6"></i>
                        <span>Penilaian Technical Competency</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    @endif
                    <ul id="evaluationSubsectionTwo" class="nav-content collapse" data-bs-parent="#nav-tech-competency">
                        @if (in_array(Auth::user()->role_id, $secHeadRoles))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('penilaian.index') }}">
                                <i class="bi bi-person-check-fill fs-6"></i>
                                <span>Penilaian Technical Competency by Sec. Head</span>
                            </a>
                        </li>
                        @endif
                        @if (in_array(Auth::user()->role_id, $deptHeadRoles))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('penilaian.index2') }}">
                                <i class="bi bi-person-check-fill fs-6"></i>
                                <span>Penilaian Technical Competency by Dept. Head</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
            </ul>
            @endif

            @php
            $deptrole = [1, 2, 5, 11, 7, 14, 15]; // Roles for accessing Training Management
            @endphp
            @if (in_array(Auth::user()->role_id, $deptrole))
            <a class="nav-link collapsed" data-bs-target="#nav-training" data-bs-toggle="collapse" href="#">
                <i class="bi bi-journal-text"></i><span>Training Development</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            @endif
            <ul id="nav-training" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                @if (in_array(Auth::user()->role_id, $deptrole))
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#formSubsectionTraining">
                        <i class="bi bi-file-earmark-text fs-6"></i>
                        <span>Forms Pengajuan</span>
                        <i class="bi bi-chevron-down ms-auto fs-6"></i>
                    </a>
                    <ul id="formSubsectionTraining" class="nav-content collapse" data-bs-parent="#nav-training">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('indexPD') }}">
                                <i class="bi bi-file-earmark-plus fs-6"></i>
                                <span>Form Pengajuan Training</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                @php
                $hrgarole = [1, 14, 15]; // Roles for accessing Training Management
                @endphp
                @if (in_array(Auth::user()->role_id, $hrgarole))
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#evaluationSubsectionTraining">
                        <i class="bi bi-bar-chart-line fs-6"></i>
                        <span>Penilaian</span>
                        <i class="bi bi-chevron-down ms-auto fs-6"></i>
                    </a>
                    <ul id="evaluationSubsectionTraining" class="nav-content collapse" data-bs-parent="#nav-training">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('indexPD2') }}">
                                <i class="bi bi-check-circle fs-6"></i>
                                <span>Persetujuan Development</span>
                            </a>
                        </li>

                    </ul>
                </li>
                @endif
            </ul>

            @php
            $secHeadRoles = [1, 2, 3, 4, 5, 9, 12, 14, 15, 22, 30, 31, 39, 40, 41, 44, 48, 50, 54];
            $deptHeadRoles = [1, 2, 5, 11, 7, 14, 30]; // Department Head Roles
            $userRoles = [1, 14, 15, 50, 30, 40, 11, 39]; // User Roles
            $finnRole = [1, 14, 11, 12]; // Finance Roles
            $procRoles = [1, 14, 41, 54]; // Procurement Roles
            $ppicRoles = [52, 48];
            $foremanRoles = [27];
            // Gabungkan semua roles ke dalam satu array
            $allRoles = array_merge($secHeadRoles, $deptHeadRoles, $userRoles, $finnRole, $procRoles, $ppicRoles,
            $foremanRoles);
            @endphp
            @if (in_array(Auth::user()->role_id, $allRoles))
            <li class="nav-heading">Form Pengajuan Barang/Jasa</li>

            <a class="nav-link collapsed" data-bs-target="#nav-po-pengajuan" data-bs-toggle="collapse" href="#">
                <i class="bi bi-journal-text"></i><span>Pengajuan Form</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>

            <ul id="nav-po-pengajuan" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                @if (in_array(Auth::user()->role_id, array_merge($secHeadRoles, $ppicRoles)))
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('index.PO') }}">
                        <i class="bi bi-file-earmark-plus fs-6"></i>
                        <span>Form Permintaan Barang/Jasa</span>
                    </a>
                </li>
                @endif

                @if (in_array(Auth::user()->role_id, $deptHeadRoles))
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse"
                        href="#approvalSubsectionPoPengajuanDeptHead">
                        <i class="bi bi-bar-chart-line fs-6"></i>
                        <span>Approval</span>
                        <i class="bi bi-chevron-down ms-auto fs-6"></i>
                    </a>
                    <ul id="approvalSubsectionPoPengajuanDeptHead" class="nav-content collapse"
                        data-bs-parent="#nav-po-pengajuan">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('index.PO.Dept') }}">
                                <i class="bi bi-check-circle fs-6"></i>
                                <span>Persetujuan FPB by Dept. Head</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                @if (in_array(Auth::user()->role_id, $userRoles))
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#approvalSubsectionPoPengajuanUser">
                        <i class="bi bi-bar-chart-line fs-6"></i>
                        <span>Approval</span>
                        <i class="bi bi-chevron-down ms-auto fs-6"></i>
                    </a>
                    <ul id="approvalSubsectionPoPengajuanUser" class="nav-content collapse"
                        data-bs-parent="#nav-po-pengajuan">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('index.PO.user') }}">
                                <i class="bi bi-check-circle fs-6"></i>
                                <span>Persetujuan FPB by User</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                @if (in_array(Auth::user()->role_id, $finnRole))
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse"
                        href="#approvalSubsectionPoPengajuanFinance">
                        <i class="bi bi-bar-chart-line fs-6"></i>
                        <span>Approval</span>
                        <i class="bi bi-chevron-down ms-auto fs-6"></i>
                    </a>
                    <ul id="approvalSubsectionPoPengajuanFinance" class="nav-content collapse"
                        data-bs-parent="#nav-po-pengajuan">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('index.PO.finance') }}">
                                <i class="bi bi-check-circle fs-6"></i>
                                <span>Persetujuan FPB by Finance</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                @if (in_array(Auth::user()->role_id, $procRoles))
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse"
                        href="#approvalSubsectionPoPengajuanProcurement">
                        <i class="bi bi-bar-chart-line fs-6"></i>
                        <span>Approval</span>
                        <i class="bi bi-chevron-down ms-auto fs-6"></i>
                    </a>
                    <ul id="approvalSubsectionPoPengajuanProcurement" class="nav-content collapse"
                        data-bs-parent="#nav-po-pengajuan">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('index.PO.procurement') }}">
                                <i class="bi bi-check-circle fs-6"></i>
                                <span>Persetujuan Pengajuan Form by Procurement Menu 1</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('index.PO.procurement2') }}">
                                <i class="bi bi-check-circle fs-6"></i>
                                <span>Persetujuan Pengajuan Form by Procurement Menu 2</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link collapsed" data-bs-toggle="collapse" href="#subcontProposalSection">
                        <i class="bi bi-bar-chart-line fs-6"></i>
                        <span>Pengajuan Penawaran Subcont</span>
                        <i class="bi bi-chevron-down ms-auto fs-6"></i>
                    </a>
                    <ul id="subcontProposalSection" class="nav-content collapse" data-bs-parent="#nav-po-pengajuan">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('indexProc') }}">
                                <i class="bi bi-check-circle fs-6"></i>
                                <span>Persetujuan Penawaran Subcont</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Menu Baru: Pengajuan Penawaran Subcont -->
                @endif
            </ul>

            <a class="nav-link collapsed" data-bs-target="#forms-nav-subcont" data-bs-toggle="collapse" href="#">
                <i class="bi bi-journal-text"></i><span>Pengajuan Penawaran Subcont</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="forms-nav-subcont" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('indexSales') }}">
                        <i class="bi bi-list-check fs-6"></i><span>Buat Penawaran Subcont</span>
                    </a>
                </li>
            </ul>
            @endif

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
            {{-- Menu Inventory-PPC --}}
            <li class="nav-heading">PPIC</li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('reportInquiry') }}">
                    <i class="bi bi-cloud-upload"></i>
                    <span>Validasi Sales</span>
                </a>
            </li><!-- End Profile Page Nav -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('validasiInquiry') }}">
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
            @endif
            @php
            $acsrole = [1, 5, 14, 22, 26];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
            <li class="nav-heading">WO Heat Treatment</li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('dashboardImportWO') }}">
                    <i class="bi bi-cloud-upload"></i>
                    <span>Import WO</span>
                </a>
            </li><!-- End Profile Page Nav -->
            @endif

            @php
            $acsrole = [1, 2, 3, 4, 5, 14, 22, 26, 28, 30];
            @endphp
            @if (in_array(Auth::user()->role_id, $acsrole))
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('dashboardTracingWO') }}">
                    <i class="bi bi-search"></i>
                    <span>Tracing WO</span>
                </a>
            </li>

            {{--
            <hr> --}}
            @endif
        </ul>

        <!-- Footer Sidebar -->
        <ul class="sidebar-nav fixed-bottom ps-3">
        </ul>
        <!-- End Footer Sidebar -->
    </aside><!-- End Sidebar-->

    @yield('content');

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