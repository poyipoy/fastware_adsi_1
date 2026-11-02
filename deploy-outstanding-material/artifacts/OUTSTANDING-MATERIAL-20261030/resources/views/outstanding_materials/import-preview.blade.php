@extends('layout')

@section('content')
<main id="main" class="main">
    <div class="pagetitle"><h1>Preview Import Multi-Invoice</h1><nav><ol class="breadcrumb"><li class="breadcrumb-item">Procurement</li><li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li><li class="breadcrumb-item active">Import Preview</li></ol></nav></div>
    <section class="section">
        @if (!empty($preview['errors']))
            <div class="alert alert-danger"><strong>Import diblokir karena terdapat error.</strong><ul class="mb-0 mt-2">@foreach ($preview['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if (!empty($preview['warnings']))
            <div class="alert alert-warning"><strong>Catatan:</strong><ul class="mb-0 mt-2">@foreach ($preview['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>
        @endif

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Ringkasan Preview</strong>
                <span class="badge {{ ($preview['mode'] ?? 'add') === 'replace' ? 'text-bg-warning text-dark' : 'text-bg-primary' }} text-uppercase">
                    Mode: {{ $preview['mode'] ?? 'add' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    @if (($preview['mode'] ?? 'add') === 'replace')
                        <div class="col-md-4"><div class="border rounded p-3"><div class="text-muted small">Akan Diupdate (Matched)</div><strong class="fs-4 text-warning">{{ $preview['summary']['matched'] ?? 0 }}</strong></div></div>
                        <div class="col-md-4"><div class="border rounded p-3"><div class="text-muted small">Tidak Ditemukan (Unmatched)</div><strong class="fs-4 text-danger">{{ $preview['summary']['unmatched'] ?? 0 }}</strong></div></div>
                        <div class="col-md-4"><div class="border rounded p-3"><div class="text-muted small">Total Errors</div><strong class="fs-4 text-danger">{{ $preview['summary']['errors'] ?? 0 }}</strong></div></div>
                    @else
                        <div class="col-md-6"><div class="border rounded p-3"><div class="text-muted small">Akan Ditambahkan</div><strong class="fs-4 text-primary">{{ $preview['summary']['new'] ?? 0 }}</strong></div></div>
                        <div class="col-md-6"><div class="border rounded p-3"><div class="text-muted small">Errors</div><strong class="fs-4 text-danger">{{ $preview['summary']['errors'] ?? 0 }}</strong></div></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Detail Baris</strong>
                <span class="small text-muted">
                    @if (($preview['mode'] ?? 'add') === 'replace')
                        Hanya baris dengan status MATCHED yang akan diupdate. Packing List dan MTC tetap dipertahankan.
                    @else
                        Semua baris valid akan ditambahkan sebagai data baru.
                    @endif
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Row</th>
                                <th>Supplier</th>
                                <th>Invoice</th>
                                <th>TYPE</th>
                                <th>Status</th>
                                <th>Classification</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($preview['rows'] as $row)
                                <tr>
                                    <td>{{ $row['source_row'] }}</td>
                                    <td>{{ $row['supplier'] }}</td>
                                    <td>{{ $row['number_invoice'] }}</td>
                                    <td>{{ $row['type'] }}</td>
                                    <td>{{ $row['status'] }}</td>
                                    <td>
                                        @if (($row['classification'] ?? '') === 'matched')
                                            <span class="badge text-bg-warning text-dark">REPLACE (MATCHED)</span>
                                        @elseif (($row['classification'] ?? '') === 'unmatched')
                                            <span class="badge text-bg-danger">NOT FOUND</span>
                                        @else
                                            <span class="badge text-bg-primary">NEW</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Tidak ada baris valid.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a href="{{ route('outstanding-materials.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <form method="POST" action="{{ route('outstanding-materials.import.preview.execute', $preview['token']) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" @disabled(!empty($preview['errors']))>
                            <i class="bi bi-check-lg me-1"></i>Execute Import ({{ strtoupper($preview['mode'] ?? 'add') }})
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
