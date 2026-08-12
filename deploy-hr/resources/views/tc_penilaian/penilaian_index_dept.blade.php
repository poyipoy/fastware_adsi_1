@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <style>
            .blue-theme-header {
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                color: white;
                padding: 1.5rem;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                margin-bottom: 2rem;
            }
            .blue-theme-header h1 {
                color: white;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }
            .blue-theme-header .breadcrumb-item.active {
                color: #e0e0e0;
            }
            .filter-card {
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                padding: 1.25rem;
                margin-bottom: 2rem;
                border: 1px solid #eef2f5;
            }
        </style>
        <div class="pagetitle blue-theme-header">
            <h1>Halaman Penilaian Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Penilaian Competency Dept</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container-fluid">
                <div class="filter-card d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div></div> <!-- Placeholder for flex alignment -->
                    <form action="{{ route('penilaian.index') }}" method="GET" class="d-flex align-items-center flex-wrap gap-3">
                        <input type="hidden" name="level" value="{{ $level }}">
                        
                        <div class="d-flex flex-column" style="width: 150px;">
                            <label for="yearFilter" class="form-label small fw-semibold text-secondary mb-1">Tahun</label>
                            <select name="year" id="yearFilter" class="form-select form-select-sm rounded-pill shadow-sm border-primary" onchange="this.form.submit()">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex flex-column" style="width: 220px;">
                            <label for="statusFilter" class="form-label small fw-semibold text-secondary mb-1">Status</label>
                            <select name="status" id="statusFilter" class="form-select form-select-sm rounded-pill shadow-sm border-primary" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="2" {{ ($selectedStatus ?? '') === '2' ? 'selected' : '' }}>Menunggu Konfirmasi Dept. Head</option>
                                <option value="3" {{ ($selectedStatus ?? '') === '3' ? 'selected' : '' }}>Menunggu Konfirmasi Div. Head</option>
                                <option value="4" {{ ($selectedStatus ?? '') === '4' ? 'selected' : '' }}>Telah Disetujui (Final)</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="card shadow-sm rounded-4 border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="datatable table table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">NO</th>
                            <th scope="col">Job Position</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penilaianData as $item)
                                <tr>
                                    <th scope="row">{{ $loop->iteration }}</th>
                                    <td>{{ $item->jobPosition->position_name ?? 'N/A' }}</td>
                                    <td>
                                        @if ($item->status == 1)
                                            <span class="badge rounded-pill bg-primary">Draf</span>
                                        @elseif ($item->status == 2)
                                            <span class="badge rounded-pill bg-warning">Menunggu Konfirmasi Dept.
                                                Head</span>
                                        @elseif ($item->status == 3)
                                            <span class="badge rounded-pill bg-warning text-dark">Menunggu Konfirmasi Div. Head</span>
                                        @elseif ($item->status == 4)
                                            <span class="badge rounded-pill bg-success">Telah Disetujui (Final)</span>
                                        @else
                                            <!-- Tambahkan opsi lain jika diperlukan -->
                                        @endif
                                    </td>
                                    <td>
                                        @if (in_array($item->status, [2, 3, 4]))
                                            <a href="{{ route('penilaian.edit2', $item->id_job_position) }}"
                                                class="btn btn-warning btn-sm rounded-pill shadow-sm">
                                                <i class="fas fa-edit"></i> Konfirmasi Nilai
                                            </a>

                                            @if ($item->status == 2)
                                                <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm"
                                                    onclick="kirimData('{{ $item->id_job_position }}')">
                                                    <i class="fas fa-paper-plane"></i> Setuju
                                                </button>
                                            @endif
                                        @endif

                                        <a href="{{ route('penilaian.view', $item->id_job_position) }}"
                                            class="btn btn-info btn-sm text-white rounded-pill shadow-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                        @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- jQuery -->
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

        <script>
            function kirimData(id_job_position) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data Competency Telah Finish?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika pengguna memilih Yes, lakukan AJAX request
                        $.ajax({
                            url: '{{ route('update.status2', ':id_job_position') }}'.replace(':id_job_position',
                                id_job_position),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}' // Sertakan token CSRF
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            location.reload(); // contoh aksi: merefresh halaman
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Error: ' + response.message,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'Request failed!',
                                    text: 'Request failed: ' + xhr.statusText,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    } else {
                        Swal.fire(
                            'Dibatalkan',
                            'Data Tidak DiKirim',
                            'info'
                        );
                    }
                });
            }
        </script>

    </main><!-- End #main -->
@endsection
