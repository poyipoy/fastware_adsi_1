@extends('layout')
@section('content')
<main id="main" class="main">
    <style>
  /* ====== CONTAINER UTAMA ====== */
  .dashboard-container {
    height: calc(100vh - 80px);        /* pastikan sesuai tinggi header Anda */
    padding: 15px 10px 10px 10px;
    overflow-y: auto;
    overflow-x: hidden;
    position: relative;
  }

  /* ====== CAROUSEL (WAJIB 100% TINGGI) ====== */
  .carousel-inner,
  .carousel-item {
    height: 100%;
    min-height: 0;                      /* izinkan child flex menghitung tinggi */
  }

  .carousel-fade .carousel-item {
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
  }
  .carousel-fade .carousel-item.active {
    opacity: 1;
  }

  /* ====== GRID DI DALAM SLIDE ====== */
  .carousel-item > .row,
  .dashboard-row {
    height: 100%;
    min-height: 0;
  }

  /* ====== KARTU & BODY MENGISI TINGGI ====== */
  .card {
    margin-bottom: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .card.h-100 {
    min-height: 0;                      /* penting agar flex child tidak overflow */
  }
  .card-body.stretch {
    display: flex;
    flex: 1 1 auto;
    min-height: 0;
  }

  /* ====== KANAN: DUA PIE SETENGAH-SETENGAH ====== */
  .right-stack {
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: .5rem;
    min-height: 0;
  }
  .right-stack .card {
    flex: 1 1 0;
    min-height: 0;                      /* masing-masing ambil 50% tinggi kolom */
  }

  /* ====== KONTENER CHART ====== */
  .chart-fill {
    flex: 1 1 auto;
    width: 100%;
    height: 100%;
  }
  .chart-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: #6c757d;
    flex-direction: column;
    text-align: center;
  }

  /* ====== KOMPONEN KECIL ====== */
  .form-control-sm,
  .form-select-sm,
  .btn-sm {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
  }

  /* ====== DRAG-TO-SLIDE UX ====== */
  #dashboardCarousel { cursor: grab; }
  #dashboardCarousel.dragging {
    cursor: grabbing;
    user-select: none;
  }

  /* ====== OPSIONAL: RESPONSIF KECIL ====== */
  @media (max-width: 767.98px) {
    .dashboard-container { height: auto; } /* biar boleh scroll panjang di mobile */
    .carousel-inner, .carousel-item { height: auto; }
    .carousel-item > .row, .dashboard-row { height: auto; }
    .right-stack { height: auto; }
  }
