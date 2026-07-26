@extends('layout')

@section('documentLanguage', 'id')

@push('styles')
    @vite('resources/css/km/foundation.css')
@endpush

@push('scripts')
    @vite('resources/js/km/authoring.js')
@endpush

@section('content')
<x-km.shell active="submissions">
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
                                    <td><span class="km-table-title">{{ $item->judul }}</span></td>
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
                                            @can('deactivate', $item)
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-icon"
                                                    data-km-deactivate="{{ $item->id }}" title="Nonaktifkan"
                                                    aria-label="Nonaktifkan {{ $item->judul }}">
                                                    <i class="bi bi-slash-circle" aria-hidden="true"></i>
                                                </button>
                                            @endcan
                                            @can('submit', $item)
                                                <button type="button" class="btn btn-primary btn-sm"
                                                    data-km-submit="{{ $item->id }}" title="Kirim untuk persetujuan"
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
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="kmModalLabel">Buat Draf KM</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('storeKM') }}" method="POST" enctype="multipart/form-data"
                id="km-create-form" data-km-submit-protection>
                @csrf
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
                        <div class="form-text" id="file-help">PDF mendukung preview dan thumbnail otomatis. PPT/PPTX hanya dapat diunduh.</div>
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
                            <input type="text" id="km-tags-input" class="form-control" maxlength="50" placeholder="Ketik lalu tekan Enter atau koma">
                        </div>
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
</div>

<div class="modal fade" id="editKmModal" tabindex="-1" aria-labelledby="editKmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="editKmModalLabel">Edit Draf KM</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('updateKM') }}" method="POST" enctype="multipart/form-data"
                id="km-draft-form" data-km-submit-protection>
                @csrf
                @method('PUT')
                <input type="hidden" id="editId" name="id">
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
                            <input type="text" id="edit-km-tags-input" class="form-control" maxlength="50" placeholder="Ketik lalu tekan Enter atau koma">
                        </div>
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
                            <a id="editFileName" href="#">Unduh file tersimpan</a>
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
</div>

<script>
window.kmAuthoringConfig = {
    csrfToken: @js(csrf_token()),
    editUrl: @js(route('editKM', ['id' => '__KM_ID__'])),
    deactivateUrl: @js(route('updateStatusKM', ['id' => '__KM_ID__'])),
    submitUrl: @js(route('kirimKM', ['id' => '__KM_ID__'])),
    autosaveUrl: @js(route('km.documents.autosave', ['kmPengajuan' => '__KM_ID__'])),
    coAuthorOptionsUrl: @js(route('km.co-authors.options')),
};
</script>
</x-km.shell>
@endsection
