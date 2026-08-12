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

        <div class="card mb-3"><div class="card-body"><div class="row g-3 text-center">
            <div class="col-md-6"><div class="border rounded p-3"><div class="text-muted small">Akan Ditambahkan</div><strong class="fs-4 text-primary">{{ $preview['summary']['new'] }}</strong></div></div>
            <div class="col-md-6"><div class="border rounded p-3"><div class="text-muted small">Errors</div><strong class="fs-4 text-danger">{{ $preview['summary']['errors'] }}</strong></div></div>
        </div></div></div>

        <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><strong>Detail Baris</strong><span class="small text-muted">Semua baris valid akan ditambahkan</span></div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead class="table-light"><tr><th>Row</th><th>Supplier</th><th>Invoice</th><th>TYPE</th><th>Status</th><th>Classification</th></tr></thead><tbody>
                @forelse ($preview['rows'] as $row)
                    <tr><td>{{ $row['source_row'] }}</td><td>{{ $row['supplier'] }}</td><td>{{ $row['number_invoice'] }}</td><td>{{ $row['type'] }}</td><td>{{ $row['status'] }}</td><td><span class="badge text-bg-primary">NEW</span></td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Tidak ada baris valid.</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="d-flex justify-content-between align-items-center mt-3"><a href="{{ route('outstanding-materials.index') }}" class="btn btn-outline-secondary">Cancel</a><form method="POST" action="{{ route('outstanding-materials.import.preview.execute', $preview['token']) }}">@csrf<button type="submit" class="btn btn-primary" @disabled(!empty($preview['errors']))><i class="bi bi-check-lg me-1"></i>Execute Import</button></form></div>
        </div></div>
    </section>
</main>
@endsection
