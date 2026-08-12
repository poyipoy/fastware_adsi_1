@extends('layout')

@section('documentLanguage', 'id')

@push('styles')
    @vite('resources/css/km/foundation.css')
@endpush

@section('content')
<x-km.shell>
    <x-km.page-header title="Akses Knowledge Management"
        description="Kelola kemampuan operasional KM. Hak persetujuan tetap dikunci oleh aturan sistem dan tidak tersedia di sini.">
        <x-slot:actions>
            <a href="{{ route('dsKnowlege') }}" class="btn btn-outline-secondary">Kembali ke workspace</a>
        </x-slot:actions>
    </x-km.page-header>

    <x-km.feedback :errors="$errors" />

    <section class="km-panel mb-4" aria-labelledby="access-rule-form-title">
        <h2 class="h5" id="access-rule-form-title">Tambahkan rule akses</h2>
        <form method="POST" action="{{ route('km.access-rules.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label for="access-subject-type" class="form-label">Jenis subjek</label>
                <select id="access-subject-type" name="subject_type" class="form-select" required>
                    <option value="user">Pengguna</option>
                    <option value="role">Role</option>
                    <option value="job_position">Job position</option>
                </select>
            </div>
            <div class="col-md-5">
                <label for="access-subject-id" class="form-label">Subjek</label>
                <select id="access-subject-id" name="subject_id" class="form-select" required>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" data-subject-type="user">{{ $user->name }}{{ $user->npk ? ' - '.$user->npk : '' }}</option>
                    @endforeach
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" data-subject-type="role" hidden>{{ $role->role }}</option>
                    @endforeach
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" data-subject-type="job_position" hidden>{{ $position->position_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="access-ability" class="form-label">Kemampuan</label>
                <select id="access-ability" name="ability" class="form-select" required>
                    @foreach ($abilities as $ability => $label)
                        <option value="{{ $ability }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="access-effect" class="form-label">Efek</label>
                <select id="access-effect" name="effect" class="form-select" required>
                    <option value="allow">Izinkan</option>
                    <option value="deny">Tolak</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="access-from" class="form-label">Mulai berlaku</label>
                <input id="access-from" type="datetime-local" name="valid_from" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="access-until" class="form-label">Berakhir</label>
                <input id="access-until" type="datetime-local" name="valid_until" class="form-control">
            </div>
            <div class="col-md-4">
                <label for="access-reason" class="form-label">Alasan</label>
                <input id="access-reason" name="reason" class="form-control" maxlength="2000" required>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">Simpan rule</button>
            </div>
        </form>
    </section>

    <section class="km-panel" aria-labelledby="access-rules-title">
        <h2 class="h5" id="access-rules-title">Rule aktif dan terjadwal</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Subjek</th><th>Kemampuan</th><th>Efek</th><th>Periode</th><th>Alasan</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td>{{ $subjectLabels[$rule->subject_type][$rule->subject_id] ?? '#'.$rule->subject_id }}</td>
                            <td>{{ $abilities[$rule->ability] ?? $rule->ability }}</td>
                            <td><span class="badge {{ $rule->effect === 'allow' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $rule->effect }}</span></td>
                            <td>{{ $rule->valid_from?->format('d-m-Y H:i') ?? 'sekarang' }} – {{ $rule->valid_until?->format('d-m-Y H:i') ?? 'tanpa batas' }}</td>
                            <td>{{ $rule->reason }}</td>
                            <td>
                                <form method="POST" action="{{ route('km.access-rules.destroy', $rule) }}" class="d-flex gap-2">
                                    @csrf @method('DELETE')
                                    <input name="reason" class="form-control form-control-sm" placeholder="Alasan hapus" required>
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada rule RBAC KM.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rules->links() }}
    </section>
</x-km.shell>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('access-subject-type');
    const subject = document.getElementById('access-subject-id');
    const sync = () => {
        let first = null;
        Array.from(subject.options).forEach((option) => {
            const visible = option.dataset.subjectType === type.value;
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible && first === null) first = option;
        });
        if (subject.selectedOptions[0]?.disabled && first) first.selected = true;
    };
    type?.addEventListener('change', sync);
    sync();
});
</script>
@endsection
