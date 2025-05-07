@extends('layout')
@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.20.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.highcharts.com/highcharts.js"></script>


    <main id="main" class="main">

        <section class="section">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div id="areaPatrolChart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div id="picAreaChart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div id="kategoriPatrolChart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div id="safetyPatrolChart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div id="lingkunganPatrolChart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
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

    </main><!-- End #main -->

    <script>
        const areaPatrolData = <?php echo json_encode($areaPatrolData); ?>;
        const labels = <?php echo json_encode($piclabels); ?>;
        const totalEntries = <?php echo json_encode($totalEntries); ?>;

        // Create chart
        Highcharts.chart('picAreaChart', {
            chart: {
                type: 'column'
            },
            credits: {
                enabled: false
            },
            title: {
                text: 'Total PIC Area'
            },
            xAxis: {
                categories: labels,
                title: {
                    text: 'PIC Area'
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Jumlah PIC'
                }
            },
            tooltip: {
                formatter: function() {
                    var tooltip = '<b>' + this.x + '</b><br/>'; // PIC area
                    tooltip += 'Total Form: ' + this.y +
                        '<br/><br/>'; // Jumlah form secara keseluruhan

                    // Informasi form berdasarkan area patrol
                    tooltip += '<b>Detail Area Patrol:</b><br/>';
                    for (var area in areaPatrolData[this.x]) {
                        tooltip += area + ': ' + areaPatrolData[this.x][area] + '<br/>';
                    }

                    return tooltip;
                }
            },
            series: [{
                name: 'Jumlah Form',
                data: totalEntries
            }]
        });
    </script>

    <script>
        // Data areaPatrolsData
        const areaPatrolsData = <?php echo json_encode($areaPatrolsData); ?>;
        const areaLabels = areaPatrolsData.map(item => item.area_patrol);
        const areaCounts = areaPatrolsData.map(item => item.area_patrols);

        // Define custom colors for data points
        const colors = ['#003285', '#FF7F3E', '#2A629A', '#FFDA78', '#EE4E4E', '#F6EEC9',
            '#799351'
        ]; // Add more colors if needed

        Highcharts.chart('areaPatrolChart', {
            chart: {
                type: 'column'
            },
            credits: {
                enabled: false
            },
            title: {
                text: 'Total Area Patrol'
            },
            xAxis: {
                categories: areaLabels,
                title: {
                    text: 'Area Patrol'
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Jumlah'
                }
            },
            series: [{
                name: 'Area Patrol',
                data: areaCounts.map((count, index) => ({
                    y: count,
                    color: colors[index % colors.length]
                }))
            }],
            tooltip: {
                formatter: function() {
                    return '<b>' + this.x + '</b><br/>Jumlah: ' + this.y;
                }
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data kategoriCounts
            const kategoriCounts = <?php echo json_encode($kategoriCounts); ?>;

            // Extract labels
            const labels = Object.keys(kategoriCounts);

            // Prepare series data
            const seriesData = Object.keys(kategoriCounts[labels[0]]).map((kategori, index) => ({
                name: (index + 1).toString(), // Assign series names as numbers from 1 to 5
                data: labels.map(label => kategoriCounts[label][kategori])
            }));

            // Create chart for kategoriCounts
            Highcharts.chart('kategoriPatrolChart', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Kategori 5R/5S'
                },
                xAxis: {
                    categories: labels,
                    title: {
                        text: 'Kategori 5R/5S'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Jumlah Nilai'
                    }
                },
                credits: {
                    enabled: false
                },
                series: seriesData,
                tooltip: {
                    shared: true,
                    formatter: function() {
                        let tooltip = '<b>' + this.x + '</b><br/>';
                        this.points.forEach(point => {
                            tooltip += 'Nilai ' + point.series.name + ': ' + point.y + '<br/>';
                        });
                        return tooltip;
                    }
                }
            });
        });
    </script>



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data SafetyCounts
            const safetyCounts = <?php echo json_encode($safetyCounts); ?>;

            // Extract labels
            const labels = Object.keys(safetyCounts);

            // Prepare series data
            const seriesData = Object.keys(safetyCounts[labels[0]]).map((safety, index) => ({
                name: (index + 1).toString(), // Assign series names as numbers from 1 to 5
                data: labels.map(label => safetyCounts[label][safety])
            }));

            // Create chart
            Highcharts.chart('safetyPatrolChart', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Kategori Safety'
                },
                xAxis: {
                    categories: labels,
                    title: {
                        text: 'Kategori Safety'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Jumlah Nilai'
                    }
                },
                credits: {
                    enabled: false
                },
                series: seriesData,
                tooltip: {
                    shared: true,
                    formatter: function() {
                        let tooltip = '<b>' + this.x + '</b><br/>';
                        this.points.forEach(point => {
                            tooltip += 'Nilai ' + point.series.name + ': ' + point.y + '<br/>';
                        });
                        return tooltip;
                    }
                }
            });
        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data lingkunganCounts
            const lingkunganCounts = <?php echo json_encode($lingkunganCounts); ?>;

            // Extract labels
            const labels = Object.keys(lingkunganCounts);

            // Prepare series data
            const seriesData = Object.keys(lingkunganCounts[labels[0]]).map((lingkungan, index) => ({
                name: (index + 1).toString(), // Assign series names as numbers from 1 to 5
                data: labels.map(label => lingkunganCounts[label][lingkungan])
            }));

            // Create chart
            Highcharts.chart('lingkunganPatrolChart', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Kategori Lingkungan'
                },
                xAxis: {
                    categories: labels,
                    title: {
                        text: 'Kategori Lingkungan'
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Jumlah Nilai'
                    }
                },
                credits: {
                    enabled: false
                },
                series: seriesData,
                tooltip: {
                    shared: true,
                    formatter: function() {
                        let tooltip = '<b>' + this.x + '</b><br/>';
                        this.points.forEach(point => {
                            tooltip += 'Nilai ' + point.series.name + ': ' + point.y + '<br/>';
                        });
                        return tooltip;
                    }
                }
            });
        })
    </script>
@endsection
