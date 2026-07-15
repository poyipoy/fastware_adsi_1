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
        });
    </script>
@endpush
