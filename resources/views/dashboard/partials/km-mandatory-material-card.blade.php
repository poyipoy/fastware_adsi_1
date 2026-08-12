@php
    $mandatoryStatusLabel = match ($mandatoryMaterial['status']) {
        'overdue' => 'Terlambat',
        'reading' => 'Sedang dibaca',
        default => 'Belum dimulai',
    };
@endphp

<article class="km-mandatory-item {{ $mandatoryMaterial['status'] === 'overdue' ? 'is-overdue' : '' }}">
    <img class="km-mandatory-item__thumbnail" src="{{ $mandatoryMaterial['thumbnail_url'] }}"
        alt="Sampul materi {{ $mandatoryMaterial['document_title'] }}" loading="lazy">
    <div class="km-mandatory-item__content">
        <div class="km-mandatory-item__meta">
            <span>{{ $mandatoryMaterial['category'] ?? 'Tanpa kategori' }}</span>
            <span>Versi {{ $mandatoryMaterial['version_number'] }}</span>
        </div>
        <h3>{{ $mandatoryMaterial['document_title'] }}</h3>
        <p>{{ $mandatoryMaterial['assignment_title'] }}</p>
        <div class="progress km-reading-progress" role="progressbar"
            aria-label="Progres materi wajib {{ $mandatoryMaterial['document_title'] }}"
            aria-valuenow="{{ $mandatoryMaterial['progress_percent'] }}"
            aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $mandatoryMaterial['progress_percent'] }}%"></div>
        </div>
        <small>{{ $mandatoryMaterial['progress_percent'] }}% halaman dibuka</small>
    </div>
    <div class="km-mandatory-item__deadline">
        <span class="km-mandatory-status {{ $mandatoryMaterial['status'] === 'overdue' ? 'is-overdue' : '' }}">
            {{ $mandatoryStatusLabel }}
        </span>
        <span>Deadline</span>
        <strong>{{ $mandatoryMaterial['due_at']->format('d/m/Y H:i') }}</strong>
    </div>
    <button type="button" class="btn btn-primary btn-sm km-open-document"
        data-document-id="{{ $mandatoryMaterial['document_id'] }}"
        data-document-version-id="{{ $mandatoryMaterial['document_version_id'] }}"
        data-preview-url="{{ $mandatoryMaterial['preview_url'] }}"
        data-progress-url="{{ $mandatoryMaterial['progress_url'] }}"
        data-resume-page="{{ $mandatoryMaterial['last_page'] }}"
        data-progress-percent="{{ $mandatoryMaterial['progress_percent'] }}"
        data-active-seconds="{{ $mandatoryMaterial['active_seconds'] }}"
        data-unique-pages-count="{{ $mandatoryMaterial['unique_pages_count'] }}"
        data-completed="false" data-is-pdf="true"
        data-title="{{ $mandatoryMaterial['document_title'] }}"
        data-can-complete="{{ $mandatoryMaterial['completion_eligible'] ? 'true' : 'false' }}">
        <i class="bi bi-book" aria-hidden="true"></i>
        {{ $mandatoryMaterial['status'] === 'reading' || $mandatoryMaterial['status'] === 'overdue'
            && $mandatoryMaterial['progress_percent'] > 0 ? 'Lanjutkan' : 'Buka' }}
    </button>
</article>
