@extends('layout')

@section('content')
    <main id="main" class="main">
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

            .modal-header {
                font-weight: bold;
                font-size: 1.2rem;
                text-transform: uppercase;
            }

            #details {
                font-size: 0.9rem;
                line-height: 1.6;
                color: #333;
                padding: 15px;
            }

            .styled-table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
                font-size: 0.9rem;
                text-align: left;
                border: 1px solid #ddd;
            }

            .styled-table th,
            .styled-table td {
                padding: 10px;
                border: 1px solid #ddd;
            }

            .styled-table th {
                background-color: #c4c1c1;
                color: rgb(0, 0, 0);
            }

            .styled-table tbody tr:nth-child(even) {
                background-color: #f9f9f9;
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
                    <li class="breadcrumb-item active">Menu List Penilaian Competency</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="card">

                <form id="penilaianForm" enctype="multipart/form-data" method="POST">
                    @csrf
                    @php
                        $picJobPos = Auth::user()->userJobPositions->first();
                        $picJobName = $picJobPos && $picJobPos->jobPosition ? $picJobPos->jobPosition->job_position : Auth::user()->roles->role;
                    @endphp
                    <!-- Bagian Nama PIC hingga Posisi -->
                    <div class="row g-3 mb-4 mt-2">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="nama_pic" id="nama_pic" class="form-control bg-light" value="{{ Auth::user()->name }}" readonly>
                                <label for="nama_pic">Nama PIC</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select id="jobPositionSelect" name="posisi" class="form-select">
                                    <option value="">------ Pilih Posisi ------</option>
                                    @foreach ($jobPositions as $position)
                                        <option value="{{ $position->id }}">{{ $position->job_position }}</option>
                                    @endforeach
                                </select>
                                <label for="jobPositionSelect">Posisi</label>
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


                    <!-- Bagian Header Tabel dengan Scroll -->
                    <div style="overflow-x: auto; white-space: nowrap;">
                        <table border="1" cellpadding="5" cellspacing="0">
                            <thead>
                                <tr>
                                    <th rowspan="2">NPK — Nama Employee</th>
                                    <!-- Placeholder for dynamic column titles based on type -->
                                    <th id="tcHeader" colspan="0" style="background-color: blue; color: white; display: none;">Technical Competency</th>
                                    <th id="skHeader" colspan="0" style="background-color: green; color: white; display: none;">Softskills</th>
                                    <th id="adHeader" colspan="0" style="background-color: orange; color: white; display: none;">Additional</th>
                                    <th rowspan="3" class="text-center bg-info text-white">Rata-rata Score</th>
                                </tr>
                                <tr id="headerKeterangan">
                                    <!-- Keterangan headers will be dynamically inserted here -->
                                </tr>
                                <tr id="headerNilai">
                                    <!-- Nilai headers will be dynamically inserted here -->
                                </tr>
                            </thead>
                            <tbody id="keteranganFields">
                                <!-- Rows will be dynamically inserted here -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                        <div class="d-flex gap-3">
                            <a href="{{ route('penilaian.index') }}" class="btn btn-light border px-4 py-2 rounded-pill shadow-sm">
                                <i class="fas fa-arrow-left me-2"></i> Kembali
                            </a>
                            <button id="saveFormButton" type="button" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm disabled" disabled>
                                <i class="fas fa-save me-2"></i> Submit Penilaian
                            </button>
                        </div>
                        <button id="openModalButton" type="button" class="btn btn-info text-white px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#jobPositionModal">
                            <i class="fas fa-eye me-2"></i> Lihat Summary
                        </button>
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

        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            $(document).ready(function() {
                $('#jobPositionSelect').on('change', function() {
                    var jobPosition = $(this).val();

                    if (jobPosition) {
                        $.ajax({
                            url: '{{ route('getJobPositionData') }}',
                            type: 'GET',
                            data: {
                                id: jobPosition // Mengirimkan job_position dari dropdown
                            },
                            success: function(data) {
                                // Clear header and data rows
                                $('#headerKeterangan').empty();
                                $('#keteranganFields').empty();
                                // Reset colspan group headers
                                $('#tcHeader').attr('colspan', 0).hide();
                                $('#skHeader').attr('colspan', 0).hide();
                                $('#adHeader').attr('colspan', 0).hide();

                                var tcHeaders = [];
                                var skHeaders = [];
                                var adHeaders = [];

                                // Mengumpulkan headers untuk setiap kategori dan menghindari duplikat
                                data.forEach(function(row) {
                                    if (row.type === "tc" && row.keterangan && !tcHeaders
                                        .some(header => header.keterangan === row
                                            .keterangan)) {
                                        tcHeaders.push({
                                            keterangan: row.keterangan,
                                            nilai: row.nilai,
                                            id_tc: row.id_tc,
                                            id_poin_kategori: row.id_poin_kategori
                                        });
                                    } else if (row.type === "sk" && row.keterangan && !
                                        skHeaders
                                        .some(header => header.keterangan === row
                                            .keterangan)) {
                                        skHeaders.push({
                                            keterangan: row.keterangan,
                                            nilai: row.nilai,
                                            id_sk: row.id_sk,
                                            id_poin_kategori: row.id_poin_kategori
                                        });
                                    } else if (row.type === "ad" && row.keterangan && !
                                        adHeaders
                                        .some(header => header.keterangan === row
                                            .keterangan)) {
                                        adHeaders.push({
                                            keterangan: row.keterangan,
                                            nilai: row.nilai,
                                            id_ad: row.id_ad,
                                            id_poin_kategori: row.id_poin_kategori
                                        });
                                    }
                                });

                                // Helper: warna berdasarkan id_poin_kategori
                                function getColorByPoinKategori(id) {
                                    if (id == 1) return 'blue';
                                    if (id == 2) return 'green';
                                    if (id == 3) return 'orange';
                                    return '#6c757d';
                                }

                                // Update colspan & tampilkan group header jika ada data
                                if (tcHeaders.length > 0) {
                                    $('#tcHeader').attr('colspan', tcHeaders.length).show();
                                }
                                if (skHeaders.length > 0) {
                                    $('#skHeader').attr('colspan', skHeaders.length).show();
                                }
                                if (adHeaders.length > 0) {
                                    $('#adHeader').attr('colspan', adHeaders.length).show();
                                }

                                // Tambahkan sub-headers TC dengan warna
                                tcHeaders.forEach(function(header) {
                                    var bgColor = getColorByPoinKategori(header.id_poin_kategori);
                                    $('#headerKeterangan').append(
                                        `<th style="width: 200px; white-space: nowrap; background-color: ${bgColor}; color: white;">
                            ${header.keterangan} - (STD ${header.nilai})
                        </th>`
                                    );
                                });

                                // Tambahkan sub-headers SK dengan warna
                                skHeaders.forEach(function(header) {
                                    var bgColor = getColorByPoinKategori(header.id_poin_kategori);
                                    $('#headerKeterangan').append(
                                        `<th style="width: 200px; white-space: nowrap; background-color: ${bgColor}; color: white;">
                            ${header.keterangan} - (STD ${header.nilai})
                        </th>`
                                    );
                                });

                                // Tambahkan sub-headers AD dengan warna
                                adHeaders.forEach(function(header) {
                                    var bgColor = getColorByPoinKategori(header.id_poin_kategori);
                                    $('#headerKeterangan').append(
                                        `<th style="width: 200px; white-space: nowrap; background-color: ${bgColor}; color: white;">
                            ${header.keterangan} - (STD ${header.nilai})
                        </th>`
                                    );
                                });

                                // Membuat baris hanya untuk karyawan yang BELUM punya penilaian
                                var displayedNames = {};

                                data.forEach(function(row) {
                                    // Skip user yang sudah punya penilaian (hanya dipakai untuk headers)
                                    if (row.has_penilaian == 1) return;

                                    if (!displayedNames[row.id_user]) {
                                        const npk = String(row.npk || '').trim();
                                        const employeeLabel = `${npk && npk !== '0' ? npk : '-'} — ${row.name || '-'}`;
                                        const safeEmployeeLabel = $('<div>').text(employeeLabel).html();
                                        var newRow = `<tr>
                                <td>${safeEmployeeLabel}</td>
                                <input type="hidden" name="id_user[]" value="${row.id_user}">
                            `;

                                        // Ini adalah bagian untuk membuat input dinamis untuk setiap user
                                        tcHeaders.forEach(function(header) {
                                            newRow +=
                                                `<td>
                                    <input type="number" name="nilai_tc[${row.id_user}][]" min="1" max="4" step="1" size="6" data-id_tc="${header.id_tc}" data-id_user="${row.id_user}">
                                    <input type="hidden" name="id_tc[${row.id_user}][]" value="${header.id_tc}">
                                </td>`;
                                        });

                                        skHeaders.forEach(function(header) {
                                            newRow +=
                                                `<td>
                                    <input type="number" name="nilai_sk[${row.id_user}][]" min="1" max="4" step="1" size="6" data-id_sk="${header.id_sk}" data-id_user="${row.id_user}">
                                    <input type="hidden" name="id_sk[${row.id_user}][]" value="${header.id_sk}">
                                </td>`;
                                        });

                                        adHeaders.forEach(function(header) {
                                            newRow +=
                                                `<td>
                                    <input type="number" name="nilai_ad[${row.id_user}][]" min="1" max="4" step="1" size="6" data-id_ad="${header.id_ad}" data-id_user="${row.id_user}">
                                    <input type="hidden" name="id_ad[${row.id_user}][]" value="${header.id_ad}">
                                </td>`;
                                        });

                                        newRow += '<td class="text-center align-middle fw-bold avg-score">0.00</td></tr>';
                                        displayedNames[row.id_user] = newRow;
                                    }
                                });

                                // Akhirnya, tampilkan semua baris yang telah diproses
                                var totalCount = Object.keys(displayedNames).length;
                                if (totalCount === 0) {
                                    var totalCols = 1 + tcHeaders.length + skHeaders.length + adHeaders.length + 1;
                                    $('#keteranganFields').append(`
                                        <tr>
                                            <td colspan="${totalCols}" class="text-center py-5 text-muted bg-light">
                                                <i class="bi bi-info-circle fs-3 d-block mb-2 text-primary"></i>
                                                <h6 class="fw-bold mb-1">Semua karyawan pada posisi ini sudah memiliki penilaian</h6>
                                                <p class="small mb-2">Anda tidak perlu menambahkan penilaian baru untuk periode saat ini.</p>
                                                <a href="{{ route('penilaian.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    <i class="fas fa-edit me-1"></i> Ke Menu Edit Penilaian
                                                </a>
                                            </td>
                                        </tr>
                                    `);
                                    $('#saveFormButton').prop('disabled', true).addClass('disabled');
                                } else {
                                    for (var name in displayedNames) {
                                        $('#keteranganFields').append(displayedNames[name]);
                                    }
                                    $('#saveFormButton').prop('disabled', false).removeClass('disabled');
                                }

                                // Initial Calculation
                                calculateAverages();
                            },
                            error: function(xhr, status, error) {
                                console.error('Terjadi kesalahan:', error);
                            }
                        });

                        // Menghapus permintaan Ajax getJobPointKategori
                    } else {
                        $('#headerKeterangan').empty();
                        $('#keteranganFields').empty();
                        $('#saveFormButton').prop('disabled', true).addClass('disabled');
                    }
                });
            });
            
            function calculateAverages() {
                $('#keteranganFields tr').each(function() {
                    let total = 0;
                    let count = 0;
                    $(this).find('input[type="number"]').each(function() {
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

            // Calculate average on input change
            $(document).on('input', 'input[type="number"]', function() {
                calculateAverages();
            });

            $('#saveFormButton').on('click', function(event) {
                event.preventDefault(); // Prevent the default action

                var formData = new FormData($('#penilaianForm')[0]); // Ambil semua data dari form

                // Ambil nilai job_position dari dropdown
                var idJobPosition = $('#jobPositionSelect').val();

                if (!idJobPosition) {
                    alert('Harap pilih posisi terlebih dahulu.');
                    return;
                }

                $.ajax({
                    url: '{{ route('savePenilaian') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // CSRF token dalam header
                    },
                    data: formData,
                    processData: false, // Menghindari proses otomatis jQuery terhadap data
                    contentType: false, // Menghindari pengaturan contentType otomatis oleh jQuery
                    success: function(response) {
                        console.log('Data berhasil disimpan:', response);
                        window.location.href = '{{ route('penilaian.preview', '') }}/' + idJobPosition;
                    },
                    error: function(xhr, status, error) {
                        console.error('Terjadi kesalahan:', error);
                    }
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
