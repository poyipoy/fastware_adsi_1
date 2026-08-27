@extends('layout')

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Master Job Position</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">HR</a></li>
                <li class="breadcrumb-item active">Master Job Position</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Daftar Job Position & Rute Approval</h5>
                <a href="{{ route('mst-job-position.create') }}" class="btn btn-primary rounded-pill shadow-sm px-4">
                    <i class="fas fa-plus me-1"></i> Tambah Posisi
                </a>
            </div>

            <!-- Filter Panel -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('mst-job-position.index') }}" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="position_name" class="form-label small fw-semibold text-secondary">Nama Job Position</label>
                                <input type="text" name="position_name" id="position_name" class="form-control rounded-3" 
                                       placeholder="Cari nama posisi..." value="{{ request('position_name') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="department_id" class="form-label small fw-semibold text-secondary">Department</label>
                                <select name="department_id" id="department_id" class="form-select rounded-3">
                                    <option value="">Semua Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="section_id" class="form-label small fw-semibold text-secondary">Section</label>
                                <select name="section_id" id="section_id" class="form-select rounded-3">
                                    <option value="">Semua Section</option>
                                    @foreach($sections as $sec)
                                        <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>
                                            {{ $sec->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="is_active" class="form-label small fw-semibold text-secondary">Status</label>
                                <select name="is_active" id="is_active" class="form-select rounded-3">
                                    <option value="">Semua Status</option>
                                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Non-aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('mst-job-position.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="datatable table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px">No</th>
                                    <th>Job Position</th>
                                    <th>Department</th>
                                    <th>Section</th>
                                    <th class="text-center">Approval 0<br><small class="text-muted fw-normal">Sub Sec Head</small></th>
                                    <th class="text-center">Approval 1<br><small class="text-muted fw-normal">Section Head</small></th>
                                    <th class="text-center">Approval 2<br><small class="text-muted fw-normal">Dept Head</small></th>
                                    <th class="text-center">Approval 3<br><small class="text-muted fw-normal">Div Head</small></th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($positions as $pos)
                                    @php
                                        $approval0 = $pos->approvalRoutes->firstWhere('approval_level', 0);
                                        $approval1 = $pos->approvalRoutes->firstWhere('approval_level', 1);
                                        $approval2 = $pos->approvalRoutes->firstWhere('approval_level', 2);
                                        $approval3 = $pos->approvalRoutes->firstWhere('approval_level', 3);
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $pos->position_name }}</td>
                                        <td class="text-secondary">{{ $pos->department->name ?? '—' }}</td>
                                        <td class="text-secondary">{{ $pos->section->name ?? '—' }}</td>
                                        <td class="text-center">
                                            @if($approval0?->approverPosition)
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">
                                                    {{ $approval0->approverPosition->position_name }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($approval1?->approverPosition)
                                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                                                    {{ $approval1->approverPosition->position_name }}
                                                </span>
                                            @elseif($approval1)
                                                <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Belum dipetakan</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($approval2?->approverPosition)
                                                <span class="badge bg-info-subtle text-info rounded-pill px-3">
                                                    {{ $approval2->approverPosition->position_name }}
                                                </span>
                                            @elseif($approval2)
                                                <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Belum dipetakan</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($approval3?->approverPosition)
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">
                                                    {{ $approval3->approverPosition->position_name }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($pos->is_active)
                                                <span class="badge bg-success rounded-pill px-3">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary rounded-pill px-3">Non-aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('mst-job-position.edit', $pos->id) }}"
                                               class="btn btn-sm btn-warning rounded-pill px-3 me-1">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <form method="POST" action="{{ route('mst-job-position.toggle-active', $pos->id) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm {{ $pos->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} rounded-pill px-3 me-1"
                                                        type="submit" title="{{ $pos->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    <i class="fas {{ $pos->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('mst-job-position.destroy', $pos->id) }}" class="d-inline"
                                                  onsubmit="return confirm('Hapus posisi ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger rounded-pill px-3" type="submit">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            Belum ada data Job Position. <a href="{{ route('mst-job-position.create') }}">Tambah sekarang</a>.
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
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filterForm');
    const deptSelect = document.getElementById('department_id');
    const sectionSelect = document.getElementById('section_id');
    const isActiveSelect = document.getElementById('is_active');
    const positionNameInput = document.getElementById('position_name');

    // Auto submit form on dropdown change
    deptSelect.addEventListener('change', function() {
        // Clear section selection when department changes to avoid mismatch
        sectionSelect.value = "";
        form.submit();
    });

    sectionSelect.addEventListener('change', function() {
        form.submit();
    });

    isActiveSelect.addEventListener('change', function() {
        form.submit();
    });

    // Auto submit when user finishes typing and changes focus
    positionNameInput.addEventListener('change', function() {
        form.submit();
    });
});
</script>
@endpush
@endsection
