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
        /* Optional: Placeholder for charts while loading */
        .chart-placeholder {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: #6c757d; /* Bootstrap muted text color */
        }
    </style>
    <section class="section dashboard dashboard-container">
         <div id="dashboardCarousel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel" data-bs-interval="10000"> {{-- Interval false untuk kontrol manual --}}
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="0" class="active" data-slide-id="slide1"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="1" data-slide-id="slide2"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="2" data-slide-id="slide3"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="3" data-slide-id="slide4"></button>
            </div>
            <div class="carousel-inner h-100">
                <!-- Slide 1: Form Pengajuan Barang + 2 Pie Chart -->
                <div class="carousel-item active h-100" data-slide-id="slide1">
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
                                                value="{{ request('start_date_fpb', now()->subYear()->startOfYear()->format('Y-m-d')) }}" placeholder="Dari">
                                        </div>
                                        <div style="max-width: 150px;">
                                            <input type="date" name="end_date_fpb" id="end_date_fpb" class="form-control form-control-sm"
                                                value="{{ request('end_date_fpb', now()->format('Y-m-d')) }}" placeholder="Sampai">
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
                                        <strong>Total FPB: <span id="total-fpb-display">{{ $totalFPB ?? 'Memuat...' }}</span></strong>
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
                                        <div id="chart-status-fpb-placeholder" class="chart-placeholder">Memuat chart...</div>
                                        <div id="chart-status-fpb" style="height: 400px; margin: 0 auto; display: none;"></div>
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
                                    <div id="pieChart-placeholder" class="chart-placeholder">Memuat chart...</div>
                                    <div id="pieChart" style="height: 250px; display: none;"></div>
                                </div>
                            </div>
                            <div class="card flex-fill mt-2">
                                <div class="card-header">
                                    <h6 class="mb-0">Status Inquiry</h6> {{-- Note: This is actually FPB Status based on controller data --}}
                                </div>
                                <div class="card-body p-2">
                                    <div id="pieChart1-placeholder" class="chart-placeholder">Memuat chart...</div>
                                    <div id="pieChart1" style="height: 250px; display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Slide 2: Leadtime + Form Inquiry -->
                <div class="carousel-item h-100" data-slide-id="slide2">
                    <div class="row h-100">
                        <div class="col-sm-6 h-100">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4>Leadtime Order Fulfillment</h4>
                                    <form method="GET" action="{{ route('dashboardFPB') }}" class="d-flex align-items-center" style="gap: 10px;">
                                        <input type="hidden" name="filter_type" value="leadtime">
                                        <div style="max-width: 150px;">
                                            <label for="start_date_leadtime" class="form-label sr-only">Dari</label>
                                            <input type="date" name="start_date_leadtime" id="start_date_leadtime" class="form-control form-control-sm" value="{{ request('start_date_leadtime', now()->subYear()->startOfYear()->format('Y-m-d')) }}" aria-label="Tanggal mulai lead time">
                                        </div>
                                        <div style="max-width: 150px;">
                                            <label for="end_date_leadtime" class="form-label sr-only">Sampai</label>
                                            <input type="date" name="end_date_leadtime" id="end_date_leadtime" class="form-control form-control-sm" value="{{ request('end_date_leadtime', now()->format('Y-m-d')) }}" aria-label="Tanggal akhir lead time">
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
                                        <div id="chart-lead-time-placeholder" class="chart-placeholder">Memuat chart...</div>
                                        <div id="chart-lead-time" style="height: 100%; margin: 0 auto; display: none;"></div>
                                    </figure>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 h-100">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4>Form Inquiry Local</h4>
                                    <div class="card p-2 bg-light text-dark">
                                        <strong>Total Inquiry: <span id="total-inquiry-display">{{ $totalinquiry ?? 'Memuat...' }}</span></strong>
                                    </div>
                                    <form method="GET" action="{{ route('dashboardFPB') }}" class="d-flex align-items-center" style="gap: 10px;">
                                        <input type="hidden" name="filter_type" value="inquiry">
                                        <div style="max-width: 150px;">
                                            <label for="start_date_inquiry" class="form-label sr-only">Dari</label>
                                            <input type="date" name="start_date_inquiry" id="start_date_inquiry" class="form-control form-control-sm" value="{{ request('start_date_inquiry', now()->subYear()->startOfYear()->format('Y-m-d')) }}" aria-label="Tanggal mulai inquiry">
                                        </div>
                                        <div style="max-width: 150px;">
                                            <label for="end_date_inquiry" class="form-label sr-only">Sampai</label>
                                            <input type="date" name="end_date_inquiry" id="end_date_inquiry" class="form-control form-control-sm" value="{{ request('end_date_inquiry', now()->format('Y-m-d')) }}" aria-label="Tanggal akhir inquiry">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                    </form>
                                </div>
                                <div class="card-body h-100">
                                    <figure class="highcharts-figure h-100">
                                        <div id="chart-status-inquiry-placeholder" class="chart-placeholder">Memuat chart...</div>
                                        <div id="chart-status-inquiry" style="height: 100%; margin: 0 auto; display: none;"></div>
                                    </figure>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Slide 3: Dashboard CRP -->
                <div class="carousel-item h-100" data-slide-id="slide3">
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
                                                <select id="category-filter" class="form-select form-select-sm" aria-label="Filter kategori untuk chart Actual vs Plan">
                                                    <option value="Total" selected>All Categories (Total)</option>
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
                                                <div id="chart-crp-placeholder" class="chart-placeholder">Memuat chart...</div>
                                                <div id="chart-crp" style="height: 100%; min-height: 250px; display: none;"></div>
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
                                                <select id="category-grandtot-filter" class="form-select form-select-sm" aria-label="Filter kategori untuk chart Grand Total">
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
                                                <div id="grandtot-chart-placeholder" class="chart-placeholder">Memuat chart...</div>
                                                <div id="grandtot-chart" style="height: 100%; min-height: 250px; display: none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item h-100" data-slide-id="slide4">
                    <div class="container-fluid h-100 d-flex flex-column">
                        <div class="row flex-grow-1 g-2">
                            <div class="col-12 h-100">
                                <div class="card h-100 d-flex flex-column">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">Cumulative CRP</h4>
                                        <div class="w-50">
                                            <select id="category-filter-cumulative" class="form-select form-select-sm" aria-label="Filter kategori untuk chart kumulatif">
                                                <option value="Total" selected>All Categories (Total)</option>
                                                @foreach ($allCategories as $category)
                                                    <option value="{{ $category }}">{{ $category }}</option>
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
                                                <div id="chart-cumulative-crp-placeholder" class="chart-placeholder">Memuat chart...</div>
                                                <div id="chart-cumulative-crp" style="height: 100%; min-height: 300px; display: none;"></div>
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

    <!-- Dependensi (pastikan urutan benar) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>

    <script>
        $(document).ready(function() {
            // Hover function for dropdowns (if used elsewhere)
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
        if (!carousel) return;

        // --- Dragging functionality ---
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
                if (bsCarousel) {
                    if (diff > 0) {
                        bsCarousel.prev();
                    } else {
                        bsCarousel.next();
                    }
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


        // Track loaded slides to prevent re-initialization
        const loadedSlides = new Set();

        // Chart instances (global scope for reflow)
        let chartStatusFpb = null;
        let pieChart = null;
        let pieChart1 = null;
        let chartLeadTime = null;
        let chartStatusInquiry = null;
        let chartCrp = null;
        let grandTotChart = null;
        let chartCumulativeCrp = null;

        // --- Chart Initialization Functions with AJAX ---

        // Slide 1: FPB Charts
        async function initSlide1Charts() {
            // HAPUS deklarasi `const data = result.data;` yang menyebabkan error
            if (loadedSlides.has('slide1')) return;
            console.log("Initializing Slide 1 Charts (AJAX)...");

            // Tampilkan placeholder
            document.getElementById('chart-status-fpb-placeholder').textContent = 'Memuat data FPB...';
            document.getElementById('pieChart-placeholder').textContent = 'Memuat data kategori...';
            document.getElementById('pieChart1-placeholder').textContent = 'Memuat data status...';

            try {
                // Ambil nilai filter saat ini dari form di slide 1
                const startDateFpb = document.getElementById('start_date_fpb')?.value || '';
                const endDateFpb = document.getElementById('end_date_fpb')?.value || '';
                const kategoriPo = document.getElementById('kategori_po')?.value || '';

                // Fetch data dari endpoint baru
                const response = await fetch('{{ route("api.slide1and2.data") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        start_date_fpb: startDateFpb,
                        end_date_fpb: endDateFpb,
                        kategori_po: kategoriPo,
                        // Tambahkan parameter filter lain jika diperlukan untuk slide 2
                        start_date_leadtime: document.getElementById('start_date_leadtime')?.value || '',
                        end_date_leadtime: document.getElementById('end_date_leadtime')?.value || '',
                        start_date_inquiry: document.getElementById('start_date_inquiry')?.value || '',
                        end_date_inquiry: document.getElementById('end_date_inquiry')?.value || '',
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Gagal memuat data slide 1 & 2');
                }

                const data = result.data; // <-- result didefinisikan di sini

                // --- Perbarui tampilan total ---
                document.getElementById('total-fpb-display').textContent = data.totalFPB ?? '0';
                // --- Akhir pembaruan tampilan ---

                // Sembunyikan placeholder, tampilkan chart containers
                document.getElementById('chart-status-fpb-placeholder').style.display = 'none';
                document.getElementById('chart-status-fpb').style.display = 'block';
                document.getElementById('pieChart-placeholder').style.display = 'none';
                document.getElementById('pieChart').style.display = 'block';
                document.getElementById('pieChart1-placeholder').style.display = 'none';
                document.getElementById('pieChart1').style.display = 'block';

                // --- Render Charts for Slide 1 using fetched data ---
                chartStatusFpb = Highcharts.chart('chart-status-fpb', {
                    chart: { type: 'column' },
                    title: { align: 'center', text: 'Form Pengajuan Barang (FPB)' },
                    xAxis: { categories: getFilteredMonths(data.startDate1, data.endDate1, data.monthlyData) },
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
                        {
                            name: 'Open',
                            data: getOpenData(data.startDate1, data.endDate1, data.monthlyData)
                        },
                        {
                            name: 'Finish',
                            data: getFinishData(data.startDate1, data.endDate1, data.monthlyData)
                        }
                    ],
                    credits: { enabled: false }
                });

                // Pie Chart 1: Status berdasarkan FPB (menggunakan data dari response)
                const openCount = data.inquiryStatusBreakdown?.open || 0;
                const onProgressCount = data.inquiryStatusBreakdown?.onprogress || 0;
                const finishCount = data.inquiryStatusBreakdown?.finish || 0;
                const totalFPBStatus = openCount + onProgressCount + finishCount;

                if (totalFPBStatus === 0) {
                    document.getElementById('pieChart1').innerHTML = '<div class="text-center text-muted">Tidak ada data FPB status.</div>';
                } else {
                     pieChart1 = Highcharts.chart('pieChart1', {
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

                loadedSlides.add('slide1');
            } catch (error) {
                console.error('Error fetching Slide 1 data:', error);
                // Tampilkan pesan error di placeholder
                document.getElementById('chart-status-fpb-placeholder').textContent = 'Gagal memuat data FPB: ' + error.message;
                document.getElementById('pieChart-placeholder').textContent = 'Gagal memuat data.';
                document.getElementById('pieChart1-placeholder').textContent = 'Gagal memuat data.';
                // Sembunyikan chart containers jika error
                document.getElementById('chart-status-fpb').style.display = 'none';
                document.getElementById('pieChart').style.display = 'none';
                document.getElementById('pieChart1').style.display = 'none';
            }
        }

        // Slide 2: Lead Time & Inquiry Charts
        async function initSlide2Charts() {
            // HAPUS deklarasi `const data = result.data;` yang menyebabkan error
            if (loadedSlides.has('slide2')) return;
            console.log("Initializing Slide 2 Charts (AJAX)...");

            // Tampilkan placeholder
            document.getElementById('chart-lead-time-placeholder').textContent = 'Memuat data Lead Time...';
            document.getElementById('chart-status-inquiry-placeholder').textContent = 'Memuat data Inquiry...';

            try {
                // Ambil nilai filter saat ini dari form di slide 2
                const startDateLeadtime = document.getElementById('start_date_leadtime')?.value || '';
                const endDateLeadtime = document.getElementById('end_date_leadtime')?.value || '';
                const startDateInquiry = document.getElementById('start_date_inquiry')?.value || '';
                const endDateInquiry = document.getElementById('end_date_inquiry')?.value || '';
                const startDateFpb = document.getElementById('start_date_fpb')?.value || '';
                const endDateFpb = document.getElementById('end_date_fpb')?.value || '';
                const kategoriPo = document.getElementById('kategori_po')?.value || '';

                // Fetch data dari endpoint yang sama (karena data slide 1 & 2 diambil bersama)
                const response = await fetch('{{ route("api.slide1and2.data") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        start_date_fpb: startDateFpb,
                        end_date_fpb: endDateFpb,
                        kategori_po: kategoriPo,
                        start_date_leadtime: startDateLeadtime,
                        end_date_leadtime: endDateLeadtime,
                        start_date_inquiry: startDateInquiry,
                        end_date_inquiry: endDateInquiry,
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Gagal memuat data slide 1 & 2');
                }

                const data = result.data; // <-- result didefinisikan di sini

                // --- Perbarui tampilan total ---
                document.getElementById('total-inquiry-display').textContent = data.totalinquiry ?? '0';
                // --- Akhir pembaruan tampilan ---

                // Sembunyikan placeholder, tampilkan chart containers
                document.getElementById('chart-lead-time-placeholder').style.display = 'none';
                document.getElementById('chart-lead-time').style.display = 'block';
                document.getElementById('chart-status-inquiry-placeholder').style.display = 'none';
                document.getElementById('chart-status-inquiry').style.display = 'block';

                // --- Render Charts for Slide 2 using fetched data ---
                // Lead Time Chart
                const categories = ['Total', 'IT', 'Spareparts', 'Consumable', 'GA', 'Subcont'];
                const targetCategories = ['IT', 'Spareparts', 'Consumable'];
                const leadDaysFirst = categories.map(cat => data.leadTimeData[cat]?.average_lead_days_first || 0);
                const leadDaysSecond = categories.map(cat => data.leadTimeData[cat]?.average_lead_days_second || 0);
                const totalLeadDays = leadDaysFirst.map((val, i) => val + leadDaysSecond[i]);
                const avgFirst = leadDaysFirst.slice(1).reduce((sum, v) => sum + v, 0) / (categories.length - 1 || 1);
                const avgSecond = leadDaysSecond.slice(1).reduce((sum, v) => sum + v, 0) / (categories.length - 1 || 1);
                leadDaysFirst[0] = Math.round(avgFirst);
                leadDaysSecond[0] = Math.round(avgSecond);
                totalLeadDays[0] = Math.round(avgFirst + avgSecond);
                const targetData = categories.map(cat => targetCategories.includes(cat) ? 5 : null);
                const fpbFirst = categories.map(cat => data.leadTimeData[cat]?.submit_confirm || 0);
                const fpbSecond = categories.map(cat => data.leadTimeData[cat]?.confirm_finish || 0);
                fpbFirst[0] = fpbFirst.slice(1).reduce((sum, val) => sum + val, 0);
                fpbSecond[0] = fpbSecond.slice(1).reduce((sum, val) => sum + val, 0);
                chartLeadTime = Highcharts.chart('chart-lead-time', {
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

                // Inquiry Status Chart
                chartStatusInquiry = Highcharts.chart('chart-status-inquiry', {
                    chart: { type: 'column' },
                    title: { text: 'Form Inquiry Local' },
                    xAxis: { categories: getFilteredMonthsInquiry(data.startDateInq, data.endDateInq, data.monthlyData1) },
                    yAxis: {
                        min: 0,
                        title: { text: 'Jumlah Inquiry' }
                    },
                    tooltip: {
                        shared: true,
                        valueSuffix: ' Inquiry'
                    },
                    plotOptions: {
                        column: {
                            grouping: true,
                            pointPadding: 0.1,
                            borderWidth: 0,
                            dataLabels: {
                                enabled: true,
                                style: { fontWeight: 'bold' }
                            }
                        }
                    },
                    series: [
                        {
                            name: 'Open',
                            data: getOpenDataInquiry(data.monthlyData1),
                            color: '#3498db'
                        },
                        {
                            name: 'On Progress',
                            data: getOnProgressDataInquiry(data.monthlyData1),
                            color: '#e67e22'
                        },
                        {
                            name: 'Finish',
                            data: getFinishDataInquiry(data.monthlyData1),
                            color: '#2ecc71'
                        }
                    ],
                    credits: { enabled: false }
                });

                loadedSlides.add('slide2');
            } catch (error) {
                console.error('Error fetching Slide 2 data:', error);
                // Tampilkan pesan error di placeholder
                document.getElementById('chart-lead-time-placeholder').textContent = 'Gagal memuat data Lead Time: ' + error.message;
                document.getElementById('chart-status-inquiry-placeholder').textContent = 'Gagal memuat data Inquiry: ' + error.message;
                // Sembunyikan chart containers jika error
                document.getElementById('chart-lead-time').style.display = 'none';
                document.getElementById('chart-status-inquiry').style.display = 'none';
            }
        }

        // Slide 3: CRP Charts (Part 1)
        async function initSlide3Charts() {
            if (loadedSlides.has('slide3')) return;
            console.log("Initializing Slide 3 Charts (AJAX)...");

            // Tampilkan placeholder
            document.getElementById('chart-crp-placeholder').textContent = 'Memuat data CRP Actual vs Plan...';
            document.getElementById('grandtot-chart-placeholder').textContent = 'Memuat data Grand Total CRP...';

            try {
                // Fetch data dari endpoint baru untuk CRP
                const response = await fetch('{{ route("api.slide3and4.data") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({}) // Kirim body kosong atau filter jika diperlukan
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Gagal memuat data slide 3 & 4');
                }

                const data = result.data;

                // Sembunyikan placeholder, tampilkan chart containers
                document.getElementById('chart-crp-placeholder').style.display = 'none';
                document.getElementById('chart-crp').style.display = 'block';
                document.getElementById('grandtot-chart-placeholder').style.display = 'none';
                document.getElementById('grandtot-chart').style.display = 'block';

                // --- Render Charts for Slide 3 using fetched data ---
                // CRP Actual vs Plan Chart
                const initialCategory = 'Total'; // Default category
                chartCrp = Highcharts.chart('chart-crp', getChartOptionsCrp(initialCategory, data));

                // Grand Total Chart
                renderGrandTotChart(initialCategory, data);

                // Add event listeners for filters (only once)
                if (!document.getElementById('category-filter').dataset.listenerAdded) {
                     document.getElementById('category-filter').addEventListener('change', function() {
                         const selected = this.value;
                         if (chartCrp) {
                             chartCrp.update(getChartOptionsCrp(selected, data), true, true);
                         }
                     });
                     document.getElementById('category-filter').dataset.listenerAdded = 'true';
                }
                if (!document.getElementById('category-grandtot-filter').dataset.listenerAdded) {
                     document.getElementById('category-grandtot-filter').addEventListener('change', function() {
                         const selected = this.value;
                         renderGrandTotChart(selected, data);
                     });
                     document.getElementById('category-grandtot-filter').dataset.listenerAdded = 'true';
                }

                loadedSlides.add('slide3');
            } catch (error) {
                console.error('Error fetching Slide 3 data:', error);
                // Tampilkan pesan error di placeholder
                document.getElementById('chart-crp-placeholder').textContent = 'Gagal memuat data CRP: ' + error.message;
                document.getElementById('grandtot-chart-placeholder').textContent = 'Gagal memuat data Grand Total: ' + error.message;
                // Sembunyikan chart containers jika error
                document.getElementById('chart-crp').style.display = 'none';
                document.getElementById('grandtot-chart').style.display = 'none';
            }
        }

        // Slide 4: CRP Cumulative Chart
        async function initSlide4Charts() {
            if (loadedSlides.has('slide4')) return;
            console.log("Initializing Slide 4 Charts (AJAX)...");

            // Tampilkan placeholder
            document.getElementById('chart-cumulative-crp-placeholder').textContent = 'Memuat data CRP Kumulatif...';

            try {
                // Fetch data dari endpoint baru untuk CRP (kita bisa gunakan data yang sama dengan slide 3)
                const response = await fetch('{{ route("api.slide3and4.data") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({})
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Gagal memuat data slide 3 & 4');
                }

                const data = result.data;

                // Sembunyikan placeholder, tampilkan chart containers
                document.getElementById('chart-cumulative-crp-placeholder').style.display = 'none';
                document.getElementById('chart-cumulative-crp').style.display = 'block';

                // --- Render Chart for Slide 4 using fetched data ---
                // Cumulative CRP Chart
                const initialCategoryCumulative = document.getElementById('category-filter-cumulative').value || 'Total';
                renderChartCumulative(initialCategoryCumulative, data);

                // Add event listener for filter (only once)
                if (!document.getElementById('category-filter-cumulative').dataset.listenerAdded) {
                     document.getElementById('category-filter-cumulative').addEventListener('change', function() {
                        const selected = this.value;
                        renderChartCumulative(selected, data);
                     });
                     document.getElementById('category-filter-cumulative').dataset.listenerAdded = 'true';
                }

                loadedSlides.add('slide4');
            } catch (error) {
                console.error('Error fetching Slide 4 data:', error);
                // Tampilkan pesan error di placeholder
                document.getElementById('chart-cumulative-crp-placeholder').textContent = 'Gagal memuat data CRP Kumulatif: ' + error.message;
                // Sembunyikan chart containers jika error
                document.getElementById('chart-cumulative-crp').style.display = 'none';
            }
        }

        // --- Helper Functions untuk menerima data dari AJAX ---
        function getFilteredMonths(startDate, endDate, monthlyData) {
            const allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                               'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const start = startDate ? new Date(startDate) : null;
            const end = endDate ? new Date(endDate) : null;
            let filteredMonths = [];
            if (!start || !end) {
                // Jika tidak ada filter, tampilkan 12 bulan statis
                for (let i = 0; i < 12; i++) {
                     filteredMonths.push(allMonths[i]);
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
                    }
                }
            }
            return filteredMonths;
        }

        function getOpenData(startDate, endDate, monthlyData) {
             const start = startDate ? new Date(startDate) : null;
             const end = endDate ? new Date(endDate) : null;
             let openData = [];
             if (!start || !end) {
                 // Jika tidak ada filter, gunakan data bulan statis
                 return Array.from({length: 12}, (_, i) => monthlyData.open[i] ?? 0);
             } else {
                 let startYear = start.getFullYear();
                 let startMonth = start.getMonth();
                 let endYear = end.getFullYear();
                 let endMonth = end.getMonth();
                 // Hitung jumlah bulan dalam rentang
                 let totalMonths = (endYear - startYear) * 12 + (endMonth - startMonth) + 1;
                 openData = Array(totalMonths).fill(0);
                 let index = 0;
                 for (let year = startYear; year <= endYear; year++) {
                     let monthStart = (year === startYear) ? startMonth : 0;
                     let monthEnd = (year === endYear) ? endMonth : 11;
                     for (let month = monthStart; month <= monthEnd; month++) {
                         openData[index++] = monthlyData.open[month] ?? 0;
                     }
                 }
             }
             return openData;
         }

        function getFinishData(startDate, endDate, monthlyData) {
             const start = startDate ? new Date(startDate) : null;
             const end = endDate ? new Date(endDate) : null;
             let finishData = [];
             if (!start || !end) {
                 // Jika tidak ada filter, gunakan data bulan statis
                 return Array.from({length: 12}, (_, i) => monthlyData.finish[i] ?? 0);
             } else {
                 let startYear = start.getFullYear();
                 let startMonth = start.getMonth();
                 let endYear = end.getFullYear();
                 let endMonth = end.getMonth();
                 // Hitung jumlah bulan dalam rentang
                 let totalMonths = (endYear - startYear) * 12 + (endMonth - startMonth) + 1;
                 finishData = Array(totalMonths).fill(0);
                 let index = 0;
                 for (let year = startYear; year <= endYear; year++) {
                     let monthStart = (year === startYear) ? startMonth : 0;
                     let monthEnd = (year === endYear) ? endMonth : 11;
                     for (let month = monthStart; month <= monthEnd; month++) {
                         finishData[index++] = monthlyData.finish[month] ?? 0;
                     }
                 }
             }
             return finishData;
         }

        function getFilteredMonthsInquiry(startDate, endDate, monthlyData1) {
            const allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                               'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const start = startDate ? new Date(startDate) : null;
            const end = endDate ? new Date(endDate) : null;
            let filteredMonths = [];
            if (!start || !end) {
                // Default 12 bulan
                for (let i = 0; i < 12; i++) {
                    filteredMonths.push(allMonths[i]);
                }
            } else {
                const startYear = start.getFullYear();
                const endYear = end.getFullYear();
                for (let year = startYear; year <= endYear; year++) {
                    const startMonth = (year === startYear) ? start.getMonth() : 0;
                    const endMonth = (year === endYear) ? end.getMonth() : 11;
                    for (let month = startMonth; month <= endMonth; month++) {
                        filteredMonths.push(`${allMonths[month]} ${year}`);
                    }
                }
            }
            return filteredMonths;
        }

        function getOpenDataInquiry(monthlyData1) {
            return Array.from({length: 12}, (_, i) => monthlyData1.open[i] ?? 0);
        }

        function getOnProgressDataInquiry(monthlyData1) {
            return Array.from({length: 12}, (_, i) => monthlyData1.onprogress[i] ?? 0);
        }

        function getFinishDataInquiry(monthlyData1) {
            return Array.from({length: 12}, (_, i) => monthlyData1.finish[i] ?? 0);
        }

        function getChartOptionsCrp(category, data) {
            const actualData = data.monthlyActuals[category] ?? [];
            const planData = data.monthlyPlans[category] ?? [];
            return {
                chart: { zoomType: 'xy' },
                title: { text: `CRP Actual vs Plan - ${category}` },
                xAxis: [{ categories: data.bulanList, crosshair: true }],
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

        function renderGrandTotChart(category, data) {
            const dataForCategory = data.grandTotalComparison[category];
            if (!dataForCategory) {
                console.error(`Data grand total untuk kategori ${category} tidak ditemukan`);
                return;
            }
            const chartData = [
                { name: 'Plan', data: [dataForCategory.Plan], color: '#7cb5ec' },
                { name: 'Actual', data: [dataForCategory.Actual], color: '#f45b5b' }
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

        function renderChartCumulative(category, data) {
            const planData = data.allMonthlyData[category]?.plan || [];
            const actualData = data.allMonthlyData[category]?.actual || [];

            if (!planData.length || !actualData.length) {
                console.error(`Data kumulatif untuk kategori ${category} tidak ditemukan`);
                return;
            }
            if (chartCumulativeCrp) {
                chartCumulativeCrp.series[0].setData(planData);
                chartCumulativeCrp.series[1].setData(actualData);
                chartCumulativeCrp.setTitle({ text: `Kumulatif Bulanan Plan vs Actual - ${category}` });
                chartCumulativeCrp.xAxis[0].setCategories(data.bulanList);
            } else {
                chartCumulativeCrp = Highcharts.chart('chart-cumulative-crp', {
                    chart: { type: 'column' },
                    title: { text: `Kumulatif Bulanan Plan vs Actual - ${category}` },
                    xAxis: { categories: data.bulanList, crosshair: true },
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

        // --- Initial Load ---
        // Initialize charts for the first active slide on page load
        const initialActiveSlide = document.querySelector('.carousel-item.active');
        const initialSlideId = initialActiveSlide ? initialActiveSlide.dataset.slideId : null;
        if (initialSlideId) {
            switch (initialSlideId) {
                case 'slide1': initSlide1Charts(); break;
                case 'slide2': initSlide2Charts(); break;
                case 'slide3': initSlide3Charts(); break;
                case 'slide4': initSlide4Charts(); break;
            }
        }

        // --- Event Listener for Slide Changes ---
            carousel.addEventListener('slid.bs.carousel', function (e) {
                const activeSlide = e.relatedTarget || carousel.querySelector('.carousel-item.active');
                const slideId = activeSlide ? activeSlide.dataset.slideId : null;
                loadedSlides.clear();
            if (slideId) {
                switch (slideId) {
                    case 'slide1': initSlide1Charts(); break;
                    case 'slide2': initSlide2Charts(); break;
                    case 'slide3': initSlide3Charts(); break;
                    case 'slide4': initSlide4Charts(); break;
                }
            }
            // Reflow charts if they exist (in case of resize during slide change)
            setTimeout(() => {
                 if (chartStatusFpb) chartStatusFpb.reflow();
                 if (pieChart) pieChart.reflow();
                 if (pieChart1) pieChart1.reflow();
                 if (chartLeadTime) chartLeadTime.reflow();
                 if (chartStatusInquiry) chartStatusInquiry.reflow();
                 if (chartCrp) chartCrp.reflow();
                 if (grandTotChart) grandTotChart.reflow();
                 if (chartCumulativeCrp) chartCumulativeCrp.reflow();
             }, 100);
        });

    }); // End DOMContentLoaded
</script>
</main>
@endsection