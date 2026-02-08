@extends('layout')

@section('content')

<main id="main" class="main">
    <div class="pagetitle">
        <h1>CRM Report</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Sales</li>
                <li class="breadcrumb-item active">CRM Report</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">CRM Report</h5>

                        <form id="filterForm" class="row g-3 mb-3">
                            <div class="col-12">
                                <div class="row g-3">
                                    <div class="col-auto">
                                        <label class="form-label">Plan Visit (from)</label>
                                        <input type="date" name="planStartDate" id="planStart" class="form-control">
                                    </div>
                                    <div class="col-auto">
                                        <label class="form-label">to</label>
                                        <input type="date" name="planEndDate" id="planEnd" class="form-control">
                                    </div>
                                    <div class="col-auto">
                                        <label class="form-label">Customer Name</label>
                                        <select id="companyFilter" class="form-select">
                                            <option value="">All</option>
                                            @foreach($companies as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto align-self-end">
                                        <a id="btnExportPlan" href="#" class="btn btn-success">Export PvsV .xlsx</a>
                                    </div>                                    
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="row g-3 align-items-end">
                                    <div class="col-auto">
                                        <label class="form-label">Visit Date (from)</label>
                                        <input type="date" name="visitStartDate" id="visitStart" class="form-control">
                                    </div>
                                    <div class="col-auto">
                                        <label class="form-label">to</label>
                                        <input type="date" name="visitEndDate" id="visitEnd" class="form-control">
                                    </div>
                                    <div class="col-auto">
                                        <label class="form-label">Customer Name</label>
                                        <select id="companyFilterVisit" class="form-select">
                                            <option value="">All</option>
                                            @foreach($companies as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto align-self-end">
                                        <a id="btnExportVisit" href="#" class="btn btn-primary ms-2">Export as Visit .xlsx</a>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Toolbar kustom: gabungkan Page length dan Search (sembunyikan kontrol default DataTables) -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <label class="mb-0">Show</label>
                                <select id="page-length" class="form-select form-select-sm" style="width:80px">
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <label class="text-muted ms-1">entries</label>
                            </div>
                            <div class="d-flex align-items-center">
                                <input id="crm-search" class="form-control form-control-sm" placeholder="Search...">
                            </div>
                        </div>

                        <style>
                            /* Hide built-in DataTables length & filter when using custom toolbar */
                            .dataTables_length, .dataTables_filter { display: none !important; }
                            /* Highlight custom search box */
                            .search-box { min-width:220px; }
                            .search-box .input-group-text { background: transparent; border-right: 0; }
                            .search-box .form-control { border-left: 0; box-shadow: none; }
                        </style>
                        <div class="table-responsive">
                            <table id="crmTable" class="datatables datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Salesperson</th>
                                        <th>Customer Name</th>
                                        <th>New Customer Name</th>
                                        <th>PIC Customer</th>
                                        <th>Plan Visit</th>
                                        <th>Keterangan</th>
                                        <th>Visit Date</th>
                                        <th>Visit Result</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    // Tunggu sampai halaman sepenuhnya ter-load (termasuk skrip layout yang mungkin memuat jQuery kemudian)
    window.addEventListener('load', function(){
        // Helper untuk memuat skrip secara berurutan jika diperlukan
        function loadScript(url) {
            return new Promise(function(resolve, reject) {
                // Jika DataTables sudah tersedia dan ini adalah script-nya, selesaikan segera
                if (url.includes('jquery.dataTables') && typeof $.fn.DataTable !== 'undefined') {
                    return resolve();
                }
                // Gunakan script DOM biasa untuk menghindari ketergantungan pada jQuery yang mungkin ditimpa
                var s = document.createElement('script');
                s.src = url;
                s.onload = function(){ resolve(); };
                s.onerror = function(){ reject(new Error('Failed loading ' + url)); };
                document.head.appendChild(s);
            });
        }

        // Siapkan skrip yang akan dimuat: pastikan jQuery dulu (jika belum ada), lalu DataTables
        var tasks = [];
        if (typeof window.jQuery === 'undefined') {
            // Load modern jQuery if page doesn't have it
            tasks.push(loadScript('https://code.jquery.com/jquery-3.6.4.min.js'));
        }

        // Load DataTables (core and bootstrap adapter) if missing
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
            tasks.push(loadScript('https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js'));
        }
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn === 'undefined' || typeof window.jQuery.fn.dataTable === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
            tasks.push(loadScript('https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js'));
        }

        Promise.all(tasks).then(function(){
            // Inisialisasi DataTable (server-side) setelah skrip tersedia
            initCrmDataTable();
        }).catch(function(err){
            console.error('Gagal memuat skrip DataTables:', err);
        });

        // Jika tidak ada yang perlu dimuat dan plugin sudah hadir, inisialisasi segera
        if (tasks.length === 0) {
            initCrmDataTable();
        }

        function initCrmDataTable() {
            if (typeof window.jQuery === 'undefined') {
                console.error('jQuery tidak ditemukan saat inisialisasi DataTable; mencoba lagi.');
                return setTimeout(initCrmDataTable, 100);
            }
            const $ = window.jQuery;
            // Inisialisasi DataTable: sembunyikan kontrol length/filter default (kita gunakan toolbar kustom)
            var initialLen = parseInt(document.getElementById('page-length')?.value || 15, 10);
            const table = $('#crmTable').DataTable({
            processing: true,
            serverSide: true,
            dom: 'rtip',
            pageLength: initialLen,
            lengthMenu: [[10,15,25,50,100],[10,15,25,50,100]],
            ajax: {
                url: '{{ route("sales.crm.data") }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: function(d){
                    d.planStartDate = $('#planStart').val();
                    d.planEndDate = $('#planEnd').val();
                    d.visitStartDate = $('#visitStart').val();
                    d.visitEndDate = $('#visitEnd').val();
                    d.planCompanyFilter = $('#companyFilter').val();
                    d.visitCompanyFilter = $('#companyFilterVisit').val();
                }
            },
            columns: [
                { data: null, orderable: false, searchable: false, render: function(data, type, row, meta){ return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'sales_name', name: 'sales_name' },
                { data: 'company', name: 'company' },
                { data: null, name: 'new_customer_name', orderable: false, searchable: false, render: function(){ return '-'; } },
                { data: 'pic_cust', name: 'pic_cust' },
                { data: 'plan_date', name: 'plan_date' },
                { data: 'keterangan', name: 'keterangan' },
                { data: 'visit_date', name: 'visit_date' },
                { data: 'visit_result', name: 'visit_result' },
                { data: 'remark', name: 'remark', render: function(data){ if(!data || data === '-') return '-'; return '<span class="badge bg-secondary">'+data+'</span>'; } }
            ],
            order: [[1, 'asc']],
            language: {
                processing: 'Loading...'
            }
        });

        // Sinkronkan select page-length kustom dengan DataTable
        (function(){
            var $page = $('#page-length');
            // set initial to DataTable value
            $page.val(table.page.len());
            $page.on('change', function(){
                var val = parseInt(this.value, 10) || 10;
                table.page.len(val).draw(false);
            });

            // Hubungkan pencarian kustom (debounce) ke DataTable
            var searchInput = document.getElementById('crm-search');
            var deb = (function(fn, wait){ var t; return function(){ var args=arguments; clearTimeout(t); t=setTimeout(function(){ fn.apply(null,args); }, wait); }; })(function(){ table.search(searchInput.value).draw(); }, 400);
            searchInput.addEventListener('input', deb);

            // Saat DataTable mengubah length di tempat lain, sinkronkan kembali
            table.on('length.dt', function(e, settings, len){ $page.val(len); });
        })();

        // Muat ulang DataTable otomatis saat filter berubah
        $('#planStart, #planEnd, #visitStart, #visitEnd, #companyFilter, #companyFilterVisit').on('change', function(){
            table.ajax.reload();
        });

        // Input pencarian dengan debounce untuk mengurangi permintaan saat mengetik
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(function(){ func.apply(context, args); }, wait);
            };
        }

        // Lampirkan pencarian debounced setelah DataTable diinisialisasi untuk menghindari masalah timing
        table.on('init.dt', function(){
            const dtSearchInput = $('#crmTable_filter input');
            if (dtSearchInput.length) {
                // remove default handlers attached by DataTables
                dtSearchInput.off();
                dtSearchInput.on('keyup', debounce(function() {
                    if (table && typeof table.search === 'function') {
                        table.search(this.value).draw();
                    }
                }, 400));
            }
        });

        // Helper untuk menampilkan tooltip validasi browser native pada sebuah input
        function showNativeRequired($el) {
            var el = $el && $el[0] ? $el[0] : $el;
            if (!el) return;
            // Temporarily mark required so browser shows native tooltip
            el.setAttribute('required', '');
            // focus first so tooltip is shown in supported browsers
            try { el.focus(); } catch (e) {}
            // reportValidity will trigger the native tooltip
            el.reportValidity();
            // Remove required after a short delay so tooltip can appear
            setTimeout(function(){
                try { el.removeAttribute('required'); } catch(e) {}
            }, 7000);
        }

        // Export - Hanya Plan (param visit sengaja dikosongkan)
        $('#btnExportPlan').on('click', function(e){
            e.preventDefault();
            const planStartDate = $('#planStart').val();
            const planEndDate = $('#planEnd').val();
            const planCompany = $('#companyFilter').val();
            if (!planStartDate || !planEndDate) {
                // tunjukkan tooltip native pada field pertama yang kosong
                if (!planStartDate) { showNativeRequired($('#planStart')); }
                else { showNativeRequired($('#planEnd')); }
                return;
            }
            const params = new URLSearchParams({planStartDate, planEndDate, visitStartDate: '', visitEndDate: '', planCompanyFilter: planCompany, exportType: 'plan'});
            window.location = '{{ route("sales.crm.export") }}' + '?' + params.toString();
        });

        // Export - Hanya Visit (param plan sengaja dikosongkan)
        $('#btnExportVisit').on('click', function(e){
            e.preventDefault();
            const visitStartDate = $('#visitStart').val();
            const visitEndDate = $('#visitEnd').val();
            const visitCompany = $('#companyFilterVisit').val();
            if (!visitStartDate || !visitEndDate) {
                if (!visitStartDate) { showNativeRequired($('#visitStart')); }
                else { showNativeRequired($('#visitEnd')); }
                return;
            }
            const params = new URLSearchParams({planStartDate: '', planEndDate: '', visitStartDate, visitEndDate, visitCompanyFilter: visitCompany, exportType: 'visit'});
            window.location = '{{ route("sales.crm.export") }}' + '?' + params.toString();
        });
        }
    });
</script>

@endsection
