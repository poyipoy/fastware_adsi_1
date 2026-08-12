@extends('layout')

@section('documentLanguage', 'id')

@push('styles')
    @vite('resources/css/km/foundation.css')
@endpush

@push('scripts')
    @vite('resources/js/km/approval.js')
@endpush

@section('content')
    @php
        $oldBulkItems = collect(old('items', []))->keyBy(
            fn ($item) => (string) ($item['document_id'] ?? '')
        );
        $oldBulkAction = match (old('action')) {
            'approved' => 'approve',
            'rejected' => 'reject',
            default => old('action', 'approve'),
        };
        $hasBulkErrors = old('items') !== null && $errors->any();
        $approvalErrorFields = ['id', 'action', 'judul', 'posisi', 'id_km_kategori', 'keterangan', 'reason'];
        $hasApprovalErrors = $errors->hasAny($approvalErrorFields) && old('id');
        $oldApprovalAction = old('action');
        if (! in_array($oldApprovalAction, ['approved', 'rejected'], true)) {
            $oldApprovalAction = old('reject') !== null
                ? 'rejected'
                : (old('approve') !== null ? 'approved' : '');
        }
    @endphp

    <x-km.shell>
        <div data-km-approval-page
            data-detail-url-template="{{ route('showPersetujuan', ['id' => '__KM_ID__']) }}">
            <x-km.page-header
                title="Persetujuan Materi"
                description="Tinjau dokumen satu per satu atau proses beberapa dokumen secara atomik.">
                <x-slot:actions>
                    <a class="btn btn-outline-primary" href="{{ route('km.analytics.popular') }}">
                        <i class="bi bi-bar-chart" aria-hidden="true"></i>
                        Materi Populer
                    </a>
                </x-slot:actions>
            </x-km.page-header>

            <x-km.feedback />

            <section aria-labelledby="km-approval-list-title">
                <div class="km-panel">
                    <div class="km-panel__header">
                        <div>
                            <h2 class="km-panel__title" id="km-approval-list-title">Antrean Persetujuan</h2>
                            <p class="text-muted small mb-0">Kategori dipilih per dokumen sebelum batch disetujui. SLA antrean dihitung dalam hari kerja Senin–Jumat.</p>
                        </div>
                        @if (! $km->isEmpty())
                            <span class="text-muted small">{{ $km->total() }} dokumen</span>
                        @endif
                    </div>

                    @if ($hasBulkErrors)
                        <div class="alert alert-danger mt-3" role="alert" tabindex="-1" data-km-bulk-error>
                            <strong>Batch belum diproses.</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($km->isEmpty())
                        <x-km.empty-state
                            icon="bi-check2-circle"
                            title="Antrean persetujuan kosong"
                            description="Tidak ada dokumen yang menunggu keputusan saat ini." />
                    @else
                        <form action="{{ route('km.approvals.bulk') }}" method="POST" id="bulkApprovalForm"
                            class="mt-3" data-km-bulk-form>
                            @csrf
                            <div class="km-panel km-bulk-toolbar">
                                <div class="row g-3 align-items-end">
                                    <div class="col-12 col-md-3">
                                        <label for="bulkAction" class="form-label">Tindakan batch</label>
                                        <select id="bulkAction" name="action" class="form-select" data-km-bulk-action required>
                                            <option value="approve" @selected($oldBulkAction === 'approve')>Setujui</option>
                                            <option value="reject" @selected($oldBulkAction === 'reject')>Tolak</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6" data-km-bulk-reason-group
                                        @if ($oldBulkAction !== 'reject') hidden @endif>
                                        <label for="bulkReason" class="form-label">
                                            Alasan penolakan <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <textarea id="bulkReason" name="reason"
                                            class="form-control @error('reason') is-invalid @enderror"
                                            rows="2" maxlength="2000" data-km-bulk-reason
                                            aria-describedby="bulk-reason-help @error('reason') bulk-reason-error @enderror"
                                            @error('reason') aria-invalid="true" @enderror
                                            @if ($oldBulkAction === 'reject') required @endif>{{ old('reason') }}</textarea>
                                        <div class="form-text" id="bulk-reason-help">Wajib untuk penolakan, maksimum 2.000 karakter.</div>
                                        @error('reason')
                                            <div class="invalid-feedback" id="bulk-reason-error">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12 col-md-3 d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                                        <span class="small text-muted" aria-live="polite" data-km-bulk-count>0 dipilih</span>
                                        <button type="submit" class="btn btn-primary" data-km-bulk-submit disabled>
                                            Proses batch
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-nowrap">
                                <caption class="visually-hidden">Daftar dokumen dan kategori untuk persetujuan Knowledge Management</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="text-center">
                                            <input type="checkbox" class="form-check-input" data-km-bulk-select-all
                                                aria-label="Pilih semua dokumen pending pada halaman ini">
                                        </th>
                                        <th scope="col">No</th>
                                        <th scope="col">PIC</th>
                                        <th scope="col">Judul</th>
                                        <th scope="col" style="min-width: 220px;">Kategori batch</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" aria-sort="{{ $approvalSort === 'oldest' ? 'ascending' : 'descending' }}">
                                            <a class="km-sort-link" href="{{ route('persetujuanKM', [
                                                ...request()->except(['sort', 'page']),
                                                'sort' => $approvalSort === 'oldest' ? 'newest' : 'oldest',
                                            ]) }}">
                                                Menunggu
                                                <i class="bi {{ $approvalSort === 'oldest' ? 'bi-sort-up' : 'bi-sort-down' }}" aria-hidden="true"></i>
                                                <span class="visually-hidden">
                                                    {{ $approvalSort === 'oldest' ? 'Urutkan terbaru lebih dahulu' : 'Urutkan terlama lebih dahulu' }}
                                                </span>
                                            </a>
                                        </th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($km as $item)
                                        @php
                                            $isPendingApproval = $item->status == $documentStatuses::PENDING_APPROVAL->value;
                                            $oldBulkItem = $oldBulkItems->get((string) $item->id, []);
                                            $bulkSelected = $oldBulkItem !== [];
                                        @endphp
                                        <tr data-doc-id="{{ $item->id }}">
                                            <td class="text-center">
                                                @if ($isPendingApproval)
                                                    <input type="checkbox" class="form-check-input"
                                                        name="items[{{ $loop->index }}][document_id]"
                                                        form="bulkApprovalForm" value="{{ $item->id }}"
                                                        data-km-bulk-checkbox
                                                        aria-label="Pilih {{ $item->judul }} untuk batch"
                                                        @checked($bulkSelected)>
                                                @else
                                                    <span class="text-muted" aria-hidden="true">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->user?->name ?? '-' }}</td>
                                            <td>
                                                <span class="km-table-title">{{ $item->judul }}</span>
                                            </td>
                                            <td>
                                                @if ($isPendingApproval)
                                                    <label class="visually-hidden" for="bulkCategory{{ $item->id }}">
                                                        Kategori untuk {{ $item->judul }}
                                                    </label>
                                                    <select id="bulkCategory{{ $item->id }}"
                                                        name="items[{{ $loop->index }}][id_km_kategori]"
                                                        form="bulkApprovalForm" class="form-select form-select-sm"
                                                        data-km-bulk-category
                                                        @if (! $bulkSelected || $oldBulkAction !== 'approve') disabled @endif>
                                                        <option value="">Pilih kategori</option>
                                                        @foreach ($kategoris as $kategori)
                                                            <option value="{{ $kategori->id }}"
                                                                @selected((string) ($oldBulkItem['id_km_kategori'] ?? $item->id_km_kategori) === (string) $kategori->id)>
                                                                {{ $kategori->nama_kategori }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="text-muted">{{ $item->kmKategori?->nama_kategori ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <x-km.status-badge :status="$item->status" />
                                            </td>
                                            <td>
                                                @if ($isPendingApproval)
                                                    <span class="d-block">{{ (int) $item->waiting_working_days }} hari kerja</span>
                                                    @if ($item->approval_overdue)
                                                        <span class="km-status km-status--overdue mt-1">
                                                            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                                            Terlambat
                                                        </span>
                                                    @else
                                                        <span class="km-table-meta">Dalam SLA</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @can('view', $item)
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-icon" title="Buka detail"
                                                            data-km-open-approval="{{ $item->id }}"
                                                            aria-label="Buka detail dokumen {{ $item->judul }}">
                                                            <i class="bi bi-folder2-open" aria-hidden="true"></i>
                                                        </button>
                                                    @endcan
                                                </div>

                                                @if ($isPendingApproval)
                                                    <noscript>
                                                        <details class="mt-2 text-wrap" style="min-width: 260px;">
                                                            <summary>Persetujuan tanpa JavaScript</summary>
                                                            <form action="{{ route('approveKM') }}" method="POST" class="mt-2">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                                <input type="hidden" name="judul" value="{{ $item->judul }}">
                                                                <input type="hidden" name="keterangan" value="{{ $item->keterangan }}">

                                                                <label class="form-label" for="noJsPosition{{ $item->id }}">Bagian</label>
                                                                <select class="form-select form-select-sm mb-2"
                                                                    id="noJsPosition{{ $item->id }}" name="posisi" required>
                                                                    @foreach (['HR', 'Dept. Head', 'Sec. Head', 'All Employee'] as $position)
                                                                        <option value="{{ $position }}" @selected($item->posisi === $position)>
                                                                            {{ $position }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>

                                                                <label class="form-label" for="noJsCategory{{ $item->id }}">Kategori</label>
                                                                <select class="form-select form-select-sm mb-2"
                                                                    id="noJsCategory{{ $item->id }}" name="id_km_kategori" required>
                                                                    <option value="">Pilih kategori</option>
                                                                    @foreach ($kategoris as $kategori)
                                                                        <option value="{{ $kategori->id }}"
                                                                            @selected($item->id_km_kategori == $kategori->id)>
                                                                            {{ $kategori->nama_kategori }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>

                                                                <label class="form-label" for="noJsReason{{ $item->id }}">
                                                                    Alasan jika menolak
                                                                </label>
                                                                <textarea class="form-control form-control-sm mb-2"
                                                                    id="noJsReason{{ $item->id }}" name="reason"
                                                                    maxlength="2000" rows="2"></textarea>
                                                                <div class="d-flex flex-wrap gap-1">
                                                                    <button type="submit" name="approve" value="1"
                                                                        class="btn btn-success btn-sm">Setujui</button>
                                                                    <button type="submit" name="reject" value="1"
                                                                        class="btn btn-danger btn-sm">Tolak</button>
                                                                </div>
                                                            </form>
                                                        </details>
                                                    </noscript>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $km->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>

                <div class="modal fade" id="editKmModal" tabindex="-1"
                    aria-labelledby="editKmModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="editKmModalLabel">Persetujuan Knowledge Management</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('approveKM') }}" method="POST" id="singleApprovalForm"
                                    data-restore-id="{{ $hasApprovalErrors ? (int) old('id') : '' }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" id="editId" name="id" value="{{ old('id') }}">
                                    <input type="hidden" id="approvalAction" name="action" value="{{ $oldApprovalAction }}">

                                    @error('id')
                                        <div class="alert alert-danger" role="alert">{{ $message }}</div>
                                    @enderror
                                    @if ($hasApprovalErrors)
                                        @error('action')
                                            <div class="alert alert-danger" role="alert">{{ $message }}</div>
                                        @enderror
                                    @endif

                                    <div class="mb-3">
                                        <label for="editJudul" class="form-label">Judul</label>
                                        <input type="text" class="form-control @if ($hasApprovalErrors) @error('judul') is-invalid @enderror @endif"
                                            id="editJudul" name="judul" value="{{ old('judul') }}" required
                                            @if ($hasApprovalErrors && $errors->has('judul'))
                                                aria-invalid="true" aria-describedby="approval-judul-error"
                                            @endif>
                                        @if ($hasApprovalErrors)
                                            @error('judul')
                                                <div class="invalid-feedback" id="approval-judul-error">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <label for="editKeterangan" class="form-label">Sinopsis Isi Buku</label>
                                        <textarea class="form-control @if ($hasApprovalErrors) @error('keterangan') is-invalid @enderror @endif"
                                            id="editKeterangan" name="keterangan" rows="4"
                                            @if ($hasApprovalErrors && $errors->has('keterangan'))
                                                aria-invalid="true" aria-describedby="approval-keterangan-error"
                                            @endif>{{ old('keterangan') }}</textarea>
                                        @if ($hasApprovalErrors)
                                            @error('keterangan')
                                                <div class="invalid-feedback" id="approval-keterangan-error">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    <div class="mb-3 d-none" id="editFileLink">
                                        <button type="button" id="editFileButton" class="btn btn-outline-primary btn-sm">
                                            Tampilkan Buku
                                        </button>
                                        <div id="editFileState" class="form-text"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="editPosisi" class="form-label">Bagian</label>
                                        <select id="editPosisi" name="posisi"
                                            class="form-select @if ($hasApprovalErrors) @error('posisi') is-invalid @enderror @endif"
                                            required
                                            @if ($hasApprovalErrors && $errors->has('posisi'))
                                                aria-invalid="true" aria-describedby="approval-posisi-error"
                                            @endif>
                                            <option value="">----- Pilih Bagian -----</option>
                                            @foreach (['HR', 'Dept. Head', 'Sec. Head', 'All Employee'] as $position)
                                                <option value="{{ $position }}" @selected(old('posisi') === $position)>{{ $position }}</option>
                                            @endforeach
                                        </select>
                                        @if ($hasApprovalErrors)
                                            @error('posisi')
                                                <div class="invalid-feedback" id="approval-posisi-error">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <label for="editKategori" class="form-label">Kategori</label>
                                        <select id="editKategori" name="id_km_kategori"
                                            class="form-select @if ($hasApprovalErrors) @error('id_km_kategori') is-invalid @enderror @endif"
                                            required
                                            @if ($hasApprovalErrors && $errors->has('id_km_kategori'))
                                                aria-invalid="true" aria-describedby="approval-kategori-error"
                                            @endif>
                                            <option value="">----- Pilih Kategori -----</option>
                                            @foreach ($kategoris as $kategori)
                                                <option value="{{ $kategori->id }}" @selected(old('id_km_kategori') == $kategori->id)>
                                                    {{ $kategori->nama_kategori }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($hasApprovalErrors)
                                            @error('id_km_kategori')
                                                <div class="invalid-feedback" id="approval-kategori-error">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    <fieldset class="border rounded-2 p-3 mb-3">
                                        <input type="hidden" name="organization_targets_submitted" value="1">
                                        <legend class="float-none w-auto px-1 fs-6 mb-1">Target organisasi <span class="fw-normal text-muted">(opsional)</span></legend>
                                        <p class="form-text mt-0">Kosongkan keduanya agar materi mengikuti audience saja. Jika dipilih, pengguna harus cocok dengan sedikitnya satu departemen atau posisi.</p>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="editTargetDepartments" class="form-label">Departemen</label>
                                                <select id="editTargetDepartments" name="target_department_ids[]" class="form-select" multiple size="5">
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->id }}" @selected(in_array((int) $department->id, array_map('intval', old('target_department_ids', [])), true))>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="editTargetJobPositions" class="form-label">Job position</label>
                                                <select id="editTargetJobPositions" name="target_job_position_ids[]" class="form-select" multiple size="5">
                                                    @foreach ($jobPositions as $jobPosition)
                                                        <option value="{{ $jobPosition->id }}" @selected(in_array((int) $jobPosition->id, array_map('intval', old('target_job_position_ids', [])), true))>
                                                            {{ $jobPosition->position_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </fieldset>

                                    <div class="mb-3" id="rejectReasonGroup"
                                        @if ($oldApprovalAction !== 'rejected') hidden @endif>
                                        <label for="rejectReason" class="form-label">
                                            Alasan Penolakan <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <textarea class="form-control @if ($hasApprovalErrors) @error('reason') is-invalid @enderror @endif"
                                            id="rejectReason" name="reason" rows="4" maxlength="2000"
                                            aria-describedby="reject-reason-help @if ($hasApprovalErrors && $errors->has('reason')) approval-reason-error @endif"
                                            @if ($hasApprovalErrors && $errors->has('reason')) aria-invalid="true" @endif
                                            @if ($oldApprovalAction === 'rejected') required @endif>{{ old('reason') }}</textarea>
                                        <div class="form-text" id="reject-reason-help">Maksimum 2.000 karakter.</div>
                                        @if ($hasApprovalErrors)
                                            @error('reason')
                                                <div class="invalid-feedback" id="approval-reason-error">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <span class="form-label d-block">Riwayat Persetujuan</span>
                                        <div id="approvalHistory" class="km-history p-2 text-muted small" aria-live="polite">
                                            Belum ada riwayat.
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        <button type="submit" id="approveButton" name="approve" value="1"
                                            class="btn btn-success" data-km-single-action="approved">
                                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Setujui
                                        </button>
                                        <button type="submit" id="rejectButton" name="reject" value="1"
                                            class="btn btn-danger" data-km-single-action="rejected">
                                            <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Tolak
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </x-km.shell>
@endsection
