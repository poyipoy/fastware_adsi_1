@extends('layout')

@section('content')
    <style>
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

        /* Container for the radar charts */
        #radarChartContainer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
            /* Tambahkan gap untuk spasi antar card */
            margin-top: 20px;
        }

        /* Each card containing a radar chart */
        .chart-card {
            background-color: #fff;
            border: 1px solid #000000;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            width: calc(33.33% - 20px);
            /* Kalkulasi lebar agar 3 card muat dalam 1 baris */
            box-sizing: border-box;
        }

        /* Card title */
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        /* Adjustments for the canvas to ensure it fits within the card */
        .chart-card canvas {
            width: 100% !important;
            height: auto !important;
            display: block;
            margin: 0 auto;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 992px) {
            .chart-card {
                width: calc(50% - 20px);
                /* Setengah lebar jika ruang terbatas */
            }
        }

        @media (max-width: 768px) {
            .chart-card {
                width: 100%;
                /* Full width untuk layar kecil */
                max-width: 100%;
            }
        }

        /* tabel */
        .card {
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9;
        }

        .card-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #ccc;
            border-radius: 5px 5px 0 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f1f1f1;
            font-weight: bold;
            text-align: center;
            border: 1px solid #ccc;
        }

        th[rowspan="2"] {
            vertical-align: middle;
        }

        td,
        th {
            padding: 10px;
            border: 1px solid #ccc;
            white-space: nowrap;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        input[type="text"] {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {

            th,
            td {
                font-size: 12px;
                padding: 5px;
            }
        }

        th,
        td {
            text-align: center;
            vertical-align: middle;
        }

        th[rowspan="2"] {
            vertical-align: middle;
        }

        /* Optional: Center the text inside input fields as well */
        input[type="text"] {
            text-align: center;
        }
    </style>
    <main id="main" class="main">
        <section class="section dashboard">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="user-profile mb-0">
                        <i class="fas fa-user-circle profile-icon"></i>
                        <div class="user-details">
                            <span class="user-name">Nama Pengguna : </span>
                            <span class="user-job">Job Position : </span>
                            <input type="hidden" class="user-id-hidden" value="">
                        </div>
                    </div>
                    <div class="pe-4">
                        <label for="filter-tahun" class="fw-bold me-2">Tahun:</label>
                        <select id="filter-tahun" name="tahun" class="form-select form-select-sm d-inline-block w-auto" onchange="reloadAllData()">
                            @foreach (\App\Models\TrsPenilaianTc::getAvailableYears() as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="radarChartContainer">
                    {{-- <canvas id="radarEmployee"></canvas> --}}
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    Detail Competency
                </div>
                <div style="overflow-x: auto; white-space: nowrap;">
                    <table border="1" cellpadding="5" cellspacing="0">
                        <thead>
                            <tr>
                                <th rowspan="2">NPK — Nama Employee</th>
                                <th id="tcHeader" colspan="0">Technical Competency</th>
                                <th id="skHeader" colspan="0">Softskills</th>
                                <th id="adHeader" colspan="0">Additional</th>
                            </tr>
                            <tr id="headerKeterangan">
                                <!-- Keterangan headers will be dynamically inserted here -->
                            </tr>
                        </thead>
                        <tbody id="penilaianTableBody">
                            <!-- Rows will be dynamically inserted here by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 3%">
                    <button onclick="window.location.href='{{ route('dsCompetency') }}'"
                        class="btn btn-secondary float-right" style="width: 10%">
                        Back
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="form-group" style="margin-top: 2%">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            Keterangan Penilaian
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Deskripsi Technical Competency -->
                                <div class="col-md-4">
                                    <div class="card card-equal-height mb-3">
                                        <div class="card-header bg-primary text-white">
                                            {{ $dataTc1->judul_keterangan }}
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <td>1.</td>
                                                        <td>{{ $dataTc1->deskripsi_1 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>2.</td>
                                                        <td>{{ $dataTc1->deskripsi_2 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>3.</td>
                                                        <td>{{ $dataTc1->deskripsi_3 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>4.</td>
                                                        <td>{{ $dataTc1->deskripsi_4 }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Deskripsi Soft Skills -->
                                <div class="col-md-4">
                                    <div class="card card-equal-height mb-3">
                                        <div class="card-header bg-success text-white">
                                            {{ $dataTc2->judul_keterangan }}
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <td>1.</td>
                                                        <td>{{ $dataTc2->deskripsi_1 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>2.</td>
                                                        <td>{{ $dataTc2->deskripsi_2 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>3.</td>
                                                        <td>{{ $dataTc2->deskripsi_3 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>4.</td>
                                                        <td>{{ $dataTc2->deskripsi_4 }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Deskripsi Additional -->
                                <div class="col-md-4">
                                    <div class="card card-equal-height mb-3">
                                        <div class="card-header" style="background-color: orange; color: white;">
                                            {{ $dataTc3->judul_keterangan }}
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <td>1.</td>
                                                        <td>{{ $dataTc3->deskripsi_1 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>2.</td>
                                                        <td>{{ $dataTc3->deskripsi_2 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>3.</td>
                                                        <td>{{ $dataTc3->deskripsi_3 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>4.</td>
                                                        <td>{{ $dataTc3->deskripsi_4 }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== STRENGTH TABLE (Modul 1.2) ===== --}}
            <div class="card mt-3">
                <div class="card-header" style="background-color: #198754; color: white;">
                    <i class="bi bi-star-fill me-1"></i> Strength (Kompetensi Terpenuhi)
                </div>
                <div style="overflow-x: auto; white-space: nowrap;">
                    <table border="1" cellpadding="5" cellspacing="0" class="table table-bordered mt-0">
                        <thead class="table-success">
                            <tr>
                                <th>Tipe</th>
                                <th>Nama Kompetensi</th>
                                <th>Nilai Standar</th>
                                <th>Nilai Aktual</th>
                            </tr>
                        </thead>
                        <tbody id="strengthTableBody">
                            <tr><td colspan="4" class="text-center text-muted">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== AREA DEVELOPMENT TABLE (existing) ===== --}}
            <div class="card mt-3">
                <div class="card-header">
                    Area Development
                </div>
                <div style="overflow-x: auto; white-space: nowrap;">
                    <table border="1" cellpadding="5" cellspacing="0">
                        <thead>
                            <tr>
                                <th rowspan="2">NPK — Nama Employee</th>
                                <th id="tcHeaderViewOnly" colspan="0">Technical Competency</th>
                                <th id="skHeaderViewOnly" colspan="0">Softskills</th>
                                <th id="adHeaderViewOnly" colspan="0">Additional</th>
                            </tr>
                            <tr id="headerKeteranganViewOnly">
                                <!-- Keterangan headers will be dynamically inserted here -->
                            </tr>
                        </thead>
                        <tbody id="penilaianTableBodyViewOnly">
                            <!-- Rows will be dynamically inserted here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== WORKING EXPERIENCE TABLE (Modul 1.1) ===== --}}
            <div class="card mt-3">
                <div class="card-header" style="background-color: #6f42c1; color: white;">
                    <i class="bi bi-briefcase-fill me-1"></i> Working Experience (Riwayat Jabatan)
                </div>
                <div style="overflow-x: auto; white-space: nowrap;">
                    <table border="1" cellpadding="5" cellspacing="0" class="table table-bordered mt-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>Tahun Mulai</th>
                                <th>Tahun Selesai</th>
                                <th>Jabatan</th>
                                <th>Section</th>
                                <th>Departemen</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="workingExpTableBody">
                            <tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== HISTORI DEVELOPMENT TABLE (existing) ===== --}}
            <div class="card mt-3">
                <div class="card-header">
                    <div style=" justify-content: space-between; align-items: center;">
                        <span>Histori Development</span>

                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <input type="text" id="searchInput" placeholder="Cari Data Disemua Kolom..."
                            style="margin-left: 10px; padding: 5px; width: 250px;">
                    </div>
                </div>
                <div style="overflow-x: auto; white-space: nowrap;">
                    <table border="1" cellpadding="5" cellspacing="0" class="table table-bordered mt-3">
                        <thead>
                            <tr>
                                <th>NPK — Nama Employee</th>
                                <th>Nama Program</th>
                                <th>Kategori Competency</th>
                                <th>Competency</th>
                                <th>Lembaga</th>
                                <th>Periode Actual</th>
                                <th>File Download</th>
                            </tr>
                        </thead>
                        <tbody id="peopleDevTabel">
                            <!-- Data will be inserted here -->
                        </tbody>
                    </table>
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
        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            let charts = [];

            function fetchCompetencyData(userId) {
                const filterTahun = document.getElementById('filter-tahun');
                const tahunQuery = filterTahun ? filterTahun.value : '';
                fetch(`{{ route('get-detail-filter') }}?id_user=${userId}&tahun=${tahunQuery}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Data fetched:', data);
                        updateProfileDetails(data); // Pass full data object instead of just tc_data
                        updateCharts(data);
                    })
                    .catch(error => console.error('Error fetching data:', error));
            }

            function updateProfileDetails(data) {
                // Try to get user info from any available dataset
                const user = (data.tc_data && data.tc_data.length > 0) ? data.tc_data[0] : 
                             (data.sk_data && data.sk_data.length > 0) ? data.sk_data[0] : 
                             (data.ad_data && data.ad_data.length > 0) ? data.ad_data[0] : null;

                if (!user) {
                    console.error('User data is not available.');
                    return;
                }

                const userNameElement = document.querySelector('.user-name');
                if (userNameElement) {
                    const profileNpk = String(user.npk || '').trim();
                    userNameElement.textContent = `Nama Pengguna : ${profileNpk && profileNpk !== '0' ? profileNpk : '-'} — ${user.name}`;
                } else {
                    console.error('.user-name element not found.');
                }

                const userJobElement = document.querySelector('.user-job');
                if (userJobElement) {
                    userJobElement.textContent = `Job Position : ${user.job_position_name || user.id_job_position}`;
                } else {
                    console.error('.user-job element not found.');
                }

                const userIdHiddenElement = document.querySelector('.user-id-hidden');
                if (userIdHiddenElement) {
                    userIdHiddenElement.value = user.id_user;
                } else {
                    console.error('.user-id-hidden element not found.');
                }
            }

            function updateCharts(data) {
                const chartContainer = document.getElementById('radarChartContainer');
                chartContainer.innerHTML = ''; // Clear existing content

                // Create card for each chart
                createCard('Technical Competency', data.tc_data, 'keterangan_tc', 'total_nilai_tc', 'tc_nilai', 'canvasTc');
                createCard('Soft Skills', data.sk_data, 'keterangan_sk', 'total_nilai_sk', 'sk_nilai', 'canvasSk');
                createCard('Additional', data.ad_data, 'keterangan_ad', 'total_nilai_ad', 'ad_nilai', 'canvasAd');
            }

            function createCard(chartLabel, chartData, labelKey, dataKey, comparisonKey, canvasId) {
                const chartContainer = document.getElementById('radarChartContainer');

                // Create card element
                const card = document.createElement('div');
                card.className = 'chart-card'; // Add class for styling
                chartContainer.appendChild(card);

                // Create card title
                const cardTitle = document.createElement('div');
                cardTitle.className = 'card-title';
                cardTitle.textContent = chartLabel;
                card.appendChild(cardTitle);

                // Create canvas for chart
                const canvas = document.createElement('canvas');
                canvas.id = canvasId;
                card.appendChild(canvas);

                // Create chart within the card
                createChart(chartLabel, chartData, labelKey, dataKey, comparisonKey, canvasId);
            }

            function createChart(chartLabel, chartData, labelKey, dataKey, comparisonKey, canvasId) {
                const canvas = document.getElementById(canvasId);
                const ctx = canvas.getContext('2d');
                const dpr = window.devicePixelRatio || 1;
                canvas.width = 500 * dpr;
                canvas.height = 490 * dpr;
                canvas.style.width = '500px';
                canvas.style.height = '490px';
                ctx.scale(dpr, dpr);

                // Modifikasi labels agar dibungkus seimbang (max 16 karakter per baris)
                const labels = chartData.map(item => {
                    const label = item[labelKey] || chartLabel || '';
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

                const dataPoints = chartData.map(item => parseFloat(item[dataKey]) || 0);
                const comparisonDataPoints = chartData.map(item => parseFloat(item[comparisonKey]) || 0);
                const suggestedMax = 4;
                const suggestedMin = 0;
                const chart = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Nilai Standar',
                                data: comparisonDataPoints,
                                fill: false, // Tanpa fill agar tidak keruh
                                borderColor: '#f59e0b', // Amber/Gold putus-putus
                                borderWidth: 2,
                                borderDash: [5, 5],
                                pointBackgroundColor: '#f59e0b',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 1.5,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            },
                            {
                                label: 'Nilai Aktual',
                                data: dataPoints,
                                fill: true,
                                backgroundColor: 'rgba(37, 99, 235, 0.2)', // Royal blue transparan
                                borderColor: '#2563eb', // Blue-600
                                borderWidth: 3,
                                pointBackgroundColor: '#2563eb',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
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
                                max: 4,
                                ticks: {
                                    beginAtZero: true,
                                    stepSize: 1,
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
            }

            function reloadAllData() {
                const urlParams = new URLSearchParams(window.location.search);
                const userId = urlParams.get('id_user');
                if (userId) {
                    fetchCompetencyData(userId);
                    loadCompetencyData(userId);
                    loadCompetencyDataViewOnly(userId);
                    loadTabelDataView(userId);
                    loadStrengthData(userId);          // Modul 1.2
                    loadWorkingExperienceData(userId); // Modul 1.1
                }
            }

            // Ambil id_user dari parameter URL dan panggil fetchCompetencyData
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const userId = urlParams.get('id_user');
                const tahunParam = urlParams.get('tahun');
                
                if (tahunParam) {
                    const filterTahun = document.getElementById('filter-tahun');
                    if(filterTahun) filterTahun.value = tahunParam;
                }

                if (userId) {
                    reloadAllData();
                } else {
                    console.error('id_user parameter is missing in the URL.');
                }
            });

            function loadCompetencyData(userId) {
                const filterTahun = document.getElementById('filter-tahun');
                const tahunQuery = filterTahun ? filterTahun.value : '';
                // Lakukan AJAX request menggunakan jQuery
                $(document).ready(function() {
                    $.ajax({
                        url: `{{ route('get-detail-filter') }}?id_user=${userId}&tahun=${tahunQuery}`, // Gunakan id_user yang diambil dari URL
                        type: 'GET',
                        success: function(data) {
                            // Kosongkan baris yang ada di table body dan header keterangan
                            $('#penilaianTableBody').empty();
                            $('#headerKeterangan').empty();
                            $('#tcHeader').attr('colspan', 0);
                            $('#skHeader').attr('colspan', 0);
                            $('#adHeader').attr('colspan', 0);

                            if (data.penilaians.length > 0) {
                                var tcHeaders = [];
                                var skHeaders = [];
                                var adHeaders = [];

                                var displayedUsers = {}; // Object untuk melacak users dan data mereka

                                // Kumpulkan headers untuk tc, sk, dan ad
                                data.penilaians.forEach(function(row) {
                                    if (row.tc && !tcHeaders.some(item => item.keterangan === row.tc
                                            .keterangan_tc)) {
                                        tcHeaders.push({
                                            keterangan: row.tc.keterangan_tc,
                                            nilai: row.tc.nilai,
                                            id_poin_kategori: row.tc.id_poin_kategori
                                        });
                                    }
                                    if (row.sk && !skHeaders.some(item => item.keterangan === row.sk
                                            .keterangan_sk)) {
                                        skHeaders.push({
                                            keterangan: row.sk.keterangan_sk,
                                            nilai: row.sk.nilai,
                                            id_poin_kategori: row.sk.id_poin_kategori
                                        });
                                    }
                                    if (row.ad && !adHeaders.some(item => item.keterangan === row.ad
                                            .keterangan_ad)) {
                                        adHeaders.push({
                                            keterangan: row.ad.keterangan_ad,
                                            nilai: row.ad.nilai,
                                            id_poin_kategori: row.ad.id_poin_kategori
                                        });
                                    }
                                });

                                // Fungsi untuk menentukan warna latar belakang berdasarkan id_poin_kategori
                                function getBackgroundColorByIdPoinKategori(id_poin_kategori) {
                                    if (id_poin_kategori === 1) return 'blue';
                                    if (id_poin_kategori === 2) return 'green';
                                    if (id_poin_kategori === 3) return 'orange';
                                    return 'transparent'; // Default background color
                                }

                                // Tambahkan tc headers dengan nilai, background color, dan text color putih
                                tcHeaders.forEach(function(header) {
                                    var backgroundColor = getBackgroundColorByIdPoinKategori(header
                                        .id_poin_kategori);
                                    $('#headerKeterangan').append('<th style="background-color:' +
                                        backgroundColor + '; color: white;">' + header
                                        .keterangan +
                                        ' <br> Std (' + header.nilai + ')</th>');
                                    $('#tcHeader').attr('colspan', parseInt($('#tcHeader').attr(
                                        'colspan')) + 1);
                                });

                                // Tambahkan sk headers dengan nilai, background color, dan text color putih
                                skHeaders.forEach(function(header) {
                                    var backgroundColor = getBackgroundColorByIdPoinKategori(header
                                        .id_poin_kategori);
                                    $('#headerKeterangan').append('<th style="background-color:' +
                                        backgroundColor + '; color: white;">' + header
                                        .keterangan +
                                        ' <br> Std (' + header.nilai + ')</th>');
                                    $('#skHeader').attr('colspan', parseInt($('#skHeader').attr(
                                        'colspan')) + 1);
                                });

                                // Tambahkan ad headers dengan nilai, background color, dan text color putih
                                adHeaders.forEach(function(header) {
                                    var backgroundColor = getBackgroundColorByIdPoinKategori(header
                                        .id_poin_kategori);
                                    $('#headerKeterangan').append('<th style="background-color:' +
                                        backgroundColor + '; color: white;">' + header
                                        .keterangan +
                                        ' <br> Std (' + header.nilai + ')</th>');
                                    $('#adHeader').attr('colspan', parseInt($('#adHeader').attr(
                                        'colspan')) + 1);
                                });

                                // Kelompokkan data berdasarkan user
                                data.penilaians.forEach(function(row) {
                                    const userKey = String(row.id_user || row.user.id);
                                    if (!displayedUsers[userKey]) {
                                        const npk = String(row.user.npk || '').trim();
                                        displayedUsers[userKey] = {
                                            name: `${npk && npk !== '0' ? npk : '-'} — ${row.user.name}`,
                                            tc: {},
                                            sk: {},
                                            ad: {}
                                        };
                                    }
                                    if (row.tc) {
                                        displayedUsers[userKey].tc[row.tc.keterangan_tc] = {
                                            nilai: row.nilai_tc,
                                            keterangan: row.tc.keterangan_tc
                                        };
                                    }
                                    if (row.sk) {
                                        displayedUsers[userKey].sk[row.sk.keterangan_sk] = {
                                            nilai: row.nilai_sk,
                                            keterangan: row.sk.keterangan_sk
                                        };
                                    }
                                    if (row.ad) {
                                        displayedUsers[userKey].ad[row.ad.keterangan_ad] = {
                                            nilai: row.nilai_ad,
                                            keterangan: row.ad.keterangan_ad
                                        };
                                    }
                                });

                                // Buat baris untuk setiap user
                                for (var userKey in displayedUsers) {
                                    var userData = displayedUsers[userKey];
                                    var safeUserName = $('<div>').text(userData.name).html();
                                    var row = '<tr><td>' + safeUserName + '</td>';

                                    // Tambahkan fields tc
                                    tcHeaders.forEach(function(header) {
                                        var tcData = userData.tc[header
                                            .keterangan] || {
                                            nilai: '',
                                            keterangan: ''
                                        };
                                        row +=
                                            '<td><input type="text" name="nilai_tc[]" readonly value="' +
                                            tcData.nilai +
                                            '" style="width: 50px;" data-keterangan-tc="' + tcData
                                            .keterangan + '"></td>';
                                    });

                                    // Tambahkan fields sk
                                    skHeaders.forEach(function(header) {
                                        var skData = userData.sk[header
                                            .keterangan] || {
                                            nilai: '',
                                            keterangan: ''
                                        };
                                        row +=
                                            '<td><input type="text" name="nilai_sk[]" readonly value="' +
                                            skData.nilai +
                                            '" style="width: 50px;" data-keterangan-sk="' + skData
                                            .keterangan + '"></td>';
                                    });

                                    // Tambahkan fields ad
                                    adHeaders.forEach(function(header) {
                                        var adData = userData.ad[header
                                            .keterangan] || {
                                            nilai: '',
                                            keterangan: ''
                                        };
                                        row +=
                                            '<td><input type="text" name="nilai_ad[]" readonly value="' +
                                            adData.nilai +
                                            '" style="width: 50px;" data-keterangan-ad="' + adData
                                            .keterangan + '"></td>';
                                    });

                                    row += '</tr>';
                                    $('#penilaianTableBody').append(row);
                                }
                            } else {
                                // Jika tidak ada data ditemukan, tambahkan pesan ke baris tabel
                                var noDataRow =
                                    '<tr><td colspan="4">No data found for the given user.</td></tr>';
                                $('#penilaianTableBody').append(noDataRow);
                            }
                        },
                        error: function() {
                            alert('Failed to fetch data.');
                        }
                    });
                });
            }

            function loadCompetencyDataViewOnly(userId) {
                const filterTahun = document.getElementById('filter-tahun');
                const tahunQuery = filterTahun ? filterTahun.value : '';
                $(document).ready(function() {
                    $.ajax({
                        url: `{{ route('get-detail-filter') }}?id_user=${userId}&tahun=${tahunQuery}`,
                        type: 'GET',
                        success: function(data) {
                            $('#penilaianTableBodyViewOnly').empty();
                            $('#headerKeteranganViewOnly').empty();
                            $('#tcHeaderViewOnly').attr('colspan', 0);
                            $('#skHeaderViewOnly').attr('colspan', 0);
                            $('#adHeaderViewOnly').attr('colspan', 0);

                            if (data.penilaians.length > 0) {
                                var tcHeadersViewOnly = [];
                                var skHeadersViewOnly = [];
                                var adHeadersViewOnly = [];
                                var userRowData = {}; // Object to hold the row data for each user

                                data.penilaians.forEach(function(row) {
                                    // Safely check and use values if row.tc, row.sk, or row.ad is not null
                                    if (row.tc && row.nilai_tc < (row.tc.nilai || Infinity) && row
                                        .id_tc === (row.tc.id || null) && !tcHeadersViewOnly.some(
                                            item => item.keterangan === row.tc.keterangan_tc)) {
                                        tcHeadersViewOnly.push({
                                            keterangan: row.tc.keterangan_tc,
                                            nilai: row.tc.nilai,
                                            id: row.tc.id // Track the id_tc
                                        });
                                    }
                                    if (row.sk && row.nilai_sk < (row.sk.nilai || Infinity) && row
                                        .id_sk === (row.sk.id || null) && !skHeadersViewOnly.some(
                                            item => item.keterangan === row.sk.keterangan_sk)) {
                                        skHeadersViewOnly.push({
                                            keterangan: row.sk.keterangan_sk,
                                            nilai: row.sk.nilai,
                                            id: row.sk.id // Track the id_sk
                                        });
                                    }
                                    if (row.ad && row.nilai_ad < (row.ad.nilai || Infinity) && row
                                        .id_ad === (row.ad.id || null) && !adHeadersViewOnly.some(
                                            item => item.keterangan === row.ad.keterangan_ad)) {
                                        adHeadersViewOnly.push({
                                            keterangan: row.ad.keterangan_ad,
                                            nilai: row.ad.nilai,
                                            id: row.ad.id // Track the id_ad
                                        });
                                    }

                                    // Create or update the row data for the current user
                                    const userKey = String(row.id_user || row.user.id);
                                    if (!userRowData[userKey]) {
                                        const npk = String(row.user.npk || '').trim();
                                        userRowData[userKey] = {
                                            name: `${npk && npk !== '0' ? npk : '-'} — ${row.user.name}`,
                                            tcValues: {},
                                            skValues: {},
                                            adValues: {}
                                        };
                                    }

                                    // Store the values in the userRowData object, checking for null
                                    if (row.id_tc === (row.tc ? row.tc.id : null) && row.nilai_tc <
                                        (row.tc ? row.tc.nilai : Infinity)) {
                                        userRowData[userKey].tcValues[row.id_tc] = row
                                            .nilai_tc;
                                    }
                                    if (row.id_sk === (row.sk ? row.sk.id : null) && row.nilai_sk <
                                        (row.sk ? row.sk.nilai : Infinity)) {
                                        userRowData[userKey].skValues[row.id_sk] = row
                                            .nilai_sk;
                                    }
                                    if (row.id_ad === (row.ad ? row.ad.id : null) && row.nilai_ad <
                                        (row.ad ? row.ad.nilai : Infinity)) {
                                        userRowData[userKey].adValues[row.id_ad] = row
                                            .nilai_ad;
                                    }
                                });

                                // Only add headers if there are valid values to show
                                tcHeadersViewOnly.forEach(function(header) {
                                    $('#headerKeteranganViewOnly').append('<th>' + (header
                                        .keterangan || 'Unknown') + ' <br> Std(' + (header
                                        .nilai || 'Unknown') + ')</th>');
                                    $('#tcHeaderViewOnly').attr('colspan', parseInt($(
                                        '#tcHeaderViewOnly').attr('colspan')) + 1);
                                });

                                skHeadersViewOnly.forEach(function(header) {
                                    $('#headerKeteranganViewOnly').append('<th>' + (header
                                        .keterangan || 'Unknown') + ' <br> Std(' + (header
                                        .nilai || 'Unknown') + ')</th>');
                                    $('#skHeaderViewOnly').attr('colspan', parseInt($(
                                        '#skHeaderViewOnly').attr('colspan')) + 1);
                                });

                                adHeadersViewOnly.forEach(function(header) {
                                    $('#headerKeteranganViewOnly').append('<th>' + (header
                                        .keterangan || 'Unknown') + ' <br> Std(' + (header
                                        .nilai || 'Unknown') + ')</th>');
                                    $('#adHeaderViewOnly').attr('colspan', parseInt($(
                                        '#adHeaderViewOnly').attr('colspan')) + 1);
                                });

                                // Now, loop through each user and construct the rows
                                for (var userKey in userRowData) {
                                    var safeUserName = $('<div>').text(userRowData[userKey].name).html();
                                    var rowHtmlViewOnly = '<tr><td>' + safeUserName + '</td>';

                                    // Fill in TC values
                                    tcHeadersViewOnly.forEach(function(header) {
                                        var value = userRowData[userKey].tcValues[header.id] || '';
                                        rowHtmlViewOnly += '<td>' + value + '</td>';
                                    });

                                    // Fill in SK values
                                    skHeadersViewOnly.forEach(function(header) {
                                        var value = userRowData[userKey].skValues[header.id] || '';
                                        rowHtmlViewOnly += '<td>' + value + '</td>';
                                    });

                                    // Fill in AD values
                                    adHeadersViewOnly.forEach(function(header) {
                                        var value = userRowData[userKey].adValues[header.id] || '';
                                        rowHtmlViewOnly += '<td>' + value + '</td>';
                                    });

                                    rowHtmlViewOnly += '</tr>';
                                    $('#penilaianTableBodyViewOnly').append(rowHtmlViewOnly);
                                }

                                // --- Modul 2.3: Mentor Badges ---
                                var mentorBadges = data.mentor_badges || {};
                                var totalCols = 1 + tcHeadersViewOnly.length + skHeadersViewOnly.length + adHeadersViewOnly.length;

                                function renderMentorRow(headerType, headers) {
                                    headers.forEach(function(header) {
                                        var key = headerType + '_' + header.id;
                                        var mentors = mentorBadges[key] || [];
                                        if (mentors.length > 0) {
                                            var gap = totalCols - 1; // columns minus the mentor label column
                                            var badgeHtml = mentors.map(function(m) {
                                                return '<span class="badge bg-info text-dark me-1" title="Dapat menjadi mentor ' + header.keterangan + '">' +
                                                    '<i class="bi bi-person-check me-1"></i>' + m.name +
                                                    '</span>';
                                            }).join('');
                                            var mentorRow = '<tr class="table-light" style="font-size:0.85em;">' +
                                                '<td class="text-muted fst-italic ps-2">Mentor tersedia: ' + badgeHtml + '</td>' +
                                                '<td colspan="' + gap + '"></td>' +
                                                '</tr>';
                                            $('#penilaianTableBodyViewOnly').append(mentorRow);
                                        }
                                    });
                                }
                                renderMentorRow('tc', tcHeadersViewOnly);
                                renderMentorRow('sk', skHeadersViewOnly);
                                renderMentorRow('ad', adHeadersViewOnly);

                            } else {
                                var noDataRowViewOnly =
                                    '<tr><td colspan="4">No data found for the given user.</td></tr>';
                                $('#penilaianTableBodyViewOnly').append(noDataRowViewOnly);
                            }
                        },
                        error: function() {
                            alert('Failed to fetch data.');
                        }
                    });
                });
            }




            function loadTabelDataView(userId) {
                const filterTahun = document.getElementById('filter-tahun');
                const tahunQuery = filterTahun ? filterTahun.value : '';
                $(document).ready(function() {
                    // Fetch data from the server
                    $.ajax({
                        url: `{{ route('get-detail-filter') }}?id_user=${userId}&tahun=${tahunQuery}`, // Update with your route
                        method: 'GET',
                        success: function(response) {
                            var tableBody = $('#peopleDevTabel');
                            tableBody.empty(); // Clear existing table rows

                            // Pastikan mengakses dataTcPeopleDevelopment langsung dari response
                            response.dataTcPeopleDevelopment.forEach(function(item) {
                                const employeeNpk = String(item.user?.npk || '').trim();
                                const employeeLabel = `${employeeNpk && employeeNpk !== '0' ? employeeNpk : '-'} — ${item.user?.name || 'Unknown'}`;
                                const safeEmployeeLabel = $('<div>').text(employeeLabel).html();
                                var row = '<tr>' +
                                    '<td>' + safeEmployeeLabel + '</td>' +
                                    '<td>' + item.program_training_plan + '</td>' +
                                    '<td>' + item.kategori_competency + '</td>' +
                                    '<td>' + item.competency + '</td>' +
                                    '<td>' + item.lembaga_plan + '</td>' +
                                    '<td>' + item.due_date_plan + '</td>' +
                                    '<td>' +
                                    (item.file ?
                                        '<a href="#" class="btn btn-primary" onclick="downloadPdf(\'' +
                                        item.id +
                                        '\')"><i class="bi bi-filetype-pdf fs-3"></i></a>' :
                                        'File Belum Tersedia') +
                                    '</td>' +
                                    '<input type="hidden" class="file-id" value="' + item.id +
                                    '">' +
                                    '</tr>';
                                tableBody.append(row);
                            });

                            // Tambahkan event listener untuk fitur pencarian
                            $('#searchInput').on('input', function() {
                                var searchTerm = $(this).val().toLowerCase();
                                $('#peopleDevTabel tr').each(function() {
                                    var rowText = $(this).text().toLowerCase();
                                    $(this).toggle(rowText.indexOf(searchTerm) > -1);
                                });
                            });
                        },
                        error: function() {
                            console.error('Error fetching data');
                        }
                    });
                });
            }

            function downloadPdf(id) {
                var downloadPdfUrl = "{{ route('download.pdf', ['id' => ':id']) }}";
                var url = downloadPdfUrl.replace(':id', id);
                window.location.href = url; // Redirect to the download URL
            }

            // ===== Modul 1.2: Strength Table =====
            function loadStrengthData(userId) {
                const filterTahun = document.getElementById('filter-tahun');
                const tahunQuery = filterTahun ? filterTahun.value : '';
                $.ajax({
                    url: `{{ route('get-detail-filter') }}?id_user=${userId}&tahun=${tahunQuery}`,
                    type: 'GET',
                    success: function(data) {
                        var tbody = $('#strengthTableBody');
                        tbody.empty();
                        var strengthData = data.strength_data || [];

                        if (strengthData.length === 0) {
                            tbody.append('<tr><td colspan="4" class="text-center text-muted">Belum ada kompetensi yang terpenuhi.</td></tr>');
                            return;
                        }

                        var typeLabels = { tc: 'Technical Competency', sk: 'Soft Skills', ad: 'Additional' };
                        strengthData.forEach(function(item) {
                            var typeLabel = typeLabels[item.type] || item.type;
                            var gap = item.actual - item.standard;
                            var html = '<tr class="table-success">' +
                                '<td><span class="badge bg-success">' + typeLabel + '</span></td>' +
                                '<td class="text-start">' + item.name + '</td>' +
                                '<td>' + item.standard + '</td>' +
                                '<td><strong>' + item.actual + '</strong>' +
                                ' <span class="text-success">(+' + gap.toFixed(1) + ')</span></td>' +
                                '</tr>';
                            tbody.append(html);
                        });
                    },
                    error: function() {
                        $('#strengthTableBody').html('<tr><td colspan="4" class="text-danger text-center">Gagal memuat data.</td></tr>');
                    }
                });
            }

            // ===== Modul 1.1: Working Experience Table =====
            function loadWorkingExperienceData(userId) {
                $.ajax({
                    url: `{{ route('get-detail-filter') }}?id_user=${userId}`,
                    type: 'GET',
                    success: function(data) {
                        var tbody = $('#workingExpTableBody');
                        tbody.empty();
                        var weData = data.working_experience_data || [];

                        if (weData.length === 0) {
                            tbody.append('<tr><td colspan="6" class="text-center text-muted">Belum ada riwayat jabatan.</td></tr>');
                            return;
                        }

                        weData.forEach(function(item) {
                            var yearEndLabel = item.year_end_label || item.year_end || 'Present';
                            var isPresent = !item.year_end;
                            var html = '<tr' + (isPresent ? ' class="table-primary fw-semibold"' : '') + '>' +
                                '<td>' + item.year_start + '</td>' +
                                '<td>' + (isPresent ? '<span class="badge bg-primary">Present</span>' : yearEndLabel) + '</td>' +
                                '<td class="text-start">' + (item.job_position || '-') + '</td>' +
                                '<td>' + (item.section || '-') + '</td>' +
                                '<td>' + (item.departemen || '-') + '</td>' +
                                '<td class="text-start">' + (item.keterangan || '-') + '</td>' +
                                '</tr>';
                            tbody.append(html);
                        });
                    },
                    error: function() {
                        $('#workingExpTableBody').html('<tr><td colspan="6" class="text-danger text-center">Gagal memuat data.</td></tr>');
                    }
                });
            }
        </script>
    </main>
@endsection
