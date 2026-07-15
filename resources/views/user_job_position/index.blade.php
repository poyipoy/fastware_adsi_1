@extends('layout')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Mapping Karyawan → Job Position</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">HR</a></li>
                <li class="breadcrumb-item active">Mapping Karyawan</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Form Tambah Mapping --}}
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-primary text-white rounded-top-4 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-user-tag me-2"></i>Assign Karyawan ke Posisi</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('user-job-position.store') }}">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Job Position <span class="text-danger">*</span></label>
                                <select name="mst_job_position_id" class="form-select select2-form" required>
                                    <option value="">— Pilih Posisi —</option>
                                    @foreach($positions as $pos)
                                        <option value="{{ $pos->id }}">
                                            {{ $pos->position_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Karyawan <span class="text-danger">*</span> <small class="text-muted fw-normal ms-2">(Tahan Ctrl untuk pilih lebih dari 1)</small></label>
                                <select name="user_ids[]" class="form-select select2-form" multiple="multiple" required>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 shadow-sm">
                                    <i class="fas fa-plus me-1"></i> Assign
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Filter & Tabel --}}
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Filter Posisi</label>
                            <select name="position_id" id="filterPosition" class="form-select select2-filter">
                                <option value="">— Semua Posisi —</option>
                                @foreach($positions as $pos)
                                    <option value="{{ $pos->id }}" {{ request('position_id') == $pos->id ? 'selected' : '' }}>
                                        {{ $pos->position_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Cari Karyawan</label>
                            <input type="text" name="search" class="form-control" placeholder="Nama karyawan..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary rounded-pill w-100">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user-job-position.index') }}" class="btn btn-outline-secondary rounded-pill w-100">
                                <i class="fas fa-times me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px">No</th>
                                    <th>Nama Karyawan</th>
                                    <th>Job Position</th>
                                    <th>Department</th>
                                    <th>Section</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mappings as $map)
                                    <tr>
                                        <td class="text-center">{{ $mappings->firstItem() + $loop->index }}</td>
                                        <td class="fw-semibold">{{ $map->user?->name ?? '—' }}</td>
                                        <td>{{ $map->jobPosition?->position_name ?? '—' }}</td>
                                        <td class="text-secondary">{{ $map->jobPosition?->department?->name ?? '—' }}</td>
                                        <td class="text-secondary">{{ $map->jobPosition?->section?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            @if($map->is_active)
                                                <span class="badge bg-success rounded-pill px-3">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary rounded-pill px-3">Non-aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1 btn-edit-mapping"
                                                    data-id="{{ $map->id }}"
                                                    data-user-id="{{ $map->user_id }}"
                                                    data-position-id="{{ $map->mst_job_position_id }}"
                                                    title="Edit Mapping">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            {{-- Modul 3.1: Working Experience Button --}}
                                            <button type="button" class="btn btn-sm btn-outline-purple rounded-pill px-3 me-1 btn-working-exp"
                                                    data-user-id="{{ $map->user_id }}"
                                                    data-user-name="{{ $map->user?->name }}"
                                                    title="Riwayat Jabatan"
                                                    style="border-color:#6f42c1;color:#6f42c1;">
                                                <i class="bi bi-briefcase-fill"></i>
                                            </button>
                                            <form method="POST" action="{{ route('user-job-position.toggle-active', $map->id) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm {{ $map->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} rounded-pill px-3 me-1"
                                                        title="{{ $map->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    <i class="fas {{ $map->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('user-job-position.destroy', $map->id) }}" class="d-inline"
                                                  onsubmit="return confirm('Hapus mapping ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger rounded-pill px-3">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada mapping karyawan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($mappings->hasPages())
                        <div class="p-3 d-flex justify-content-center">{{ $mappings->links('pagination::bootstrap-5') }}</div>
                    @endif
                </div>
            </div>

            <!-- Modal Edit Mapping -->
            <div class="modal fade" id="modalEditMapping" tabindex="-1" aria-labelledby="modalEditMappingLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header bg-primary text-white rounded-top-4 py-3">
                            <h5 class="modal-title fw-semibold" id="modalEditMappingLabel"><i class="fas fa-edit me-2"></i>Edit Mapping Karyawan</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formEditMapping" method="POST" action="">
                            @csrf
                            @method('PUT')
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Karyawan <span class="text-danger">*</span></label>
                                    <select name="user_id" id="editUserId" class="form-select select2-edit" required>
                                        <option value="">— Pilih Karyawan —</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Job Position <span class="text-danger">*</span></label>
                                    <select name="mst_job_position_id" id="editPositionId" class="form-select select2-edit" required>
                                        <option value="">— Pilih Posisi —</option>
                                        @foreach($positions as $pos)
                                            <option value="{{ $pos->id }}">{{ $pos->position_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Modal: Working Experience (Modul 3.1) --}}
            <div class="modal fade" id="modalWorkingExp" tabindex="-1" aria-labelledby="modalWorkingExpLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header py-3" style="background:#6f42c1;color:white;">
                            <h5 class="modal-title fw-semibold" id="modalWorkingExpLabel">
                                <i class="bi bi-briefcase-fill me-2"></i>
                                Riwayat Jabatan — <span id="we-user-name"></span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            {{-- Add Form --}}
                            <div class="card border-0 bg-light rounded-3 mb-4">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold text-muted mb-3"><i class="bi bi-plus-circle me-1"></i> Tambah Riwayat</h6>
                                    <div class="row g-2 align-items-end" id="we-add-form">
                                        <div class="col-md-2">
                                            <label class="form-label small fw-semibold">Tahun Mulai <span class="text-danger">*</span></label>
                                            <input type="number" id="we-year-start" class="form-control form-control-sm" placeholder="Tahun Mulai" min="2000" max="2099">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-semibold">Tahun Selesai <small class="text-muted">(kosong = sekarang)</small></label>
                                            <input type="number" id="we-year-end" class="form-control form-control-sm" placeholder="Tahun Selesai" min="2000" max="2099">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Jabatan <span class="text-danger">*</span></label>
                                            <input type="text" id="we-job-position" class="form-control form-control-sm" placeholder="Jabatan">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-semibold">Section</label>
                                            <input type="text" id="we-section" class="form-control form-control-sm" placeholder="Section">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-semibold">Departemen</label>
                                            <input type="text" id="we-departemen" class="form-control form-control-sm" placeholder="Departemen">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" id="we-btn-add" class="btn btn-sm w-100" style="background:#6f42c1;color:white;border-radius:20px;">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label small fw-semibold">Keterangan</label>
                                        <input type="text" id="we-keterangan" class="form-control form-control-sm" placeholder="Keterangan">
                                    </div>
                                </div>
                            </div>
                            {{-- Table --}}
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle" id="we-table">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Tahun Mulai</th>
                                            <th>Tahun Selesai</th>
                                            <th>Jabatan</th>
                                            <th>Section</th>
                                            <th>Departemen</th>
                                            <th>Keterangan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="we-tbody">
                                        <tr><td colspan="7" class="text-center text-muted">Memuat data...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer border-0 bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal: Edit Working Experience --}}
            <div class="modal fade" id="modalEditWE" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header py-3" style="background:#6f42c1;color:white;">
                            <h6 class="modal-title fw-semibold"><i class="bi bi-pencil-fill me-2"></i>Edit Riwayat Jabatan</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <input type="hidden" id="edit-we-id">
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label small fw-semibold">Tahun Mulai</label>
                                    <input type="number" id="edit-we-year-start" class="form-control form-control-sm" placeholder="Tahun Mulai"></div>
                                <div class="col-6"><label class="form-label small fw-semibold">Tahun Selesai</label>
                                    <input type="number" id="edit-we-year-end" class="form-control form-control-sm" placeholder="Tahun Selesai"></div>
                                <div class="col-12"><label class="form-label small fw-semibold">Jabatan</label>
                                    <input type="text" id="edit-we-job-position" class="form-control form-control-sm" placeholder="Jabatan"></div>
                                <div class="col-6"><label class="form-label small fw-semibold">Section</label>
                                    <input type="text" id="edit-we-section" class="form-control form-control-sm" placeholder="Section"></div>
                                <div class="col-6"><label class="form-label small fw-semibold">Departemen</label>
                                    <input type="text" id="edit-we-departemen" class="form-control form-control-sm" placeholder="Departemen"></div>
                                <div class="col-12"><label class="form-label small fw-semibold">Keterangan</label>
                                    <input type="text" id="edit-we-keterangan" class="form-control form-control-sm" placeholder="Keterangan"></div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="button" id="we-btn-save-edit" class="btn rounded-pill px-4" style="background:#6f42c1;color:white;">Simpan</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>
@endsection

@push('scripts')
    <!-- jQuery must be loaded before Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2 untuk form tambah
            $('.select2-form').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: function() {
                    $(this).data('placeholder');
                }
            });

            // Inisialisasi Select2 untuk filter tabel
            $('.select2-filter').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Inisialisasi Select2 untuk Modal Edit
            $('.select2-edit').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#modalEditMapping')
            });

            // Handler tombol Edit
            $('.btn-edit-mapping').on('click', function() {
                var id = $(this).data('id');
                var userId = $(this).data('user-id');
                var positionId = $(this).data('position-id');
                
                // Update url form action
                var url = "{{ route('user-job-position.update', ':id') }}".replace(':id', id);
                $('#formEditMapping').attr('action', url);
                
                // Set values and trigger change for Select2
                $('#editUserId').val(userId).trigger('change');
                $('#editPositionId').val(positionId).trigger('change');
                
                // Show modal
                $('#modalEditMapping').modal('show');
            });

            // Submit otomatis saat filter berubah
            $('#filterPosition').on('change', function() {
                $(this).closest('form').submit();
            });

            // ===== Modul 3.1: Working Experience CRUD =====
            var weCurrentUserId = null;
            var weApiBase = "{{ route('user-job-position.api.working-experience.index') }}";

            function weLoadData(userId) {
                var url = weApiBase + '?user_id=' + userId;
                $.getJSON(url, function(res) {
                    var tbody = $('#we-tbody');
                    tbody.empty();
                    var data = res.data || [];
                    if (data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center text-muted">Belum ada riwayat jabatan.</td></tr>');
                        return;
                    }
                    data.forEach(function(item) {
                        var yearEnd = item.year_end ? item.year_end : '<span class="badge bg-primary">Present</span>';
                        var row = $('<tr>');
                        row.append('<td>' + item.year_start + '</td>');
                        row.append('<td>' + yearEnd + '</td>');
                        row.append('<td>' + (item.job_position || '-') + '</td>');
                        row.append('<td>' + (item.section || '-') + '</td>');
                        row.append('<td>' + (item.departemen || '-') + '</td>');
                        row.append('<td>' + (item.keterangan || '-') + '</td>');
                        var editBtn = '<button class="btn btn-sm btn-outline-primary rounded-pill px-2 me-1 we-btn-edit" data-id="' + item.id + '"><i class="bi bi-pencil-fill"></i></button>';
                        var delBtn = '<button class="btn btn-sm btn-outline-danger rounded-pill px-2 we-btn-delete" data-id="' + item.id + '"><i class="bi bi-trash-fill"></i></button>';
                        row.append('<td class="text-center">' + editBtn + delBtn + '</td>');
                        tbody.append(row);
                    });
                }).fail(function() {
                    $('#we-tbody').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data.</td></tr>');
                });
            }

            // Open Working Experience modal
            $(document).on('click', '.btn-working-exp', function() {
                weCurrentUserId = $(this).data('user-id');
                var userName = $(this).data('user-name');
                $('#we-user-name').text(userName);
                // Reset form fields
                $('#we-year-start,#we-year-end,#we-job-position,#we-section,#we-departemen,#we-keterangan').val('');
                weLoadData(weCurrentUserId);
                $('#modalWorkingExp').modal('show');
            });

            // Add Working Experience
            $('#we-btn-add').on('click', function() {
                var yearStart = $('#we-year-start').val();
                var jobPosition = $('#we-job-position').val();
                if (!yearStart || !jobPosition) {
                    alert('Tahun Mulai dan Jabatan wajib diisi.');
                    return;
                }
                var payload = {
                    user_id: weCurrentUserId,
                    year_start: yearStart,
                    year_end: $('#we-year-end').val() || null,
                    job_position: jobPosition,
                    section: $('#we-section').val() || null,
                    departemen: $('#we-departemen').val() || null,
                    keterangan: $('#we-keterangan').val() || null,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };
                $.ajax({
                    url: weApiBase,
                    method: 'POST',
                    data: payload,
                    success: function() {
                        $('#we-year-start,#we-year-end,#we-job-position,#we-section,#we-departemen,#we-keterangan').val('');
                        weLoadData(weCurrentUserId);
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menyimpan data.';
                        alert(msg);
                    }
                });
            });

            // Open edit modal
            $(document).on('click', '.we-btn-edit', function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                var cells = row.find('td');
                $('#edit-we-id').val(id);
                $('#edit-we-year-start').val($(cells[0]).text().trim());
                $('#edit-we-year-end').val($(cells[1]).text().trim() === 'Present' ? '' : $(cells[1]).text().trim());
                $('#edit-we-job-position').val($(cells[2]).text().trim());
                $('#edit-we-section').val($(cells[3]).text().trim());
                $('#edit-we-departemen').val($(cells[4]).text().trim());
                $('#edit-we-keterangan').val($(cells[5]).text().trim());
                $('#modalEditWE').modal('show');
            });

            // Save edit
            $('#we-btn-save-edit').on('click', function() {
                var id = $('#edit-we-id').val();
                var baseUrl = "{{ route('user-job-position.api.working-experience.update', ':id') }}";
                var url = baseUrl.replace(':id', id);
                var payload = {
                    user_id: weCurrentUserId,
                    year_start: $('#edit-we-year-start').val(),
                    year_end: $('#edit-we-year-end').val() || null,
                    job_position: $('#edit-we-job-position').val(),
                    section: $('#edit-we-section').val() || null,
                    departemen: $('#edit-we-departemen').val() || null,
                    keterangan: $('#edit-we-keterangan').val() || null,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT'
                };
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: payload,
                    success: function() {
                        $('#modalEditWE').modal('hide');
                        weLoadData(weCurrentUserId);
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menyimpan.';
                        alert(msg);
                    }
                });
            });

            // Delete
            $(document).on('click', '.we-btn-delete', function() {
                if (!confirm('Hapus riwayat ini?')) return;
                var id = $(this).data('id');
                var baseUrl = "{{ route('user-job-position.api.working-experience.destroy', ':id') }}";
                var url = baseUrl.replace(':id', id);
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
                    success: function() { weLoadData(weCurrentUserId); },
                    error: function() { alert('Gagal menghapus.'); }
                });
            });
        });
    </script>
@endpush
