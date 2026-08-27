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
                <form id="penilaianForm" action="{{ route('updatePenilaian', $penilaian->id_job_position) }}"
                    method="POST">
                    @csrf
                    @method('PUT')
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
                    <!-- Link menuju route 'penilaian.index' -->
                    <div class="d-flex justify-content-end mt-4 mb-3">
                        <a href="{{ route('penilaian.index') }}" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i> Submit Preview
                        </a>
                    </div>
                </form>
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

                                // Add tc fields
                                tcHeaders.forEach(function(header) {
                                    var tcData = displayedUsers[userName].tc[header.keterangan] || {
                                        nilai: '',
                                        keterangan: ''
                                    };
                                    row += '<td><input type="text" name="nilai_tc[]" value="' +
                                        tcData.nilai +
                                        '" style="width: 50px;" data-keterangan-tc="' + tcData
                                        .keterangan + '"></td>';
                                });

                                // Add sk fields
                                skHeaders.forEach(function(header) {
                                    var skData = displayedUsers[userName].sk[header.keterangan] || {
                                        nilai: '',
                                        keterangan: ''
                                    };
                                    row += '<td><input type="text" name="nilai_sk[]" value="' +
                                        skData.nilai +
                                        '" style="width: 50px;" data-keterangan-sk="' + skData
                                        .keterangan + '"></td>';
                                });

                                // Add ad fields
                                adHeaders.forEach(function(header) {
                                    var adData = displayedUsers[userName].ad[header.keterangan] || {
                                        nilai: '',
                                        keterangan: ''
                                    };
                                    row += '<td><input type="text" name="nilai_ad[]" value="' +
                                        adData.nilai +
                                        '" style="width: 50px;" data-keterangan-ad="' + adData
                                        .keterangan + '"></td>';
                                });

                                row += '</tr>';
                                $('#penilaianTableBody').append(row);
                            }
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
        </script>
    </main><!-- End #main -->
@endsection
