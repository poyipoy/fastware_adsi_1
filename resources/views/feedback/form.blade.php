@extends('layout')

@section('content')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Evaluasi Kinerja Sistem</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Feedback</li>
                <li class="breadcrumb-item active">Form Kepuasan</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .feedback-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        font-family: 'Segoe UI', 'Roboto', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .feedback-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 29, 61, 0.08);
        overflow: hidden;
    }

    .feedback-header {
        background: linear-gradient(135deg, #001d3d 0%, #003566 100%);
        color: #ffffff;
        padding: 40px;
        text-align: center;
    }

    .feedback-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 10px;
        letter-spacing: -0.5px;
    }

    .feedback-header p {
        font-size: 15px;
        opacity: 0.9;
        margin: 0;
    }

    .feedback-body {
        padding: 40px;
    }

    .form-section {
        margin-bottom: 40px;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #001d3d;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #003566;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #2b2d42;
        margin-bottom: 8px;
    }

    .form-label.required::after {
        content: ' *';
        color: #dc2626;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        font-size: 14px;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .form-control:focus {
        outline: none;
        border-color: #003566;
        box-shadow: 0 0 0 3px rgba(0, 53, 102, 0.1);
    }

    .form-control:disabled {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    .metrics-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .metrics-table th {
        background: #f8f9fa;
        padding: 14px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #001d3d;
        border-bottom: 1.5px solid #e5e7eb;
    }

    .metrics-table td {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
    }

    .metrics-table tr:last-child td {
        border-bottom: none;
    }

    .metric-name {
        font-weight: 500;
        color: #2b2d42;
        font-size: 14px;
    }

    .metric-description {
        font-size: 12px;
        color: #6b7280;
        margin-top: 2px;
    }

    .star-rating {
        display: inline-flex;
        gap: 8px;
        cursor: pointer;
    }

    .star {
        font-size: 28px;
        color: #d1d5db;
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
    }

    .star:hover,
    .star.active {
        color: #fbbf24;
        transform: scale(1.1);
    }

    .feature-row {
        display: grid;
        grid-template-columns: 2fr repeat(3, 1fr) auto;
        gap: 16px;
        align-items: start;
        margin-bottom: 16px;
        padding: 20px;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }

    .feature-input-group {
        display: flex;
        flex-direction: column;
    }

    .feature-input-label {
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .feature-input {
        padding: 10px 12px;
        font-size: 14px;
        border: 1.5px solid #d1d5db;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .feature-input:focus {
        outline: none;
        border-color: #003566;
        box-shadow: 0 0 0 3px rgba(0, 53, 102, 0.1);
    }

    .feature-stars {
        display: flex;
        gap: 4px;
        margin-top: 6px;
    }

    .feature-star {
        font-size: 20px;
        color: #d1d5db;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .feature-star:hover,
    .feature-star.active {
        color: #fbbf24;
    }

    .btn {
        padding: 12px 24px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .btn-primary {
        background: linear-gradient(135deg, #001d3d 0%, #003566 100%);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 29, 61, 0.2);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 29, 61, 0.3);
    }

    .btn-secondary {
        background: #ffffff;
        color: #003566;
        border: 1.5px solid #003566;
    }

    .btn-secondary:hover {
        background: #f8f9fa;
    }

    .btn-danger {
        background: #dc2626;
        color: #ffffff;
        padding: 8px 16px;
        font-size: 13px;
    }

    .btn-danger:hover {
        background: #b91c1c;
    }

    .btn-success {
        background: #059669;
        color: #ffffff;
    }

    .btn-success:hover {
        background: #047857;
    }

    .btn-add-feature {
        margin-top: 16px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1.5px solid #e5e7eb;
    }

    .alert {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
    }

    .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .alert-success {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    @media (max-width: 768px) {
        .feedback-header {
            padding: 30px 20px;
        }

        .feedback-header h1 {
            font-size: 22px;
        }

        .feedback-body {
            padding: 24px;
        }

        .feature-row {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }

    .info-box {
        background: #eff6ff;
        border-left: 4px solid #003566;
        padding: 16px;
        border-radius: 6px;
        margin-bottom: 24px;
        font-size: 13px;
        color: #1e3a8a;
    }
</style>

<div class="feedback-container">
    <div class="feedback-card">
        <div class="feedback-header">
            <h1>Evaluasi Kinerja Sistem Fastware</h1>
            <p>PT Astra Daido Steel Indonesia</p>
        </div>

        <div class="feedback-body">
            <div class="info-box">
                <strong>📋 Petunjuk:</strong> Mohon berikan penilaian jujur terhadap sistem yang Anda gunakan. Feedback Anda sangat berharga untuk pengembangan sistem ke depan.
            </div>

            <div id="alert-container"></div>

            <form id="feedbackForm">
                @csrf

                <!-- Header Section -->
                <div class="form-section">
                    <h2 class="section-title">Informasi Pengguna</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Nama Pengguna</label>
                        <input type="text" class="form-control" id="user_name" value="{{ Auth::user()->name }}" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control" id="jabatan" placeholder="Contoh: Staff Produksi, Supervisor QC, dll.">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Nama Sistem/Modul yang Dinilai</label>
                        <input type="text" class="form-control" id="system_name" placeholder="Contoh: Modul Inventory, Dashboard BOPM, Form Klaim, dll." required>
                    </div>
                </div>

                <!-- Core Metrics Section -->
                <div class="form-section">
                    <h2 class="section-title">Metrik Kinerja Inti</h2>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">Berikan penilaian pada skala 1-5 untuk aspek berikut:</p>
                    
                    <table class="metrics-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Aspek</th>
                                <th>Penilaian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="metric-name">Akurasi Data</div>
                                    <div class="metric-description">Ketepatan informasi yang dihasilkan sistem</div>
                                </td>
                                <td>
                                    <div class="star-rating" data-metric="akurasi">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="metric-name">Responsivitas</div>
                                    <div class="metric-description">Kecepatan sistem dalam memproses perintah</div>
                                </td>
                                <td>
                                    <div class="star-rating" data-metric="responsivitas">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="metric-name">Stabilitas</div>
                                    <div class="metric-description">Keandalan sistem dari error/bug</div>
                                </td>
                                <td>
                                    <div class="star-rating" data-metric="stabilitas">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="metric-name">Efisiensi</div>
                                    <div class="metric-description">Dampak sistem terhadap kecepatan kerja user</div>
                                </td>
                                <td>
                                    <div class="star-rating" data-metric="efisiensi">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Dynamic Features Section -->
                <div class="form-section">
                    <h2 class="section-title">Evaluasi Fitur Spesifik</h2>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">Tambahkan fitur yang ingin Anda nilai dan berikan rating untuk setiap aspeknya:</p>
                    
                    <div id="features-container">
                        <!-- Feature rows will be added here dynamically -->
                    </div>

                    <button type="button" class="btn btn-secondary btn-add-feature" onclick="addFeatureRow()">
                        <span>➕</span> Tambah Fitur
                    </button>
                </div>

                <!-- Qualitative Feedback -->
                <div class="form-section">
                    <h2 class="section-title">Umpan Balik Kualitatif</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Apa kendala utama yang menghambat pekerjaan Anda di sistem ini?</label>
                        <textarea class="form-control" id="obstacles" rows="4" placeholder="Jelaskan kendala atau masalah yang sering Anda temui..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Saran pengembangan agar sistem lebih mendukung operasional perusahaan</label>
                        <textarea class="form-control" id="suggestions" rows="4" placeholder="Berikan saran untuk perbaikan atau fitur baru yang dibutuhkan..."></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset Form</button>
                    <button type="submit" class="btn btn-primary">Kirim Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Store ratings data
    const coreMetrics = {
        akurasi: 0,
        responsivitas: 0,
        stabilitas: 0,
        efisiensi: 0
    };

    const features = [];

    // Initialize star ratings for core metrics
    document.querySelectorAll('.star-rating').forEach(rating => {
        const metric = rating.dataset.metric;
        const stars = rating.querySelectorAll('.star');
        
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const value = parseInt(star.dataset.value);
                coreMetrics[metric] = value;
                
                // Update star display
                stars.forEach(s => {
                    const starValue = parseInt(s.dataset.value);
                    s.classList.toggle('active', starValue <= value);
                });
            });

            // Hover effect
            star.addEventListener('mouseenter', () => {
                const value = parseInt(star.dataset.value);
                stars.forEach(s => {
                    const starValue = parseInt(s.dataset.value);
                    if (starValue <= value) {
                        s.style.color = '#fbbf24';
                    }
                });
            });
        });

        rating.addEventListener('mouseleave', () => {
            stars.forEach(s => {
                const starValue = parseInt(s.dataset.value);
                if (starValue <= coreMetrics[metric]) {
                    s.style.color = '#fbbf24';
                } else {
                    s.style.color = '#d1d5db';
                }
            });
        });
    });

    // Add initial feature row
    addFeatureRow();

    function addFeatureRow() {
        const container = document.getElementById('features-container');
        const index = container.children.length;
        
        const featureRow = document.createElement('div');
        featureRow.className = 'feature-row';
        featureRow.dataset.index = index;
        
        featureRow.innerHTML = `
            <div class="feature-input-group">
                <label class="feature-input-label">Nama Fitur</label>
                <input type="text" class="feature-input feature-name" placeholder="Contoh: Export Excel, Filter Data, dll." data-index="${index}">
            </div>
            <div class="feature-input-group">
                <label class="feature-input-label">Kemudahan</label>
                <div class="feature-stars" data-aspect="kemudahan" data-index="${index}">
                    <span class="feature-star" data-value="1">★</span>
                    <span class="feature-star" data-value="2">★</span>
                    <span class="feature-star" data-value="3">★</span>
                    <span class="feature-star" data-value="4">★</span>
                    <span class="feature-star" data-value="5">★</span>
                </div>
            </div>
            <div class="feature-input-group">
                <label class="feature-input-label">Tampilan</label>
                <div class="feature-stars" data-aspect="tampilan" data-index="${index}">
                    <span class="feature-star" data-value="1">★</span>
                    <span class="feature-star" data-value="2">★</span>
                    <span class="feature-star" data-value="3">★</span>
                    <span class="feature-star" data-value="4">★</span>
                    <span class="feature-star" data-value="5">★</span>
                </div>
            </div>
            <div class="feature-input-group">
                <label class="feature-input-label">Kebergunaan</label>
                <div class="feature-stars" data-aspect="kebergunaan" data-index="${index}">
                    <span class="feature-star" data-value="1">★</span>
                    <span class="feature-star" data-value="2">★</span>
                    <span class="feature-star" data-value="3">★</span>
                    <span class="feature-star" data-value="4">★</span>
                    <span class="feature-star" data-value="5">★</span>
                </div>
            </div>
            <div class="feature-input-group" style="align-self: flex-end;">
                <button type="button" class="btn btn-danger" onclick="removeFeatureRow(${index})">🗑️</button>
            </div>
        `;
        
        container.appendChild(featureRow);
        
        // Initialize feature data
        features[index] = {
            name: '',
            kemudahan: 0,
            tampilan: 0,
            kebergunaan: 0
        };
        
        // Add event listeners for stars
        initializeFeatureStars(index);
    }

    function initializeFeatureStars(index) {
        const row = document.querySelector(`.feature-row[data-index="${index}"]`);
        const nameInput = row.querySelector('.feature-name');
        
        nameInput.addEventListener('input', (e) => {
            features[index].name = e.target.value;
        });
        
        row.querySelectorAll('.feature-stars').forEach(starsContainer => {
            const aspect = starsContainer.dataset.aspect;
            const stars = starsContainer.querySelectorAll('.feature-star');
            
            stars.forEach(star => {
                star.addEventListener('click', () => {
                    const value = parseInt(star.dataset.value);
                    features[index][aspect] = value;
                    
                    stars.forEach(s => {
                        const starValue = parseInt(s.dataset.value);
                        s.classList.toggle('active', starValue <= value);
                    });
                });

                star.addEventListener('mouseenter', () => {
                    const value = parseInt(star.dataset.value);
                    stars.forEach(s => {
                        const starValue = parseInt(s.dataset.value);
                        if (starValue <= value) {
                            s.style.color = '#fbbf24';
                        }
                    });
                });
            });

            starsContainer.addEventListener('mouseleave', () => {
                stars.forEach(s => {
                    const starValue = parseInt(s.dataset.value);
                    if (starValue <= features[index][aspect]) {
                        s.style.color = '#fbbf24';
                    } else {
                        s.style.color = '#d1d5db';
                    }
                });
            });
        });
    }

    function removeFeatureRow(index) {
        const row = document.querySelector(`.feature-row[data-index="${index}"]`);
        if (row) {
            row.remove();
            delete features[index];
        }
    }

    function resetForm() {
        if (confirm('Apakah Anda yakin ingin mereset form? Semua data yang telah diisi akan hilang.')) {
            location.reload();
        }
    }

    function showAlert(message, type = 'error') {
        const alertContainer = document.getElementById('alert-container');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        
        alertContainer.innerHTML = `
            <div class="alert ${alertClass}">
                ${message}
            </div>
        `;
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }

    // Form submission
    document.getElementById('feedbackForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Clear previous alerts
        document.getElementById('alert-container').innerHTML = '';
        
        // Validation
        const systemName = document.getElementById('system_name').value.trim();
        if (!systemName) {
            showAlert('❌ Nama Sistem/Modul wajib diisi!', 'error');
            return;
        }
        
        // Filter out empty features
        const validFeatures = features.filter(f => f && f.name.trim() !== '');
        
        if (validFeatures.length === 0) {
            showAlert('❌ Minimal harus ada satu fitur yang dinilai!', 'error');
            return;
        }
        
        // Collect form data
        const formData = {
            jabatan: document.getElementById('jabatan').value.trim(),
            system_name: systemName,
            core_metrics: coreMetrics,
            features: validFeatures,
            obstacles: document.getElementById('obstacles').value.trim(),
            suggestions: document.getElementById('suggestions').value.trim()
        };
        
        // Get CSRF token
        const csrfToken = document.querySelector('input[name="_token"]').value;
        
        try {
            const response = await fetch('/api/fastware/survey/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('✅ ' + result.message, 'success');
                
                // Reset form after 2 seconds
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showAlert('❌ ' + (result.message || 'Terjadi kesalahan saat mengirim feedback'), 'error');
            }
        } catch (error) {
            console.error('Error submitting feedback:', error);
            showAlert('❌ Terjadi kesalahan koneksi. Silakan coba lagi.', 'error');
        }
        
        // Console log for debugging
        console.log('📊 Feedback Data:', formData);
    });
</script>
</div>
    </section>
</main><!-- End #main -->
@endsection
