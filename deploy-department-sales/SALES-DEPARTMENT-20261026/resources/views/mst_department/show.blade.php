@extends('layout')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/hr/departments.css') }}">
@endpush

@section('content')
<main id="main" class="main department-page">
<div class="department-shell">
    <div class="department-heading d-flex flex-wrap justify-content-between align-items-center">
        <h1>{{ $department->name }}</h1>
        <a href="{{ route('mst-department.index') }}" class="btn btn-outline-secondary">Kembali ke Master Department</a>
    </div>
    @include('mst_department._alerts')
    <div class="card"><div class="card-body">
        <h2 class="department-panel-title">Edit Department</h2>
        <form method="POST" action="{{ route('mst-department.update', $department) }}" class="department-create-form row g-3 align-items-end mb-4">
            @csrf @method('PUT')
            <div class="col-md-8"><label for="department-name" class="form-label">Nama department</label>
                <input id="department-name" name="name" value="{{ old('name', $department->name) }}" class="form-control" required maxlength="100"></div>
            <div class="col-md-auto"><button class="btn btn-primary" type="submit">Simpan Nama</button></div>
        </form>
        <form method="POST" action="{{ route('mst-department.status', $department) }}">
            @csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $department->is_active ? 0 : 1 }}">
            <p>Status: <strong>{{ $department->is_active ? 'Aktif' : 'Nonaktif' }}</strong>. Department hanya dapat dinonaktifkan setelah seluruh section dan posisi aktif dipindahkan atau dinonaktifkan.</p>
            <button type="submit" class="btn btn-outline-secondary">{{ $department->is_active ? 'Nonaktifkan Department' : 'Aktifkan Department' }}</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <h2 class="department-panel-title">Section dan Pemindahan Struktur</h2>
        <p class="text-muted">Seluruh posisi dalam section ikut dipindahkan, termasuk posisi nonaktif. Jalur approval tetap; ubah terpisah di Master Job Position.</p>
        @forelse($department->sections as $section)
            <div class="department-section">
                <h6>{{ $section->name }} <span class="badge {{ $section->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</span></h6>
                <p>Department asal: <strong>{{ $department->name }}</strong> · Posisi terdampak: <strong>{{ $section->job_positions_count }}</strong></p>
                <form method="POST" action="{{ route('mst-department.move-section', [$department, $section]) }}" class="row g-3 align-items-end">
                    @csrf<input type="hidden" name="expected_department_id" value="{{ $department->id }}">
                    <input type="hidden" name="structure_token" value="{{ $tokens[$section->id] }}">
                    <div class="col-md-8"><label class="form-label" for="destination-{{ $section->id }}">Department tujuan</label>
                        <select class="form-select" name="department_id" id="destination-{{ $section->id }}" required>
                            <option value="">Pilih department aktif</option>
                            @foreach($destinations as $destination)<option value="{{ $destination->id }}">{{ $destination->name }}</option>@endforeach
                        </select></div>
                    <div class="col-md-auto"><button type="submit" class="btn btn-outline-primary" @disabled($destinations->isEmpty())>Pindahkan Section</button></div>
                </form>
            </div>
        @empty<p class="text-muted">Belum ada section pada department ini.</p>@endforelse
    </div></div>
    <div class="card"><div class="card-body">
        <h2 class="department-panel-title">Daftar Posisi</h2>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>Posisi</th><th>Section</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>@forelse($positions as $position)
                <tr><td>{{ $position->position_name }}</td><td>{{ $position->section?->name ?? 'Tanpa section' }}</td><td>{{ $position->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td><a href="{{ route('mst-job-position.edit', $position) }}" class="btn btn-sm btn-outline-primary">Edit Posisi / Approval</a></td></tr>
            @empty<tr><td colspan="4" class="text-muted text-center">Belum ada posisi.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $positions->links() }}
    </div></div>
</div>
</main>
@endsection
