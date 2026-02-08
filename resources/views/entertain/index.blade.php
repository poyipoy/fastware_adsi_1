@extends('layout')

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Data Entertainment</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/forumSS') }}">Home</a></li>
                <li class="breadcrumb-item active">Entertainment</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                                <i class="bi bi-plus-circle"></i> Create New
                            </button>
                            <button type="button" class="btn btn-success" id="btnExport">
                                <i class="bi bi-file-earmark-pdf"></i> Export to PDF
                            </button>
                        </div>

                        <!-- Filter Section -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="filter_start_date" class="form-label">Filter Tanggal Dari</label>
                                <input type="date" class="form-control" id="filter_start_date">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_end_date" class="form-label">Filter Tanggal Sampai</label>
                                <input type="date" class="form-control" id="filter_end_date">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_user" class="form-label">Filter User</label>
                                <select class="form-select" id="filter_user">
                                    <option value="">-- Semua User --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_perusahaan" class="form-label">Filter Nama Perusahaan</label>
                                <input type="text" class="form-control" id="filter_perusahaan" placeholder="Cari nama perusahaan...">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-success" id="btnFilter">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <button type="button" class="btn btn-secondary" id="btnReset">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="entertainTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>User</th>
                                        <th>Tanggal</th>
                                        <th>Tempat</th>
                                        <th>Alamat</th>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                        <th>Nama</th>
                                        <th>Posisi</th>
                                        <th>Nama Perusahaan</th>
                                        <th>Jenis Usaha</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createModalLabel">Tambah Data Entertainment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="tgl" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tgl" name="tgl" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tempat" class="form-label">Tempat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tempat" name="tempat" maxlength="50" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="alamat" name="alamat" maxlength="255" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="jenis" class="form-label">Jenis <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="jenis" name="jenis" maxlength="30" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="jumlah" name="jumlah" maxlength="50" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama" name="nama" maxlength="50" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="posisi" class="form-label">Posisi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="posisi" name="posisi" maxlength="25" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama_perusahaan" class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_perusahaan" name="nama_perusahaan" maxlength="50" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="jenis_usaha" class="form-label">Jenis Usaha <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="jenis_usaha" name="jenis_usaha" maxlength="50" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#entertainTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('entertain.getData') }}",
            type: 'GET',
            data: function(d) {
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
                d.user_id = $('#filter_user').val();
                d.nama_perusahaan = $('#filter_perusahaan').val();
            }
        },
        columns: [
            { 
                data: null,
                searchable: false,
                orderable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'user_name' },
            { data: 'tgl' },
            { data: 'tempat' },
            { data: 'alamat' },
            { data: 'jenis' },
            { data: 'jumlah' },
            { data: 'nama' },
            { data: 'posisi' },
            { data: 'nama_perusahaan' },
            { data: 'jenis_usaha' },
            { 
                data: 'status',
                orderable: false
            },
            { 
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[2, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: "Memuat data...",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data yang tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)",
            search: "Cari:",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Filter button
    $('#btnFilter').on('click', function() {
        table.ajax.reload();
    });

    // Reset button
    $('#btnReset').on('click', function() {
        $('#filter_start_date').val('');
        $('#filter_end_date').val('');
        $('#filter_user').val('');
        $('#filter_perusahaan').val('');
        table.ajax.reload();
    });

    // Export button
    $('#btnExport').on('click', function() {
        var startDate = $('#filter_start_date').val();
        var endDate = $('#filter_end_date').val();
        var userId = $('#filter_user').val();
        var namaPerusahaan = $('#filter_perusahaan').val();

        // Build URL with parameters
        var url = "{{ route('entertain.export') }}";
        var params = [];
        
        if (startDate) params.push('start_date=' + startDate);
        if (endDate) params.push('end_date=' + endDate);
        if (userId) params.push('user_id=' + userId);
        if (namaPerusahaan) params.push('nama_perusahaan=' + encodeURIComponent(namaPerusahaan));
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        // Open download in new window
        window.open(url, '_blank');
    });

    // Submit create form
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('entertain.store') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 1500
                });
                
                $('#createModal').modal('hide');
                $('#createForm')[0].reset();
                table.ajax.reload();
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                var errorMessage = '';
                
                if (errors) {
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '<br>';
                    });
                } else {
                    errorMessage = 'Terjadi kesalahan saat menyimpan data';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage
                });
            }
        });
    });
});

// Download function - open in new tab
function downloadRow(id) {
    // Open PDF in new tab
    window.open("{{ url('entertain/download') }}/" + id, '_blank');
    
    // Reload table after a short delay to update status
    setTimeout(function() {
        $('#entertainTable').DataTable().ajax.reload(null, false);
    }, 1000);
}
</script>
@endsection
