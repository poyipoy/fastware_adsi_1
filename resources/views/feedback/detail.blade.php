@extends('layout')

@section('content')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Detail Feedback</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Feedback</li>
                <li class="breadcrumb-item"><a href="{{ route('feedback.list') }}">Data Feedback</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
<style>
    .detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        font-family: 'Segoe UI', 'Roboto', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .detail-header {
        background: linear-gradient(135deg, #001d3d 0%, #003566 100%);
        color: #ffffff;
        padding: 30px 40px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 29, 61, 0.08);
    }

    .detail-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0 0 10px 0;
    }

    .detail-header .meta {
        font-size: 14px;
        opacity: 0.9;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .detail-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #001d3d;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #003566;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
    }

    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .info-value {
        font-size: 15px;
        color: #2b2d42;
        font-weight: 500;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .metric-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #003566;
    }

    .metric-name {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .metric-rating {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .stars {
        color: #fbbf24;
        font-size: 20px;
    }

    .rating-value {
        font-size: 16px;
        font-weight: 600;
        color: #001d3d;
    }

    .features-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 20px;
    }

    .feature-item {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #3b82f6;
    }

    .feature-name {
        font-size: 16px;
        font-weight: 600;
        color: #001d3d;
        margin-bottom: 15px;
    }

    .feature-ratings {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .feature-metric {
        display: flex;
        flex-direction: column;
    }

    .feature-metric-label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .feature-metric-value {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .feedback-text {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #10b981;
        margin-top: 15px;
    }

    .feedback-text-title {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .feedback-text-content {
        font-size: 14px;
        color: #2b2d42;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-submitted {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-reviewed {
        background: #d1fae5;
        color: #065f46;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }

    .btn-secondary:hover {
        background: #d1d5db;
        color: #374151;
    }

    .back-button {
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .detail-header {
            padding: 20px;
        }

        .detail-card {
            padding: 20px;
        }

        .info-grid,
        .metrics-grid,
        .feature-ratings {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="detail-container">
    <div class="back-button">
        <a href="{{ route('feedback.list') }}" class="btn btn-secondary">
            ← Kembali ke Daftar
        </a>
    </div>

    <div class="detail-header">
        <h1>Detail Feedback Kepuasan</h1>
        <div class="meta">
            <span>📅 {{ $feedback->created_at->format('d F Y, H:i') }}</span>
            <span>👤 {{ $feedback->user_name }}</span>
            <span class="badge badge-{{ $feedback->status }}">{{ $feedback->status }}</span>
        </div>
    </div>

    <!-- Informasi Umum -->
    <div class="detail-card">
        <h2 class="section-title">Informasi Pengguna</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nama Pengguna</div>
                <div class="info-value">{{ $feedback->user_name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Jabatan</div>
                <div class="info-value">{{ $feedback->jabatan ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sistem/Modul yang Dinilai</div>
                <div class="info-value" style="color: #003566; font-weight: 600;">{{ $feedback->system_name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Dimodifikasi Oleh</div>
                <div class="info-value">{{ $feedback->modified_at ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Metrik Kinerja Inti -->
    <div class="detail-card">
        <h2 class="section-title">Metrik Kinerja Inti</h2>
        <div class="metrics-grid">
            @php
                $coreMetrics = is_array($feedback->core_metrics) ? $feedback->core_metrics : json_decode($feedback->core_metrics, true);
                $metricLabels = [
                    'akurasi' => 'Akurasi Data',
                    'responsivitas' => 'Responsivitas',
                    'stabilitas' => 'Stabilitas',
                    'efisiensi' => 'Efisiensi'
                ];
            @endphp

            @foreach($metricLabels as $key => $label)
                <div class="metric-card">
                    <div class="metric-name">{{ $label }}</div>
                    <div class="metric-rating">
                        <span class="stars">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= ($coreMetrics[$key] ?? 0))
                                    ★
                                @else
                                    ☆
                                @endif
                            @endfor
                        </span>
                        <span class="rating-value">{{ $coreMetrics[$key] ?? 0 }}/5</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Evaluasi Fitur Spesifik -->
    <div class="detail-card">
        <h2 class="section-title">Evaluasi Fitur Spesifik</h2>
        @php
            $features = is_array($feedback->features) ? $feedback->features : json_decode($feedback->features, true);
        @endphp

        @if($features && count($features) > 0)
            <div class="features-list">
                @foreach($features as $feature)
                    @if(isset($feature['name']) && $feature['name'])
                        <div class="feature-item">
                            <div class="feature-name">{{ $feature['name'] }}</div>
                            <div class="feature-ratings">
                                <div class="feature-metric">
                                    <div class="feature-metric-label">Kemudahan</div>
                                    <div class="feature-metric-value">
                                        <span class="stars" style="font-size: 16px;">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= ($feature['kemudahan'] ?? 0))
                                                    ★
                                                @else
                                                    ☆
                                                @endif
                                            @endfor
                                        </span>
                                        <span>{{ $feature['kemudahan'] ?? 0 }}/5</span>
                                    </div>
                                </div>
                                <div class="feature-metric">
                                    <div class="feature-metric-label">Tampilan</div>
                                    <div class="feature-metric-value">
                                        <span class="stars" style="font-size: 16px;">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= ($feature['tampilan'] ?? 0))
                                                    ★
                                                @else
                                                    ☆
                                                @endif
                                            @endfor
                                        </span>
                                        <span>{{ $feature['tampilan'] ?? 0 }}/5</span>
                                    </div>
                                </div>
                                <div class="feature-metric">
                                    <div class="feature-metric-label">Kebergunaan</div>
                                    <div class="feature-metric-value">
                                        <span class="stars" style="font-size: 16px;">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= ($feature['kebergunaan'] ?? 0))
                                                    ★
                                                @else
                                                    ☆
                                                @endif
                                            @endfor
                                        </span>
                                        <span>{{ $feature['kebergunaan'] ?? 0 }}/5</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p style="color: #6b7280; font-style: italic;">Tidak ada evaluasi fitur yang disubmit.</p>
        @endif
    </div>

    <!-- Umpan Balik Kualitatif -->
    <div class="detail-card">
        <h2 class="section-title">Umpan Balik Kualitatif</h2>
        
        <div class="feedback-text">
            <div class="feedback-text-title">Kendala Utama</div>
            <div class="feedback-text-content">
                {{ $feedback->obstacles ?: 'Tidak ada kendala yang dilaporkan.' }}
            </div>
        </div>

        <div class="feedback-text">
            <div class="feedback-text-title">Saran Pengembangan</div>
            <div class="feedback-text-content">
                {{ $feedback->suggestions ?: 'Tidak ada saran yang diberikan.' }}
            </div>
        </div>
    </div>
</div>
    </section>
</main><!-- End #main -->
@endsection
