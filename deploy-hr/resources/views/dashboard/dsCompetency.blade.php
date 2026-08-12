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
            width: 100% !important;
            max-width: 450px !important;
            max-height: 450px !important;
            margin: 0 auto;
        }
    </style>

    <main id="main" class="main">
        <section class="section dashboard">
            <div class="card" style="width: 100%">
                <!-- Dropdown select di luar inner-card -->
                <div class="dropdown-select d-flex gap-3 align-items-center">
                    <div>
                        <label for="options">Pilih Job Position:</label>
                        <select id="options" name="options" onchange="updateChart()">
                            <option value="">----- Pilih Position ------</option>
                            @foreach ($jobPositions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filter-tahun">Tahun:</label>
                        <select id="filter-tahun" name="tahun" onchange="updateChart()">
                            @foreach (\App\Models\TrsPenilaianTc::getAvailableYears() as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
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

            function formatRadarLabels(labels) {
                return labels.map(label => {
                    if (Array.isArray(label)) return label;
                    if (!label) return '';
                    const words = String(label).split(' ');
                    let lines = [];
                    let currentLine = '';
                    words.forEach(word => {
                        if ((currentLine + ' ' + word).trim().length > 16 && currentLine !== '') {
                            lines.push(currentLine);
                            currentLine = word;
                        } else {
                            currentLine = (currentLine + ' ' + word).trim();
                        }
                    });
                    if (currentLine) lines.push(currentLine);
                    return lines.length > 1 ? lines : label;
                });
            }

            function updateChart() {
                const jobPositionSelect = document.getElementById('options');
                const jobPositionId = jobPositionSelect.value;
                const tahunFilter = document.getElementById('filter-tahun').value;

                if (jobPositionId) {
                    $.ajax({
                        url: '{{ route('get-competency-data') }}',
                        type: 'GET',
                        data: {
                            job_position: jobPositionId,
                            tahun: tahunFilter
                        },
                        success: function(response) {
                            console.log('AJAX Response:', response);
                            window.initialCompetencyUsers = response;

                            $('#radarChartContainer').empty();
                            charts.forEach(chart => chart.destroy());
                            charts = [];

                            if (response.length > 0) {
                                const maxCharts = 10;
                                const chartsToDisplay = response.slice(0, maxCharts);

                                chartsToDisplay.forEach((user, index) => {
                                    const labels = formatRadarLabels([
                                        'Technical Competency',
                                        'Softskills Competency',
                                        'Additional Competency'
                                    ]);
                                    const dataPoints = [
                                        parseFloat(user.total_nilai_tc) || 0,
                                        parseFloat(user.total_nilai_sk) || 0,
                                        parseFloat(user.total_nilai_ad) || 0,
                                    ];
                                    const standardDataPoints = [
                                        parseFloat(user.standar_nilai_tc) || 0,
                                        parseFloat(user.standar_nilai_sk) || 0,
                                        parseFloat(user.standar_nilai_ad) || 0,
                                    ];

                                    const suggestedMax = 12;
                                    const suggestedMin = 0;

                                    const canvasId = 'radarChart' + index;
                                    $('#radarChartContainer').append(
                                        '<div class="card">' +
                                        '<div class="user-profile">' +
                                        '<i class="fas fa-user-circle profile-icon"></i>' +
                                        '<div class="user-details">' +
                                        '<span class="user-name" data-user-id="' + user.id_user +
                                        '">Nama Pengguna : ' + user.name + '</span>' +
                                        '<span class="user-job">Job Position : ' + (user.job_position_name || '') +
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
                                        ')" style="margin-top: 10px; width: 30%"><i class="bi bi-person-badge me-1"></i>Individual Profile</button>' +
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
                                                    data: dataPoints,
                                                    fill: true,
                                                    backgroundColor: 'rgba(37, 99, 235, 0.2)', // Royal blue transparan lembut
                                                    borderColor: '#2563eb', // Blue-600
                                                    borderWidth: 3,
                                                    pointBackgroundColor: '#2563eb',
                                                    pointBorderColor: '#fff',
                                                    pointBorderWidth: 2,
                                                    pointRadius: 4,
                                                    pointHoverRadius: 6,
                                                },
                                                {
                                                    label: 'Nilai Standar',
                                                    data: standardDataPoints,
                                                    fill: false, // Tanpa fill agar tidak membuat grafik keruh!
                                                    borderColor: '#f59e0b', // Amber/Gold putus-putus
                                                    borderWidth: 2,
                                                    borderDash: [5, 5],
                                                    pointBackgroundColor: '#f59e0b',
                                                    pointBorderColor: '#fff',
                                                    pointBorderWidth: 1.5,
                                                    pointRadius: 3,
                                                    pointHoverRadius: 5,
                                                }
                                            ]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: true,
                                            scales: {
                                                r: {
                                                    angleLines: {
                                                        display: true,
                                                        color: '#cbd5e1', // Slate-300 lembut
                                                        lineWidth: 1
                                                    },
                                                    grid: {
                                                        color: '#e2e8f0', // Slate-200
                                                        lineWidth: 1
                                                    },
                                                    suggestedMin: suggestedMin,
                                                    suggestedMax: suggestedMax,
                                                    min: 0,
                                                    max: 12,
                                                    ticks: {
                                                        beginAtZero: true,
                                                        stepSize: 2,
                                                        backdropColor: 'transparent', // Bersih tanpa kotak putih
                                                        color: '#64748b',
                                                        font: {
                                                            size: 10,
                                                            weight: 'bold'
                                                        }
                                                    },
                                                    pointLabels: {
                                                        font: {
                                                            size: 12,
                                                            weight: '600'
                                                        },
                                                        color: '#334155',
                                                        padding: 8
                                                    }
                                                }
                                            },
                                            layout: {
                                                padding: {
                                                    left: 45,
                                                    right: 45,
                                                    top: 25,
                                                    bottom: 25
                                                }
                                            },
                                            plugins: {
                                                legend: {
                                                    display: true,
                                                    position: 'top',
                                                    labels: {
                                                        font: {
                                                            size: 12,
                                                            weight: '600'
                                                        },
                                                        color: '#475569',
                                                        usePointStyle: true,
                                                        padding: 15
                                                    }
                                                },
                                                tooltip: {
                                                    enabled: true,
                                                    backgroundColor: 'rgba(255, 255, 255, 0.98)',
                                                    titleColor: '#0f172a',
                                                    titleFont: { weight: 'bold', size: 13 },
                                                    bodyColor: '#334155',
                                                    bodyFont: { size: 13 },
                                                    borderColor: '#e2e8f0',
                                                    borderWidth: 1,
                                                    padding: 10,
                                                    boxPadding: 4,
                                                    usePointStyle: true,
                                                    callbacks: {
                                                        label: function(tooltipItem) {
                                                            return `${tooltipItem.dataset.label}: ${tooltipItem.raw}`;
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

                if (!selectedDataType || selectedDataType === '') {
                    const chart = charts[index];
                    const user = window.initialCompetencyUsers ? window.initialCompetencyUsers.find(u => u.id_user == userId) : null;
                    if (user && chart) {
                        chart.data.labels = formatRadarLabels([
                            'Technical Competency',
                            'Softskills Competency',
                            'Additional Competency'
                        ]);
                        chart.data.datasets[0].data = [
                            parseFloat(user.total_nilai_tc) || 0,
                            parseFloat(user.total_nilai_sk) || 0,
                            parseFloat(user.total_nilai_ad) || 0,
                        ];
                        chart.data.datasets[1].data = [
                            parseFloat(user.standar_nilai_tc) || 0,
                            parseFloat(user.standar_nilai_sk) || 0,
                            parseFloat(user.standar_nilai_ad) || 0,
                        ];
                        chart.options.scales.r.max = 12;
                        chart.options.scales.r.suggestedMax = 12;
                        chart.options.scales.r.ticks.stepSize = 2;
                        chart.update();
                    }
                    return;
                }

                const tahunFilter = document.getElementById('filter-tahun').value;

                $.ajax({
                    url: '{{ route('get-competency-filter') }}',
                    type: 'GET',
                    data: {
                        job_position: document.getElementById('options').value,
                        data_type: selectedDataType,
                        user_id: userId,
                        tahun: tahunFilter
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
                                dataPoints1.push(parseFloat(item.total_nilai_tc) || 0);
                                dataPoints2.push(parseFloat(item.tc_nilai) ||
                                    0); // Compare with another value, e.g., max value
                            });
                        } else if (selectedDataType === 'total_nilai_sk') {
                            filteredResponse.forEach(item => {
                                labels.push(item.keterangan_sk);
                                dataPoints1.push(parseFloat(item.total_nilai_sk) || 0);
                                dataPoints2.push(parseFloat(item.sk_nilai) ||
                                    0); // Compare with another value, e.g., max value
                            });
                        } else if (selectedDataType === 'total_nilai_ad') {
                            filteredResponse.forEach(item => {
                                labels.push(item.keterangan_ad);
                                dataPoints1.push(parseFloat(item.total_nilai_ad) || 0);
                                dataPoints2.push(parseFloat(item.ad_nilai) ||
                                    0); // Compare with another value, e.g., max value
                            });
                        }

                        // Update chart with new labels and data points for the specific user
                        chart.data.labels = formatRadarLabels(labels);
                        chart.data.datasets[0].data = dataPoints1;
                        chart.data.datasets[1].data = dataPoints2; // Add the second dataset for comparison
                        chart.options.scales.r.max = 4;
                        chart.options.scales.r.suggestedMax = 4;
                        chart.options.scales.r.ticks.stepSize = 1;
                        chart.update();
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                    }
                });
            }

            function btnDsDetail(userId) {
                const jobPosition = document.getElementById('options').value;
                const tahunFilter = document.getElementById('filter-tahun').value;
                // Redirect ke controller dsDetailCompetency dengan mengirimkan id_user dan id_job_position sebagai parameter
                window.location.href = `{{ route('dsDetailCompetency') }}?id_user=${userId}&id_job_position=${jobPosition}&tahun=${tahunFilter}`;
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
