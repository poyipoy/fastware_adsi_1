@extends('layout')

@section('content')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Data Feedback Kepuasan</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Feedback</li>
                <li class="breadcrumb-item active">Data Feedback</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
<style>
    .feedback-list-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
        font-family: 'Segoe UI', 'Roboto', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .page-header {
        background: linear-gradient(135deg, #001d3d 0%, #003566 100%);
        color: #ffffff;
        padding: 30px 40px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 29, 61, 0.08);
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
        letter-spacing: -0.5px;
    }

    .filter-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    }

    .filter-title {
        font-size: 16px;
        font-weight: 600;
        color: #001d3d;
        margin-bottom: 15px;
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-size: 13px;
        font-weight: 500;
        color: #2b2d42;
        margin-bottom: 6px;
    }

    .filter-input {
        padding: 10px 14px;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .filter-input:focus {
        outline: none;
        border-color: #003566;
        box-shadow: 0 0 0 3px rgba(0, 53, 102, 0.1);
    }

    .feedback-table {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    }

    .table {
        margin: 0;
        width: 100%;
    }

    .table thead th {
        background: #f8f9fa;
        color: #001d3d;
        font-weight: 600;
        font-size: 13px;
        padding: 16px;
        border-bottom: 2px solid #e5e7eb;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #2b2d42;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        text-transform: capitalize;
    }

    .badge-submitted {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-reviewed {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-active {
        background: #dcfce7;
        color: #166534;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: linear-gradient(135deg, #001d3d 0%, #003566 100%);
        color: #ffffff;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 29, 61, 0.3);
        color: #ffffff;
    }

    .btn-detail {
        background: #3b82f6;
        color: #ffffff;
        padding: 6px 14px;
        font-size: 13px;
    }

    .btn-detail:hover {
        background: #2563eb;
        color: #ffffff;
    }

    .pagination {
        margin-top: 25px;
        display: flex;
        justify-content: center;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
    }

    .metrics-preview {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .metric-tag {
        background: #eff6ff;
        color: #1e40af;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    @media (max-width: 768px) {
        .filter-row {
            grid-template-columns: 1fr;
        }

        .table {
            font-size: 12px;
        }

        .table thead th,
        .table tbody td {
            padding: 10px;
        }
    }
</style>

<div class="feedback-list-container">
    <div class="page-header">
        <h1>📊 Data Feedback Kepuasan Pengguna</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 14px;">Daftar feedback yang telah disubmit oleh pengguna sistem Fastware</p>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="filter-title">🔍 Filter Data</div>
        <form method="GET" action="{{ route('feedback.list') }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">Nama Pengguna</label>
                    <input type="text" name="user_name" class="filter-input" 
                           placeholder="Cari nama pengguna..." value="{{ request('user_name') }}">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Nama Sistem/Modul</label>
                    <input type="text" name="system_name" class="filter-input" 
                           placeholder="Cari nama sistem..." value="{{ request('system_name') }}">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-input">
                        <option value="">Semua Status</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <a href="{{ route('feedback.list') }}" class="btn" style="background: #e5e7eb; color: #374151;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="feedback-table">
        @if($feedbacks->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Pengguna</th>
                        <th>Jabatan</th>
                        <th>Sistem/Modul</th>
                        <th>Rata-rata Rating</th>
                        <th>Jumlah Fitur</th>
                        <th>Status</th>
                        <th>Tanggal Submit</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($feedbacks as $index => $feedback)
                        @php
                            $coreMetrics = is_array($feedback->core_metrics) ? $feedback->core_metrics : json_decode($feedback->core_metrics, true);
                            $features = is_array($feedback->features) ? $feedback->features : json_decode($feedback->features, true);
                            
                            // Calculate average rating from core metrics
                            $totalRating = 0;
                            $ratingCount = 0;
                            if ($coreMetrics) {
                                foreach ($coreMetrics as $value) {
                                    if (is_numeric($value) && $value > 0) {
                                        $totalRating += $value;
                                        $ratingCount++;
                                    }
                                }
                            }
                            $avgRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : 0;
                            $featureCount = is_array($features) ? count($features) : 0;
                        @endphp
                        <tr>
                            <td>{{ $feedbacks->firstItem() + $index }}</td>
                            <td><strong>{{ $feedback->user_name }}</strong></td>
                            <td>{{ $feedback->jabatan ?? '-' }}</td>
                            <td><strong style="color: #003566;">{{ $feedback->system_name }}</strong></td>
                            <td>
                                <span style="color: #fbbf24; font-size: 16px;">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($avgRating))
                                            ★
                                        @elseif($i - $avgRating < 1)
                                            ☆
                                        @else
                                            ☆
                                        @endif
                                    @endfor
                                </span>
                                <span style="font-size: 13px; color: #6b7280; margin-left: 4px;">{{ $avgRating }}/5</span>
                            </td>
                            <td>
                                <span class="metric-tag">{{ $featureCount }} fitur</span>
                            </td>
                            <td>
                                <span class="badge-status badge-{{ $feedback->status }}">
                                    {{ $feedback->status }}
                                </span>
                            </td>
                            <td style="font-size: 13px;">
                                {{ $feedback->created_at->format('d M Y, H:i') }}
                            </td>
                            <td>
                                <a href="{{ route('feedback.detail', $feedback->id) }}" class="btn btn-detail">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                {{ $feedbacks->links() }}
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Belum Ada Data Feedback</h3>
                <p>Belum ada feedback yang disubmit. Mulai kumpulkan feedback dari pengguna!</p>
            </div>
        @endif
    </div>
</div>
    </section>
</main><!-- End #main -->
@endsection
