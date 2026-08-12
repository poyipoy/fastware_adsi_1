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
    $filterQuery = collect($filters)
        ->except(['page', 'document', 'assignment', 'insight'])
        ->reject(fn ($value) => $value === null || $value === '' || $value === [])
        ->all();
    $removeFilterUrl = static function (string $key, ?int $tagId = null) use ($filterQuery): string {
        $query = $filterQuery;
        if ($key === 'tag_ids' && $tagId !== null) {
            $remainingTags = array_values(array_filter(
                array_map('intval', $query['tag_ids'] ?? []),
                static fn (int $currentTagId): bool => $currentTagId !== $tagId,
            ));
            if ($remainingTags === []) {
                unset($query['tag_ids']);
            } else {
                $query['tag_ids'] = $remainingTags;
            }
        } else {
            unset($query[$key]);
        }

        return route('dsKnowlege', $query);
    };
    $activeFilterChips = [];
    if (! empty($filterQuery['q'])) {
        $activeFilterChips[] = ['label' => 'Pencarian: '.$filterQuery['q'], 'url' => $removeFilterUrl('q')];
    }
    if (! empty($filterQuery['category'])) {
        $category = $kategoris->firstWhere('id', (int) $filterQuery['category']);
        $activeFilterChips[] = [
            'label' => 'Kategori: '.($category?->nama_kategori ?? 'Tidak diketahui'),
            'url' => $removeFilterUrl('category'),
        ];
    }
    $selectedTagIds = array_values(array_unique(array_map('intval', $filterQuery['tag_ids'] ?? [])));
    foreach ($selectedTagIds as $tagId) {
        $tag = $tags->firstWhere('id', $tagId);
        $activeFilterChips[] = [
            'label' => 'Tag: '.($tag?->name ?? 'Tidak diketahui'),
            'url' => $removeFilterUrl('tag_ids', $tagId),
        ];
    }
    if (! empty($filterQuery['read_status'])) {
        $readStatusLabels = ['unread' => 'Belum dibaca', 'reading' => 'Sedang dibaca', 'completed' => 'Selesai'];
        $activeFilterChips[] = [
            'label' => 'Status: '.($readStatusLabels[$filterQuery['read_status']] ?? $filterQuery['read_status']),
            'url' => $removeFilterUrl('read_status'),
        ];
    }
    if (! empty($filterQuery['date_from'])) {
        $activeFilterChips[] = ['label' => 'Dari: '.$filterQuery['date_from'], 'url' => $removeFilterUrl('date_from')];
    }
    if (! empty($filterQuery['date_to'])) {
        $activeFilterChips[] = ['label' => 'Sampai: '.$filterQuery['date_to'], 'url' => $removeFilterUrl('date_to')];
    }
    if (($filterQuery['bookmarked'] ?? false) === true) {
        $activeFilterChips[] = ['label' => 'Baca Nanti', 'url' => $removeFilterUrl('bookmarked')];
    }
    if (($filterQuery['mandatory'] ?? false) === true) {
        $activeFilterChips[] = ['label' => 'Materi Wajib Saya', 'url' => $removeFilterUrl('mandatory')];
    }
    if (! empty($filterQuery['sort']) && $filterQuery['sort'] !== 'latest') {
        $sortLabels = [
            'oldest' => 'Terlama',
            'title_asc' => 'Judul A-Z',
            'popular' => 'Terpopuler',
            'relevance' => 'Relevansi pencarian',
        ];
        $activeFilterChips[] = [
            'label' => 'Urutan: '.($sortLabels[$filterQuery['sort']] ?? $filterQuery['sort']),
            'url' => $removeFilterUrl('sort'),
        ];
    }
    if (! empty($filterQuery['per_page']) && (int) $filterQuery['per_page'] !== 12) {
        $activeFilterChips[] = [
            'label' => 'Per halaman: '.(int) $filterQuery['per_page'],
            'url' => $removeFilterUrl('per_page'),
        ];
    }
    $hasActiveFilters = $activeFilterChips !== [];
    $advancedFilterCount = (! empty($filterQuery['category']) ? 1 : 0)
        + count($selectedTagIds)
        + (! empty($filterQuery['read_status']) ? 1 : 0)
        + (! empty($filterQuery['date_from']) ? 1 : 0)
        + (! empty($filterQuery['date_to']) ? 1 : 0)
        + (! empty($filterQuery['bookmarked']) ? 1 : 0)
        + (! empty($filterQuery['mandatory']) ? 1 : 0)
        + (! empty($filterQuery['per_page']) && (int) $filterQuery['per_page'] !== 12 ? 1 : 0);
    $hasAdvancedFilters = $advancedFilterCount > 0;
    $isCompletionEligible = static function ($transaction, $document): bool {
        if (! $document->isPreviewableFile()) {
            return true;
        }
        $pagesTotal = (int) ($transaction?->pages_total ?? 0);
        if ($pagesTotal <= 0) {
            return false;
        }
        $requiredPages = (int) ceil(
            min(1, max(0, (float) config('knowledge_management.reading.unique_page_ratio', 0.9))) * $pagesTotal
        );
        $minimumSeconds = max(0, (int) config('knowledge_management.reading.minimum_active_seconds', 60));
        $perPageSeconds = max(0, (int) config('knowledge_management.reading.seconds_per_page', 20));
        $maximumSeconds = max($minimumSeconds, (int) config('knowledge_management.reading.maximum_required_seconds', 900));
        $requiredSeconds = max($minimumSeconds, min($perPageSeconds * $pagesTotal, $maximumSeconds));

        return (int) ($transaction?->unique_pages_count ?? 0) >= $requiredPages
            && (int) ($transaction?->active_seconds ?? 0) >= $requiredSeconds;
    };
