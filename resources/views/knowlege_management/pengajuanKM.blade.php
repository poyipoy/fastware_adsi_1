@extends('layout')

@section('documentLanguage', 'id')

@push('styles')
    @vite('resources/css/km/foundation.css')
@endpush

@push('scripts')
    @vite('resources/js/km/authoring.js')
@endpush

@section('content')
<x-km.shell>
    <x-km.page-header
        title="Pengajuan Saya"
        description="Kelola draf dan kirim materi pengetahuan Anda untuk persetujuan.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kmModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Buat Draf
            </button>
        </x-slot:actions>
    </x-km.page-header>

    <x-km.feedback :errors="$errors" error-title="Pengajuan belum dapat diproses." />

    <section aria-labelledby="km-submission-list-title">
        <div class="km-panel">
            <div class="km-panel__header">
                <div>
                    <h2 class="km-panel__title" id="km-submission-list-title">Dokumen Saya</h2>
                    <p class="text-muted small mb-0">Status dan tindakan tersedia sesuai tahap dokumen.</p>
                </div>
                @if (! $km->isEmpty())
                    <span class="text-muted small">{{ $km->total() }} dokumen</span>
                @endif
            </div>

            <noscript>
                <style>
                    .km-app #kmModal { display: block; position: static; opacity: 1; }
                    .km-app #kmModal .modal-dialog { margin: 0 0 1rem; max-width: none; }
                    .km-app #kmModal .btn-close,
                    .km-app #kmModal [data-bs-dismiss="modal"] { display: none; }
                </style>
                <div class="alert alert-info" role="status">
                    JavaScript tidak aktif. Form pembuatan draf ditampilkan langsung di bawah daftar.
                </div>
            </noscript>

            @if ($km->isEmpty())
                <x-km.empty-state
                    icon="bi-file-earmark-plus"
                    title="Belum ada pengajuan"
                    description="Buat draf pertama untuk mulai membagikan pengetahuan kepada rekan kerja.">
                    <x-slot:actions>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kmModal">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            Buat Draf
                        </button>
                    </x-slot:actions>
                </x-km.empty-state>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <caption class="visually-hidden">Daftar pengajuan Knowledge Management milik pengguna</caption>
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">PIC</th>
                                <th scope="col">Judul</th>
                                <th scope="col">Status</th>
                                <th scope="col">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($km as $item)
                                <tr>
                                    <td>{{ $km->firstItem() + $loop->index }}</td>
                                    <td>{{ $item->user?->name ?? '-' }}</td>
                                    <td>
                                        <span class="km-table-title">{{ $item->judul }}</span>
                                        @if ($item->currentVersion)
                                            <span class="badge text-bg-light border ms-1">
                                                v{{ $item->currentVersion->number() }}
                                            </span>
                                        @endif
                                        @if ($item->processingState() === 'pending_processing')
                                            <span class="d-block text-warning-emphasis small mt-1">
                                                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                                Menunggu konversi
                                            </span>
                                        @endif
                                    </td>
                                    <td><x-km.status-badge :status="$item->status" /></td>
                                    <td>
                                        <div class="km-action-group text-nowrap">
                                            @can('update', $item)
                                                <button type="button" class="btn btn-outline-primary btn-sm btn-icon"
                                                    data-km-edit="{{ $item->id }}" title="Edit"
                                                    aria-label="Edit draf {{ $item->judul }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                            @endcan
                                            @can('revise', $item)
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-km-revise="{{ $item->id }}"
                                                    data-km-document-title="{{ $item->judul }}">
                                                    <i class="bi bi-files" aria-hidden="true"></i>
                                                    Buat Revisi
                                                </button>
                                            @endcan
                                            @can('deactivate', $item)
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-icon"
                                                    data-km-deactivate="{{ $item->id }}" title="Nonaktifkan"
                                                    aria-label="Nonaktifkan {{ $item->judul }}">
                                                    <i class="bi bi-slash-circle" aria-hidden="true"></i>
                                                </button>
                                            @endcan
                                            @can('submit', $item)
                                                <button type="button" class="btn btn-primary btn-sm"
                                                    data-km-submit="{{ $item->id }}" title="{{ $item->isReadyForSubmission()
                                                        ? 'Kirim untuk persetujuan'
                                                        : ($item->isOfficeFile() && $item->processingState() === 'ready'
                                                            ? 'Submission Office belum diaktifkan administrator'
                                                            : 'File masih menunggu konversi') }}"
                                                    @disabled(! $item->isReadyForSubmission())
                                                    aria-label="Kirim {{ $item->judul }} untuk persetujuan">
                                                    <i class="bi bi-send" aria-hidden="true"></i>
                                                    Kirim
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $km->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </section>

