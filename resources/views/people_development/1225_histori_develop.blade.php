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
                <div class="card mt-3">
                    <div class="card-header">
                        <div style="justify-content: space-between; align-items: center;">
                            <span><b>Histori Development</b></span>
                        </div>

                        <div
                            style="display: flex; justify-content: space-between; align-items: center; gap: 50px; margin-top: 2%;">
                            <div style="display: flex; align-items: center; gap: 40px;">
                                <!-- Dropdown for selecting role_id -->
                                <select id="role_id" style="width: 45%;">
                                    <option value=""> ----- Pilih Departemen ------ </option>
                                    <!-- Show only if user's role_id is 11 -->
                                    @if (auth()->user()->role_id == 11 || in_array(auth()->user()->role_id, [1, 14, 15]))
                                        <option value="11">Departemen Finn Acc Hrga IT Proc</option>
                                    @endif

                                    <!-- Show only if user's role_id is 2 -->
                                    @if (auth()->user()->role_id == 2 || in_array(auth()->user()->role_id, [1, 14, 15]))
                                        <option value="2">Departemen Sales Marketing</option>
                                    @endif

                                    <!-- Show only if user's role_id is 5 -->
                                    @if (auth()->user()->role_id == 5 || in_array(auth()->user()->role_id, [1, 14, 15]))
                                        <option value="5">Departemen Production</option>
                                    @endif

                                    <!-- Show only if user's role_id is 7 -->
                                    @if (auth()->user()->role_id == 7 || in_array(auth()->user()->role_id, [1, 14, 15]))
                                        <option value="7">Departemen Logistics</option>
                                    @endif
                                </select>

                                <!-- Dropdown for selecting year -->
                                <input list="yearList" id="year" name="year" placeholder="Pilih Tahun"
                                    style="width: 150px;">
                                <datalist id="yearList">
                                    @for ($year = 2000; $year <= now()->year; $year++)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endfor
                                </datalist>

                                <!-- Button to trigger filtering -->
                                <button onclick="filterData()">Filter Data</button>
                            </div>

                            <!-- Input field for search -->
                            <input type="text" id="searchInput" placeholder="Cari Data Disemua Kolom..."
                                style="padding: 5px; width: 250px;">
                        </div>

                    </div>

                    <div style="overflow-x: auto; white-space: nowrap;">
                        <table border="1" cellpadding="5" cellspacing="0" class="table table-bordered mt-3">
                            <thead>
                                <tr>
                                    <th>Nama Employee</th>
                                    <th>Nama Program</th>
                                    <th>Kategori Competency</th>
                                    <th>Competency</th>
                                    <th>Lembaga</th>
                                    <th>Periode Actual</th>
                                    <th>File Download</th>
                                </tr>
                            </thead>
                            <tbody id="peopleDevTabel">
                                <!-- Initially populated from server-side -->
                                @if ($dataTcPeopleDevelopment->isEmpty())
                                    <tr>
                                        <td colspan="6" style="text-align: center;">Tidak ada data tersedia</td>
                                    </tr>
                                @else
                                    @foreach ($dataTcPeopleDevelopment as $data)
                                        <tr>
                                            <td>{{ $data->user->name ?? '-' }}</td>
                                            <td>{{ $data->program_training_plan ?? '-' }}</td>
                                            <td>{{ $data->kategori_competency ?? '-' }}</td>
                                            <td>{{ $data->competency ?? '-' }}</td>
                                            <td>{{ $data->lembaga_plan ?? '-' }}</td>
                                            <td>{{ $data->due_date_plan ?? '-' }}</td>
                                            <td>
                                                <button onclick="downloadPdf({{ $data->id }})"
                                                    style="cursor: pointer; background-color: transparent; border: none; color: blue; text-decoration: underline;">
                                                    <i class="bi bi-download"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
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

        <!-- Include Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            function filterData() {
                var roleId = document.getElementById('role_id').value;
                var year = document.getElementById('year').value;

                var url = "{{ route('people_development.filter') }}?role_id=" + roleId + "&year=" + year;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        var tbody = document.getElementById('peopleDevTabel');
                        tbody.innerHTML = ''; // Clear the current table body

                        if (data.length === 0) {
                            // No data case
                            tbody.innerHTML =
                                `<tr><td colspan="6" style="text-align: center;">Tidak ada data tersedia</td></tr>`;
                        } else {
                            // Populate the table with the filtered data
                            data.forEach(function(item) {
                                var row = `
                        <tr>
                            <td>${item.user.name}</td>
                            <td>${item.program_training_plan}</td>
                            <td>${item.kategori_competency}</td>
                            <td>${item.competency}</td>
                            <td>${item.lembaga_plan}</td>
                            <td>${item.due_date_plan}</td>
                            <td>
                                <button onclick="downloadPdf(${item.id})"
                                    style="cursor: pointer; background-color: transparent; border: none; color: blue; text-decoration: underline;">
                                    <i class="bi bi-download"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                                tbody.innerHTML += row; // Append each row to the table body
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function downloadPdf(id) {
                var downloadPdfUrl = "{{ route('download.pdf', ['id' => ':id']) }}";
                var url = downloadPdfUrl.replace(':id', id);
                window.location.href = url; // Redirect to the download URL
            }

            // Tambahkan event listener untuk fitur pencarian
            document.getElementById('searchInput').addEventListener('input', function() {
                var searchTerm = this.value.toLowerCase();
                var tableRows = document.querySelectorAll('#peopleDevTabel tr');

                tableRows.forEach(function(row) {
                    var rowText = row.textContent.toLowerCase();
                    if (rowText.indexOf(searchTerm) > -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            document.getElementById("role_id").addEventListener("change", function() {
                if (this.value === "") {
                    window.location.href = "{{ route('historiDept') }}";
                }
            });
        </script>
    </main>
@endsection
