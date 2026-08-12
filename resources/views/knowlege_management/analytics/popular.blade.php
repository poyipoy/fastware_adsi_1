@extends('layout')

@section('documentLanguage', 'id')

@push('styles')
    @vite('resources/css/km/foundation.css')
@endpush

@section('content')
<x-km.shell>
    <x-km.page-header
        title="Materi Populer"
        description="Laporan operasional materi KM, bukan KPI atau dasar penilaian individu.">
        <x-slot:actions>
            <a href="{{ route('persetujuanKM') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </x-slot:actions>
    </x-km.page-header>

    <x-km.feedback :errors="$errors" error-title="Laporan belum dapat ditampilkan." />

    <section aria-labelledby="km-popular-report-title">
        <h2 class="visually-hidden" id="km-popular-report-title">Laporan materi populer</h2>

        <div class="km-operational-note mb-3">
            <strong>Dibuat {{ $generatedAt->format('d-m-Y H:i:s') }} WIB.</strong>
            Laporan ini bersifat operasional. Counter historis sebelum hardening mungkin memiliki keterbatasan
            dan tidak boleh digunakan sebagai KPI resmi atau penilaian individu.
        </div>

        @if ($exportLimitReached)
            <div class="alert alert-info" role="status">
                {{ $exportTruncated
                    ? 'Hasil melebihi 10.000 baris; export dibatasi pada 10.000 materi pertama sesuai urutan laporan.'
                    : 'Hasil mencapai batas export 10.000 baris.' }}
            </div>
        @endif

        <div class="km-panel mb-3">
            <form method="GET" action="{{ route('km.analytics.popular') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="popular-category" class="form-label">Kategori</label>
                        <select id="popular-category" name="category" class="form-select">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($filters['category'] === (int) $category->id)>
                                    {{ $category->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="popular-tags" class="form-label">Tag</label>
                        <select id="popular-tags" name="tag_ids[]" class="form-select" multiple size="3">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}" @selected(in_array((int) $tag->id, $filters['tag_ids'], true))>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Dokumen cocok bila memiliki salah satu tag terpilih.</div>
                    </div>
                    <div class="col-md-3 d-flex flex-wrap gap-2">
                        <a href="{{ route('km.analytics.popular') }}" class="btn btn-outline-secondary">Reset</a>
                        <button type="submit" class="btn btn-primary">Terapkan</button>
                    </div>
                </div>
            </form>
        </div>

        @php($exportQuery = request()->except('page'))
        <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
            <a href="{{ route('km.analytics.popular.export.xlsx', $exportQuery) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>Export XLSX
            </a>
            <a href="{{ route('km.analytics.popular.export.pdf', $exportQuery) }}" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Export PDF
            </a>
        </div>

        <div class="km-panel">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Peringkat</th>
                            <th scope="col">Judul</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Tag</th>
                            <th scope="col" class="text-end">Total Lihat</th>
                            <th scope="col" class="text-end">Pembaca Selesai</th>
                            <th scope="col" class="text-end">Like</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                            <tr>
                                <td>{{ $materials->firstItem() + $loop->index }}</td>
                                <td>{{ $material->judul }}</td>
                                <td>{{ $material->kmKategori?->nama_kategori ?? '-' }}</td>
                                <td>{{ $material->tags->pluck('name')->implode(', ') ?: '-' }}</td>
                                <td class="text-end">{{ number_format((int) $material->total_views) }}</td>
                                <td class="text-end">{{ number_format((int) $material->completed_readers) }}</td>
                                <td class="text-end">{{ number_format((int) $material->likes_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Tidak ada materi untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mb-3">
                Ranking: total lihat menurun, pembaca selesai menurun, like menurun, lalu ID dokumen menaik.
            </div>
            {{ $materials->links('pagination::bootstrap-5') }}
        </div>
    </section>
</x-km.shell>
@endsection
