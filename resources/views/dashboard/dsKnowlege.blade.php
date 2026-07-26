@extends('layout')

@section('documentLanguage', 'id')

@push('styles')
    @vite(['resources/css/km/foundation.css', 'resources/css/km/dashboard.css'])
@endpush

@push('scripts')
    @vite('resources/js/km/dashboard.js')
@endpush

@section('content')
@php
    $hasActiveFilters = collect(request()->except('page'))
        ->contains(fn ($value) => $value !== null && $value !== '');
    $hasAdvancedFilters = collect(request()->except(['page', 'q']))
        ->contains(fn ($value) => $value !== null && $value !== '');
@endphp

<x-km.shell active="library">
    <x-km.page-header
        title="Knowledge Library"
        description="Temukan, baca, dan simpan materi pengetahuan yang tersedia untuk Anda.">
        <x-slot:actions>
            @can('create', \App\Models\KmPengajuan::class)
                <a href="{{ route('pengajuanKM') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Buat Pengajuan
                </a>
            @endcan
        </x-slot:actions>
    </x-km.page-header>

    <x-km.feedback :errors="$errors" error-title="Filter belum dapat diterapkan." />

    <section aria-labelledby="km-library-heading">
        <h2 class="visually-hidden" id="km-library-heading">Daftar materi Knowledge Management</h2>

        <div class="km-panel km-filter-bar">
            <form method="GET" action="{{ route('dsKnowlege') }}" id="km-filter-form">
                <label for="km-search" class="form-label">Cari materi</label>
                <div class="row g-2 align-items-stretch">
                    <div class="col-lg-9">
                        <div class="input-group km-search-control">
                            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input type="search" id="km-search" name="q" value="{{ $filters['q'] ?? '' }}"
                                class="form-control" maxlength="100"
                                placeholder="Cari judul atau sinopsis" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-lg-3 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            Cari
                        </button>
                    </div>
                </div>

                <details class="km-filter-disclosure" @if ($hasAdvancedFilters) open @endif>
                    <summary>
                        <span>Filter lanjutan</span>
                        @if ($hasAdvancedFilters)
                            <span class="km-active-filter-note">Filter aktif</span>
                        @endif
                    </summary>
                    <div class="km-filter-disclosure__body">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3 col-md-6">
                                <label for="km-category" class="form-label">Kategori</label>
                                <select id="km-category" name="category" class="form-select">
                                    <option value="">Semua kategori</option>
                                    @foreach ($kategoris as $kategori)
                                        <option value="{{ $kategori->id }}" @selected(($filters['category'] ?? null) == $kategori->id)>
                                            {{ $kategori->nama_kategori }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="km-tags" class="form-label">Tag</label>
                                <select id="km-tags" name="tag_ids[]" class="form-select" multiple size="3"
                                    aria-describedby="km-tags-help">
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}"
                                            @selected(in_array((int) $tag->id, array_map('intval', $filters['tag_ids'] ?? []), true))>
                                            {{ $tag->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="km-tags-help">Pilih satu atau beberapa tag (cocok salah satu).</div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="km-read-status" class="form-label">Status baca</label>
                                <select id="km-read-status" name="read_status" class="form-select">
                                    <option value="">Semua status</option>
                                    <option value="unread" @selected(($filters['read_status'] ?? null) === 'unread')>Belum dibaca</option>
                                    <option value="reading" @selected(($filters['read_status'] ?? null) === 'reading')>Sedang dibaca</option>
                                    <option value="completed" @selected(($filters['read_status'] ?? null) === 'completed')>Selesai</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="km-sort" class="form-label">Urutkan</label>
                                <select id="km-sort" name="sort" class="form-select">
                                    <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Terbaru</option>
                                    <option value="oldest" @selected(($filters['sort'] ?? null) === 'oldest')>Terlama</option>
                                    <option value="title_asc" @selected(($filters['sort'] ?? null) === 'title_asc')>Judul A-Z</option>
                                    <option value="popular" @selected(($filters['sort'] ?? null) === 'popular')>Terpopuler</option>
                                    <option value="relevance" @selected(($filters['sort'] ?? null) === 'relevance')>Relevansi pencarian</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="km-date-from" class="form-label">Dari tanggal</label>
                                <input id="km-date-from" type="date" name="date_from"
                                    value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="km-date-to" class="form-label">Sampai tanggal</label>
                                <input id="km-date-to" type="date" name="date_to"
                                    value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="km-per-page" class="form-label">Per halaman</label>
                                <select id="km-per-page" name="per_page" class="form-select">
                                    @foreach ([12, 24, 48] as $size)
                                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 12) === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input id="km-bookmarked" type="checkbox" name="bookmarked" value="1"
                                        class="form-check-input" @checked($filters['bookmarked'] ?? false)>
                                    <label for="km-bookmarked" class="form-check-label">Hanya Baca Nanti</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                                <a href="{{ route('dsKnowlege') }}" class="btn btn-outline-secondary">Reset</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel" aria-hidden="true"></i>
                                    Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </details>
            </form>
        </div>

        <div class="row g-4">
            @forelse ($pengajuans as $pengajuan)
                @php
                    $transaction = $pengajuan->kmTransaksi->first();
                    $hasFile = $pengajuan->hasCompletePrivateFileMetadata();
                    $isPdf = $hasFile && $pengajuan->isPreviewableFile();
                    $isBookmarked = (bool) $pengajuan->is_bookmarked;
                    $isCompleted = $transaction
                        && (int) $transaction->status === \App\Enums\KnowledgeManagement\KmReadStatus::COMPLETED->value;
                    $isReading = $transaction
                        && (int) $transaction->status === \App\Enums\KnowledgeManagement\KmReadStatus::READING->value;
                @endphp

                <div class="col-xl-3 col-lg-4 col-md-6 d-flex align-items-stretch">
                    <article class="card km-document-card border-0 shadow-sm w-100">
                        <div class="km-thumbnail-wrapper">
                            <img src="{{ route('km.documents.thumbnail', $pengajuan) }}"
                                alt="Thumbnail {{ $pengajuan->judul }}" loading="lazy">
                            @if ($isCompleted)
                                <span class="position-absolute top-0 end-0 m-2 badge bg-success" title="Selesai dibaca">
                                    <i class="bi bi-check-lg"></i>
                                </span>
                            @endif
                        </div>

                        <div class="card-body d-flex flex-column p-3">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <small class="text-primary fw-semibold">{{ $pengajuan->kmKategori?->nama_kategori ?? '-' }}</small>
                                @if ($pengajuan->reading_minutes)
                                    <small class="text-muted text-nowrap">
                                        <i class="bi bi-clock"></i> {{ $pengajuan->reading_minutes }} menit
                                    </small>
                                @endif
                            </div>

                            <h2 class="km-document-title" title="{{ $pengajuan->judul }}">{{ $pengajuan->judul }}</h2>

                            @if ($pengajuan->tags->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1 mb-2" aria-label="Tag dokumen">
                                    @foreach ($pengajuan->tags as $tag)
                                        <span class="badge text-bg-light border">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($pengajuan->coAuthors->isNotEmpty())
                                <small class="text-muted mb-2">
                                    Bersama {{ $pengajuan->coAuthors->pluck('name')->join(', ') }}
                                </small>
                            @endif

                            <div class="d-flex justify-content-between align-items-center mt-auto mb-3 small text-muted">
                                <span><i class="bi bi-eye"></i> {{ (int) ($pengajuan->total_views ?? 0) }}</span>
                                <span>
                                    @if ($isCompleted)
                                        Selesai
                                    @elseif ($isReading)
                                        Sedang dibaca
                                    @else
                                        Belum dibaca
                                    @endif
                                </span>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm flex-grow-1 km-open-document"
                                    @disabled(! $hasFile)
                                    data-document-id="{{ $pengajuan->id }}"
                                    data-preview-url="{{ $hasFile ? route('km.documents.preview', $pengajuan) : '' }}"
                                    data-download-url="{{ $hasFile ? route('km.documents.download', $pengajuan) : '' }}"
                                    data-is-pdf="{{ $isPdf ? 'true' : 'false' }}"
                                    data-title="{{ $pengajuan->judul }}"
                                    data-can-complete="{{ $isCompleted ? 'false' : 'true' }}">
                                    <i class="bi bi-book me-1"></i>{{ $hasFile ? ($isReading ? 'Lanjutkan' : 'Buka') : 'File belum tersedia' }}
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm km-show-synopsis"
                                    data-title="{{ $pengajuan->judul }}"
                                    data-synopsis="{{ $pengajuan->keterangan }}" title="Lihat sinopsis" aria-label="Lihat sinopsis">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center px-3 py-2">
                            <div class="d-flex gap-3">
                                <button type="button" class="btn btn-sm p-0 btn-like {{ $pengajuan->is_liked ? 'liked' : '' }}"
                                    data-km-like data-document-id="{{ $pengajuan->id }}"
                                    aria-label="Sukai dokumen" aria-pressed="{{ $pengajuan->is_liked ? 'true' : 'false' }}">
                                    <i class="bi bi-hand-thumbs-up-fill"></i>
                                    <span data-km-like-count>{{ $pengajuan->km_sukas_count }}</span>
                                </button>
                                <button type="button" class="btn btn-sm p-0 text-muted" data-bs-toggle="modal"
                                    data-bs-target="#insightModal{{ $pengajuan->id }}" title="Insight" aria-label="Buka insight">
                                    <i class="bi bi-chat"></i>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm km-bookmark-btn" data-km-bookmark
                                data-document-id="{{ $pengajuan->id }}"
                                data-store-url="{{ route('km.bookmarks.store', $pengajuan) }}"
                                data-destroy-url="{{ route('km.bookmarks.destroy', $pengajuan) }}"
                                data-bookmarked="{{ $isBookmarked ? 'true' : 'false' }}"
                                aria-label="{{ $isBookmarked ? 'Hapus dari Baca Nanti' : 'Simpan ke Baca Nanti' }}"
                                aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}"
                                title="{{ $isBookmarked ? 'Hapus dari Baca Nanti' : 'Simpan ke Baca Nanti' }}">
                                <i class="km-bookmark-icon bi {{ $isBookmarked ? 'bi-bookmark-fill' : 'bi-bookmark' }}"></i>
                            </button>
                        </div>
                    </article>

                    <div class="modal fade" id="insightModal{{ $pengajuan->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h2 class="modal-title fs-5">Insight - {{ $pengajuan->judul }}</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    @forelse ($pengajuan->insights as $insight)
                                        <div class="mb-3 pb-2 border-bottom">
                                            <strong>{{ $insight->user?->name ?? '-' }}</strong>
                                            <small class="text-muted ms-2">{{ $insight->created_at?->format('d M Y H:i') }}</small>
                                            <p class="mb-0 mt-1">{{ $insight->content }}</p>
                                        </div>
                                    @empty
                                        <p class="text-muted">Belum ada insight.</p>
                                    @endforelse
                                    <form action="{{ route('insights.add') }}" method="POST" data-km-submit-protection>
                                        @csrf
                                        <input type="hidden" name="id_km_pengajuan" value="{{ $pengajuan->id }}">
                                        <label for="insight-{{ $pengajuan->id }}" class="form-label">Tambah insight</label>
                                        <textarea id="insight-{{ $pengajuan->id }}" class="form-control" name="content"
                                            rows="3" maxlength="1200" required></textarea>
                                        <button type="submit" class="btn btn-primary btn-sm mt-2">Kirim</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <x-km.empty-state
                        :icon="$hasActiveFilters ? 'bi-search' : 'bi-journal-x'"
                        :title="$hasActiveFilters ? 'Filter tidak menemukan hasil' : 'Belum ada materi'"
                        :description="$hasActiveFilters
                            ? 'Ubah atau hapus filter untuk melihat materi lain.'
                            : 'Materi yang dapat Anda akses akan tampil di sini.'">
                        @if ($hasActiveFilters)
                            <x-slot:actions>
                                <a href="{{ route('dsKnowlege') }}" class="btn btn-outline-primary">Reset filter</a>
                            </x-slot:actions>
                        @endif
                    </x-km.empty-state>
                </div>
            @endforelse
        </div>

        @if ($pengajuans->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $pengajuans->links('pagination::bootstrap-5') }}
            </div>
        @endif

        <div class="mt-5">
            <h2 class="fs-5 mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>Peringkat Pembaca Teraktif</h2>
            <div class="d-flex gap-3 overflow-auto pb-2">
                @forelse ($leaderboard->take(10) as $index => $leader)
                    <div class="km-leaderboard-item border bg-white p-3 text-center">
                        <span class="badge text-bg-dark mb-2">#{{ $index + 1 }}</span>
                        <div class="fw-semibold text-truncate" title="{{ $leader->name }}">{{ $leader->name }}</div>
                        <small class="text-muted">{{ number_format($leader->km_total_poin ?? 0) }} poin</small>
                    </div>
                @empty
                    <p class="text-muted">Belum ada data poin.</p>
                @endforelse
            </div>
        </div>
    </section>

