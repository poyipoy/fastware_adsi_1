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
                    <!-- Bagian Nama PIC hingga Posisi -->
                    <table border="1" cellpadding="5" cellspacing="0">
                        <tr>
                            <td colspan="2">Nama PIC :</td>
                            <td colspan="9">
                                <input type="text" name="nama_pic" class="input-field" value="{{ Auth::user()->name }}"
                                    readonly>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">Section :</td>
                            <td colspan="9">
                                <input type="text" name="section" class="input-field"
                                    value="{{ Auth::user()->roles->role }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">Posisi :</td>
                            <td colspan="9">
                                <select id="jobPositionSelect" name="posisi" class="form-control">
                                    <option value="">------ Pilih Posisi ------</option>
                                    @foreach ($jobPositions as $position)
                                        <option value="{{ $position->job_position }}">{{ $position->job_position }}</option>
                                    @endforeach
                                </select>
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


                    <!-- Bagian Header Tabel dengan Scroll -->
                    <div style="overflow-x: auto; white-space: nowrap;">
                        <table border="1" cellpadding="5" cellspacing="0">
                            <thead>
                                <tr>
                                    <th rowspan="2">Nama Employee</th>
                                    <!-- Placeholder for dynamic column titles based on type -->
                                    <th id="tcHeader" colspan="0">Technical Competency</th>
                                    <th id="skHeader" colspan="0">Softskills</th>
                                    <th id="adHeader" colspan="0">Additional</th>
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

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Tombol Submit dan Kembali -->
                        <div>
                            <button id="saveFormButton" type="button" class="btn btn-success me-2">
                                <i class="fas fa-save"></i> Submit
                            </button>
                            <a href="{{ route('penilaian.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                        <!-- Tombol Lihat Summary -->

                    </div>
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
                                $('#headerKeterangan').empty();
                                $('#keteranganFields').empty();

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
                                            id_tc: row
                                                .id_tc // Menggunakan keterangan sebagai id_tc
                                        });
                                    } else if (row.type === "sk" && row.keterangan && !
                                        skHeaders
                                        .some(header => header.keterangan === row
                                            .keterangan)) {
                                        skHeaders.push({
                                            keterangan: row.keterangan,
                                            nilai: row.nilai,
                                            id_sk: row
                                                .id_sk // Menggunakan keterangan sebagai id_sk
                                        });
                                    } else if (row.type === "ad" && row.keterangan && !
                                        adHeaders
                                        .some(header => header.keterangan === row
                                            .keterangan)) {
                                        adHeaders.push({
                                            keterangan: row.keterangan,
                                            nilai: row.nilai,
                                            id_ad: row
                                                .id_ad // Menggunakan keterangan sebagai id_ad
                                        });
                                    }
                                });

                                // Tambahkan headers ke dalam tabel dengan nilai yang sesuai
                                tcHeaders.forEach(function(header) {
                                    $('#headerKeterangan').append(
                                        `<th style="width: 200px; white-space: nowrap;">
                            ${header.keterangan} - (STD ${header.nilai})
                        </th>`
                                    );
                                });

                                skHeaders.forEach(function(header) {
                                    $('#headerKeterangan').append(
                                        `<th style="width: 200px; white-space: nowrap;">
                            ${header.keterangan} - (STD ${header.nilai})
                        </th>`
                                    );
                                });

                                adHeaders.forEach(function(header) {
                                    $('#headerKeterangan').append(
                                        `<th style="width: 200px; white-space: nowrap;">
                            ${header.keterangan} - (STD ${header.nilai})
                        </th>`
                                    );
                                });

                                // Membuat baris untuk setiap karyawan
                                var displayedNames = {};

                                data.forEach(function(row) {
                                    if (!displayedNames[row.name]) {
                                        var newRow = `<tr>
                                <td>${row.name}</td>
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

                                        newRow += '</tr>';
                                        displayedNames[row.name] = newRow;
                                    }
                                });

                                // Akhirnya, tampilkan semua baris yang telah diproses
                                for (var name in displayedNames) {
                                    $('#keteranganFields').append(displayedNames[name]);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Terjadi kesalahan:', error);
                            }
                        });

                        // Menghapus permintaan Ajax getJobPointKategori
                    } else {
                        $('#headerKeterangan').empty();
                        $('#keteranganFields').empty();
                    }
                });
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
