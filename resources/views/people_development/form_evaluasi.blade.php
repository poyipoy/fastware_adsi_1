@extends('layout')

@section('content')
    <div class="container mt-5">
        <h1 class="mb-4">Update Evaluasi</h1>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Form Evaluasi</h5>
                <button type="button" id="printPdf" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i> Cetak PDF
                </button>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('update-evaluasi.update', $data->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- Bagian Peserta -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="seksi" class="form-label"><strong>Seksi</strong></label>
                            <input type="text" class="form-control" id="section" name="section"
                                value="{{ $data->section }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="npk" class="form-label"><strong>NPK</strong></label>
                            <input type="text" class="form-control" id="npk" name="npk"
                                value="{{ $data->user ? $data->user->npk : '' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="nama" class="form-label"><strong>Nama</strong></label>
                            <input type="text" class="form-control" id="nama" name="nama"
                                value="{{ $data->user ? $data->user->name : '' }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="program_training" class="form-label"><strong>Program Pelatihan</strong></label>
                            <input type="text" class="form-control" id="program_training" name="program_training"
                                value="{{ $data->program_training_plan }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="penyelenggara" class="form-label"><strong>Penyelenggara</strong></label>
                            <input type="text" class="form-control" id="penyelenggara" name="penyelenggara"
                                value="{{ $data->lembaga ?? '-' }}" readonly>
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
                            <h5 class="mb-0">Evaluasi Peserta</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="metode_evaluasi" class="form-label"><strong>Metode
                                            Evaluasi</strong></label>
                                    <select class="form-select" id="metode_evaluasi" name="metode_evaluasi" required>
                                        <option value=""> ---- Pilih Data ----
                                        </option>
                                        <option value="Sharing Knowledge"
                                            {{ $data->metode_evaluasi == 'Sharing Knowledge' ? 'selected' : '' }}>Sharing
                                            Knowledge</option>
                                        <option value="Penerapan"
                                            {{ $data->metode_evaluasi == 'Penerapan' ? 'selected' : '' }}>Penerapan
                                        </option>
                                        <option value="Interview"
                                            {{ $data->metode_evaluasi == 'Interview' ? 'selected' : '' }}>Interview
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="minat" class="form-label"><strong>Minat Pelatihan</strong></label>
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
                                    <label for="daya_serap" class="form-label"><strong>Daya Serap Peserta</strong></label>
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
                                    <label for="penerapan" class="form-label"><strong>Penerapan dalam
                                            Tugas</strong></label>
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
                                <label for="efektif" class="form-label fs-2" style="color: blue"><strong> <b>APAKAH
                                            PELATIHAN
                                            INI EFEKTIF? </b>
                                    </strong></label>
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
                                    <label for="dievaluasi" class="form-label"><strong>Diketahui oleh:</strong></label>
                                    <br>
                                    <label for="tgl_pengajuan" class="form-label"><strong>Tgl:</strong></label>
                                    <label id="tgl_pengajuan" class="form-control"
                                        style="display: block;">{{ $data->tgl_pengajuan }}</label>
                                    <br><br>
                                    <label id="dievaluasi" class="form-control"
                                        style="display: block; margin-top: 3%;">Jessica Paune</label>
                                </div>
                                <div class="col-md-6">
                                    <label for="diketahui" class="form-label"><strong>Dievaluasi oleh:</strong></label>
                                    <br>
                                    <label for="tgl_pengajuan" class="form-label"><strong>Tgl:</strong></label>
                                    <label id="tgl_pengajuan" class="form-control"
                                        style="display: block;">{{ $data->tgl_pengajuan }}</label>
                                    <br><br>
                                    <label id="diketahui" class="form-control" style="display: block; margin-top: 3%;">
                                        {{ $data->diketahui }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol -->
                    <div class="d-flex justify-content-between">
                        @if (!in_array(auth()->user()->role_id, [14, 15]))
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update
                            </button>
                        @endif
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dapatkan role_id dari user yang sedang login langsung dari Blade
            const roleId = {{ auth()->user()->role_id }};

            if (roleId === 14 || roleId === 15) {
                // Jadikan semua input, textarea, dan select hanya-baca
                const inputs = document.querySelectorAll('form input, form textarea, form select');
                inputs.forEach(input => {
                    input.setAttribute('readonly', true); // Atur readonly untuk input dan textarea
                    input.setAttribute('disabled', true); // Atur disabled untuk select
                });

                // Nonaktifkan tombol submit
                const submitButton = document.querySelector('form button[type="submit"]');
                if (submitButton) {
                    submitButton.setAttribute('disabled', true);
                }
            }
        });

        document.getElementById('printPdf').addEventListener('click', function() {
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

            // Ambil data tanda tangan dari HTML
            const diketahuiOleh = document.querySelector('label#dievaluasi')?.innerText || '-';
            const diketahuiTanggal = document.querySelector('label#tgl_pengajuan')?.innerText || '-';
            const dievaluasiOleh = document.querySelector('label#diketahui')?.innerText || '-';
            const dievaluasiTanggal = document.querySelector('label#tgl_pengajuan')?.innerText || '-';


            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4',
            });

            // Logo
            const logoURL = `{{ asset('assets/foto/AdasiLogo.png') }}`; // Path logo
            const logo = new Image();
            logo.src = logoURL;

            logo.onload = function() {
                const pageWidth = pdf.internal.pageSize.getWidth();
                const imgWidth = 40; // Lebar gambar dalam mm
                const imgHeight = (logo.height / logo.width) * imgWidth; // Proporsi tinggi gambar
                const imgX = (pageWidth - imgWidth) / 2; // Posisi X agar gambar di tengah
                const imgY = 10; // Posisi Y gambar

                // Tambahkan logo ke header
                pdf.addImage(logo, 'PNG', imgX, imgY, imgWidth, imgHeight);

                // Sesuaikan posisi teks judul berdasarkan posisi akhir gambar
                const textY = imgY + imgHeight + 5; // Tambahkan margin kecil (5mm) antara gambar dan teks
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

            logo.onerror = function() {
                alert("Logo tidak ditemukan. Pastikan path logo sudah benar.");
            };
        });
    </script>
@endsection