</style>


    <section class="section dashboard dashboard-container">
        {{-- Konten HTML tidak berubah dari versi lengkap sebelumnya --}}
        <div id="dashboardCarousel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel" data-bs-interval="15000">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="0" class="active" aria-current="true" data-slide-id="slide1"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="1" data-slide-id="slide2"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="2" data-slide-id="slide3"></button>
                <button type="button" data-bs-target="#dashboardCarousel" data-bs-slide-to="3" data-slide-id="slide4"></button>
            </div>

            <div class="carousel-inner h-100">
                <div class="carousel-item active h-100" data-slide-id="slide1">
                    <div class="row g-2 dashboard-row">
  <!-- KIRI: Chart utama -->
  <div class="col-md-8 h-100">
    <div class="card h-100 d-flex flex-column">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Form Pengajuan Barang</h5>
        <div class="d-flex align-items-center flex-wrap gap-2">
          <input type="date" id="start_date_fpb" class="form-control form-control-sm" style="max-width:150px" value="{{ now()->subYear()->startOfYear()->format('Y-m-d') }}">
          <input type="date" id="end_date_fpb" class="form-control form-control-sm" style="max-width:150px" value="{{ now()->format('Y-m-d') }}">
          <select id="kategori_po" class="form-select form-select-sm" style="max-width:150px">
            <option value="">Semua Kategori</option>
            @foreach($kategoriList as $kategoriItem)
              <option value="{{ $kategoriItem }}">{{ $kategoriItem }}</option>
            @endforeach
          </select>
          <button type="button" id="filter-fpb-btn" class="btn btn-primary btn-sm">Filter</button>
        </div>
        <div class="card p-2 bg-light text-dark mb-0">
          <strong>Total FPB: <span id="total-fpb-display">...</span></strong>
        </div>
      </div>

      <div class="card-body flex-grow-1 stretch">
        <div id="chart-status-fpb-placeholder" class="chart-placeholder">
          <div class="spinner-border text-primary"></div><span class="mt-2">Memuat...</span>
        </div>
        <div id="chart-status-fpb" class="chart-fill" style="display:none;"></div>
      </div>
    </div>
  </div>

  <!-- KANAN: Dua pie, total tinggi = tinggi kolom kiri -->
  <div class="col-md-4 h-100 d-flex right-stack">
    <div class="card">
      <div class="card-header py-2"><h6 class="mb-0">Status FPB</h6></div>
      <div class="card-body p-2 stretch">
        <div id="pieChart-placeholder" class="chart-placeholder">
          <div class="spinner-border text-secondary spinner-border-sm"></div>
        </div>
        <div id="pieChart" class="chart-fill" style="display:none;"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header py-2"><h6 class="mb-0">FPB per Kategori</h6></div>
      <div class="card-body p-2 stretch">
        <div id="pieChart1-placeholder" class="chart-placeholder">
          <div class="spinner-border text-secondary spinner-border-sm"></div>
        </div>
        <div id="pieChart1" class="chart-fill" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

                </div>

                <div class="carousel-item h-100" data-slide-id="slide2">
                     <div class="row h-100 g-2">
                        <div class="col-md-6 h-100">
                            <div class="card h-100 d-flex flex-column">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0">Leadtime Order Fulfillment</h5>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <input type="date" id="start_date_leadtime" class="form-control form-control-sm" value="{{ now()->subYear()->startOfYear()->format('Y-m-d') }}">
                                        <input type="date" id="end_date_leadtime" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
                                        <button type="button" id="filter-leadtime-btn" class="btn btn-primary btn-sm">Filter</button>
                                    </div>
                                </div>
                                <div class="card-body flex-grow-1">
                                    <div id="chart-lead-time-placeholder" class="chart-placeholder"><div class="spinner-border text-primary"></div><span class="mt-2">Memuat...</span></div>
                                    <div id="chart-lead-time" style="height: 100%; display: none;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 h-100">
                            <div class="card h-100 d-flex flex-column">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0">Form Inquiry Local</h5>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <input type="date" id="start_date_inquiry" class="form-control form-control-sm" value="{{ now()->subYear()->startOfYear()->format('Y-m-d') }}">
                                        <input type="date" id="end_date_inquiry" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
                                        <button type="button" id="filter-inquiry-btn" class="btn btn-primary btn-sm">Filter</button>
                                    </div>
                                    <div class="card p-2 bg-light text-dark mb-0">
                                        <strong>Total: <span id="total-inquiry-display">...</span></strong>
                                    </div>
                                </div>
                                <div class="card-body flex-grow-1">
                                    <div id="chart-status-inquiry-placeholder" class="chart-placeholder"><div class="spinner-border text-primary"></div><span class="mt-2">Memuat...</span></div>
                                    <div id="chart-status-inquiry" style="height: 100%; display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="carousel-item h-100" data-slide-id="slide3">
                    <div class="card h-100 d-flex flex-column">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">Dashboard CRP: Actual vs Plan</h5>
                             <div class="d-flex align-items-center flex-wrap gap-2">
                                <label for="category-filter" class="form-label small mb-0"><strong>Filter:</strong></label>
                                <select id="category-filter" class="form-select form-select-sm" style="width: 200px;">
                                    <option value="Total" selected>All Categories (Total)</option>
                                    @foreach ($allCategories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="card-body flex-grow-1">
                             <div id="chart-crp-placeholder" class="chart-placeholder"><div class="spinner-border text-primary"></div><span class="mt-2">Memuat...</span></div>
                             <div id="chart-crp" style="height: 100%; display: none;"></div>
                        </div>
                    </div>
                </div>

                <div class="carousel-item h-100" data-slide-id="slide4">
                    <div class="row h-100 g-2">
                        <div class="col-md-6 h-100">
                             <div class="card h-100 d-flex flex-column">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0">Grand Total CRP</h5>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <label for="category-grandtot-filter" class="form-label small mb-0"><strong>Filter:</strong></label>
                                        <select id="category-grandtot-filter" class="form-select form-select-sm" style="width: 200px;">
                                            <option value="Total" selected>All Categories (Total)</option>
                                             @foreach ($allCategories as $category)
                                                <option value="{{ $category }}">{{ $category }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body flex-grow-1">
                                     <div id="grandtot-chart-placeholder" class="chart-placeholder"><div class="spinner-border text-primary"></div><span class="mt-2">Memuat...</span></div>
                                     <div id="grandtot-chart" style="height: 100%; display: none;"></div>
                                </div>
                            </div>
                        </div>
                         <div class="col-md-6 h-100">
                             <div class="card h-100 d-flex flex-column">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0">Cumulative CRP</h5>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <label for="category-filter-cumulative" class="form-label small mb-0"><strong>Filter:</strong></label>
                                        <select id="category-filter-cumulative" class="form-select form-select-sm" style="width: 200px;">
                                            <option value="Total" selected>All Categories (Total)</option>
                                             @foreach ($allCategories as $category)
                                                <option value="{{ $category }}">{{ $category }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body flex-grow-1">
                                     <div id="chart-cumulative-crp-placeholder" class="chart-placeholder"><div class="spinner-border text-primary"></div><span class="mt-2">Memuat...</span></div>
                                     <div id="chart-cumulative-crp" style="height: 100%; display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const carousel = document.getElementById('dashboardCarousel');
        if (!carousel) return;

        // --- FUNGSI DRAG-TO-SLIDE UNTUK CAROUSEL ---
        let isDragging = false;
        let startX;

        const startDrag = (e) => {
            isDragging = true;
            startX = e.pageX || e.touches[0].pageX;
            carousel.classList.add('dragging');
        };

        const endDrag = () => {
            if (!isDragging) return;
            isDragging = false;
            carousel.classList.remove('dragging');
        };

        const moveDrag = (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const currentX = e.pageX || e.touches[0].pageX;
            const walk = currentX - startX;

            if (Math.abs(walk) > 50) { // Jarak minimal geser 50px
                const bsCarousel = bootstrap.Carousel.getInstance(carousel);
                if (bsCarousel) {
                    if (walk > 0) bsCarousel.prev();
                    else bsCarousel.next();
                }
                endDrag(); 
            }
        };

        carousel.addEventListener('mousedown', startDrag);
        carousel.addEventListener('mouseup', endDrag);
        carousel.addEventListener('mouseleave', endDrag);
        carousel.addEventListener('mousemove', moveDrag);
        carousel.addEventListener('touchstart', startDrag, { passive: true });
        carousel.addEventListener('touchend', endDrag);
        carousel.addEventListener('touchmove', moveDrag);

        // --- Logika untuk Memuat Chart ---
        const loadedSlides = new Set();
        const chartInstances = {};
        let crpDataCache = null;
        const requestCache = new Map();
        const pendingRequests = new Map();

        async function fetchData(endpointUrl, params = {}, options = {}) {
            const { forceRefresh = false } = options;
            const url = new URL(endpointUrl, window.location.origin);
            Object.entries(params).forEach(([key, value]) => {
                if (value === undefined || value === null || value === '') {
                    return;
                }
                url.searchParams.set(key, value);
            });

            const cacheKey = `${url.pathname}?${url.searchParams.toString()}`;

            if (!forceRefresh) {
                if (requestCache.has(cacheKey)) {
                    return requestCache.get(cacheKey);
                }
                if (pendingRequests.has(cacheKey)) {
                    return pendingRequests.get(cacheKey);
                }
            } else {
                requestCache.delete(cacheKey);
            }

            const requestPromise = (async () => {
                const response = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json().catch(() => null);

                if (!response.ok) {
                    throw new Error(payload?.message ?? 'Gagal memuat data');
                }

                if (!payload?.success) {
                    throw new Error(payload?.message ?? 'Gagal memuat data');
                }

                requestCache.set(cacheKey, payload.data);
                return payload.data;
            })();

            pendingRequests.set(cacheKey, requestPromise);

            try {
                return await requestPromise;
            } finally {
                pendingRequests.delete(cacheKey);
            }
        }

        function togglePlaceholder(chartId, showPlaceholder) {
            const chartEl = document.getElementById(chartId);
            const placeholderEl = document.getElementById(`${chartId}-placeholder`);
            if (chartEl) {
                chartEl.style.display = showPlaceholder ? 'none' : 'block';
            }
            if (placeholderEl) {
                placeholderEl.style.display = showPlaceholder ? 'flex' : 'none';
            }
        }

        async function initSlide1Charts(forceRefresh = false) {
            if (!forceRefresh && loadedSlides.has('slide1')) return;
            loadedSlides.add('slide1');

            ['chart-status-fpb', 'pieChart', 'pieChart1'].forEach(id => togglePlaceholder(id, true));

            try {
                const data = await fetchData('{{ route("api.dashboard.fpb") }}', {
                    start_date: document.getElementById('start_date_fpb')?.value,
                    end_date: document.getElementById('end_date_fpb')?.value,
                    kategori_po: document.getElementById('kategori_po')?.value,
                }, { forceRefresh });

                const totalDisplay = document.getElementById('total-fpb-display');
                if (totalDisplay) {
                    totalDisplay.textContent = data.totalFPB ?? 0;
                }

                chartInstances.fpbStatus = Highcharts.chart('chart-status-fpb', {
                    chart: { type: 'column' },
                    title: { text: null },
                    xAxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'] },
                    yAxis: { min: 0, title: { text: 'Jumlah' } },
                    series: [
                        { name: 'Dibuat', data: data.monthlyData.open },
                        { name: 'Selesai', data: data.monthlyData.finish },
                    ],
                    credits: { enabled: false },
                });

                chartInstances.pieStatus = Highcharts.chart('pieChart', {
                    chart: { type: 'pie' },
                    title: { text: null },
                    tooltip: { pointFormat: '{series.name}: <b>{point.y}</b>' },
                    series: [{
                        name: 'Jumlah',
                        data: [
                            { name: 'Open', y: data.pieStatus.open },
                            { name: 'Finish', y: data.pieStatus.finish },
                        ],
                    }],
                    credits: { enabled: false },
                });

                const categoryData = Object.entries(data.pieCategory).map(([name, value]) => ({ name, y: value }));
                chartInstances.pieCategory = Highcharts.chart('pieChart1', {
                    chart: { type: 'pie' },
                    title: { text: null },
                    tooltip: { pointFormat: '{series.name}: <b>{point.y}</b>' },
                    series: [{ name: 'Jumlah', data: categoryData }],
                    credits: { enabled: false },
                });

                ['chart-status-fpb', 'pieChart', 'pieChart1'].forEach(id => togglePlaceholder(id, false));
            } catch (error) {
                ['chart-status-fpb', 'pieChart', 'pieChart1'].forEach(id => {
                    const placeholderEl = document.getElementById(`${id}-placeholder`);
                    if (placeholderEl) {
                        placeholderEl.innerHTML = `<span class="text-danger">Gagal memuat: ${error.message}</span>`;
                        placeholderEl.style.display = 'flex';
                    }
                });
            }
        }

        async function initSlide2Charts(forceRefresh = false) {
            if (!forceRefresh && loadedSlides.has('slide2')) return;
            loadedSlides.add('slide2');

            ['chart-lead-time', 'chart-status-inquiry'].forEach(id => togglePlaceholder(id, true));

            try {
                const [leadTimeData, inquiryData] = await Promise.all([
                    fetchData('{{ route("api.dashboard.leadtime") }}', {
                        start_date: document.getElementById('start_date_leadtime')?.value,
                        end_date: document.getElementById('end_date_leadtime')?.value,
                    }, { forceRefresh }),
                    fetchData('{{ route("api.dashboard.inquiry") }}', {
                        start_date: document.getElementById('start_date_inquiry')?.value,
                        end_date: document.getElementById('end_date_inquiry')?.value,
                    }, { forceRefresh }),
                ]);

                const leadTimeResult = leadTimeData.leadTimeData;
                const categories = Object.keys(leadTimeResult);

                chartInstances.leadTime = Highcharts.chart('chart-lead-time', {
                    chart: { type: 'column' },
                    title: { text: null },
                    xAxis: { categories },
                    yAxis: { title: { text: 'Rata-rata Hari' } },
                    plotOptions: { column: { stacking: 'normal' } },
                    series: [
                        {
                            name: 'Submit-Confirm',
                            data: categories.map(c => leadTimeResult[c]?.average_lead_days_first ?? 0),
                        },
                        {
                            name: 'Confirm-Finish',
                            data: categories.map(c => leadTimeResult[c]?.average_lead_days_second ?? 0),
                        },
                    ],
                    credits: { enabled: false },
                });

                const totalInquiry = document.getElementById('total-inquiry-display');
                if (totalInquiry) {
                    totalInquiry.textContent = inquiryData.totalinquiry ?? 0;
                }

                chartInstances.inquiryStatus = Highcharts.chart('chart-status-inquiry', {
                    chart: { type: 'column' },
                    title: { text: null },
                    xAxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'] },
                    yAxis: { title: { text: 'Jumlah' } },
                    series: [
                        { name: 'Open', data: inquiryData.monthlyData1.open },
                        { name: 'On Progress', data: inquiryData.monthlyData1.onprogress },
                        { name: 'Finish', data: inquiryData.monthlyData1.finish },
                    ],
                    credits: { enabled: false },
                });

                ['chart-lead-time', 'chart-status-inquiry'].forEach(id => togglePlaceholder(id, false));
            } catch (error) {
                ['chart-lead-time', 'chart-status-inquiry'].forEach(id => {
                    const placeholderEl = document.getElementById(`${id}-placeholder`);
                    if (placeholderEl) {
                        placeholderEl.innerHTML = `<span class="text-danger">Gagal memuat: ${error.message}</span>`;
                        placeholderEl.style.display = 'flex';
                    }
                });
            }
        }
        
        async function initCrpCharts(forceRefresh = false) {
            if (crpDataCache && !forceRefresh) {
                renderCrpCharts();
                return;
            }

            if (forceRefresh) {
                crpDataCache = null;
            }

            ['chart-crp', 'grandtot-chart', 'chart-cumulative-crp'].forEach(id => togglePlaceholder(id, true));

            try {
                crpDataCache = await fetchData('{{ route("api.dashboard.crp") }}', {}, { forceRefresh });
                renderCrpCharts();
                ['chart-crp', 'grandtot-chart', 'chart-cumulative-crp'].forEach(id => togglePlaceholder(id, false));
            } catch (error) {
                ['chart-crp', 'grandtot-chart', 'chart-cumulative-crp'].forEach(id => {
                    const placeholderEl = document.getElementById(`${id}-placeholder`);
                    if (placeholderEl) {
                        placeholderEl.innerHTML = `<span class="text-danger">Gagal memuat: ${error.message}</span>`;
                        placeholderEl.style.display = 'flex';
                    }
                });
            }
        }

        function renderCrpCharts() {
            if (!crpDataCache) return;

            const cat1 = document.getElementById('category-filter')?.value ?? 'Total';
            const cat2 = document.getElementById('category-grandtot-filter')?.value ?? 'Total';
            const cat3 = document.getElementById('category-filter-cumulative')?.value ?? 'Total';

            chartInstances.crp = Highcharts.chart('chart-crp', {
                chart: { zoomType: 'xy' },
                title: { text: `Actual vs Plan - ${cat1}` },
                xAxis: [{ categories: crpDataCache.bulanList, crosshair: true }],
                yAxis: [{ title: { text: 'Nilai (Rp)' } }],
                series: [
                    { name: 'Actual', type: 'column', data: crpDataCache.monthlyActuals[cat1] },
                    { name: 'Plan', type: 'spline', data: crpDataCache.monthlyPlans[cat1] },
                ],
                credits: { enabled: false },
            });

            chartInstances.grandTot = Highcharts.chart('grandtot-chart', {
                chart: { type: 'column' },
                title: { text: `Grand Total - ${cat2}` },
                xAxis: { categories: ['Grand Total'] },
                yAxis: { min: 0, title: { text: 'Nilai (Rp)' } },
                series: [
                    { name: 'Plan', data: [crpDataCache.grandTotalComparison[cat2].Plan] },
                    { name: 'Actual', data: [crpDataCache.grandTotalComparison[cat2].Actual] },
                ],
                credits: { enabled: false },
            });

            chartInstances.cumulative = Highcharts.chart('chart-cumulative-crp', {
                chart: { type: 'column' },
                title: { text: `Kumulatif - ${cat3}` },
                xAxis: { categories: crpDataCache.bulanList, crosshair: true },
                yAxis: { min: 0, title: { text: 'Nilai Kumulatif (Rp)' } },
                series: [
                    { name: 'Plan', data: crpDataCache.allMonthlyData[cat3].plan },
                    { name: 'Actual', data: crpDataCache.allMonthlyData[cat3].actual },
                ],
                credits: { enabled: false },
            });
        }
        
        function handleFilter(slideKey, initFunction) {
            loadedSlides.delete(slideKey);
            initFunction(true);
        }

        const filterFpbBtn = document.getElementById('filter-fpb-btn');
        const filterLeadBtn = document.getElementById('filter-leadtime-btn');
        const filterInquiryBtn = document.getElementById('filter-inquiry-btn');

        if (filterFpbBtn) {
            filterFpbBtn.addEventListener('click', () => handleFilter('slide1', initSlide1Charts));
        }
        if (filterLeadBtn) {
            filterLeadBtn.addEventListener('click', () => handleFilter('slide2', initSlide2Charts));
        }
        if (filterInquiryBtn) {
            filterInquiryBtn.addEventListener('click', () => handleFilter('slide2', initSlide2Charts));
        }
        
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (event) => {
                const selected = event.target.value;
                if (!crpDataCache || !chartInstances.crp) return;
                chartInstances.crp.update({
                    title: { text: `Actual vs Plan - ${selected}` },
                    series: [
                        { data: crpDataCache.monthlyActuals[selected] },
                        { data: crpDataCache.monthlyPlans[selected] },
                    ],
                }, true);
            });
        }

        const categoryGrandTotFilter = document.getElementById('category-grandtot-filter');
        if (categoryGrandTotFilter) {
            categoryGrandTotFilter.addEventListener('change', (event) => {
                const selected = event.target.value;
                if (!crpDataCache || !chartInstances.grandTot) return;
                chartInstances.grandTot.update({
                    title: { text: `Grand Total - ${selected}` },
                    series: [
                        { data: [crpDataCache.grandTotalComparison[selected].Plan] },
                        { data: [crpDataCache.grandTotalComparison[selected].Actual] },
                    ],
                }, true);
            });
        }

        const categoryCumulativeFilter = document.getElementById('category-filter-cumulative');
        if (categoryCumulativeFilter) {
            categoryCumulativeFilter.addEventListener('change', (event) => {
                const selected = event.target.value;
                if (!crpDataCache || !chartInstances.cumulative) return;
                chartInstances.cumulative.update({
                    title: { text: `Kumulatif - ${selected}` },
                    series: [
                        { data: crpDataCache.allMonthlyData[selected].plan },
                        { data: crpDataCache.allMonthlyData[selected].actual },
                    ],
                }, true);
            });
        }

        carousel.addEventListener('slid.bs.carousel', function (event) {
            const slideId = event.relatedTarget.dataset.slideId;
            if (slideId === 'slide1') initSlide1Charts();
            if (slideId === 'slide2') initSlide2Charts();
            if (slideId === 'slide3' || slideId === 'slide4') initCrpCharts();
        });

        const initialActiveSlide = carousel.querySelector('.carousel-item.active');
        if (initialActiveSlide) {
            initSlide1Charts();
        }
    });
    </script>
</main>
@endsection
