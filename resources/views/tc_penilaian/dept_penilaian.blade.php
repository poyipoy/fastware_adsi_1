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

            .top-right-alert {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: #fc0909;
                color: #ffffff;
                padding: 10px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease, transform 0.3s ease;
                transform: translateY(-20px);
            }

            .top-right-alert.show {
                opacity: 1;
                transform: translateY(0);
            }
        </style>
        <div class="pagetitle">
            <h1>Halaman Penilaian Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Penilaian Competency</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div id="topRightAlert" class="top-right-alert">
                ⚠️ Catatan Tidak Boleh Kosong!
            </div>
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
                    <table border="1" cellpadding="5" cellspacing="0">
                        <tr>
                            <td colspan="2">Nama PIC :</td>
                            <td colspan="2">
                                <input type="text" name="nama_pic" value="{{ $penilaian->modified_updated }}" style="width: 30%" readonly>
                            </td>
                        </tr>
                        <!-- Tampilkan data dari job position -->
                        <tr>
                            <td colspan="2">Posisi :</td>
                            <td colspan="2">
                                <input id="jobPositionSelect" type="text" name="posisi"
                                    value="{{ $penilaian->id_job_position ?? 'N/A' }}" style="width: 30%" readonly>
                            </td>
                        </tr>
                    </table>

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
                    <button id="saveFormButton" type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update
                    </button>

                    <button type="button" onclick="window.location='{{ route('penilaian.index2') }}'"
                        class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                </form>
            </div>

            <div class="text-end">
                <button id="openModalButton" class="btn btn-primary" style="width: 10%;" data-bs-toggle="modal"
                    data-bs-target="#jobPositionModal">
                    <i class="fas fa-eye"></i> Lihat Summary
                </button>
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
                                        <th>Nama Karyawan</th>
                                        {{-- <th style="width: 25%">Keterangan Sebelumnya</th> --}}
                                        <th style="width: 25%">Keterangan Detail</th>
                                        <th>Catatan</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Modified By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($detailPenilaian as $index => $detail)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $detail->name }}</td>
                                            {{-- <td>{{ $detail->keterangan_sebelum }}</td> --}}
                                            <td>{{ $detail->keterangan_detail }}</td>
                                            <td>{{ $detail->catatan }}</td>
                                            <td>{{ $detail->created_at->format('d-m-Y | H:i') }}</td>
                                            <td>{{ $detail->modified_at }}</td>
                                            </form>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No history found.</td>
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
                // Get the job position value from the input field
                var jobPosition = $('#jobPositionSelect').val();

                // Step 1: Fetch existing penilaian records
                $.ajax({
                    url: '{{ route('getDataTrs') }}',
                    type: 'GET',
                    data: { id_job_position: jobPosition },
                    success: function(existingData) {
                        // Step 2: Fetch ALL competency definitions from master data
                        $.ajax({
                            url: '{{ route('getJobPositionData') }}',
                            type: 'GET',
                            data: { id: jobPosition },
                            success: function(masterData) {
                                buildEditTable(existingData, masterData);
                            },
                            error: function() {
                                buildEditTable(existingData, []);
                            }
                        });
                    },
                    error: function() {
                        alert('Failed to fetch data.');
                    }
                });

                function buildEditTable(existingData, masterData) {
                    $('#penilaianTableBody').empty();
                    $('#headerKeterangan').empty();
                    $('#tcHeader').attr('colspan', 0);
                    $('#skHeader').attr('colspan', 0);
                    $('#adHeader').attr('colspan', 0);

                    var tcHeaders = [];
                    var skHeaders = [];
                    var adHeaders = [];

                    // Build headers from master data (complete set including new competencies)
                    masterData.forEach(function(row) {
                        if (row.type === 'tc' && row.keterangan && !tcHeaders.some(h => h.keterangan === row.keterangan)) {
                            tcHeaders.push({
                                keterangan: row.keterangan,
                                nilai: row.nilai,
                                id_tc: row.id_tc,
                                id_poin_kategori: row.id_poin_kategori
                            });
                        } else if (row.type === 'sk' && row.keterangan && !skHeaders.some(h => h.keterangan === row.keterangan)) {
                            skHeaders.push({
                                keterangan: row.keterangan,
                                nilai: row.nilai,
                                id_sk: row.id_sk,
                                id_poin_kategori: row.id_poin_kategori
                            });
                        } else if (row.type === 'ad' && row.keterangan && !adHeaders.some(h => h.keterangan === row.keterangan)) {
                            adHeaders.push({
                                keterangan: row.keterangan,
                                nilai: row.nilai,
                                id_ad: row.id_ad,
                                id_poin_kategori: row.id_poin_kategori
                            });
                        }
                    });

                    // Fallback: if master data empty, build headers from existing data
                    if (tcHeaders.length === 0 && skHeaders.length === 0 && adHeaders.length === 0) {
                        existingData.forEach(function(row) {
                            if (row.tc && !tcHeaders.some(item => item.keterangan === row.tc.keterangan_tc)) {
                                tcHeaders.push({
                                    keterangan: row.tc.keterangan_tc,
                                    nilai: row.tc.nilai,
                                    id_tc: row.tc.id,
                                    id_poin_kategori: row.tc.id_poin_kategori
                                });
                            }
                            if (row.sk && !skHeaders.some(item => item.keterangan === row.sk.keterangan_sk)) {
                                skHeaders.push({
                                    keterangan: row.sk.keterangan_sk,
                                    nilai: row.sk.nilai,
                                    id_sk: row.sk.id,
                                    id_poin_kategori: row.sk.id_poin_kategori
                                });
                            }
                            if (row.ad && !adHeaders.some(item => item.keterangan === row.ad.keterangan_ad)) {
                                adHeaders.push({
                                    keterangan: row.ad.keterangan_ad,
                                    nilai: row.ad.nilai,
                                    id_ad: row.ad.id,
                                    id_poin_kategori: row.ad.id_poin_kategori
                                });
                            }
                        });
                    }

                    function getBackgroundColorByIdPoinKategori(id_poin_kategori) {
                        if (id_poin_kategori == 1) return 'blue';
                        if (id_poin_kategori == 2) return 'green';
                        if (id_poin_kategori == 3) return 'orange';
                        return 'transparent';
                    }

                    tcHeaders.forEach(function(header) {
                        var bgColor = getBackgroundColorByIdPoinKategori(header.id_poin_kategori);
                        $('#headerKeterangan').append('<th style="background-color:' + bgColor + '; color: white;">' + header.keterangan + ' - (' + header.nilai + ')</th>');
                        $('#tcHeader').attr('colspan', parseInt($('#tcHeader').attr('colspan')) + 1);
                    });
                    skHeaders.forEach(function(header) {
                        var bgColor = getBackgroundColorByIdPoinKategori(header.id_poin_kategori);
                        $('#headerKeterangan').append('<th style="background-color:' + bgColor + '; color: white;">' + header.keterangan + ' - (' + header.nilai + ')</th>');
                        $('#skHeader').attr('colspan', parseInt($('#skHeader').attr('colspan')) + 1);
                    });
                    adHeaders.forEach(function(header) {
                        var bgColor = getBackgroundColorByIdPoinKategori(header.id_poin_kategori);
                        $('#headerKeterangan').append('<th style="background-color:' + bgColor + '; color: white;">' + header.keterangan + ' - (' + header.nilai + ')</th>');
                        $('#adHeader').attr('colspan', parseInt($('#adHeader').attr('colspan')) + 1);
                    });

                    // Group existing data by user (include record ID and user ID)
                    var displayedUsers = {};
                    existingData.forEach(function(row) {
                        if (!displayedUsers[row.user.name]) {
                            displayedUsers[row.user.name] = {
                                userId: row.id_user || row.user.id,
                                tc: {},
                                sk: {},
                                ad: {}
                            };
                        }
                        if (row.tc) {
                            displayedUsers[row.user.name].tc[row.tc.keterangan_tc] = {
                                nilai: row.nilai_tc,
                                keterangan: row.tc.keterangan_tc,
                                recordId: row.id
                            };
                        }
                        if (row.sk) {
                            displayedUsers[row.user.name].sk[row.sk.keterangan_sk] = {
                                nilai: row.nilai_sk,
                                keterangan: row.sk.keterangan_sk,
                                recordId: row.id
                            };
                        }
                        if (row.ad) {
                            displayedUsers[row.user.name].ad[row.ad.keterangan_ad] = {
                                nilai: row.nilai_ad,
                                keterangan: row.ad.keterangan_ad,
                                recordId: row.id
                            };
                        }
                    });

                    if (Object.keys(displayedUsers).length === 0) {
                        $('#penilaianTableBody').append('<tr><td colspan="4">No data found for the given job position.</td></tr>');
                        return;
                    }

                    for (var userName in displayedUsers) {
                        var userData = displayedUsers[userName];
                        var row = '<tr><td>' + userName + '</td>';

                        tcHeaders.forEach(function(header) {
                            var tcData = userData.tc[header.keterangan] || {
                                nilai: '',
                                keterangan: header.keterangan,
                                recordId: ''
                            };
                            var inputStyle = tcData.nilai && tcData.nilai < header.nilai ?
                                'color: red; background-color: #ffcccc;' : '';
                            row += '<td><input type="text" name="nilai_tc[]" value="' + tcData.nilai +
                                '" style="width: 50px;' + inputStyle +
                                '" data-keterangan-tc="' + header.keterangan +
                                '" data-record-id="' + tcData.recordId +
                                '" data-name="' + userName +
                                '" data-id-tc="' + (header.id_tc || '') +
                                '" data-id-user="' + userData.userId +
                                '"></td>';
                        });

                        skHeaders.forEach(function(header) {
                            var skData = userData.sk[header.keterangan] || {
                                nilai: '',
                                keterangan: header.keterangan,
                                recordId: ''
                            };
                            var inputStyle = skData.nilai && skData.nilai < header.nilai ?
                                'color: red; background-color: #ffcccc;' : '';
                            row += '<td><input type="text" name="nilai_sk[]" value="' + skData.nilai +
                                '" style="width: 50px;' + inputStyle +
                                '" data-keterangan-sk="' + header.keterangan +
                                '" data-record-id="' + skData.recordId +
                                '" data-name="' + userName +
                                '" data-id-sk="' + (header.id_sk || '') +
                                '" data-id-user="' + userData.userId +
                                '"></td>';
                        });

                        adHeaders.forEach(function(header) {
                            var adData = userData.ad[header.keterangan] || {
                                nilai: '',
                                keterangan: header.keterangan,
                                recordId: ''
                            };
                            var inputStyle = adData.nilai && adData.nilai < header.nilai ?
                                'color: red; background-color: #ffcccc;' : '';
                            row += '<td><input type="text" name="nilai_ad[]" value="' + adData.nilai +
                                '" style="width: 50px;' + inputStyle +
                                '" data-keterangan-ad="' + header.keterangan +
                                '" data-record-id="' + adData.recordId +
                                '" data-name="' + userName +
                                '" data-id-ad="' + (header.id_ad || '') +
                                '" data-id-user="' + userData.userId +
                                '"></td>';
                        });

                        row += '</tr>';
                        $('#penilaianTableBody').append(row);
                    }
                }
            });

            //button update
            $(document).ready(function() {
                // Get CSRF token from meta tag in header
                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                // Set CSRF token for all AJAX requests
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                $('#saveFormButton').on('click', function(e) {
                    e.preventDefault();

                    // Gather data grouped by record ID for correct backend mapping
                    var recordsMap = {};
                    var newRecords = [];

                    $('input[name="nilai_tc[]"]').each(function() {
                        var recordId = $(this).data('record-id');
                        if (!recordId) {
                            var val = $(this).val();
                            if (val) {
                                newRecords.push({
                                    id_user: $(this).data('id-user'),
                                    id_tc: $(this).data('id-tc'),
                                    nilai_tc: val,
                                    keterangan_tc: $(this).data('keterangan-tc'),
                                    name: $(this).data('name')
                                });
                            }
                            return;
                        }
                        if (!recordsMap[recordId]) {
                            recordsMap[recordId] = { id: recordId, name: $(this).data('name') };
                        }
                        recordsMap[recordId].nilai_tc = $(this).val();
                        recordsMap[recordId].keterangan_tc = $(this).data('keterangan-tc');
                    });

                    $('input[name="nilai_sk[]"]').each(function() {
                        var recordId = $(this).data('record-id');
                        if (!recordId) {
                            var val = $(this).val();
                            if (val) {
                                newRecords.push({
                                    id_user: $(this).data('id-user'),
                                    id_sk: $(this).data('id-sk'),
                                    nilai_sk: val,
                                    keterangan_sk: $(this).data('keterangan-sk'),
                                    name: $(this).data('name')
                                });
                            }
                            return;
                        }
                        if (!recordsMap[recordId]) {
                            recordsMap[recordId] = { id: recordId, name: $(this).data('name') };
                        }
                        recordsMap[recordId].nilai_sk = $(this).val();
                        recordsMap[recordId].keterangan_sk = $(this).data('keterangan-sk');
                    });

                    $('input[name="nilai_ad[]"]').each(function() {
                        var recordId = $(this).data('record-id');
                        if (!recordId) {
                            var val = $(this).val();
                            if (val) {
                                newRecords.push({
                                    id_user: $(this).data('id-user'),
                                    id_ad: $(this).data('id-ad'),
                                    nilai_ad: val,
                                    keterangan_ad: $(this).data('keterangan-ad'),
                                    name: $(this).data('name')
                                });
                            }
                            return;
                        }
                        if (!recordsMap[recordId]) {
                            recordsMap[recordId] = { id: recordId, name: $(this).data('name') };
                        }
                        recordsMap[recordId].nilai_ad = $(this).val();
                        recordsMap[recordId].keterangan_ad = $(this).data('keterangan-ad');
                    });

                    var data = {
                        records: Object.values(recordsMap),
                        new_records: newRecords
                    };

                    console.log("Data to be sent:", data);

                    // Prompt with SweetAlert to ask for reason
                    Swal.fire({
                        title: 'Alasan Perubahan Data',
                        input: 'textarea',
                        inputPlaceholder: 'Isi alasan perubahan data di sini...',
                        showCancelButton: true,
                        confirmButtonText: 'Simpan',
                        cancelButtonText: 'Batal',
                        inputValidator: (value) => {
                            if (!value) {
                                return 'Alasan perubahan wajib diisi!';
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Add the reason to data
                            data.alasan_perubahan = result.value;

                            // Send AJAX request
                            $.ajax({
                                url: '{{ route('updateDept', $penilaian->id_job_position) }}',
                                type: 'PUT',
                                contentType: 'application/json',
                                data: JSON.stringify(data),
                                success: function(response) {
                                    if (response.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Berhasil',
                                            text: response.message
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Gagal',
                                            text: 'Gagal memperbarui nilai.'
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Terjadi kesalahan:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Terjadi kesalahan: ' + error
                                    });
                                    console.log('Response Text:', xhr.responseText);
                                }
                            });
                        }
                    });
                });
            });

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
                            <th>Keterangan Competency</th>
                            <th>Deskripsi</th>
                            <th>Judul Keterangan Kategori</th>
                            <th>Nilai 1</th>
                            <th>Nilai 2</th>
                            <th>Nilai 3</th>
                            <th>Nilai 4</th>
                            <th>Nilai Standar</th>
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
                        <td>${tc.keterangan_tc ?? '-'}</td>
                        <td>${tc.deskripsi_tc ?? '-'}</td>
                        <td style="${background}">${tc.poin_kategori?.judul_keterangan ?? '-'}</td>
                        <td>${tc.poin_kategori?.deskripsi_1 ?? '-'}</td>
                        <td>${tc.poin_kategori?.deskripsi_2 ?? '-'}</td>
                        <td>${tc.poin_kategori?.deskripsi_3 ?? '-'}</td>
                        <td>${tc.poin_kategori?.deskripsi_4 ?? '-'}</td>
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
                            <th>Keterangan Soft Skills</th>
                            <th>Deskripsi</th>
                            <th>Judul Keterangan Kategori</th>
                            <th>Nilai 1</th>
                            <th>Nilai 2</th>
                            <th>Nilai 3</th>
                            <th>Nilai 4</th>
                            <th>Nilai Standar</th>
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
                        <td>${skill.keterangan_sk ?? '-'}</td>
                        <td>${skill.deskripsi_sk ?? '-'}</td>
                        <td style="${background}">${skill.poin_kategori?.judul_keterangan ?? '-'}</td>
                        <td>${skill.poin_kategori?.deskripsi_1 ?? '-'}</td>
                        <td>${skill.poin_kategori?.deskripsi_2 ?? '-'}</td>
                        <td>${skill.poin_kategori?.deskripsi_3 ?? '-'}</td>
                        <td>${skill.poin_kategori?.deskripsi_4 ?? '-'}</td>
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
                            <th>Keterangan Additional</th>
                            <th>Deskripsi</th>
                            <th>Judul Keterangan Kategori</th>
                            <th>Nilai 1</th>
                            <th>Nilai 2</th>
                            <th>Nilai 3</th>
                            <th>Nilai 4</th>
                            <th>Nilai Standar</th>
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
                        <td>${additional.keterangan_ad ?? '-'}</td>
                        <td>${additional.deskripsi_ad ?? '-'}</td>
                        <td style="${background}">${additional.poin_kategori?.judul_keterangan ?? '-'}</td>
                        <td>${additional.poin_kategori?.deskripsi_1 ?? '-'}</td>
                        <td>${additional.poin_kategori?.deskripsi_2 ?? '-'}</td>
                        <td>${additional.poin_kategori?.deskripsi_3 ?? '-'}</td>
                        <td>${additional.poin_kategori?.deskripsi_4 ?? '-'}</td>
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
