@extends('layout')

@section('content')
    <style>
        /* Umum */
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        /* Dropdown select */
        .dropdown-select {
            margin: 20px;
            text-align: left;
        }

        .dropdown-select label {
            margin-right: 10px;
            font-weight: bold;
        }

        .dropdown-select select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        /* Container untuk grafik radar */
        #radarChartContainer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
            box-sizing: border-box;
            justify-items: stretch;
            /* Mengubah dari center ke stretch untuk lebar penuh */
        }

        /* Card untuk setiap grafik radar */
        .card {
            padding: 20px;
            border: 1px solid #000000;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            /* Mengubah dari center ke stretch untuk lebar penuh */
        }


        /* Profil pengguna */
        .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            width: 100%;
        }

        .profile-icon {
            font-size: 50px;
            color: #555;
            margin-right: 15px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name,
        .user-job {
            font-weight: bold;
            font-size: 14px;
            margin: 2px 0;
        }

        /* Judul card */
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;

        }

        /* Canvas */
        canvas {
            width: 100%;
            height: 500px !important;
            /* max-width: 500%; */

            /* Mengubah dari 500px ke 100% */
        }
    </style>

    <main id="main" class="main">
        <section class="section dashboard">
            <div class="card" style="width: 100%">
                <!-- Dropdown select di luar inner-card -->
                <div class="dropdown-select">
                    <label for="options">Pilih Job Position:</label>
                    <select id="options" name="options" onchange="updateChart()">
                        <option value="">----- Pilih Position ------</option>
                        @foreach ($jobPositions as $jobPosition)
                            <option value="{{ $jobPosition }}">{{ $jobPosition }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="radarChartContainer" class="row">
                    <!-- Grafik radar akan di-render di sini -->
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
        <!-- Include Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            let charts = [];

            function updateChart() {
                const jobPosition = document.getElementById('options').value;

                if (jobPosition) {
                    $.ajax({
                        url: '{{ route('get-competency-data') }}',
                        type: 'GET',
                        data: {
                            job_position: jobPosition
                        },
                        success: function(response) {
                            console.log('AJAX Response:', response);

                            $('#radarChartContainer').empty();
                            charts.forEach(chart => chart.destroy());
                            charts = [];

                            if (response.length > 0) {
                                const maxCharts = 10;
                                const chartsToDisplay = response.slice(0, maxCharts);

                                chartsToDisplay.forEach((user, index) => {
                                    const labels = [
                                        'Total Nilai Technical Competency',
                                        'Total Nilai Softskills',
                                        'Total Nilai Additional'
                                    ];
                                    const dataPoints = [
                                        parseInt(user.total_nilai_tc) || 0,
                                        parseInt(user.total_nilai_sk) || 0,
                                        parseInt(user.total_nilai_ad) || 0,
                                    ];
                                    const standardDataPoints = [
                                        parseInt(user.standar_nilai_tc) || 0,
                                        parseInt(user.standar_nilai_sk) || 0,
                                        parseInt(user.standar_nilai_ad) || 0,
                                    ];

                                    console.log('Standard Data Points:', standardDataPoints);

                                    const suggestedMax = 12;
                                    const minDataPoint = Math.min(...dataPoints, ...standardDataPoints);
                                    const suggestedMin = minDataPoint - 1 < 0 ? 0 : minDataPoint - 1;

                                    const canvasId = 'radarChart' + index;
                                    $('#radarChartContainer').append(
                                        '<div class="card">' +
                                        '<div class="user-profile">' +
                                        '<i class="fas fa-user-circle profile-icon"></i>' +
                                        '<div class="user-details">' +
                                        '<span class="user-name" data-user-id="' + user.id_user +
                                        '">Nama Pengguna : ' + user.name + '</span>' +
                                        '<span class="user-job">Job Position : ' + jobPosition +
                                        '</span>' +
                                        '<input type="hidden" class="user-id-hidden" value="' + user
                                        .id_user + '">' +
                                        '</div>' +
                                        '</div>' +
                                        '<div class="card-title">Chart Competency</div>' +
                                        '<div class="dropdown-container" style="text-align: left; margin-bottom: 10px;">' +
                                        '<label for="data-select-' + index + '"></label>' +
                                        '<select class="data-select" id="data-select-' + index +
                                        '" onchange="updateChartData(' + index + ')">' +
                                        '<option value="">-------- Pilih Competency --------</option>' +
                                        '<option value="total_nilai_tc">Technical Competency</option>' +
                                        '<option value="total_nilai_sk">Soft Skill</option>' +
                                        '<option value="total_nilai_ad">Additional</option>' +
                                        '</select>' +
                                        '</div>' +
                                        '<canvas id="' + canvasId +
                                        '" width="500" height="490"></canvas>' +
                                        '<button type="button" onclick="btnDsDetail(' + user.id_user +
                                        ')" style="margin-top: 10px; width: 30%">Competency Employee</button>' +
                                        '</div>'
                                    );

                                    // Create the radar chart directly here
                                    const ctx = document.getElementById(canvasId).getContext('2d');
                                    const chart = new Chart(ctx, {
                                        type: 'radar',
                                        data: {
                                            labels: labels,
                                            datasets: [{
                                                    label: 'Nilai Aktual',
                                                    data: dataPoints, // Data nilai aktual
                                                    fill: true,
                                                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                                    borderColor: 'rgba(54, 162, 235, 1)',
                                                    borderWidth: 3, // Garis lebih tebal untuk nilai aktual
                                                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                                                    pointBorderColor: '#fff',
                                                    pointHoverBackgroundColor: '#fff',
                                                    pointHoverBorderColor: 'rgba(54, 162, 235, 1)',
                                                },
                                                {
                                                    label: 'Nilai Standar',
                                                    data: standardDataPoints, // Data nilai standar dari response
                                                    fill: true,
                                                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                                    borderColor: 'rgba(255, 99, 132, 1)',
                                                    borderWidth: 1.5, // Garis lebih tebal untuk nilai standar
                                                    borderDash: [5, 5],
                                                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                                                    pointBorderColor: '#fff',
                                                    pointHoverBackgroundColor: '#fff',
                                                    pointHoverBorderColor: 'rgba(255, 99, 132, 1)',
                                                }
                                            ]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: true,
                                            scales: {
                                                r: {
                                                    angleLines: {
                                                        display: true, // Menampilkan garis sudut
                                                    },
                                                    suggestedMin: suggestedMin, // Minimum skala
                                                    suggestedMax: suggestedMax, // Maksimum skala
                                                    ticks: {
                                                        beginAtZero: true,
                                                        stepSize: 2, // Jarak antar tick
                                                        backdropPaddingX: 2, // Padding horizontal untuk ticks
                                                        backdropPaddingY: 2, // Padding vertikal untuk ticks
                                                        maxTicksLimit: 6, // Batas jumlah ticks
                                                    },
                                                    pointLabels: {
                                                        font: {
                                                            size: 16 // Ukuran font label titik
                                                        },
                                                        padding: 2 // Padding untuk label titik
                                                    }
                                                }
                                            },
                                            layout: {
                                                padding: {
                                                    left: 20,
                                                    right: 20,
                                                    top: 20,
                                                    bottom: 20
                                                }
                                            },
                                            plugins: {
                                                legend: {
                                                    display: true,
                                                    labels: {
                                                        font: {
                                                            size: 12 // Ukuran font legenda
                                                        }
                                                    }
                                                },
                                                tooltip: {
                                                    enabled: true,
                                                    callbacks: {
                                                        label: function(tooltipItem) {
                                                            return `Value: ${tooltipItem.raw}`;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    });
                                    charts.push(chart);
                                });
                            } else {
                                $('#radarChartContainer').append(
                                    '<div class="card"><p>Data tidak ditemukan untuk posisi pekerjaan yang dipilih.</p></div>'
                                );
                                console.log('No data found for the selected job position.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                        }
                    });
                } else {
                    $('#radarChartContainer').empty();
                    console.log('No job position selected.');
                }
            }

            function updateChartData(index) {
                const selectedDataType = document.getElementById('data-select-' + index).value;

                // Mengambil id_user dari elemen hidden input
                const userId = document.querySelectorAll('.user-id-hidden')[index].value;

                // Menampilkan id_user di console log
                console.log('Selected user ID:', userId);

                $.ajax({
                    url: '{{ route('get-competency-filter') }}',
                    type: 'GET',
                    data: {
                        job_position: document.getElementById('options').value,
                        data_type: selectedDataType,
                        user_id: userId
                    },
                    success: function(response) {
                        const chart = charts[index];
                        let labels = [];
                        let dataPoints1 = [];
                        let dataPoints2 = [];

                        // Filter response to only include data for the selected user
                        const filteredResponse = response.filter(item => item.id_user == userId);

                        if (selectedDataType === 'total_nilai_tc') {
                            filteredResponse.forEach(item => {
                                labels.push(item.keterangan_tc);
                                dataPoints1.push(parseInt(item.total_nilai_tc) || 0);
                                dataPoints2.push(parseInt(item.tc_nilai) ||
                                    0); // Compare with another value, e.g., max value
                            });
                        } else if (selectedDataType === 'total_nilai_sk') {
                            filteredResponse.forEach(item => {
                                labels.push(item.keterangan_sk);
                                dataPoints1.push(parseInt(item.total_nilai_sk) || 0);
                                dataPoints2.push(parseInt(item.sk_nilai) ||
                                    0); // Compare with another value, e.g., max value
                            });
                        } else if (selectedDataType === 'total_nilai_ad') {
                            filteredResponse.forEach(item => {
                                labels.push(item.keterangan_ad);
                                dataPoints1.push(parseInt(item.total_nilai_ad) || 0);
                                dataPoints2.push(parseInt(item.ad_nilai) ||
                                    0); // Compare with another value, e.g., max value
                            });
                        }

                        // Update chart with new labels and data points for the specific user
                        chart.data.labels = labels;
                        chart.data.datasets[0].data = dataPoints1;
                        chart.data.datasets[1].data = dataPoints2; // Add the second dataset for comparison
                        chart.update();
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                    }
                });
            }

            function btnDsDetail(userId) {
                const jobPosition = document.getElementById('options').value;
                // Redirect ke controller dsDetailCompetency dengan mengirimkan id_user dan id_job_position sebagai parameter
                window.location.href = `{{ route('dsDetailCompetency') }}?id_user=${userId}&id_job_position=${jobPosition}`;
            }

            $(document).on('change', '.data-select', function() {
                const index = $(this).attr('id').split('-')[2];
                const userId = $(this).closest('.inner-card').find('.user-name').data('user-id');
                updateChartData(index, userId);
            });

            $('#options').on('change', function() {
                updateChart();
            });

            $(document).ready(function() {
                updateChart();
            });
        </script>
    </main>
@endsection
