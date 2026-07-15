@extends('layout')

@section('content')
    <main id="main" class="main">

        <style>
            .switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
            }

            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                border-radius: 50%;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
            }

            input:checked+.slider {
                background-color: #0d6efd;
            }

            input:checked+.slider:before {
                transform: translateX(26px);
            }

            .disabled {
                pointer-events: none;
                opacity: 0.6;
            }

            .dashboard-header {
                background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                border-radius: 15px;
                padding: 1.5rem;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                margin-bottom: 2rem;
                border: 1px solid rgba(0,0,0,0.05);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .table-custom {
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            }
            
            .table-custom thead th {
                background-color: #f8f9fa;
                color: #2c3e50;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.85rem;
                letter-spacing: 0.5px;
                border-bottom: 2px solid #e9ecef;
                padding: 1rem;
            }

            .table-custom tbody td {
                padding: 1rem;
                vertical-align: middle;
                border-bottom: 1px solid #f8f9fa;
                color: #495057;
            }

            .table-custom tbody tr {
                transition: all 0.2s ease;
            }

            .table-custom tbody tr:hover {
                background-color: #f8f9fa;
                transform: translateY(-1px);
                box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            }
            
            .action-buttons .btn {
                border-radius: 8px;
                padding: 0.4rem 0.8rem;
                transition: all 0.2s ease;
            }
            
            .action-buttons .btn:hover {
                transform: translateY(-2px);
            }
        </style>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle mb-4">
            <h1>Menu List Training</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">People Development</a></li>
                    <li class="breadcrumb-item active">Pengajuan Training</li>
                </ol>
            </nav>
        </div>

        <div class="container-fluid px-0">
            @php
                $user = auth()->user();
                $roleAccess = app(\App\Services\HR\HRRoleAccessService::class);
                $canManageConfig = $roleAccess->canManageTrainingConfig($user);
            @endphp

            <div class="dashboard-header">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-journal-bookmark text-primary me-2"></i>Daftar Pengajuan</h5>
                    <p class="text-muted small mb-0">Kelola dan pantau pengajuan training karyawan.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @if ($canManageConfig)
                        <div class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded-pill shadow-sm border">
                            <span class="small fw-semibold text-muted">Akses Form:</span>
                            <label class="switch mb-0">
                                <input type="checkbox" id="toggleSwitch" {{ $buttonStatus ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 shadow-sm border bg-white text-dark d-flex align-items-center gap-2" onclick="changeActiveYear()">
                            <span class="small fw-semibold text-muted">Tahun Aktif:</span>
                            <span class="fw-bold">{{ $activeYear }}</span>
                        </button>
                    @endif
                    <a href="{{ route('createPD') }}" id="trainingButton"
                        class="btn btn-primary rounded-pill px-4 shadow-sm {{ $buttonStatus ? '' : 'disabled' }}">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Pengajuan
                    </a>
                </div>
            </div>

            <!-- KPI Widget Cards -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3 text-primary">
                                <i class="bi bi-journal-text fs-3"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small fw-bold text-uppercase">Total Rencana Training</h6>
                                <h4 class="mb-0 fw-bold">{{ $kpiTotalProgram ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3 text-success">
                                <i class="bi bi-cash-stack fs-3"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small fw-bold text-uppercase">Total Estimasi Budget</h6>
                                <h4 class="mb-0 fw-bold">Rp {{ number_format($kpiTotalBiaya ?? 0, 0, ',', '.') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 p-3 rounded-3 me-3 text-info">
                                <i class="bi bi-people fs-3"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small fw-bold text-uppercase">Karyawan Ditraining</h6>
                                <h4 class="mb-0 fw-bold">{{ $kpiTotalKaryawan ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3">
                            <h6 class="text-muted mb-2 small fw-bold text-uppercase">Status Progres Training</h6>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="badge bg-info rounded-pill px-2">Mencari Vendor: {{ $kpiMencariVendor ?? 0 }}</span>
                                <span class="badge bg-primary rounded-pill px-2">Pendaftaran: {{ $kpiProsesPendaftaran ?? 0 }}</span>
                                <span class="badge bg-warning text-dark rounded-pill px-2">On Progress: {{ $kpiOnProgress ?? 0 }}</span>
                                <span class="badge bg-success rounded-pill px-2">Done: {{ $kpiDone ?? 0 }}</span>
                                <span class="badge bg-secondary rounded-pill px-2">Pending: {{ $kpiPending ?? 0 }}</span>
                                <span class="badge bg-danger rounded-pill px-2">Ditolak: {{ $kpiDitolak ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SweetAlert untuk Notifikasi -->
            @if (!empty($hasDoneStatus))
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Tindakan Diperlukan!',
                            text: 'Ada pengajuan dengan status "Done". Harap segera mengisi form evaluasi pada menu view.',
                            icon: 'warning',
                            confirmButtonColor: '#0d6efd',
                            confirmButtonText: 'Mengerti'
                        });
                    });
                </script>
            @endif

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom datatable mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center" width="5%">No</th>
                                    <th scope="col">Nama PIC</th>
                                    <th scope="col">Tahun Aktual</th>
                                    <th scope="col" width="25%">Status Pengajuan / Training</th>
                                    <th scope="col" class="text-center" width="20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $index => $item)
                                    <tr>
                                        <td class="text-center fw-semibold text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-weight: 600;">
                                                    {{ substr($item->modified_at, 0, 1) }}
                                                </div>
                                                <span class="fw-semibold">{{ $item->modified_at }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><i class="bi bi-calendar-event me-1"></i>{{ $item->tahun_aktual }}</span>
                                        </td>
                                        <td>
                                            @if ($item->status_1 == 1)
                                                <span class="badge rounded-pill bg-secondary px-3 py-2"><i class="bi bi-file-earmark me-1"></i>Draf</span>
                                            @elseif ($item->status_1 == 2)
                                                <span class="badge rounded-pill bg-warning text-dark px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>Menunggu HRGA</span>
                                            @elseif ($item->status_1 == 3)
                                                <span class="badge rounded-pill bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i>Telah Disetujui</span>
                                            @else
                                                <span class="badge rounded-pill bg-info px-3 py-2">{{ $item->status_1 }}</span>
                                            @endif
                                            {{-- Modul 4.1: Status Training (status_2) --}}
                                            @if (!empty($item->status_2))
                                                @php
                                                    $s2Colors = \App\Enums\TrainingStatus::colorConfig();
                                                    $s2Badge  = $s2Colors[$item->status_2]['badge'] ?? 'bg-secondary';
                                                    $s2Icons  = [
                                                        'Mencari Vendor'    => 'bi-search',
                                                        'Proses Pendaftaran' => 'bi-pencil-square',
                                                        'On Progress'       => 'bi-play-circle',
                                                        'Done'              => 'bi-check2-all',
                                                        'Pending'           => 'bi-pause-circle',
                                                        'Ditolak'           => 'bi-x-circle',
                                                    ];
                                                    $s2Icon = $s2Icons[$item->status_2] ?? 'bi-circle';
                                                @endphp
                                                <br>
                                                <span class="badge {{ $s2Badge }} mt-1" style="font-size:0.75em;">
                                                    <i class="bi {{ $s2Icon }} me-1"></i>{{ $item->status_2 }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2 action-buttons">
                                                @if (!in_array($item->status_1, [2, 3, 4]))
                                                    <a href="{{ route('editPdPengajuan', ['modified_at' => $item->modified_at, 'tahun_aktual' => $item->tahun_aktual]) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Edit Form" data-bs-toggle="tooltip">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="{{ route('sendPD', ['modified_at' => $item->modified_at, 'tahun_aktual' => $item->tahun_aktual]) }}"
                                                        class="btn btn-sm btn-outline-success" title="Kirim Pengajuan" data-bs-toggle="tooltip">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('viewPD', ['modified_at' => $item->modified_at, 'tahun_aktual' => $item->tahun_aktual]) }}"
                                                    class="btn btn-sm btn-outline-info" title="Lihat Detail" data-bs-toggle="tooltip">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                                                <p class="mb-0">Belum ada data pengajuan training.</p>
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

        </section>

                        <!-- jQuery -->
                        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
                        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
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


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toggleSwitch = document.getElementById('toggleSwitch');
                const trainingButton = document.getElementById('trainingButton');

                function applyButtonState(enabled) {
                    if (enabled) {
                        trainingButton.classList.remove('disabled');
                        trainingButton.style.pointerEvents = 'auto';
                    } else {
                        trainingButton.classList.add('disabled');
                        trainingButton.style.pointerEvents = 'none';
                    }
                }

                // Inisialisasi awal berdasarkan server-rendered class
                applyButtonState(!trainingButton.classList.contains('disabled'));

                if (toggleSwitch) {
                    // Admin: toggle mengontrol button dan update server
                    toggleSwitch.addEventListener('change', function() {
                        const isChecked = toggleSwitch.checked;

                        applyButtonState(isChecked);

                        fetch('{{ route('updateButtonStatus') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ enabled: isChecked })
                        }).then(response => {
                            if (!response.ok) throw new Error('HTTP ' + response.status);
                            return response.json();
                        })
                          .then(data => {
                              console.log('Status updated:', data);
                              alert('Button status berhasil diubah: ' + (isChecked ? 'ON' : 'OFF'));
                          })
                          .catch(error => {
                              console.error('Error:', error);
                              alert('GAGAL update button status! Error: ' + error.message);
                              // Revert toggle jika gagal
                              toggleSwitch.checked = !isChecked;
                              applyButtonState(!isChecked);
                          });
                    });
                }
            });
        </script>
    </main><!-- End #main -->
@endsection
