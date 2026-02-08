@include('layout')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<main id="main" class="container-fluid py-4">
    <div class="pagetitle mb-4">
            <h1 class="mb-1">Dashboard Material Price Daido</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">BOPM</li>
                </ol>
            </nav>
        </div>
        <!-- Filter Section -->
        <section class="card shadow-sm mb-4" style="background-color: #6c757d;">
            <div class="card-body p-4">
                <form id="filterForm">
                    <div class="row g-4">
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label fw-semibold text-uppercase small text-white" for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label fw-semibold text-uppercase small text-white" for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold text-uppercase small text-white" for="material_id">Material Grade</label>
                        <select id="material_id" name="material_id" class="form-select">
                            <option value="all">Semua Material (Jumlah Keseluruhan)</option>
                            @foreach($materials as $material)
                                <option value="{{ $material['id'] }}">{{ $material['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-semibold text-uppercase small text-white" for="currency_month">Currency Month</label>
                        <select id="currency_month" name="currency_month" class="form-select">
                            <option value="">YEN (Default)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-semibold text-uppercase small text-white" for="currency_id">Phase</label>
                        <select id="currency_id" name="currency_id" disabled class="form-select">
                            <option value="">Pilih Bulan Kurs dulu</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-semibold text-uppercase small text-white" for="multiplier">Data Multiplier</label>
                        <select id="multiplier" name="multiplier" class="form-select">
                            <option value="1">Default (x1)</option>
                            <option value="1.05">x1.05</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 d-flex align-items-end">
                        <button type="button" id="btnReset" class="btn btn-light w-100">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset Filters
                        </button>
                    </div>
                </div>
                    <div class="mt-4 pt-4 border-top border-light d-flex flex-wrap gap-3">
                        <button type="button" id="btnAddMaterial" data-bs-toggle="modal" data-bs-target="#materialModal" class="btn btn-danger">
                            <i class="bi bi-plus-circle me-2"></i>Add Material
                        </button>
                        <button type="button" id="btnAddCurrency" data-bs-toggle="modal" data-bs-target="#currencyModal" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Add Currency
                        </button>
                        <button type="button" id="btnImportExport" data-bs-toggle="modal" data-bs-target="#importExportModal" class="btn btn-warning text-dark ms-auto">
                            <i class="bi bi-file-earmark-arrow-up me-2"></i>Import/Export
                        </button>
                        <span id="filterStatus" class="d-none align-items-center px-4 py-2 rounded badge bg-light text-dark">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span id="filterStatusText">Memuat data...</span>
                        </span>
                    </div>
                </form>
            </div>
        </section>

        <!-- Chart Section -->
        <section class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 fw-bold mb-0">
                        <span class="border-start border-primary border-4 ps-2">Quarterly Trends Analysis</span>
                    </h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-three-dots"></i>
                        </button>
                    </div>
                </div>
                <div id="chartContainer" class="position-relative" style="height: 550px;">
                    <div class="position-absolute top-50 start-50 translate-middle text-center">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 fw-semibold text-muted">Memuat grafik data...</p>
                        <small class="text-muted">Mohon tunggu sebentar</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- Table Section -->
        <!-- Table Section -->      <section class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                <h2 class="h4 fw-bold mb-0">
                    <span class="border-start border-success border-4 ps-2">Detailed Quarterly Data</span>
                </h2>
                <div class="d-flex gap-3">
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <button id="btnDownloadExcel" class="btn btn-sm btn-primary">
                        <i class="bi bi-download me-1"></i>Download Excel
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0" id="dataTable">
                        <thead class="table-dark text-white">
                            <tr>
                                <th rowspan="2" class="align-middle text-white">Grade</th>
                                <th rowspan="2" class="align-middle text-white">Component</th>
                                <th colspan="1" id="periodHeader" class="text-center text-white">Periods</th>
                            </tr>
                            <tr id="periodSubHeader">
                                <!-- Will be populated dynamically with quarters -->
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="100" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-3 fw-semibold text-muted">Memuat data tabel...</p>
                                    <small class="text-muted">Sedang mengambil data dari server</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
</main>

<!-- Material Modal -->
<div class="modal fade" id="materialModal" tabindex="-1" aria-labelledby="materialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="materialModalLabel">Tambah Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="materialForm">
                    <div class="mb-3">
                        <label for="material_grade" class="form-label">Grade <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="material_grade" name="grade" placeholder="Contoh: SUS304" required>
                    </div>
                    <div class="mb-3">
                        <label for="material_shape" class="form-label">Shape <span class="text-danger">*</span></label>
                        <select class="form-select" id="material_shape" name="shape" required>
                            <option value="">-- Pilih Shape --</option>
                            @foreach(($shapes ?? []) as $shape)
                                <option value="{{ $shape->id }}">{{ $shape->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveMaterial">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Currency Modal -->
<div class="modal fade" id="currencyModal" tabindex="-1" aria-labelledby="currencyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="currencyModalLabel">Tambah Data Kurs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="currencyForm">
                    <div class="mb-3">
                        <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                        <select class="form-select" id="currency" name="currency" required>
                            <option value="">-- Pilih Currency --</option>
                            <option value="IDR">IDR (Indonesian Rupiah)</option>
                            <!-- Tambahkan currency lain di sini sesuai kebutuhan -->
                        </select>
                        <small class="text-muted">Pilih mata uang yang ingin ditambahkan</small>
                    </div>
                    <div class="mb-3">
                        <label for="kurs" class="form-label">Nilai Kurs <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="kurs" name="kurs" 
                            placeholder="Contoh: 100.50" required step="0.01" min="0">
                        <small class="text-muted">1 YEN = berapa <span id="selectedCurrency">IDR</span>? (Format: gunakan titik untuk desimal, contoh: 100.50)</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Contoh:</strong> Jika 1 YEN = 100 IDR, maka pilih currency: <code>IDR</code> dan masukkan kurs: <code>100</code>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveCurrency">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Import/Export Modal -->
<div class="modal fade" id="importExportModal" tabindex="-1" aria-labelledby="importExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importExportModalLabel">Import / Export Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importExportForm" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="export_year" class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="export_year" name="export_year" 
                                placeholder="Contoh: 2024" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label for="export_quarter" class="form-label">Kuartal <span class="text-danger">*</span></label>
                            <select class="form-select" id="export_quarter" name="export_quarter" required>
                                <option value="">-- Pilih Kuartal --</option>
                                <option value="Q1">Q1</option>
                                <option value="Q2">Q2</option>
                                <option value="Q3">Q3</option>
                                <option value="Q4">Q4</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Instruksi:</strong> Pilih tahun dan kuartal untuk export atau import data Excel.
                    </div>
                    <div class="row g-3 mb-2">
                        <div class="col-12">
                            <label for="import_file" class="form-label">File Import (Excel) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="import_file" name="import_file" accept=".xlsx,.xls">
                            <small class="text-muted">Gunakan template yang diunduh: header <code>nama material, shape, tahun, quartal, base, alloy, fob, cnf, freight</code>. Kolom base/alloy/fob/cnf/freight dibiarkan kosong saat export.</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btnExportData">
                    <i class="bi bi-download"></i> Export Excel
                </button>
                <button type="button" class="btn btn-primary" id="btnImportData">
                    <i class="bi bi-upload"></i> Import Excel
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script>
console.log('=== BOPM Dashboard Script Starting ===');
console.log('jQuery available?', typeof jQuery !== 'undefined');
console.log('$ available?', typeof $ !== 'undefined');

jQuery(document).ready(function($) {
    console.log('=== jQuery Document Ready ===');
    console.log('$ in ready function:', typeof $ !== 'undefined');

    const today = new Date();
    const currentYear = today.getFullYear();
    let currencyListData = [];
    let filterTimeout = null;
    
    console.log('Current year:', currentYear);

    // Helper function to format date as YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Helper function to get quarter from month (1-12)
    function getQuarterFromMonth(month) {
        if (month >= 1 && month <= 3) return 1;
        if (month >= 4 && month <= 6) return 2;
        if (month >= 7 && month <= 9) return 3;
        return 4; // months 10-12
    }

    // Helper function to calculate and display quarter range from date selection
    function calculateQuarterRange(startDateStr, endDateStr) {
        if (!startDateStr || !endDateStr) return null;
        
        const startDate = new Date(startDateStr);
        const endDate = new Date(endDateStr);
        
        const startYear = startDate.getFullYear();
        const endYear = endDate.getFullYear();
        const startMonth = startDate.getMonth() + 1; // 1-12
        const endMonth = endDate.getMonth() + 1; // 1-12
        
        const startQuarter = getQuarterFromMonth(startMonth);
        const endQuarter = getQuarterFromMonth(endMonth);
        
        // Build quarter range description
        let description = '';
        if (startYear === endYear) {
            description = `Q${startQuarter}-Q${endQuarter} ${startYear}`;
        } else {
            description = `Q${startQuarter}-Q4 ${startYear}`;
            for (let year = startYear + 1; year < endYear; year++) {
                description += `, Q1-Q4 ${year}`;
            }
            description += `, Q1-Q${endQuarter} ${endYear}`;
        }
        
        return {
            startYear: startYear,
            startQuarter: startQuarter,
            endYear: endYear,
            endQuarter: endQuarter,
            description: description
        };
    }

    // Helper function to get quarter date range
    function getQuarterDateRange(year, quarter) {
        let startMonth, endMonth;
        switch(quarter) {
            case 1: startMonth = 0; endMonth = 2; break;  // Jan-Mar
            case 2: startMonth = 3; endMonth = 5; break;  // Apr-Jun
            case 3: startMonth = 6; endMonth = 8; break;  // Jul-Sep
            case 4: startMonth = 9; endMonth = 11; break; // Oct-Dec
        }
        return {
            start: new Date(year, startMonth, 1),
            end: new Date(year, endMonth + 1, 0) // Last day of the month
        };
    }

    // Set default date range to current year (can be changed to any range)
    const startOfYear = new Date(currentYear, 0, 1); // Jan 1
    const endOfYear = new Date(currentYear, 11, 31); // Dec 31
    
    console.log('Setting default dates:', formatDate(startOfYear), 'to', formatDate(endOfYear));
    $('#start_date').val(formatDate(startOfYear));
    $('#end_date').val(formatDate(endOfYear));
    console.log('Dates set in inputs:', $('#start_date').val(), $('#end_date').val());

    // Load currency list on page load
    console.log('Loading currency list...');
    loadCurrencyList();

    // Load initial data
    console.log('Loading initial chart and table data...');
    loadChartData();
    loadTableData();

    // Auto-reload data when filters change - dengan debounce untuk date inputs
    $('#start_date, #end_date').on('change', function() {
        console.log('Date filter changed:', $(this).attr('id'), $(this).val());
        
        // Calculate quarter range for backend
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        
        if (startDate && endDate) {
            const qRange = calculateQuarterRange(startDate, endDate);
            if (qRange) {
                console.log('Quarter Range:', qRange.description);
                console.log('From Q' + qRange.startQuarter + ' ' + qRange.startYear + ' to Q' + qRange.endQuarter + ' ' + qRange.endYear);
            }
        }
        
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            reloadData();
        }, 500); // Debounce 500ms untuk date
    });

    // Instant reload untuk dropdown filters
    $('#material_id').on('change', function() {
        console.log('Material filter changed:', $(this).val());
        reloadData();
    });

    // Multiplier change handler
    $('#multiplier').on('change', function() {
        console.log('Multiplier changed:', $(this).val());
        reloadData();
    });

    // Currency dropdown change handler - update label
    $('#currency').on('change', function() {
        const selectedText = $(this).find(':selected').text();
        const currencyCode = $(this).val();
        $('#selectedCurrency').text(currencyCode || 'IDR');
    });

    // Currency month change handler
    $('#currency_month').on('change', function() {
        console.log('=== Currency month changed ===');
        const selectedMonth = $(this).val();
        const currencySelect = $('#currency_id');
        
        console.log('Selected month:', selectedMonth);
        
        if (!selectedMonth) {
            // No month selected, disable and show placeholder
            currencySelect.html('<option value="">Pilih Bulan Kurs dulu</option>');
            currencySelect.prop('disabled', true);
            currencySelect.val('').trigger('change');
            console.log('No month selected, currency select disabled');
            return;
        }

        const monthData = currencyListData.find(item => item.month_year === selectedMonth);
        console.log('Month data found:', monthData);
        
        if (monthData && monthData.items && monthData.items.length > 0) {
            // Enable dropdown
            currencySelect.prop('disabled', false);
            
            // Show currencies as Phase 1, Phase 2, etc - Sort terbaru di atas
            let options = '<option value="">Default (YEN)</option>';
            const sortedItems = monthData.items.slice().sort(function(a, b) {
                return b.id - a.id; // Sort by ID descending (terbaru = ID lebih besar)
            });
            sortedItems.forEach(function(item, index) {
                const phaseNumber = index + 1;
                // Format kurs with thousand separator
                const kursFormatted = parseFloat(item.kurs).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
                options += `<option value="${item.id}">Phase ${phaseNumber} - ${item.currency} - Rp ${kursFormatted}</option>`;
            });
            currencySelect.html(options);
            currencySelect.val('').trigger('change');
            console.log('Loaded', monthData.items.length, 'currencies as phases (sorted newest first)');
        } else {
            // No currencies for this month, disable and show message
            currencySelect.html('<option value="">Tidak ada kurs untuk bulan ini</option>');
            currencySelect.prop('disabled', true);
            currencySelect.val('').trigger('change');
            console.log('No currencies found for month, currency select disabled');
        }
    });

    // Currency ID change handler - trigger chart reload
    $('#currency_id').on('change', function() {
        console.log('=== Currency ID changed ===', $(this).val());
        reloadData();
    });

    // Reset button
    $('#btnReset').on('click', function() {
        console.log('=== Reset button clicked ===');
        $('#start_date').val(formatDate(startOfYear));
        $('#end_date').val(formatDate(endOfYear));
        $('#material_id').val('all').trigger('change.select2');
        $('#currency_month').val('');
        $('#currency_id').html('<option value="">Pilih Bulan Kurs dulu</option>').val('').prop('disabled', true).trigger('change.select2');
        $('#multiplier').val('1');
        reloadData();
    });

    // Save currency button
    $('#btnSaveCurrency').on('click', function() {
        saveCurrency();
    });

    // Save material button
    $('#btnSaveMaterial').on('click', function() {
        saveMaterial();
    });

    // Download Excel button - exports table data with current filters
    $('#btnDownloadExcel').on('click', function() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const materialId = $('#material_id').val();
        const currencyId = $('#currency_id').val();
        const multiplier = $('#multiplier').val();
        
        if (!startDate || !endDate) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Harap pilih tanggal mulai dan akhir', 'error');
            } else {
                alert('Harap pilih tanggal mulai dan akhir');
            }
            return;
        }
        
        // Calculate quarter range
        const qRange = calculateQuarterRange(startDate, endDate);
        
        // Build export URL with query params
        const baseUrl = '{{ route("bopm.dashboard.export-table") }}';
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
            material_id: materialId === 'all' ? '' : materialId,
            currency_id: currencyId || '',
            multiplier: multiplier || '1',
            start_quarter: qRange ? qRange.startQuarter : '',
            start_year: qRange ? qRange.startYear : '',
            end_quarter: qRange ? qRange.endQuarter : '',
            end_year: qRange ? qRange.endYear : ''
        });
        
        const url = `${baseUrl}?${params.toString()}`;
        console.log('Downloading Excel:', url);
        window.location.href = url;
    });

    // Export button
    $('#btnExportData').on('click', function() {
        const year = $('#export_year').val();
        const quarter = $('#export_quarter').val();
        const baseExportUrl = '{{ route("bopm.dashboard.export-template") }}';
        
        if (!year || !quarter) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Harap pilih tahun dan kuartal', 'error');
            } else {
                alert('Harap pilih tahun dan kuartal');
            }
            return;
        }

        // Download template with query params
        const url = `${baseExportUrl}?year=${encodeURIComponent(year)}&quarter=${encodeURIComponent(quarter)}`;
        console.log('Downloading template:', url);
        window.location.href = url;
    });

    // Import button
    $('#btnImportData').on('click', function() {
        const year = $('#export_year').val();
        const quarter = $('#export_quarter').val();
        const fileInput = document.getElementById('import_file');
        const file = fileInput ? fileInput.files[0] : null;
        const importUrl = '{{ route("bopm.dashboard.import") }}';
        
        if (!year || !quarter) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Harap pilih tahun dan kuartal', 'error');
            } else {
                alert('Harap pilih tahun dan kuartal');
            }
            return;
        }

        if (!file) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Harap pilih file Excel untuk diupload', 'error');
            } else {
                alert('Harap pilih file Excel untuk diupload');
            }
            return;
        }

        const formData = new FormData();
        formData.append('import_file', file);
        formData.append('year', year);
        formData.append('quarter', quarter);
        formData.append('_token', '{{ csrf_token() }}');

        $.ajax({
            url: importUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Tutup modal import/export (Prioritas tutup dulu)
                    const importModalEl = document.getElementById('importExportModal');
                    
                    // Coba ambil instance bootstrap 5
                    let importModalInstance = bootstrap.Modal.getInstance(importModalEl);
                    if (importModalInstance) {
                        importModalInstance.hide();
                    } else {
                        // Fallback ke jQuery
                        $(importModalEl).modal('hide');
                    }

                    // SAFETY: Paksa hapus backdrop jika masih tertinggal (fix untuk layar gelap)
                    setTimeout(() => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css({
                            'overflow': '',
                            'padding-right': ''
                        });
                    }, 300);

                    // Tampilkan notifikasi setelah modal mulai menutup
                    setTimeout(() => {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Berhasil', response.message || 'Data berhasil diimport', 'success');
                        } else {
                            alert(response.message || 'Data berhasil diimport');
                        }
                    }, 200);

                    // Reset form
                    $('#importExportForm')[0].reset();
                    if (fileInput) fileInput.value = '';

                    // Auto reload data
                    setTimeout(function() {
                        reloadData();
                    }, 500);
                } else {
                    const msg = response.message || 'Import gagal. Periksa data atau duplikasi kuartal.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', msg, 'error');
                    } else {
                        alert(msg);
                    }
                }
            },
            error: function(xhr) {
                console.error('Import Error:', xhr.responseText || xhr.statusText);
                let msg = 'Terjadi kesalahan saat import.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', msg, 'error');
                } else {
                    alert(msg);
                }
            }
        });
    });

    function saveMaterial() {
        const grade = $('#material_grade').val();
        const shape = $('#material_shape').val();

        if (!grade || !shape) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Grade dan Shape wajib diisi', 'error');
            } else {
                alert('Grade dan Shape wajib diisi');
            }
            return;
        }

        $.ajax({
            url: '{{ route("bopm.dashboard.material.store") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                grade: grade,
                shape: shape
            },
            success: function(response) {
                if (response.success && response.data) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Berhasil', response.message || 'Material berhasil ditambahkan', 'success');
                    } else {
                        alert(response.message || 'Material berhasil ditambahkan');
                    }

                    // Tambahkan ke dropdown material
                    const option = new Option(response.data.label, response.data.id, false, false);
                    $('#material_id').append(option).trigger('change.select2');

                    // Reset form & tutup modal
                    $('#materialForm')[0].reset();
                    const modalEl = document.getElementById('materialModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        $(modalEl).modal('hide');
                    }
                    
                    // Auto reload data after adding material
                    setTimeout(function() {
                        reloadData();
                    }, 500);
                } else {
                    const msg = response.message || 'Gagal menambah material';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', msg, 'error');
                    } else {
                        alert(msg);
                    }
                }
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan saat menambah material';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', msg, 'error');
                } else {
                    alert(msg);
                }
            }
        });
    }

    // Load currency list
    function loadCurrencyList() {
        $.ajax({
            url: '{{ route("bopm.dashboard.currency.list") }}',
            method: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    currencyListData = response.data;
                    populateCurrencyMonthDropdown();
                }
            },
            error: function(xhr, status, error) {
                console.error('Currency List Error:', xhr, status, error);
            }
        });
    }

    // Populate currency month dropdown - Sort terbaru di atas
    function populateCurrencyMonthDropdown() {
        const monthSelect = $('#currency_month');
        let options = '<option value="">YEN (Default)</option>';
        
        // Sort by month_year descending (terbaru di atas)
        const sortedData = currencyListData.slice().sort(function(a, b) {
            return b.month_year.localeCompare(a.month_year);
        });
        
        sortedData.forEach(function(item) {
            options += `<option value="${item.month_year}">${item.month_name} (${item.count} kurs)</option>`;
        });
        
        monthSelect.html(options);
    }

    // Enhance selects with search
    $('#material_id').select2({
        placeholder: 'Pilih material',
        allowClear: true,
        width: '100%'
    });
    $('#currency_id').select2({
        placeholder: 'Pilih kurs',
        allowClear: true,
        width: '100%'
    });
    $('#material_shape').select2({
        placeholder: 'Pilih shape',
        allowClear: true,
        dropdownParent: $('#materialModal'),
        width: '100%'
    });

    // Function to reload data with loading indicator
    function reloadData() {
        console.log('=== Reloading data dynamically ===');
        showLoadingIndicator();
        loadChartData();
        loadTableData();
    }

    // Show loading indicator
    function showLoadingIndicator() {
        $('#filterStatus').removeClass('d-none bg-success bg-danger bg-light')
            .addClass('d-flex bg-warning text-dark')
            .find('#filterStatusText').text('Sedang memuat data dari server...');
        $('#filterStatus .spinner-border').show();
    }
    
    // Hide loading indicator with success message (GREEN)
    function hideLoadingIndicator() {
        $('#filterStatus').removeClass('bg-warning bg-danger bg-light text-dark')
            .addClass('bg-success text-white')
            .find('#filterStatusText').text('Data berhasil dimuat!');
        $('#filterStatus .spinner-border').hide();
        setTimeout(function() {
            $('#filterStatus').addClass('d-none').removeClass('d-flex');
        }, 2000);
    }

    // Show error indicator (RED)
    function showErrorIndicator(message) {
        $('#filterStatus').removeClass('bg-warning bg-success bg-light text-dark')
            .addClass('d-flex bg-danger text-white')
            .find('#filterStatusText').text(message || 'Gagal memuat data!');
        $('#filterStatus .spinner-border').hide();
        setTimeout(function() {
            $('#filterStatus').addClass('d-none').removeClass('d-flex');
        }, 3000);
    }

    // Save currency
    function saveCurrency() {
        const currency = $('#currency').val();
        const kurs = $('#kurs').val();

        if (!currency || !kurs) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Harap isi semua field', 'error');
            } else {
                alert('Harap isi semua field');
            }
            return;
        }

        $.ajax({
            url: '{{ route("bopm.dashboard.currency.save") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                currency: currency,
                kurs: kurs
            },
            success: function(response) {
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Berhasil', 'Data kurs berhasil disimpan', 'success');
                    } else {
                        alert('Data kurs berhasil disimpan');
                    }
                    
                    // Close modal properly
                    const currencyModalElement = document.getElementById('currencyModal');
                    const currencyModalInstance = bootstrap.Modal.getInstance(currencyModalElement);
                    if (currencyModalInstance) {
                        currencyModalInstance.hide();
                    } else {
                        $(currencyModalElement).modal('hide');
                    }
                    
                    // Reset form
                    $('#currencyForm')[0].reset();
                    $('#currency').val('').change();
                    
                    // Reload currency list
                    loadCurrencyList();
                    
                    // Auto reload data after saving currency
                    setTimeout(function() {
                        reloadData();
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                console.error('Save Currency Error:', xhr, status, error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal menyimpan data kurs', 'error');
                } else {
                    alert('Gagal menyimpan data kurs');
                }
            }
        });
    }

    // Load chart data
    function loadChartData() {
        console.log('=== loadChartData START ===');
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const materialId = $('#material_id').val();
        const currencyId = $('#currency_id').val();
        const multiplier = $('#multiplier').val();
        
        console.log('Chart data params:', { startDate, endDate, materialId, currencyId, multiplier });

        // Validate dates
        if (!startDate || !endDate) {
            console.error('Dates are missing!');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Tanggal mulai dan tanggal akhir harus diisi', 'error');
            }
            return;
        }

        // Show loading with smooth transition
        $('#chartContainer').html('<div class="position-absolute top-50 start-50 translate-middle text-center"><div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Loading...</span></div><p class="mt-3 fw-semibold text-muted">Memuat grafik data...</p><small class="text-muted">Sedang memproses filter Anda</small></div>');

        // Build data object, only include non-empty values
        const ajaxData = {
            start_date: startDate,
            end_date: endDate,
            multiplier: multiplier || '1' // Always send multiplier
        };
        
        // Calculate quarter range and add to request
        const qRange = calculateQuarterRange(startDate, endDate);
        if (qRange) {
            ajaxData.start_quarter = qRange.startQuarter;
            ajaxData.start_year = qRange.startYear;
            ajaxData.end_quarter = qRange.endQuarter;
            ajaxData.end_year = qRange.endYear;
            console.log('Including quarter range in request:', qRange.description);
        }
        
        if (materialId && materialId !== 'all') {
            ajaxData.material_id = materialId;
        }
        
        if (currencyId && currencyId !== 'yen') {
            ajaxData.currency_id = currencyId;
        }

        console.log('Chart AJAX data being sent:', ajaxData);

        $.ajax({
            url: '{{ route("bopm.dashboard.chart") }}',
            method: 'GET',
            data: ajaxData,
            success: function(response) {
                console.log('Chart Response:', response);
                if (response.success && response.data) {
                    renderChart(response.data);
                    hideLoadingIndicator();
                } else {
                    $('#chartContainer').html('<div class="text-center p-5 text-danger">Gagal memuat data chart</div>');
                    showErrorIndicator(response.message || 'Gagal memuat data chart');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', response.message || 'Gagal memuat data chart', 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('=== Chart AJAX Error ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('XHR Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                console.error('Response JSON:', xhr.responseJSON);
                
                $('#chartContainer').html('<div class="text-center p-5 text-danger">Terjadi kesalahan saat memuat data chart</div>');
                let errorMsg = 'Terjadi kesalahan saat memuat data chart';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                    console.error('Error message from server:', errorMsg);
                }
                showErrorIndicator(errorMsg);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', errorMsg, 'error');
                }
            }
        });
    }

    // Render Highcharts - Simple structure like example
    function renderChart(data) {
        console.log('=== renderChart START ===');
        console.log('Chart data received:', data);
        console.log('Currency symbol:', data.currency_symbol);
        console.log('Currency name:', data.currency_name);
        
        if (!data || !data.series || !data.categories) {
            $('#chartContainer').html('<div class="flex items-center justify-center h-full text-yellow-600 dark:text-yellow-400 font-medium">Tidak ada data untuk ditampilkan</div>');
            return;
        }

        // Define colors
        const seriesColors = {
            'Base': '#1f77b4',        // Dark blue
            'CNF': '#9467bd',          // Purple
            'Alloy': '#d62728',        // Red
            'FOB': '#2ca02c',          // Green
            'Freight': '#17becf'       // Light blue/Cyan
        };

        // Define order: Base, FOB, CNF, Alloy, Freight (Alloy & Freight grouped at end)
        const seriesOrder = ['Base', 'FOB', 'CNF', 'Alloy', 'Freight'];
        
        // Sort series according to desired order
        const sortedSeries = data.series.slice().sort(function(a, b) {
            const indexA = seriesOrder.indexOf(a.name);
            const indexB = seriesOrder.indexOf(b.name);
            if (indexA === -1) return 1;
            if (indexB === -1) return -1;
            return indexA - indexB;
        });

        // Prepare series data
        const seriesData = sortedSeries.map(function(serie) {
            return {
                name: serie.name,
                data: serie.data,
                yAxis: serie.yAxis || 0,
                color: seriesColors[serie.name] || null
            };
        });

        // Calculate min and max for Y-axis LEFT (Base, FOB, CNF)
        let leftAxisValues = [];
        data.series.forEach(function(serie) {
            if (['Base', 'FOB', 'CNF'].includes(serie.name)) {
                serie.data.forEach(function(value) {
                    if (value !== null && value !== undefined) {
                        leftAxisValues.push(value);
                    }
                });
            }
        });
        
        // Calculate min and max for Y-axis RIGHT (Alloy, Freight)
        let rightAxisValues = [];
        data.series.forEach(function(serie) {
            if (['Alloy', 'Freight'].includes(serie.name)) {
                serie.data.forEach(function(value) {
                    if (value !== null && value !== undefined) {
                        rightAxisValues.push(value);
                    }
                });
            }
        });
        
        // Calculate axis ranges independently
        const leftMinValue = leftAxisValues.length > 0 ? Math.min(...leftAxisValues) : 0;
        const leftMaxValue = leftAxisValues.length > 0 ? Math.max(...leftAxisValues) : 1000;
        const leftPadding = (leftMaxValue - leftMinValue) * 0.1;
        const leftYAxisMin = Math.floor((leftMinValue - leftPadding) / 100) * 100;
        const leftYAxisMax = Math.ceil((leftMaxValue + leftPadding) / 100) * 100;
        
        const rightMinValue = rightAxisValues.length > 0 ? Math.min(...rightAxisValues) : 0;
        const rightMaxValue = rightAxisValues.length > 0 ? Math.max(...rightAxisValues) : 100;
        const rightPadding = (rightMaxValue - rightMinValue) * 0.1;
        const rightYAxisMin = Math.floor((rightMinValue - rightPadding) / 100) * 100;
        const rightYAxisMax = Math.ceil((rightMaxValue + rightPadding) / 100) * 100;

        // Highcharts configuration - Simple like example
        try {
            Highcharts.chart('chartContainer', {
                chart: {
                    type: 'line'
                },
                title: {
                    text: null
                },
                xAxis: {
                    categories: data.categories || []
                },
                yAxis: [
                    {
                        title: {
                            text: null
                        },
                        labels: {
                            format: (data.currency_symbol || '¥') + '{value}'
                        },
                        min: leftYAxisMin,
                        max: leftYAxisMax,
                        opposite: false
                    },
                    {
                        title: {
                            text: null
                        },
                        labels: {
                            format: (data.currency_symbol || '¥') + '{value}'
                        },
                        min: rightYAxisMin,
                        max: rightYAxisMax,
                        opposite: true
                    }
                ],
                tooltip: {
                    shared: false,
                    formatter: function() {
                        if (!this.point || !this.series) {
                            return '';
                        }
                        
                        const currencyId = document.getElementById('currency_id').value;
                        const currencySymbol = currencyId === 'yen' ? '¥' : getCurrencySymbol();
                        const value = this.y !== null && this.y !== undefined 
                            ? currencySymbol + ' ' + parseFloat(this.y).toLocaleString('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            })
                            : '-';
                        
                        let tooltip = '<b>' + this.x + '</b><br/>';
                        tooltip += '<span style="color:' + this.color + '">●</span> ' + 
                                   this.series.name + ': <b>' + value + '</b>';
                        
                        return tooltip;
                    }
                },
                legend: {
                    layout: 'horizontal',
                    align: 'center',
                    verticalAlign: 'bottom'
                },
                plotOptions: {
                    line: {
                        dataLabels: {
                            enabled: false
                        },
                        enableMouseTracking: true
                    }
                },
                series: seriesData,
                credits: {
                    enabled: false
                }
            });
        } catch (error) {
            console.error('Highcharts Error:', error);
            document.getElementById('chartContainer').innerHTML = '<div class="text-center p-5 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error rendering chart: ' + error.message + '</div>';
        }
    }

    // Load table data
    function loadTableData() {
        console.log('=== loadTableData START ===');
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const materialId = $('#material_id').val();
        const currencyId = $('#currency_id').val();
        const multiplier = $('#multiplier').val();
        
        console.log('Table data params:', { startDate, endDate, materialId, currencyId, multiplier });

        // Validate dates
        if (!startDate || !endDate) {
            console.error('Dates are missing!');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Tanggal mulai dan tanggal akhir harus diisi', 'error');
            }
            return;
        }

        // Show loading with smooth transition
        $('#tableBody').html('<tr><td colspan="100" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 fw-semibold text-muted">Memuat data tabel...</p><small class="text-muted">Sedang memproses filter Anda</small></td></tr>');

        // Build data object, only include non-empty values
        const ajaxData = {
            start_date: startDate,
            end_date: endDate,
            multiplier: multiplier || '1' // Always send multiplier
        };
        
        // Calculate quarter range and add to request
        const qRange = calculateQuarterRange(startDate, endDate);
        if (qRange) {
            ajaxData.start_quarter = qRange.startQuarter;
            ajaxData.start_year = qRange.startYear;
            ajaxData.end_quarter = qRange.endQuarter;
            ajaxData.end_year = qRange.endYear;
            console.log('Including quarter range in table request:', qRange.description);
        }
        
        if (materialId && materialId !== 'all') {
            ajaxData.material_id = materialId;
        }
        
        if (currencyId && currencyId !== 'yen') {
            ajaxData.currency_id = currencyId;
        }
        
        console.log('Table AJAX data being sent:', ajaxData);

        $.ajax({
            url: '{{ route("bopm.dashboard.table") }}',
            method: 'GET',
            data: ajaxData,
            success: function(response) {
                console.log('Table Response:', response);
                if (response.success && response.data) {
                    renderTable(response.data);
                    hideLoadingIndicator();
                } else {
                    $('#tableBody').html('<tr><td colspan="100%" class="text-center text-danger">Gagal memuat data tabel</td></tr>');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', response.message || 'Gagal memuat data tabel', 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('=== Table AJAX Error ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('XHR Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                console.error('Response JSON:', xhr.responseJSON);
                
                $('#tableBody').html('<tr><td colspan="100%" class="text-center text-danger">Terjadi kesalahan saat memuat data tabel</td></tr>');
                let errorMsg = 'Terjadi kesalahan saat memuat data tabel';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                    console.error('Error message from server:', errorMsg);
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', errorMsg, 'error');
                }
            }
        });
    }

    // Generate random color for year
    function generateYearColor(year) {
        // Use year as seed for consistent color
        const colors = [
            '#e8f4f8', // Light Blue
            '#f0e8f8', // Light Purple
            '#f8f0e8', // Light Orange
            '#e8f8f0', // Light Green
            '#f8e8f4', // Light Pink
            '#f8f8e8', // Light Yellow
            '#e8e8f8', // Light Lavender
            '#f8e8e8', // Light Red
            '#e8f8e8', // Light Mint
            '#f4f8e8'  // Light Lime
        ];
        const index = (year % colors.length);
        return colors[index];
    }
    
    // Extract year from period string (e.g., "Q1 2023" -> 2023)
    function extractYear(periodString) {
        const match = periodString.match(/(\d{4})/);
        return match ? parseInt(match[1]) : 0;
    }

    // Render table - Format seperti foto (Grade vertical, Period horizontal)
    function renderTable(data) {
        if (!data || data.length === 0) {
            $('#tableBody').html('<tr><td colspan="100" class="text-center py-4 text-muted"><i class="bi bi-inbox me-2"></i>Tidak ada data</td></tr>');
            return;
        }

        // Build period headers from first row
        const firstRow = data[0];
        const periods = firstRow.quarters || [];
        
        // Update period header colspan
        $('#periodHeader').attr('colspan', periods.length);
        
        // Build period sub-header dengan warna berbeda per tahun
        let subHeaderHtml = '';
        let yearColorMap = {};
        let colorIndex = 0;
        const yearColors = ['table-success', 'table-info', 'table-warning', 'table-primary'];
        
        periods.forEach(function(period, index) {
            const year = extractYear(period.period);
            
            // Assign color to year if not yet assigned
            if (!yearColorMap[year]) {
                yearColorMap[year] = yearColors[colorIndex % yearColors.length];
                colorIndex++;
            }
            
            subHeaderHtml += '<th class="text-center text-white ' + yearColorMap[year] + '">' + period.period + '</th>';
        });
        $('#periodSubHeader').html(subHeaderHtml);

        // Build table body
        let tbodyHtml = '';
        
        data.forEach(function(material) {
            const rowCount = 5;
            
            // Create year color map for consistent coloring
            let yearColorMap = {};
            let colorIndex = 0;
            const yearColors = ['table-success', 'table-info', 'table-warning', 'table-primary'];
            
            material.quarters.forEach(function(quarter) {
                const year = extractYear(quarter.period);
                if (!yearColorMap[year]) {
                    yearColorMap[year] = yearColors[colorIndex % yearColors.length];
                    colorIndex++;
                }
            });
            
            // Row 1: Base
            tbodyHtml += '<tr>';
            tbodyHtml += '<td rowspan="' + rowCount + '" class="fw-bold align-top">' + (material.grade || '-') + '</td>';
            tbodyHtml += '<td class="fw-semibold">Base</td>';
            material.quarters.forEach(function(quarter, idx) {
                const year = extractYear(quarter.period);
                const bgClass = yearColorMap[year];
                tbodyHtml += '<td class="text-end ' + bgClass + '">' + formatNumber(quarter.base) + '</td>';
            });
            tbodyHtml += '</tr>';
            
            // Row 2: Alloy Surcharge
            tbodyHtml += '<tr>';
            tbodyHtml += '<td class="text-muted">Alloy Surcharge</td>';
            material.quarters.forEach(function(quarter, idx) {
                const value = formatNumber(quarter.alloy);
                const year = extractYear(quarter.period);
                const textClass = value === '-' ? 'text-muted' : '';
                const bgClass = yearColorMap[year] ? yearColorMap[year] + ' bg-opacity-25' : '';
                tbodyHtml += '<td class="text-end ' + textClass + ' ' + bgClass + '">' + value + '</td>';
            });
            tbodyHtml += '</tr>';
            
            // Row 3: FOB (highlighted)
            tbodyHtml += '<tr>';
            tbodyHtml += '<td class="text-muted">FOB (Total)</td>';
            material.quarters.forEach(function(quarter, idx) {
                const year = extractYear(quarter.period);
                const bgClass = yearColorMap[year];
                tbodyHtml += '<td class="text-end text-muted ' + bgClass + '">' + formatNumber(quarter.fob) + '</td>';
            });
            tbodyHtml += '</tr>';
            
            // Row 4: CNF
            tbodyHtml += '<tr class="fw-bold">';
            tbodyHtml += '<td class="table-secondary">CNF</td>';
            material.quarters.forEach(function(quarter, idx) {
                const year = extractYear(quarter.period);
                const bgClass = yearColorMap[year] ? yearColorMap[year] + ' bg-opacity-25' : '';
                tbodyHtml += '<td class="text-end fw-bold ' + bgClass + '">' + formatNumber(quarter.cnf) + '</td>';
            });
            tbodyHtml += '</tr>';
            
            // Row 5: Freight
            tbodyHtml += '<tr>';
            tbodyHtml += '<td class="text-muted">Freight</td>';
            material.quarters.forEach(function(quarter, idx) {
                const year = extractYear(quarter.period);
                const bgClass = yearColorMap[year] ? yearColorMap[year] + ' bg-opacity-25' : '';
                tbodyHtml += '<td class="text-end text-muted ' + bgClass + '">' + formatNumber(quarter.freight) + '</td>';
            });
            tbodyHtml += '</tr>';
        });
        
        $('#tableBody').html(tbodyHtml);
    }

    // Get currency symbol from selected currency
    function getCurrencySymbol() {
        const currencyId = document.getElementById('currency_id').value;
        if (currencyId === 'yen') return '¥';
        
        const selectedOption = document.getElementById('currency_id').selectedOptions[0];
        if (selectedOption) {
            const text = selectedOption.text;
            const match = text.match(/- ([A-Z]{3})/);
            return match ? match[1] : '';
        }
        return '';
    }

    // Format number
    function formatNumber(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }
        return parseFloat(value).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});
