@extends('layout')

<script src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <nav>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section dashboard">
            <div class="row">
                <h3 style="display: flex;
            justify-content: center;">Dashboard Repair Maintenance</h3>
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card sales-card">
                        <div class="card-body">
                            <h5 class="card-title">Open <span></span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    @if ($openCount > 0)
                                        <i class="bi bi-envelope-open-fill"></i>
                                    @else
                                        <i class="bi bi-envelope-open-fill"></i>
                                    @endif
                                </div>
                                <div class="ps-3">
                                    <h2>{{ $openCount }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Sales Card Today -->

                <!-- Sales Card This Month -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card sales-card">
                        <div class="card-body">
                            <h5 class="card-title">On Progress <span></span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    @if ($onProgressCount > 0)
                                        <i class="bi bi-hourglass-split "></i>
                                    @else
                                        <i class="bi bi-hourglass-split "></i>
                                    @endif
                                </div>
                                <div class="ps-3">
                                    <h2>{{ $onProgressCount }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Sales Card This Month -->

                <!-- Revenue Card Today -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card sales-card">
                        <div class="card-body">
                            <h5 class="card-title">Finish <span></span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    @if ($finishCount > 0)
                                        <i class="bi bi-check2-all"></i>
                                    @else
                                        <i class="bi bi-check2-all"></i>
                                    @endif
                                </div>
                                <div class="ps-3">
                                    <h2>{{ $finishCount }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Revenue Card Today -->

                <!-- Revenue Card This Month -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card sales-card">
                        <div class="card-body">
                            <h5 class="card-title">Closed <span></span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    @if ($closedCount > 0)
                                        <i class="bi bi-x-circle-fill"></i>
                                    @else
                                        <i class="bi bi-x-circle-fill"></i>
                                    @endif
                                </div>
                                <div class="ps-3">
                                    <h2>{{ $closedCount }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Revenue Card This Month -->
            </div>

            <div class="row">
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div id="chartCutting" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div id="chartMachining" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div id="chartHeatTreatment" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div id="chartMachiningCustom" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <div id="summaryHighcharts" style="width: 100%; height: 400px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Linestop (Dalam Menit)</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="yearDropdown">Pilih Tahun:</label>
                                    <select id="date-dropdown2" class="form-control" onchange="updateChart2()">
                                        @foreach ($years2 as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="sectionDropdown">Pilih Section:</label>
                                    <select id="section-dropdown" class="form-control" onchange="updateChart2()">
                                        <option value="All" selected>All</option> <!-- Default option -->
                                        @foreach ($sections as $section)
                                            <option value="{{ $section }}">{{ $section }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="repairMaintenance" style="width: 100%; height: auto;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card" style="height: 560px; overflow: hidden;">
                        <div class="card-body">
                            <h5 class="card-title">Linestop (Dalam Menit)</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="start_month2">Bulan Mulai:</label>
                                        <input type="date" id="start_month2" name="start_month2" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="end_month2">Bulan Akhir:</label>
                                        <input type="date" id="end_month2" name="end_month2" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div id="periodeRepair" style="width: 100%; height: 400px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div id="highcharts-container" class="card">
                        <div class="card-body">
                            <h5 class="card-title">Detail Linestop / Mesin (Dalam Menit)</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="sectionDropdown">Pilih Section:</label>
                                    <select id="section-dropdown2" class="form-control"
                                        onchange="updateChartPeriodeMesin()">
                                        <option value="All" selected>All</option>
                                        <!-- Default option with "selected" attribute -->
                                        @foreach ($sections as $section)
                                            <option value="{{ $section }}">{{ $section }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="start_mesin">Bulan Mulai:</label>
                                    <input type="date" id="start_mesin" name="start_mesin" class="form-control"
                                        onchange="updateChartPeriodeMesin()">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_mesin">Bulan Akhir:</label>
                                    <input type="date" id="end_mesin" name="end_mesin" class="form-control"
                                        onchange="updateChartPeriodeMesin()">
                                </div>
                            </div>
                            <div id="periodeRepairMesin" style="width: 100%; height: 400px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                <div class="col-sm-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Linestop Alat Bantu (Dalam Menit)</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="yearDropdown">Pilih Tahun:</label>
                                    <select id="date-dropdown-alat" class="form-control" onchange="updateChartAlat()">
                                        @foreach ($years2 as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="sectionDropdown">Pilih Section:</label>
                                    <select id="section-dropdown-alat" class="form-control" onchange="updateChartAlat()">
                                        <option value="All" selected>All</option> <!-- Default option -->
                                        @foreach ($sections as $section)
                                            <option value="{{ $section }}">{{ $section }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="repairAlatBantu" style="width: 100%; height: auto;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="card" style="height: 560px; overflow: hidden;">
                        <div class="card-body">
                            <h5 class="card-title">Linestop Alat Bantu (Dalam Menit)</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="start_month_alat">Bulan Mulai:</label>
                                        <input type="date" id="start_month_alat" name="start_month_alat"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="end_month_alat">Bulan Akhir:</label>
                                        <input type="date" id="end_month_alat" name="end_month_alat"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div id="periodeRepairAlat" style="width: 100%; height: 100%;"></div>
                        </div>
                    </div>
                </div>


                <div class="col-sm-12">
                    <div id="highcharts-container" class="card">
                        <div class="card-body">
                            <h5 class="card-title">Detail Linestop / Alat Bantu (Dalam Menit)</h5>
                            <div class="row">
                                <!-- Tambahkan dropdown dan input date untuk filter -->
                                <div class="col-md-3">
                                    <label for="sectionDropdown">Pilih Section:</label>
                                    <select id="section-dropdown-periode-alat" class="form-control"
                                        onchange="updateChartPeriodeAlat()">
                                        <option value="All" selected>All</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section }}">{{ $section }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="start_alat">Bulan Mulai:</label>
                                    <input type="date" id="start_alat" name="start_alat" class="form-control"
                                        onchange="updateChartPeriodeAlat()">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_alat">Bulan Akhir:</label>
                                    <input type="date" id="end_alat" name="end_alat" class="form-control"
                                        onchange="updateChartPeriodeAlat()">
                                </div>

                                <!-- Elemen untuk menampilkan grafik -->
                                <div id="periodeAlat" style="width: 100%; height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
        </section>

                <!-- jQuery -->
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
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Data cutting chart
            var cuttingData = {!! $chartCutting !!};

            // Inisialisasi array untuk bulan-bulan
            var months = [];
            for (var i = 1; i <= 12; i++) {
                months.push(getMonthName(i));
            }

            // Memetakan total status 1 (status_2=0) dari data
            var status1 = [];
            for (var i = 1; i <= 12; i++) {
                var found = cuttingData.find(function(item) {
                    return parseInt(item.month) === i;
                });
                if (found) {
                    status1.push(found.total_status_2_0);
                } else {
                    status1.push(0);
                }
            }

            // Memetakan total status 2 (status=3) dari data
            var status2 = [];
            for (var i = 1; i <= 12; i++) {
                var found = cuttingData.find(function(item) {
                    return parseInt(item.month) === i;
                });
                if (found) {
                    status2.push(found.total_status_3);
                } else {
                    status2.push(0);
                }
            }

            Highcharts.chart('chartCutting', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Mesin Cutting'
                },
                xAxis: {
                    categories: months,
                    crosshair: true,
                    accessibility: {
                        description: 'Bulan'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Jumlah Repair'
                    }
                },
                credits: { // Configuration to disable credits
                    enabled: false
                },
                tooltip: {
                    headerFormat: '<span style="font-size:12px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
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
                    name: 'Status (Open)',
                    data: status1,
                    color: 'rgba(0, 150, 0, 0.5)' // Warna hijau yang lebih gelap untuk 'Status (Open)'
                }, {
                    name: 'Status (Closed)',
                    data: status2,
                    color: 'rgba(0, 0, 0, 0.7)' // Warna hitam yang lebih terang untuk 'Status (Closed)'
                }]

            });


            // Data Heat Treatment chart
            var heattreatmentData = {!! $chartHeatTreatment !!};

            // Memetakan total status 1 (status_2=0) dari data
            var status1 = [];
            for (var i = 1; i <= 12; i++) {
                var found = heattreatmentData.find(function(item) {
                    return parseInt(item.month) === i;
                });
                if (found) {
                    status1.push(found.total_status_2_0);
                } else {
                    status1.push(0);
                }
            }

            // Memetakan total status 2 (status=3) dari data
            var status2 = [];
            for (var i = 1; i <= 12; i++) {
                var found = heattreatmentData.find(function(item) {
                    return parseInt(item.month) === i;
                });
                if (found) {
                    status2.push(found.total_status_3);
                } else {
                    status2.push(0);
                }
            }

            Highcharts.chart('chartHeatTreatment', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Mesin Heat Treatment'
                },
                xAxis: {
                    categories: months,
                    crosshair: true,
                    accessibility: {
                        description: 'Bulan'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Jumlah Repair'
                    }
                },
                credits: { // Configuration to disable credits
                    enabled: false
                },
                tooltip: {
                    headerFormat: '<span style="font-size:12px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
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
                    name: 'Status (Open)',
                    data: status1,
                    color: 'rgba(0, 150, 0, 0.5)' // Warna hijau yang lebih gelap untuk 'Status (Open)'
                }, {
                    name: 'Status (Closed)',
                    data: status2,
                    color: 'rgba(0, 0, 0, 0.7)' // Warna hitam yang lebih terang untuk 'Status (Closed)'
                }]

            });

            // Data Machining Chart
            var machiningData = {!! $chartMachining !!};

            // Memetakan total status 1 (status_2=0) dari data
            var status1 = [];
            for (var i = 1; i <= 12; i++) {
                var found = machiningData.find(function(item) {
                    return parseInt(item.month) === i;
                });
                if (found) {
                    status1.push(found.total_status_2_0);
                } else {
                    status1.push(0);
                }
            }

            // Memetakan total status 2 (status=3) dari data
            var status2 = [];
            for (var i = 1; i <= 12; i++) {
                var found = machiningData.find(function(item) {
                    return parseInt(item.month) === i;
                });
                if (found) {
                    status2.push(found.total_status_3);
                } else {
                    status2.push(0);
                }
            }

            Highcharts.chart('chartMachining', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Mesin Machining'
                },
                xAxis: {
                    categories: months,
                    crosshair: true,
                    accessibility: {
                        description: 'Bulan'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Jumlah Repair'
                    }
                },
                credits: { // Configuration to disable credits
                    enabled: false
                },
                tooltip: {
                    headerFormat: '<span style="font-size:12px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                },
                credits: { // Configuration to disable credits
                    enabled: false
                },
                plotOptions: {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0
                    }
                },
                series: [{
                    name: 'Status (Open)',
                    data: status1,
                    color: 'rgba(0, 150, 0, 0.5)' // Warna hijau yang lebih gelap untuk 'Status (Open)'
                }, {
                    name: 'Status (Closed)',
                    data: status2,
                    color: 'rgba(0, 0, 0, 0.7)' // Warna hitam yang lebih terang untuk 'Status (Closed)'
                }]

            });

            // Data CT Bubut Chart
            var machiningcustomData = {!! $chartMachiningCustom !!};

            // Memetakan total status 1 (status_2=0) dari data
            var status1 = [];
            for (var i = 1; i <= 12; i++) {
                var found = machiningcustomData.find(function(item) {
                    return parseInt(item.month) === i;
                });
                if (found) {
                    status1.push(found.total_status_2_0);
                } else {
                    status1.push(0);
                }
            }

            // Memetakan total status 2 (status=3) dari data
            var status2 = [];
            for (var i = 1; i <= 12; i++) {
                var found = machiningcustomData.find(function(item) {
                    return parseInt(item.month) === i;
                });
                if (found) {
                    status2.push(found.total_status_3);
                } else {
                    status2.push(0);
                }
            }

            Highcharts.chart('chartMachiningCustom', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Mesin Maching Custom'
                },
                xAxis: {
                    categories: months,
                    crosshair: true,
                    accessibility: {
                        description: 'Bulan'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Jumlah Repair'
                    }
                },
                credits: { // Configuration to disable credits
                    enabled: false
                },
                tooltip: {
                    headerFormat: '<span style="font-size:12px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
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
                    name: 'Status (Open)',
                    data: status1,
                    color: 'rgba(0, 150, 0, 0.5)' // Warna hijau yang lebih gelap untuk 'Status (Open)'
                }, {
                    name: 'Status (Closed)',
                    data: status2,
                    color: 'rgba(0, 0, 0, 0.7)' // Warna hitam yang lebih terang untuk 'Status (Closed)'
                }]

            });

            var summaryData = {!! json_encode($summaryData) !!};

            // Initialize array for months
            var months = [];
            for (var i = 1; i <= 12; i++) {
                months.push(getMonthName(i));
            }

            // Function to get month name from its number
            function getMonthName(monthNumber) {
                var d = new Date();
                d.setMonth(monthNumber - 1);
                return d.toLocaleString('en-us', {
                    month: 'long'
                });
            }

            // Get all unique sections
            var sections = ['CUTTING', 'HEAT TREATMENT', 'MACHINING', 'MACHINING CUSTOM'];

            // Define colors for each section
            var sectionColors = {
                'CUTTING': {
                    'open': '#FF6666', // Warna merah muda untuk Cutting (Open)
                    'closed': '#CC0000' // Warna merah tua untuk Cutting (Closed)
                },
                'HEAT TREATMENT': {
                    'open': '#66FF66', // Warna hijau muda untuk Heat Treatment (Open)
                    'closed': '#009900' // Warna hijau tua untuk Heat Treatment (Closed)
                },
                'MACHINING': {
                    'open': '#0652DD', // Warna biru muda untuk Machining (Open)
                    'closed': '#1B1464' // Warna biru tua untuk Machining (Closed)
                },
                'MACHINING CUSTOM': {
                    'open': '#ADD8E6', // Warna biru muda untuk Machining Custom (Open)
                    'closed': '#4682B4' // Warna biru tua untuk Machining Custom (Closed)
                }
            };

            // Create data series for Highcharts
            var seriesData = [];
            sections.forEach(function(section) {
                var openArray = [];
                var closedArray = [];
                months.forEach(function(month) {
                    // Check if data exists for this section and month
                    var sectionData = summaryData.find(data => data.section.toUpperCase() === section &&
                        getMonthName(data.month) === month);
                    if (sectionData) {
                        openArray.push(parseInt(sectionData.total_status_2_0));
                        closedArray.push(parseInt(sectionData.total_status_3));
                    } else {
                        openArray.push(0);
                        closedArray.push(0);
                    }
                });

                seriesData.push({
                    name: section + ' (Open)',
                    data: openArray,
                    color: sectionColors[section.toUpperCase()][
                        'open'
                    ] // Mengatur warna label berdasarkan bagian (Open)
                }, {
                    name: section + ' (Closed)',
                    data: closedArray,
                    color: sectionColors[section.toUpperCase()][
                        'closed'
                    ] // Mengatur warna label berdasarkan bagian (Closed)
                });
            });

            // Create chart using Highcharts
            Highcharts.chart('summaryHighcharts', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Summary Repair Maintenance'
                },
                xAxis: {
                    categories: months,
                    crosshair: true
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Total Repair Maintenance'
                    }
                },
                credits: { // Configuration to disable credits
                    enabled: false
                },
                tooltip: {
                    headerFormat: '<span style="font-size: 12px">{point.key}</span><br/>',
                    pointFormat: '<span style="color:{series.color};font-weight:bold;">{series.name}: </span>' +
                        '<span style="font-weight:bold;">{point.y}</span><br/>',
                    footerFormat: '',
                    shared: true,
                    useHTML: true,
                    style: {
                        width: '200px'
                    }
                },
                plotOptions: {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0
                    }
                },
                series: seriesData
            });
        </script>
        <script>
            var repairMaintenanceChart;
            var periodeWaktuPengerjaanChart;

            document.addEventListener('DOMContentLoaded', function() {
                let dateDropdown = document.getElementById('date-dropdown2');
                let currentYear = new Date().getFullYear();
                let earliestYear = 2020;
                while (currentYear >= earliestYear) {
                    let dateOption = document.createElement('option');
                    dateOption.text = currentYear;
                    dateOption.value = currentYear;
                    dateDropdown.add(dateOption);
                    currentYear -= 1;
                }
                updateCharts();
            });

            document.getElementById('date-dropdown2').addEventListener('change', function() {
                updateCharts();
                updatePeriodeWaktuPengerjaanChart();
            });

            document.getElementById('section-dropdown').addEventListener('change', function() {
                updateCharts();
                updatePeriodeWaktuPengerjaanChart();
            });

            document.getElementById('start_month2').addEventListener('change', function() {
                updateCharts();
                updatePeriodeWaktuPengerjaanChart();
            });

            document.getElementById('end_month2').addEventListener('change', function() {
                updateCharts();
                updatePeriodeWaktuPengerjaanChart();
            });

            function updateCharts() {
                var selectedYear = document.getElementById('date-dropdown2').value;
                var selectedSection = document.getElementById('section-dropdown').value;
                var startMonth = document.getElementById('start_month2').value;
                var endMonth = document.getElementById('end_month2').value;

                $.ajax({
                    url: '{{ route('getMaintenanceData') }}',
                    method: 'GET',
                    data: {
                        year: selectedYear,
                        section: selectedSection,
                        start_month2: startMonth,
                        end_month2: endMonth
                    },
                    success: function(response) {
                        console.log('Response:', response);
                        updateRepairMaintenanceChart(response.repairMaintenance.labels, response.repairMaintenance
                            .data2, selectedSection);
                        updatePeriodeWaktuPengerjaanChart(response.periodeWaktuPengerjaan.labels, response
                            .periodeWaktuPengerjaan.data2, selectedSection);
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }

            function updatePeriodeWaktuPengerjaanChart(labels, data2, selectedSection) {
                console.log('Data received:', labels, data2); // Debug log

                var totalLabels = ['Linestop (Dalam Menit)']; // Label di sumbu X
                var totalData = data2 && data2.length > 0 ? parseFloat(data2[0]) : 0; // Data di sumbu Y

                var color;
                switch (selectedSection) {
                    case 'CUTTING':
                        color = '#e74c3c';
                        break;
                    case 'MACHINING CUSTOM':
                        color = '#3498db';
                        break;
                    case 'MACHINING':
                        color = 'blue';
                        break;
                    case 'HEAT TREATMENT':
                        color = '#27ae60';
                        break;
                    default:
                        color = 'darkgrey';
                }

                if (!periodeWaktuPengerjaanChart) {
                    periodeWaktuPengerjaanChart = Highcharts.chart('periodeRepair', {
                        chart: {
                            type: 'column', // Menggunakan tipe 'column' untuk grafik batang vertikal
                            height: '100%' // Atur tinggi grafik
                        },
                        title: {
                            text: 'Linestop (Dalam Menit)'
                        },
                        xAxis: {
                            categories: totalLabels, // Label sumbu X
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Menit' // Label sumbu Y
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Linestop (Dalam menit)',
                            data: [totalData], // Data di sumbu Y
                            color: color // Warna berdasarkan selectedSection
                        }]
                    });
                } else {
                    periodeWaktuPengerjaanChart.xAxis[0].setCategories(totalLabels); // Update label sumbu X
                    periodeWaktuPengerjaanChart.series[0].setData([totalData], true); // Update data di sumbu Y
                    periodeWaktuPengerjaanChart.series[0].update({
                        color: color
                    }); // Update warna berdasarkan selectedSection
                }
            }










            function updateRepairMaintenanceChart(labels, data2, selectedSection) {
                var color;
                switch (selectedSection) {
                    case 'CUTTING':
                        color = '#e74c3c';
                        break;
                    case 'MACHINING CUSTOM':
                        color = '#3498db';
                        break;
                    case 'MACHINING':
                        color = 'blue';
                        break;
                    case 'HEAT TREATMENT':
                        color = '#27ae60';
                        break;
                    default:
                        color = 'darkgrey';
                }

                if (!repairMaintenanceChart) {
                    repairMaintenanceChart = Highcharts.chart('repairMaintenance', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'Linestop (Dalam Menit)'
                        },
                        xAxis: {
                            categories: labels
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Waktu (menit)'
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Line Stop (Dalam Menit)',
                            data: data2,
                            color: color
                        }]
                    });
                } else {
                    repairMaintenanceChart.xAxis[0].setCategories(labels, false);
                    repairMaintenanceChart.series[0].setData(data2, true);
                    repairMaintenanceChart.series[0].update({
                        color: color
                    });
                }
            }
        </script>




        <!-- Repair Maintenance Alat Bantu -->
        <script>
            var repairMaintenanceAlatChart;
            var periodeWaktuPengerjaanAlatChart;

            document.addEventListener('DOMContentLoaded', function() {
                let dateDropdown = document.getElementById('date-dropdown-alat');
                let currentYear = new Date().getFullYear();
                let earliestYear = 2020;
                while (currentYear >= earliestYear) {
                    let dateOption = document.createElement('option');
                    dateOption.text = currentYear;
                    dateOption.value = currentYear;
                    dateDropdown.add(dateOption);
                    currentYear -= 1;
                }
                updateChartsAlat();
            });

            document.getElementById('date-dropdown-alat').addEventListener('change', function() {
                updateChartsAlat();
                updatePeriodeWaktuPengerjaanAlatChart();
            });

            document.getElementById('section-dropdown-alat').addEventListener('change', function() {
                updateChartsAlat();
                updatePeriodeWaktuPengerjaanAlatChart();
            });

            document.getElementById('start_month_alat').addEventListener('change', function() {
                updateChartsAlat();
                updatePeriodeWaktuPengerjaanAlatChart();
            });

            document.getElementById('end_month_alat').addEventListener('change', function() {
                updateChartsAlat();
                updatePeriodeWaktuPengerjaanAlatChart();
            });

            function updateChartsAlat() {
                var selectedYear = document.getElementById('date-dropdown-alat').value;
                var selectedSection = document.getElementById('section-dropdown-alat').value;
                var startMonth = document.getElementById('start_month_alat').value;
                var endMonth = document.getElementById('end_month_alat').value;

                $.ajax({
                    url: '{{ route('getMaintenanceDataAlat') }}',
                    method: 'GET',
                    data: {
                        year: selectedYear,
                        section: selectedSection,
                        start_month_alat: startMonth,
                        end_month_alat: endMonth
                    },
                    success: function(response) {
                        console.log('Response:', response);
                        var repairAlatData = response.repairAlat; // Retrieve repairAlat data from response
                        var periodeWaktuAlatData = response
                            .periodeWaktuAlat; // Retrieve periodeWaktuAlat data from response

                        updateRepairMaintenanceAlatChart(repairAlatData.labels, repairAlatData.data2,
                            selectedSection);
                        updatePeriodeWaktuPengerjaanAlatChart(periodeWaktuAlatData.labels, periodeWaktuAlatData
                            .data2, selectedSection);
                    },

                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }

            function updatePeriodeWaktuPengerjaanAlatChart(labels, data2, selectedSection) {
                console.log('Data received:', labels, data2); // Debug log

                var totalLabels = ['Linestop (Dalam Menit)']; // Label di sumbu X
                var totalData = data2 && data2.length > 0 ? parseFloat(data2[0]) : 0; // Data di sumbu Y

                var color;
                switch (selectedSection) {
                    case 'CUTTING':
                        color = '#e74c3c';
                        break;
                    case 'MACHINING CUSTOM':
                        color = '#3498db';
                        break;
                    case 'MACHINING':
                        color = 'blue';
                        break;
                    case 'HEAT TREATMENT':
                        color = '#27ae60';
                        break;
                    default:
                        color = 'darkgrey';
                }

                if (!periodeWaktuPengerjaanAlatChart) {
                    periodeWaktuPengerjaanAlatChart = Highcharts.chart('periodeRepairAlat', {
                        chart: {
                            type: 'column', // Menggunakan tipe 'column' untuk grafik batang vertikal
                            height: '100%' // Atur tinggi grafik
                        },
                        title: {
                            text: 'Linestop (Dalam Menit)'
                        },
                        xAxis: {
                            categories: totalLabels, // Label sumbu X
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Menit' // Label sumbu Y
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Linestop (Dalam menit)',
                            data: [totalData], // Data di sumbu Y
                            color: color // Warna berdasarkan selectedSection
                        }]
                    });
                } else {
                    periodeWaktuPengerjaanAlatChart.xAxis[0].setCategories(totalLabels); // Update label sumbu X
                    periodeWaktuPengerjaanAlatChart.series[0].setData([totalData], true); // Update data di sumbu Y
                    periodeWaktuPengerjaanAlatChart.series[0].update({
                        color: color
                    }); // Update warna berdasarkan selectedSection
                }
            }

            function updateRepairMaintenanceAlatChart(labels, data2, selectedSection) {
                var color;
                switch (selectedSection) {
                    case 'CUTTING':
                        color = '#e74c3c';
                        break;
                    case 'MACHINING CUSTOM':
                        color = '#3498db';
                        break;
                    case 'MACHINING':
                        color = 'blue';
                        break;
                    case 'HEAT TREATMENT':
                        color = '#27ae60';
                        break;
                    default:
                        color = 'darkgrey';
                }

                if (!repairMaintenanceAlatChart) {
                    repairMaintenanceAlatChart = Highcharts.chart('repairAlatBantu', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'Linestop (Dalam Menit)'
                        },
                        xAxis: {
                            categories: labels
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Waktu (menit)'
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Line Stop (Dalam Menit)',
                            data: data2,
                            color: color
                        }]
                    });
                } else {
                    repairMaintenanceAlatChart.xAxis[0].setCategories(labels, false);
                    repairMaintenanceAlatChart.series[0].setData(data2, true);
                    repairMaintenanceAlatChart.series[0].update({
                        color: color
                    });
                }
            }
        </script>


        <script>
            function updateChartPeriodeAlat() {
                var section_alat = document.getElementById('section-dropdown-periode-alat').value;
                var startDate_alat = document.getElementById('start_alat').value;
                var endDate_alat = document.getElementById('end_alat').value;

                $.ajax({
                    url: '{{ route('getPeriodeAlat') }}',
                    type: 'GET',
                    data: {
                        section_alat: section_alat,
                        start_alat: startDate_alat,
                        end_alat: endDate_alat
                    },
                    success: function(response) {
                        drawChartPeriodeAlat(response);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            }

            // Fungsi untuk menggambar grafik linestop alat bantu menggunakan data yang diterima
            function drawChartPeriodeAlat(data) {
                var categories_alat = []; // Array untuk menyimpan kategori (nomor mesin)
                var seriesData_alat = []; // Array untuk menyimpan data series

                // Tentukan warna yang sesuai untuk setiap section
                var colors = {
                    'cutting': '#e74c3c',
                    'machining custom': '#3498db',
                    'machining': 'blue',
                    'heat treatment': '#27ae60'
                };

                // Membuat data series berdasarkan section dengan warna yang sesuai
                data.forEach(item => {
                    // Menambahkan kategori (nomor mesin) dan data series
                    categories_alat.push(item.mesin);
                    var color = colors[item.section.toLowerCase()] ||
                        'gray'; // Gunakan warna sesuai dengan section, jika tidak ada gunakan warna default
                    seriesData_alat.push({
                        name: item.mesin + ' (' + item.section + ')',
                        y: parseFloat(item.total_minutes_alat),
                        color: color
                    });
                });

                // Menggambar grafik menggunakan Highcharts JS
                Highcharts.chart('periodeAlat', {
                    chart: {
                        type: 'column'
                    },
                    title: {
                        text: 'Detail Linestop / Alat Bantu (Dalam Menit)'
                    },
                    xAxis: {
                        categories: categories_alat
                    },
                    yAxis: {
                        title: {
                            text: 'Total Menit'
                        }
                    },
                    series: [{
                        name: 'Line Stop (Dalam menit)',
                        data: seriesData_alat // Menggunakan data yang sudah disesuaikan warnanya
                    }],
                    credits: { // Configuration to disable credits
                        enabled: false
                    },
                    plotOptions: {
                        column: {
                            colorByPoint: true // Mengatur agar warna sesuai dengan point (data) pada sumbu-x
                        }
                    }
                });
            }

            // Memanggil fungsi updateChartPeriodeAlat() untuk menginisialisasi grafik linestop alat bantu
            updateChartPeriodeAlat();
        </script>
        <script>
            // Fungsi untuk memuat data dari server dan menggambar grafik
            function updateChartPeriodeMesin() {
                var section = document.getElementById('section-dropdown2').value;
                var startDate = document.getElementById('start_mesin').value;
                var endDate = document.getElementById('end_mesin').value;

                // AJAX request untuk mendapatkan data dari server
                $.ajax({
                    url: '{{ route('getPeriodeMesin') }}',
                    type: 'GET',
                    data: {
                        section: section,
                        start_mesin: startDate,
                        end_mesin: endDate
                    },
                    success: function(response) {
                        drawChart(response);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            }

            // Fungsi untuk menggambar grafik menggunakan Highcharts JS
            function drawChart(data) {
                console.log(data);
                var categories = []; // Array untuk menyimpan kategori (nomor mesin)
                var seriesData = []; // Array untuk menyimpan data series

                // Tentukan warna yang sesuai untuk setiap section
                var colors = {
                    'CUTTING': '#e74c3c',
                    'MACHINING CUSTOM': '#3498db',
                    'MACHINING': 'blue',
                    'HEAT TREATMENT': '#27ae60'
                };

                // Membuat data series berdasarkan section dengan warna yang sesuai
                data.forEach(item => {
                    // Menambahkan kategori (nomor mesin) dan data series
                    categories.push(item.no_mesin);
                    var color = colors[item
                        .section]; // Gunakan warna sesuai dengan section, jika tidak ada gunakan warna default
                    seriesData.push({
                        name: item.no_mesin + ' (' + item.section + ')',
                        y: parseFloat(item.total_minutes),
                        color: color
                    });
                });

                // Menggambar grafik menggunakan Highcharts JS
                Highcharts.chart('periodeRepairMesin', {
                    chart: {
                        type: 'column'
                    },
                    credits: {
                        enabled: false
                    },
                    title: {
                        text: 'Detail Linestop / Mesin (Dalam Menit)'
                    },
                    xAxis: {
                        categories: categories
                    },
                    yAxis: {
                        title: {
                            text: 'Total Menit'
                        }
                    },
                    series: [{
                        name: 'Line Stop (Dalam menit)',
                        data: seriesData // Menggunakan data yang sudah disesuaikan warnanya
                    }],
                    plotOptions: {
                        column: {
                            colorByPoint: true // Mengatur agar warna sesuai dengan point (data) pada sumbu-x
                        }
                    }
                });
            }

            // Memanggil fungsi updateChartPeriodeMesin() untuk menginisialisasi grafik
            updateChartPeriodeMesin();
        </script>

        <script>
            function validateDates() {
                var startMonth = document.getElementById("start_month2");
                var endMonth = document.getElementById("end_month2");

                var startDate = new Date(startMonth.value);
                var endDate = new Date(endMonth.value);

                if (endMonth.value && startDate > endDate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Bulan Akhir tidak boleh lebih awal daripada Bulan Mulai',
                    });
                    endMonth.value = ""; // Clear the invalid date
                }

                if (startMonth.value && startDate > endDate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Bulan Mulai tidak boleh lebih lama daripada Bulan Akhir',
                    });
                    startMonth.value = ""; // Clear the invalid date
                }
            }

            document.getElementById("start_month2").addEventListener("change", validateDates);
            document.getElementById("end_month2").addEventListener("change", validateDates);
        </script>

        <script>
            function validateDates2() {
                var startMonth2 = document.getElementById("start_mesin");
                var endMonth2 = document.getElementById("end_mesin");

                var startDate2 = new Date(startMonth2.value);
                var endDate2 = new Date(endMonth2.value);

                if (endMonth2.value && startDate2 > endDate2) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Bulan Akhir tidak boleh lebih awal daripada Bulan Mulai',
                    });
                    endMonth2.value = ""; // Clear the invalid date
                }

                if (startMonth2.value && startDate2 > endDate2) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Bulan Mulai tidak boleh lebih lama daripada Bulan Akhir',
                    });
                    startMonth2.value = ""; // Clear the invalid date
                }
            }

            document.getElementById("start_mesin").addEventListener("change", validateDates2);
            document.getElementById("end_mesin").addEventListener("change", validateDates2);
        </script>

        <script>
            function validateDates3() {
                var startMonth3 = document.getElementById("start_month_alat");
                var endMonth3 = document.getElementById("end_month_alat");

                var startDate3 = new Date(startMonth3.value);
                var endDate3 = new Date(endMonth3.value);

                if (endMonth3.value && startDate3 > endDate3) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Bulan Akhir tidak boleh lebih awal daripada Bulan Mulai',
                    });
                    endMonth3.value = ""; // Clear the invalid date
                }

                if (startMonth3.value && startDate3 > endDate3) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Bulan Mulai tidak boleh lebih lama daripada Bulan Akhir',
                    });
                    startMonth3.value = ""; // Clear the invalid date
                }
            }

            document.getElementById("start_month_alat").addEventListener("change", validateDates3);
            document.getElementById("end_month_alat").addEventListener("change", validateDates3);
        </script>

        <script>
            function validateDates4() {
                var startMonth4 = document.getElementById("start_alat");
                var endMonth4 = document.getElementById("end_alat");

                var startDate4 = new Date(startMonth4.value);
                var endDate4 = new Date(endMonth4.value);

                if (endMonth4.value && startDate4 > endDate4) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Bulan Akhir tidak boleh lebih awal daripada Bulan Mulai',
                    });
                    endMonth4.value = ""; // Clear the invalid date
                }

                if (startMonth4.value && startDate4 > endDate4) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Bulan Mulai tidak boleh lebih lama daripada Bulan Akhir',
                    });
                    startMonth4.value = ""; // Clear the invalid date
                }
            }

            document.getElementById("start_alat").addEventListener("change", validateDates4);
            document.getElementById("end_alat").addEventListener("change", validateDates4);
        </script>







    </main><!-- End #main -->
@endsection
