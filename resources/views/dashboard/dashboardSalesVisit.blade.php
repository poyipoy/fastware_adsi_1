@extends('layout')
@section('content')
<style>
    /* Styling for Select2 Border to match Form-Control */
    .select2-container .select2-selection--single {
        border: 1px solid #ced4da !important;
        height: 38px !important;
        padding: 0.375rem 0.75rem;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        top: 1px !important;
        right: 1px !important;
    }
    .select2-container .select2-selection--single .select2-selection__rendered {
        padding-left: 0 !important;
        color: #212529 !important;
    }
</style>
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Dashboard Sales Visit</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('login') }}">Home</a></li>
                <li class="breadcrumb-item active">Dashboard Sales Visit</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard dashboard-container">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" id="startDate" class="form-control w-100" value="{{ \Carbon\Carbon::now()->subDays(7)->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" id="endDate" class="form-control w-100" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sales</label>
                    <select id="salesFilter" class="form-select select2" style="width: 100%">
                        <option value="">Semua Sales</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Region</label>
                    <select id="regionFilter" class="form-select w-100">
                        <option value="">Semua Region</option>
                        <option value="Region 1">Region 1</option>
                        <option value="Region 2">Region 2</option>
                        <option value="Region 3">Region 3</option>
                        <option value="Region 4">Region 4</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Perusahaan</label>
                    <select id="companyFilter" class="form-select select2" style="width: 100%">
                        <option value="">Semua Perusahaan</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="exportExcel" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-file-earmark-excel"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Summary Cards Row 1 -->
        <div class="row">
            <div class="col-md-3">
                <div class="card summary-card border-0 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white mb-1">Total Plans</h6>
                                <h3 class="mb-0" id="totalPlans">-</h3>
                            </div>
                            <i class="bi bi-calendar-check card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card border-0 bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white mb-1">Total Visits</h6>
                                <h3 class="mb-0" id="totalVisits">-</h3>
                            </div>
                            <i class="bi bi-pin-map card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card border-0 bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white mb-1">Sesuai Plan</h6>
                                <h3 class="mb-0" id="sesuaiPlan">-</h3>
                            </div>
                            <i class="bi bi-check-circle card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card border-0 bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Tidak Sesuai Plan</h6>
                                <h3 class="mb-0" id="tidakSesuaiPlan">-</h3>
                            </div>
                            <i class="bi bi-exclamation-triangle card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards Row 2 - Customer Statistics -->
        <div class="row">
            <div class="col-md-4">
                <div class="card summary-card border-0 bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white mb-1">Total Customer</h6>
                                <h3 class="mb-0" id="totalCustomer">-</h3>
                            </div>
                            <i class="bi bi-people card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card border-0 bg-dark text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white mb-1">Customer Lama</h6>
                                <h3 class="mb-0" id="customerLama">-</h3>
                            </div>
                            <i class="bi bi-person-check card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card border-0" style="background-color: #6f42c1; color: white;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white mb-1">Customer Baru</h6>
                                <h3 class="mb-0" id="customerBaru">-</h3>
                            </div>
                            <i class="bi bi-person-plus card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="chart-container">
            <h5 class="mb-3">Plan vs Visit Per Sales</h5>
            <div>
                <div id="planVisitChart" style="width:100%; height:400px;"></div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <h5 class="mb-3">Detail Kunjungan</h5>
            <table id="visitTable" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Nama Sales</th>
                        <th>Tgl Plan</th>
                        <th>Keterangan</th>
                        <th>Nama Customer</th>
                        <th>PIC</th>
                        <th>Tgl Visit</th>
                        <th>Remark</th>
                        <th>Visit Result</th>
                        <th>File</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@push('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

<style>
    .dashboard-container {
        padding: 20px;
    }

    .summary-card {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        margin-bottom: 20px;
        min-height: 100px;
    }

    .summary-card .card-body {
        padding: 1rem;
    }

    .summary-card h6 {
        font-size: 0.85rem;
        font-weight: 500;
    }

    .summary-card h3 {
        font-size: 1.75rem;
        font-weight: 700;
    }

    .summary-card:hover {
        transform: translateY(-5px);
    }

    .card-icon {
        font-size: 2rem;
        opacity: 0.8;
    }

    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .table-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 500;
    }

    .badge-sesuai-plan {
        background-color: #d4edda;
        color: #155724;
    }

    .badge-tidak-sesuai {
        background-color: #fff3cd;
        color: #856404;
    }

    .badge-planned-only {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .badge-visit-only {
        background-color: #f8d7da;
        color: #721c24;
    }

    .select2-container {
        width: 100% !important;
    }

    @media (max-width: 768px) {
        .col-md-2, .col-md-3, .col-md-4 {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<!-- jQuery (must be first) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Chart.js -->
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script>
$(document).ready(function() {
    // let planVisitChart; // Not needed for clean Highcharts (we simply overwrite)

    // Initialize Select2 with strict width
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Pilih...',
        allowClear: true
    });

    // Initialize DataTable (Server-side)
    var table = $('#visitTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        scrollX: true,
        ajax: {
            url: "{{ route('salesvisit.dashboard.detail-data') }}",
            data: function(d) {
                d.startDate = $('#startDate').val();
                d.endDate = $('#endDate').val();
                d.salesFilter = $('#salesFilter').val();
                d.regionFilter = $('#regionFilter').val();
                d.companyFilter = $('#companyFilter').val();
            },
            dataSrc: 'data'
        },
        columns: [
            { data: 'sales_name', name: 'sales_name' },
            { data: 'plan_date', name: 'plan_date' },
            { data: 'keterangan', name: 'keterangan' },
            { data: 'company', name: 'company' },
            { data: 'pic_cust', name: 'pic_cust' },
            { data: 'visit_date', name: 'visit_date' },
            { 
                data: 'remark', 
                name: 'remark',
                render: function(data) {
                    if (!data || data === '-') return '-';
                    let badgeClass = data.toLowerCase().includes('follow') ? 'bg-warning text-dark' : 'bg-info text-white';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { data: 'visit_result', name: 'visit_result' },
            { 
                data: 'files', 
                name: 'files',
                render: function(data, type, row) {
                    if (!data || data === '-') return '-';
                    if (Array.isArray(data)) {
                        return data.map((file, index) => 
                            `<a href="${file}" target="_blank" class="badge bg-primary me-1">File ${index + 1}</a>`
                        ).join(' ');
                    }
                    return data;
                }
            }
        ],
        order: [[5, 'desc']] // Sort by Visit Date default
    });

    // Load Dashboard Stats & Charts (AJAX)
    function loadDashboardData() {
        $.ajax({
            url: "{{ route('salesvisit.dashboard.data') }}",
            method: 'GET',
            data: {
                startDate: $('#startDate').val(),
                endDate: $('#endDate').val(),
                salesFilter: $('#salesFilter').val(),
                regionFilter: $('#regionFilter').val(),
                companyFilter: $('#companyFilter').val()
            },
            success: function(response) {
                // Update Cards
                $('#totalPlans').text(response.summary.totalPlans);
                $('#totalVisits').text(response.summary.totalVisits);
                $('#sesuaiPlan').text(response.summary.sesuaiPlan);
                $('#tidakSesuaiPlan').text(response.summary.tidakSesuaiPlan);
                $('#totalCustomer').text(response.summary.totalCustomer);
                $('#customerLama').text(response.summary.customerLama);
                $('#customerBaru').text(response.summary.customerBaru);

                // Update Chart
                updateChart(response.chartData);

                // Populate Companies
                if ($('#companyFilter option').length <= 1) {
                    populateCompanyFilter(response.companies);
                }
                
                // Populate Sales
                if ($('#salesFilter option').length <= 1) {
                    populateSalesFilter(response.chartData.labels); 
                }
            },
            error: function(xhr) {
                console.error("Error loading stats:", xhr);
            }
        });
    }

    // Debounced Filter Function (Auto Update)
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    const autoUpdate = debounce(function() {
        // Show Loading State on Cards
        $('.summary-card h3').text('...');
        
        table.draw(); // Reload Table
        loadDashboardData(); // Reload Stats
    }, 300);

    // Bind events for responsiveness
    $('#startDate, #endDate').on('change input', autoUpdate);
    $('#salesFilter, #regionFilter, #companyFilter').on('change select2:select select2:clear', autoUpdate);

    // Export Button
    $('#exportExcel').click(function() {
        var query = $.param({
            startDate: $('#startDate').val(),
            endDate: $('#endDate').val(),
            salesFilter: $('#salesFilter').val(),
            regionFilter: $('#regionFilter').val(),
            companyFilter: $('#companyFilter').val()
        });
        window.location.href = "{{ route('salesvisit.dashboard.export') }}?" + query;
    });
    
    function populateSalesFilter(salesNames) {
        if($('#salesFilter option').length > 1) return;
        const salesFilter = $('#salesFilter');
        salesNames.forEach(function(name) {
            salesFilter.append(new Option(name, name));
        });
    }

    function populateCompanyFilter(companies) {
        const companyFilter = $('#companyFilter');
        companyFilter.find('option:not(:first)').remove();
        companies.forEach(function(company) {
            companyFilter.append(new Option(company, company));
        });
    }

    function updateChart(chartData) {
        Highcharts.chart('planVisitChart', {
            chart: {
                type: 'column'
            },
            title: {
                text: null
            },
            xAxis: {
                categories: chartData.labels,
                crosshair: true
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Jumlah'
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                    '<td style="padding:0"><b>{point.y}</b></td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0
                }
            },
            series: [{
                name: 'Plan',
                data: chartData.plans,
                color: '#ffc107' // Bootstrap Warning
            }, {
                name: 'Actual Visits',
                data: chartData.visits,
                color: '#0dcaf0' // Bootstrap Info
            }],
            credits: {
                enabled: false
            }
        });
    }

    // Initial Load
    loadDashboardData();
});
</script>
@endpush

