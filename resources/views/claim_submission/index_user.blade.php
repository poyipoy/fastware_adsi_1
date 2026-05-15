@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Claim Submission</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Claim Submission</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Data Claim Submission</h5>
                            <div class="card-header" style="margin-bottom: 20px;">
                                <a href="{{ route('claim.create') }}" class="btn btn-success btn-sm"
                                    style="font-size: 20px;">
                                    <i class="fas fa-plus"></i> Tambah Claim
                                </a>
                            </div>

                            @if (session('success'))
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        Swal.fire({
                                            title: 'Berhasil!',
                                            text: '{{ session('success') }}',
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        });
                                    });
                                </script>
                            @endif

                            @if (session('error'))
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: '{{ session('error') }}',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    });
                                </script>
                            @endif

                            <div class="claim-datatable-wrapper datatable-wrapper no-header">
                                <div class="datatable-container table-responsive" style="height: 100%; overflow-y: auto;">
                                <table class="datatable-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px" data-sortable="false">NO</th>
                                            <th class="text-center" width="120px">PIC</th>
                                            <th class="text-center" width="100px">No. PR</th>
                                            <th class="text-center" width="150px">Nama Produk</th>
                                            <th class="text-center" width="120px">Submission Date</th>
                                            <th class="text-center" width="120px">Category</th>
                                            <th class="text-center" width="200px">Description of Issue</th>
                                            <th class="text-center" width="200px">Proposed Solution</th>
                                            <th class="text-center" width="100px">Status</th>
                                            <th class="text-center" width="100px" data-sortable="false">Foto/File</th>
                                            <th class="text-center" width="120px" data-sortable="false">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($claims as $key => $claim)
                                            <tr>
                                                <td class="text-center">{{ ($claims->firstItem() ?? 1) + $key }}</td>
                                                <td class="text-center">{{ $claim->modified_at ?? '-' }}</td>
                                                <td class="text-center">{{ $claim->no_pr }}</td>
                                                <td class="text-center">{{ $claim->nama_produk }}</td>
                                                <td class="text-center" data-order="{{ $claim->submission_date ? $claim->submission_date->timestamp : 0 }}">
                                                    {{ $claim->submission_date ? $claim->submission_date->format('d-m-Y') : '-' }}
                                                </td>
                                                <td class="text-center">{{ $claim->category }}</td>
                                                <td class="text-center">{{ Str::limit($claim->description_of_issue, 50) }}</td>
                                                <td class="text-center">{{ Str::limit($claim->proposed_solution, 50) }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ $claim->status_badge }} align-items-center"
                                                        style="font-size: 16px;">{{ $claim->status_label }}</span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    @if ($claim->file)
                                                        <a href="{{ asset($claim->file) }}" target="_blank"
                                                            class="btn btn-outline-secondary btn-sm"
                                                            data-bs-toggle="tooltip" title="{{ $claim->file_name }}">
                                                            <i class="fas fa-file-image"></i> Lihat
                                                        </a>
                                                    @else
                                                        <span class="text-muted fst-italic">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($claim->status === 'open')
                                                        <a href="{{ route('claim.edit', $claim->id) }}"
                                                            class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-danger"
                                                            onclick="event.preventDefault(); deleteClaim('{{ route('claim.destroy', $claim->id) }}');"
                                                            title="Hapus">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('claim.view', $claim->id) }}"
                                                        class="btn btn-sm btn-info" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>

                                <div class="datatable-bottom">
                                    <div class="datatable-info">
                                        <div class="d-flex align-items-center gap-2">
                                            <label for="perPageSelect" class="mb-0">Show entries:</label>
                                            <select id="perPageSelect" class="form-select" style="width: auto;" onchange="changePerPage(this.value)">
                                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                            </select>
                                        </div>
                                        <div style="margin-top: 8px;">
                                            @if ($claims->count() > 0)
                                                Menampilkan {{ $claims->firstItem() }} - {{ $claims->lastItem() }} dari {{ $claims->total() }} data
                                            @else
                                                Menampilkan 0 data
                                            @endif
                                        </div>
                                    </div>

                                    @if ($claims->hasPages())
                                        <div class="datatable-pagination">
                                            {{ $claims->onEachSide(1)->links('pagination::bootstrap-5') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <style>
            .claim-datatable-wrapper .datatable-container {
                border-top: 1px solid #d9d9d9;
                border-bottom: 1px solid #d9d9d9;
            }

            .claim-datatable-wrapper .datatable-bottom {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
                padding: 8px 10px;
            }

            .claim-datatable-wrapper .datatable-info {
                margin: 0;
                font-size: 8pt;
            }

            .claim-datatable-wrapper .pagination {
                margin: 0;
            }

            .claim-datatable-wrapper .page-link {
                border: 1px solid transparent;
                margin-left: 2px;
                padding: 6px 12px;
                color: #333;
            }

            .claim-datatable-wrapper .page-link:hover {
                background-color: #d9d9d9;
                color: #333;
            }

            .claim-datatable-wrapper .page-item.active .page-link,
            .claim-datatable-wrapper .page-item.active .page-link:hover {
                background-color: #d9d9d9;
                border-color: transparent;
                color: #333;
            }

            .claim-datatable-wrapper .page-item.disabled .page-link {
                opacity: 0.4;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function changePerPage(value) {
                const url = new URL(window.location);
                url.searchParams.set('per_page', value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }

            $(document).ready(function() {
                $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });

            function deleteClaim(url) {
                Swal.fire({
                    title: 'Apakah anda yakin?',
                    text: "Data yang telah dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Terhapus!', response.message, 'success')
                                    .then(() => location.reload());
                            },
                            error: function(xhr) {
                                Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                            }
                        });
                    }
                });
            }
        </script>

    </main>
@endsection
