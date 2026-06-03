@extends('layout')

@section('content')
@php
    $title = $isEdit ? 'Edit Outstanding Material' : 'Add Outstanding Material';
    $action = $isEdit ? route('outstanding-materials.update', $material) : route('outstanding-materials.store');
@endphp

<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $title }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Procurement</li>
                <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Create' }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ $title }}</h5>

                        <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
                            @csrf
                            @if ($isEdit)
                                @method('PUT')
                            @endif

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="supplier" class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <input type="text" id="supplier" name="supplier" class="form-control" value="{{ old('supplier', $material->supplier) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label">TYPE <span class="text-danger">*</span></label>
                                    <input type="text" id="type" name="type" class="form-control" value="{{ old('type', $material->type) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select id="status" name="status" class="form-select" required>
                                        <option value="">Pilih Status</option>
                                        @foreach ($statusOptions as $status)
                                            <option value="{{ $status }}" @selected(old('status', $material->status) === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="thickness" class="form-label">Thickness</label>
                                    <input type="number" step="0.01" id="thickness" name="thickness" class="form-control" value="{{ old('thickness', $material->thickness) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="width" class="form-label">Width</label>
                                    <input type="number" step="0.01" id="width" name="width" class="form-control" value="{{ old('width', $material->width) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="diameter" class="form-label">Diameter</label>
                                    <input type="number" step="0.01" id="diameter" name="diameter" class="form-control" value="{{ old('diameter', $material->diameter) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="length" class="form-label">Length</label>
                                    <input type="text" id="length" name="length" class="form-control" value="{{ old('length', $material->length) }}" placeholder="Contoh: 1000-2000 atau 1000~2000">
                                </div>

                                <div class="col-md-3">
                                    <label for="qty_pcs" class="form-label">QTY (PCS)</label>
                                    <input type="number" step="0.01" id="qty_pcs" name="qty_pcs" class="form-control" value="{{ old('qty_pcs', $material->qty_pcs) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="est_qty_kg" class="form-label">Est QTY (KG)</label>
                                    <input type="number" step="0.01" id="est_qty_kg" name="est_qty_kg" class="form-control" value="{{ old('est_qty_kg', $material->est_qty_kg) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="number_invoice" class="form-label">Number Invoice</label>
                                    <input type="text" id="number_invoice" name="number_invoice" class="form-control" value="{{ old('number_invoice', $material->number_invoice) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="estimasi_bulan_eta" class="form-label">Estimasi Bulan ETA</label>
                                    <input type="text" id="estimasi_bulan_eta" name="estimasi_bulan_eta" class="form-control" value="{{ old('estimasi_bulan_eta', $material->estimasi_bulan_eta) }}" placeholder="Contoh: May 2026">
                                </div>

                                <div class="col-md-3">
                                    <label for="estimasi_eta_port" class="form-label">Estimasi ETA Port</label>
                                    <input type="date" id="estimasi_eta_port" name="estimasi_eta_port" class="form-control" value="{{ old('estimasi_eta_port', $material->estimasi_eta_port) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="estimasi_eta_warehouse" class="form-label">Estimasi ETA Warehouse</label>
                                    <input type="date" id="estimasi_eta_warehouse" name="estimasi_eta_warehouse" class="form-control" value="{{ old('estimasi_eta_warehouse', $material->estimasi_eta_warehouse) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="estimasi_delay_eta_port" class="form-label">Estimasi Delay ETA Port</label>
                                    <input type="date" id="estimasi_delay_eta_port" name="estimasi_delay_eta_port" class="form-control" value="{{ old('estimasi_delay_eta_port', $material->estimasi_delay_eta_port) }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="estimasi_delay_eta_warehouse" class="form-label">Estimasi Delay ETA Warehouse</label>
                                    <input type="date" id="estimasi_delay_eta_warehouse" name="estimasi_delay_eta_warehouse" class="form-control" value="{{ old('estimasi_delay_eta_warehouse', $material->estimasi_delay_eta_warehouse) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="keterangan" class="form-label">Keterangan</label>
                                    <select id="keterangan" name="keterangan" class="form-select">
                                        <option value="">Pilih Keterangan</option>
                                        @foreach ($keteranganOptions as $keterangan)
                                            <option value="{{ $keterangan }}" @selected(old('keterangan', $material->keterangan) === $keterangan)>{{ $keterangan }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label for="attachment" class="form-label">DOKUMEN PACKING LIST DAN MTC</label>
                                    <input type="file" id="attachment" name="attachment" class="form-control" accept=".pdf,.xls,.xlsx,.doc,.docx,.jpg,.jpeg,.png">
                                    @if ($material->attachment_path)
                                        <div class="form-text">
                                            File saat ini:
                                            @if (str_starts_with($material->attachment_path, 'outstanding-materials/'))
                                                <a href="{{ route('outstanding-materials.attachment', $material) }}" target="_blank">{{ basename($material->attachment_path) }}</a>
                                            @else
                                                {{ $material->attachment_path }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('outstanding-materials.index') }}" class="btn btn-secondary">Back</a>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
