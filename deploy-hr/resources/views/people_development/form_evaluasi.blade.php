@extends('layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/hr/training-evaluation.css') }}">
@endpush

@section('content')
    <div class="container mt-4 mb-5 training-evaluation-page">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <div class="text-primary fw-semibold small text-uppercase mb-1">
                    {{ $isSharing ? 'Sharing Knowledge' : 'Training Development' }}
                </div>
                <h1 class="h3 mb-1">{{ $isSharing ? 'Evaluasi Grup Sharing Knowledge' : 'Update Evaluasi' }}</h1>
                <p class="text-muted mb-0">
                    {{ $isSharing
                        ? 'Satu hasil evaluasi berlaku untuk seluruh participant kegiatan.'
                        : 'Lengkapi hasil evaluasi pelatihan karyawan.' }}
                </p>
            </div>
            <button type="button" id="printPdf" class="btn btn-outline-success evaluation-action">
                <i class="fas fa-file-pdf me-1" aria-hidden="true"></i> Cetak PDF
            </button>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert" aria-labelledby="evaluation-error-title">
                <h2 class="h6 alert-heading" id="evaluation-error-title">Data belum dapat disimpan</h2>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card evaluation-card">
            <div class="card-header bg-white d-flex align-items-center justify-content-between gap-3">
                <h2 class="h5 card-title mb-0">Form Evaluasi</h2>
                <span class="badge {{ $data->evaluation_completed ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $data->evaluation_completed ? 'Evaluasi selesai' : 'Belum dievaluasi' }}
                </span>
            </div>
            <div class="card-body p-3 p-lg-4">
                @if ($isSharing)
                    <div class="alert alert-primary d-flex gap-3 align-items-start" role="note">
                        <i class="bi bi-people-fill fs-4" aria-hidden="true"></i>
                        <div>
                            <strong>Penilaian dilakukan secara kelompok.</strong>
                            <div class="small mt-1">
                                Nilai minat, daya serap, penerapan, dan efektivitas mewakili seluruh participant.
                            </div>
                        </div>
                    </div>
                @endif

                <form id="evaluasiForm" method="POST" action="{{ route('update-evaluasi.update', $data->id) }}">
                    @csrf
                    @method('PUT')
                    <fieldset class="border-0 p-0 m-0" @disabled(! $canEditEvaluation)>

                    <!-- Bagian Peserta -->
                    @if ($isSharing)
                        <section class="evaluation-section" aria-labelledby="participant-heading">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <h3 class="h6 mb-0" id="participant-heading">Participant kegiatan</h3>
                                <span class="badge bg-light text-dark border">{{ $participants->count() }} participant</span>
                            </div>
                            <ul class="participant-list mb-0">
                                @foreach ($participants as $participant)
                                    <li>
                                        <i class="bi bi-person-circle text-primary" aria-hidden="true"></i>
                                        <span>{{ \App\Services\HR\EmployeeIdentityFormatter::label($participant, ' — ') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @else
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="section" class="form-label"><strong>Seksi</strong></label>
                                <input type="text" class="form-control evaluation-readonly" id="section" name="section"
                                    value="{{ $data->section->name ?? '-' }}" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="npk" class="form-label"><strong>NPK</strong></label>
                                <input type="text" class="form-control evaluation-readonly" id="npk" name="npk"
                                    value="{{ \App\Services\HR\EmployeeIdentityFormatter::npk($data->user?->npk) }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="nama" class="form-label"><strong>Nama</strong></label>
                                <input type="text" class="form-control evaluation-readonly" id="nama" name="nama"
                                    value="{{ $data->user ? $data->user->name : '' }}" readonly>
                            </div>
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="program_training" class="form-label">
                                <strong>{{ $isSharing ? 'Program Sharing Knowledge' : 'Program Pelatihan' }}</strong>
                            </label>
                            <input type="text" class="form-control evaluation-readonly" id="program_training"
                                name="program_training"
                                value="{{ $data->program_training_plan ?: $data->program_training }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="penyelenggara" class="form-label"><strong>Penyelenggara</strong></label>
                            <input type="text" class="form-control evaluation-readonly" id="penyelenggara"
                                name="penyelenggara" value="{{ $data->lembaga_plan ?: ($data->lembaga ?? '-') }}" readonly>
                        </div>
                    </div>

                    <!-- Evaluasi Materi -->
                    <div class="card my-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Evaluasi Materi</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="relevansi" class="form-label"><strong>1. Relevansi bagi peserta</strong></label>
                                <select class="form-select" id="relevansi" name="relevansi" required>
                                    <option value=""> ---- Pilih Data ----
                                    </option>
                                    <option value="Ya" {{ $data->relevansi == 'Ya' ? 'selected' : '' }}>Ya</option>
                                    <option value="Tidak" {{ $data->relevansi == 'Tidak' ? 'selected' : '' }}>Tidak
                                    </option>
                                </select>
                                <textarea class="form-control mt-2" id="alasan_relevansi" name="alasan_relevansi" placeholder="Alasan">{{ $data->alasan_relevansi }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="rekomendasi" class="form-label"><strong>2. Rekomendasi
                                        selanjutnya</strong></label>
                                <select class="form-select" id="rekomendasi" name="rekomendasi" required>
                                    <option value=""> ---- Pilih Data ----
                                    </option>
                                    <option value="Lanjutkan" {{ $data->rekomendasi == 'Lanjutkan' ? 'selected' : '' }}>
                                        Lanjutkan</option>
                                    <option value="Dihentikan" {{ $data->rekomendasi == 'Dihentikan' ? 'selected' : '' }}>
                                        Dihentikan</option>
                                </select>
                                <textarea class="form-control mt-2" id="alasan_rekomendasi" name="alasan_rekomendasi" placeholder="Alasan">{{ $data->alasan_rekomendasi }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Evaluasi Penyelenggaraan -->
                    <div class="card my-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Evaluasi Penyelenggaraan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="kelengkapan_materi" class="form-label"><strong>Kelengkapan
                                            Materi</strong></label>
                                    <select class="form-select" id="kelengkapan_materi" name="kelengkapan_materi" required>
                                        <option value=""> ---- Pilih Data ----
                                        </option>
                                        <option value="Lengkap"
                                            {{ $data->kelengkapan_materi == 'Lengkap' ? 'selected' : '' }}>Lengkap</option>
                                        <option value="Cukup Lengkap"
                                            {{ $data->kelengkapan_materi == 'Cukup Lengkap' ? 'selected' : '' }}>Cukup
                                            Lengkap</option>
                                        <option value="Tidak Lengkap"
                                            {{ $data->kelengkapan_materi == 'Tidak Lengkap' ? 'selected' : '' }}>Tidak
                                            Lengkap</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="lokasi" class="form-label"><strong>Lokasi
                                            Penyelenggaraan</strong></label>
                                    <select class="form-select" id="lokasi" name="lokasi" required>
                                        <option value=""> ---- Pilih Data ----
                                        </option>
                                        <option value="Dekat" {{ $data->lokasi == 'Dekat' ? 'selected' : '' }}>Dekat
                                        </option>
                                        <option value="Sedang" {{ $data->lokasi == 'Sedang' ? 'selected' : '' }}>Sedang
                                        </option>
                                        <option value="Jauh" {{ $data->lokasi == 'Jauh' ? 'selected' : '' }}>Jauh
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="metode_pengajaran" class="form-label"><strong>Metode
                                            Pengajaran</strong></label>
                                    <select class="form-select" id="metode_pengajaran" name="metode_pengajaran" required>
                                        <option value=""> ---- Pilih Data ----
                                        </option>
                                        <option value="Mudah Dimengerti"
                                            {{ $data->metode_pengajaran == 'Mudah Dimengerti' ? 'selected' : '' }}>Mudah
                                            Dimengerti</option>
                                        <option value="Cukup Dimengerti"
                                            {{ $data->metode_pengajaran == 'Cukup Dimengerti' ? 'selected' : '' }}>Cukup
                                            Dimengerti</option>
                                        <option value="Sulit Dimengerti"
                                            {{ $data->metode_pengajaran == 'Sulit Dimengerti' ? 'selected' : '' }}>Sulit
                                            Dimengerti</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="fasilitas" class="form-label"><strong>Fasilitas
                                            Pengajaran</strong></label>
                                    <select class="form-select" id="fasilitas" name="fasilitas" required>
                                        <option value=""> ---- Pilih Data ----
                                        </option>
                                        <option value="Lengkap" {{ $data->fasilitas == 'Lengkap' ? 'selected' : '' }}>
                                            Lengkap</option>
                                        <option value="Cukup Lengkap"
                                            {{ $data->fasilitas == 'Cukup Lengkap' ? 'selected' : '' }}>Cukup Lengkap
                                        </option>
                                        <option value="Tidak Lengkap"
                                            {{ $data->fasilitas == 'Tidak Lengkap' ? 'selected' : '' }}>Tidak Lengkap
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="lainnya_1" class="form-label"><strong>Lainnya</strong></label>
                                <input type="text" class="form-control" id="lainnya_1" name="lainnya_1"
                                    value="{{ $data->lainnya_1 }}" placeholder="Tuliskan evaluasi lainnya">
                            </div>
                        </div>
                    </div>

                    <!-- Evaluasi Peserta -->
                    <div class="card my-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                {{ $isSharing ? 'Evaluasi Peserta Secara Kelompok' : 'Evaluasi Peserta' }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="metode_evaluasi" class="form-label"><strong>Metode
                                            Evaluasi</strong></label>
                                    @if ($isSharing)
                                        <select class="form-select evaluation-readonly" id="metode_evaluasi_display"
                                            aria-describedby="metode-evaluasi-help" disabled>
                                            <option selected>Sharing Knowledge</option>
                                        </select>
                                        <input type="hidden" id="metode_evaluasi" name="metode_evaluasi"
                                            value="Sharing Knowledge">
                                        <div class="form-text" id="metode-evaluasi-help">
                                            Metode dikunci sesuai kategori usulan.
                                        </div>
                                    @else
                                        <select class="form-select" id="metode_evaluasi" name="metode_evaluasi" required>
                                            <option value=""> ---- Pilih Data ----</option>
                                            <option value="Sharing Knowledge"
                                                {{ $data->metode_evaluasi == 'Sharing Knowledge' ? 'selected' : '' }}>
                                                Sharing Knowledge
                                            </option>
                                            <option value="Penerapan"
                                                {{ $data->metode_evaluasi == 'Penerapan' ? 'selected' : '' }}>Penerapan
                                            </option>
                                            <option value="Interview"
                                                {{ $data->metode_evaluasi == 'Interview' ? 'selected' : '' }}>Interview
                                            </option>
                                        </select>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label for="minat" class="form-label">
                                        <strong>{{ $isSharing ? 'Minat Participant Secara Kelompok' : 'Minat Pelatihan' }}</strong>
                                    </label>
                                    <select class="form-select" id="minat" name="minat" required>
                                        <option value=""> ---- Pilih Data ----
                                        </option>
                                        <option value="Tinggi" {{ $data->minat == 'Tinggi' ? 'selected' : '' }}>Tinggi
                                        </option>
                                        <option value="Sedang" {{ $data->minat == 'Sedang' ? 'selected' : '' }}>Sedang
                                        </option>
                                        <option value="Rendah" {{ $data->minat == 'Rendah' ? 'selected' : '' }}>Rendah
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="daya_serap" class="form-label">
                                        <strong>{{ $isSharing ? 'Daya Serap Kelompok' : 'Daya Serap Peserta' }}</strong>
                                    </label>
                                    <select class="form-select" id="daya_serap" name="daya_serap" required>
                                        <option value=""> ---- Pilih Data ----
                                        </option>
                                        <option value="Menguasai Materi"
                                            {{ $data->daya_serap == 'Menguasai Materi' ? 'selected' : '' }}>Menguasai
                                            Materi</option>
                                        <option value="Paham Materi Penting"
                                            {{ $data->daya_serap == 'Paham Materi Penting' ? 'selected' : '' }}>Paham
                                            Materi Penting</option>
                                        <option value="Tidak Paham"
                                            {{ $data->daya_serap == 'Tidak Paham' ? 'selected' : '' }}>Tidak Paham</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="penerapan" class="form-label">
                                        <strong>{{ $isSharing ? 'Penerapan Kelompok dalam Tugas' : 'Penerapan dalam Tugas' }}</strong>
                                    </label>
                                    <select class="form-select" id="penerapan" name="penerapan" required>
                                        <option value=""> ---- Pilih Data ----
                                        </option>
                                        <option value="Cepat" {{ $data->penerapan == 'Cepat' ? 'selected' : '' }}>Cepat
                                        </option>
                                        <option value="Cukup" {{ $data->penerapan == 'Cukup' ? 'selected' : '' }}>Cukup
                                        </option>
                                        <option value="Lambat" {{ $data->penerapan == 'Lambat' ? 'selected' : '' }}>Lambat
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="lainnya_2" class="form-label"><strong>Lainnya</strong></label>
                                <input type="text" class="form-control" id="lainnya_2" name="lainnya_2"
                                    value="{{ $data->lainnya_2 }}" placeholder="Tuliskan evaluasi lainnya">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="efektif" class="form-label fw-bold">
                                    Apakah {{ $isSharing ? 'kegiatan Sharing Knowledge' : 'pelatihan' }} ini efektif?
                                </label>
                                <select class="form-select" id="efektif" name="efektif" required>
                                    <option value=""> ---- Pilih Data ----
                                    </option>
                                    <option value="Efektif" {{ $data->efektif == 'Efektif' ? 'selected' : '' }}>Efektif
                                    </option>
                                    <option value="Tidak Efektif"
                                        {{ $data->efektif == 'Tidak Efektif' ? 'selected' : '' }}>Tidak Efektif
                                    </option>
                                </select>
                                <textarea class="form-control mt-2" id="catatan_tambahan" name="catatan_tambahan" placeholder="catatan tambahan">{{ $data->catatan_tambahan }}</textarea>
                            </div>
                        </div>
                    </div>
                    <!-- Tanda Tangan -->
                    <div class="card my-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Tanda Tangan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    @if ($isSharing)
                                        <label class="form-label"><strong>Participant kegiatan:</strong></label>
                                        <div class="evaluation-readonly participant-signature-list" id="diketahui_display">
                                            @foreach ($participants as $participant)
                                                <div>{{ \App\Services\HR\EmployeeIdentityFormatter::label($participant, ' — ') }}</div>
                                            @endforeach
                                        </div>
                                        <span id="tgl_diketahui" class="visually-hidden">
                                            Tidak memerlukan konfirmasi participant
                                        </span>
                                    @else
                                        <label class="form-label"><strong>Diketahui oleh:</strong></label>
                                        <div class="form-label small text-muted">Tanggal</div>
                                        <div id="tgl_diketahui" class="form-control evaluation-readonly">
                                            {{ $data->tgl_pengajuan ? \Carbon\Carbon::parse($data->tgl_pengajuan)->format('d-m-Y') : ($data->updated_at ? \Carbon\Carbon::parse($data->updated_at)->format('d-m-Y') : \Carbon\Carbon::parse($data->created_at)->format('d-m-Y')) }}
                                        </div>
                                        <div id="diketahui_display" class="form-control evaluation-readonly mt-2">
                                            {{ $data->user ? \App\Services\HR\EmployeeIdentityFormatter::label($data->user, ' — ') : ($data->diketahui ?? '-') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><strong>Dievaluasi oleh:</strong></label>
                                    <div class="form-label small text-muted">Tanggal</div>
                                    <div id="tgl_dievaluasi" class="form-control evaluation-readonly">
                                        {{ $data->tgl_pengajuan ? \Carbon\Carbon::parse($data->tgl_pengajuan)->format('d-m-Y') : '-' }}
                                    </div>
                                    <div id="dievaluasi_display" class="form-control evaluation-readonly mt-2">
                                        {{ $data->dievaluasi ?? auth()->user()->name ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol -->
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        @if ($canEditEvaluation)
                            <button type="submit" class="btn btn-primary evaluation-action">
                                <i class="fas fa-save me-1" aria-hidden="true"></i>
                                {{ $isSharing ? 'Simpan Evaluasi Grup' : 'Update' }}
                            </button>
                        @endif
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary evaluation-action">Kembali</a>
                    </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/hr/training-evaluation-pdf.js') }}"></script>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: "{{ session('error') }}",
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    @endif

    <script>
        document.getElementById('evaluasiForm').addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Simpan',
                text: "Apakah Anda yakin ingin menyimpan formulir evaluasi ini?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    this.submit();
                }
            });
        });

        document.getElementById('printPdfLegacy')?.addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;

            // Ambil nilai dari form
            const section = document.querySelector('input[name="section"]')?.value || '-';
            const peserta = document.querySelector('input[name="nama"]')?.value || '-';
            const npk = document.querySelector('input[name="npk"]')?.value || '-';
            const program = document.querySelector('input[name="program_training"]')?.value || '-';
            const penyelenggara = document.querySelector('input[name="penyelenggara"]')?.value || '-';

            const relevansi = document.querySelector('select[name="relevansi"]')?.value || '-';
            const alasanRelevansi = document.querySelector('textarea[name="alasan_relevansi"]')?.value || '-';

            const rekomendasi = document.querySelector('select[name="rekomendasi"]')?.value || '-';
            const alasanRekomendasi = document.querySelector('textarea[name="alasan_rekomendasi"]')?.value || '-';

            const kelengkapanMateri = document.querySelector('select[name="kelengkapan_materi"]')?.value || '-';
            const lokasi = document.querySelector('select[name="lokasi"]')?.value || '-';
            const metodePengajaran = document.querySelector('select[name="metode_pengajaran"]')?.value || '-';
            const fasilitas = document.querySelector('select[name="fasilitas"]')?.value || '-';
            const lainnyaPenyelenggara = document.querySelector('input[name="lainnya_1"]')?.value || '-';

            const metodeEvaluasi = document.querySelector('select[name="metode_evaluasi"]')?.value || '-';
            const minat = document.querySelector('select[name="minat"]')?.value || '-';
            const dayaSerap = document.querySelector('select[name="daya_serap"]')?.value || '-';
            const penerapan = document.querySelector('select[name="penerapan"]')?.value || '-';
            const lainnyaPeserta = document.querySelector('input[name="lainnya_2"]')?.value || '-';

            const efektif = document.querySelector('select[name="efektif"]')?.value || '-';
            const catatanTambahan = document.querySelector('textarea[name="catatan_tambahan"]')?.value || '-';

            // Ambil data tanda tangan dari HTML (gunakan ID unik)
            const diketahuiOleh = document.querySelector('label#diketahui_display')?.innerText || '-';
            const diketahuiTanggal = document.querySelector('label#tgl_diketahui')?.innerText || '-';
            const dievaluasiOleh = document.querySelector('label#dievaluasi_display')?.innerText || '-';
            const dievaluasiTanggal = document.querySelector('label#tgl_dievaluasi')?.innerText || '-';


            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4',
            });

            // Try available logo paths and fall back to no-logo PDF
            const logoUrls = [
                `{{ asset('assets/img/AdasiLogo.png') }}`,
                `{{ asset('assets/foto/AdasiLogo.jpg') }}`
            ];

            let logoIndex = 0;
            const logo = new Image();
            logo.crossOrigin = 'anonymous';

            const generatePdf = function(logoImg) {
                const pageWidth = pdf.internal.pageSize.getWidth();
                let textY = 20; // Default title Y when no logo

                if (logoImg) {
                    const imgWidth = 40; // Lebar gambar dalam mm
                    const imgHeight = (logoImg.height / logoImg.width) * imgWidth; // Proporsi tinggi gambar
                    const imgX = (pageWidth - imgWidth) / 2; // Posisi X agar gambar di tengah
                    const imgY = 10; // Posisi Y gambar
                    try {
                        pdf.addImage(logoImg, 'PNG', imgX, imgY, imgWidth, imgHeight);
                        textY = imgY + imgHeight + 5;
                    } catch (e) {
                        // If adding image fails, continue without logo
                        textY = 20;
                    }
                }

                // Sesuaikan posisi teks judul berdasarkan posisi akhir gambar
                pdf.setFontSize(12);
                pdf.text("FORMULIR EVALUASI HASIL PELATIHAN", pageWidth / 2, textY, {
                    align: "center"
                });

                // Border utama
                pdf.setDrawColor(0);
                pdf.setLineWidth(0.5);
                pdf.rect(10, 30, 190, 250);

                // Data Peserta
                pdf.setFontSize(10);
                pdf.setFont("helvetica", "normal");
                pdf.text("Seksi:", 12, 40);
                pdf.text(section, 50, 40);

                pdf.text("Peserta:", 12, 50);
                pdf.text(peserta, 50, 50);
                pdf.text("NPK:", 110, 50);
                pdf.text(npk, 140, 50);

                pdf.text("Program Pelatihan:", 12, 60);
                pdf.text(program, 50, 60);
                pdf.text("Penyelenggara:", 12, 70);
                pdf.text(penyelenggara, 50, 70);

                // Evaluasi Materi
                pdf.setFont("helvetica", "bold");
                pdf.text("EVALUASI - MATERI", 12, 80);
                pdf.setDrawColor(0);
                pdf.setLineWidth(0.2);
                pdf.rect(10, 75, 190, 40);
                pdf.setFont("helvetica", "normal");
                pdf.text("1. Relevansi bagi peserta:", 12, 90);
                pdf.text(`Jawaban: ${relevansi}`, 59, 90);
                pdf.text("Alasan:", 12, 95);
                pdf.text(alasanRelevansi, 50, 95);

                pdf.text("2. Rekomendasi selanjutnya:", 12, 105);
                pdf.text(`Jawaban: ${rekomendasi}`, 59, 105);
                pdf.text("Alasan:", 12, 110);
                pdf.text(alasanRekomendasi, 50, 110);

                // Evaluasi Penyelenggara
                pdf.setFont("helvetica", "bold");
                pdf.text("EVALUASI - PENYELENGGARA", 12, 120);
                pdf.rect(10, 115, 190, 60);
                pdf.setFont("helvetica", "normal");
                pdf.text("1. Kelengkapan Materi:", 12, 130);
                pdf.text(kelengkapanMateri, 57, 130);

                pdf.text("2. Lokasi Penyelenggaraan:", 12, 135);
                pdf.text(lokasi, 57, 135);

                pdf.text("3. Metode Pengajaran:", 12, 140);
                pdf.text(metodePengajaran, 57, 140);

                pdf.text("4. Fasilitas:", 12, 145);
                pdf.text(fasilitas, 57, 145);

                pdf.text("5. Lainnya:", 12, 150);
                pdf.text(lainnyaPenyelenggara, 50, 150);

                // Evaluasi Peserta
                pdf.setFont("helvetica", "bold");
                pdf.text("EVALUASI - PESERTA", 12, 180);
                pdf.rect(10, 175, 190, 40);
                pdf.setFont("helvetica", "normal");
                pdf.text("1. Metode Evaluasi:", 12, 190);
                pdf.text(metodeEvaluasi, 50, 190);

                pdf.text("2. Minat Pelatihan:", 12, 195);
                pdf.text(minat, 50, 195);

                pdf.text("3. Daya Serap:", 12, 200);
                pdf.text(dayaSerap, 50, 200);

                pdf.text("4. Penerapan:", 12, 205);
                pdf.text(penerapan, 50, 205);

                pdf.text("5. Lainnya:", 12, 210);
                pdf.text(lainnyaPeserta, 50, 210);

                // Efektivitas
                pdf.setFont("helvetica", "bold");
                pdf.text("EFEKTIVITAS", 12, 220);
                pdf.rect(10, 215, 190, 30);
                pdf.setFont("helvetica", "bold");
                pdf.text(`Apakah Pelatihan Ini Efektif? ${efektif}`, 12, 230);
                pdf.text("Catatan Tambahan:", 12, 235);
                pdf.text(catatanTambahan, 50, 235);

                // Footer - Tanda Tangan
                pdf.setFont("helvetica", "bold");
                pdf.text("Diketahui oleh:", 12, 260);
                pdf.text(diketahuiOleh, 50, 260);
                pdf.text("Tgl:", 12, 265);
                pdf.text(diketahuiTanggal, 50, 265);

                pdf.text("Dievaluasi oleh:", 110, 260);
                pdf.text(dievaluasiOleh, 150, 260);
                pdf.text("Tgl:", 110, 265);
                pdf.text(dievaluasiTanggal, 150, 265);

                // Border untuk tanda tangan
                pdf.rect(10, 255, 90, 15);
                pdf.rect(110, 255, 90, 15);

                // Simpan PDF
                pdf.save("Export_Form Evaluasi.pdf");
            };

            logo.onload = function() {
                generatePdf(logo);
            };

            logo.onerror = function() {
                logoIndex++;
                if (logoIndex < logoUrls.length) {
                    // Try next URL
                    logo.src = logoUrls[logoIndex];
                } else {
                    // No logo found, generate PDF without logo
                    generatePdf(null);
                }
            };

            // Start loading first logo
            logo.src = logoUrls[0];
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('evaluasiForm');
            const basePayload = @json($evaluationPayload);
            const fieldValue = (name) => form.elements.namedItem(name)?.value || '-';

            document.getElementById('printPdf')?.addEventListener('click', function() {
                const payload = {
                    ...basePayload,
                    relevansi: fieldValue('relevansi'),
                    alasan_relevansi: fieldValue('alasan_relevansi'),
                    rekomendasi: fieldValue('rekomendasi'),
                    alasan_rekomendasi: fieldValue('alasan_rekomendasi'),
                    kelengkapan_materi: fieldValue('kelengkapan_materi'),
                    lokasi: fieldValue('lokasi'),
                    metode_pengajaran: fieldValue('metode_pengajaran'),
                    fasilitas: fieldValue('fasilitas'),
                    lainnya_1: fieldValue('lainnya_1'),
                    metode_evaluasi: fieldValue('metode_evaluasi'),
                    minat: fieldValue('minat'),
                    daya_serap: fieldValue('daya_serap'),
                    penerapan: fieldValue('penerapan'),
                    lainnya_2: fieldValue('lainnya_2'),
                    efektif: fieldValue('efektif'),
                    catatan_tambahan: fieldValue('catatan_tambahan'),
                    dievaluasi: document.getElementById('dievaluasi_display')?.textContent?.trim() || '-',
                    tgl_pengajuan: document.getElementById('tgl_dievaluasi')?.textContent?.trim() || '-',
                };

                try {
                    window.TrainingEvaluationPdf.download(payload);
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'PDF belum dapat dibuat',
                        text: error.message,
                    });
                }
            });

            document.querySelector('[aria-invalid="true"]')?.focus();
        });
    </script>
@endsection
