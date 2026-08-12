@extends('layout')

@section('documentLanguage', 'id')

@push('styles')
    @vite('resources/css/km/foundation.css')
@endpush

@section('content')
<x-km.shell>
    <x-km.page-header title="Compliance Knowledge Management"
        description="Monitoring operasional assignment dan completion. Data ini bukan KPI atau dasar penilaian kinerja individu.">
        <x-slot:actions><a href="{{ route('dsKnowlege') }}" class="btn btn-outline-secondary">Kembali ke workspace</a></x-slot:actions>
    </x-km.page-header>

    <x-km.feedback :errors="$errors" />

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @if (app(\App\Services\KnowledgeManagement\KmAccessService::class)->canExport(auth()->user()))
            <a class="btn btn-outline-primary btn-sm" href="{{ route('km.compliance.export.details', 'xlsx') }}">Export HR XLSX</a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('km.compliance.export.details', 'csv') }}">Export HR CSV</a>
        @endif
        @if ($canViewAnalytics)
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('km.compliance.export.pdf') }}">PDF agregat</a>
        @endif
    </div>

    <section class="row g-3 mb-4" aria-label="Ringkasan compliance">
        @foreach ([
            'Assignment' => $summary['assignments'],
            'Penerima' => $summary['recipients'],
            'Selesai' => $summary['completed'],
            'Overdue' => $summary['overdue'],
            'Pengguna terlibat' => $summary['engaged_users'],
            'Rata-rata approval (jam)' => $summary['average_approval_hours'],
        ] as $label => $value)
            <div class="col-6 col-lg-2"><div class="km-panel h-100"><span class="text-muted small d-block">{{ $label }}</span><strong class="fs-4">{{ $value }}</strong></div></div>
        @endforeach
    </section>

    @if ($canManageAssignments)
        <section class="km-panel mb-4" aria-labelledby="assignment-form-title">
            <h2 class="h5" id="assignment-form-title">Buat assignment wajib</h2>
            <form method="POST" action="{{ route('km.compliance.assignments.store') }}" class="row g-3">
                @csrf
                <div class="col-lg-6">
                    <label for="assignment-version" class="form-label">Versi materi</label>
                    <select id="assignment-version" name="document_version_id" class="form-select" required>
                        <option value="">Pilih versi published</option>
                        @foreach ($versions as $version)
                            <option value="{{ $version->id }}">{{ $version->document?->judul ?? $version->title }} — v{{ $version->number() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4"><label for="assignment-title" class="form-label">Nama assignment</label><input id="assignment-title" name="title" class="form-control" required></div>
                <div class="col-lg-2"><label for="assignment-due" class="form-label">Tenggat</label><input id="assignment-due" type="datetime-local" name="due_at" class="form-control" value="{{ now()->addDays(14)->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-md-4">
                    <label for="assignment-users" class="form-label">Pengguna tertentu</label>
                    <select id="assignment-users" name="target_user_ids[]" class="form-select" multiple size="7">
                        @foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}{{ $user->npk ? ' - '.$user->npk : '' }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="assignment-departments" class="form-label">Departemen</label>
                    <select id="assignment-departments" name="target_department_ids[]" class="form-select" multiple size="7">
                        @foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="assignment-positions" class="form-label">Job position</label>
                    <select id="assignment-positions" name="target_job_position_ids[]" class="form-select" multiple size="7">
                        @foreach ($positions as $position)<option value="{{ $position->id }}">{{ $position->position_name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-10"><label for="assignment-reason" class="form-label">Alasan assignment</label><input id="assignment-reason" name="reason" class="form-control" maxlength="2000" required></div>
                <div class="col-lg-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Buat assignment</button></div>
            </form>
        </section>
    @endif

    <section class="km-panel mb-4" aria-labelledby="assignment-list-title">
        <h2 class="h5" id="assignment-list-title">Daftar assignment</h2>
        <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>Assignment</th><th>Tenggat</th><th>Penerima</th><th>Selesai</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($assignments as $assignment)
                    <tr><td>{{ $assignment->title }}</td><td>{{ $assignment->due_at->format('d-m-Y H:i') }}</td><td>{{ $assignment->users_count }}</td><td>{{ $assignment->completed_count }}</td><td>{{ $assignment->status }}</td></tr>
                @empty <tr><td colspan="5" class="text-center text-muted py-4">Belum ada assignment.</td></tr> @endforelse
            </tbody>
        </table></div>
        {{ $assignments->links() }}
    </section>

    <section class="km-panel mb-4" aria-labelledby="department-cohort-title">
        <h2 class="h5" id="department-cohort-title">Cohort departemen</h2>
        <p class="text-muted">Hanya departemen dengan minimal lima pengguna yang ditampilkan.</p>
        <div class="table-responsive"><table class="table"><thead><tr><th>Departemen</th><th>Cohort</th><th>Completion</th></tr></thead><tbody>
            @forelse ($departmentCohorts as $cohort)
                <tr><td>{{ $cohort->department_snapshot }}</td><td>{{ $cohort->cohort_size }}</td><td>{{ $cohort->completed_count }}</td></tr>
            @empty <tr><td colspan="3" class="text-muted">Belum ada cohort yang memenuhi batas privasi.</td></tr> @endforelse
        </tbody></table></div>
    </section>

    @if ($canOverrideCompletion)
        <section class="km-panel" aria-labelledby="override-title">
            <h2 class="h5" id="override-title">Override completion aksesibilitas</h2>
            <p class="text-muted">Gunakan hanya untuk kebutuhan aksesibilitas. Alasan wajib dan seluruh tindakan diaudit.</p>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pengguna</th><th>Materi</th><th>Tenggat</th><th>Tindakan</th></tr></thead><tbody>
                @forelse ($pendingRecipients as $recipient)
                    <tr>
                        <td>{{ $recipient->user?->name }}</td><td>{{ $recipient->assignment?->title }}</td><td>{{ $recipient->due_at->format('d-m-Y') }}</td>
                        <td>
                            <form class="d-flex gap-2 mb-2" method="POST" action="{{ route('km.compliance.override', ['version' => $recipient->assignment->document_version_id, 'user' => $recipient->user_id]) }}">@csrf<input name="reason" class="form-control form-control-sm" placeholder="Alasan aksesibilitas" required><button class="btn btn-outline-primary btn-sm" type="submit">Catat selesai</button></form>
                            @if ($canManageAssignments)
                                <form class="d-flex gap-2" method="POST" action="{{ route('km.compliance.exempt', $recipient) }}">@csrf<input name="reason" class="form-control form-control-sm" placeholder="Alasan pengecualian" required><button class="btn btn-outline-secondary btn-sm" type="submit">Exempt</button></form>
                            @endif
                        </td>
                    </tr>
                @empty <tr><td colspan="4" class="text-muted">Tidak ada penerima pending.</td></tr> @endforelse
            </tbody></table></div>
        </section>
    @endif
</x-km.shell>
@endsection
