@extends('layout')

@section('content')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Dashboard Feedback</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Dashboard Feedback</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-lg-4 col-md-6">
                <div class="card info-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Feedback</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-chat-square-text"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $totalFeedback }}</h6>
                                <span class="text-muted small pt-1">Feedback Submitted</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card info-card">
                    <div class="card-body">
                        <h5 class="card-title">Rata-rata Rating</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #fef3c7; color: #fbbf24;">
                                <i class="bi bi-star-fill"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $avgOverallRating }}/5</h6>
                                <span class="text-muted small pt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($avgOverallRating))
                                            <i class="bi bi-star-fill" style="color: #fbbf24;"></i>
                                        @else
                                            <i class="bi bi-star" style="color: #fbbf24;"></i>
                                        @endif
                                    @endfor
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card info-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sistem</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #dbeafe; color: #3b82f6;">
                                <i class="bi bi-grid"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $totalSystems }}</h6>
                                <span class="text-muted small pt-1">Sistem Dinilai</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- System Ratings Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Rating Per Sistem</h5>
                        <div id="systemRatingsChart" style="min-height: 400px;"></div>
                    </div>
                </div>
            </div>

            <!-- Core Metrics Breakdown -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Metrik Kinerja Inti</h5>
                        <div id="metricsChart" style="min-height: 400px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Feedback Trend -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Trend Feedback Per Bulan</h5>
                        <div id="feedbackTrendChart" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div>

            <!-- Top Systems Table -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Top 5 Sistem Terbaik</h5>
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Sistem</th>
                                    <th scope="col">Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($systemRatings, 0, 5) as $index => $system)
                                <tr>
                                    <th scope="row">{{ $index + 1 }}</th>
                                    <td>{{ $system['name'] }}</td>
                                    <td>
                                        <span class="badge" style="background: linear-gradient(135deg, #001d3d 0%, #003566 100%);">
                                            {{ $system['rating'] }}/5
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </section>
</main><!-- End #main -->

<!-- Highcharts Library -->
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // System Ratings Chart
    Highcharts.chart('systemRatingsChart', {
        chart: {
            type: 'bar'
        },
        title: {
            text: null
        },
        xAxis: {
            categories: {!! json_encode(array_column($systemRatings, 'name')) !!},
            title: {
                text: 'Sistem'
            }
        },
        yAxis: {
            min: 0,
            max: 5,
            title: {
                text: 'Rating (1-5)'
            }
        },
        legend: {
            enabled: false
        },
        plotOptions: {
            bar: {
                dataLabels: {
                    enabled: true,
                    format: '{y}/5'
                },
                colorByPoint: true
            }
        },
        colors: ['#003566', '#0077b6', '#0096c7', '#00b4d8', '#48cae4', '#90e0ef'],
        series: [{
            name: 'Rating',
            data: {!! json_encode(array_column($systemRatings, 'rating')) !!}
        }],
        credits: {
            enabled: false
        }
    });

    // Metrics Breakdown Chart
    Highcharts.chart('metricsChart', {
        chart: {
            type: 'column'
        },
        title: {
            text: null
        },
        xAxis: {
            categories: ['Akurasi', 'Responsivitas', 'Stabilitas', 'Efisiensi']
        },
        yAxis: {
            min: 0,
            max: 5,
            title: {
                text: 'Rating'
            }
        },
        legend: {
            enabled: false
        },
        plotOptions: {
            column: {
                dataLabels: {
                    enabled: true,
                    format: '{y}/5'
                },
                colorByPoint: true
            }
        },
        colors: ['#22c55e', '#3b82f6', '#8b5cf6', '#f59e0b'],
        series: [{
            name: 'Rating',
            data: [
                {{ $metricsBreakdown['akurasi'] }},
                {{ $metricsBreakdown['responsivitas'] }},
                {{ $metricsBreakdown['stabilitas'] }},
                {{ $metricsBreakdown['efisiensi'] }}
            ]
        }],
        credits: {
            enabled: false
        }
    });

    // Feedback Trend Chart
    Highcharts.chart('feedbackTrendChart', {
        chart: {
            type: 'spline'
        },
        title: {
            text: null
        },
        xAxis: {
            categories: {!! json_encode($feedbackTrend->pluck('month')->toArray()) !!},
            title: {
                text: 'Bulan'
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Jumlah Feedback'
            }
        },
        legend: {
            enabled: false
        },
        plotOptions: {
            spline: {
                dataLabels: {
                    enabled: true
                },
                marker: {
                    enabled: true,
                    radius: 4
                }
            }
        },
        series: [{
            name: 'Feedback',
            data: {!! json_encode($feedbackTrend->pluck('count')->toArray()) !!},
            color: '#001d3d'
        }],
        credits: {
            enabled: false
        }
    });
});
</script>

<style>
.info-card .card-icon {
    width: 56px;
    height: 56px;
    background: #d1fae5;
    color: #10b981;
}

.info-card h6 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    color: #012970;
}

.card-title {
    padding: 20px 0 15px 0;
    font-size: 18px;
    font-weight: 500;
    color: #012970;
}
</style>
@endsection
