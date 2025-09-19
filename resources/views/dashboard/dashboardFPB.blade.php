@extends('layout')
@section('content')
<main id="main" class="main">
    <style>
        .dashboard-container { height: calc(100vh - 80px); overflow: hidden; padding: 15px 10px 10px 10px; position: relative; }
        .carousel-inner, .carousel-item, .carousel-item.active { height: 100%; }
        .carousel-fade .carousel-item { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .carousel-fade .carousel-item.active { opacity: 1; }
        .card { margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .chart-placeholder { display: flex; justify-content: center; align-items: center; height: 100%; color: #6c757d; flex-direction: column; text-align: center; }
        .form-control-sm, .form-select-sm, .btn-sm { font-size: 0.8rem; padding: 0.25rem 0.5rem; }

        /* PERUBAHAN: CSS untuk drag-to-slide */
        #dashboardCarousel {
            cursor: grab;
        }
        #dashboardCarousel.dragging {
            cursor: grabbing;
            user-select: none; /* Mencegah seleksi teks saat dragging */
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
                    <div class="row h-100 g-2">
                        <div class="col-md-8 h-100">
                            <div class="card h-100 d-flex flex-column">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0">Form Pengajuan Barang</h5>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <input type="date" id="start_date_fpb" class="form-control form-control-sm" style="max-width: 150px;" value="{{ now()->subYear()->startOfYear()->format('Y-m-d') }}">
                                        <input type="date" id="end_date_fpb" class="form-control form-control-sm" style="max-width: 150px;" value="{{ now()->format('Y-m-d') }}">
                                        <select id="kategori_po" class="form-select form-select-sm" style="max-width: 150px;">
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
                                <div class="card-body flex-grow-1">
                                    <div id="chart-status-fpb-placeholder" class="chart-placeholder"><div class="spinner-border text-primary"></div><span class="mt-2">Memuat...</span></div>
                                    <div id="chart-status-fpb" style="height: 100%; display: none;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 h-100 d-flex flex-column gap-2">
                            <div class="card flex-fill">
                                <div class="card-header py-2"><h6 class="mb-0">Status FPB</h6></div>
                                <div class="card-body p-2">
                                    <div id="pieChart-placeholder" class="chart-placeholder"><div class="spinner-border text-secondary spinner-border-sm"></div></div>
                                    <div id="pieChart" style="height: 100%; display: none;"></div>
                                </div>
                            </div>
                             <div class="card flex-fill">
                                <div class="card-header py-2"><h6 class="mb-0">FPB per Kategori</h6></div>
                                <div class="card-body p-2">
                                    <div id="pieChart1-placeholder" class="chart-placeholder"><div class="spinner-border text-secondary spinner-border-sm"></div></div>
                                    <div id="pieChart1" style="height: 100%; display: none;"></div>
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

        async function fetchData(endpointUrl, params = {}) {
            const url = new URL(endpointUrl, window.location.origin);
            Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            if (!response.ok) throw new Error('Network response was not ok.');
            const result = await response.json();
            if (!result.success) throw new Error(result.message || 'Gagal memuat data');
            return result.data;
        }

        function togglePlaceholder(chartId, show) {
            const chartEl = document.getElementById(chartId);
            const placeholderEl = document.getElementById(`${chartId}-placeholder`);
            if(chartEl) chartEl.style.display = show ? 'none' : 'block';
            if(placeholderEl) placeholderEl.style.display = show ? 'flex' : 'none';
        }

        async function initSlide1Charts() {
            if (loadedSlides.has('slide1')) return;
            loadedSlides.add('slide1');
            ['chart-status-fpb', 'pieChart', 'pieChart1'].forEach(id => togglePlaceholder(id, true));
            try {
                const data = await fetchData('{{ route("api.dashboard.fpb") }}', {
                    start_date: document.getElementById('start_date_fpb').value,
                    end_date: document.getElementById('end_date_fpb').value,
                    kategori_po: document.getElementById('kategori_po').value
                });
                document.getElementById('total-fpb-display').textContent = data.totalFPB;
                chartInstances.fpbStatus = Highcharts.chart('chart-status-fpb', { chart: { type: 'column' }, title: { text: null }, xAxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'] }, yAxis: { min: 0, title: { text: 'Jumlah' } }, series: [{ name: 'Dibuat', data: data.monthlyData.open }, { name: 'Selesai', data: data.monthlyData.finish }], credits: { enabled: false } });
                chartInstances.pieStatus = Highcharts.chart('pieChart', { chart: { type: 'pie' }, title: { text: null }, tooltip: { pointFormat: '{series.name}: <b>{point.y}</b>' }, series: [{ name: 'Jumlah', data: [{ name: 'Open', y: data.pieStatus.open }, { name: 'Finish', y: data.pieStatus.finish }] }], credits: { enabled: false } });
                const categoryData = Object.keys(data.pieCategory).map(key => ({ name: key, y: data.pieCategory[key] }));
                chartInstances.pieCategory = Highcharts.chart('pieChart1', { chart: { type: 'pie' }, title: { text: null }, tooltip: { pointFormat: '{series.name}: <b>{point.y}</b>' }, series: [{ name: 'Jumlah', data: categoryData }], credits: { enabled: false } });
            } catch (error) {
                document.getElementById('chart-status-fpb-placeholder').innerHTML = `<span class="text-danger">Gagal: ${error.message}</span>`;
            } finally {
                ['chart-status-fpb', 'pieChart', 'pieChart1'].forEach(id => togglePlaceholder(id, false));
            }
        }

        async function initSlide2Charts() {
            if (loadedSlides.has('slide2')) return;
            loadedSlides.add('slide2');
            ['chart-lead-time', 'chart-status-inquiry'].forEach(id => togglePlaceholder(id, true));
            try {
                const [leadTimeData, inquiryData] = await Promise.all([
                    fetchData('{{ route("api.dashboard.leadtime") }}', { start_date: document.getElementById('start_date_leadtime').value, end_date: document.getElementById('end_date_leadtime').value }),
                    fetchData('{{ route("api.dashboard.inquiry") }}', { start_date: document.getElementById('start_date_inquiry').value, end_date: document.getElementById('end_date_inquiry').value })
                ]);
                document.getElementById('total-inquiry-display').textContent = inquiryData.totalinquiry;
                const ltData = leadTimeData.leadTimeData;
                const categories = Object.keys(ltData);
                chartInstances.leadTime = Highcharts.chart('chart-lead-time', { chart: { type: 'column' }, title: { text: null }, xAxis: { categories }, yAxis: { title: { text: 'Rata-rata Hari' } }, plotOptions: { column: { stacking: 'normal' } }, series: [{ name: 'Submit-Confirm', data: categories.map(c => ltData[c].average_lead_days_first) }, { name: 'Confirm-Finish', data: categories.map(c => ltData[c].average_lead_days_second) }], credits: { enabled: false } });
                chartInstances.inquiryStatus = Highcharts.chart('chart-status-inquiry', { chart: { type: 'column' }, title: { text: null }, xAxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'] }, yAxis: { title: { text: 'Jumlah' } }, series: [{ name: 'Open', data: inquiryData.monthlyData1.open }, { name: 'On Progress', data: inquiryData.monthlyData1.onprogress }, { name: 'Finish', data: inquiryData.monthlyData1.finish }], credits: { enabled: false } });
            } catch (error) {
                document.getElementById('chart-lead-time-placeholder').innerHTML = `<span class="text-danger">Gagal: ${error.message}</span>`;
            } finally {
                ['chart-lead-time', 'chart-status-inquiry'].forEach(id => togglePlaceholder(id, false));
            }
        }
        
        async function initCrpCharts() {
            if (crpDataCache) { renderCrpCharts(); return; }
            ['chart-crp', 'grandtot-chart', 'chart-cumulative-crp'].forEach(id => togglePlaceholder(id, true));
             try {
                crpDataCache = await fetchData('{{ route("api.dashboard.crp") }}');
                renderCrpCharts();
             } catch(error) {
                document.getElementById('chart-crp-placeholder').innerHTML = `<span class="text-danger">Gagal: ${error.message}</span>`;
             }
        }

        function renderCrpCharts() {
            if (!crpDataCache) return;
            const data = crpDataCache;
            
            togglePlaceholder('chart-crp', false);
            const cat1 = document.getElementById('category-filter').value;
            chartInstances.crp = Highcharts.chart('chart-crp', { chart: { zoomType: 'xy' }, title: { text: `Actual vs Plan - ${cat1}` }, xAxis: [{ categories: data.bulanList, crosshair: true }], yAxis: [{ title: { text: 'Nilai (Rp)' } }], series: [{ name: 'Actual', type: 'column', data: data.monthlyActuals[cat1] }, { name: 'Plan', type: 'spline', data: data.monthlyPlans[cat1] }], credits: { enabled: false } });
            
            togglePlaceholder('grandtot-chart', false);
            togglePlaceholder('chart-cumulative-crp', false);
            const cat2 = document.getElementById('category-grandtot-filter').value;
            const cat3 = document.getElementById('category-filter-cumulative').value;
            chartInstances.grandTot = Highcharts.chart('grandtot-chart', { chart: { type: 'column' }, title: { text: `Grand Total - ${cat2}` }, xAxis: { categories: ['Grand Total'] }, yAxis: { min: 0, title: { text: 'Nilai (Rp)' } }, series: [{name: 'Plan', data: [data.grandTotalComparison[cat2].Plan]}, {name: 'Actual', data: [data.grandTotalComparison[cat2].Actual]}], credits: { enabled: false } });
            chartInstances.cumulative = Highcharts.chart('chart-cumulative-crp', { chart: { type: 'column' }, title: { text: `Kumulatif - ${cat3}` }, xAxis: { categories: data.bulanList, crosshair: true }, yAxis: { min: 0, title: { text: 'Nilai Kumulatif (Rp)' } }, series: [{ name: 'Plan', data: data.allMonthlyData[cat3].plan }, { name: 'Actual', data: data.allMonthlyData[cat3].actual }], credits: { enabled: false } });
        }
        
        function handleFilter(slideKey, initFunction) {
            loadedSlides.delete(slideKey);
            initFunction();
        }

        document.getElementById('filter-fpb-btn').addEventListener('click', () => handleFilter('slide1', initSlide1Charts));
        document.getElementById('filter-leadtime-btn').addEventListener('click', () => handleFilter('slide2', initSlide2Charts));
        document.getElementById('filter-inquiry-btn').addEventListener('click', () => handleFilter('slide2', initSlide2Charts));
        
        document.getElementById('category-filter').addEventListener('change', (e) => chartInstances.crp?.update({ title: { text: `Actual vs Plan - ${e.target.value}` }, series: [{data: crpDataCache.monthlyActuals[e.target.value]}, {data: crpDataCache.monthlyPlans[e.target.value]}] }));
        document.getElementById('category-grandtot-filter').addEventListener('change', (e) => chartInstances.grandTot?.update({ title: { text: `Grand Total - ${e.target.value}` }, series: [{data: [crpDataCache.grandTotalComparison[e.target.value].Plan]}, {data: [crpDataCache.grandTotalComparison[e.target.value].Actual]}] }));
        document.getElementById('category-filter-cumulative').addEventListener('change', (e) => chartInstances.cumulative?.update({ title: { text: `Kumulatif - ${e.target.value}` }, series: [{data: crpDataCache.allMonthlyData[e.target.value].plan}, {data: crpDataCache.allMonthlyData[e.target.value].actual}] }));

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