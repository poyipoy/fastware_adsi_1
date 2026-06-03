@extends('layout')

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Detail Outstanding Material</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Procurement</li>
                <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Detail Outstanding Material</h5>
                            <div class="d-flex gap-2">
                                <a href="{{ route('outstanding-materials.edit', $material) }}" class="btn btn-warning">Edit</a>
                                <a href="{{ route('outstanding-materials.index') }}" class="btn btn-secondary">Back</a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr><th style="width: 280px;">Supplier</th><td>{{ $material->supplier }}</td></tr>
                                    <tr><th>TYPE</th><td>{{ $material->type }}</td></tr>
                                    <tr><th>Thickness</th><td>{{ $material->thickness ?? '-' }}</td></tr>
                                    <tr><th>Width</th><td>{{ $material->width ?? '-' }}</td></tr>
                                    <tr><th>Diameter</th><td>{{ $material->diameter ?? '-' }}</td></tr>
                                    <tr><th>Length</th><td>{{ $material->length ?? '-' }}</td></tr>
                                    <tr><th>QTY (PCS)</th><td>{{ $material->qty_pcs ?? '-' }}</td></tr>
                                    <tr><th>Est QTY (KG)</th><td>{{ $material->est_qty_kg ?? '-' }}</td></tr>
                                    <tr><th>Number Invoice</th><td>{{ $material->number_invoice ?? '-' }}</td></tr>
                                    <tr><th>Status</th><td>{{ $material->status }}</td></tr>
                                    <tr><th>Estimasi ETA Port</th><td>{{ $material->estimasi_eta_port ?? '-' }}</td></tr>
                                    <tr><th>Estimasi ETA Warehouse</th><td>{{ $material->estimasi_eta_warehouse ?? '-' }}</td></tr>
                                    <tr><th>Estimasi Bulan ETA</th><td>{{ $material->estimasi_bulan_eta ?? '-' }}</td></tr>
                                    <tr><th>Keterangan</th><td>{{ $material->keterangan ?? '-' }}</td></tr>
                                    <tr><th>Estimasi Delay ETA Port</th><td>{{ $material->estimasi_delay_eta_port ?? '-' }}</td></tr>
                                    <tr><th>Estimasi Delay ETA Warehouse</th><td>{{ $material->estimasi_delay_eta_warehouse ?? '-' }}</td></tr>
                                    <tr>
                                        <th>DOKUMEN PACKING LIST DAN MTC</th>
                                        <td>
                                            @if ($material->attachment_path)
                                                @if (str_starts_with($material->attachment_path, 'outstanding-materials/'))
                                                    <a href="{{ route('outstanding-materials.attachment', $material) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat File</a>
                                                @else
                                                    {{ $material->attachment_path }}
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr><th>Created By</th><td>{{ optional($material->creator)->name ?? '-' }}</td></tr>
                                    <tr><th>Updated By</th><td>{{ optional($material->updater)->name ?? '-' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
