@extends('layout')

@section('content')
    <style>
        @media (max-width: 768px) {

            /* Lebar layar <= 768px, menyesuaikan ukuran chart */
            #myChart {
                height: 50vh;
            }

            #chartAllPeriode {
                height: 50vh;
            }

            #date-dropdown {
                width: 100%;
            }
        }
    </style>
    <main id="main" class="main">
        <section class="section dashboard">
            <div class="row">
                <h3 style="display: flex; justify-content: center;">Dashboard Handling Klaim dan Komplain</h3>
                <!-- Left side columns -->
                <div class="col-lg-12">
                    <div class="row">

                        <!-- Sales Card -->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card info-card sales-card">
                                <div class="card-body">
                                    <h5 class="card-title">Open</h5>
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-envelope-open-fill"></i>
                                        </div>
                                        <div class="ps-3">
                                            @php
                                                $openHandlingsCount = \App\Models\Handling::where('status', 0)->count();
                                            @endphp
                                            <h6>{{ $openHandlingsCount }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Sales Card -->

                        <!-- Revenue Card -->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card info-card sales-card">
                                <div class="card-body">
                                    <h5 class="card-title">On Progress</h5>

                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-hourglass-split "></i>
                                        </div>
                                        <div class="ps-3">
                                            @php
                                                $openHandlingsCount = \App\Models\Handling::where('status', 1)->count();
                                            @endphp
                                            <h6>{{ $openHandlingsCount }}</h6>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div><!-- End Revenue Card -->

                        <!-- Customers Card -->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card info-card sales-card">
                                <div class="card-body">
                                    <h5 class="card-title">Finish</h5>

                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-check2-all"></i>
                                        </div>
                                        <div class="ps-3">
                                            @php
                                                $openHandlingsCount = \App\Models\Handling::where('status', 2)->count();
                                            @endphp
                                            <h6>{{ $openHandlingsCount }}</h6>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div><!-- End Customers Card -->
                        <!-- Customers Card -->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card info-card sales-card">
                                <div class="card-body">
                                    <h5 class="card-title">Close</h5>
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </div>
                                        <div class="ps-3">
                                            @php
                                                $openHandlingsCount = \App\Models\Handling::where('status', 3)->count();
                                            @endphp
                                            <h6>{{ $openHandlingsCount }}</h6>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div><!-- End Customers Card -->

                        <div class="container-fluid">
                            <!-- Row for the first set of charts -->
                            <div class="row">
                                <!-- Bar chart for claims and complaints -->
                                <div class="col-sm-12 col-md-9 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Chart Bar Klaim dan Komplain</h5>
                                            <div>
                                                <label for="yearDropdown">Pilih Tahun:</label>
                                                <select id='date-dropdown' class="form-control"
                                                    style="width: 100%; max-width: 200px;"></select>
                                                <canvas id="myChart" style="height:24.5vh; width:100%"></canvas>
                                            </div>
                                            <div class="row" style="margin-top: 5%">
                                                <div class="col-md-3 mb-2">
                                                    <label for="start_month">Bulan Mulai:</label>
                                                    <input type="date" id="start_month5" name="start_month"
                                                        class="form-control" onchange="validateDates5()">
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <label for="end_month">Bulan Akhir:</label>
                                                    <input type="date" id="end_month5" name="end_month"
                                                        class="form-control" onchange="validateDates5()">
                                                </div>
                                                <div class="col-md-2 mb-2">
                                                    <label>&nbsp;</label> <!-- Label kosong untuk mengatur posisi tombol -->
                                                    <button type="button" class="btn btn-success form-control"
                                                        onclick="exportToExcel()">
                                                        <i class="fas fa-file-excel"></i> Export ke Excel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Bar chart for period -->
                                <div class="col-sm-12 col-md-3 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Chart Bar Periode</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label for="start_periode">Bulan Mulai:</label>
                                                    <input type="date" id="start_periode" name="start_periode"
                                                        class="form-control" onchange="validateDates()">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label for="end_periode">Bulan Akhir:</label>
                                                    <input type="date" id="end_periode" name="end_periode"
                                                        class="form-control" onchange="validateDates()">
                                                </div>
                                                <div id="chartAllPeriode" style="height:36.3vh; width:120%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row for the pie charts -->
                            <div class="row">
                                <!-- Pie chart for material type -->
                                <div class="col-sm-12 col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Chart Pie Tipe Material</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label for="start_month">Bulan Mulai:</label>
                                                    <input type="date" id="start_month" name="start_month"
                                                        class="form-control" onchange="validateDates2()">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label for="end_month">Bulan Akhir:</label>
                                                    <input type="date" id="end_month" name="end_month"
                                                        class="form-control" onchange="validateDates2()">
                                                </div>
                                                <div class="col-lg-6 mb-2">
                                                    <label for="jenis">Jenis:</label>
                                                    <select id="jenis" class="form-select form-select-sm"
                                                        onchange="FilterPieChartTipe()">
                                                        <option selected>--- Pilih Jenis ---</option>
                                                        <option value="frekuensi">Frekuensi Jenis</option>
                                                        <option value="qty">QTY</option>
                                                        <option value="pcs">PCS</option>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mb-2">
                                                    <label for="tipe">Kategori:</label>
                                                    <select id="type" class="form-select form-select-sm"
                                                        onchange="FilterPieChartTipe()">
                                                        <option selected>--- Pilih Kategori ---</option>
                                                        <option value="kategori">All Kategori</option>
                                                        <option value="type_1">Komplain</option>
                                                        <option value="type_2">Klaim</option>
                                                    </select>
                                                </div>
                                                <div id="ChartPieTypeMaterial"
                                                    style="width: 100%; height: 300px; margin-top: 5%;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pie chart for process -->
                                <div class="col-sm-12 col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Chart Pie Proses</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label for="start_month3">Bulan Mulai:</label>
                                                    <input type="date" id="start_month3" name="start_month3"
                                                        class="form-control" onchange="validateDates3()">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label for="end_month3">Bulan Akhir:</label>
                                                    <input type="date" id="end_month3" name="end_month3"
                                                        class="form-control" onchange="validateDates3()">
                                                </div>
                                                <div class="col-lg-6 mb-2">
                                                    <label for="jenis2">Jenis:</label>
                                                    <select id="jenis2" class="form-select form-select-sm"
                                                        onchange="updatePieChart();">
                                                        <option selected>--- Pilih Jenis ---</option>
                                                        <option value="frekuensi2">Frekuensi Jenis</option>
                                                        <option value="qty">QTY</option>
                                                        <option value="pcs">PCS</option>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mb-2">
                                                    <label for="tipe2">Kategori:</label>
                                                    <select id="tipe2" class="form-select form-select-sm"
                                                        onchange="updatePieChart();">
                                                        <option selected>--- Pilih Kategori ---</option>
                                                        <option value="kategori_2">All Kategori</option>
                                                        <option value="type_1">Komplain</option>
                                                        <option value="type_2">Klaim</option>
                                                    </select>
                                                </div>
                                                <div id="ChartPieProses"
                                                    style="width: 100%; height: 300px; margin-top: 5%;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row for the NG pie chart -->
                            <div class="row">
                                <!-- Pie chart for NG categories -->
                                <div class="col-sm-12 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Chart Pie Kategori (NG)</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label for="start_month4">Bulan Mulai:</label>
                                                    <input type="date" id="start_month4" name="start_month4"
                                                        class="form-control" onchange="validateDates4()">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label for="end_month4">Bulan Akhir:</label>
                                                    <input type="date" id="end_month4" name="end_month4"
                                                        class="form-control" onchange="validateDates4()">
                                                </div>
                                                <div class="col-lg-6 mb-2">
                                                    <label for="jenis3">Jenis:</label>
                                                    <select id="jenis3" class="form-select form-select-sm"
                                                        onchange="updatePieChart();">
                                                        <option selected>--- Pilih Jenis (NG) ---</option>
                                                        <option value="CT - Ukuran Minus">CT - Ukuran Minus</option>
                                                        <option value="CT - Potongan Miring">CT - Potongan Miring</option>
                                                        <option value="CT - NG Dimensi">CT - NG Dimensi</option>
                                                        <option value="MCH - Dimensi NG">MCH - Dimensi NG</option>
                                                        <option value="MCH - Ada Step">MCH - Ada Step</option>
                                                        <option value="MCH - NG Paralelism">MCH - NG Paralelism</option>
                                                        <option value="MCH - NG Siku">MCH - NG Siku</option>
                                                        <option value="HT - NG Siku">HT - NG Siku</option>
                                                        <option value="HT - Retak/Patah">HT - Retak/Patah</option>
                                                        <option value="HT - Bending">HT - Bending</option>
                                                        <option value="HT - Kekerasan Minus">HT - Kekerasan Minus</option>
                                                        <option value="HT - Kekerasan Lebih">HT - Kekerasan Lebih</option>
                                                        <option value="HT - Scratch/Gores">HT - Scratch/Gores</option>
                                                        <option value="HT - Aus">HT - Aus</option>
                                                        <option value="HT - Appearance">HT - Appearance</option>
                                                        <option value="MKT - Jumlah Tidak Sesuai">MKT - Jumlah Tidak Sesuai
                                                        </option>
                                                        <option value="MKT - Dimensi Tidak Sesuai">MKT - Dimensi Tidak
                                                            Sesuai</option>
                                                        <option value="MKT - Type Material Tidak Sesuai">MKT - Type
                                                            Material Tidak Sesuai</option>
                                                        <option value="MTRL - Pin Hole">MTRL - Pin Hole</option>
                                                        <option value="MTRL - Inklusi">MTRL - Inklusi</option>
                                                        <option value="MTRL - Sulit Machining">MTRL - Sulit Machining
                                                        </option>
                                                        <option value="MTRL - Karat">MTRL - Karat</option>
                                                        <option value="Others">Others</option>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mb-2">
                                                    <label for="tipe3">Kategori:</label>
                                                    <select id="tipe3" class="form-select form-select-sm"
                                                        onchange="updatePieChart();">
                                                        <option selected>--- Pilih Kategori ---</option>
                                                        <option value="kategori_3">All Kategori</option>
                                                        <option value="type_1">Komplain</option>
                                                        <option value="type_2">Klaim</option>
                                                    </select>
                                                </div>
                                                <div id="ChartPieNG" style="width: 100%; height: 300px; margin-top: 5%;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End Left side columns -->
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
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Tambahkan ini di dalam <head> atau sebelum </body> -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.highcharts.com/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/modules/export-data.js"></script>
        <script src="https://code.highcharts.com/modules/accessibility.js"></script>

        <script>
            //export excel ds
            function exportToExcel() {
                var startMonth = document.getElementById('start_month5').value;
                var endMonth = document.getElementById('end_month5').value;
                if (startMonth && endMonth) {
                    var url = "{{ route('export.handlings') }}";
                    window.location.href = `${url}?start_month=${startMonth}&end_month=${endMonth}`;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tanggal Tidak Valid',
                        text: 'Silakan pilih kedua tanggal!'
                    });
                }
            }

            //validasi bulan awal dan akhir
            function validateDates() {
                var startPeriode = document.getElementById('start_periode').value;
                var endPeriode = document.getElementById('end_periode').value;

                if (startPeriode && endPeriode) {
                    var startDate = new Date(startPeriode);
                    var endDate = new Date(endPeriode);

                    if (endDate < startDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tanggal Tidak Valid',
                            text: 'Bulan Akhir tidak boleh kurang dari Bulan Mulai.',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('end_periode').value = '';
                            }
                        });
                    }
                }
            }

            function validateDates2() {
                var startPeriode = document.getElementById('start_month').value;
                var endPeriode = document.getElementById('end_month').value;

                if (startPeriode && endPeriode) {
                    var startDate = new Date(startPeriode);
                    var endDate = new Date(endPeriode);

                    if (endDate < startDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tanggal Tidak Valid',
                            text: 'Bulan Akhir tidak boleh kurang dari Bulan Mulai.',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('end_periode').value = '';
                            }
                        });
                    }
                }
            }

            function validateDates3() {
                var startPeriode = document.getElementById('start_month3').value;
                var endPeriode = document.getElementById('end_month3').value;

                if (startPeriode && endPeriode) {
                    var startDate = new Date(startPeriode);
                    var endDate = new Date(endPeriode);

                    if (endDate < startDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tanggal Tidak Valid',
                            text: 'Bulan Akhir tidak boleh kurang dari Bulan Mulai.',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('end_periode').value = '';
                            }
                        });
                    }
                }
            }

            function validateDates4() {
                var startPeriode = document.getElementById('start_month4').value;
                var endPeriode = document.getElementById('end_month4').value;

                if (startPeriode && endPeriode) {
                    var startDate = new Date(startPeriode);
                    var endDate = new Date(endPeriode);

                    if (endDate < startDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tanggal Tidak Valid',
                            text: 'Bulan Akhir tidak boleh kurang dari Bulan Mulai.',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('end_periode').value = '';
                            }
                        });
                    }
                }
            }

            function validateDates5() {
                var startPeriode = document.getElementById('start_month5').value;
                var endPeriode = document.getElementById('end_month5').value;

                if (startPeriode && endPeriode) {
                    var startDate = new Date(startPeriode);
                    var endDate = new Date(endPeriode);

                    if (endDate < startDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tanggal Tidak Valid',
                            text: 'Bulan Akhir tidak boleh kurang dari Bulan Mulai.',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('end_periode').value = '';
                            }
                        });
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Mendapatkan elemen input tanggal
                var startDateInput = document.getElementById('start_periode');
                var endDateInput = document.getElementById('end_periode');

                // Menambahkan event listener untuk memperbarui chart saat nilai input tanggal berubah
                startDateInput.addEventListener('change', updateChart);
                endDateInput.addEventListener('change', updateChart);

                function updateChart() {
                    var startDate = startDateInput.value;
                    var endDate = endDateInput.value;

                    // Mengirim permintaan AJAX untuk mendapatkan data dari controller
                    $.ajax({
                        url: '{{ route('getChartData') }}',
                        method: 'GET',
                        data: {
                            start_date: startDate,
                            end_date: endDate
                        },
                        success: function(response) {
                            var responseData = response;
                            renderChart(responseData);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error saat memuat data:', xhr.statusText);
                        }
                    });
                }

                function renderChart(data) {
                    // Mengatur data yang diperlukan untuk chart
                    Highcharts.chart('chartAllPeriode', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'Status Handling'
                        },
                        xAxis: {
                            categories: ['Open', 'Close']
                        },
                        yAxis: {
                            title: {
                                text: 'Jumlah'
                            }
                        },
                        plotOptions: {
                            series: {
                                borderRadius: 5,
                                colorByPoint: true
                            }
                        },
                        series: [{
                            name: 'Data',
                            data: [{
                                name: 'Open',
                                y: data.Open,
                                color: '#0000FF'
                            }, {
                                name: 'Close',
                                y: data.Close,
                                color: '#FF0000'
                            }]
                        }]
                    });
                }
            });
            // Inisialisasi variabel myChart sebagai variabel global
            var myChart;

            //dropdownYear
            let dateDropdown = document.getElementById('date-dropdown');

            let currentYear = new Date().getFullYear();
            let earliestYear = 2020;
            while (currentYear >= earliestYear) {
                let dateOption = document.createElement('option');
                dateOption.text = currentYear;
                dateOption.value = currentYear;
                dateDropdown.add(dateOption);
                currentYear -= 1;
            }

            // Inisialisasi chart saat halaman dimuat
            $(document).ready(function() {
                // Ambil data awal untuk tahun saat ini
                var currentYear = new Date().getFullYear();
                fetchDataByYear(currentYear);
            });

            // Event listener untuk menangkap perubahan pada dropdown tahun
            dateDropdown.addEventListener('change', function() {
                var selectedYear = this.value; // Mendapatkan tahun yang dipilih dari dropdown
                fetchDataByYear(selectedYear); // Panggil fungsi untuk mengambil data berdasarkan tahun yang dipilih
            });

            // Fungsi untuk mengambil data berdasarkan tahun yang dipilih
            function fetchDataByYear(year) {
                // Buat permintaan AJAX ke server dengan tahun yang dipilih
                $.ajax({
                    url: '{{ route('getDataByYear') }}',
                    type: 'GET',
                    data: {
                        year: year
                    },
                    success: function(response) {
                        // Update chart dengan data baru dari respons server
                        updateChart(response);
                    },
                    error: function(xhr, status, error) {
                        console.error(error); // Tampilkan pesan error jika terjadi kesalahan
                    }
                });
            }

            // Fungsi untuk memperbarui chart dengan data baru
            function updateChart(data) {
                // Memetakan data baru ke status1 dan status2
                var status1 = [];
                var status2 = [];
                for (var i = 0; i < 12; i++) {
                    var found = data.find(function(item) {
                        return parseInt(item.month) === i + 1; // Ubah indeks bulan menjadi dimulai dari 1
                    });
                    if (found) {
                        status1.push(found.total_status_2_0);
                        status2.push(found.total_status_3);
                    } else {
                        status1.push(0);
                        status2.push(0);
                    }
                }

                // Perbarui data chart jika myChart telah diinisialisasi
                if (typeof myChart !== 'undefined') {
                    // Perbarui data chart hanya jika datasets tersedia
                    if (myChart.data.datasets) {
                        // Perbarui data chart
                        myChart.data.datasets[0].data = status1; // Update data untuk status1 (Open)
                        myChart.data.datasets[1].data = status2; // Update data untuk status2 (Close)
                        myChart.update(); // Perbarui chart
                    }
                }
            }

            function getMonthName(monthNumber) {
                const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September",
                    "October", "November", "December"
                ];
                return monthNames[monthNumber - 1];
            }

            $(document).ready(function() {
                $.ajax({
                    url: '{{ route('getChartStatusHandling') }}', // Menggunakan sintaks Blade yang benar
                    method: 'GET',
                    success: function(chartData) {
                        // Inisialisasi array untuk bulan-bulan (dimulai dari Januari)
                        var months = [];
                        for (var i = 1; i <= 12; i++) {
                            months.push(getMonthName(i));
                        }

                        // Memetakan total status 1 (status_2=0) dari data
                        var status1 = [];
                        var status2 = [];
                        for (var i = 0; i < 12; i++) {
                            status1.push(chartData[i].total_status_2_0);
                            status2.push(chartData[i].total_status_3);
                        }

                        var ctx = document.getElementById('myChart').getContext('2d');

                        // Gunakan variabel global myChart
                        myChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: months,
                                datasets: [{
                                    label: 'Open',
                                    data: status1,
                                    backgroundColor: 'rgba(54, 162, 235, 0.5)', // Warna biru untuk 'Open'
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 2
                                }, {
                                    label: 'Close',
                                    data: status2,
                                    backgroundColor: 'rgba(255, 99, 132, 0.5)', // Warna merah untuk 'Close'
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.1)' // Warna grid sumbu y
                                        },
                                        ticks: {
                                            color: 'rgba(0, 0, 0, 0.7)' // Warna label sumbu y
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.1)' // Warna grid sumbu x
                                        },
                                        ticks: {
                                            color: 'rgba(0, 0, 0, 0.7)' // Warna label sumbu x
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        labels: {
                                            color: 'rgba(0, 0, 0, 0.7)' // Warna label legenda
                                        }
                                    }
                                },
                                animation: {
                                    duration: 1500, // Durasi animasi
                                    easing: 'easeInOutQuart' // Efek animasi
                                }
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                    }
                });
            });


            document.addEventListener('DOMContentLoaded', function() {
                var pieData = {!! json_encode($formattedData) !!};

                Highcharts.chart('ChartPieTypeMaterial', {
                    chart: {
                        type: 'pie'
                    },
                    credits: {
                        enabled: false
                    },
                    title: {
                        text: 'Total Tipe Material'
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.y}</b>'
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                            }
                        }
                    },
                    series: [{
                        name: 'Total Type Materials',
                        colorByPoint: true,
                        data: pieData
                    }]
                });

                document.getElementById('type').addEventListener('change', FilterPieChartTipe);

                function FilterPieChartTipe() {
                    var jenis = document.getElementById('jenis').value;
                    var typeSelected = document.getElementById('type').value;
                    var kategori = document.getElementById('type').options[document.getElementById('type')
                        .selectedIndex].text;

                    console.log('Memilih kategori:', kategori);
                    var filterType;

                    if (jenis === 'frekuensi') {
                        if (typeSelected === 'kategori') {
                            filterType = 'kategori';
                        } else if (typeSelected === 'type_1') {
                            filterType = 'total_komplain';
                        } else if (typeSelected === 'type_2') {
                            filterType = 'total_klaim';
                        }
                    } else if (jenis === 'qty') {
                        if (typeSelected === 'kategori') {
                            filterType = 'qty_all';
                        } else if (typeSelected === 'type_1') {
                            filterType = 'qty_komplain';
                        } else if (typeSelected === 'type_2') {
                            filterType = 'qty_klaim';
                        }
                    } else if (jenis === 'pcs') {
                        if (typeSelected === 'kategori') {
                            filterType = 'pcs_all';
                        } else if (typeSelected === 'type_1') {
                            filterType = 'pcs_komplain';
                        } else if (typeSelected === 'type_2') {
                            filterType = 'pcs_klaim';
                        }
                    }

                    var startMonth = document.getElementById('start_month').value;
                    var endMonth = document.getElementById('end_month').value;

                    $.ajax({
                        url: '{{ route('FilterPieChartTipe') }}',
                        method: 'GET',
                        data: {
                            jenis: jenis,
                            type: typeSelected,
                            kategori: kategori,
                            start_month: startMonth,
                            end_month: endMonth
                        },
                        success: function(response) {
                            var data = response;
                            renderChart(data, filterType, jenis, kategori);
                            console.log("kategori masuk1: ", data);
                        }
                    });
                }

                function renderChart(data, filterType, jenis, kategori) {
                    var chartData = [];
                    console.log('Nilai kategori:', kategori);
                    for (var i = 0; i < data.length; i++) {
                        if (data[i][filterType] > 0) {
                            chartData.push({
                                name: data[i].type_name,
                                y: parseInt(data[i][filterType]),
                                qty: data[i].total_qty,
                                pcs: data[i].total_pcs,
                                total_klaim: data[i].total_klaim,
                                total_komplain: data[i].total_komplain,
                                qty_komplain: data[i].qty_komplain,
                                qty_klaim: data[i].qty_klaim,
                                pcs_komplain: data[i].pcs_komplain,
                                pcs_klaim: data[i].pcs_klaim,
                                qty_all: data[i].qty_all,
                                pcs_all: data[i].pcs_all
                            });
                        }
                    }

                    Highcharts.chart('ChartPieTypeMaterial', {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'Pie Chart Berdasarkan Tipe'
                        },
                        tooltip: {
                            formatter: function() {
                                var tooltip = '<b>' + this.point.name + '</b>: ';

                                if (jenis === 'qty') {
                                    if (kategori === 'All Kategori') {
                                        tooltip += this.point.qty_all + ' kg';
                                    } else if (kategori === 'Komplain') {
                                        tooltip += this.point.qty_komplain + ' kg';
                                    } else if (kategori === 'Klaim') {
                                        tooltip += this.point.qty_klaim + ' kg';
                                    }
                                } else if (jenis === 'pcs') {
                                    if (kategori === 'All Kategori') {
                                        tooltip += this.point.pcs_all + ' pcs';
                                    } else if (kategori === 'Komplain') {
                                        tooltip += this.point.pcs_komplain + ' pcs';
                                    } else if (kategori === 'Klaim') {
                                        tooltip += this.point.pcs_klaim + ' pcs';
                                    }
                                } else if (jenis === 'frekuensi') {
                                    if (kategori === 'All Kategori') {
                                        tooltip += '<br>' + 'Total Klaim: ' + this.point.total_klaim +
                                            '<br>' + ' Total Komplain: ' + this.point.total_komplain +
                                            '<br>' + 'Total Qty: ' + this.point.qty_all + '<br>' +
                                            'Total Pcs: ' + this.point.pcs_all;
                                    } else if (kategori === 'Komplain') {
                                        tooltip += 'Jumlah Komplain: ' + this.point.total_komplain +
                                            '<br>' + ' Total Qty: ' + this.point.qty_all;
                                    } else if (kategori === 'Klaim') {
                                        tooltip += 'Jumlah Klaim: ' + this.point.total_klaim + '<br>' +
                                            ' Total Pcs: ' + this.point.pcs_all;
                                    }
                                }
                                tooltip += ' (' + this.point.percentage.toFixed(1) + '%)';
                                return tooltip;
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: false
                                },
                                showInLegend: true
                            }
                        },
                        series: [{
                            name: 'Total Data',
                            data: chartData
                        }]
                    });
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('tipe2').addEventListener('change', updatePieChart);

                function updatePieChart() {
                    var jenis2 = document.getElementById('jenis2').value;
                    var typeSelected2 = document.getElementById('tipe2').value;
                    var kategori_2 = document.getElementById('tipe2').options[document.getElementById('tipe2')
                        .selectedIndex].text;

                    var filterType2;

                    if (jenis2 === 'frekuensi2') {
                        if (typeSelected2 === 'kategori_2') {
                            filterType2 = 'kategori_2';
                        } else if (typeSelected2 === 'type_1') {
                            filterType2 = 'total_komplain2';
                        } else if (typeSelected2 === 'type_2') {
                            filterType2 = 'total_klaim2';
                        }
                    } else if (jenis2 === 'qty') {
                        if (typeSelected2 === 'kategori_2') {
                            filterType2 = 'qty_all';
                        } else if (typeSelected2 === 'type_1') {
                            filterType2 = 'qty_komplain';
                        } else if (typeSelected2 === 'type_2') {
                            filterType2 = 'qty_klaim';
                        }
                    } else if (jenis2 === 'pcs') {
                        if (typeSelected2 === 'kategori_2') {
                            filterType2 = 'pcs_all';
                        } else if (typeSelected2 === 'type_1') {
                            filterType2 = 'pcs_komplain';
                        } else if (typeSelected2 === 'type_2') {
                            filterType2 = 'pcs_klaim';
                        }
                    }

                    var startMonth3 = document.getElementById('start_month3').value;
                    var endMonth3 = document.getElementById('end_month3').value;

                    $.ajax({
                        url: '{{ route('FilterPieChartProses') }}',
                        method: 'GET',
                        data: {
                            jenis2: jenis2,
                            tipe2: typeSelected2,
                            kategori_2: kategori_2,
                            start_month3: startMonth3,
                            end_month3: endMonth3
                        },
                        success: function(response) {
                            var data = response;
                            renderPieChart(data, filterType2, jenis2, kategori_2);
                            console.log("kategori masuk1: ", data);
                        }
                    });
                }

                function renderPieChart(data, filterType2, jenis2, kategori_2) {
                    var chartData = [];

                    // Memproses data yang diterima untuk grafik
                    for (var i = 0; i < data.length; i++) {
                        // Memeriksa apakah nilai filterType2 dari data saat ini tidak nol dan sesuai dengan filter yang dipilih
                        if (data[i][filterType2] > 0) {
                            // Menambahkan data ke chartData
                            chartData.push({
                                name: data[i].type_name,
                                y: parseInt(data[i][filterType2]),
                                qty: data[i].total_qty,
                                pcs: data[i].total_pcs,
                                total_klaim2: data[i].total_klaim2,
                                total_komplain2: data[i].total_komplain2,
                                qty_komplain: data[i].qty_komplain,
                                qty_klaim: data[i].qty_klaim,
                                pcs_komplain: data[i].pcs_komplain,
                                pcs_klaim: data[i].pcs_klaim,
                                qty_all: data[i].qty_all,
                                pcs_all: data[i].pcs_all
                            });
                            console.log("kategori masuk: ", data);
                        }
                    }

                    // Membuat grafik pie menggunakan Highcharts
                    Highcharts.chart('ChartPieProses', {
                        chart: {
                            type: 'pie'
                        },
                        credits: {
                            enabled: false
                        },
                        title: {
                            text: 'Total Proses'
                        },
                        tooltip: {
                            formatter: function() {
                                var tooltip = '<b>' + this.point.name + '</b>: ';
                                if (jenis2 === 'qty') {
                                    if (kategori_2 === 'All Kategori') {
                                        tooltip += this.point.qty_all + ' kg';
                                    } else if (kategori_2 === 'Komplain') {
                                        tooltip += this.point.qty_komplain + ' kg';
                                    } else if (kategori_2 === 'Klaim') {
                                        tooltip += this.point.qty_klaim + ' kg';
                                    }
                                } else if (jenis2 === 'pcs') {
                                    if (kategori_2 === 'All Kategori') {
                                        tooltip += this.point.pcs_all + ' pcs';
                                    } else if (kategori_2 === 'Komplain') {
                                        tooltip += this.point.pcs_komplain + ' pcs';
                                    } else if (kategori_2 === 'Klaim') {
                                        tooltip += this.point.pcs_klaim + ' pcs';
                                    }
                                } else if (jenis2 === 'frekuensi2') {
                                    if (kategori_2 === 'All Kategori') {
                                        tooltip += '<br>' + 'Total Klaim: ' + this.point.total_klaim2 +
                                            '<br>' + ' Total Komplain: ' + this.point.total_komplain2 +
                                            '<br>' + 'Total Qty: ' + this.point.qty_all + '<br>' +
                                            'Total Pcs: ' + this.point.pcs_all;
                                    } else if (kategori_2 === 'Komplain') {
                                        tooltip += 'Jumlah Komplain: ' + this.point.total_komplain2 +
                                            '<br>' + ' Total Qty: ' + this.point.qty_all;
                                    } else if (kategori_2 === 'Klaim') {
                                        tooltip += 'Jumlah Klaim: ' + this.point.total_klaim2 + '<br>' +
                                            ' Total Pcs: ' + this.point.pcs_all;
                                    }
                                }

                                tooltip += ' (' + this.point.percentage.toFixed(1) + '%)';
                                return tooltip;
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: false
                                },
                                showInLegend: true
                            }
                        },
                        series: [{
                            name: 'Total Data',
                            data: chartData
                        }]
                    });
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                var chartData = {!! json_encode($pieProses) !!};
                console.log(chartData); // Pastikan untuk memeriksa data yang tercetak di konsol

                Highcharts.chart('ChartPieProses', {
                    chart: {
                        type: 'pie'
                    },
                    credits: {
                        enabled: false
                    },
                    title: {
                        text: 'Total Proses'
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                            }
                        }
                    },
                    series: [{
                        name: 'Process',
                        colorByPoint: true,
                        data: chartData
                    }]
                });
            });


            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('tipe3').addEventListener('change', updatePieChart);
                document.getElementById('jenis3').addEventListener('change', updatePieChart);

                function updatePieChart() {
                    var jenis3 = document.getElementById('jenis3').value;
                    var typeSelected3 = document.getElementById('tipe3').value;

                    var filterType3;

                    if (typeSelected3 === 'type_1') {
                        filterType3 = 'total_komplain';
                    } else if (typeSelected3 === 'type_2') {
                        filterType3 = 'total_klaim';
                    } else {
                        filterType3 = 'total_all';
                    }

                    var startMonth4 = document.getElementById('start_month4').value;
                    var endMonth4 = document.getElementById('end_month4').value;

                    $.ajax({
                        url: '{{ route('filterPieChartNG') }}',
                        method: 'GET',
                        data: {
                            jenis3: jenis3,
                            tipe3: typeSelected3,
                            start_month4: startMonth4,
                            end_month4: endMonth4
                        },
                        success: function(response) {
                            var data = response;
                            renderPieChart(data, filterType3, jenis3, typeSelected3);
                        }
                    });
                }

                function renderPieChart(data, filterType3, jenis3, typeSelected3) {
                    var chartData = [];

                    for (var i = 0; i < data.length; i++) {
                        if (data[i][filterType3] > 0) {
                            chartData.push({
                                name: data[i].category,
                                y: parseInt(data[i][filterType3]),
                                total_komplain: data[i].total_komplain,
                                total_klaim: data[i].total_klaim,
                                total_all: data[i].total_all
                            });
                        }
                    }

                    Highcharts.chart('ChartPieNG', {
                        chart: {
                            type: 'pie'
                        },
                        credits: {
                            enabled: false
                        },
                        title: {
                            text: 'Total Proses'
                        },
                        tooltip: {
                            formatter: function() {
                                var tooltip = '<b>' + this.point.name + '</b>: ';
                                if (typeSelected3 === 'type_1') {
                                    tooltip += 'Jumlah Komplain: ' + this.point.total_komplain;
                                } else if (typeSelected3 === 'type_2') {
                                    tooltip += 'Jumlah Klaim: ' + this.point.total_klaim;
                                } else {
                                    tooltip += 'Jumlah Komplain: ' + this.point.total_komplain + '<br>';
                                    tooltip += 'Jumlah Klaim: ' + this.point.total_klaim + '<br>';
                                    tooltip += 'Jumlah Total: ' + this.point.total_all;
                                }
                                tooltip += ' (' + this.point.percentage.toFixed(1) + '%)';
                                return tooltip;
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: false
                                },
                                showInLegend: true
                            }
                        },
                        series: [{
                            name: 'Total Data',
                            data: chartData
                        }]
                    });
                }
            });
        </script>
    </main>
@endsection
