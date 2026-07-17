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
            .status-dropdown option {
                background-color: #ffffff !important;
                color: #212529 !important;
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
                                <th scope="col" rowspan="2" style="min-width:150px; white-space:normal;">Objective Learning</th>
                            </tr>
                            <tr style="background-color: #f0ad4e;">
                                <th scope="col">Nama Program</th>
                                <th scope="col">Date Actual</th>
                                <th scope="col" style="width: 15%">Biaya Actual</th>
                                <th scope="col">Lembaga</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col" style="min-width:150px;">Sharing Knowledge</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <!-- Tampilkan data yang tidak memiliki tahun_usulan -->
                            @foreach ($data as $index => $item)
                                @if (is_null($item->tahun_usulan))
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->section?->name ?? '-' }}</td>
                                        <td>{{ $item->jobPosition?->position_name ?? '-' }}</td>
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
                                        <td style="min-width:150px;">
                                            <textarea class="form-control" style="min-width: 150px; white-space: normal; height: 80px;" disabled>{{ $item->objective_learning ?? '-' }}</textarea>
                                        </td>
                                        <td>{{ $item->program_training_plan ?? '-' }}</td>
                                        <td>{{ $item->due_date_plan ?? '-' }}</td>
                                        <td>
                                            {{ $item->biaya_plan ? 'Rp ' . number_format((float) str_replace(['Rp', '.', ' '], '', $item->biaya_plan), 0, ',', '.') : '-' }}
                                        </td>
                                        <td>{{ $item->lembaga_plan ?? '-' }}</td>
                                        <td>{{ $item->keterangan_plan ?? '-' }}</td>
                                        <td style="min-width:150px;">
                                            <textarea class="form-control" style="min-width: 150px; white-space: normal; height: 80px;" disabled>{{ $item->objective_learning_aktual ?? '-' }}</textarea>
                                        </td>
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
                                <td colspan="18" style="text-align:left; font-weight:bold;">
                                    <h3><b>ADDITIONAL</b> <i class="fas fa-chevron-down"></i></h3>
                                </td>
                            </tr>

                            <!-- Tampilkan data yang memiliki tahun_usulan -->
                            @foreach ($data as $index => $item)
                                @if (!is_null($item->tahun_usulan))
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ !empty($item->section_id) ? ($item->section?->name ?? '-') : '-' }}</td>
                                        <td>{{ !empty($item->id_job_position) ? ($item->jobPosition?->position_name ?? '-') : '-' }}</td>
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
                                        <td style="min-width:150px;">
                                            <textarea class="form-control" style="min-width: 150px; white-space: normal; height: 80px;" disabled>{{ $item->objective_learning ?? '-' }}</textarea>
                                        </td>
                                        <td>{{ !empty($item->program_training_plan) ? $item->program_training_plan : '-' }}
                                        </td>
                                        <td>{{ !empty($item->due_date_plan) ? $item->due_date_plan : '-' }}</td>
                                        <td>{{ !empty($item->biaya_plan) ? 'Rp ' . number_format($item->biaya_plan, 0, ',', '.') : '-' }}
                                        </td>
                                        <td>{{ !empty($item->lembaga_plan) ? $item->lembaga_plan : '-' }}</td>
                                        <td>{{ !empty($item->keterangan_plan) ? $item->keterangan_plan : '-' }}</td>
                                        <td style="min-width:150px;">
                                            <textarea class="form-control" style="min-width: 150px; white-space: normal; height: 80px;" disabled>{{ $item->objective_learning_aktual ?? '-' }}</textarea>
                                        </td>
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
                <div class="mt-4 mb-4">
                    <h6 class="fw-bold mb-3" style="font-weight: 600;"><i class="fas fa-chart-pie me-2 text-secondary"></i> Ringkasan Status</h6>
                    <div class="d-flex flex-wrap" style="gap: 12px;">
                        <!-- Biru: Mencari Vendor -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; border: 1px solid rgba(13, 110, 253, 0.2); font-size: 13px; font-weight: 600;">
                            Mencari Vendor
                            <span class="mx-2 text-primary" style="opacity: 0.3;">|</span>
                            <span id="status-blue-percentage">
                                {{ $countStatusBlue }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusBlue, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Orange: Proses Pendaftaran -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(253, 126, 20, 0.1); color: #fd7e14; border: 1px solid rgba(253, 126, 20, 0.2); font-size: 13px; font-weight: 600;">
                            Proses Pendaftaran
                            <span class="mx-2" style="opacity: 0.3; color: #fd7e14;">|</span>
                            <span id="status-orange-percentage">
                                {{ $countStatusOrange }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusOrange, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Kuning: On Progress -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(255, 193, 7, 0.15); color: #d39e00; border: 1px solid rgba(255, 193, 7, 0.3); font-size: 13px; font-weight: 600;">
                            On Progress
                            <span class="mx-2" style="opacity: 0.3; color: #d39e00;">|</span>
                            <span id="status-yellow-percentage">
                                {{ $countStatusYellow }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusYellow, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Hijau: Done -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(25, 135, 84, 0.1); color: #198754; border: 1px solid rgba(25, 135, 84, 0.2); font-size: 13px; font-weight: 600;">
                            Done
                            <span class="mx-2" style="opacity: 0.3; color: #198754;">|</span>
                            <span id="status-green-percentage">
                                {{ $countStatusGreen }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusGreen, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Abu: Pending -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(108, 117, 125, 0.1); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.2); font-size: 13px; font-weight: 600;">
                            Pending
                            <span class="mx-2" style="opacity: 0.3; color: #6c757d;">|</span>
                            <span id="status-gray-percentage">
                                {{ $countStatusGray }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusGray, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Merah: Ditolak -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2); font-size: 13px; font-weight: 600;">
                            Ditolak
                            <span class="mx-2" style="opacity: 0.3; color: #dc3545;">|</span>
                            <span id="status-red-percentage">
                                {{ $countStatusRed }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusRed, 0) }}%)</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 3%">
                    <a href="{{ route('indexPD2') }}" class="btn btn-secondary">Close</a>
                    <a href="{{ route('exportPD2', $data->first()->tahun_aktual ?? request()->segment(2) ?? date('Y')) }}" class="btn btn-primary">
                        <i class="bi bi-printer-fill"></i>Export Excel
                    </a>
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



    </main><!-- End #main -->
@endsection
