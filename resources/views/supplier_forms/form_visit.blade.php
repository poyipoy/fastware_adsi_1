@extends('layout')

@section('content')
<style>
    .error-message { color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; }
    .rating-table th, .rating-table td { vertical-align: middle; text-align: center; }
    .rating-table .kriteria { text-align: left; width: 40%; }
    .avg-score { font-weight: bold; font-size: 1.1em; }
</style>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Laporan Visit & Trial Supplier</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('supplierform.index') }}">Supplier</a></li>
                <li class="breadcrumb-item"><a href="{{ route('supplierform.show', $form->id) }}">Detail</a></li>
                <li class="breadcrumb-item active">Penilaian</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body p-4">

                        <a href="{{ route('supplierform.show', $form->id) }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-2"></i> Keluar</a>

                        @php
                        // tentukan kriteria sesuai type supplier
                        if($form->supplier->type === 'Non Trade') {
                            $kriteria = [
                                'kualitas_baja' => 'Kualitas Material Baja',
                                'stok'          => 'Ketersediaan Stock',
                                'waktu_kirim'   => 'Waktu Pemesanan & Pengiriman',
                                'responsif'     => 'Responsif Supplier',
                                'office_wh'     => 'Kelayakan Office / Warehouse',
                                'mesin'         => 'Kondisi Mesin Pabrik',
                                'safety'        => 'Safety Karyawan',
                            ];
                        } else {
                            $kriteria = [
                                'kelengkapan_apd' => 'Kelengkapan APD Pekerja',
                                'fasilitas'       => 'Fasilitas',
                                'alat_ukur'       => 'Alat Ukur',
                                'lisensi'         => 'Lisensi',
                                'lima_r'          => '5R',
                            ];
                        }
                        @endphp

                        {{-- ======================================================= --}}
                        {{-- == Kondisi Visit Form: Create atau Edit               == --}}
                        {{-- ======================================================= --}}
                        @if(!$form->visitDetail)
                            {{-- === CREATE === --}}
                            <h5 class="card-title">Buat Laporan Visit</h5>
                            <form method="POST" action="{{ route('assessment.visit.store', $form->id) }}" enctype="multipart/form-data">
                                @csrf

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Supplier</label>
                                        <input type="text" class="form-control" value="{{ $form->supplier->supplier_name ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type</label>
                                        <input type="text" class="form-control" value="{{ $form->supplier->type ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Visit</label>
                                        <input type="date" name="tanggal_visit" value="{{ old('tanggal_visit') }}"
                                               class="form-control @error('tanggal_visit') is-invalid @enderror" required>
                                        @error('tanggal_visit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Lokasi</label>
                                        <input type="text" name="lokasi" value="{{ old('lokasi') }}"
                                               class="form-control @error('lokasi') is-invalid @enderror" required>
                                        @error('lokasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- tabel kriteria --}}
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered rating-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="kriteria">Kriteria</th>
                                                <th colspan="5">Level of Satisfactory</th>
                                            </tr>
                                            <tr>
                                                <th></th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($kriteria as $name => $label)
                                            <tr>
                                                <td class="kriteria">{{ $label }}</td>
                                                @for($i=1; $i<=5; $i++)
                                                    <td>
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input type="radio" class="form-check-input score-radio"
                                                                   name="{{ $name }}" value="{{ $i }}"
                                                                   {{ old($name) == $i ? 'checked' : '' }} required>
                                                        </div>
                                                    </td>
                                                @endfor
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mb-3 text-end">
                                    <span class="avg-score">Rata-rata: <span id="avg-score">0</span></span>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Catatan</label>
                                    <textarea class="form-control" name="catatan" rows="3">{{ old('catatan') }}</textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Lampiran Foto</label>
                                    <input type="file" class="form-control" name="lampiran_foto[]" multiple>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary">Simpan Laporan Visit</button>
                                </div>
                            </form>
                        @else
                            {{-- === EDIT === --}}
                            <h5 class="card-title">Edit Laporan Visit</h5>
                            <form action="{{ route('assessment.visit.update', $form->visitDetail->id) }}" 
                                  method="POST" 
                                  enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Supplier</label>
                                        <input type="text" class="form-control" value="{{ $form->supplier->supplier_name ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type</label>
                                        <input type="text" class="form-control" value="{{ $form->supplier->type ?? '' }}" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Visit</label>
                                        <input type="date" name="tanggal_visit"
                                               value="{{ old('tanggal_visit', optional($form->visitDetail->tanggal_visit)->format('Y-m-d')) }}"
                                               class="form-control @error('tanggal_visit') is-invalid @enderror" required>
                                        @error('tanggal_visit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Lokasi</label>
                                        <input type="text" name="lokasi"
                                               value="{{ old('lokasi', $form->visitDetail->lokasi) }}"
                                               class="form-control @error('lokasi') is-invalid @enderror" required>
                                        @error('lokasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- tabel kriteria --}}
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered rating-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="kriteria">Kriteria</th>
                                                <th colspan="5">Level of Satisfactory</th>
                                            </tr>
                                            <tr>
                                                <th></th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($kriteria as $name => $label)
                                            <tr>
                                                <td class="kriteria">{{ $label }}</td>
                                                @for($i=1; $i<=5; $i++)
                                                    <td>
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input type="radio" class="form-check-input score-radio"
                                                                   name="{{ $name }}" value="{{ $i }}"
                                                                   {{ old($name, $form->visitDetail->$name) == $i ? 'checked' : '' }} required>
                                                        </div>
                                                    </td>
                                                @endfor
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mb-3 text-end">
                                    <span class="avg-score">Rata-rata: <span id="avg-score">0</span></span>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Catatan</label>
                                    <textarea class="form-control" name="catatan" rows="3">{{ old('catatan', $form->visitDetail->catatan) }}</textarea>
                                </div>

                                {{-- Foto lama --}}
                                @php
                                    $photos = $form->visitDetail->lampiran_foto_array 
                                        ?? (!empty($form->visitDetail->lampiran_foto) 
                                            ? explode(',', $form->visitDetail->lampiran_foto) 
                                            : []);
                                @endphp
                                @if(!empty($photos))
                                    <div class="mb-3">
                                        <label class="form-label">Foto Lama</label>
                                        <div class="row">
                                            @foreach($photos as $photo)
                                                <div class="col-md-3 text-center mb-3">
                                                    <a href="{{ asset('assets/form_supplier/visit/photos/' . $photo) }}" target="_blank">
                                                        <img src="{{ asset('assets/form_supplier/visit/photos/' . $photo) }}" 
                                                             class="img-thumbnail mb-2" style="max-height:150px;">
                                                    </a>
                                                    <div class="form-check">
                                                        <input type="checkbox" name="hapus_foto[]" value="{{ $photo }}" 
                                                            id="hapus_{{ md5($photo) }}" class="form-check-input">
                                                        <label class="form-check-label" for="hapus_{{ md5($photo) }}">Hapus</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Tambah foto baru --}}
                                <div class="mb-3">
                                    <label for="lampiran_foto" class="form-label">Tambah Foto Baru</label>
                                    <input type="file" name="lampiran_foto[]" id="lampiran_foto" class="form-control" multiple>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary">Update Laporan Visit</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function calculateAverage() {
        let total = 0, count = 0;
        document.querySelectorAll('.score-radio:checked').forEach(el => {
            total += parseInt(el.value);
            count++;
        });
        document.getElementById('avg-score').innerText = count > 0 ? (total / count).toFixed(2) : 0;
    }
    document.querySelectorAll('.score-radio').forEach(el => {
        el.addEventListener('change', calculateAverage);
    });
    calculateAverage(); // initial load
});
</script>
@endsection
