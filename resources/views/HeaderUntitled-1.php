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
         // Gabungkan semua roles ke dalam satu array
         $allRoles = array_merge($secHeadRoles, $deptHeadRoles, $userRoles, $finnRole, $procRoles, $ppicRoles);
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

















 <td>
     @if (
     $inquiry->status != 0 &&
     $inquiry->status != 1 &&
     $inquiry->status != 3 &&
     $inquiry->status != 4 &&
     $inquiry->status != 5 &&
     $inquiry->status != 6 &&
     $inquiry->status != 7)
     <a class="btn btn-primary mt-1 btn-sm" title="Edit">
         <i class="bi bi-pencil-fill"
             onclick="openEditInquiryModal({{ $inquiry->id }})"></i>
     </a>
     <a class="btn btn-danger mt-1 btn-sm" title="Delete">
         <i class="bi bi-trash-fill" onclick="deleteInquiry({{ $inquiry->id }})"></i>
     </a>
     <a class="btn btn-info mt-1 btn-sm"
         href="{{ route('formulirInquiry', ['id' => $inquiry->id]) }}"
         title="Formulir Inquiry">
         <i class="bi bi-file-earmark-arrow-up-fill"></i>
     </a>
     @endif

     <a class="btn btn-warning mt-1 btn-sm" title="View Form"
         href="{{ route('historyFormSS', $inquiry->id) }}">
         <i class="bi bi-eye-fill"></i>
     </a>

 </td>











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

 .collapsing {
 max-height: 0;
 overflow: hidden;
 /* Sembunyikan elemen yang tidak ditampilkan */
 transition: max-height 0.5s ease-in-out;
 /* Transisi saat menyembunyikan elemen */
 }









 public function tindakLanjutInquiry($id)
 {
 $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);
 $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();
 $typeMaterials = TypeMaterial::all();

 return view('inquiry.tindakLanjutInquiry', compact('inquiry', 'materials', 'typeMaterials'));
 }



 public function editInquiry($id)
 {
 $inquiry = InquirySales::find($id);
 $customers = Customer::all();

 if (!$inquiry) {
 return response()->json(['error' => 'Inquiry not found'], 404);
 }

 $supplier = $inquiry->supplier;
 return response()->json([
 'id' => $inquiry->id,
 'kode_inquiry' => $inquiry->kode_inquiry,
 'jenis_inquiry' => $inquiry->jenis_inquiry,
 'id_customer' => $inquiry->id_customer,
 'customer_name' => $inquiry->customer->name_customer, // Assuming a relation is set up
 'customers' => $customers,
 'supplier' => $supplier,
 ]);
 }

 public function update(Request $request, $id)
 {
 $request->validate([
 'jenis_inquiry' => 'required',
 'id_customer' => 'required',
 'supplier' => 'required',
 ]);

 $inquiry = InquirySales::findOrFail($id);

 // Generate kode inquiry
 $jenisInquiry = $request->jenis_inquiry; // RO atau SPOR
 $currentMonth = Carbon::now()->format('m');
 $currentYear = Carbon::now()->format('Y');
 $lastInquiry = InquirySales::where('jenis_inquiry', $jenisInquiry)->orderBy('id', 'desc')->first();
 $nextNumber = $lastInquiry ? intval(substr($lastInquiry->kode_inquiry, -3)) + 1 : 1;
 $kodeInquiry = sprintf('%s/%02d/%04d/%03d', $jenisInquiry, $currentMonth, $currentYear, $nextNumber);

 $inquiry->kode_inquiry = $kodeInquiry;
 $inquiry->supplier = $request->supplier;

 // Assign the name of the logged-in user to the create_by field
 $inquiry->create_by = Auth::user()->name;

 $inquiry->update($request->except('order_from')); // Update excluding order_from

 return redirect()->route('createinquiry')->with('success', 'Inquiry updated successfully');
 }




 public function showFormSS($id)
 {
 $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);

 // Fetch all detail inquiries based on id_inquiry from the main inquiry
 $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();

 $typeMaterials = TypeMaterial::all(); // Ambil semua data TypeMaterial, sesuaikan dengan kebutuhan

 return view('inquiry.showFormSS', compact('inquiry', 'materials', 'typeMaterials'));
 }

 public function historyFormSS($id)
 {
 $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);

 // Fetch all detail inquiries based on id_inquiry from the main inquiry
 $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();

 $typeMaterials = TypeMaterial::all(); // Ambil semua data TypeMaterial, sesuaikan dengan kebutuhan

 return view('inquiry.historyFormSS', compact('inquiry', 'materials', 'typeMaterials'));
 }

 public function konfirmInquiry()
 {
 $statuses = [3, 1, 4, 5, 6, 7];
 $inquiries = InquirySales::with('customer', 'details.type_materials')
 ->whereIn('status', $statuses)
 ->orderByRaw('FIELD(status, 3, 1, 4, 5, 6, 7)')
 ->get();

 // Ambil semua data TypeMaterial, sesuaikan dengan kebutuhan
 $typeMaterials = TypeMaterial::all();
 $customers = Customer::all(); // Ambil semua data pelanggan

 // Variabel untuk menyimpan semua detail inquiries
 $allMaterials = [];

 // Loop untuk setiap inquiry dalam $inquiries
 foreach ($inquiries as $inquiry) {
 // Fetch all detail inquiries based on id_inquiry from the main inquiry
 $materials = DetailInquiry::where('id_inquiry', $inquiry->id)
 ->with('type_materials')
 ->get();

 // Tambahkan ke dalam array $allMaterials
 $allMaterials[$inquiry->id] = $materials;
 }

 return view('inquiry.konfirmInquiry', compact('inquiries', 'allMaterials', 'typeMaterials', 'customers'));
 }

 public function validasiInquiry()
 {
 $statuses = [3, 1, 4, 5, 6, 7];
 $inquiries = InquirySales::with('customer', 'details.type_materials')
 ->whereIn('status', $statuses)
 ->orderByRaw('FIELD(status, 3, 1, 4, 5, 6, 7)')
 ->get();

 // Ambil semua data TypeMaterial, sesuaikan dengan kebutuhan
 $typeMaterials = TypeMaterial::all();
 $customers = Customer::all(); // Ambil semua data pelanggan

 // Variabel untuk menyimpan semua detail inquiries
 $allMaterials = [];

 // Loop untuk setiap inquiry dalam $inquiries
 foreach ($inquiries as $inquiry) {
 // Fetch all detail inquiries based on id_inquiry from the main inquiry
 $materials = DetailInquiry::where('id_inquiry', $inquiry->id)
 ->with('type_materials')
 ->get();

 // Tambahkan ke dalam array $allMaterials
 $allMaterials[$inquiry->id] = $materials;
 }

 return view('inquiry.validasi', compact('inquiries', 'allMaterials', 'typeMaterials', 'customers'));
 }

 public function reportInquiry()
 {
 $statuses = [1, 3, 4, 5, 6, 7];
 $inquiries = InquirySales::with('customer', 'details.type_materials')
 ->whereIn('status', $statuses)
 ->orderByRaw('FIELD(status, 3, 4, 1, 5, 6, 7)')
 ->get();

 // Ambil semua data TypeMaterial, sesuaikan dengan kebutuhan
 $typeMaterials = TypeMaterial::all();
 $customers = Customer::all(); // Ambil semua data pelanggan

 // Variabel untuk menyimpan semua detail inquiries
 $allMaterials = [];

 // Loop untuk setiap inquiry dalam $inquiries
 foreach ($inquiries as $inquiry) {
 // Fetch all detail inquiries based on id_inquiry from the main inquiry
 $materials = DetailInquiry::where('id_inquiry', $inquiry->id)
 ->with('type_materials')
 ->get();

 // Tambahkan ke dalam array $allMaterials
 $allMaterials[$inquiry->id] = $materials;
 }

 return view('inquiry.report', compact('inquiries'));
 }

 // {
 // // Validasi permintaan
 // $request->validate([
 // 'id_inquiry' => 'required|exists:inquiry_sales,id',
 // 'materials' => 'required|array', // Memastikan materials adalah array
 // 'materials.*.id_type' => 'required',
 // 'materials.*.jenis' => 'required',
 // 'materials.*.thickness' => 'required',
 // 'materials.*.weight' => 'required',
 // 'materials.*.inner_diameter' => 'required',
 // 'materials.*.outer_diameter' => 'required',
 // 'materials.*.length' => 'required',
 // 'materials.*.qty' => 'required',
 // 'materials.*.m1' => 'required',
 // 'materials.*.m2' => 'required',
 // 'materials.*.m3' => 'required',
 // 'materials.*.ship' => 'required',
 // 'materials.*.note' => 'required',
 // ]);

 // // Dapatkan inquiry berdasarkan ID
 // $inquiry = InquirySales::findOrFail($request->id_inquiry);

 // // Hapus data detail lama jika diperlukan
 // DetailInquiry::where('id_inquiry', $inquiry->id)->delete();

 // // Loop melalui setiap material dan simpan ke detail_inquiry
 // foreach ($request->materials as $material) {
 // $detailInquiry = new DetailInquiry();
 // $detailInquiry->id_inquiry = $inquiry->id;
 // $detailInquiry->id_type = $material['id_type'];
 // $detailInquiry->jenis = $material['jenis'];
 // $detailInquiry->thickness = $material['thickness'];
 // $detailInquiry->weight = $material['weight'];
 // $detailInquiry->inner_diameter = $material['inner_diameter'];
 // $detailInquiry->outer_diameter = $material['outer_diameter'];
 // $detailInquiry->length = $material['length'];
 // $detailInquiry->qty = $material['qty'];
 // $detailInquiry->m1 = $material['m1'];
 // $detailInquiry->m2 = $material['m2'];
 // $detailInquiry->m3 = $material['m3'];
 // $detailInquiry->ship = $material['ship'];
 // $detailInquiry->note = $material['note'];
 // $detailInquiry->save(); // Simpan ke tabel detail_inquiry
 // }

 // return redirect()->route('showFormSS', $inquiry->id)
 // ->with('success', 'Inquiry details saved successfully.');
 // }

 // public function approvedInquiry(Request $request, $id)
 // {
 // $inquiry = InquirySales::findOrFail($id);

 // $inquiry->to_validate = 'Waiting';

 // if ($request->action_type == 'approved') {
 // $inquiry->to_approve = 'Approved';
 // $inquiry->status = 5;
 // } elseif ($request->action_type == 'not_approved') {
 // $inquiry->to_approve = 'Not Approved';
 // $inquiry->status = 0; // Or any other status code you want to set for not approved
 // }

 // $inquiry->save(); // Save the inquiry to update the database

 // return redirect()->route('konfirmInquiry')->with('success', 'Inquiry updated successfully');
 // }

 // public function validateInquiry(Request $request, $id)
 // {
 // $inquiry = InquirySales::findOrFail($id);

 // // Handle validation
 // if ($request->action_type == 'validated') {
 // $inquiry->to_validate = 'Validated';
 // $inquiry->status = 6;

 // // Save note
 // $inquiry->note = $request->note;

 // // Save attachment file
 // if ($request->hasFile('file')) {
 // $file = $request->file('file');
 // $fileName = $file->getClientOriginalName(); // Use the original file name
 // $file->move(public_path('assets/files'), $fileName); // Save to public/assets/files directory
 // $inquiry->attach_file = $fileName;
 // }
 // } elseif ($request->action_type == 'not_validated') {
 // $inquiry->to_validate = 'Not Validated';
 // $inquiry->status = 0;
 // }

 // $inquiry->save(); // Save changes to the database

 // return redirect()->route('reportInquiry')->with('success', 'Inquiry updated successfully');
 // }

 public function previewSS(Request $request)
 {
 // Validasi input
 $request->validate([
 'id_inquiry' => 'required|integer',
 'materials.*.id_type' => 'required|integer',
 'materials.*.jenis' => 'required|string',
 'materials.*.thickness' => 'nullable|string',
 'materials.*.weight' => 'nullable|string',
 'materials.*.inner_diameter' => 'nullable|string',
 'materials.*.outer_diameter' => 'nullable|string',
 'materials.*.length' => 'nullable|string',
 'materials.*.qty' => 'nullable|string',
 'materials.*.m1' => 'nullable|string',
 'materials.*.m2' => 'nullable|string',
 'materials.*.m3' => 'nullable|string',
 'materials.*.ship' => 'nullable|string',
 'materials.*.note' => 'nullable|string',
 ]);

 // Ambil id_inquiry dari request
 $id_inquiry = $request->id_inquiry;
 Log::info('ID Inquiry:', ['id_inquiry' => $id_inquiry]);

 // Iterasi dan simpan atau update material
 foreach ($request->materials as $material) {
 Log::info('Processing Material:', $material);

 // Cari detail inquiry berdasarkan id_inquiry dan id_type
 $detailInquiry = DetailInquiry::where('id_inquiry', $id_inquiry)
 ->where('id_type', $material['id_type'])
 ->first();
 if ($detailInquiry) {
 // Perbarui data
 $detailInquiry->update($material);
 Log::info('DetailInquiry updated', ['id' => $detailInquiry->id]);
 } else {
 // Buat detail inquiry baru
 $newDetailInquiry = DetailInquiry::create(array_merge(['id_inquiry' => $id_inquiry], $material));
 Log::info('DetailInquiry created', ['id' => $newDetailInquiry->id]);
 }

 // Cari dan perbarui status inquiry
 $inquiry = InquirySales::find($id_inquiry);
 if ($inquiry) {
 $inquiry->status = 3; // Menandakan status "On Progress" atau sesuai definisi Anda
 $inquiry->save();
 Log::info('Inquiry status updated to 3', ['id' => $inquiry->id]);
 } else {
 Log::warning('Inquiry not found', ['id_inquiry' => $id_inquiry]);
 return response()->json(['message' => 'Inquiry not found'], 404);
 }

 return response()->json(['message' => 'Detail Inquiry updated or created successfully']);
 }
 }

 // public function saveTindakLanjut(Request $request)
 // {
 // // Validasi input
 // $request->validate([
 // 'id_inquiry' => 'required|integer',
 // 'materials.*.id_type' => 'required|integer', // Validate id_type
 // 'materials.*.jenis' => 'required|string', // Validate jenis
 // 'materials.*.thickness' => 'nullable|string',
 // 'materials.*.weight' => 'nullable|string',
 // 'materials.*.inner_diameter' => 'nullable|string',
 // 'materials.*.outer_diameter' => 'nullable|string',
 // 'materials.*.length' => 'nullable|string',
 // 'materials.*.pcs' => 'nullable|string',
 // 'materials.*.qty' => 'nullable|string',
 // 'materials.*.konfirmasi' => 'nullable|string',
 // 'materials.*.no_po' => 'nullable|string',
 // 'materials.*.rencana_kedatangan' => 'nullable|string',
 // ]);

 // // Ambil id_inquiry dari request
 // $id_inquiry = $request->id_inquiry;
 // Log::info('ID Inquiry:', ['id_inquiry' => $id_inquiry]);

 // // Iterasi dan simpan atau update material
 // foreach ($request->materials as $material) {
 // Log::info('Processing Material:', $material);

 // // Cari detail inquiry berdasarkan id_inquiry dan id_type
 // $detailInquiry = DetailInquiry::where('id_inquiry', $id_inquiry)
 // ->where('id_type', $material['id_type'])
 // ->first();
 // if ($detailInquiry) {
 // // Jika detail inquiry ditemukan, perbarui data detail inquiry
 // $detailInquiry->update([
 // 'id_type' => $material['id_type'],
 // 'jenis' => $material['jenis'],
 // 'thickness' => $material['thickness'],
 // 'weight' => $material['weight'],
 // 'inner_diameter' => $material['inner_diameter'],
 // 'outer_diameter' => $material['outer_diameter'],
 // 'length' => $material['length'],
 // 'pcs' => $material['pcs'],
 // 'qty' => $material['qty'],
 // 'konfirmasi' => $material['konfirmasi'],
 // 'no_po' => $material['no_po'],
 // 'rencana_kedatangan' => $material['rencana_kedatangan'],
 // ]);
 // Log::info('DetailInquiry updated', ['id' => $detailInquiry->id]);
 // } else {
 // // Jika detail inquiry tidak ditemukan, buat detail inquiry baru
 // $newDetailInquiry = DetailInquiry::create([
 // 'id_inquiry' => $id_inquiry,
 // 'id_type' => $material['id_type'],
 // 'jenis' => $material['jenis'],
 // 'thickness' => $material['thickness'],
 // 'weight' => $material['weight'],
 // 'inner_diameter' => $material['inner_diameter'],
 // 'outer_diameter' => $material['outer_diameter'],
 // 'length' => $material['length'],
 // 'pcs' => $material['pcs'],
 // 'qty' => $material['qty'],
 // 'konfirmasi' => $material['konfirmasi'],
 // 'no_po' => $material['no_po'],
 // 'rencana_kedatangan' => $material['rencana_kedatangan'],
 // ]);
 // Log::info('DetailInquiry created', ['id' => $newDetailInquiry->id]);
 // }
 // }

 // // Cari inquiry berdasarkan id_inquiry
 // $inquiry = InquirySales::find($id_inquiry);
 // if ($inquiry) {
 // // Perbarui status inquiry menjadi 3
 // $inquiry->status = 7;
 // $inquiry->save();
 // Log::info('Inquiry status updated to 3', ['id' => $inquiry->id]);
 // } else {
 // Log::warning('Inquiry not found', ['id_inquiry' => $id_inquiry]);
 // }

 // return response()->json(['message' => 'Detail Inquiry updated or created successfully']);
 // }







 id_type: idTypeElement.value,
 jenis: jenisElement.value,
 thicknessElement: thicknessElement ? thicknessElement.value,
 weightElement: weightElement ? weightElement.value,
 innerDiameterElement: innerDiameterElement ? innerDiameterElement.value,
 outerDiameterElement: outerDiameterElement ? outerDiameterElement.value,
 lengthElement: lengthElement ? lengthElement.value,
 qtyElement: qtyElement ? qtyElement.value,
 m1Element: m1Element ? m1Element.value,
 m2Element: m2Element ? m2Element.value,
 m3Element: m3Element ? m3Element.value,
 shipElement: shipElement ? shipElement.value,
 soElement: soElement ? soElement.value,
 noteElement: noteElement ? noteElement.value,




 table {
 width: 100%;
 border-collapse: collapse;
 font-size: 0.9rem;
 /* Ukuran font lebih kecil */
 }

 th {
 background-color: #007bff;
 /* Warna header tabel */
 color: white;
 /* Warna teks di header */
 padding: 0.75rem;
 /* Padding untuk header */
 /* border: 1px solid #858585; */
 font-family: 'Cambria', serif;
 text-align: center;
 }

 td {
 padding: 0.75rem;
 /* Padding untuk sel */
 /* border: 1px solid #858585; */
 /* Border antara sel */
 transition: background-color 0.3s;
 /* Transisi halus saat hover */
 font-family: 'Cambria', serif;
 }

 td:hover {
 background-color: #f1f1f1;
 /* Efek hover pada sel */
 }




 public function generatePDF($id)
 {
 // Ambil data inquiry berdasarkan ID
 $inquiry = InquirySales::with(['details.type_materials', 'createdBy'])->findOrFail($id);
 $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();

 // Data pengguna berdasarkan region
 $regionData = [
 'region1' => [
 'sales' => ['INDRA', 'INDRI', 'INDRO'],
 'kasie' => 'ABDUR',
 'kadept' => 'MANAN',
 'inventory' => 'DILA',
 'purchasing' => 'AHMAD',
 ],
 'region2' => [
 'sales' => ['ANDRI', 'ANDRE', 'ANDRO'],
 'kasie' => 'FARIS',
 'kadept' => 'ROJO',
 'inventory' => 'DILA',
 'purchasing' => 'AHMAD',
 ],
 ];

 // Inisialisasi tanda tangan
 $signatures = [
 'submitted' => $inquiry->createdBy ? $inquiry->createdBy->name : 'N/A',
 'approved_kasie' => $this->getApproverKaSie($inquiry, $regionData),
 'approved_kadept' => $this->getApproverKaDept($inquiry, $regionData),
 'approved_inventory' => $this->getApproverInventory($inquiry, $regionData),
 'confirmed_purchasing' => $this->getApproverPurchasing($inquiry, $regionData),
 ];

 // Konversi ke PDF dengan orientasi landscape
 $pdf = PDF::loadView('pdf.inquiry', compact('inquiry', 'materials', 'signatures'))
 ->setPaper('a4', 'landscape'); // Set kertas A4 dalam orientasi landscape

 return $pdf->download('ADSI_FormInquiry.pdf');
 }

 // Fungsi untuk mendapatkan approver Ka. Sie
 private function getApproverKaSie($inquiry, $regionData)
 {
 // Cek region mana yang digunakan berdasarkan pengguna yang melakukan entri
 if (in_array($inquiry->create_by, $regionData['region1']['sales'])) {
 return $regionData['region1']['kasie'];
 } elseif (in_array($inquiry->create_by, $regionData['region2']['sales'])) {
 return $regionData['region2']['kasie'];
 }
 return 'N/A';
 }

 // Fungsi untuk mendapatkan approver Ka. Dept
 private function getApproverKaDept($inquiry, $regionData)
 {
 if (in_array($inquiry->create_by, $regionData['region1']['sales'])) {
 return $regionData['region1']['kadept'];
 } elseif (in_array($inquiry->create_by, $regionData['region2']['sales'])) {
 return $regionData['region2']['kadept'];
 }
 return 'N/A';
 }

 // Tambahkan fungsi serupa untuk Inventory dan Purchasing
 private function getApproverInventory($inquiry, $regionData)
 {
 if (in_array($inquiry->create_by, $regionData['region1']['sales'])) {
 return $regionData['region1']['inventory'];
 } elseif (in_array($inquiry->create_by, $regionData['region2']['sales'])) {
 return $regionData['region2']['inventory'];
 }
 return 'N/A';
 }

 private function getApproverPurchasing($inquiry, $regionData)
 {
 if (in_array($inquiry->create_by, $regionData['region1']['sales'])) {
 return $regionData['region1']['purchasing'];
 } elseif (in_array($inquiry->create_by, $regionData['region2']['sales'])) {
 return $regionData['region2']['purchasing'];
 }
 return 'N/A';
 }




 view : overviewPurchase

 @extends('layout')

 @section('content')
 <main id="main" class="main">

     <style>
         .searchable-dropdown {
             position: relative;
         }

         .searchable-dropdown input {
             width: 100%;
             box-sizing: border-box;
         }

         .dropdown-items {
             display: none;
             position: absolute;
             background-color: white;
             border: 1px solid #ddd;
             max-height: 200px;
             overflow-y: auto;
             z-index: 1000;
         }

         .dropdown-items div {
             padding: 8px;
             cursor: pointer;
         }

         .dropdown-items div:hover {
             background-color: #f1f1f1;
         }

         .font-sii {
             font-family: 'Cambria', serif;
             font-weight: bold;
         }

         .table-1 {
             margin: 5px auto;
             margin-top: 1.5rem;
             /* Pusatkan tabel */
             padding: 1rem;
             /* Padding di sekeliling tabel */
             background-color: #00346b;
             /* Warna latar belakang */
             border-radius: 8px;
             /* Sudut membulat */
             box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
             /* Bayangan untuk efek kedalaman */
         }

         .table-1 th {
             background-color: #002144;
             /* Warna latar belakang */
             border-radius: 8px;
             /* Sudut membulat */
             color: #f1f1f1;
             font-size: 13pt;
             /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); */
             /* Bayangan untuk efek kedalaman */
         }

         table {
             width: 100%;
             border-collapse: collapse;
             font-size: 0.9rem;
             /* Ukuran font lebih kecil */
         }

         th {
             background-color: #007bff;
             /* Warna header tabel */
             color: white;
             /* Warna teks di header */
             padding: 0.75rem;
             /* Padding untuk header */
             /* border: 1px solid #858585; */
             font-family: 'Cambria', serif;
         }

         td {
             padding: 0.75rem;
             /* Padding untuk sel */
             /* border: 1px solid #858585; */
             /* Border antara sel */
             transition: background-color 0.3s;
             /* Transisi halus saat hover */
             font-family: 'Cambria', serif;
         }

         td:hover {
             background-color: #f1f1f1;
             /* Efek hover pada sel */
         }

         .dataTable-pagination {
             padding: 0.25rem;
             /* Padding lebih kecil untuk pagination */
             font-size: 0.8rem;
             /* Ukuran font lebih kecil */
         }

         .dataTable-pagination .dataTable-info,
         .dataTable-pagination .dataTable-pagination-button {
             margin: 0;
             /* Hapus margin untuk elemen info dan tombol pagination */
         }

         .datatable-dropdown {
             font-family: 'Cambria', serif;
             font-size: 0.8rem;
         }

         .datatable-selector {
             padding: 0.2rem;
             /* Padding lebih kecil pada dropdown pagination */
             font-size: 0.8rem;
             /* Ukuran font lebih kecil */
             border-radius: 4px;
             /* Sudut membulat */
             border: 1px solid #ddd;
             /* Border untuk dropdown */
             font-family: 'Cambria', serif;
         }

         input[type="search"] {
             width: 100%;
             /* Lebar input pencarian */
             padding: 0.5rem;
             /* Padding untuk input */
             border: 1px solid #ddd;
             /* Border untuk input */
             border-radius: 10px;
             /* Sudut membulat untuk input */
             margin-bottom: 0.5rem;
             /* Jarak antara input dan tabel */
             transition: border-color 0.3s;
             /* Transisi saat berinteraksi */
             font-family: 'Cambria', serif;
         }

         input[type="search"] {
             padding: 0.3rem;
             /* Padding lebih kecil untuk input pencarian */
             font-size: 0.8rem;
             /* Ukuran font lebih kecil */
             border-radius: 10px;
             /* Sudut membulat */
             border: 1px solid #ddd;
             /* Border untuk input */
         }

         .dataTable-search {
             margin-bottom: 0.5rem;
             /* Jarak antara input pencarian dan tabel */
             font-family: 'Cambria', serif;
         }

         <style>.btn-custom-draft {
             background-color: #6c757d;
             /* atau warna lain yang Anda inginkan */
             color: white;
         }

         .btn-custom-open {
             background-color: #008f64;
             /* atau warna lain */
             color: white;
         }

         .btn-custom-approve-dept {
             background-color: #ff7707;
             /* Warna kuning bisa jadi untuk approve ka.dept */
             color: black;
         }

         .btn-custom-approve-sie {
             background-color: #17a2b8;
             /* Warna biru bisa untuk approve ka.sie */
             color: white;
         }

         .btn-custom-in-progress {
             background-color: #fbff07;
             /* Warna kuning tua untuk on progress */
             color: rgb(0, 0, 0);
         }

         .btn-custom-finished {
             background-color: #00346b;
             /* Warna biru untuk finished */
             color: white;
         }

         .btn-custom-rejected {
             background-color: #dc3545;
             /* Merah untuk rejected */
             color: white;
         }

         .btn-custom-show {
             background-color: #f300a2;
             /* Merah untuk show form */
         }

         .btn-custom-show:hover {
             background-color: #5e0051;
             /* Merah untuk show form */
             color: #f7f7f7;
         }

         .btn-custom-inventory {
             background-color: #00d9ffbb;
             /* Merah untuk show form */
             color: #000000;
         }

         .btn-custom-inventory:hover {
             background-color: #00a4f0;
             /* Merah untuk show form */
             color: #000000;
         }
     </style>

     <section class="section">
         <div class="card">
             <div class="card-body">
                 <h5 class="card-title font-sii text-center">Overview Purchase</h5>
             </div>

             {{-- Overview All 1,2,3,4 --}}

             <section class="section">
                 <div class="card">
                     <div class="card-body">
                         <div class="table-responsive">
                             <table class="table table-1" id="overviewTable">
                                 <thead>
                                     <tr>
                                         <th>No</th>
                                         <th class="text-center">Create By</th>
                                         <th class="text-center">Reference</th>
                                         <th class="text-center">Category</th>
                                         <th class="text-center">Supplier</th>
                                         <th class="text-center">Customer</th>
                                         <th>Status</th>
                                         <th>Last Update</th>
                                         <th>Est. Date</th>
                                         <th>Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($draftInquiries as $index => $inquiry)
                                     <tr>
                                         <td>{{ $index + 1 }}</td>
                                         <td class="text-center">{{ $inquiry->create_by }}</td>
                                         <td class="text-center">{{ $inquiry->kode_inquiry }}</td>
                                         <td class="text-center">{{ $inquiry->loc_imp }}</td>
                                         <td class="text-center">{{ $inquiry->supplier }}</td>
                                         <td class="text-center">
                                             {{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}
                                         </td>
                                         @php
                                         $statusDescriptions = [
                                         1 => 'Draft',
                                         2 => 'Open',
                                         3 => 'Approve Ka.Dept',
                                         4 => 'Approve Ka.Sie',
                                         5 => 'On Progress',
                                         6 => 'Finished',
                                         7 => 'Rejected',
                                         8 => 'Approve Inventory',
                                         9 => 'Confirm Purchasing',
                                         ];

                                         // Mendefinisikan kelas tombol berdasarkan status
                                         $buttonClasses = [
                                         1 => 'btn-secondary', // Draft
                                         2 => 'btn-success', // Open
                                         3 => 'btn-danger', // Approve Ka.Dept
                                         4 => 'btn-info', // Approve Ka.Sie
                                         5 => 'btn-warning', // On Progress
                                         6 => 'btn-primary', // Finished
                                         7 => 'btn-danger', // Rejected
                                         8 => 'btn-danger', // Approve Inventory
                                         9 => 'btn-warning', // Confirm Purchasing
                                         ];
                                         @endphp
                                         <td>
                                             <button
                                                 class="btn btn-sm 
                                                        {{ $buttonClasses[$inquiry->status] ?? 'btn-light' }} 
                                                        {{ $inquiry->status == 1 ? 'btn-custom-draft' : '' }}
                                                        {{ $inquiry->status == 2 ? 'btn-custom-open' : '' }}
                                                        {{ $inquiry->status == 3 ? 'btn-custom-approve-dept' : '' }}
                                                        {{ $inquiry->status == 4 ? 'btn-custom-approve-sie' : '' }}
                                                        {{ $inquiry->status == 5 ? 'btn-custom-in-progress' : '' }}
                                                        {{ $inquiry->status == 6 ? 'btn-custom-finished' : '' }}
                                                        {{ $inquiry->status == 7 ? 'btn-custom-rejected' : '' }}
                                                        {{ $inquiry->status == 8 ? 'btn-custom-inventory' : '' }}
                                                        {{ $inquiry->status == 9 ? 'btn-custom-confirm-purchasing' : '' }}">
                                                 {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                             </button>
                                         </td>
                                         <td>
                                             {{ $inquiry->last_update ?? 'No updates yet' }}
                                         </td>
                                         <td>{{ $inquiry->est_date }}</td>
                                         {{-- <td class="text-center"> --}}
                                         {{-- <a href="#" class="btn btn-info btn-sm"
                                                        onclick="showProgressHistory({{ $inquiry->id }}); return false;">
                                         History Progress
                                         </a> --}}
                                         {{-- </td> --}}
                                         <td>
                                             {{-- <a href="#" class="btn btn-info btn-sm"
                                                        onclick="showProgressModal1({{ $inquiry->id }}); return false;"
                                             title="Show Detail Inquiry">
                                             <i class="bi bi-info-square-fill"></i>
                                             </a> --}}
                                             <a href="#" class="btn btn-custom-show btn-sm"
                                                 onclick="showInquiry({{ $inquiry->id }}); return false;"
                                                 title="Show Form">
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
             </section>
             {{-- Overview All 5,6,7 --}}
             <section class="section">
                 <div class="card">
                     <div class="card-body">
                         <div class="table-responsive">
                             <table class="table table-1" id="overviewTable">
                                 <thead>
                                     <tr>
                                         <th>No</th>
                                         <th class="text-center">Create By</th>
                                         <th class="text-center">Reference</th>
                                         <th class="text-center">Category</th>
                                         <th class="text-center">Supplier</th>
                                         <th class="text-center">Customer</th>
                                         <th>Status</th>
                                         <th>Last Update</th>
                                         <th>Est. Date</th>
                                         <th style="width: 250px">Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($inquiries as $index => $inquiry)
                                     <tr>
                                         <td>{{ $index + 1 }}</td>
                                         <td class="text-center">{{ $inquiry->create_by }}</td>
                                         <td class="text-center">{{ $inquiry->kode_inquiry }}</td>
                                         <td class="text-center">{{ $inquiry->loc_imp }}</td>
                                         <td class="text-center">{{ $inquiry->supplier }}</td>
                                         <td class="text-center">
                                             {{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}
                                         </td>
                                         @php
                                         $statusDescriptions = [
                                         1 => 'Draft',
                                         2 => 'Open',
                                         3 => 'Approve Ka.Dept',
                                         4 => 'Approve Ka.Sie',
                                         5 => 'On Progress',
                                         6 => 'Finished',
                                         7 => 'Rejected',
                                         8 => 'Approve Inventory',
                                         9 => 'Confirm Purchasing',
                                         ];

                                         // Mendefinisikan kelas tombol berdasarkan status
                                         $buttonClasses = [
                                         1 => 'btn-secondary', // Draft
                                         2 => 'btn-success', // Open
                                         3 => 'btn-danger', // Approve Ka.Dept
                                         4 => 'btn-info', // Approve Ka.Sie
                                         5 => 'btn-warning', // On Progress
                                         6 => 'btn-primary', // Finished
                                         7 => 'btn-danger', // Rejected
                                         8 => 'btn-danger', // Approve Inventory
                                         9 => 'btn-warning', // Confirm Purchasing
                                         ];
                                         @endphp
                                         <td>
                                             <button
                                                 class="btn btn-sm 
                                                        {{ $buttonClasses[$inquiry->status] ?? 'btn-light' }} 
                                                        {{ $inquiry->status == 1 ? 'btn-custom-draft' : '' }}
                                                        {{ $inquiry->status == 2 ? 'btn-custom-open' : '' }}
                                                        {{ $inquiry->status == 3 ? 'btn-custom-approve-dept' : '' }}
                                                        {{ $inquiry->status == 4 ? 'btn-custom-approve-sie' : '' }}
                                                        {{ $inquiry->status == 5 ? 'btn-custom-in-progress' : '' }}
                                                        {{ $inquiry->status == 6 ? 'btn-custom-finished' : '' }}
                                                        {{ $inquiry->status == 7 ? 'btn-custom-rejected' : '' }}
                                                        {{ $inquiry->status == 8 ? 'btn-custom-inventory' : '' }}
                                                        {{ $inquiry->status == 9 ? 'btn-custom-confirm-purchasing' : '' }}">
                                                 {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                             </button>
                                         </td>
                                         <td>
                                             {{ $inquiry->last_update ?? 'No updates yet' }}
                                         </td>
                                         {{-- <td class="text-center"> --}}
                                         {{-- <a href="#" class="btn btn-info btn-sm"
                                                        onclick="showProgressHistory({{ $inquiry->id }}); return false;">
                                         History Progress
                                         </a> --}}
                                         {{-- </td> --}}
                                         <td>{{ $inquiry->est_date }}</td>
                                         <td>
                                             {{-- Jika status belum selesai --}}
                                             <a href="#" class="btn btn-custom-in-progress btn-sm"
                                                 onclick="confirmPurchasing({{ $inquiry->id }}); return false;"
                                                 title="Confirm Form">
                                                 <i class="bi bi-hand-index-thumb-fill"></i>
                                             </a>
                                             {{-- Jika status belum selesai --}}
                                             <a href="#" class="btn btn-primary btn-sm"
                                                 onclick="finishInquiry({{ $inquiry->id }}); return false;"
                                                 title="Finish Inquiry">
                                                 <i class="bi bi-emoji-sunglasses-fill"></i>
                                             </a>
                                             <a href="#" class="btn btn-custom-in-progress btn-sm"
                                                 onclick="showProgressModal({{ $inquiry->id }}); return false;"
                                                 title="Update Progress">
                                                 <i class="bi bi-chat-square-text-fill"></i>
                                             </a>
                                             <a href="#" class="btn btn-info btn-sm"
                                                 onclick="showProgressModal1({{ $inquiry->id }}); return false;"
                                                 title="Show Detail Inquiry">
                                                 <i class="bi bi-info-square-fill"></i>
                                             </a>
                                             <a href="#" class="btn btn-custom-show btn-sm"
                                                 onclick="showInquiry({{ $inquiry->id }}); return false;"
                                                 title="Show Form">
                                                 <i class="bi bi-eye-fill"></i>
                                             </a>
                                             <a class="btn btn-primary btn-sm"
                                                 onclick="openEditSupplierModal({{ $inquiry->id }})">
                                                 <i class="bi bi-pencil"></i>
                                             </a>
                                         </td>
                                     </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     </div>
                 </div>
             </section>

             <!-- Modal for Updating Progress -->
             <div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel"
                 aria-hidden="true">
                 <div class="modal-dialog">
                     <div class="modal-content">
                         <div class="modal-header">
                             <h5 class="modal-title" id="progressModalLabel">Update Progress</h5>
                             <button type="button" class="btn-close" data-bs-dismiss="modal"
                                 aria-label="Close"></button>
                         </div>
                         <div class="modal-body">
                             <form id="progressForm">
                                 @csrf
                                 <input type="hidden" id="progressInquiryId" name="inquiry_id">
                                 <div class="mb-3">
                                     <label for="progressDescription" class="form-label">Progress Description</label>
                                     <textarea class="form-control" id="progressDescription" name="progress_description" rows="3" required></textarea>
                                 </div>
                             </form>
                         </div>
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                             <button type="button" class="btn btn-primary" onclick="submitProgress()">Submit</button>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Modal for Viewing Progress History -->
             <div class="modal fade" id="progressHistoryModal1" tabindex="-1"
                 aria-labelledby="progressHistoryModalLabel" aria-hidden="true">
                 <div class="modal-dialog modal-lg modal-dialog-centered">
                     <div class="modal-content">
                         <div class="modal-header">
                             <h5 class="modal-title" id="progressHistoryModalLabel">History Progress</h5>
                             <button type="button" class="btn-close" data-bs-dismiss="modal"
                                 aria-label="Close"></button>
                         </div>
                         <div class="modal-body">
                             <div class="table-responsive">
                                 <table class="table table-1" id="historyTable">
                                     <thead>
                                         <tr>
                                             <th>No</th>
                                             <th>Date</th>
                                             <th>User</th>
                                             <th>Progress Description</th>
                                         </tr>
                                     </thead>
                                     <tbody id="historyBody">
                                         <!-- Data akan ditambahkan melalui AJAX -->
                                     </tbody>
                                 </table>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Modal for Edit Supplier -->
             <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel"
                 aria-hidden="true">
                 <div class="modal-dialog">
                     <div class="modal-content">
                         <div class="modal-header">
                             <h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
                             <button type="button" class="btn-close" data-bs-dismiss="modal"
                                 aria-label="Close"></button>
                         </div>
                         <div class="modal-body">
                             <form id="editSupplierForm">
                                 @csrf
                                 <input type="hidden" id="editInquiryId" name="inquiry_id">
                                 <div class="mb-3">
                                     <label for="editSupplier" class="form-label">Supplier</label>
                                     <select class="form-select" id="editSupplier" name="supplier" required>
                                         <option value="PT. SINAR PUTRA METALINDO">PT. SINAR PUTRA METALINDO</option>
                                         <option value="PT. TRUST STEEL INDO">PT. TRUST STEEL INDO</option>
                                         <option value="PT. LUKWINDO NUSA DWIPA">PT. LUKWINDO NUSA DWIPA</option>
                                         <option value="CV. BAJA MAKMUR">CV. BAJA MAKMUR</option>
                                         <option value="CV. REIHAI ABADI METAL INDONESIA">CV. REIHAI ABADI METAL
                                             INDONESIA</option>
                                         <option value="PT. SAMUDRA BAJA NUSANTARA">PT. SAMUDRA BAJA NUSANTARA</option>
                                         <option value="PT. SURYA SEJAHTERA METALINDO LESTARI">PT. SURYA SEJAHTERA
                                             METALINDO LESTARI</option>
                                         <option value="CV. DIMA RAMA SAKTI">CV.DIMA RAMA SAKTI</option>
                                         <!-- Tambahkan opsi lain jika diperlukan -->
                                     </select>
                                 </div>
                                 <div class="mb-3">
                                     <label for="estDate" class="form-label">Est. Date</label>
                                     <input type="date" class="form-control" id="estDate" name="est_date">
                                 </div>
                                 <div class="modal-footer">
                                     <button type="button" class="btn btn-secondary"
                                         data-bs-dismiss="modal">Close</button>
                                     <button type="submit" class="btn btn-primary">Save changes</button>
                                 </div>
                             </form>
                         </div>
                     </div>
                 </div>
             </div>
             <!-- End Modal -->

         </div>
     </section>

     <!-- jQuery -->
     <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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
         function showInquiry(id) {
             window.location.href = '{{ route('
             formulirInquiry ', '
             ') }}/' + id;
         }

         function showInquiry(id) {
             // Tampilkan detail inquiry dan tambahkan parameter query
             window.location.href = '{{ route('
             showFormSS ', '
             ') }}/' + id + '?source=approval';
         }
     </script>


     <script>
         function showProgressHistory(inquiryId) {
             // Aksi yang dilakukan saat mengklik History Progress
             window.location.href = '{{ route('
             progressHistory ', '
             ') }}/' + inquiryId; // Route yang perlu dibuat
         }

         function showProgressModal(id) {
             $('#progressInquiryId').val(id); // Simpan ID ke dalam modal
             $('#progressModal').modal('show'); // Tampilkan modal

         }

         function showProgressModal1(id) {
             // Tampilkan modal
             $('#progressHistoryModal1').modal('show');

             // Ambil data progress untuk inquiry tersebut
             $.ajax({
                 url: '{{ route('
                 progressHistory ', '
                 ') }}/' + id, // Pastikan route-nya sesuai
                 method: 'GET',
                 success: function(response) {
                     const historyBody = $('#historyBody');
                     historyBody.empty(); // Bersihkan data sebelumnya

                     // Menambahkan data response ke dalam tabel
                     response.progressUpdates.forEach((progress, index) => {
                         historyBody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${new Date(progress.created_at).toLocaleString()}</td>
                        <td>${progress.user.name}</td>
                        <td>${progress.description}</td>
                    </tr>
                `);
                     });
                 },
                 error: function(xhr) {
                     console.error(xhr.responseText);
                     Swal.fire('Error!', 'An error occurred while fetching progress history.', 'error');
                 }
             });
         }

         function submitProgress() {
             var inquiryId = $('#progressInquiryId').val();
             var progressDescription = $('#progressDescription').val();

             $.ajax({
                 url: '{{ route('
                 storeProgressPurchase ') }}', // Route untuk menyimpan progress
                 method: 'POST',
                 data: {
                     _token: '{{ csrf_token() }}',
                     inquiry_id: inquiryId,
                     progress_description: progressDescription
                 },
                 success: function(response) {
                     Swal.fire('Success!', 'Progress updated successfully.', 'success');
                     $('#progressModal').modal('hide'); // Tutup modal
                     location.reload(); // Reload halaman
                 },
                 error: function(xhr) {
                     console.error(xhr.responseText);
                     Swal.fire('Error!', 'An error occurred while updating progress.', 'error');
                 }
             });
         }

         // Function to update the overview table dynamically

         function updateOverviewTable(inquiry) {
             // Logic to update the relevant row in the overview table
             const tableBody = document.getElementById('overviewTable').getElementsByTagName('tbody')[0];

             // Find the row based on inquiry ID (assumed to be already existing in the table)
             let rowToUpdate = Array.from(tableBody.rows).find(row => {
                 return row.cells[1].textContent.includes(inquiry.kode_inquiry);
             });

             if (rowToUpdate) {
                 // Update the specific cells as necessary
                 rowToUpdate.cells[5].textContent = 'On Progress'; // Assuming the 6th cell for the status
                 // Other cells can be updated as needed
             } else {
                 // Optional: If row is not found, you might handle this to log or notify that update failed
                 console.warn('Row not found for inquiry update.');
             }
         }

         function confirmPurchasing(id) {
             // Tampilkan konfirmasi sebelum mengirim permintaan
             if (confirm("Are you sure you want to confirm the purchase?")) {
                 $.ajax({
                     url: '{{ route('
                     confirmPurchase ', '
                     ') }}/' + id,
                     method: 'POST',
                     data: {
                         '_token': '{{ csrf_token() }}' // CSRF token
                     },
                     success: function(response) {
                         // Tampilkan alert dengan SweetAlert saat berhasil
                         Swal.fire('Success!', response.success, 'success').then(() => {
                             location.reload(); // Reload halaman
                         });
                     },
                     error: function(xhr) {
                         console.error(xhr.responseText);
                         Swal.fire('Error!', xhr.responseJSON.error, 'error'); // Tampilkan pesan error
                     }
                 });
             }
         }

         function finishInquiry(id) {
             // Menampilkan konfirmasi sebelum melanjutkan
             Swal.fire({
                 title: 'Are you sure?',
                 text: "This will mark the inquiry as finished.",
                 icon: 'warning',
                 showCancelButton: true,
                 confirmButtonColor: '#3085d6',
                 cancelButtonColor: '#d33',
                 confirmButtonText: 'Yes, finish it!'
             }).then((result) => {
                 if (result.isConfirmed) {
                     // Jika pengguna mengkonfirmasi, lanjutkan dengan AJAX
                     $.ajax({
                         url: '{{ route('
                         finishInquiry ', '
                         ') }}/' + id, // Route untuk finishing inquiry
                         method: 'POST',
                         data: {
                             '_token': '{{ csrf_token() }}'
                         },
                         success: function(response) {
                             Swal.fire('Success!', 'Inquiry marked as finished.', 'success').then(() => {
                                 location.reload(); // Reload halaman untuk melihat update
                             });
                         },
                         error: function(xhr) {
                             console.error(xhr.responseText);
                             Swal.fire('Error!', 'An error occurred while finishing the inquiry.',
                                 'error');
                         }
                     });
                 }
             });
         }

         function openEditSupplierModal(id) {
             // Ambil data inquiry berdasarkan ID
             $.ajax({
                 url: '{{ route('
                 editInquiry ', ['
                 id ' => ': id ']) }}'.replace(':id', id),
                 type: 'GET',
                 success: function(response) {
                     $('#editInquiryId').val(response.id);
                     $('#editSupplier').val(response.supplier); // Isi dropdown supplier
                     $('#editSupplierModal').modal('show'); // Tampilkan modal
                 },
                 error: function(xhr) {
                     console.log(xhr.responseText);
                 }
             });
         }

         // Menangani submit form untuk memperbarui supplier
         $('#editSupplierForm').on('submit', function(e) {
             e.preventDefault(); // Mencegah pengiriman form default
             var inquiryId = $('#editInquiryId').val();
             var supplierData = {
                 supplier: $('#editSupplier').val(),
                 est_date: $('#estDate').val(),
                 _token: '{{ csrf_token() }}' // Sertakan token CSRF
             };

             $.ajax({
                 url: '{{ route('
                 updateSupplier ', ['
                 id ' => ': id ']) }}'.replace(':id', inquiryId),
                 type: 'POST',
                 data: supplierData,
                 success: function(response) {
                     Swal.fire('Success!', 'Supplier updated successfully.', 'success');
                     $('#editSupplierModal').modal('hide'); // Tutup modal
                     location.reload(); // Reload halaman untuk melihat perubahan
                 },
                 error: function(xhr) {
                     console.error(xhr.responseText);
                     Swal.fire('Error!', 'An error occurred while updating the supplier.', 'error');
                 }
             });
         });
     </script>

 </main>
 @endsection



 <!-- jQuery -->
 <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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
     function showInquiry(id) {
         window.location.href = '{{ route('
         formulirInquiry ', '
         ') }}/' + id;
     }

     function showInquiry(id) {
         // Tampilkan detail inquiry dan tambahkan parameter query
         window.location.href = '{{ route('
         showFormSS ', '
         ') }}/' + id + '?source=approval';
     }
 </script>


 <script>
     function showProgressHistory(inquiryId) {
         // Aksi yang dilakukan saat mengklik History Progress
         window.location.href = '{{ route('
         progressHistory ', '
         ') }}/' + inquiryId; // Route yang perlu dibuat
     }

     function showProgressModal(id) {
         $('#progressInquiryId').val(id); // Simpan ID ke dalam modal
         $('#progressModal').modal('show'); // Tampilkan modal

     }

     function showProgressModal1(id) {
         // Tampilkan modal
         $('#progressHistoryModal1').modal('show');

         // Ambil data progress untuk inquiry tersebut
         $.ajax({
             url: '{{ route('
             progressHistory ', '
             ') }}/' + id, // Pastikan route-nya sesuai
             method: 'GET',
             success: function(response) {
                 const historyBody = $('#historyBody');
                 historyBody.empty(); // Bersihkan data sebelumnya

                 // Menambahkan data response ke dalam tabel
                 response.progressUpdates.forEach((progress, index) => {
                     historyBody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${new Date(progress.created_at).toLocaleString()}</td>
                        <td>${progress.user.name}</td>
                        <td>${progress.description}</td>
                    </tr>
                `);
                 });
             },
             error: function(xhr) {
                 console.error(xhr.responseText);
                 Swal.fire('Error!', 'An error occurred while fetching progress history.', 'error');
             }
         });
     }


     function updateOverviewTable(inquiry) {
         // Logic to update the relevant row in the overview table
         const tableBody = document.getElementById('overviewTable').getElementsByTagName('tbody')[0];

         // Find the row based on inquiry ID (assumed to be already existing in the table)
         let rowToUpdate = Array.from(tableBody.rows).find(row => {
             return row.cells[1].textContent.includes(inquiry.kode_inquiry);
         });

         if (rowToUpdate) {
             // Update the specific cells as necessary
             rowToUpdate.cells[5].textContent = 'On Progress'; // Assuming the 6th cell for the status
             // Other cells can be updated as needed
         } else {
             // Optional: If row is not found, you might handle this to log or notify that update failed
             console.warn('Row not found for inquiry update.');
         }
     }

     function confirmPurchasing(id) {
         // Tampilkan konfirmasi sebelum mengirim permintaan
         if (confirm("Are you sure you want to confirm the purchase?")) {
             $.ajax({
                 url: '{{ route('
                 confirmPurchase ', '
                 ') }}/' + id,
                 method: 'POST',
                 data: {
                     '_token': '{{ csrf_token() }}' // CSRF token
                 },
                 success: function(response) {
                     // Tampilkan alert dengan SweetAlert saat berhasil
                     Swal.fire('Success!', response.success, 'success').then(() => {
                         location.reload(); // Reload halaman
                     });
                 },
                 error: function(xhr) {
                     console.error(xhr.responseText);
                     Swal.fire('Error!', xhr.responseJSON.error, 'error'); // Tampilkan pesan error
                 }
             });
         }
     }

     function showCombinedModal(id) {
         // Set inquiry_id
         document.getElementById('inquiryId').value = id;

         // Tampilkan modal
         var myModal = new bootstrap.Modal(document.getElementById('combinedModal'), {});
         myModal.show();
     }

     function submitCombinedForm() {
         const form = document.getElementById('combinedForm');
         const formData = new FormData(form);

         // Kirim data untuk update supplier dan progress
         $.ajax({
             url: '{{ route('
             storeProgressPurchase ') }}', // mengarah ke fungsi storeProgressPurchase
             method: 'POST',
             data: formData,
             processData: false,
             contentType: false,
             success: function(response) {
                 Swal.fire('Success!', response.message, 'success').then(() => {
                     location.reload(); // Reload halaman
                 });
             },
             error: function(xhr) {
                 console.error(xhr.responseText);
                 Swal.fire('Error!', xhr.responseJSON.error, 'error'); // Tampilkan pesan error
             }
         });
     }

     function finishInquiry(id) {
         // Menampilkan konfirmasi sebelum melanjutkan
         Swal.fire({
             title: 'Are you sure?',
             text: "This will mark the inquiry as finished.",
             icon: 'warning',
             showCancelButton: true,
             confirmButtonColor: '#3085d6',
             cancelButtonColor: '#d33',
             confirmButtonText: 'Yes, finish it!'
         }).then((result) => {
             if (result.isConfirmed) {
                 // Jika pengguna mengkonfirmasi, lanjutkan dengan AJAX
                 $.ajax({
                     url: '{{ route('
                     finishInquiry ', '
                     ') }}/' + id, // Route untuk finishing inquiry
                     method: 'POST',
                     data: {
                         '_token': '{{ csrf_token() }}'
                     },
                     success: function(response) {
                         Swal.fire('Success!', 'Inquiry marked as finished.', 'success').then(() => {
                             location.reload(); // Reload halaman untuk melihat update
                         });
                     },
                     error: function(xhr) {
                         console.error(xhr.responseText);
                         Swal.fire('Error!', 'An error occurred while finishing the inquiry.',
                             'error');
                     }
                 });
             }
         });
     }



     // Menangani submit form untuk memperbarui supplier
     $('#editSupplierForm').on('submit', function(e) {
         e.preventDefault(); // Mencegah pengiriman form default
         var inquiryId = $('#editInquiryId').val();
         var supplierData = {
             supplier: $('#editSupplier').val(),
             est_date: $('#estDate').val(),
             _token: '{{ csrf_token() }}' // Sertakan token CSRF
         };

         $.ajax({
             url: '{{ route('
             updateSupplier ', ['
             id ' => ': id ']) }}'.replace(':id', inquiryId),
             type: 'POST',
             data: supplierData,
             success: function(response) {
                 Swal.fire('Success!', 'Supplier updated successfully.', 'success');
                 $('#editSupplierModal').modal('hide'); // Tutup modal
                 location.reload(); // Reload halaman untuk melihat perubahan
             },
             error: function(xhr) {
                 console.error(xhr.responseText);
                 Swal.fire('Error!', 'An error occurred while updating the supplier.', 'error');
             }
         });
     });
 </script>