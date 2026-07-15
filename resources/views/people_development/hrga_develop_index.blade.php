@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            .blue-theme-header {
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                border-radius: 12px;
                padding: 1.5rem 2rem;
                color: white;
                margin-bottom: 1.5rem;
                box-shadow: 0 4px 20px rgba(30, 60, 114, 0.3);
            }
            .blue-theme-header h1,
            .blue-theme-header .breadcrumb-item,
            .blue-theme-header .breadcrumb-item a,
            .blue-theme-header .breadcrumb-item.active {
                color: rgba(255,255,255,0.85) !important;
                font-size: 0.85rem;
            }
            .blue-theme-header h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.25rem; color: #fff !important; }
            .blue-theme-header .breadcrumb { margin-bottom: 0; background: transparent; padding: 0; }
            .blue-theme-header .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,0.5); }

            .filter-card {
                background: #fff;
                border: 1px solid #e8ecf0;
                border-radius: 10px;
                padding: 1rem 1.25rem;
                margin-bottom: 1.5rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            }
            .filter-card label { font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 0.2rem; }
            .filter-card .form-select, .filter-card .form-control {
                font-size: 0.85rem;
                border-radius: 7px;
                border: 1.5px solid #dee2e6;
                min-width: 160px;
                max-width: 200px;
            }
            .filter-card .btn-filter {
                font-size: 0.82rem;
                border-radius: 7px;
                padding: 0.38rem 1rem;
                font-weight: 600;
            }
            .kpi-card {
                border: none;
                border-radius: 12px;
                box-shadow: 0 2px 12px rgba(0,0,0,0.06);
                transition: transform 0.2s;
            }
            .kpi-card:hover { transform: translateY(-2px); }
            .kpi-icon {
                width: 48px; height: 48px;
                border-radius: 10px;
                display: flex; align-items: center; justify-content: center;
                font-size: 1.4rem;
                flex-shrink: 0;
            }
            .table-card {
                border: none;
                border-radius: 12px;
                box-shadow: 0 2px 12px rgba(0,0,0,0.06);
                overflow: hidden;
            }
            .table-card .table thead th {
                background: #f8f9fa;
                font-size: 0.78rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #495057;
                border-bottom: 2px solid #e9ecef;
                padding: 0.85rem 1rem;
            }
            .table-card .table tbody td {
                font-size: 0.88rem;
                padding: 0.85rem 1rem;
                vertical-align: middle;
                border-bottom: 1px solid #f0f0f0;
            }
            .table-card .table tbody tr:last-child td { border-bottom: none; }
            .table-card .table tbody tr:hover { background: #f8fbff; }
            .badge-status {
                font-size: 0.75rem;
                padding: 0.35em 0.75em;
                border-radius: 20px;
                font-weight: 600;
            }
            .empty-state {
                padding: 3rem;
                text-align: center;
                color: #adb5bd;
            }
            .empty-state i { font-size: 3rem; margin-bottom: 1rem; }
        </style>

        {{-- ===== PAGE HEADER ===== --}}
        <div class="blue-theme-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="bi bi-shield-check me-2"></i>Menu List Training (HRGA)</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">People Development</a></li>
                        <li class="breadcrumb-item active">Approval HRGA</li>
                    </ol>
                </nav>
            </div>
            <div>
                <button type="button" class="btn btn-light rounded-pill px-4 py-2 shadow-sm text-primary fw-bold" onclick="changeActiveYear()">
                    <i class="bi bi-calendar-check me-1"></i> Tahun Aktif: {{ $activeYear }}
                </button>
            </div>
        </div>

        <div class="container-fluid px-0">

            {{-- ===== KPI CARDS ===== --}}
            <div class="row mb-4 g-3">
                <div class="col-6 col-md-3">
                    <div class="card kpi-card h-100">
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">Departemen Aktif</div>
                                <div class="fw-bold fs-4">{{ $kpiTotalDepartemen ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card kpi-card h-100">
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">Budget Usulan</div>
                                <div class="fw-bold" style="font-size:1rem;">Rp {{ number_format($kpiTotalBiayaUsulan ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card kpi-card h-100">
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">Budget Plan</div>
                                <div class="fw-bold" style="font-size:1rem;">Rp {{ number_format($kpiTotalBiayaPlan ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card kpi-card h-100">
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-people"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-bold text-uppercase" style="font-size:0.72rem;">Karyawan Ditraining</div>
                                <div class="fw-bold fs-4">{{ $kpiTotalKaryawan ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== FILTER PANEL ===== --}}
            <div class="filter-card">
                <form action="{{ route('indexPD2') }}" method="GET" class="d-flex align-items-end flex-wrap gap-3">
                    <div>
                        <label for="year">Filter Tahun</label>
                        <select id="year" name="year" class="form-select" onchange="this.form.submit()">
                            <option value="">— Semua Tahun —</option>
                            @foreach ($availableYears as $yr)
                                <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($selectedYear)
                        <div>
                            <a href="{{ route('indexPD2') }}" class="btn btn-outline-secondary btn-filter">
                                <i class="bi bi-x-circle me-1"></i>Reset
                            </a>
                        </div>
                    @endif
                    <div class="ms-auto text-muted small align-self-center">
                        <i class="bi bi-info-circle me-1"></i>
                        Menampilkan {{ $data->count() }} pengajuan
                        @if ($selectedYear)
                            tahun <strong>{{ $selectedYear }}</strong>
                        @endif
                    </div>
                </form>
            </div>

            {{-- ===== DATA TABLE ===== --}}
            <div class="card table-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="hrga-table">
                            <thead>
                                <tr>
                                    <th style="width:60px;">NO</th>
                                    <th>Tahun</th>
                                    <th>Status Pengajuan / Training</th>
                                    <th style="width:160px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $index => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $item->tahun_aktual }}</span>
                                        </td>
                                        <td>
                                            @if ($item->status_1 == 1)
                                                <span class="badge badge-status bg-secondary">Draf</span>
                                            @elseif ($item->status_1 == 2)
                                                <span class="badge badge-status bg-warning text-dark">
                                                    <i class="bi bi-hourglass-split me-1"></i>Menunggu Konfirmasi HRGA
                                                </span>
                                            @elseif ($item->status_1 == 3)
                                                <span class="badge badge-status bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>Telah Disetujui
                                                </span>
                                            @endif
                                            {{-- Modul 4.1: Status Training (status_2) --}}
                                            @if (!empty($item->status_2))
                                                @php
                                                    $s2Colors = \App\Enums\TrainingStatus::colorConfig();
                                                    $s2Badge  = $s2Colors[$item->status_2]['badge'] ?? 'bg-secondary';
                                                    $s2Icons  = [
                                                        'Mencari Vendor'     => 'bi-search',
                                                        'Proses Pendaftaran' => 'bi-pencil-square',
                                                        'On Progress'        => 'bi-play-circle',
                                                        'Done'               => 'bi-check2-all',
                                                        'Pending'            => 'bi-pause-circle',
                                                        'Ditolak'            => 'bi-x-circle',
                                                    ];
                                                    $s2Icon = $s2Icons[$item->status_2] ?? 'bi-circle';
                                                @endphp
                                                <br>
                                                <span class="badge badge-status {{ $s2Badge }} mt-1">
                                                    <i class="bi {{ $s2Icon }} me-1"></i>{{ $item->status_2 }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <a href="{{ route('editPdPengajuanHRGA', ['tahun_aktual' => $item->tahun_aktual]) }}"
                                                   class="btn btn-sm btn-primary" title="Review & Setujui">
                                                    <i class="bi bi-clipboard2-check-fill me-1"></i>Review
                                                </a>
                                                <a href="{{ route('viewPD2', $item->tahun_aktual) }}"
                                                   class="btn btn-sm btn-outline-info" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @if ($item->status_1 != 3)
                                                    <a href="{{ route('sendPD2', $item->tahun_aktual) }}"
                                                       class="btn btn-sm btn-outline-success" title="Setujui & Kirim">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox d-block"></i>
                                                <p class="mb-0">Tidak ada data pengajuan training
                                                    @if ($selectedYear)
                                                        untuk tahun <strong>{{ $selectedYear }}</strong>
                                                    @endif
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script>
            function changeActiveYear() {
                Swal.fire({
                    title: 'Set Tahun Aktif',
                    input: 'number',
                    inputLabel: 'Masukkan Tahun Aktif Pengajuan',
                    inputValue: {{ $activeYear }},
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#0d6efd',
                    inputValidator: (value) => {
                        if (!value || value < 2000 || value > 2100) {
                            return 'Masukkan tahun yang valid!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("pd.active-year.set") }}',
                            type: 'POST',
                            data: {
                                year: result.value,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonColor: '#0d6efd'
                                    }).then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Terjadi kesalahan saat menyimpan data.', 'error');
                            }
                        });
                    }
                });
            }

            $(document).ready(function () {
                // Hover dropdown
                $('.nav-item.dropdown').hover(function () {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function () {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });
        </script>

    </main><!-- End #main -->
@endsection
