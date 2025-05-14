<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Astra Daido Steel Indonesia</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ asset('assets/img/logo-menu.png') }}" rel="icon">
    <link href="{{ asset('assets/img/logo-menu.png') }}" rel="apple-touch-icon">
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
            width: 2.5rem;
            height: 2.5rem;
            box-shadow: 1px 2px 2px rgba(247, 247, 247, 0.767);
        }

        @media (max-width: 995px) {
            .logo-img {
                width: 2.5rem;
                height: 2.5rem;
                margin-right: 0.5rem;
                box-shadow: 1px 2px 2px rgba(247, 247, 247, 0.767);
            }

            .navbar-nav .nav-link.active,
            .navbar-nav .nav-link.show {
                color: #fffb00;
            }
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

        .navbar-nav .nav-link.active,
        .navbar-nav .nav-link.show {
            color: #fffb00;
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
                <button class="navbar-toggler bg-light mb-2 mt-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon bg-light"></span>
                </button>
                <img src="{{ asset('assets/img/logo-adasi.png') }}" alt="" class="rounded-circle logo-img">
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <!-- Dropdown Kelola Akun -->
                        @if (in_array(Auth::user()->role_id, [1]))
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown1"
                                    role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown2"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Dashboard
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown2">
                                @php
                                    $allowedUsers = [
                                        'ADMINSTRATOR',
                                        'ANDI SIMPONI',
                                        'MUGI PRAMONO',
                                        'RANGGA FADILLAH',
                                        'VITRI HANDAYANI',
                                        'ADHI PRASETIYO',
                                        'AHMAD RIDWAN',
                                        'ANDIK TOTOK SISWOYO',
                                        'ARY RODJO PRASETYO',
                                        'DANIA ISNAWATI',
                                        'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                                        'DWI KUNTORO',
                                        'HARDI SAPUTRA',
                                        'HUSEIN ABDULLAH',
                                        'ILHAM CHOLID',
                                        'JESSICA PAUNE',
                                        'JUN JOHAMIN PD',
                                        'KUSTIONO',
                                        'LINA UNIARSIH',
                                        'M. RIDWAN GUNAWAN',
                                        'MARTINUS CAHYO RAHASTO',
                                        'MUHAMMAD MAHBUB',
                                        'NUR DWITA SURA WIJAYA',
                                        'PUTRI ANINDIA',
                                        'RAGIL ISHA RAHMANTO',
                                        'RICHARDUS',
                                        'RISFAN FAISAL',
                                        'SENDY PRABOWO',
                                        'SITI MARIA ULFA',
                                        'WULYO EKO PRASETYO',
                                        'YAN WELEM MANGINSELA',
                                        'YULMAI RIDO WINANDA',
                                        'YUNASIS PALGUNADI',
                                        'ABDUR RAHMAN AL FAAIZ',
                                        'SONY STIAWAN',
                                        'HERLIANA',
                                    ];
                                @endphp

                                @if (in_array(auth()->user()->name, $allowedUsers))
                                    <li><a class="dropdown-item"
                                            href="{{ route('dashboardMaintenance') }}">Maintenance</a></li>
                                    <li><a class="dropdown-item" href="{{ route('dshandling') }}">Handling Klaim dan
                                            Komplain</a></li>
                                    <li><a class="dropdown-item" href="{{ route('dsCompetency') }}">People
                                            Development</a></li>
                                @endif
                                @php
                                    $allowedUsers = [
                                        'ADMINSTRATOR',
                                        'AFILIANDI',
                                        'AGUNG PANGESTU YUSUF',
                                        'AGUS PRIYANTO',
                                        'AGUS ROSIDIN',
                                        'ANDI SANTOSO',
                                        'ANDI SIMPONI',
                                        'ARRY SOEBHEKTI',
                                        'AWING',
                                        'DASUKI',
                                        'DEDY SETIAWAN',
                                        'DIAMAN DARMAWINATA',
                                        'ELI HANDOYO',
                                        'FAIZAL AFDAU',
                                        'FATUL MUKMIN',
                                        'HAERUL IKHSAN',
                                        'HENDRIO',
                                        'JAKA RARA SUKMA',
                                        'JAKARIA',
                                        'KARYA WIJAYA',
                                        'LUKMAN AHMAD',
                                        'MAMIK ABIDIN',
                                        'MEDI KRISNANTO',
                                        'MIFTAKHUROHMAN',
                                        'MUGI PRAMONO',
                                        'NUR SUPRIYANTO',
                                        'NURSAID',
                                        'NURSALIM',
                                        'R.WAWAN HIMAWAN',
                                        'RAHMAT NUGROHO',
                                        'RANGGA FADILLAH',
                                        'RIZKY ANDREA RAHMAWAN',
                                        'RUKMAN',
                                        'RUSITO',
                                        'SABAR WASIRAN',
                                        'SEPTIADI PRATOMO',
                                        'SUDIYATNO',
                                        'UMAR HADI',
                                        'VITRI HANDAYANI',
                                        'YANUARDIN SALEH SIREGAR',
                                        'YUSUF SYAFAAT',
                                        'ADHI PRASETIYO',
                                        'AHMAD RIDWAN',
                                        'ANDIK TOTOK SISWOYO',
                                        'ARY RODJO PRASETYO',
                                        'AVI SHENNA',
                                        'BANGUN SUTOPO',
                                        'CECEP ISKANDAR',
                                        'DANIA ISNAWATI',
                                        'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                                        'DWI KUNTORO',
                                        'FIKRI SYAHBANA',
                                        'FRISILIA CLAUDIA HUTAMA',
                                        'GUNAWAN',
                                        'HARDI SAPUTRA',
                                        'HARRY SUPRIYADI',
                                        'HERLIANA',
                                        'HERY HERMAWAN',
                                        'HUSEIN ABDULLAH',
                                        'ILHAM CHOLID',
                                        'ILHAM SETIA DARMA',
                                        'IMAM PRASETYO',
                                        'IMAM SOPYAN',
                                        'JEFRY WASTON E',
                                        'JESSICA PAUNE',
                                        'JONI SETIAWAN',
                                        'JUN JOHAMIN PD',
                                        'KUSTIONO',
                                        'LINA UNIARSIH',
                                        'M. RIDWAN GUNAWAN',
                                        'MARTINUS CAHYO RAHASTO',
                                        'MOCHAMMAD ANDRIANSYAH',
                                        'MOHAMMAD FATKHURROHMAN',
                                        'MUHAMMAD DINAR FARISI',
                                        'MUHAMMAD MAHBUB',
                                        'NUR DWITA SURA WIJAYA',
                                        'PUTRI ANINDIA',
                                        'RAGIL ISHA RAHMANTO',
                                        'RIADUS SOLIHIN',
                                        'RICHARDUS',
                                        'RISFAN FAISAL',
                                        'RUSLAN M.ALI',
                                        'SENDY PRABOWO',
                                        'SETIYAWAN',
                                        'SITI MARIA ULFA',
                                        'SUKIMIN',
                                        'WULYO EKO PRASETYO',
                                        'YAN WELEM MANGINSELA',
                                        'YUDHI PRASETYO RAHMAWANTO',
                                        'YULMAI RIDO WINANDA',
                                        'YUNASIS PALGUNADI',
                                        'ZAENAL ARIFIN',
                                        'ABDUR RAHMAN AL FAAIZ',
                                        'YAN WALEM MANGINSELA',
                                        'VIVIAN ANGELIKA',
                                        'SONY STIAWAN',
                                        'FAJAR BAGASKARA',
                                    ];
                                @endphp

                                @if (in_array(auth()->user()->name, $allowedUsers))
                                    <li><a class="dropdown-item" href="{{ route('dashboardSS') }}">Sumbang Saran</a>
                                    </li>
                                    <li><a class="dropdown-item" href="{{ route('dsKnowlege') }}">Knowledge
                                            Management</a></li>
                                @endif

                                @php
                                    $allowedUsers = [
                                        'ADMINSTRATOR',
                                        'MEDI KRISNANTO',
                                        'JESSICA PAUNE',
                                        'MARTINUS CAHYO RAHASTO',
                                        'VIVIAN ANGELIKA',
                                    ];
                                @endphp
                                @if (in_array(auth()->user()->name, $allowedUsers))
                                    <li><a class="dropdown-item" href="{{ route('dashboardFPB') }}">Pengajuan
                                            Barang</a></li>
                                @endif

                                {{-- <li><a class="dropdown-item" href="{{ route('reportpatrol') }}">Safety Patrol</a>
                                </li> --}}
                            </ul>
                        </li>
                        <!-- Dropdown Sales -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Sales
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Form Permintaan Perbaikan
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @php
                                            $allowedUsers = [
                                                'ADMINSTRATOR',
                                                'MAMIK ABIDIN',
                                                'MUGI PRAMONO',
                                                'RANGGA FADILLAH',
                                                'RUSITO',
                                                'SUDIYATNO',
                                                'ARY RODJO PRASETYO',
                                                'BANGUN SUTOPO',
                                                'JESSICA PAUNE',
                                                'RAGIL ISHA RAHMANTO',
                                                'ZAENAL ARIFIN',
                                                'ABDUR RAHMAN AL FAAIZ',
                                                'SONY STIAWAN',
                                            ];
                                        @endphp

                                        @if (in_array(auth()->user()->name, $allowedUsers))
                                            <li><a class="dropdown-item" href="{{ route('sales.index') }}">Data Form
                                                    Perbaikan</a></li>
                                        @endif

                                        @php
                                            $allowedUsers = [
                                                'ADMINSTRATOR',
                                                'MAMIK ABIDIN',
                                                'MUGI PRAMONO',
                                                'RANGGA FADILLAH',
                                                'RUSITO',
                                                'SUDIYATNO',
                                                'ARY RODJO PRASETYO',
                                                'BANGUN SUTOPO',
                                                'JESSICA PAUNE',
                                                'RAGIL ISHA RAHMANTO',
                                                'ZAENAL ARIFIN',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ];
                                        @endphp

                                        @if (in_array(auth()->user()->name, $allowedUsers))
                                            <li><a class="dropdown-item" href="{{ route('fpps.history') }}">Riwayat
                                                    Form Perbaikan</a></li>
                                        @endif

                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Handling Klaim dan Komplain
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @php
                                            $allowedUsers = [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'RANGGA FADILLAH',
                                                'ADHI PRASETIYO',
                                                'AHMAD RIDWAN',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'AVI SHENNA',
                                                'DANIA ISNAWATI',
                                                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                                                'DWI KUNTORO',
                                                'FIKRI SYAHBANA',
                                                'GUNAWAN',
                                                'HARDI SAPUTRA',
                                                'HUSEIN ABDULLAH',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JONI SETIAWAN',
                                                'JUN JOHAMIN PD',
                                                'LINA UNIARSIH',
                                                'M. RIDWAN GUNAWAN',
                                                'MARTINUS CAHYO RAHASTO',
                                                'MOHAMMAD FATKHURROHMAN',
                                                'NUR DWITA SURA WIJAYA',
                                                'PUTRI ANINDIA',
                                                'RIADUS SOLIHIN',
                                                'RICHARDUS',
                                                'RISFAN FAISAL',
                                                'RUSLAN M.ALI',
                                                'SENDY PRABOWO',
                                                'SUKIMIN',
                                                'WULYO EKO PRASETYO',
                                                'YAN WELEM MANGINSELA',
                                                'YUDHI PRASETYO RAHMAWANTO',
                                                'YULMAI RIDO WINANDA',
                                                'YUNASIS PALGUNADI',
                                                'SONY STIAWAN',
                                            ];
                                        @endphp

                                        @if (in_array(auth()->user()->name, $allowedUsers))
                                            <li><a class="dropdown-item" href="{{ route('index') }}">Form Pengajuan
                                                    Klaim/Komplain</a></li>
                                        @endif

                                        @php
                                            $allowedUsers = [
                                                'ADMINSTRATOR',
                                                'ADHI PRASETIYO',
                                                'AHMAD RIDWAN',
                                                'ANDIK TOTOK SISWOYO',
                                                'DANIA ISNAWATI',
                                                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                                                'DWI KUNTORO',
                                                'GUNAWAN',
                                                'HUSEIN ABDULLAH',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'LINA UNIARSIH',
                                                'M. RIDWAN GUNAWAN',
                                                'MARTINUS CAHYO RAHASTO',
                                                'NUR DWITA SURA WIJAYA',
                                                'PUTRI ANINDIA',
                                                'RICHARDUS',
                                                'RISFAN FAISAL',
                                                'SENDY PRABOWO',
                                                'WULYO EKO PRASETYO',
                                                'YAN WELEM MANGINSELA',
                                                'YUDHI PRASETYO RAHMAWANTO',
                                                'YULMAI RIDO WINANDA',
                                                'YUNASIS PALGUNADI',
                                                'Sony Stiawan',
                                            ];
                                        @endphp

                                        @if (in_array(auth()->user()->name, $allowedUsers))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showHistoryCLaimComplain') }}">Riwayat
                                                    Klaim/Komplain</a></li>
                                        @endif

                                        @php
                                            $allowedUsers = [
                                                'ADMINSTRATOR',
                                                'ADHI PRASETIYO',
                                                'AHMAD RIDWAN',
                                                'ANDIK TOTOK SISWOYO',
                                                'DANIA ISNAWATI',
                                                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                                                'DWI KUNTORO',
                                                'GUNAWAN',
                                                'HUSEIN ABDULLAH',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'LINA UNIARSIH',
                                                'M. RIDWAN GUNAWAN',
                                                'MARTINUS CAHYO RAHASTO',
                                                'NUR DWITA SURA WIJAYA',
                                                'PUTRI ANINDIA',
                                                'RICHARDUS',
                                                'RISFAN FAISAL',
                                                'SENDY PRABOWO',
                                                'WULYO EKO PRASETYO',
                                                'YAN WELEM MANGINSELA',
                                                'YUDHI PRASETYO RAHMAWANTO',
                                                'YULMAI RIDO WINANDA',
                                                'YUNASIS PALGUNADI',
                                                'Sony Stiawan',
                                            ];
                                        @endphp

                                        @if (in_array(auth()->user()->name, $allowedUsers))
                                            <li><a class="dropdown-item" href="{{ route('scheduleVisit') }}">Jadwal
                                                    Kunjungan</a></li>
                                        @endif

                                    </ul>
                                </li>
                                @php
                                    $allowedUsers = [
                                        'ADMINSTRATOR',
                                        'ADHI PRASETIYO',
                                        'AHMAD RIDWAN',
                                        'ANDIK TOTOK SISWOYO',
                                        'DANIA ISNAWATI',
                                        'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                                        'DWI KUNTORO',
                                        'GUNAWAN',
                                        'HUSEIN ABDULLAH',
                                        'ILHAM CHOLID',
                                        'JESSICA PAUNE',
                                        'JUN JOHAMIN PD',
                                        'LINA UNIARSIH',
                                        'M. RIDWAN GUNAWAN',
                                        'MARTINUS CAHYO RAHASTO',
                                        'NUR DWITA SURA WIJAYA',
                                        'PUTRI ANINDIA',
                                        'RICHARDUS',
                                        'RISFAN FAISAL',
                                        'SENDY PRABOWO',
                                        'WULYO EKO PRASETYO',
                                        'YAN WELEM MANGINSELA',
                                        'YUDHI PRASETYO RAHMAWANTO',
                                        'YULMAI RIDO WINANDA',
                                        'YUNASIS PALGUNADI',
                                        'Sony Stiawan',
                                    ];
                                @endphp

                                @if (in_array(auth()->user()->name, $allowedUsers))
                                    <li>
                                        <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Inquiry Status
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                            <li><a class="dropdown-item" href="{{ route('createinquiry') }}">Form
                                                    Inquiry Material</a></li>
                                        </ul>
                                    </li>
                                @endif
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Custom Request
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('showCustomRequest') }}">Custom
                                                Request</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('showApproveMarketing') }}">Persetujuan Marketing</a>
                                        </li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('showApproveFinance') }}">Persetujuan Finance</a></li>
                                    </ul>
                                </li>

                            </ul>
                        </li>
                        <!-- Dropdown Productions -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Productions
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                @php
                                    $allowedUsers = [
                                        'ADMINSTRATOR',
                                        'MAMIK ABIDIN',
                                        'MUGI PRAMONO',
                                        'RANGGA FADILLAH',
                                        'RUSITO',
                                        'SUDIYATNO',
                                        'ARY RODJO PRASETYO',
                                        'BANGUN SUTOPO',
                                        'JESSICA PAUNE',
                                        'RAGIL ISHA RAHMANTO',
                                        'ZAENAL ARIFIN',
                                        'ABDUR RAHMAN AL FAAIZ',
                                        'Sony Stiawan',
                                    ];
                                @endphp

                                @if (in_array(auth()->user()->name, $allowedUsers))
                                    <li><a class="dropdown-item" href="{{ route('fpps.index') }}">Form Permintaan
                                            Perbaikan</a></li>
                                @endif

                                @php
                                    $allowedUsers = [
                                        'ADMINSTRATOR',
                                        'MAMIK ABIDIN',
                                        'MUGI PRAMONO',
                                        'RANGGA FADILLAH',
                                        'RUSITO',
                                        'SUDIYATNO',
                                        'ARY RODJO PRASETYO',
                                        'BANGUN SUTOPO',
                                        'JESSICA PAUNE',
                                        'RAGIL ISHA RAHMANTO',
                                        'ZAENAL ARIFIN',
                                        'ABDUR RAHMAN AL FAAIZ',
                                    ];
                                @endphp
                                @if (in_array(auth()->user()->name, $allowedUsers))
                                    <li><a class="dropdown-item" href="{{ route('fpps.history') }}">Riwayat
                                            Permintaan Perbaikan</a></li>
                                @endif
                                <li>

                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Bagian Maintenance
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @php
                                            $allowedUsers = [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'VITRI HANDAYANI',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'JESSICA PAUNE',
                                                'MARTINUS CAHYO RAHASTO',
                                                'SITI MARIA ULFA',
                                                'YULMAI RIDO WINANDA',
                                            ];
                                        @endphp
                                        @if (in_array(auth()->user()->name, $allowedUsers))
                                            <li><a class="dropdown-item" href="{{ route('dashboardmesins') }}">Kelola
                                                    DMI</a></li>
                                            <li><a class="dropdown-item"
                                                    href="{{ route('deptmtce.index') }}">Persetujuan FPP</a></li>
                                        @endif

                                        @php
                                            $fppsHistoryUsers = [
                                                'ADMINSTRATOR',
                                                'ANDI SIMPONI',
                                                'MUGI PRAMONO',
                                                'RANGGA FADILLAH',
                                                'ADHI PRASETIYO',
                                                'ARY RODJO PRASETYO',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'KUSTIONO',
                                                'RAGIL ISHA RAHMANTO',
                                                'RICHARDUS',
                                                'SITI MARIA ULFA',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ];

                                            $preventiveTableUsers = [
                                                'ADMINSTRATOR',
                                                'ANDI SIMPONI',
                                                'MUGI PRAMONO',
                                                'RANGGA FADILLAH',
                                                'ADHI PRASETIYO',
                                                'ARY RODJO PRASETYO',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'KUSTIONO',
                                                'RAGIL ISHA RAHMANTO',
                                                'RICHARDUS',
                                                'SITI MARIA ULFA',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ];
                                        @endphp

                                        @if (in_array(auth()->user()->name, $fppsHistoryUsers))
                                            <li><a class="dropdown-item" href="{{ route('fpps.history') }}">Riwayat
                                                    Form Perbaikan</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, $preventiveTableUsers))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('dashboardPreventive') }}">Tabel Preventif</a></li>
                                        @endif

                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Bagian Engineering
                                    </a>
                                    @php
                                        $formTindakLanjutUsers = [
                                            'ADMINSTRATOR',
                                            'MUGI PRAMONO',
                                            'ARY RODJO PRASETYO',
                                            'JESSICA PAUNE',
                                        ];

                                        $riwayatKlaimKomplainUsers = [
                                            'ADMINSTRATOR',
                                            'MUGI PRAMONO',
                                            'ARY RODJO PRASETYO',
                                            'JESSICA PAUNE',
                                        ];

                                        $jadwalKunjunganUsers = [
                                            'ADMINSTRATOR',
                                            'MUGI PRAMONO',
                                            'ARY RODJO PRASETYO',
                                            'JESSICA PAUNE',
                                        ];
                                    @endphp

                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @if (in_array(auth()->user()->name, $formTindakLanjutUsers))
                                            <li><a class="dropdown-item" href="{{ route('submission') }}">Form Tindak
                                                    Lanjut</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, $riwayatKlaimKomplainUsers))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showHistoryCLaimComplain') }}">Riwayat Klaim &
                                                    Komplain</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, $jadwalKunjunganUsers))
                                            <li><a class="dropdown-item" href="{{ route('scheduleVisit') }}">Jadwal
                                                    Kunjungan</a></li>
                                        @endif
                                    </ul>

                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Maintenance Korektif
                                    </a>
                                    @php
                                        $terimaFormPerbaikanUsers = [
                                            'ADMINSTRATOR',
                                            'ANDI SIMPONI',
                                            'MUGI PRAMONO',
                                            'RANGGA FADILLAH',
                                            'VITRI HANDAYANI',
                                            'ADHI PRASETIYO',
                                            'ANDIK TOTOK SISWOYO',
                                            'ARY RODJO PRASETYO',
                                            'HARDI SAPUTRA',
                                            'ILHAM CHOLID',
                                            'JESSICA PAUNE',
                                            'JUN JOHAMIN PD',
                                            'KUSTIONO',
                                            'MARTINUS CAHYO RAHASTO',
                                            'RAGIL ISHA RAHMANTO',
                                            'RICHARDUS',
                                            'SITI MARIA ULFA',
                                            'YULMAI RIDO WINANDA',
                                            'ABDUR RAHMAN AL FAAIZ',
                                        ];

                                        $riwayatFormPerbaikanUsers = [
                                            'ADMINSTRATOR',
                                            'ANDI SIMPONI',
                                            'MUGI PRAMONO',
                                            'RANGGA FADILLAH',
                                            'ADHI PRASETIYO',
                                            'ARY RODJO PRASETYO',
                                            'ILHAM CHOLID',
                                            'JESSICA PAUNE',
                                            'JUN JOHAMIN PD',
                                            'KUSTIONO',
                                            'RAGIL ISHA RAHMANTO',
                                            'RICHARDUS',
                                            'SITI MARIA ULFA',
                                            'ABDUR RAHMAN AL FAAIZ',
                                        ];
                                    @endphp

                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @if (in_array(auth()->user()->name, $terimaFormPerbaikanUsers))
                                            <li><a class="dropdown-item"
                                                    href="{{ asset('dashboardmaintenance') }}">Terima Form
                                                    Perbaikan</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, $riwayatFormPerbaikanUsers))
                                            <li><a class="dropdown-item" href="{{ route('fpps.history') }}">Riwayat
                                                    Form Perbaikan</a></li>
                                        @endif
                                    </ul>

                                </li>
                                @php
                                    $maintenancePreventifUsers = [
                                        'ADMINSTRATOR',
                                        'ANDI SIMPONI',
                                        'MUGI PRAMONO',
                                        'RANGGA FADILLAH',
                                        'ADHI PRASETIYO',
                                        'ARY RODJO PRASETYO',
                                        'ILHAM CHOLID',
                                        'JESSICA PAUNE',
                                        'JUN JOHAMIN PD',
                                        'KUSTIONO',
                                        'RAGIL ISHA RAHMANTO',
                                        'RICHARDUS',
                                        'SITI MARIA ULFA',
                                        'ABDUR RAHMAN AL FAAIZ',
                                    ];
                                @endphp

                                @if (in_array(auth()->user()->name, $maintenancePreventifUsers))
                                    <li>
                                        <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Maintenance Preventif
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                            <li><a class="dropdown-item"
                                                    href="{{ route('dashboardPreventiveMaintenance') }}">Jadwal
                                                    Preventif</a></li>
                                        </ul>
                                    </li>
                                @endif

                            </ul>
                        </li>
                        <!-- Dropdown Procurement -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Procurement
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="{{ route('overviewfpb') }}">Overview FPB</a></li>
                                @if (in_array(Auth::user()->name, [
                                        'ADMINSTRATOR',
                                        'DIAMAN DARMAWINATA',
                                        'FAIZAL AFDAU',
                                        'MEDI KRISNANTO',
                                        'MUGI PRAMONO',
                                        'NURSALIM',
                                        'RANGGA FADILLAH',
                                        'ADHI PRASETIYO',
                                        'DANIA ISNAWATI',
                                        'DWI KUNTORO',
                                        'FRISILIA CLAUDIA HUTAMA',
                                        'HARRY SUPRIYADI',
                                        'HERLIANA',
                                        'HERY HERMAWAN',
                                        'HUSEIN ABDULLAH',
                                        'ILHAM CHOLID',
                                        'JESSICA PAUNE',
                                        'JUN JOHAMIN PD',
                                        'LINA UNIARSIH',
                                        'MARTINUS CAHYO RAHASTO',
                                        'MUHAMMAD DINAR FARISI',
                                        'PUTRI ANINDIA',
                                        'RAGIL ISHA RAHMANTO',
                                        'RICHARDUS',
                                        'RISFAN FAISAL',
                                        'SENDY PRABOWO',
                                        'SITI MARIA ULFA',
                                        'WULYO EKO PRASETYO',
                                        'YAN WELEM MANGINSELA',
                                        'YUNASIS PALGUNADI',
                                        'ABDUR RAHMAN AL FAAIZ',
                                        'VIVIAN ANGELIKA',
                                        'Sony Stiawan',
                                        'FAJAR BAGASKARA',
                                        'M. IQBAL',
                                        'SARAH EGA BUDI ASTUTI',
                                        'YULMAI RIDO WINANDA',
                                        'HARDI SAPUTRA',
                                    ]))
                                    <li><a class="dropdown-item" href="{{ route('index.PO') }}">Form Pengajuan
                                            Barang/Jasa</a></li>
                                @endif

                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Persetujuan Form
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @if (in_array(Auth::user()->name, [
                                                'ADMINSTRATOR',
                                                'MEDI KRISNANTO',
                                                'JESSICA PAUNE',
                                                'MUHAMMAD DINAR FARISI',
                                                'SITI MARIA ULFA',
                                                'NURSALIM',
                                                'MARTINUS CAHYO RAHASTO',
                                            ]))
                                            <li><a class="dropdown-item" href="{{ route('index.PO.user') }}">User
                                                    Section</a></li>
                                        @endif
                                        @if (in_array(Auth::user()->name, [
                                                'ADMINSTRATOR',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'JESSICA PAUNE',
                                                'MARTINUS CAHYO RAHASTO',
                                                'YULMAI RIDO WINANDA',
                                                'ADHI PRASETIYO',
                                                'RICHARDUS',
                                                'ILHAM CHOLID',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ]))
                                            <li><a class="dropdown-item" href="{{ route('index.PO.Dept') }}">Ka.
                                                    Dept</a></li>
                                        @endif
                                        @if (in_array(Auth::user()->name, [
                                                'ADMINSTRATOR',
                                                'ADHI PRASETIYO',
                                                'JESSICA PAUNE',
                                                'MARTINUS CAHYO RAHASTO',
                                                'RICHARDUS',
                                            ]))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('index.PO.finance') }}">Finance Section</a></li>
                                        @endif
                                        @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'JESSICA PAUNE', 'VIVIAN ANGELIKA', 'FAJAR BAGASKARA']))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('index.PO.procurement') }}">Procurement Menu 1</a>
                                            </li>
                                        @endif
                                        @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'JESSICA PAUNE', 'VIVIAN ANGELIKA', 'FAJAR BAGASKARA']))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('index.PO.procurement2') }}">Procurement Menu 2</a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>

                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Panawaran Subcont Project Sales
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'JESSICA PAUNE', 'RAGIL ISHA RAHMANTO', 'SARAH EGA BUDI ASTUTI']))
                                            <li><a class="dropdown-item" href="{{ route('indexSales') }}">Form
                                                    Penawaran Subcont</a></li>
                                        @endif
                                        @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'JESSICA PAUNE', 'VIVIAN ANGELIKA', 'FAJAR BAGASKARA']))
                                            <li><a class="dropdown-item" href="{{ route('indexProc') }}">Persetujuan
                                                    Subcont</a></li>
                                        @endif
                                    </ul>
                                </li>

                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Inquiry Order Local
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @if (in_array(Auth::user()->name, [
                                                'ADMINSTRATOR',
                                                'ANDIK TOTOK SISWOYO',
                                                'DANIA ISNAWATI',
                                                'DWI KUNTORO',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'LINA UNIARSIH',
                                                'MARTINUS CAHYO RAHASTO',
                                                'RISFAN FAISAL',
                                                'SENDY PRABOWO',
                                                'WULYO EKO PRASETYO',
                                                'YAN WELEM MANGINSELA',
                                                'YULMAI RIDO WINANDA',
                                                'YUNASIS PALGUNADI',
                                                'SARAH EGA BUDI ASTUTI',
                                                'DIMAS ADITYA PRIANDANA',
                                                'HEXAPA DARMADI',
                                                'HERY HERMAWAN',
                                                'WULYO EKO PRASETYO',
                                                'SONY STIAWAN',
                                                'FISKA CHRISMAS YUDHA',
                                            ]))
                                            <li><a class="dropdown-item" href="{{ route('createinquiry') }}">
                                                    Form Inquiry Local</a></li>
                                        @endif
                                        @if (in_array(Auth::user()->name, [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'ANDIK TOTOK SISWOYO',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                            ]))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showApprovalKaSie') }}">Persetujuan Ka. Sie</a>
                                            </li>
                                        @endif
                                        @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'ANDIK TOTOK SISWOYO', 'JESSICA PAUNE', 'YULMAI RIDO WINANDA']))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showApprovalKaDept') }}">Persetujuan Ka. Dept</a>
                                            </li>
                                        @endif
                                        @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'M. IQBAL']))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showApprovalInventory') }}">Persetujuan
                                                    Inventory</a></li>
                                        @endif
                                        @if (in_array(Auth::user()->name, ['ADMINSTRATOR', 'JESSICA PAUNE', 'VIVIAN ANGELIKA', 'M. IQBAL']))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('overviewPurchase') }}">Overview Purchase</a></li>
                                        @endif
                                        <li><a class="dropdown-item" href="{{ route('overviewInquiry') }}">Overview
                                                Inquiry Order Local</a></li>
                                    </ul>
                                </li>

                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Inquiry Order Import
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('createinquiryImport') }}">Form
                                                Inquiry Import</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('showApprovalInventoryImport') }}">Persetujuan
                                                Inventory</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('overviewPurchaseImport') }}">Overview Purchasing
                                                Import</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('overviewInquiryImport') }}">Overview Inquiry
                                                Import</a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Import Administration
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        <li><a class="dropdown-item" href="{{ route('createadministration') }}">Form
                                                Administration Purchasing</a></li>
                                        <li><a class="dropdown-item" href="{{ route('createadministration') }}">Form
                                                Administration Admin</a></li>
                                    </ul>
                                </li>

                            </ul>

                        </li>
                        <!-- Dropdown Human Resource -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Human Resource
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Pengajuan Form Knowledge Management
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @if (in_array(auth()->user()->name, [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'YULMAI RIDO WINANDA',
                                                'SITI MARIA ULFA',
                                                'JESSICA PAUNE',
                                                'ADHI PRASETIYO',
                                                'ANDIK TOTOK SISWOYO',
                                                'RICHARDUS',
                                            ]))
                                            <li><a class="dropdown-item" href="{{ route('pengajuanKM') }}">Form
                                                    Knowledge Management</a></li>
                                            <li><a class="dropdown-item"
                                                    href="{{ route('persetujuanKM') }}">Persetujuan Knowledge
                                                    Management</a></li>
                                        @endif
                                    </ul>
                                </li>

                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Base Competency
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @if (in_array(auth()->user()->name, ['ADMINSTRATOR', 'JESSICA PAUNE', 'SITI MARIA ULFA']))
                                            <li><a class="dropdown-item" href="{{ route('jobShow') }}">Form Job
                                                    Position</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, [
                                                'ADMINSTRATOR',
                                                'YULMAI RIDO WINANDA',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'JESSICA PAUNE',
                                                'MARTINUS CAHYO RAHASTO',
                                                'SITI MARIA ULFA',
                                                'RICHARDUS',
                                                'MUGI PRAMONO',
                                                'ABDUR RAHMAN AL FAAIZ',
                                                'RAGIL ISHA RAHMANTO',
                                            ]))
                                            <li><a class="dropdown-item" href="{{ route('tcShow') }}">Form Pengajuan
                                                    Competency</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, [
                                                'ADMINSTRATOR',
                                                'YULMAI RIDO WINANDA',
                                                'MUGI PRAMONO',
                                                'ADHI PRASETIYO',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'MARTINUS CAHYO RAHASTO',
                                                'RAGIL ISHA RAHMANTO',
                                                'RICHARDUS',
                                                'SITI MARIA ULFA',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ]))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('penilaian.index') }}">Penilaian Technical
                                                    Competency Ka. Sie</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'YULMAI RIDO WINANDA',
                                                'ADHI PRASETIYO',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'MARTINUS CAHYO RAHASTO',
                                                'RAGIL ISHA RAHMANTO',
                                                'RICHARDUS',
                                                'SITI MARIA ULFA',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ]))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('penilaian.index') }}">Penilaian Technical
                                                    Competency Ka. Dept</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'YULMAI RIDO WINANDA',
                                                'ADHI PRASETIYO',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'MARTINUS CAHYO RAHASTO',
                                                'RAGIL ISHA RAHMANTO',
                                                'RICHARDUS',
                                                'SITI MARIA ULFA',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ]))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('penilaian.index') }}">Penilaian Technical
                                                    Competency HR</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, [
                                                'ADMINSTRATOR',
                                                'AFILIANDI',
                                                'AGUNG PANGESTU YUSUF',
                                                'AGUS PRIYANTO',
                                                'AGUS ROSIDIN',
                                                'ANDI SANTOSO',
                                                'ANDI SIMPONI',
                                                'ARRY SOEBHEKTI',
                                                'AWING',
                                                'DASUKI',
                                                'DEDY SETIAWAN',
                                                'DIAMAN DARMAWINATA',
                                                'ELI HANDOYO',
                                                'FAIZAL AFDAU',
                                                'FATUL MUKMIN',
                                                'HAERUL IKHSAN',
                                                'HENDRIO',
                                                'JAKA RARA SUKMA',
                                                'JAKARIA',
                                                'KARYA WIJAYA',
                                                'LUKMAN AHMAD',
                                                'MAMIK ABIDIN',
                                                'MEDI KRISNANTO',
                                                'MIFTAKHUROHMAN',
                                                'MUGI PRAMONO',
                                                'NUR SUPRIYANTO',
                                                'NURSAID',
                                                'NURSALIM',
                                                'R.WAWAN HIMAWAN',
                                                'RAHMAT NUGROHO',
                                                'RANGGA FADILLAH',
                                                'RIZKY ANDREA RAHMAWAN',
                                                'RUKMAN',
                                                'RUSITO',
                                                'SABAR WASIRAN',
                                                'SEPTIADI PRATOMO',
                                                'SUDIYATNO',
                                                'UMAR HADI',
                                                'VITRI HANDAYANI',
                                                'YANUARDIN SALEH SIREGAR',
                                                'YUSUF SYAFAAT',
                                                'ADHI PRASETIYO',
                                                'AHMAD RIDWAN',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'AVI SHENNA',
                                                'BANGUN SUTOPO',
                                                'CECEP ISKANDAR',
                                                'DANIA ISNAWATI',
                                                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                                                'DWI KUNTORO',
                                                'FIKRI SYAHBANA',
                                                'FRISILIA CLAUDIA HUTAMA',
                                                'GUNAWAN',
                                                'HARDI SAPUTRA',
                                                'HARRY SUPRIYADI',
                                                'HERLIANA',
                                                'HERY HERMAWAN',
                                                'HUSEIN ABDULLAH',
                                                'ILHAM CHOLID',
                                                'ILHAM SETIA DARMA',
                                                'IMAM PRASETYO',
                                                'IMAM SOPYAN',
                                                'JEFRY WASTON E',
                                                'JESSICA PAUNE',
                                                'JONI SETIAWAN',
                                                'JUN JOHAMIN PD',
                                                'KUSTIONO',
                                                'LINA UNIARSIH',
                                                'M. RIDWAN GUNAWAN',
                                                'MARTINUS CAHYO RAHASTO',
                                                'MOCHAMMAD ANDRIANSYAH',
                                                'MOHAMMAD FATKHURROHMAN',
                                                'MUHAMMAD DINAR FARISI',
                                                'MUHAMMAD MAHBUB',
                                                'NUR DWITA SURA WIJAYA',
                                                'PUTRI ANINDIA',
                                                'RAGIL ISHA RAHMANTO',
                                                'RIADUS SOLIHIN',
                                                'RICHARDUS',
                                                'RISFAN FAISAL',
                                                'RUSLAN M.ALI',
                                                'SENDY PRABOWO',
                                                'SETIYAWAN',
                                                'SITI MARIA ULFA',
                                            ]))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('job.positions.index') }}">Summary Competency</a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>

                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Training Development
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @if (in_array(auth()->user()->name, [
                                                'ADMINSTRATOR',
                                                'YULMAI RIDO WINANDA',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'JESSICA PAUNE',
                                                'MARTINUS CAHYO RAHASTO',
                                                'SITI MARIA ULFA',
                                                'RICHARDUS',
                                                'ADHI PRASETYO',
                                            ]))
                                            <li><a class="dropdown-item" href="{{ route('indexPD') }}">Form Pengajuan
                                                    Training</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, ['ADMINSTRATOR', 'JESSICA PAUNE', 'SITI MARIA ULFA']))
                                            <li><a class="dropdown-item" href="{{ route('indexPD2') }}">Persetujuan
                                                    Development</a></li>
                                        @endif

                                        @if (in_array(auth()->user()->name, [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'YULMAI RIDO WINANDA',
                                                'ADHI PRASETIYO',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'MARTINUS CAHYO RAHASTO',
                                                'RAGIL ISHA RAHMANTO',
                                                'RICHARDUS',
                                                'SITI MARIA ULFA',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ]))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('penilaian.index2') }}">History Development</a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            </ul>
                        </li>

                        {{-- Dropdown Inventory --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                CRP System
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="{{ route('crp') }}">Form CRP</a></li>
                            </ul>
                        </li>

                        <!-- Dropdown Sumbang Saran -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-si" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Sumbang Saran
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                @php
                                    $allowedUsers = [
                                        'ADMINSTRATOR',
                                        'AFILIANDI',
                                        'AGUNG PANGESTU YUSUF',
                                        'AGUS PRIYANTO',
                                        'AGUS ROSIDIN',
                                        'ANDI SANTOSO',
                                        'ANDI SIMPONI',
                                        'ARRY SOEBHEKTI',
                                        'AWING',
                                        'DASUKI',
                                        'DEDY SETIAWAN',
                                        'DIAMAN DARMAWINATA',
                                        'ELI HANDOYO',
                                        'FAIZAL AFDAU',
                                        'FATUL MUKMIN',
                                        'HAERUL IKHSAN',
                                        'HENDRIO',
                                        'JAKA RARA SUKMA',
                                        'JAKARIA',
                                        'KARYA WIJAYA',
                                        'LUKMAN AHMAD',
                                        'MAMIK ABIDIN',
                                        'MEDI KRISNANTO',
                                        'MIFTAKHUROHMAN',
                                        'MUGI PRAMONO',
                                        'NUR SUPRIYANTO',
                                        'NURSAID',
                                        'NURSALIM',
                                        'R.WAWAN HIMAWAN',
                                        'RAHMAT NUGROHO',
                                        'RANGGA FADILLAH',
                                        'RIZKY ANDREA RAHMAWAN',
                                        'RUKMAN',
                                        'RUSITO',
                                        'SABAR WASIRAN',
                                        'SEPTIADI PRATOMO',
                                        'SUDIYATNO',
                                        'UMAR HADI',
                                        'VITRI HANDAYANI',
                                        'YANUARDIN SALEH SIREGAR',
                                        'YUSUF SYAFAAT',
                                        'ADHI PRASETIYO',
                                        'AHMAD RIDWAN',
                                        'ANDIK TOTOK SISWOYO',
                                        'ARY RODJO PRASETYO',
                                        'AVI SHENNA',
                                        'BANGUN SUTOPO',
                                        'CECEP ISKANDAR',
                                        'DANIA ISNAWATI',
                                        'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                                        'DWI KUNTORO',
                                        'FIKRI SYAHBANA',
                                        'FRISILIA CLAUDIA HUTAMA',
                                        'GUNAWAN',
                                        'HARDI SAPUTRA',
                                        'HARRY SUPRIYADI',
                                        'HERLIANA',
                                        'HERY HERMAWAN',
                                        'HUSEIN ABDULLAH',
                                        'ILHAM CHOLID',
                                        'ILHAM SETIA DARMA',
                                        'IMAM PRASETYO',
                                        'IMAM SOPYAN',
                                        'JEFRY WASTON E',
                                        'JESSICA PAUNE',
                                        'JONI SETIAWAN',
                                        'JUN JOHAMIN PD',
                                        'KUSTIONO',
                                        'LINA UNIARSIH',
                                        'M. RIDWAN GUNAWAN',
                                        'MARTINUS CAHYO RAHASTO',
                                        'MOCHAMMAD ANDRIANSYAH',
                                        'MOHAMMAD FATKHURROHMAN',
                                        'MUHAMMAD DINAR FARISI',
                                        'MUHAMMAD MAHBUB',
                                        'NUR DWITA SURA WIJAYA',
                                        'PUTRI ANINDIA',
                                        'RAGIL ISHA RAHMANTO',
                                        'RIADUS SOLIHIN',
                                        'RICHARDUS',
                                        'RISFAN FAISAL',
                                        'RUSLAN M.ALI',
                                        'SENDY PRABOWO',
                                        'SETIYAWAN',
                                        'SITI MARIA ULFA',
                                        'SUKIMIN',
                                        'WULYO EKO PRASETYO',
                                        'YAN WELEM MANGINSELA',
                                        'YUDHI PRASETYO RAHMAWANTO',
                                        'YULMAI RIDO WINANDA',
                                        'YUNASIS PALGUNADI',
                                        'ZAENAL ARIFIN',
                                        'ABDUR RAHMAN AL FAAIZ',
                                        'YAN WALEM MANGINSELA',
                                        'VIVIAN ANGELIKA',
                                        'Sony Stiawan',
                                        'FAJAR BAGASKARA',
                                    ];
                                @endphp
                                @if (in_array(Auth::user()->name, $allowedUsers))
                                    <li><a class="dropdown-item" href="{{ route('upload.json') }}">Upload JSON to
                                            CSV</a>
                                    </li>
                                    <li><a class="dropdown-item" href="{{ route('showSS') }}">Form Sumbang Saran</a>
                                    </li>
                                    <li><a class="dropdown-item" href="{{ route('forumSS') }}">Overview Sumbang
                                            Saran</a></li>
                                @else
                                    <li><a class="dropdown-item" href="#">Access Denied</a></li>
                                @endif

                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Persetujuan Atasan
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @php
                                            $kaSieUsers = [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'RANGGA FADILLAH',
                                                'VITRI HANDAYANI',
                                                'ADHI PRASETIYO',
                                                'ANDIK TOTOK SISWOYO',
                                                'ARY RODJO PRASETYO',
                                                'HARDI SAPUTRA',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'MARTINUS CAHYO RAHASTO',
                                                'RAGIL ISHA RAHMANTO',
                                                'RICHARDUS',
                                                'SITI MARIA ULFA',
                                                'YUDHI PRASETYO RAHMAWANTO',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ];

                                            $kaDeptUsers = [
                                                'ADMINSTRATOR',
                                                'MUGI PRAMONO',
                                                'RANGGA FADILLAH',
                                                'ADHI PRASETIYO',
                                                'ARY RODJO PRASETYO',
                                                'ILHAM CHOLID',
                                                'JESSICA PAUNE',
                                                'JUN JOHAMIN PD',
                                                'RAGIL ISHA RAHMANTO',
                                                'RICHARDUS',
                                                'SITI MARIA ULFA',
                                                'ABDUR RAHMAN AL FAAIZ',
                                            ];
                                        @endphp

                                        @if (in_array(Auth::user()->name, $kaSieUsers))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showKonfirmasiForeman') }}">Ka. Sie</a></li>
                                        @endif

                                        @if (in_array(Auth::user()->name, $kaDeptUsers))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showKonfirmasiDeptHead') }}">Ka. Dept</a></li>
                                        @endif
                                    </ul>
                                </li>
                                <li>
                                    <a class="dropdown-item dropdown-toggle" href="#" id="childDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Penilaian
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="childDropdown">
                                        @php
                                            $penilaianKomiteUsers = [
                                                'ADMINSTRATOR',
                                                'ARY RODJO PRASETYO',
                                                'JESSICA PAUNE',
                                                'SITI MARIA ULFA',
                                            ];

                                            $penilaianHRGAUsers = [
                                                'ADMINSTRATOR',
                                                'ARY RODJO PRASETYO',
                                                'JESSICA PAUNE',
                                                'SITI MARIA ULFA',
                                            ];
                                        @endphp

                                        @if (in_array(Auth::user()->name, $penilaianKomiteUsers))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showKonfirmasiKomite') }}">Penilaian Komite</a>
                                            </li>
                                        @endif

                                        @if (in_array(Auth::user()->name, $penilaianHRGAUsers))
                                            <li><a class="dropdown-item"
                                                    href="{{ route('showKonfirmasiHRGA') }}">Penilaian HRGA</a></li>
                                        @endif

                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>

                    <div class="d-flex justify-content-end m-2 p-1">
                        @if (Auth::check())
                            <small
                                style="color: #f8f9fa; font-weight: bold; font-family: Cambria, serif; font-size: 12pt;">Hello,
                                {{ Auth::user()->name }}</small>
                        @endif
                    </div>
                    <!-- Search Bar -->
                    <form class="d-flex justify-content-end">
                        {{-- <input class="form-control me-2" disabled> --}}
                        {{-- <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                        --}}
                        <a class="btn btn-outline-warning me-3" style="color: rgb(0, 0, 0)"
                            href="{{ route('showDataDiri') }}" title="Profile">
                            <i class="bx bxs-user-circle"></i>
                        </a>
                        <a class="btn btn-outline-warning" style="color: rgb(31, 0, 0)" href="#"
                            title="Keluar"
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
            }, function() {
                $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
            });
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