<div class="modal fade" id="globalSynopsisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="globalSynopsisTitle">Sinopsis</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p id="globalSynopsisContent" class="mb-0 km-synopsis-content"></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="km-viewer-modal" tabindex="-1" aria-labelledby="km-viewer-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-light">
            <div class="modal-header py-2 bg-white km-viewer-toolbar">
                <h2 class="modal-title fs-6 text-truncate" id="km-viewer-modal-title">Pratinjau Dokumen</h2>
                <div class="ms-auto d-flex align-items-center gap-2 flex-wrap justify-content-end">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ukuran halaman">
                        <button type="button" class="btn btn-outline-secondary" data-km-viewer-action="fit-width"
                            title="Sesuaikan lebar" aria-label="Sesuaikan lebar dokumen">
                            <i class="bi bi-arrows-expand"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary active" data-km-viewer-action="fit-page"
                            title="Sesuaikan halaman" aria-label="Sesuaikan seluruh halaman">
                            <i class="bi bi-file-earmark"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-km-viewer-action="zoom-out"
                            title="Perkecil" aria-label="Perkecil dokumen">
                            <i class="bi bi-zoom-out"></i>
                        </button>
                        <span id="km-viewer-zoom-label" class="btn btn-outline-secondary disabled km-viewer-label">100%</span>
                        <button type="button" class="btn btn-outline-secondary" data-km-viewer-action="zoom-in"
                            title="Perbesar" aria-label="Perbesar dokumen">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Navigasi halaman">
                        <button type="button" class="btn btn-outline-secondary" data-km-viewer-action="previous"
                            title="Halaman sebelumnya" aria-label="Buka halaman sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span id="km-viewer-page-info" class="btn btn-outline-secondary disabled km-viewer-label">- / -</span>
                        <button type="button" class="btn btn-outline-secondary" data-km-viewer-action="next"
                            title="Halaman berikutnya" aria-label="Buka halaman berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <button type="button" id="km-viewer-complete" class="btn btn-sm btn-success" hidden>
                        <i class="bi bi-check-circle me-1"></i>Tandai selesai
                    </button>
                    <a id="km-viewer-download-link" href="#" class="btn btn-sm btn-outline-primary" hidden>
                        <i class="bi bi-download me-1"></i>Unduh
                    </a>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0 position-relative km-viewer-body" id="km-viewer-container">
                <div id="km-viewer-loading" class="km-viewer-message">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0">Memuat dokumen...</p>
                </div>
                <div id="km-viewer-canvas-wrapper" class="km-viewer-canvas-wrapper">
                    <canvas id="km-viewer-canvas"></canvas>
                </div>
                <div id="km-viewer-error" class="km-viewer-message d-none" role="alert">
                    <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                    <p id="km-viewer-error-msg" class="mt-2 mb-0">Gagal memuat dokumen.</p>
                </div>
                <div id="km-viewer-fallback" class="km-viewer-message d-none">
                    <i class="bi bi-file-earmark-arrow-down fs-1 text-primary"></i>
                    <p id="km-viewer-fallback-msg" class="mt-2 mb-0">Preview belum tersedia.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.kmConfig = {
    csrfToken: @js(csrf_token()),
    completionUrl: @js(route('kmTransaksi.saveTransaction')),
    dashboardUrl: @js(route('dsKnowlege')),
    likeUrl: @js(route('kmSuka.like')),
    unlikeUrl: @js(route('kmSuka.unlike')),
    markAsReadUrl: @js(route('kmTransaksi.markAsRead')),
};
</script>
</x-km.shell>
@endsection