@endphp

<x-km.shell>
    <x-km.page-header
        title="Knowledge Workspace"
        description="Kelola aktivitas membaca dan temukan materi pengetahuan yang relevan untuk pekerjaan Anda." />

    <x-km.feedback :errors="$errors" error-title="Filter belum dapat diterapkan." />

    @if (($mandatoryContext['available'] ?? true) === false)
        <div class="alert alert-warning km-mandatory-notice" role="status">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <span>{{ $mandatoryContext['notice'] }}</span>
        </div>
    @endif

    <div class="km-dashboard-content">
        <section class="km-workspace-overview" aria-labelledby="km-workspace-summary-title">
            <div class="km-workspace-overview__heading">
                <div>
                    <p class="km-section-kicker">Ringkasan pribadi</p>
                    <h2 id="km-workspace-summary-title">Aktivitas pengetahuan Anda</h2>
                </div>
                <p>Pantau progres membaca dan kembali ke pekerjaan KM yang paling relevan.</p>
            </div>

            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <a class="km-summary-card" href="{{ route('dsKnowlege', ['read_status' => 'reading']) }}">
                        <span class="km-summary-card__icon" aria-hidden="true"><i class="bi bi-book-half"></i></span>
                        <span class="km-summary-card__content">
                            <span>Sedang dibaca</span>
                            <strong>{{ number_format($workspaceSummary['reading_count']) }}</strong>
                            <small>Lanjutkan progres aktif</small>
                        </span>
                    </a>
                </div>
                <div class="col-6 col-xl-3">
                    <a class="km-summary-card" href="{{ route('dsKnowlege', ['read_status' => 'completed']) }}">
                        <span class="km-summary-card__icon" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                        <span class="km-summary-card__content">
                            <span>Selesai dibaca</span>
                            <strong>{{ number_format($workspaceSummary['completed_count']) }}</strong>
                            <small>Materi yang dituntaskan</small>
                        </span>
                    </a>
                </div>
                <div class="col-6 col-xl-3">
                    <a class="km-summary-card" href="{{ route('dsKnowlege', ['bookmarked' => 1]) }}">
                        <span class="km-summary-card__icon" aria-hidden="true"><i class="bi bi-bookmark"></i></span>
                        <span class="km-summary-card__content">
                            <span>Baca nanti</span>
                            <strong>{{ number_format($workspaceSummary['bookmarked_count']) }}</strong>
                            <small>Materi yang disimpan</small>
                        </span>
                    </a>
                </div>
                <div class="col-6 col-xl-3">
                    <a class="km-summary-card" href="#km-leaderboard-title">
                        <span class="km-summary-card__icon" aria-hidden="true"><i class="bi bi-award"></i></span>
                        <span class="km-summary-card__content">
                            <span>Poin saya</span>
                            <strong>{{ number_format($workspaceSummary['points']) }}</strong>
                            <small>Lihat posisi Anda</small>
                        </span>
                    </a>
                </div>
            </div>

            <nav class="km-quick-actions" aria-label="Akses cepat Knowledge Management">
                <span class="km-quick-actions__label">Akses cepat</span>
                <div class="km-quick-actions__links">
                    @can('create', \App\Models\KmPengajuan::class)
                        <a href="{{ route('pengajuanKM') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            Buat Pengajuan
                        </a>
                        <a href="{{ route('pengajuanKM') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                            Pengajuan Saya
                        </a>
                    @endcan
                    @can('bulkApprove', \App\Models\KmPengajuan::class)
                        <a href="{{ route('persetujuanKM') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-check2-square" aria-hidden="true"></i>
                            Persetujuan
                        </a>
                    @endcan
                    @can('viewPopularAnalytics', \App\Models\KmPengajuan::class)
                        <a href="{{ route('km.analytics.popular') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-bar-chart" aria-hidden="true"></i>
                            Materi Populer
                        </a>
                    @endcan
                    @if (app(\App\Services\KnowledgeManagement\KmAccessService::class)->canManageAccess(auth()->user()))
                        <a href="{{ route('km.access-rules.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            Kelola Akses
                        </a>
                    @endif
                    @if (app(\App\Services\KnowledgeManagement\KmAccessService::class)->canManageAssignments(auth()->user()) || app(\App\Services\KnowledgeManagement\KmAccessService::class)->canViewAnalytics(auth()->user()))
                        <a href="{{ route('km.compliance.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-clipboard2-check" aria-hidden="true"></i>
                            Compliance
                        </a>
                    @endif
                    <a href="{{ route('dsKnowlege', ['mandatory' => 1]) }}"
                        class="btn btn-outline-primary btn-sm km-mandatory-shortcut">
                        <i class="bi bi-clipboard2-check" aria-hidden="true"></i>
                        Materi Wajib
                        <span class="badge {{ $mandatorySummary['overdue_count'] > 0 ? 'text-bg-danger' : 'text-bg-light' }}">
                            {{ number_format($mandatorySummary['active_count']) }}
                        </span>
                        @if ($mandatorySummary['overdue_count'] > 0)
                            <span class="visually-hidden">{{ $mandatorySummary['overdue_count'] }} melewati deadline</span>
                        @endif
                    </a>
                    <a href="{{ route('dsKnowlege', ['bookmarked' => 1]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-bookmark" aria-hidden="true"></i>
                        Baca Nanti
                    </a>
                </div>
            </nav>
            <div class="km-gamification-summary" aria-label="Tier dan badge Knowledge Management">
                <div>
                    <span class="km-section-kicker">Recognition pembelajaran</span>
                    <strong>{{ $gamificationProfile['tier'] ?? 'Belum bertier' }}</strong>
                    @if ($gamificationProfile['next_tier'])
                        <small>{{ number_format($gamificationProfile['points_to_next']) }} poin menuju {{ $gamificationProfile['next_tier'] }}</small>
                    @else
                        <small>Tier tertinggi telah dicapai.</small>
                    @endif
                    @if ($gamificationProfile['tier'])
                        <a class="btn btn-outline-secondary btn-sm mt-2" target="_blank" rel="noopener" href="{{ route('km.recognition.certificate') }}">Lihat sertifikat internal</a>
                    @endif
                </div>
                <div class="km-badge-list" aria-label="Badge yang diperoleh">
                    @forelse ($gamificationProfile['badges'] as $userBadge)
                        <span class="km-badge-chip" title="{{ $userBadge->badge?->description }}">
                            <i class="bi bi-patch-check" aria-hidden="true"></i>{{ $userBadge->badge?->name }}
                        </span>
                    @empty
                        <span class="text-muted small">Badge pertama akan muncul setelah milestone tervalidasi.</span>
                    @endforelse
                </div>
            </div>
        </section>

        @if ($continueReading->isNotEmpty())
            <section class="km-panel km-continue-section" aria-labelledby="km-continue-reading-title">
                <div class="km-panel__header">
                    <div>
                        <h2 class="km-panel__title" id="km-continue-reading-title">Lanjutkan Membaca</h2>
                        <p class="text-muted small mb-0">Kembali ke halaman terakhir dari materi yang baru Anda buka.</p>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach ($continueReading as $reading)
                        @php
                            $continueDocument = $reading->kmPengajuan;
                            $continueVersion = $reading->documentVersion;
                            $continueVersionId = $continueVersion?->getKey();
                            $continueHasFile = $continueVersion?->isReady()
                                ?? ($continueDocument?->hasCompletePrivateFileMetadata() ?? false);
                            $continueIsPdf = $continueVersion?->isReady()
                                ?? ($continueHasFile && $continueDocument->isPreviewableFile());
                            $continueTitle = $continueVersion?->title ?? $continueDocument?->judul;
                            $continueCategory = $continueVersion?->category?->nama_kategori
                                ?? $continueDocument?->kmKategori?->nama_kategori;
                            $continuePreviewUrl = ! $continueDocument
                                ? ''
                                : ($continueVersion
                                    ? route('km.document-versions.preview', [$continueDocument, $continueVersion])
                                    : route('km.documents.preview', $continueDocument));
                            $continueProgress = (int) ($reading->progress_percent ?? 0);
                            $continueCanComplete = $continueDocument
                                ? $isCompletionEligible($reading, $continueDocument)
                                : false;
                        @endphp
                        @if ($continueDocument)
                            <div class="col-12 col-lg-4">
                                <article class="km-continue-card">
                                    <div class="min-w-0">
                                        <span class="km-table-meta">{{ $continueCategory ?? 'Tanpa kategori' }}</span>
                                        <h3 class="km-continue-card__title">{{ $continueTitle }}</h3>
                                        <div class="progress km-reading-progress" role="progressbar"
                                            aria-label="Progres baca {{ $continueTitle }}"
                                            aria-valuenow="{{ $continueProgress }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar" style="width: {{ $continueProgress }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $continueProgress }}% halaman dibuka</small>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm km-open-document"
                                        @disabled(! $continueHasFile)
                                        data-document-id="{{ $continueDocument->id }}"
                                        @if ($continueVersionId) data-document-version-id="{{ $continueVersionId }}" @endif
                                        data-preview-url="{{ $continueHasFile ? $continuePreviewUrl : '' }}"
                                        data-progress-url="{{ route('km.reading.progress', $continueDocument) }}"
                                        data-resume-page="{{ max(1, (int) ($reading->last_page ?? 1)) }}"
                                        data-progress-percent="{{ $continueProgress }}"
                                        data-active-seconds="{{ (int) ($reading->active_seconds ?? 0) }}"
                                        data-unique-pages-count="{{ (int) ($reading->unique_pages_count ?? 0) }}"
                                        data-completed="false"
                                        data-is-pdf="{{ $continueIsPdf ? 'true' : 'false' }}"
                                        data-title="{{ $continueTitle }}"
                                        data-can-complete="{{ $continueCanComplete ? 'true' : 'false' }}">
                                        <i class="bi bi-book" aria-hidden="true"></i>
                                        Lanjutkan
                                    </button>
                                </article>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @if (($filters['mandatory'] ?? false) !== true && $mandatoryMaterials->isNotEmpty())
            <section class="km-panel km-mandatory-section" aria-labelledby="km-mandatory-title">
                <div class="km-panel__header km-mandatory-section__header">
                    <div>
                        <p class="km-section-kicker">Pembelajaran wajib</p>
                        <h2 class="km-panel__title" id="km-mandatory-title">Materi Wajib Saya</h2>
                        <p class="text-muted small mb-0">Selesaikan materi sesuai deadline yang ditetapkan untuk Anda.</p>
                    </div>
                    <div class="km-mandatory-summary" aria-label="Ringkasan materi wajib">
                        <span><strong>{{ number_format($mandatorySummary['active_count']) }}</strong> aktif</span>
                        @if ($mandatorySummary['overdue_count'] > 0)
                            <span class="is-overdue"><strong>{{ number_format($mandatorySummary['overdue_count']) }}</strong> terlambat</span>
                        @endif
                        <span><strong>{{ number_format($mandatorySummary['completed_count']) }}</strong> selesai</span>
                        <a href="{{ route('dsKnowlege', ['mandatory' => 1]) }}" class="btn btn-outline-primary btn-sm">
                            Lihat semua
                        </a>
                    </div>
                </div>

                <div class="km-mandatory-list">
                    @foreach ($mandatoryMaterials as $mandatoryMaterial)
                        @include('dashboard.partials.km-mandatory-material-card', ['mandatoryMaterial' => $mandatoryMaterial])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($recommendedMaterials->isNotEmpty())
            <section class="km-panel km-recommendation-section" aria-labelledby="km-recommendation-title">
                <div class="km-panel__header">
                    <div>
                        <p class="km-section-kicker">Untuk Anda</p>
                        <h2 class="km-panel__title" id="km-recommendation-title">Rekomendasi Materi</h2>
                        <p class="text-muted small mb-0">Disusun dari riwayat belajar, lalu dilengkapi materi populer dan terbaru.</p>
                    </div>
                </div>
                <div class="km-recommendation-grid">
                    @foreach ($recommendedMaterials as $recommended)
                        @php
                            $recommendedReady = $recommended->isPreviewableFile();
                        @endphp
                        <article class="km-recommendation-card">
                            <div class="km-recommendation-card__meta">
                                <span>{{ $recommended->kmKategori?->nama_kategori ?? 'Tanpa kategori' }}</span>
                                <span>{{ $recommended->recommendation_reason }}</span>
                            </div>
                            <h3>{{ $recommended->judul }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit($recommended->keterangan, 120) }}</p>
                            <button type="button" class="btn btn-outline-primary btn-sm km-open-document"
                                @disabled(! $recommendedReady)
                                data-document-id="{{ $recommended->id }}"
                                @if ($recommended->published_version_id) data-document-version-id="{{ $recommended->published_version_id }}" @endif
                                data-preview-url="{{ $recommendedReady ? route('km.documents.preview', $recommended) : '' }}"
                                data-progress-url="{{ route('km.reading.progress', $recommended) }}"
                                data-resume-page="1" data-progress-percent="0" data-active-seconds="0"
                                data-unique-pages-count="0" data-completed="false" data-is-pdf="true"
                                data-title="{{ $recommended->judul }}" data-can-complete="false">
                                <i class="bi bi-book" aria-hidden="true"></i>
                                Buka Materi
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="km-panel km-filter-bar" aria-labelledby="km-discovery-title">
            <div class="km-panel__header">
                <div>
                    <p class="km-section-kicker">Temukan materi</p>
                    <h2 class="km-panel__title" id="km-discovery-title">Pencarian dan filter</h2>
                    <p class="text-muted small mb-0">Cari cepat, lalu buka filter lanjutan bila diperlukan.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('dsKnowlege') }}" id="km-filter-form">
                <div class="row g-2 align-items-end km-primary-filters">
                    <div class="col-xl-7 col-lg-6">
                        <label for="km-search" class="form-label">Cari materi</label>
                        <div class="input-group km-search-control">
                            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input type="search" id="km-search" name="q" value="{{ $filters['q'] ?? '' }}"
                                class="form-control" maxlength="100"
                                placeholder="Cari judul atau sinopsis" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3">
                        <label for="km-sort" class="form-label">Urutkan</label>
                        <select id="km-sort" name="sort" class="form-select">
                            <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Terbaru</option>
                            <option value="oldest" @selected(($filters['sort'] ?? null) === 'oldest')>Terlama</option>
                            <option value="title_asc" @selected(($filters['sort'] ?? null) === 'title_asc')>Judul A-Z</option>
                            <option value="popular" @selected(($filters['sort'] ?? null) === 'popular')>Terpopuler</option>
                            <option value="relevance" @selected(($filters['sort'] ?? null) === 'relevance')>Relevansi pencarian</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            Cari
                        </button>
                    </div>
                </div>

                <details class="km-filter-disclosure" data-km-advanced-filter @if ($hasAdvancedFilters) open @endif>
                    <summary>
                        <span class="km-filter-disclosure__heading">
                            <span class="km-filter-disclosure__icon" aria-hidden="true">
                                <i class="bi bi-funnel"></i>
                            </span>
                            <span>
                                <strong>Filter lanjutan</strong>
                                <small>Kategori, tag, status baca, periode, kewajiban, dan tampilan.</small>
                            </span>
                        </span>
                        @if ($hasAdvancedFilters)
                            <span class="km-active-filter-note">{{ $advancedFilterCount }} aktif</span>
                        @endif
                    </summary>
                    <div class="km-filter-disclosure__body">
                        <div class="row g-3">
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
                                <label for="km-read-status" class="form-label">Status baca</label>
                                <select id="km-read-status" name="read_status" class="form-select">
                                    <option value="">Semua status</option>
                                    <option value="unread" @selected(($filters['read_status'] ?? null) === 'unread')>Belum dibaca</option>
                                    <option value="reading" @selected(($filters['read_status'] ?? null) === 'reading')>Sedang dibaca</option>
                                    <option value="completed" @selected(($filters['read_status'] ?? null) === 'completed')>Selesai</option>
                                </select>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-label" id="km-tags-label">Tag</div>
                                <details class="km-tag-filter" data-km-tag-filter>
                                    <summary aria-labelledby="km-tags-label km-tags-summary" aria-describedby="km-tags-help">
                                        <span id="km-tags-summary" data-km-tag-summary aria-live="polite">
                                            {{ count($selectedTagIds) > 0 ? count($selectedTagIds).' tag dipilih' : 'Semua tag' }}
                                        </span>
                                    </summary>
                                    <div class="km-tag-filter__menu">
                                        <div class="km-tag-filter__search" data-km-tag-search hidden>
                                            <label for="km-tag-search" class="visually-hidden">Cari tag</label>
                                            <i class="bi bi-search" aria-hidden="true"></i>
                                            <input id="km-tag-search" type="search" class="form-control"
                                                placeholder="Cari tag" autocomplete="off">
                                        </div>
                                        <div class="km-tag-filter__options" data-km-tag-options>
                                            @forelse ($tags as $tag)
                                                <label class="km-tag-filter__option" data-km-tag-option>
                                                    <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                                                        class="form-check-input"
                                                        @checked(in_array((int) $tag->id, $selectedTagIds, true))>
                                                    <span>{{ $tag->name }}</span>
                                                </label>
                                            @empty
                                                <p class="km-tag-filter__empty mb-0">Belum ada tag tersedia.</p>
                                            @endforelse
                                            <p class="km-tag-filter__empty mb-0" data-km-tag-empty hidden>Tag tidak ditemukan.</p>
                                        </div>
                                    </div>
                                </details>
                                <div class="form-text" id="km-tags-help">Pilih satu atau beberapa tag (cocok salah satu).</div>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-lg-2 col-md-6">
                                <label for="km-date-from" class="form-label">Dari tanggal</label>
                                <input id="km-date-from" type="date" name="date_from"
                                    value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="km-date-to" class="form-label">Sampai tanggal</label>
                                <input id="km-date-to" type="date" name="date_to"
                                    value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="km-per-page" class="form-label">Per halaman</label>
                                <select id="km-per-page" name="per_page" class="form-select">
                                    @foreach ([12, 24, 48] as $size)
                                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 12) === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="km-bookmark-filter" for="km-bookmarked">
                                    <input id="km-bookmarked" type="checkbox" name="bookmarked" value="1"
                                        class="form-check-input" @checked($filters['bookmarked'] ?? false)>
                                    <span>
                                        <strong>Hanya Baca Nanti</strong>
                                        <small>Tampilkan materi yang Anda simpan.</small>
                                    </span>
                                </label>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="km-bookmark-filter km-mandatory-filter" for="km-mandatory">
                                    <input id="km-mandatory" type="checkbox" name="mandatory" value="1"
                                        class="form-check-input" @checked($filters['mandatory'] ?? false)>
                                    <span>
                                        <strong>Hanya Materi Wajib Saya</strong>
                                        <small>Tampilkan tugas aktif yang ditujukan kepada Anda.</small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="km-filter-actions">
                            <a href="{{ route('dsKnowlege') }}" class="btn btn-outline-secondary">Reset Semua</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel" aria-hidden="true"></i>
                                Terapkan
                            </button>
                        </div>
                    </div>
                </details>
            </form>

            @if ($activeFilterChips !== [])
                <div class="km-active-filters" aria-label="Filter yang sedang aktif">
                    <div class="km-active-filters__header">
                        <span>{{ count($activeFilterChips) }} filter aktif</span>
                        <a href="{{ route('dsKnowlege') }}">Hapus semua</a>
                    </div>
                    <div class="km-active-filters__list">
                        @foreach ($activeFilterChips as $chip)
                            <a href="{{ $chip['url'] }}" class="km-filter-chip"
                                aria-label="Hapus filter {{ $chip['label'] }}">
                                <span>{{ $chip['label'] }}</span>
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        @php
            $isMandatoryCatalog = $mandatoryAssignments !== null;
            $catalogPaginator = $mandatoryAssignments ?? $pengajuans;
        @endphp
        <section class="km-catalog" aria-labelledby="km-library-heading" id="km-material-catalog">
            <div class="km-catalog-header">
                <div>
                    <p class="km-section-kicker">{{ $isMandatoryCatalog ? 'Pembelajaran wajib' : 'Katalog materi' }}</p>
                    <h2 id="km-library-heading">{{ $isMandatoryCatalog ? 'Semua Materi Wajib Saya' : 'Materi tersedia' }}</h2>
                    <p>
                        @if ($catalogPaginator->total() > 0)
                            Menampilkan {{ number_format($catalogPaginator->firstItem()) }}&ndash;{{ number_format($catalogPaginator->lastItem()) }}
                            dari {{ number_format($catalogPaginator->total()) }} materi.
                        @else
                            Belum ada materi yang sesuai untuk ditampilkan.
                        @endif
                    </p>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route('dsKnowlege') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        Reset filter
                    </a>
                @endif
            </div>

            @if ($isMandatoryCatalog)
                <div class="km-mandatory-list">
                    @forelse ($mandatoryAssignments as $mandatoryMaterial)
                        @include('dashboard.partials.km-mandatory-material-card', ['mandatoryMaterial' => $mandatoryMaterial])
                    @empty
                        <x-km.empty-state
                            icon="bi-clipboard2-x"
                            title="Materi wajib tidak ditemukan"
                            description="Tidak ada assignment aktif yang sesuai dengan filter Anda.">
                            <x-slot:actions>
                                <a href="{{ route('dsKnowlege') }}" class="btn btn-outline-primary">Reset filter</a>
                            </x-slot:actions>
                        </x-km.empty-state>
                    @endforelse
                </div>
            @else
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
                    $progressPercent = (int) ($transaction?->progress_percent ?? 0);
                    $canComplete = ! $isCompleted && $isCompletionEligible($transaction, $pengajuan);
                    $visibleTags = $pengajuan->tags->take(2);
                    $remainingTagCount = max(0, $pengajuan->tags->count() - $visibleTags->count());
                    $visibleCoAuthors = $pengajuan->coAuthors->take(2);
                    $remainingCoAuthorCount = max(0, $pengajuan->coAuthors->count() - $visibleCoAuthors->count());
                    $mandatoryAssignment = $pengajuan->mandatory_assignment;
                @endphp

                <div class="col-xl-3 col-lg-4 col-md-6 d-flex align-items-stretch">
                    <article class="card km-document-card w-100">
                        <div class="km-thumbnail-wrapper">
                            <img src="{{ route('km.documents.thumbnail', $pengajuan) }}"
                                alt="Sampul materi {{ $pengajuan->judul }}" loading="lazy">
                            <span class="km-thumbnail-badges">
                                @if ($mandatoryAssignment)
                                    <span class="km-mandatory-badge {{ $mandatoryAssignment['status'] === 'overdue' ? 'is-overdue' : '' }}">
                                        <i class="bi bi-clipboard2-check" aria-hidden="true"></i>
                                        Wajib
                                    </span>
                                @endif
                                @if ($isCompleted)
                                    <span class="km-document-status-badge" title="Selesai dibaca">
                                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                                        Selesai
                                    </span>
                                @endif
                            </span>
                            <button type="button" class="btn btn-sm km-bookmark-btn km-bookmark-btn--overlay"
                                data-km-bookmark data-document-id="{{ $pengajuan->id }}"
                                data-store-url="{{ route('km.bookmarks.store', $pengajuan) }}"
                                data-destroy-url="{{ route('km.bookmarks.destroy', $pengajuan) }}"
                                data-bookmarked="{{ $isBookmarked ? 'true' : 'false' }}"
                                aria-label="{{ $isBookmarked ? 'Hapus dari Baca Nanti' : 'Simpan ke Baca Nanti' }}"
                                aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}"
                                title="{{ $isBookmarked ? 'Hapus dari Baca Nanti' : 'Simpan ke Baca Nanti' }}">
                                <i class="km-bookmark-icon bi {{ $isBookmarked ? 'bi-bookmark-fill' : 'bi-bookmark' }}"
                                    aria-hidden="true"></i>
                            </button>
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

                            <h3 class="km-document-title" title="{{ $pengajuan->judul }}">{{ $pengajuan->judul }}</h3>

                            @if ($mandatoryAssignment)
                                <div class="km-catalog-mandatory-meta">
                                    <strong>{{ $mandatoryAssignment['assignment_title'] }}</strong>
                                    <span>
                                        Deadline {{ $mandatoryAssignment['due_at']->format('d/m/Y H:i') }}
                                        &middot;
                                        {{ match ($mandatoryAssignment['status']) {
                                            'overdue' => 'Terlambat',
                                            'reading' => 'Sedang dibaca',
                                            default => 'Belum dimulai',
                                        } }}
                                    </span>
                                </div>
                            @endif

                            @if ($pengajuan->tags->isNotEmpty())
                                <div class="km-document-tags" aria-label="Tag dokumen">
                                    @foreach ($visibleTags as $tag)
                                        <span class="badge text-bg-light border">{{ $tag->name }}</span>
                                    @endforeach
                                    @if ($remainingTagCount > 0)
                                        <span class="badge text-bg-light border" title="{{ $remainingTagCount }} tag lainnya">
                                            +{{ $remainingTagCount }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            @if ($pengajuan->coAuthors->isNotEmpty())
                                <small class="km-document-coauthors">
                                    Bersama {{ $visibleCoAuthors->pluck('name')->join(', ') }}
                                    @if ($remainingCoAuthorCount > 0)
                                        dan {{ $remainingCoAuthorCount }} lainnya
                                    @endif
                                </small>
                            @endif

                            <div class="d-flex justify-content-between align-items-center mt-auto mb-3 small text-muted">
                                <span><i class="bi bi-eye"></i> {{ (int) ($pengajuan->total_views ?? 0) }}</span>
                                <span data-km-reading-status>
                                    @if ($isCompleted)
                                        Selesai
                                    @elseif ($isReading)
                                        Sedang dibaca
                                    @else
                                        Belum dibaca
                                    @endif
                                </span>
                            </div>

                            @if (($isReading || $isCompleted) && $transaction?->pages_total)
                                <div class="mb-3">
                                    <div class="progress km-reading-progress" role="progressbar"
                                        aria-label="Progres baca {{ $pengajuan->judul }}"
                                        aria-valuenow="{{ $progressPercent }}"
                                        aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar" style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $progressPercent }}% halaman dibuka</small>
                                </div>
                            @elseif ($isCompleted)
                                <p class="small text-muted mb-3">Status selesai tercatat; progres halaman historis tidak tersedia.</p>
                            @endif

                            <div class="km-document-actions">
                                <button type="button" class="btn btn-primary btn-sm flex-grow-1 km-open-document"
                                    @disabled(! $hasFile)
                                    data-document-id="{{ $pengajuan->id }}"
                                    @if ($pengajuan->published_version_id) data-document-version-id="{{ $pengajuan->published_version_id }}" @endif
                                    data-preview-url="{{ $hasFile ? route('km.documents.preview', $pengajuan) : '' }}"
                                    data-progress-url="{{ route('km.reading.progress', $pengajuan) }}"
                                    data-resume-page="{{ max(1, (int) ($transaction?->last_page ?? 1)) }}"
                                    data-progress-percent="{{ $progressPercent }}"
                                    data-active-seconds="{{ (int) ($transaction?->active_seconds ?? 0) }}"
                                    data-unique-pages-count="{{ (int) ($transaction?->unique_pages_count ?? 0) }}"
                                    data-completed="{{ $isCompleted ? 'true' : 'false' }}"
                                    data-is-pdf="{{ $isPdf ? 'true' : 'false' }}"
                                    data-title="{{ $pengajuan->judul }}"
                                    data-can-complete="{{ $canComplete ? 'true' : 'false' }}">
                                    <i class="bi bi-book me-1"></i>{{ $hasFile ? ($isReading ? 'Lanjutkan' : 'Buka') : 'File belum tersedia' }}
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm km-show-synopsis"
                                    data-title="{{ $pengajuan->judul }}"
                                    data-synopsis="{{ $pengajuan->keterangan }}" title="Lihat sinopsis" aria-label="Lihat sinopsis">
                                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                                    <span>Sinopsis</span>
                                </button>
                            </div>
                        </div>

                        <div class="card-footer km-document-card__footer">
                            <button type="button" class="btn btn-sm km-card-action btn-like {{ $pengajuan->is_liked ? 'liked' : '' }}"
                                data-km-like data-document-id="{{ $pengajuan->id }}"
                                aria-label="Sukai dokumen" aria-pressed="{{ $pengajuan->is_liked ? 'true' : 'false' }}">
                                <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                                <span>Suka</span>
                                <strong data-km-like-count>{{ $pengajuan->km_sukas_count }}</strong>
                            </button>
                            <button type="button" class="btn btn-sm km-card-action" data-km-insights-open
                                data-document-id="{{ $pengajuan->id }}" data-title="{{ $pengajuan->judul }}"
                                title="Insight" aria-label="Buka insight {{ $pengajuan->judul }}">
                                <i class="bi bi-chat" aria-hidden="true"></i>
                                <span>Insight</span>
                                <strong data-km-insight-count>{{ $pengajuan->insights_count }}</strong>
                            </button>
                        </div>
                    </article>

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
            @endif

        @if ($catalogPaginator->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $catalogPaginator->links('pagination::bootstrap-5') }}
            </div>
        @endif
        </section>

        <section class="km-panel mt-5" aria-labelledby="km-leaderboard-title" data-km-leaderboard>
            <div class="km-panel__header align-items-start">
                <div>
                    <p class="km-section-kicker">Kontribusi pembelajaran</p>
                    <h2 class="km-panel__title" id="km-leaderboard-title">Peringkat Pembaca Teraktif</h2>
                    <p class="text-muted small mb-0">Global menggunakan poin; departemen memakai ledger KM.</p>
                </div>
                <fieldset class="km-leaderboard-toggle">
                    <legend class="visually-hidden">Cakupan leaderboard</legend>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="km-leaderboard-scope" id="km-leaderboard-global"
                            value="global" checked data-km-leaderboard-toggle>
                        <label class="btn btn-outline-primary btn-sm" for="km-leaderboard-global">Global</label>
                        <input type="radio" class="btn-check" name="km-leaderboard-scope" id="km-leaderboard-department"
                            value="department" data-km-leaderboard-toggle>
                        <label class="btn btn-outline-primary btn-sm" for="km-leaderboard-department">Departemen Saya</label>
                    </div>
                </fieldset>
            </div>

            <div data-km-leaderboard-panel="global">
                <div class="km-my-rank">
                    <span class="km-my-rank__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                    <div>
                        <span>Posisi saya &middot; Global</span>
                        <strong>#{{ number_format($leaderboardPosition['global']['rank']) }}</strong>
                    </div>
                    <span class="km-my-rank__points">{{ number_format($leaderboardPosition['global']['points']) }} poin</span>
                </div>

                <div class="km-leaderboard-list" role="list" aria-label="Sepuluh pembaca teraktif secara global">
                    @forelse ($leaderboard->take(10) as $leader)
                        <div class="km-leaderboard-item" role="listitem"
                            @if ((int) $leader->id === (int) auth()->id()) data-current-user="true" @endif>
                            <span class="km-leaderboard-item__rank">#{{ $leader->leaderboard_rank }}</span>
                            <div class="km-leaderboard-item__person">
                                <strong title="{{ $leader->name }}">{{ $leader->name }}</strong>
                                @if ((int) $leader->id === (int) auth()->id())
                                    <span>Anda</span>
                                @endif
                            </div>
                            <span class="km-leaderboard-item__points">{{ number_format($leader->km_total_poin ?? 0) }} poin</span>
                        </div>
                    @empty
                        <div class="km-operational-note" role="status">Belum ada data poin global.</div>
                    @endforelse
                </div>
            </div>

            <div data-km-leaderboard-panel="department" hidden>
                @if ($departmentLeaderboard['insufficient_cohort'])
                    <div class="km-operational-note" role="status">
                        {{ $leaderboardPosition['department']['reason'] }}
                        @if ($departmentLeaderboard['department'])
                            Departemen: {{ $departmentLeaderboard['department'] }}.
                        @endif
                    </div>
                @else
                    @if ($leaderboardPosition['department']['available'])
                        <div class="km-my-rank">
                            <span class="km-my-rank__icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                            <div>
                                <span>Posisi saya &middot; {{ $departmentLeaderboard['department'] }}</span>
                                <strong>#{{ number_format($leaderboardPosition['department']['rank']) }}</strong>
                            </div>
                            <span class="km-my-rank__points">{{ number_format($leaderboardPosition['department']['points']) }} poin</span>
                        </div>
                    @else
                        <div class="km-operational-note" role="status">
                            {{ $leaderboardPosition['department']['reason'] }}
                        </div>
                    @endif

                    <p class="km-leaderboard-context">
                        {{ $departmentLeaderboard['department'] }} &middot;
                        {{ number_format($departmentLeaderboard['cohort_size']) }} pembaca aktif
                    </p>
                    <div class="km-leaderboard-list" role="list" aria-label="Sepuluh pembaca teraktif di departemen Anda">
                        @forelse ($departmentLeaderboard['leaders'] as $leader)
                            <div class="km-leaderboard-item" role="listitem"
                                @if ((int) $leader->user_id === (int) auth()->id()) data-current-user="true" @endif>
                                <span class="km-leaderboard-item__rank">#{{ $leader->leaderboard_rank }}</span>
                                <div class="km-leaderboard-item__person">
                                    <strong title="{{ $leader->name }}">{{ $leader->name }}</strong>
                                    @if ((int) $leader->user_id === (int) auth()->id())
                                        <span>Anda</span>
                                    @endif
                                </div>
                                <span class="km-leaderboard-item__points">{{ number_format($leader->points ?? 0) }} poin</span>
                            </div>
                        @empty
                            <div class="km-operational-note" role="status">Belum ada data poin departemen.</div>
                        @endforelse
                    </div>
                @endif
            </div>
            <p class="km-sr-status" aria-live="polite" data-km-leaderboard-status></p>
        </section>
    </div>

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

