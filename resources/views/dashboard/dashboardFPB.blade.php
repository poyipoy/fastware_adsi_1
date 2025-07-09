@extends('layout')

@section('content')
<main id="main" class="main">

    <style>
        /* CSS Anpassen */
        .dashboard-container {
            height: calc(100vh - 80px); /* Sesuaikan dengan tinggi header */
            overflow: hidden;
            padding: 15px 10px 10px 10px;
            position: relative;
        }

        .carousel-inner,
        .carousel-item,
        .carousel-item.active {
            height: 100%;
        }

        /* Tambahkan CSS untuk fade effect dan scroll */
        .carousel-fade .carousel-item {
            opacity: 0;
            transition: opacity 0.5s ease-in-out; /* Efek fade */
        }
        .carousel-fade .carousel-item.active {
            opacity: 1; /* Item aktif terlihat */
        }

        .dashboard-container {
            overflow-x: hidden; /* Nonaktifkan scroll horizontal default */
        }

        .card {
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card-body {
            overflow: auto;
        }

        .form-select-sm {
            max-width: 250px;
        }

        @media (max-width: 768px) {
            .form-select-sm {
                max-width: 100%;
            }

            .card-header h4 {
                font-size: 1.1rem;
            }

            .card-header h5 {
                font-size: 1rem;
            }
        }

        .highcharts-figure {
            height: calc(100% - 30px);
            position: relative;
        }

        .chart-wrapper {
            height: 90%;
            min-height: 300px;
        }

        #dashboardCarousel {
            cursor: grab; /* Menunjukkan elemen dapat di-drag */
        }
        #dashboardCarousel.dragging {
            user-select: none; /* Mencegah seleksi teks saat dragging */
        }
    </style>

  

  <section class="section dashboard dashboard-container">
        <div id="dashboardCarousel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="2"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="3"></button>
            </div>
            <div class="carousel-inner h-100">
                <!-- Slide 1: Form Pengajuan Barang + 2 Pie Chart -->
                <div class="carousel-item active h-100">
    <div class="row h-100">
        {{-- Bagian Chart FPB (Column) --}}
        <div class="col-sm-8 h-100">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h4 class="mb-2 mb-sm-0">Form Pengajuan Barang</h4>
                    <form method="GET" action="{{ route('dashboardFPB') }}" class="d-flex align-items-center flex-wrap gap-2">
                        <input type="hidden" name="filter_type" value="fpb">
                        
                        <div style="max-width: 150px;">
                            <input type="date" name="start_date_fpb" id="start_date_fpb" class="form-control form-control-sm"
                                value="{{ request('start_date_fpb', '2024-01-01') }}" placeholder="Dari">
                        </div>

                        <div style="max-width: 150px;">
                            <input type="date" name="end_date_fpb" id="end_date_fpb" class="form-control form-control-sm"
                                value="{{ request('end_date_fpb', '2025-12-31') }}" placeholder="Sampai">
                        </div>

                        <div style="max-width: 150px;">
                            <select name="kategori_po" id="kategori_po" class="form-control form-control-sm">
                                <option value="">Semua Kategori</option>
                                @foreach($kategoriList as $kategoriItem)
                                    <option value="{{ $kategoriItem }}" {{ request('kategori_po') == $kategoriItem ? 'selected' : '' }}>
                                        {{ $kategoriItem }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="start_date_leadtime" value="{{ request('start_date_leadtime') }}">
                        <input type="hidden" name="end_date_leadtime" value="{{ request('end_date_leadtime') }}">

                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    </form>

                    <div class="card p-2 bg-light text-dark mt-2 mt-sm-0">
                        <strong>Total: {{ $totalFPB }}</strong>
                    </div>
                </div>

                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <p><strong>Periode:</strong>
                            @if(request('start_date_fpb') && request('end_date_fpb'))
                                {{ \Carbon\Carbon::parse(request('start_date_fpb'))->format('d M Y') }} s/d
                                {{ \Carbon\Carbon::parse(request('end_date_fpb'))->format('d M Y') }}
                            @else
                                Semua Tanggal
                            @endif
                        </p>
                        <p><strong>Kategori:</strong>
                            {{ request('kategori_po') ?: 'Semua Kategori' }}
                        </p>
                    </div>

                    <figure class="highcharts-figure" style="height: 100%;">
                        <div id="chart-status-fpb" style="height: 400px; margin: 0 auto;"></div>
                    </figure>
                </div>
            </div>
        </div>

        {{-- Bagian Pie Chart --}}
        <div class="col-sm-4 h-100 d-flex flex-column">
            <div class="card flex-fill mb-2">
                <div class="card-header">
                    <h6 class="mb-0">Status FPB</h6>
                </div>
                <div class="card-body p-2">
                    <div id="pieChart" style="height: 250px;"></div>
                </div>
            </div>

            <div class="card flex-fill mt-2">
                <div class="card-header">
                    <h6 class="mb-0">Status Inquiry</h6>
                </div>
                <div class="card-body p-2">
                    <div id="pieChart1" style="height: 250px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

                <!-- Slide 2: Leadtime + Form Inquiry -->
                <div class="carousel-item h-100">
                    <div class="row h-100">
                        <div class="col-sm-6 h-100">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4>Leadtime Order Fulfillment</h4>
                                    <form method="GET" action="{{ route('dashboardFPB') }}" class="d-flex align-items-center" style="gap: 10px;">
                                        <input type="hidden" name="filter_type" value="leadtime">
                                        <div style="max-width: 150px;">
                                            <label for="start_date_leadtime" class="form-label sr-only">Dari</label>
                                            <input type="date" name="start_date_leadtime" id="start_date_leadtime" class="form-control form-control-sm" value="{{ request('start_date_leadtime') }}" aria-label="Tanggal mulai lead time">
                                        </div>
                                        <div style="max-width: 150px;">
                                            <label for="end_date_leadtime" class="form-label sr-only">Sampai</label>
                                            <input type="date" name="end_date_leadtime" id="end_date_leadtime" class="form-control form-control-sm" value="{{ request('end_date_leadtime') }}" aria-label="Tanggal akhir lead time">
                                        </div>
                                        <input type="hidden" name="start_date_fpb" value="{{ request('start_date_fpb') }}">
                                        <input type="hidden" name="end_date_fpb" value="{{ request('end_date_fpb') }}">
                                        <input type="hidden" name="kategori_po" value="{{ request('kategori_po') }}">
                                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                    </form>
                                </div>
                                <div class="card-body h-100">
                                    <div class="alert alert-info">
                                        <p><strong>Periode Lead Time:</strong> 
                                            @if(request('start_date_leadtime') && request('end_date_leadtime'))
                                                {{ \Carbon\Carbon::parse(request('start_date_leadtime'))->format('d M Y') }} 
                                                s/d 
                                                {{ \Carbon\Carbon::parse(request('end_date_leadtime'))->format('d M Y') }}
                                            @else
                                                Semua Tanggal
                                            @endif
                                        </p>
                                    </div>
                                    <figure class="highcharts-figure h-100">
                                        <div id="chart-lead-time" style="height: 100%; margin: 0 auto;"></div>
                                    </figure>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 h-100">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4>Form Inquiry Local</h4>
                                    <div class="card p-2 bg-light text-dark">
                                        <strong>Total: {{ $totalinquiry }}</strong>
                                    </div>
                                    <form method="GET" action="{{ route('dashboardFPB') }}" class="d-flex align-items-center" style="gap: 10px;">
                                        <input type="hidden" name="filter_type" value="inquiry">
                                        <div style="max-width: 150px;">
                                            <label for="start_date_inquiry" class="form-label sr-only">Dari</label>
                                            <input type="date" name="start_date_inquiry" id="start_date_inquiry" class="form-control form-control-sm" value="{{ request('start_date_inquiry') }}" aria-label="Tanggal mulai inquiry">
                                        </div>
                                        <div style="max-width: 150px;">
                                            <label for="end_date_inquiry" class="form-label sr-only">Sampai</label>
                                            <input type="date" name="end_date_inquiry" id="end_date_inquiry" class="form-control form-control-sm" value="{{ request('end_date_inquiry') }}" aria-label="Tanggal akhir inquiry">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                    </form>
                                </div>
                                <div class="card-body h-100">
                                    <figure class="highcharts-figure h-100">
                                        <div id="chart-status-inquiry" style="height: 100%; margin: 0 auto;"></div>
                                    </figure>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Slide 3: Dashboard CRP -->
                <div class="carousel-item h-100">
                    <div class="container-fluid h-100 d-flex flex-column">
                        <div class="row flex-grow-1 g-2 mb-2">
                            <div class="col-lg-6 h-100">
                                <div class="card h-100 d-flex flex-column">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">Dashboard CRP</h4>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-3 gap-2">
                                            <div class="w-100">
                                                <label for="category-filter" class="form-label small mb-1"><strong>Filter Kategori:</strong></label>
                                                <select id="category-filter" class="form-select form-select-sm" onchange="filterChart()" aria-label="Filter kategori untuk chart Actual vs Plan">
                                                    <option value="all" selected>All Categories (Total)</option>
                                                    @foreach ($allCategories as $category)
                                                        <option value="{{ $category }}">{{ $category }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="card flex-grow-1">
                                            <div class="card-header py-2">
                                                <h5 class="mb-0">Actual dan Plan</h5>
                                            </div>
                                            <div class="card-body p-2 h-100">
                                                <div id="chart-crp" style="height: 100%; min-height: 250px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 h-100">
                                <div class="card h-100 d-flex flex-column">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">Grand Total CRP</h4>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center mb-3 gap-2">
                                            <div class="w-100">
                                                <label for="category-grandtot-filter" class="form-label small mb-1"><strong>Filter Kategori:</strong></label>
                                                <select id="category-grandtot-filter" class="form-select form-select-sm" onchange="updateGrandTotChart()" aria-label="Filter kategori untuk chart Grand Total">
                                                    <option value="Total" selected>All Categories (Total)</option>
                                                    @foreach ($allCategories as $category)
                                                        <option value="{{ $category }}">{{ $category }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="card flex-grow-1">
                                            <div class="card-header py-2">
                                                <h5 class="mb-0">Perbandingan Grand Total Plan vs Actual</h5>
                                            </div>
                                            <div class="card-body p-2 h-100">
                                                <div id="grandtot-chart" style="height: 100%; min-height: 250px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item h-100">
                    <div class="container-fluid h-100 d-flex flex-column">
                        <div class="row flex-grow-1 g-2">
                            <div class="col-12 h-100">
                                <div class="card h-100 d-flex flex-column">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">Cumulative CRP</h4>
                                        <div class="w-50">
                                            <select id="category-filter-cumulative" class="form-select form-select-sm" onchange="updateCategory()" aria-label="Filter kategori untuk chart kumulatif">
                                                <option value="Total" {{ $selectedCategory == 'Total' ? 'selected' : '' }}>All Categories (Total)</option>
                                                @foreach ($allCategories as $category)
                                                    <option value="{{ $category }}" {{ $selectedCategory == $category ? 'selected' : '' }}>{{ $category }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="card-body p-2 flex-grow-1">
                                        <div class="card h-100">
                                            <div class="card-header py-2">
                                                <h5 class="mb-0">Perbandingan Kumulatif Bulanan Plan vs Actual</h5>
                                            </div>
                                            <div class="card-body p-2 h-100">
                                                <div id="chart-cumulative-crp" style="height: 100%; min-height: 300px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- Dependensi -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            $(document).ready(function() {
                // Hover function for dropdowns
                $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });
        </script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.getElementById('dashboardCarousel');
    if (carousel) {
        let isDragging = false;
        let startX;

        const startDrag = function(e) {
            isDragging = true;
            startX = e.pageX || (e.touches && e.touches[0].pageX);
            carousel.classList.add('dragging');
            carousel.style.cursor = 'grabbing';
        };

        const moveDrag = function(e) {
            if (!isDragging) return;
            const currentX = e.pageX || (e.touches && e.touches[0].pageX);
            const diff = currentX - startX;
            if (Math.abs(diff) > 50) {
                const bsCarousel = bootstrap.Carousel.getInstance(carousel);
                if (diff > 0) {
                    bsCarousel.prev();
                } else {
                    bsCarousel.next();
                }
                endDrag();
            }
        };

        const endDrag = function() {
            isDragging = false;
            carousel.classList.remove('dragging');
            carousel.style.cursor = 'grab';
        };

        carousel.addEventListener('mousedown', startDrag);
        carousel.addEventListener('mousemove', moveDrag);
        carousel.addEventListener('mouseup', endDrag);
        carousel.addEventListener('mouseleave', endDrag);
        carousel.addEventListener('touchstart', startDrag);
        carousel.addEventListener('touchmove', moveDrag);
        carousel.addEventListener('touchend', endDrag);
    }

    // Data dari controller
    const bulanList = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const actuals = @json($monthlyActuals);
    const plans = @json($monthlyPlans);
    const grandTotalData = @json($grandTotalComparison);
    const allMonthlyData = @json($allMonthlyData);
    const monthlyPlanData = @json($monthlyPlanData);
    const monthlyActualData = @json($monthlyActualData);
    const monthlyData = @json($monthlyData);
    const startDate = @json($startDate1);
    const endDate = @json($endDate1);
    const leadTimeData = @json($leadTimeData);
    const monthlyData1 = @json($monthlyData1);
    const $startDate1 = @json($startDate1);
    const $endDate1 = @json($endDate1);

    // Debugging data
    // console.log('actuals[Total]:', actuals['Total']);
    // console.log('plans[Total]:', plans['Total']);
    // console.log('grandTotalData[Total]:', grandTotalData['Total']);
    // console.log('allMonthlyData[Total]:', allMonthlyData['Total']);
    // console.log('monthlyData:', monthlyData);
    // console.log('leadTimeData:', leadTimeData);
    // console.log('monthlyData1:', monthlyData1);

    // Inisialisasi chart
    let chart = null;
    let grandTotChart = null;
    let chartCumulativeCrp = null;

    // Fungsi untuk chart-crp (Actual vs Plan bulanan)
    function getChartOptions(category) {
        const actualData = actuals[category] ?? [];
        const planData = plans[category] ?? [];
        console.log(`Data untuk ${category} - Actual:`, actualData);
        console.log(`Data untuk ${category} - Plan:`, planData);
        if (!actualData.length || !planData.length) {
            console.warn(`Data untuk kategori ${category} tidak tersedia di chart-crp`);
        }
        return {
            chart: { zoomType: 'xy' },
            title: { text: `CRP Actual vs Plan - ${category}` },
            xAxis: [{ categories: bulanList, crosshair: true }],
            yAxis: [{ title: { text: 'Nilai CRP (Rp)' } }],
            tooltip: { shared: true, valueDecimals: 2 },
            series: [{
                name: 'Actual',
                type: 'column',
                data: actualData,
                color: '#f45b5b',
            }, {
                name: 'Plan (Target)',
                type: 'spline',
                data: planData,
                color: '#7cb5ec',
            }],
            credits: { enabled: false }
        };
    }

    function filterChart() {
        const selectElement = document.getElementById('category-filter');
        if (!selectElement) {
            console.error('Elemen category-filter tidak ditemukan');
            return;
        }
        const selected = selectElement.value;
        console.log('Filter chart-crp dipilih:', selected);
        const options = getChartOptions(selected);

        if (chart) {
            chart.update(options, true, true);
        } else {
            console.error('Chart CRP belum diinisialisasi, menginisialisasi sekarang');
            chart = Highcharts.chart('chart-crp', options);
        }
    }

    // Fungsi untuk grandtot-chart (Grand Total Plan vs Actual)
    function renderGrandTotChart(category) {
        const data = grandTotalData[category];
        if (!data) {
            console.error(`Data grand total untuk kategori ${category} tidak ditemukan`);
            return;
        }
        const chartData = [
            { name: 'Plan', data: [data.Plan], color: '#7cb5ec' },
            { name: 'Actual', data: [data.Actual], color: '#f45b5b' }
        ];
        if (grandTotChart) {
            grandTotChart.series[0].setData(chartData[0].data);
            grandTotChart.series[1].setData(chartData[1].data);
            grandTotChart.setTitle({ text: `Grand Total CRP - ${category}` });
        } else {
            grandTotChart = Highcharts.chart('grandtot-chart', {
                chart: { type: 'column' },
                title: { text: `Grand Total CRP - ${category}` },
                xAxis: { categories: ['Grand Total'] },
                yAxis: { min: 0, title: { text: 'Total Value (Rp)' } },
                tooltip: { valueDecimals: 2 },
                series: chartData,
                credits: { enabled: false }
            });
        }
    }

    function updateGrandTotChart() {
        const selected = document.getElementById('category-grandtot-filter')?.value;
        if (!selected) {
            console.error('Elemen category-grandtot-filter tidak ditemukan');
            return;
        }
        console.log('Filter grandtot-chart dipilih:', selected);
        renderGrandTotChart(selected);
    }

    // Fungsi untuk chart-cumulative-crp (Kumulatif Plan vs Actual)
    function renderChart(category) {
        const planData = allMonthlyData[category]?.plan || monthlyPlanData;
        const actualData = allMonthlyData[category]?.actual || monthlyActualData;
        if (!planData.length || !actualData.length) {
            console.error(`Data kumulatif untuk kategori ${category} tidak ditemukan`);
            return;
        }
        if (chartCumulativeCrp) {
            chartCumulativeCrp.series[0].setData(planData);
            chartCumulativeCrp.series[1].setData(actualData);
            chartCumulativeCrp.setTitle({ text: `Kumulatif Bulanan Plan vs Actual - ${category}` });
        } else {
            chartCumulativeCrp = Highcharts.chart('chart-cumulative-crp', {
                chart: { type: 'column' },
                title: { text: `Kumulatif Bulanan Plan vs Actual - ${category}` },
                xAxis: { categories: bulanList, crosshair: true },
                yAxis: { min: 0, title: { text: 'Nilai Kumulatif (Rp)' } },
                tooltip: { shared: true, valueDecimals: 2 },
                series: [{
                    name: 'Plan',
                    data: planData,
                    color: '#7cb5ec'
                }, {
                    name: 'Actual',
                    data: actualData,
                    color: '#f45b5b'
                }],
                credits: { enabled: false }
            });
        }
    }

    function updateCategory() {
        const selected = document.getElementById('category-filter-cumulative').value;
        console.log('Filter Cumulative dipilih:', selected);
        const planData = allMonthlyData[selected]?.plan || [];
        const actualData = allMonthlyData[selected]?.actual || [];
        if (planData.length === 0 || actualData.length === 0) {
            console.error(`Data Cumulative tidak tersedia untuk kategori: ${selected}`);
            document.getElementById('chart-cumulative-crp').innerHTML =
                `<div class="alert alert-danger m-2">Data Cumulative tidak tersedia untuk kategori ${selected}</div>`;
            return;
        }
        const options = {
            title: { text: `Kumulatif Bulanan Plan vs Actual - ${selected}` },
            series: [{
                name: 'Plan',
                data: planData,
                color: '#7cb5ec'
            }, {
                name: 'Actual',
                data: actualData,
                color: '#f45b5b'
            }]
        };
        if (chartCumulativeCrp) {
            chartCumulativeCrp.update(options);
        } else {
            chartCumulativeCrp = Highcharts.chart('chart-cumulative-crp', {
                chart: { type: 'column' },
                title: options.title,
                xAxis: { categories: bulanList, crosshair: true },
                yAxis: { min: 0, title: { text: 'Nilai Kumulatif (Rp)' }},
                tooltip: { shared: true, valueDecimals: 2 },
                series: options.series,
                credits: { enabled: false }
            });
        }
    }

    document.getElementById('category-filter').addEventListener('change', filterChart);
    document.getElementById('category-grandtot-filter').addEventListener('change', updateGrandTotChart);
    document.getElementById('category-filter-cumulative').addEventListener('change', updateCategory);

   function renderChartStatusFpb() {
    const monthlyData = @json($monthlyData);
    const startDate = @json($startDate1);
    const endDate = @json($endDate1);
    const fpbCategoryBreakdown = @json($fpbCategoryBreakdown);
    const inquiryStatusBreakdown = @json($inquiryStatusBreakdown); // sekarang ini berasal dari FPB, bukan inquiry

    const allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const start = startDate ? new Date(startDate) : null;
    const end = endDate ? new Date(endDate) : null;

    let filteredMonths = [];
    let openData = [];
    let finishData = [];

    if (!start || !end) {
        const allYears = Object.keys(monthlyData.open_by_year).sort((a, b) => a - b);
        for (const year of allYears) {
            for (let month = 0; month < 12; month++) {
                const label = `${allMonths[month]} ${year}`;
                filteredMonths.push(label);
                openData.push(monthlyData.open_by_year[year]?.[month] || 0);
                finishData.push(monthlyData.finish_by_year[year]?.[month] || 0);
            }
        }
    } else {
        let startYear = start.getFullYear();
        let startMonth = start.getMonth();
        let endYear = end.getFullYear();
        let endMonth = end.getMonth();

        for (let year = startYear; year <= endYear; year++) {
            let monthStart = (year === startYear) ? startMonth : 0;
            let monthEnd = (year === endYear) ? endMonth : 11;

            for (let month = monthStart; month <= monthEnd; month++) {
                const label = `${allMonths[month]} ${year}`;
                filteredMonths.push(label);
                openData.push(monthlyData.open_by_year[year]?.[month] || 0);
                finishData.push(monthlyData.finish_by_year[year]?.[month] || 0);
            }
        }
    }

    // Column Chart (FPB)
    Highcharts.chart('chart-status-fpb', {
        chart: { type: 'column' },
        title: { align: 'center', text: 'Form Pengajuan Barang (FPB)' },
        xAxis: { categories: filteredMonths },
        yAxis: {
            allowDecimals: false,
            min: 0,
            title: { text: 'Jumlah FPB' }
        },
        tooltip: {
            formatter: function () {
                return `<b>${this.series.name}</b><br>Jumlah: ${this.point.y}`;
            }
        },
        plotOptions: {
            column: {
                dataLabels: {
                    enabled: true,
                    formatter: function () {
                        return `${this.y} FPB`;
                    },
                    style: {
                        fontWeight: 'bold',
                        color: 'black',
                        textOutline: 'none'
                    }
                }
            }
        },
        colors: ['#f1c40f', '#3498db'],
        series: [
            { name: 'Open', data: openData },
            { name: 'Finish', data: finishData }
        ],
        credits: { enabled: false }
    });

    // Pie Chart 1: FPB kategori Consumable
    const consumableOpen = fpbCategoryBreakdown?.Consumable?.open || 0;
    const consumableFinish = fpbCategoryBreakdown?.Consumable?.finish || 0;
    const totalConsumable = consumableOpen + consumableFinish;

    Highcharts.chart('pieChart', {
        chart: { type: 'pie' },
        title: { text: 'Non Material' },
        tooltip: {
            pointFormat: '<b>{point.percentage:.1f}%</b> ({point.count})'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '{point.name}: {point.percentage:.1f}% ({point.count})',
                    style: {
                        fontWeight: 'bold',
                        color: 'black',
                        textOutline: 'none'
                    }
                }
            }
        },
        series: [{
            name: 'Status',
            colorByPoint: true,
            data: [
                {
                    name: 'Open',
                    y: totalConsumable ? (consumableOpen / totalConsumable) * 100 : 0,
                    count: consumableOpen
                },
                {
                    name: 'Finish',
                    y: totalConsumable ? (consumableFinish / totalConsumable) * 100 : 0,
                    count: consumableFinish
                }
            ]
        }],
        credits: { enabled: false },
        colors: ['#f1c40f', '#3498db']
    });

    // Pie Chart 2: Status berdasarkan FPB (bukan inquiry lagi)
    const openCount = inquiryStatusBreakdown?.open || 0;
    const onProgressCount = inquiryStatusBreakdown?.onprogress || 0;
    const finishCount = inquiryStatusBreakdown?.finish || 0;
    const totalFPBStatus = openCount + onProgressCount + finishCount;

    const pieChartContainer = document.getElementById('pieChart1');
    if (!pieChartContainer) return;

    if (totalFPBStatus === 0) {
        pieChartContainer.innerHTML = '<div class="text-center text-muted">Tidak ada data FPB status.</div>';
        return;
    }

    Highcharts.chart('pieChart1', {
        chart: { type: 'pie' },
        title: { text: 'Status FPB' },
        tooltip: {
            formatter: function () {
                return `<b>${this.point.name}</b>: ${this.percentage.toFixed(1)}% (${this.point.custom.count})`;
            }
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    formatter: function () {
                        return `${this.point.name}: ${this.percentage.toFixed(1)}% (${this.point.custom.count})`;
                    },
                    style: {
                        fontWeight: 'bold',
                        color: 'black',
                        textOutline: 'none'
                    }
                }
            }
        },
        series: [{
            name: 'Status',
            colorByPoint: true,
            data: [
                {
                    name: 'Open',
                    y: openCount,
                    custom: { count: openCount }
                },
                {
                    name: 'On Progress',
                    y: onProgressCount,
                    custom: { count: onProgressCount }
                },
                {
                    name: 'Finish',
                    y: finishCount,
                    custom: { count: finishCount }
                }
            ]
        }],
        credits: { enabled: false },
        colors: ['#f1c40f', '#f39c12', '#3498db']
    });
}



    // Chart Lead Time
    function renderChartFpbAndInquiry() {
        
    // === Bagian Lead Time FPB (Highcharts) ===
    const categories = ['Total', 'IT', 'Spareparts', 'Consumable', 'GA', 'Subcont'];
    const targetCategories = ['IT', 'Spareparts', 'Consumable'];

    const leadDaysFirst = categories.map(cat => leadTimeData[cat]?.average_lead_days_first || 0);
    const leadDaysSecond = categories.map(cat => leadTimeData[cat]?.average_lead_days_second || 0);
    const totalLeadDays = leadDaysFirst.map((val, i) => val + leadDaysSecond[i]);

    const avgFirst = leadDaysFirst.slice(1).reduce((sum, v) => sum + v, 0) / (categories.length - 1);
    const avgSecond = leadDaysSecond.slice(1).reduce((sum, v) => sum + v, 0) / (categories.length - 1);

    leadDaysFirst[0] = Math.round(avgFirst);
    leadDaysSecond[0] = Math.round(avgSecond);
    totalLeadDays[0] = Math.round(avgFirst + avgSecond);

    const targetData = categories.map(cat => targetCategories.includes(cat) ? 5 : null);

    const fpbFirst = categories.map(cat => leadTimeData[cat]?.submit_confirm || 0);
    const fpbSecond = categories.map(cat => leadTimeData[cat]?.confirm_finish || 0);

    fpbFirst[0] = fpbFirst.slice(1).reduce((sum, val) => sum + val, 0);
    fpbSecond[0] = fpbSecond.slice(1).reduce((sum, val) => sum + val, 0);

    Highcharts.chart('chart-lead-time', {
        chart: { type: 'column' },
        title: { text: 'Non Material FPB (Lead Time)' },
        xAxis: { categories: categories },
        yAxis: {
            title: { text: 'Total Lead Time (Hari)' },
            min: 0,
            labels: { format: '{value} Hari' }
        },
        tooltip: {
            shared: true,
            useHTML: true,
            formatter: function () {
                const i = this.points[0].point.index;
                return `
                    <b>${categories[i]}</b><br>
                    Submit - Confirm: <b>${leadDaysFirst[i]} hari</b> (${fpbFirst[i]} FPB)<br>
                    Confirm - Finish: <b>${leadDaysSecond[i]} hari</b> (${fpbSecond[i]} FPB)<br>
                    Total: <b>${totalLeadDays[i]} hari</b>
                `;
            }
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                    formatter: function () {
                        const idx = this.point.index;
                        const fpbCount = this.series.name === 'Submit - Confirm'
                            ? fpbFirst[idx]
                            : fpbSecond[idx];
                        return `${Math.round(this.y)} hari<br>(${fpbCount} FPB)`;
                    },
                    style: {
                        fontWeight: 'bold',
                        color: 'black',
                        textOutline: 'none'
                    }
                }
            },
            line: {
                dashStyle: 'Solid',
                marker: { enabled: false }
            }
        },
        series: [
            {
                name: 'Confirm - Finish',
                data: leadDaysSecond,
                color: '#3498db'
            },
            {
                name: 'Submit - Confirm',
                data: leadDaysFirst,
                color: '#f1c40f'
            },
            {
                name: 'Target',
                type: 'line',
                data: targetData,
                color: 'red',
                lineWidth: 2,
                tooltip: { pointFormat: '' }
            }
        ],
        credits: { enabled: false }
    });

    // === Bagian Inquiry Status (Chart.js) ===
    const monthlyData = monthlyData1;
    const startDate = $startDate1;
    const endDate = $endDate1;

    const allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const start = startDate ? new Date(startDate) : null;
    const end = endDate ? new Date(endDate) : null;

    let filteredMonths = [];
    let openData = [];
    let onProgressData = [];
    let finishData = [];

    if (!start && !end) {
        for (let i = 0; i < 12; i++) {
            filteredMonths.push(allMonths[i]);
            openData.push(monthlyData.open[i] ?? 0);
            onProgressData.push(monthlyData.onprogress[i] ?? 0);
            finishData.push(monthlyData.finish[i] ?? 0);
        }
    } else {
        const startYear = start?.getFullYear() ?? new Date().getFullYear();
        const endYear = end?.getFullYear() ?? new Date().getFullYear();

        for (let year = startYear; year <= endYear; year++) {
            const startMonth = (year === startYear) ? start.getMonth() : 0;
            const endMonth = (year === endYear) ? end.getMonth() : 11;

            for (let month = startMonth; month <= endMonth; month++) {
                filteredMonths.push(`${allMonths[month]} ${year}`);
                openData.push(monthlyData.open[month] ?? 0);
                onProgressData.push(monthlyData.onprogress[month] ?? 0);
                finishData.push(monthlyData.finish[month] ?? 0);
            }
        }
    }

    Highcharts.chart('chart-status-inquiry', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Form Inquiry Local'
        },
        xAxis: {
            categories: filteredMonths,
            title: {
                text: 'Bulan'
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Jumlah Inquiry'
            }
        },
        tooltip: {
            shared: true,
            valueSuffix: ' Inquiry'
        },
        plotOptions: {
            column: {
                grouping: true,       // ✅ Default true, pastikan ini true
                pointPadding: 0.1,    // ✅ Lebar antar bar
                borderWidth: 0,
                dataLabels: {
                    enabled: true,
                    style: {
                        fontWeight: 'bold'
                    }
                }
            }
        },
        series: [
            {
                name: 'Open',
                data: openData,
                color: '#3498db'
            },
            {
                name: 'On Progress',
                data: onProgressData,
                color: '#e67e22'
            },
            {
                name: 'Finish',
                data: finishData,
                color: '#2ecc71'
            }
        ],
        credits: {
            enabled: false
        }
    });


}


    // Grafik Pie
    

    // Inisialisasi semua chart
    try {
        const chartElements = [
            'chart-crp', 'grandtot-chart', 'chart-cumulative-crp',
            'chart-status-fpb', 'pieChart', 'chart-lead-time',
            'chart-status-inquiry', 'pieChart1'
        ];
        for (const id of chartElements) {
            if (!document.getElementById(id)) {
                console.error(`Elemen chart ${id} tidak ditemukan di DOM`);
                return;
            }
        }
        // Inisialisasi chart CRP
        if (actuals['Total'] && plans['Total']) {
            chart = Highcharts.chart('chart-crp', getChartOptions('Total'));
        } else {
            console.error('Data Total untuk chart-crp tidak tersedia');
        }
        if (grandTotalData['Total']) {
            renderGrandTotChart('Total');
        } else {
            console.error('Data Total untuk grandtot-chart tidak tersedia');
        }
        if (allMonthlyData['Total']) {
            renderChart('Total');
        } else {
            console.error('Data Total untuk chart-cumulative-crp tidak tersedia');
        }
        // Inisialisasi chart FPB, Lead Time, dan Inquiry
        renderChartStatusFpb();
        renderPieChart();
        
        renderChartFpbAndInquiry();
        renderPieChart1();
        // Pastikan filter CRP diatur ke Total
        const selectElement = document.getElementById('category-filter');
        if (selectElement && selectElement.value !== 'Total') {
            console.warn('Filter chart-crp tidak diatur ke Total, mengatur ulang');
            selectElement.value = 'Total';
            filterChart();
        }
        const selectCumulative = document.getElementById('category-filter-cumulative');
        if (selectCumulative && selectCumulative.value !== 'Total') {
            console.warn('Filter chart-cumulative-crp tidak diatur ke Total, mengatur ulang');
            selectCumulative.value = 'Total';
            updateCategory();
        }
    } catch (error) {
        console.error('Error inisialisasi chart:', error);
    }

    // Resize chart saat slide carousel berubah
    carousel.addEventListener('slid.bs.carousel', function (e) {
    const activeSlide = e.relatedTarget || carousel.querySelector('.carousel-item.active');

    if (activeSlide) {
        if (activeSlide.querySelector('#chart-crp') && window.chart) chart.reflow();
        if (activeSlide.querySelector('#grandtot-chart') && window.grandTotChart) grandTotChart.reflow();
        if (activeSlide.querySelector('#chart-cumulative-crp') && window.chartCumulativeCrp) chartCumulativeCrp.reflow();
        if (
            activeSlide.querySelector('#chart-lead-time') ||
            activeSlide.querySelector('#chart-status-inquiry')
        ) {
            setTimeout(() => {
                renderChartFpbAndInquiry();
            }, 200);
        }
    }
});


});
</script>


</main>
@endsection
