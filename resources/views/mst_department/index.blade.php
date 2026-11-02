@extends('layout')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/hr/departments.css') }}">
@endpush

@section('content')
<main id="main" class="main department-page">
<div class="department-shell">
    <div class="department-heading d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1>Master Department</h1>
            <p class="text-muted mb-0">Kelola department, section, dan penempatan posisi.</p>
        </div>
        <a href="{{ route('mst-job-position.index') }}" class="btn btn-outline-secondary">Master Job Position</a>
    </div>
    @include('mst_department._alerts')
    <div class="card"><div class="card-body">
        <h2 class="department-panel-title">Tambah Department</h2>
        <form method="POST" action="{{ route('mst-department.store') }}" class="department-create-form row g-3 align-items-end">
            @csrf
            <div class="col-md-8"><label for="new-department-name" class="form-label">Nama department</label>
                <input id="new-department-name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="100" autocomplete="off"></div>
            <div class="col-md-auto"><button class="btn btn-primary" type="submit">Tambah Department</button></div>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h2 class="department-panel-title mb-0">Daftar Department</h2>
            <span class="text-muted">{{ $departments->total() }} department</span>
        </div>
        <form method="GET" action="{{ route('mst-department.index') }}" class="department-filters row g-3 align-items-end">
            <div class="col-md-6"><label class="form-label" for="department-search">Cari department</label>
                <input class="form-control" id="department-search" name="search" value="{{ request('search') }}" maxlength="100"></div>
            <div class="col-md-3"><label class="form-label" for="department-status">Status</label>
                <select class="form-select" name="is_active" id="department-status">
                    <option value="">Semua</option><option value="1" @selected(request('is_active') === '1')>Aktif</option><option value="0" @selected(request('is_active') === '0')>Nonaktif</option>
                </select></div>
            <div class="col-md-auto"><button class="btn btn-outline-primary" type="submit">Cari</button>
                <a class="btn btn-outline-secondary" href="{{ route('mst-department.index') }}">Reset</a></div>
        </form>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>Department</th><th>Status</th><th class="text-center">Section</th><th class="text-center">Posisi</th><th>Aksi</th></tr></thead>
            <tbody>@forelse($departments as $department)
                <tr><td>{{ $department->name }}</td><td><span class="badge {{ $department->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $department->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-center">{{ $department->sections_count }}</td><td class="text-center">{{ $department->job_positions_count }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('mst-department.show', $department) }}">Detail / Edit</a></td></tr>
            @empty<tr><td colspan="5" class="text-center text-muted">Tidak ada department sesuai pencarian.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $departments->links() }}
    </div></div>
</div>
</main>
@endsection