<div class="modal fade" id="km-insight-modal" tabindex="-1" aria-labelledby="km-insight-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="km-section-kicker">Diskusi Materi</span>
                    <h2 class="modal-title fs-5" id="km-insight-modal-title">Insight</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup insight"></button>
            </div>
            <div class="modal-body">
                <div class="km-insight-feedback" data-km-insight-feedback aria-live="polite"></div>
                <div class="km-insight-list" data-km-insight-list aria-live="polite" aria-busy="true"></div>
                <button type="button" class="btn btn-outline-secondary w-100 mb-4" data-km-insight-more hidden>
                    Muat insight berikutnya
                </button>

                <form data-km-insight-form>
                    <input type="hidden" name="parent_id" data-km-insight-parent>
                    <div class="km-insight-reply-context" data-km-insight-reply-context hidden>
                        <span data-km-insight-reply-label></span>
                        <button type="button" class="btn btn-link btn-sm" data-km-insight-cancel-reply>Batal membalas</button>
                    </div>
                    <label for="km-insight-content" class="form-label">Bagikan insight</label>
                    <textarea id="km-insight-content" class="form-control" name="content" rows="3" maxlength="1200"
                        required aria-describedby="km-insight-content-help"></textarea>
                    <div class="form-text" id="km-insight-content-help">Maksimum 1.200 karakter. Diskusi tetap mengikuti akses materi.</div>

                    <button type="button" class="btn btn-outline-secondary btn-sm mt-3"
                        data-km-insight-mention-toggle aria-expanded="false"
                        aria-controls="km-insight-mention-panel">
                        <span aria-hidden="true">@</span> Mention pengguna
                    </button>
                    <div id="km-insight-mention-panel" class="km-insight-mention-picker mt-2"
                        data-km-insight-mention-panel hidden>
                        <label for="km-insight-mention-search" class="form-label">Cari pengguna</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" id="km-insight-mention-search" class="form-control"
                                maxlength="100" autocomplete="off" placeholder="Cari nama, email, atau NPK"
                                data-km-insight-mention-search
                                aria-describedby="km-insight-mention-status km-insight-mentions-help">
                        </div>
                        <div id="km-insight-mention-status" class="km-insight-mention-status"
                            data-km-insight-mention-status aria-live="polite"></div>

                        <label for="km-insight-mentions" class="form-label mt-3">
                            Pilih pengguna <span class="text-muted fw-normal">(opsional)</span>
                        </label>
                        <select id="km-insight-mentions" class="form-select" name="mention_ids[]" multiple size="5"
                            data-km-insight-mentions aria-describedby="km-insight-mention-status km-insight-mentions-help"></select>
                        <div class="form-text" id="km-insight-mentions-help">
                            Pilih maksimal 10 pengguna yang memiliki akses ke materi ini. Mention yang sudah dikirim tetap dipertahankan saat edit.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary" data-km-insight-submit>Kirim insight</button>
                    </div>
                </form>
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
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="km-viewer-progress-bar" id="km-viewer-progress-summary" aria-live="polite">
                <div>
                    <strong data-km-viewer-progress-label>Progres 0%</strong>
                    <span data-km-viewer-active-label>0 detik aktif</span>
                </div>
                <div class="progress" role="progressbar" aria-label="Progres membaca"
                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-km-viewer-progress>
                    <div class="progress-bar" style="width: 0%"></div>
                </div>
                <small data-km-viewer-completion-hint>Buka dokumen untuk mencatat progres baca.</small>
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
    progressUrlTemplate: @js(route('km.reading.progress', ['kmPengajuan' => '__KM_DOCUMENT__'])),
    insightIndexUrlTemplate: @js(route('km.insights.index', ['kmPengajuan' => '__KM_DOCUMENT__'])),
    insightStoreUrlTemplate: @js(route('km.insights.store', ['kmPengajuan' => '__KM_DOCUMENT__'])),
    insightMentionUrlTemplate: @js(route('km.insights.mention-options', ['kmPengajuan' => '__KM_DOCUMENT__'])),
    insightUpdateUrlTemplate: @js(route('km.insights.update', ['insight' => '__KM_INSIGHT__'])),
    insightDeleteUrlTemplate: @js(route('km.insights.destroy', ['insight' => '__KM_INSIGHT__'])),
    insightReactionUrlTemplate: @js(route('km.insights.reaction.store', ['insight' => '__KM_INSIGHT__'])),
    insightFeatureUrlTemplate: @js(route('km.insights.feature', ['insight' => '__KM_INSIGHT__'])),
    reactionTypes: @js(config('knowledge_management.insights.reactions', [])),
    maximumMentions: @js((int) config('knowledge_management.insights.maximum_mentions', 10)),
    reading: {
        inactiveTimeoutSeconds: @js((int) config('knowledge_management.reading.inactive_timeout_seconds', 60)),
        progressFlushSeconds: @js((int) config('knowledge_management.reading.progress_flush_seconds', 12)),
        uniquePageRatio: @js((float) config('knowledge_management.reading.unique_page_ratio', 0.9)),
        minimumActiveSeconds: @js((int) config('knowledge_management.reading.minimum_active_seconds', 60)),
        secondsPerPage: @js((int) config('knowledge_management.reading.seconds_per_page', 20)),
        maximumRequiredSeconds: @js((int) config('knowledge_management.reading.maximum_required_seconds', 900)),
    },
};
</script>
</x-km.shell>
@endsection