</script>

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa;
    }
    
    body.dark-mode {
        background-color: #212529;
        color: #f8f9fa;
    }
    
    body.dark-mode .card {
        background-color: #343a40;
        border-color: #495057;
        color: #f8f9fa;
    }
    
    body.dark-mode .card-header {
        background-color: #2c3136 !important;
        border-color: #495057;
    }
    
    body.dark-mode .form-control,
    body.dark-mode .form-select {
        background-color: #495057;
        border-color: #6c757d;
        color: #f8f9fa;
    }
    
    body.dark-mode .form-control:focus,
    body.dark-mode .form-select:focus {
        background-color: #495057;
        border-color: #0d6efd;
        color: #f8f9fa;
    }
    
    body.dark-mode .table {
        color: #f8f9fa;
    }
    
    body.dark-mode .table-bordered {
        border-color: #495057;
    }
    
    body.dark-mode .table > :not(caption) > * > * {
        border-color: #495057;
    }
    
    body.dark-mode .btn-outline-secondary {
        color: #adb5bd;
        border-color: #6c757d;
    }
    
    body.dark-mode .btn-outline-secondary:hover {
        background-color: #495057;
        border-color: #6c757d;
        color: #f8f9fa;
    }
    
    body.dark-mode .text-muted {
        color: #adb5bd !important;
    }
    
    body.dark-mode .modal-content {
        background-color: #343a40;
        color: #f8f9fa;
    }
    
    body.dark-mode .modal-header,
    body.dark-mode .modal-footer {
        border-color: #495057;
    }
    
    .theme-icon-light {
        display: none;
    }
    
    body.dark-mode .theme-icon-dark {
        display: none;
    }
    
    body.dark-mode .theme-icon-light {
        display: inline-block;
    }
    
    /* Select2 Bootstrap integration */
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 0.375rem !important;
    }
    
    body.dark-mode .select2-container .select2-selection--single {
        background-color: #495057 !important;
        border-color: #6c757d !important;
        color: #f8f9fa !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
    }
    
    body.dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #f8f9fa;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    
    body.dark-mode .select2-dropdown {
        background-color: #343a40;
        border-color: #495057;
    }
    
    body.dark-mode .select2-results__option {
        color: #f8f9fa;
    }
    
    body.dark-mode .select2-results__option--highlighted {
        background-color: #0d6efd !important;
    }
    
    /* Sticky table headers */
    .table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #212529;
    }
    
    /* Table hover effects */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.075);
    }
    
    body.dark-mode .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.075);
    }
    
    /* Smooth transitions */
    body, .card, .btn, .form-control, .form-select {
        transition: background-color 0.2s, color 0.2s, border-color 0.2s;
    }
</style>
