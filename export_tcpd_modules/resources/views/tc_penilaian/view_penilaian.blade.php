@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            .card {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                margin: 20px auto;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                background-color: #fff;
                width: 100%;
                margin-bottom: 20px;
            }

            .poin-item {
                margin-bottom: 10px;
            }

            .input-field {
                width: 100%;
                padding: 5px;
                margin: 5px 0;
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: center;
            }

            th {
                background-color: #f2f2f2;
            }

            button {
                padding: 10px 15px;
                margin: 5px;
                border: none;
                border-radius: 4px;
                background-color: #007bff;
                color: #fff;
                cursor: pointer;
            }

            button[type="button"] {
                background-color: #6c757d;
            }

            /* CSS untuk menggeser input text dalam tabel */
            input[type="text"] {
                padding-left: 20px;
                /* Menggeser teks input ke kanan dengan padding */
                box-sizing: border-box;
                /* Memastikan padding dimasukkan dalam lebar elemen */
            }

            .card-equal-height {
                display: flex;
                flex-direction: column;
            }

            .card-equal-height .card-body {
                flex-grow: 1;
            }

            .card-body .table {
                height: 50%;
                font-size: 14px;
            }

            .row .col-md-4 {
                display: flex;
                flex-direction: column;
            }

            .card-body {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .accordion-button:not(.collapsed) {
                background-color: #f8f9fa;
                color: #333;
                box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
            }
        </style>

        <div class="pagetitle">
            <h1>Halaman Penilaian Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu View Form Penilaian Competency</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="card">
                <!-- Input hidden untuk mengirim data penting -->
                <input type="hidden" name="id_tc" value="{{ $penilaian->id_tc }}">
                <input type="hidden" name="id_sk" value="{{ $penilaian->id_sk }}">
                <input type="hidden" name="id_ad" value="{{ $penilaian->id_ad }}">
                <input type="hidden" name="id_poin_kategori" value="{{ $penilaian->id_poin_kategori }}">

                <!-- Bagian Nama PIC hingga Posisi -->
                <div class="row g-3 mb-4 mt-2">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="nama_pic" id="nama_pic" class="form-control bg-light" value="{{ $penilaian->dept_head_name }}" readonly>
                            <label for="nama_pic">Nama PIC</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                            <div class="form-floating">
                                <input type="hidden" id="jobPositionSelectId" value="{{ $penilaian->id_job_position ?? '' }}">
                                <input type="text" name="posisi" id="jobPositionSelect" class="form-control bg-light" value="{{ $penilaian->jobPosition->position_name ?? 'N/A' }}" readonly>
                                <label for="jobPositionSelect">Posisi</label>
                            </div>
                    </div>
                </div>
                <div style="overflow-x: auto; white-space: nowrap;">
                    <table border="1" cellpadding="5" cellspacing="0">
                        <thead>
                            <tr>
                                <th rowspan="2">Nama Employee</th>
                                <th id="tcHeader" colspan="0">Technical Competency</th>
                                <th id="skHeader" colspan="0">Softskills</th>
                                <th id="adHeader" colspan="0">Additional</th>
                                <th rowspan="2" class="text-center bg-info text-white">Rata-rata Score</th>
                            </tr>
                            <tr id="headerKeterangan">
                                <!-- Keterangan headers will be dynamically inserted here -->
                            </tr>
                        </thead>
                        <tbody id="penilaianTableBody">
                            <!-- Rows will be dynamically inserted here by JavaScript -->
                        </tbody>
                    </table>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                        <div class="d-flex gap-3">
                            <button type="button" onclick="history.back()" class="btn btn-light border px-4 py-2 rounded-pill shadow-sm">
                                <i class="fas fa-arrow-left me-2"></i> Kembali
                            </button>
                        </div>
                        <button id="openModalButton" type="button" class="btn btn-info text-white px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#jobPositionModal">
                            <i class="fas fa-eye me-2"></i> Lihat Summary
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Summary -->
            <div class="modal fade" id="jobPositionModal" tabindex="-1" aria-labelledby="jobPositionModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl"> <!-- Ubah ukuran modal menjadi extra large -->
                    <div class="modal-content">
                        <div class="modal-header" style="background-color: #5a8dcf; color: white;">
                            <h5 class="modal-title" id="jobPositionModalLabel">Detail Summary</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                                style="color: white;"></button>
                        </div>
                        <div class="modal-body">
                            <div id="details" style="max-height: 70vh; overflow-y: auto; padding: 10px;">
                                <!-- Data akan dimasukkan di sini melalui JavaScript -->
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: 2px solid #4CAF50;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion mb-4 mt-4" id="accordionKeteranganPenilaian">
                <div class="accordion-item border-0 shadow-sm rounded-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingKeterangan">
                        <button class="accordion-button collapsed fw-semibold text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKeterangan" aria-expanded="false" aria-controls="collapseKeterangan">
                            <i class="fas fa-info-circle me-2"></i> Tampilkan Keterangan Penilaian
                        </button>
                    </h2>
                    <div id="collapseKeterangan" class="accordion-collapse collapse" aria-labelledby="headingKeterangan" data-bs-parent="#accordionKeteranganPenilaian">
                        <div class="accordion-body bg-light">
                            <div class="row g-3">
                                <!-- Deskripsi Technical Competency -->
                                <div class="col-md-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header bg-primary text-white fw-semibold">
                                            {{ $dataTc1->judul_keterangan }}
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tbody>
                                                    <tr><td class="ps-3 py-2" width="20">1.</td><td class="py-2">{{ $dataTc1->deskripsi_1 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">2.</td><td class="py-2">{{ $dataTc1->deskripsi_2 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">3.</td><td class="py-2">{{ $dataTc1->deskripsi_3 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">4.</td><td class="py-2">{{ $dataTc1->deskripsi_4 }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <!-- Deskripsi Soft Skills -->
                                <div class="col-md-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header bg-success text-white fw-semibold">
                                            {{ $dataTc2->judul_keterangan }}
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tbody>
                                                    <tr><td class="ps-3 py-2" width="20">1.</td><td class="py-2">{{ $dataTc2->deskripsi_1 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">2.</td><td class="py-2">{{ $dataTc2->deskripsi_2 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">3.</td><td class="py-2">{{ $dataTc2->deskripsi_3 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">4.</td><td class="py-2">{{ $dataTc2->deskripsi_4 }}</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <!-- Deskripsi Additional -->
                                <div class="col-md-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header text-white fw-semibold" style="background-color: orange;">
                                            {{ $dataTc3->judul_keterangan }}
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tbody>
                                                    <tr><td class="ps-3 py-2" width="20">1.</td><td class="py-2">{{ $dataTc3->deskripsi_1 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">2.</td><td class="py-2">{{ $dataTc3->deskripsi_2 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">3.</td><td class="py-2">{{ $dataTc3->deskripsi_3 }}</td></tr>
                                                    <tr><td class="ps-3 py-2">4.</td><td class="py-2">{{ $dataTc3->deskripsi_4 }}</td></tr>
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

            <div class="row">
                <div class="form-group" style="margin-top: 2%">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            History Updated
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Keterangan Detail</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Modified By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($detailPenilaian as $index => $detail)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $detail->keterangan_detail }}</td>
                                            <td>{{ $detail->created_at->format('d-m-Y H:i:s') }}</td>
                                            <td>{{ $detail->modified_at }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No history found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
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
        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            //get data
            $(document).ready(function() {
                var jobPosition = '{{ $penilaian->id_job_position }}';
                $.ajax({
                    url: '{{ route('getDataTrs') }}', // Adjust the route name as per your routes definition
                    type: 'GET',
                    data: {
                        id_job_position: jobPosition
                    },
                    success: function(data) {
                        // Clear the existing rows in the table body and header keterangan
                        $('#penilaianTableBody').empty();
                        $('#headerKeterangan').empty();
                        $('#tcHeader').attr('colspan', 0);
                        $('#skHeader').attr('colspan', 0);
                        $('#adHeader').attr('colspan', 0);

                        if (data.length > 0) {
                            var tcHeaders = [];
                            var skHeaders = [];
                            var adHeaders = [];

                            var displayedUsers = {}; // Object to track users and their data

                            // Collect headers for tc, sk, and ad
                            data.forEach(function(row) {
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

                            // Function to determine the background color based on id_poin_kategori
                            function getBackgroundColorByIdPoinKategori(id_poin_kategori) {
                                if (id_poin_kategori === 1) return 'blue';
                                if (id_poin_kategori === 2) return 'green';
                                if (id_poin_kategori === 3) return 'orange';
                                return 'transparent'; // Default background color
                            }

                            // Append tc headers with nilai, background color, and white text color
                            tcHeaders.forEach(function(header) {
                                var backgroundColor = getBackgroundColorByIdPoinKategori(header
                                    .id_poin_kategori);
                                $('#headerKeterangan').append('<th style="background-color:' +
                                    backgroundColor + '; color: white;">' + header.keterangan +
                                    ' - (' + header.nilai + ')</th>');
                                $('#tcHeader').attr('colspan', parseInt($('#tcHeader').attr(
                                    'colspan')) + 1);
                            });

                            // Append sk headers with nilai, background color, and white text color
                            skHeaders.forEach(function(header) {
                                var backgroundColor = getBackgroundColorByIdPoinKategori(header
                                    .id_poin_kategori);
                                $('#headerKeterangan').append('<th style="background-color:' +
                                    backgroundColor + '; color: white;">' + header.keterangan +
                                    ' - (' + header.nilai + ')</th>');
                                $('#skHeader').attr('colspan', parseInt($('#skHeader').attr(
                                    'colspan')) + 1);
                            });

                            // Append ad headers with nilai, background color, and white text color
                            adHeaders.forEach(function(header) {
                                var backgroundColor = getBackgroundColorByIdPoinKategori(header
                                    .id_poin_kategori);
                                $('#headerKeterangan').append('<th style="background-color:' +
                                    backgroundColor + '; color: white;">' + header.keterangan +
                                    ' - (' + header.nilai + ')</th>');
                                $('#adHeader').attr('colspan', parseInt($('#adHeader').attr(
                                    'colspan')) + 1);
                            });

                            
                            if (tcHeaders.length === 0) $('#tcHeader').hide();
                            else $('#tcHeader').show();
                            
                            if (skHeaders.length === 0) $('#skHeader').hide();
                            else $('#skHeader').show();
                            
                            if (adHeaders.length === 0) $('#adHeader').hide();
                            else $('#adHeader').show();

                            // Group the data by user
                            data.forEach(function(row) {
                                if (!displayedUsers[row.user.name]) {
                                    displayedUsers[row.user.name] = {
                                        tc: {},
                                        sk: {},
                                        ad: {}
                                    };
                                }
                                if (row.tc) {
                                    displayedUsers[row.user.name].tc[row.tc.keterangan_tc] = {
                                        nilai: row.nilai_tc,
                                        keterangan: row.tc.keterangan_tc
                                    };
                                }
                                if (row.sk) {
                                    displayedUsers[row.user.name].sk[row.sk.keterangan_sk] = {
                                        nilai: row.nilai_sk,
                                        keterangan: row.sk.keterangan_sk
                                    };
                                }
                                if (row.ad) {
                                    displayedUsers[row.user.name].ad[row.ad.keterangan_ad] = {
                                        nilai: row.nilai_ad,
                                        keterangan: row.ad.keterangan_ad
                                    };
                                }
                            });

                            // Create rows for each user
                            for (var userName in displayedUsers) {
                                var row = '<tr><td>' + userName + '</td>';

                                tcHeaders.forEach(function(header) {
                                    var tcData = displayedUsers[userName].tc[header.keterangan] || {
                                        nilai: '',
                                        keterangan: ''
                                    };
                                    // Jika nilai di bawah standar, berikan warna merah untuk teks dan background
                                    var inputStyle = tcData.nilai < header.nilai ?
                                        'color: red; background-color: #ffcccc;' : '';
                                    row +=
                                        '<td><input type="text" name="nilai_tc[]" readonly value="' +
                                        tcData.nilai +
                                        '" style="width: 50px;' + inputStyle +
                                        '" data-keterangan-tc="' + tcData.keterangan +
                                        '" data-name="' + userName +
                                        '"></td>';
                                });

                                skHeaders.forEach(function(header) {
                                    var skData = displayedUsers[userName].sk[header.keterangan] || {
                                        nilai: '',
                                        keterangan: ''
                                    };
                                    // Jika nilai di bawah standar, berikan warna merah untuk teks dan background
                                    var inputStyle = skData.nilai < header.nilai ?
                                        'color: red; background-color: #ffcccc;' : '';
                                    row +=
                                        '<td><input type="text" name="nilai_sk[]" readonly value="' +
                                        skData.nilai +
                                        '" style="width: 50px;' + inputStyle +
                                        '" data-keterangan-sk="' + skData.keterangan +
                                        '" data-name="' + userName +
                                        '"></td>';
                                });

                                adHeaders.forEach(function(header) {
                                    var adData = displayedUsers[userName].ad[header.keterangan] || {
                                        nilai: '',
                                        keterangan: ''
                                    };
                                    // Jika nilai di bawah standar, berikan warna merah untuk teks dan background
                                    var inputStyle = adData.nilai < header.nilai ?
                                        'color: red; background-color: #ffcccc;' : '';
                                    row +=
                                        '<td><input type="text" name="nilai_ad[]" readonly value="' +
                                        adData.nilai +
                                        '" style="width: 50px;' + inputStyle +
                                        '" data-keterangan-ad="' + adData.keterangan +
                                        '" data-name="' + userName +
                                        '"></td>';
                                });

                                row += '<td class="text-center align-middle fw-bold avg-score">0.00</td></tr>';
                                $('#penilaianTableBody').append(row);
                            }

                            // Calculate averages for view-only fields
                            calculateAverages();

                        } else {
                            // If no data is found, add a message row
                            var noDataRow =
                                '<tr><td colspan="4">No data found for the given job position.</td></tr>';
                            $('#penilaianTableBody').append(noDataRow);
                        }
                    },
                    error: function() {
                        alert('Failed to fetch data.');
                    }
                });
            });

            function calculateAverages() {
                $('#penilaianTableBody tr').each(function() {
                    let total = 0;
                    let count = 0;
                    $(this).find('input[type="text"]').each(function() {
                        let val = parseFloat($(this).val());
                        if (!isNaN(val)) {
                            total += val;
                            count++;
                        }
                    });
                    let avg = count > 0 ? (total / count).toFixed(2) : '0.00';
                    $(this).find('.avg-score').text(avg);
                });
            }

            // Script Modal
            document.getElementById('openModalButton').addEventListener('click', function() {
                const jobPosition = document.getElementById('jobPositionSelect').value;

                if (!jobPosition) {
                    alert('Pilih posisi terlebih dahulu!');
                    return;
                }

                console.log('Selected Job Position:', jobPosition);

                $.ajax({
                    url: '{{ route('job.positions.details2', ':job_position') }}'.replace(':job_position',
                        jobPosition), // Gunakan route() helper
                    method: 'GET', // Gunakan metode GET
                    success: function(response) {
                        let detailsHtml = '';

                        // Tabel Technical Competency
                        if (response.tcs && response.tcs.length > 0) {
                            detailsHtml += `
                <h3>Technical Competency</h3>
                <table class="styled-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 15%; text-align: left; padding: 8px; vertical-align: middle;">Keterangan Competency</th>
                            <th rowspan="2" style="width: 20%; text-align: left; padding: 8px; vertical-align: middle;">Deskripsi</th>
                            <th colspan="4" style="text-align: center; padding: 8px;">Judul Keterangan Kategori</th>
                            <th rowspan="2" style="width: 5%; text-align: left; padding: 8px; vertical-align: middle;">Nilai Standar</th>
                        </tr>
                        <tr>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 1</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 2</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 3</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 4</th>
                        </tr>
                    </thead>
                    <tbody>`;
                            response.tcs.forEach(tc => {
                                const background = tc.poin_kategori ?
                                    tc.poin_kategori.id === 1 ?
                                    'background-color: blue; color: white;' :
                                    tc.poin_kategori.id === 2 ?
                                    'background-color: green; color: white;' :
                                    tc.poin_kategori.id === 3 ?
                                    'background-color: orange; color: white;' :
                                    '' :
                                    '';

                                detailsHtml += `
                    <tr>
                        <td>
                            ${tc.keterangan_tc ?? '-'} <br>
                            <span style="font-size: 0.85em; ${background}; padding: 2px 4px; border-radius: 4px;">(${tc.poin_kategori?.judul_keterangan ?? '-'})</span>
                        </td>
                        <td>${tc.deskripsi_tc ?? '-'}</td>
                        <td>${tc.deskripsi_level_1 || tc.poin_kategori?.deskripsi_1 || '-'}</td>
                        <td>${tc.deskripsi_level_2 || tc.poin_kategori?.deskripsi_2 || '-'}</td>
                        <td>${tc.deskripsi_level_3 || tc.poin_kategori?.deskripsi_3 || '-'}</td>
                        <td>${tc.deskripsi_level_4 || tc.poin_kategori?.deskripsi_4 || '-'}</td>
                        <td>${tc.nilai ?? '-'}</td>
                    </tr>`;
                            });
                            detailsHtml += `
                    </tbody>
                </table>`;
                        }

                        // Tabel Soft Skills
                        if (response.softSkills && response.softSkills.length > 0) {
                            detailsHtml += `
                <h3>Soft Skills</h3>
                <table class="styled-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 15%; text-align: left; padding: 8px; vertical-align: middle;">Keterangan Soft Skills</th>
                            <th rowspan="2" style="width: 20%; text-align: left; padding: 8px; vertical-align: middle;">Deskripsi</th>
                            <th colspan="4" style="text-align: center; padding: 8px;">Judul Keterangan Kategori</th>
                            <th rowspan="2" style="width: 5%; text-align: left; padding: 8px; vertical-align: middle;">Nilai Standar</th>
                        </tr>
                        <tr>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 1</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 2</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 3</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 4</th>
                        </tr>
                    </thead>
                    <tbody>`;
                            response.softSkills.forEach(skill => {
                                const background = skill.poin_kategori ?
                                    skill.poin_kategori.id === 1 ?
                                    'background-color: blue; color: white;' :
                                    skill.poin_kategori.id === 2 ?
                                    'background-color: green; color: white;' :
                                    skill.poin_kategori.id === 3 ?
                                    'background-color: orange; color: white;' :
                                    '' :
                                    '';

                                detailsHtml += `
                    <tr>
                        <td>
                            ${skill.keterangan_sk ?? '-'} <br>
                            <span style="font-size: 0.85em; ${background}; padding: 2px 4px; border-radius: 4px;">(${skill.poin_kategori?.judul_keterangan ?? '-'})</span>
                        </td>
                        <td>${skill.deskripsi_sk ?? '-'}</td>
                        <td>${skill.deskripsi_level_1 || skill.poin_kategori?.deskripsi_1 || '-'}</td>
                        <td>${skill.deskripsi_level_2 || skill.poin_kategori?.deskripsi_2 || '-'}</td>
                        <td>${skill.deskripsi_level_3 || skill.poin_kategori?.deskripsi_3 || '-'}</td>
                        <td>${skill.deskripsi_level_4 || skill.poin_kategori?.deskripsi_4 || '-'}</td>
                        <td>${skill.nilai ?? '-'}</td>
                    </tr>`;
                            });
                            detailsHtml += `
                    </tbody>
                </table>`;
                        }

                        // Tabel Additionals
                        if (response.additionals && response.additionals.length > 0) {
                            detailsHtml += `
                <h3>Additionals</h3>
                <table class="styled-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 15%; text-align: left; padding: 8px; vertical-align: middle;">Keterangan Additional</th>
                            <th rowspan="2" style="width: 20%; text-align: left; padding: 8px; vertical-align: middle;">Deskripsi</th>
                            <th colspan="4" style="text-align: center; padding: 8px;">Judul Keterangan Kategori</th>
                            <th rowspan="2" style="width: 5%; text-align: left; padding: 8px; vertical-align: middle;">Nilai Standar</th>
                        </tr>
                        <tr>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 1</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 2</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 3</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Nilai 4</th>
                        </tr>
                    </thead>
                    <tbody>`;
                            response.additionals.forEach(additional => {
                                const background = additional.poin_kategori ?
                                    additional.poin_kategori.id === 1 ?
                                    'background-color: blue; color: white;' :
                                    additional.poin_kategori.id === 2 ?
                                    'background-color: green; color: white;' :
                                    additional.poin_kategori.id === 3 ?
                                    'background-color: orange; color: white;' :
                                    '' :
                                    '';

                                detailsHtml += `
                    <tr>
                        <td>
                            ${additional.keterangan_ad ?? '-'} <br>
                            <span style="font-size: 0.85em; ${background}; padding: 2px 4px; border-radius: 4px;">(${additional.poin_kategori?.judul_keterangan ?? '-'})</span>
                        </td>
                        <td>${additional.deskripsi_ad ?? '-'}</td>
                        <td>${additional.deskripsi_level_1 || additional.poin_kategori?.deskripsi_1 || '-'}</td>
                        <td>${additional.deskripsi_level_2 || additional.poin_kategori?.deskripsi_2 || '-'}</td>
                        <td>${additional.deskripsi_level_3 || additional.poin_kategori?.deskripsi_3 || '-'}</td>
                        <td>${additional.deskripsi_level_4 || additional.poin_kategori?.deskripsi_4 || '-'}</td>
                        <td>${additional.nilai ?? '-'}</td>
                    </tr>`;
                            });
                            detailsHtml += `
                    </tbody>
                </table>`;
                        }

                        // Masukkan data ke modal
                        document.getElementById('details').innerHTML = detailsHtml;
                    },
                    error: function() {
                        document.getElementById('details').innerHTML =
                            '<p>Gagal mengambil data. Coba lagi.</p>';
                    }
                });
            });
        </script>
    </main><!-- End #main -->
@endsection