<div class="modal fade" id="kmModal" tabindex="-1" aria-labelledby="kmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <form action="{{ route('storeKM') }}" method="POST" enctype="multipart/form-data"
            class="modal-content" id="km-create-form" data-km-submit-protection>
            @csrf
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="kmModalLabel">Buat Draf KM</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                    <div class="mb-3">
                        <label for="judul" class="form-label">
                            Judul <span class="km-field-required" aria-hidden="true">*</span>
                        </label>
                        <input type="text" class="form-control @error('judul') is-invalid @enderror"
                            id="judul" name="judul" value="{{ old('judul') }}" maxlength="255" required
                            autocomplete="off" @error('judul') aria-invalid="true" aria-describedby="judul-error" @enderror>
                        @error('judul')
                            <div class="invalid-feedback" id="judul-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">
                            Sinopsis Isi Buku <span class="km-field-required" aria-hidden="true">*</span>
                        </label>
                        <textarea class="form-control @error('keterangan') is-invalid @enderror"
                            id="keterangan" name="keterangan" rows="4" maxlength="3000" required
                            @error('keterangan') aria-invalid="true" aria-describedby="keterangan-error" @enderror>{{ old('keterangan') }}</textarea>
                        @error('keterangan')
                            <div class="invalid-feedback" id="keterangan-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">
                            File Pengajuan <span class="km-field-required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control @error('file') is-invalid @enderror" type="file"
                            id="file" name="file" accept=".ppt,.pptx,.pdf" required
                            aria-describedby="file-help @error('file') file-error @enderror"
                            @error('file') aria-invalid="true" @enderror>
                        <div class="form-text" id="file-help">PDF diproses untuk pratinjau dan thumbnail otomatis. PPT/PPTX dapat dipratinjau setelah konversi selesai; file asli tidak dapat diunduh.</div>
                        @error('file')
                            <div class="invalid-feedback" id="file-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="reading_minutes" class="form-label">Estimasi Waktu Baca (menit)</label>
                        <input type="number" class="form-control @error('reading_minutes') is-invalid @enderror"
                            id="reading_minutes" name="reading_minutes" value="{{ old('reading_minutes') }}"
                            min="1" max="1440"
                            @error('reading_minutes') aria-invalid="true" aria-describedby="reading-minutes-error" @enderror>
                        @error('reading_minutes')
                            <div class="invalid-feedback" id="reading-minutes-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="km-tags-input" class="form-label">Tag</label>
                        <div class="km-tag-container" data-km-tag-picker>
                            <input type="text" id="km-tags-input" class="form-control" maxlength="50"
                                placeholder="Ketik lalu tekan Enter atau koma" aria-describedby="km-tags-feedback">
                        </div>
                        <div id="km-tags-feedback" class="km-tag-feedback" data-km-tag-feedback
                            role="status" aria-live="polite" hidden></div>
                        <input type="hidden" id="km-tags-csv" name="tags_csv" value="{{ old('tags_csv') }}">
                    </div>
                    <div class="mb-3 km-coauthor-picker" data-km-coauthor-picker="create">
                        <label for="km-coauthor-search" class="form-label">Co-author</label>
                        <input type="search" id="km-coauthor-search" class="form-control" maxlength="100" placeholder="Cari nama, email, atau NPK">
                        <div class="list-group mt-2 d-none" data-km-coauthor-results></div>
                        <div class="d-flex flex-wrap gap-1 mt-2" data-km-coauthor-selected></div>
                        <div data-km-coauthor-inputs></div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    Simpan Draf
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editKmModal" tabindex="-1" aria-labelledby="editKmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <form action="{{ route('updateKM') }}" method="POST" enctype="multipart/form-data"
            class="modal-content" id="km-draft-form" data-km-submit-protection>
            @csrf
            @method('PUT')
            <input type="hidden" id="editId" name="id">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="editKmModalLabel">Edit Draf KM</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                    <div class="mb-3">
                        <label for="editJudul" class="form-label">
                            Judul <span class="km-field-required" aria-hidden="true">*</span>
                        </label>
                        <input type="text" class="form-control @error('judul') is-invalid @enderror"
                            id="editJudul" name="judul" maxlength="255" required autocomplete="off"
                            @error('judul') aria-invalid="true" aria-describedby="edit-judul-error" @enderror>
                        @error('judul')
                            <div class="invalid-feedback" id="edit-judul-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="editKeterangan" class="form-label">
                            Sinopsis Isi Buku <span class="km-field-required" aria-hidden="true">*</span>
                        </label>
                        <textarea class="form-control @error('keterangan') is-invalid @enderror"
                            id="editKeterangan" name="keterangan" rows="4" maxlength="3000" required
                            @error('keterangan') aria-invalid="true" aria-describedby="edit-keterangan-error" @enderror></textarea>
                        @error('keterangan')
                            <div class="invalid-feedback" id="edit-keterangan-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="editReadingMinutes" class="form-label">Estimasi Waktu Baca (menit)</label>
                        <input type="number" class="form-control @error('reading_minutes') is-invalid @enderror"
                            id="editReadingMinutes" name="reading_minutes" min="1" max="1440"
                            @error('reading_minutes') aria-invalid="true" aria-describedby="edit-reading-minutes-error" @enderror>
                        @error('reading_minutes')
                            <div class="invalid-feedback" id="edit-reading-minutes-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit-km-tags-input" class="form-label">Tag</label>
                        <div class="km-tag-container" data-km-tag-picker>
                            <input type="text" id="edit-km-tags-input" class="form-control" maxlength="50"
                                placeholder="Ketik lalu tekan Enter atau koma" aria-describedby="edit-km-tags-feedback">
                        </div>
                        <div id="edit-km-tags-feedback" class="km-tag-feedback" data-km-tag-feedback
                            role="status" aria-live="polite" hidden></div>
                        <input type="hidden" id="edit-km-tags-csv" name="tags_csv" value="">
                    </div>
                    <div class="mb-3 km-coauthor-picker" data-km-coauthor-picker="edit">
                        <label for="edit-km-coauthor-search" class="form-label">Co-author</label>
                        <input type="search" id="edit-km-coauthor-search" class="form-control" maxlength="100" placeholder="Cari nama, email, atau NPK">
                        <div class="list-group mt-2 d-none" data-km-coauthor-results></div>
                        <div class="d-flex flex-wrap gap-1 mt-2" data-km-coauthor-selected></div>
                        <div data-km-coauthor-inputs></div>
                    </div>
                    <div id="km-autosave-status" class="km-autosave-status mb-3" aria-live="polite"></div>
                    <div class="mb-3">
                        <label for="editFile" id="editFileLabel" class="form-label">File Pengajuan</label>
                        <input class="form-control" type="file" id="editFile" name="file" accept=".ppt,.pptx,.pdf">
                        <div id="editFileLink" class="mt-2" hidden>
                            <span id="editFileName" class="fw-semibold"></span>
                        </div>
                        <div id="editFileState" class="form-text"></div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="kmRevisionModal" tabindex="-1" aria-labelledby="kmRevisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="km-revision-form">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="kmRevisionModalLabel">Buat Revisi Major</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="km-revision-document-title"></p>
                <label for="km-revision-note" class="form-label">
                    Catatan perubahan <span class="km-field-required" aria-hidden="true">*</span>
                </label>
                <textarea class="form-control" id="km-revision-note" name="change_note" rows="4"
                    minlength="5" maxlength="2000" required></textarea>
                <div class="form-text">
                    Revisi file atau isi akan menaikkan versi major dan wajib melalui persetujuan ulang.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-files" aria-hidden="true"></i>
                    Buat Draf Revisi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.kmAuthoringConfig = {
    csrfToken: @js(csrf_token()),
    editUrl: @js(route('editKM', ['id' => '__KM_ID__'])),
    deactivateUrl: @js(route('updateStatusKM', ['id' => '__KM_ID__'])),
    submitUrl: @js(route('kirimKM', ['id' => '__KM_ID__'])),
    autosaveUrl: @js(route('km.documents.autosave', ['kmPengajuan' => '__KM_ID__'])),
    coAuthorOptionsUrl: @js(route('km.co-authors.options')),
    majorRevisionUrl: @js(route('km.document-versions.major.store', ['kmPengajuan' => '__KM_ID__'])),
};
</script>
</x-km.shell>
@endsection
