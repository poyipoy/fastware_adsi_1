@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Dashboard Suggestion System</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Suggestion System</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Suggestion System / Departemen<span></span></h5>
                            <div class="container">
                                <div class="row align-items-center">
                                    <div class="col-lg-3">
                                        <label for="end_periode">Bagian:</label>
                                        <select id="type" class="form-select form-select-sm"
                                            aria-label=".form-select-sm example" onclick="handleSelectChange()">
                                            <option selected>--- Pilih Bagian ---</option>
                                            <option value="TotalSS">Total Company</option>
                                            <option value="Sales">Sales</option>
                                            <option value="HT">HT</option>
                                            <option value="SupplyChainProduction">Supply Chain & Production</option>
                                            <option value="FinnAccHrgaIT">Finn Acc Hrga IT</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row" style="margin-top: 10%">
                                    <div class="col-lg-12">
                                        <div id="chartBarSection" style="min-width: 310px; height: 500px; margin: 0 auto">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Suggestion System / All Employee</h5>
                            <div class="container">
                                <div class="row align-items-center">
                                    <div class="col-lg-3">
                                        <label for="start_periode">Pilih Bulan Awal:</label>
                                        <input type="month" id="start_periode" name="start_periode" class="form-control">
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="end_periode">Pilih Bulan Akhir:</label>
                                        <input type="month" id="end_periode" name="end_periode" class="form-control">
                                    </div>
                                    <div class="row" style="margin-top: 10%">
                                        <div class="col-lg-12">
                                            <div id="chartBarMountEmployee"
                                                style="min-width: 310px; height: 500px; margin: 0 auto"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Suggestion System / Employee</h5>
                            <div class="container">
                                <div class="row align-items-center">
                                    <div class="col-lg-3">
                                        <label for="startDate">Pilih Bulan Awal:</label>
                                        <input type="month" id="startDate" name="startDate" class="form-control">
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="endDate">Pilih Bulan Akhir:</label>
                                        <input type="month" id="endDate" name="endDate" class="form-control">
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="employeeType">Employee</label>
                                        <select id="employeeType" class="form-select form-select-sm"
                                            aria-label=".form-select-sm example">
                                            <option selected>--- Pilih Employee ---</option>
                                            <option value="AllEmployee">All Employee</option>
                                            <option value="User">{{ Auth::user()->name }}</option>
                                        </select>
                                    </div>
                                    <div class="row" style="margin-top: 10%">
                                        <div class="col-lg-12">
                                            <div id="chartBarAllEmployee"
                                                style="min-width: 310px; height: 500px; margin: 0 auto"></div>
                                        </div>
                                    </div>
                                </div>
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
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <!-- Load jQuery library -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            // Token CSRF disimpan di dalam tag <script>
            window.csrfToken = "{{ csrf_token() }}";
        </script>
        <script type="text/javascript">
            //chart 1
            $(document).ready(function() {
                function fetchChartData() {
                    $.ajax({
                        url: '{{ route('chartSection') }}', // Sesuaikan dengan route Anda
                        method: 'GET',
                        success: function(response) {
                            var categories = response.categories;
                            var series = response.series;

                            Highcharts.chart('chartBarSection', {
                                chart: {
                                    type: 'column'
                                },
                                title: {
                                    text: 'Jumlah Sumbang Saran berdasarkan Departemen'
                                },
                                xAxis: {
                                    categories: categories,
                                    crosshair: true
                                },
                                yAxis: {
                                    min: 0,
                                    title: {
                                        text: 'Jumlah Sumbang Saran'
                                    }
                                },
                                series: series
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error: ' + status + error);
                        }
                    });
                }
                // Ambil data dan tampilkan chart saat halaman dimuat
                fetchChartData();
            });
            //filter chart 1
            const roleData = {
                'Sales': [2, 3], // DH Sales-Maketing, SC Sales-Marketing
                'HT': [5, 9, 31, 22], // DH Productions, SC Cutting Machining Feed PPC, SC Custome Bubut, SC Heat Treatment
                'SupplyChainProduction': [7, 30], // DH Logistic Warehouse, SC Logistic Warehouse
                'FinnAccHrgaIT': [11, 12, 32, 14], // DH Fin Acc HRGA IT, SC Finance, SC Accounting, SC HRGA IT
                'TotalSS': [] // Tambahkan opsi TotalSS dengan array kosong
            };

            function handleSelectChange() {
                const selectedValue = $('#type').val();
                if (roleData[selectedValue]) {
                    const roles = roleData[selectedValue];
                    fetchChartData(roles);
                } else if (selectedValue === 'TotalSS') {
                    fetchTotalSumbangSaranData(); // Panggil fungsi untuk mengambil total data sumbangsaran
                } else {
                    $('#chartBarSection').html('<p>No data available</p>');
                }
            }

            function fetchChartData(roles) {
                $.ajax({
                    url: '{{ route('chartEmployee') }}',
                    method: 'POST',
                    data: {
                        roles: roles
                    },
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    success: function(response) {
                        renderChart(response.categories, response.series);
                    },
                    error: function(error) {
                        console.error('Error fetching data', error);
                    }
                });
            }

            function fetchTotalSumbangSaranData() {
                $.ajax({
                    url: '{{ route('chartEmployee') }}',
                    method: 'POST',
                    data: {
                        roles: []
                    },
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    success: function(response) {
                        renderChart(response.categories, response.series);
                    },
                    error: function(error) {
                        console.error('Error fetching data', error);
                    }
                });
            }

            function renderChart(categories, series) {
                Highcharts.chart('chartBarSection', {
                    chart: {
                        zoomType: 'xy'
                    },
                    title: {
                        text: 'Jumlah Sumbang Saran berdasarkan Departemen'
                    },
                    xAxis: [{
                        categories: categories,
                        crosshair: true
                    }],
                    yAxis: [{ // Primary yAxis
                        labels: {
                            format: '{value}',
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        },
                        title: {
                            text: 'Jumlah Sumbang Saran',
                            style: {
                                color: Highcharts.getOptions().colors[1]
                            }
                        }
                    }, { // Secondary yAxis
                        title: {
                            text: 'Total Sumbang Saran',
                            style: {
                                color: Highcharts.getOptions().colors[0]
                            }
                        },
                        labels: {
                            format: '{value}',
                            style: {
                                color: Highcharts.getOptions().colors[0]
                            }
                        },
                        opposite: true
                    }],
                    tooltip: {
                        shared: true
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'left',
                        x: 80,
                        verticalAlign: 'top',
                        y: 55,
                        floating: true,
                        backgroundColor: Highcharts.defaultOptions.legend.backgroundColor || 'rgba(255,255,255,0.25)'
                    },
                    series: series
                });
            }

            $(document).ready(function() {
                $('#type').on('change', handleSelectChange);
                // Ambil data dan tampilkan chart saat halaman dimuat
                fetchTotalSumbangSaranData();
            });

            // chart2
            const employeeData = {
                'AllEmployee': 'All Employee',
                'User': 'User'
            };

            function handleFilterChange() {
                const employeeType = $('#employeeType').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                fetchEmployeeChartData(employeeType, startDate, endDate);
            }

            function fetchEmployeeChartData(employeeType, startDate, endDate) {
                $.ajax({
                    url: '{{ route('chartUser') }}',
                    method: 'POST',
                    data: {
                        employeeType: employeeType,
                        startDate: startDate,
                        endDate: endDate
                    },
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    success: function(response) {
                        renderEmployeeChart(response.data);
                    },
                    error: function(error) {
                        console.error('Error fetching data', error);
                    }
                });
            }

            function renderEmployeeChart(data) {
                Highcharts.chart('chartBarAllEmployee', {
                    chart: {
                        type: 'bar'
                    },
                    title: {
                        text: 'Chart Per Employee'
                    },
                    xAxis: {
                        type: 'category',
                        title: {
                            text: 'Employee'
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Jumlah SS'
                        }
                    },
                    series: [{
                        name: 'Jumlah SS',
                        data: data
                    }]
                });
            }

            $(document).ready(function() {
                $('#endDate, #employeeType').on('change', handleFilterChange);
            });

            $(document).ready(function() {
                function handleFilterChange() {
                    const startMonth = $('#start_periode').val();
                    const endMonth = $('#end_periode').val();
                    fetchChartData(startMonth, endMonth);
                }

                function fetchChartData(startMonth, endMonth) {
                    $.ajax({
                        url: '{{ route('chartMountEmployee') }}',
                        method: 'POST',
                        data: {
                            start_periode: startMonth,
                            end_periode: endMonth
                        },
                        headers: {
                            'X-CSRF-TOKEN': window.csrfToken
                        },
                        success: function(response) {
                            renderChart(response.total, response.data);
                        },
                        error: function(error) {
                            console.error('Error fetching data', error);
                        }
                    });
                }

                function renderChart(total, chartData) {
                    const seriesData = [{
                            name: 'Total SS',
                            data: [{
                                name: 'Total',
                                y: total
                            }]
                        },
                        {
                            name: 'Company',
                            data: chartData
                        }
                    ];

                    Highcharts.chart('chartBarMountEmployee', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'SS perCompany'
                        },
                        xAxis: {
                            type: 'category',
                            title: {
                                text: 'Company'
                            }
                        },
                        yAxis: {
                            title: {
                                text: 'Jumlah SS'
                            }
                        },
                        series: seriesData,
                        tooltip: {
                            shared: true,
                            formatter: function() {
                                let s = '<b>' + this.x + '</b>';

                                this.points.forEach(function(point) {
                                    s += '<br/>' + point.series.name + ': ' + point.y;
                                });

                                return s;
                            }
                        },
                        plotOptions: {
                            column: {
                                stacking: 'normal'
                            }
                        }
                    });
                }

                // Event listeners for the date input changes
                $('#start_periode, #end_periode').on('change', handleFilterChange);

                // Initial fetch
                handleFilterChange();
            });
        </script>
    </main><!-- End #main -->
@endsection
