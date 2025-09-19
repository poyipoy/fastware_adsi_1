<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner Calon Supplier - PT Astra Daido Steel Indonesia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
        }
        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .question-card {
            border-left: 4px solid #dee2e6;
            transition: border-color 0.3s ease;
        }
        .question-card:hover {
            border-left-color: #667eea;
        }
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        .form-label.required::after {
            content: " *";
            color: red;
            font-weight: bold;
        }
        /* Banner info paling atas */
        .top-banner {
            background: #ffc107;
            color: #000;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
            border-bottom: 3px solid #e0a800;
        }
    </style>
</head>
<body>
    <!-- Banner Informasi -->
    <div class="top-banner">
        <i class="fa fa-info-circle me-2"></i>
        Harap lengkapi seluruh data dengan benar. Terdapat template dibawah yang tersedia sebelum mengunggah dokumen.
    </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card card-glass">
                    <div class="card-body p-4 p-md-5">

                        <div class="text-start mb-4">
                            <img src="{{ asset('assets/img/AdasiLogo.png') }}" alt="Logo Perusahaan" style="height: 50px;">
                        </div>

                        <div class="text-center mb-5">
                            <h1 class="card-title fw-bold fs-2">Kuesioner Calon Supplier</h1>
                            <p class="text-muted">PT Astra Daido Steel Indonesia</p>
                        </div>

                        <form id="public-supplier-form" action="{{ route('supplierform.public.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="form_token" value="{{ $token }}">

                            <!-- Informasi Dasar -->
                            <h5 class="fw-bold mb-3 border-bottom pb-2">1. Informasi Dasar Perusahaan</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label for="supplier_name" class="form-label required">Nama Perusahaan</label>
                                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="alamat" class="form-label required">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="telp" class="form-label required">No. Telepon / Fax</label>
                                    <input type="text" class="form-control" id="telp" name="telp" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="npwp" class="form-label required">No. NPWP</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="npwp" name="npwp"
                                               minlength="15" maxlength="16"
                                               pattern="\d{15,16}"
                                               title="Masukkan 15–16 digit angka" required>
                                        <input type="file" class="form-control" id="npwp_file" name="npwp_file" title="Upload Scan NPWP" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="director" class="form-label required">Pimpinan Perusahaan</label>
                                    <input type="text" class="form-control" id="director" name="director" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="pic" class="form-label required">PIC / Marketing</label>
                                    <input type="text" class="form-control" id="pic" name="pic" required>
                                </div>
                            </div>

                            <!-- Kuesioner -->
                            <h5 class="fw-bold mb-3 mt-5 border-bottom pb-2">2. Kuesioner Kualitas & Kepatuhan</h5>
                            
                            <!-- Pertanyaan 1 -->
                            <div class="card question-card mb-3">
                                <div class="card-body">
                                    <p class="fw-semibold">Apakah barang/jasa memiliki standar kualitas?</p>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_quality_standard" id="has_quality_yes" value="1" required>
                                        <label class="form-check-label required" for="has_quality_yes">Ya</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_quality_standard" id="has_quality_no" value="0">
                                        <label class="form-check-label" for="has_quality_no">Tidak</label>
                                    </div>
                                    <div id="quality_details" class="mt-3" style="display: none;">
                                        <input type="text" class="form-control mb-2" name="quality_certificate" placeholder="Sebutkan jenis sertifikat (e.g., ISO 9001)">
                                        <input type="text" class="form-control" name="quality_certificate_from" placeholder="Sebutkan badan yang mengeluarkan sertifikat">
                                    </div>
                                </div>
                            </div>  

                            <!-- Pertanyaan 2 -->
                            <div class="card question-card mb-3">
                                <div class="card-body">
                                    <p class="fw-semibold">Apakah perusahaan memiliki penanggung jawab kualitas?</p>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_quality_responsible" id="has_responsible_yes" value="1" required>
                                        <label class="form-check-label required" for="has_responsible_yes">Ya</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_quality_responsible" id="has_responsible_no" value="0">
                                        <label class="form-check-label" for="has_responsible_no">Tidak</label>
                                    </div>
                                    <div id="responsible_details" class="mt-3" style="display: none;">
                                        <input type="text" class="form-control" name="quality_responsible_name" placeholder="Sebutkan nama penanggung jawab">
                                    </div>
                                </div>
                            </div>

                            <!-- Pertanyaan lainnya -->
                            <div class="card question-card mb-3"><div class="card-body"><p class="fw-semibold">Apakah barang/jasa memiliki Material Safety Data Sheet (MSDS)?</p><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_material_safety" id="msds_yes" value="1" required><label class="form-check-label required" for="msds_yes">Ya</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_material_safety" id="msds_no" value="0"><label class="form-check-label" for="msds_no">Tidak</label></div></div></div>
                            <div class="card question-card mb-3"><div class="card-body"><p class="fw-semibold">Apakah barang/jasa yang dijual aman (safe)?</p><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_safety" id="safety_yes" value="1" required><label class="form-check-label required" for="safety_yes">Ya</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_safety" id="safety_no" value="0"><label class="form-check-label" for="safety_no">Tidak</label></div></div></div>
                            <div class="card question-card mb-3"><div class="card-body"><p class="fw-semibold">Apakah perusahaan mempekerjakan anak di bawah umur?</p><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="employs_underage" id="underage_yes" value="1" required><label class="form-check-label required" for="underage_yes">Ya</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="employs_underage" id="underage_no" value="0"><label class="form-check-label" for="underage_no">Tidak</label></div></div></div>
                            <div class="card question-card mb-4"><div class="card-body"><p class="fw-semibold">Apakah perusahaan membayar upah sesuai UMR yang berlaku?</p><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="pays_min_wage" id="wage_yes" value="1" required><label class="form-check-label required" for="wage_yes">Ya</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="pays_min_wage" id="wage_no" value="0"><label class="form-check-label" for="wage_no">Tidak</label></div></div></div>

                            <!-- Upload Dokumen -->
                            <h5 class="fw-bold mb-3 mt-5 border-bottom pb-2">3. Upload Dokumen Pendukung</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="sppkp_file" class="form-label required">SPPKP</label>
                                    <input class="form-control" type="file" id="sppkp_file" name="sppkp_file" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="nib_file" class="form-label required">NIB</label>
                                    <input class="form-control" type="file" id="nib_file" name="nib_file" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="lampiran_compro" class="form-label required">Lampiran Company Profile</label>
                                    <input type="file" class="form-control" id="lampiran_compro" name="lampiran_compro[]" multiple>
                                </div>
                                <div class="col-md-6">
                                    <label for="rek_bank" class="form-label required">Surat Keterangan Rekening Bank</label>
                                    {{-- <small class="text-muted d-block mb-2">
                                        Silakan gunakan template resmi yang tersedia di bawah ini sebelum mengunggah dokumen.
                                    </small> --}}
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="rek_bank" name="rek_bank[]" multiple>
                                        <a href="{{ route('download.template.rek') }}" class="btn btn-outline-secondary">
                                            <i class="fa fa-download"></i> Template
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-5">
                                <button type="submit" id="submit-btn" class="btn btn-primary btn-lg">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    <span class="button-text">Kirim Formulir</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tampilkan/sembunyikan detail sertifikat kualitas
            const qualityDetailsDiv = document.getElementById('quality_details');
            const qualityCertInput = qualityDetailsDiv.querySelector('input[name="quality_certificate"]');
            const qualityCertFromInput = qualityDetailsDiv.querySelector('input[name="quality_certificate_from"]');

            document.querySelectorAll('input[name="has_quality_standard"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === '1') {
                        qualityDetailsDiv.style.display = 'block';
                        qualityCertInput.required = true;
                        qualityCertFromInput.required = true;
                    } else {
                        qualityDetailsDiv.style.display = 'none';
                        qualityCertInput.required = false;
                        qualityCertFromInput.required = false;
                    }
                });
            });

            // Tampilkan/sembunyikan detail penanggung jawab kualitas
            const responsibleDetailsDiv = document.getElementById('responsible_details');
            const responsibleNameInput = responsibleDetailsDiv.querySelector('input[name="quality_responsible_name"]');

            document.querySelectorAll('input[name="has_quality_responsible"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === '1') {
                        responsibleDetailsDiv.style.display = 'block';
                        responsibleNameInput.required = true;
                    } else {
                        responsibleDetailsDiv.style.display = 'none';
                        responsibleNameInput.required = false;
                    }
                });
            });

            // Validasi real-time NPWP hanya angka
            const npwpInput = document.getElementById('npwp');
            npwpInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length > 16) {
                    this.value = this.value.slice(0, 16);
                }
            });

            // Mencegah submit ganda
            const supplierForm = document.getElementById('public-supplier-form');
            if (supplierForm) {
                supplierForm.addEventListener('submit', function(e) {
                    const submitBtn = document.getElementById('submit-btn');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    const btnText = submitBtn.querySelector('.button-text');

                    if (submitBtn.disabled) {
                        e.preventDefault();
                        return;
                    }

                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    btnText.textContent = ' Mengirim...';
                });
            }
        });
    </script>
</body>
</html>
