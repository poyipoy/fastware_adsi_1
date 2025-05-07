@extends('layout')

@section('content')
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            background-color: #f4f4f9;
        }

        h3 {
            color: #333;
            font-size: 1.5rem;
            margin-top: 20px;
        }

        /* Set table width to 100% */
        .styled-table {
            width: 100%;
            /* Semua tabel memiliki lebar penuh */
            border-collapse: collapse;
            /* Menghilangkan spasi antar border */
        }

        .styled-table th,
        .styled-table td {
            text-align: left;
            /* Menyelaraskan teks ke kiri */
            padding: 8px;
            /* Menambahkan padding */
            border: 1px solid #7a7979;
            /* Border antar sel */
        }

        .styled-table thead th {
            background-color: #c4c1c1;
            /* Warna latar belakang header */
            font-weight: bold;
            /* Menonjolkan header */
        }

        .styled-table tbody tr {
            height: 50px;
            /* Tinggi baris minimum */
        }

        .styled-table tbody tr.empty-row {
            height: 100px;
            /* Tinggi baris saat tidak ada data */
        }

        #details {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>

    </style>

    <main id="main" class="main">
        <section class="section dashboard">
            <div class="card" style="width: 100%;">
                <!-- Dropdown select di luar inner-card -->
                <div class="dropdown-select mt-4 ps-5">
                    <label for="options">Pilih Job Position:</label>
                    <select id="options" name="options" onchange="fetchDetails()">
                        <option value="">----- Pilih Position ------</option>
                        @foreach ($jobPositions as $id => $jobPosition)
                            <option value="{{ $id }}">{{ $jobPosition }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="details">
                    <!-- Details will be displayed here -->
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
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            function fetchDetails() {
                const jobPositionId = $('#options').val();

                console.log('Selected Job Position ID:', jobPositionId);

                if (jobPositionId) {
                    $.ajax({
                        url: '{{ route('job.positions.details') }}',
                        method: 'POST',
                        data: {
                            id: jobPositionId,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            let detailsHtml = '';

                            // Tabel untuk Technical Competencies
                            if (response.tcs.length > 0) {
                                detailsHtml += `
                <h3>Technical Competency</h3>
                <table class="styled-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 15%; text-align: left; padding: 8px;">Keterangan Competency</th>
                            <th style="width: 20%; text-align: left; padding: 8px;">Deskripsi</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Judul Keterangan Kategori</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 1</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 2</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 3</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 4</th>
                            <th style="width: 5%; text-align: left; padding: 8px;">Nilai Standar</th>
                        </tr>
                    </thead>
                    <tbody>`;
                                response.tcs.forEach(tc => {
                                    let judulKeterangan = tc.poin_kategori ? tc.poin_kategori
                                        .judul_keterangan : '-';
                                    let deskripsi1 = tc.poin_kategori ? tc.poin_kategori.deskripsi_1 : '-';
                                    let deskripsi2 = tc.poin_kategori ? tc.poin_kategori.deskripsi_2 : '-';
                                    let deskripsi3 = tc.poin_kategori ? tc.poin_kategori.deskripsi_3 : '-';
                                    let deskripsi4 = tc.poin_kategori ? tc.poin_kategori.deskripsi_4 : '-';

                                    // Menentukan warna background berdasarkan ID
                                    let background = '';
                                    if (tc.poin_kategori) {
                                        switch (tc.poin_kategori.id) {
                                            case 1:
                                                background = 'background-color: blue; color: white;';
                                                break;
                                            case 2:
                                                background = 'background-color: green; color: white;';
                                                break;
                                            case 3:
                                                background = 'background-color: orange; color: white;';
                                                break;
                                            default:
                                                background = '';
                                        }
                                    }

                                    detailsHtml += `
                    <tr>
                        <td style="padding: 8px;">${tc.keterangan_tc ?? '-'}</td>
                        <td style="padding: 8px;">${tc.deskripsi_tc ?? '-'}</td>
                        <td style="${background} padding: 8px;">${judulKeterangan}</td>
                        <td style="padding: 8px;">${deskripsi1}</td>
                        <td style="padding: 8px;">${deskripsi2}</td>
                        <td style="padding: 8px;">${deskripsi3}</td>
                        <td style="padding: 8px;">${deskripsi4}</td>
                        <td style="padding: 8px;">${tc.nilai ?? '-'}</td>
                    </tr>`;
                                });
                                detailsHtml += `
                    </tbody>
                </table>`;
                            }

                            // Tabel untuk Soft Skills
                            if (response.softSkills.length > 0) {
                                detailsHtml += `
                <h3>Soft Skills</h3>
                <table class="styled-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 15%; text-align: left; padding: 8px;">Keterangan Soft Skills</th>
                            <th style="width: 20%; text-align: left; padding: 8px;">Deskripsi</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Judul Keterangan Kategori</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 1</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 2</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 3</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 4</th>
                            <th style="width: 5%; text-align: left; padding: 8px;">Nilai Standar</th>
                        </tr>
                    </thead>
                    <tbody>`;
                                response.softSkills.forEach(skill => {
                                    let judulKeterangan = skill.poin_kategori ? skill.poin_kategori
                                        .judul_keterangan : '-';
                                    let deskripsi1 = skill.poin_kategori ? skill.poin_kategori.deskripsi_1 :
                                        '-';
                                    let deskripsi2 = skill.poin_kategori ? skill.poin_kategori.deskripsi_2 :
                                        '-';
                                    let deskripsi3 = skill.poin_kategori ? skill.poin_kategori.deskripsi_3 :
                                        '-';
                                    let deskripsi4 = skill.poin_kategori ? skill.poin_kategori.deskripsi_4 :
                                        '-';

                                    // Menentukan warna background berdasarkan ID
                                    let background = '';
                                    if (skill.poin_kategori) {
                                        switch (skill.poin_kategori.id) {
                                            case 1:
                                                background = 'background-color: blue; color: white;';
                                                break;
                                            case 2:
                                                background = 'background-color: green; color: white;';
                                                break;
                                            case 3:
                                                background = 'background-color: orange; color: white;';
                                                break;
                                            default:
                                                background = '';
                                        }
                                    }

                                    detailsHtml += `
                    <tr>
                        <td style="padding: 8px;">${skill.keterangan_sk ?? '-'}</td>
                        <td style="padding: 8px;">${skill.deskripsi_sk ?? '-'}</td>
                        <td style="${background} padding: 8px;">${judulKeterangan}</td>
                        <td style="padding: 8px;">${deskripsi1}</td>
                        <td style="padding: 8px;">${deskripsi2}</td>
                        <td style="padding: 8px;">${deskripsi3}</td>
                        <td style="padding: 8px;">${deskripsi4}</td>
                        <td style="padding: 8px;">${skill.nilai ?? '-'}</td>
                    </tr>`;
                                });
                                detailsHtml += `
                    </tbody>
                </table>`;
                            }

                            // Tabel untuk Additionals
                            if (response.additionals.length > 0) {
                                detailsHtml += `
                <h3>Additionals</h3>
                <table class="styled-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 15%; text-align: left; padding: 8px;">Keterangan Additional</th>
                            <th style="width: 20%; text-align: left; padding: 8px;">Deskripsi</th>
                            <th style="width: 15%; text-align: left; padding: 8px;">Judul Keterangan Kategori</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 1</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 2</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 3</th>
                            <th style="width: 10%; text-align: left; padding: 8px;">Nilai 4</th>
                            <th style="width: 5%; text-align: left; padding: 8px;">Nilai Standar</th>
                        </tr>
                    </thead>
                    <tbody>`;
                                response.additionals.forEach(additional => {
                                    let judulKeterangan = additional.poin_kategori ? additional
                                        .poin_kategori.judul_keterangan : '-';
                                    let deskripsi1 = additional.poin_kategori ? additional.poin_kategori
                                        .deskripsi_1 : '-';
                                    let deskripsi2 = additional.poin_kategori ? additional.poin_kategori
                                        .deskripsi_2 : '-';
                                    let deskripsi3 = additional.poin_kategori ? additional.poin_kategori
                                        .deskripsi_3 : '-';
                                    let deskripsi4 = additional.poin_kategori ? additional.poin_kategori
                                        .deskripsi_4 : '-';

                                    // Menentukan warna background berdasarkan ID
                                    let background = '';
                                    if (additional.poin_kategori) {
                                        switch (additional.poin_kategori.id) {
                                            case 1:
                                                background = 'background-color: blue; color: white;';
                                                break;
                                            case 2:
                                                background = 'background-color: green; color: white;';
                                                break;
                                            case 3:
                                                background = 'background-color: orange; color: white;';
                                                break;
                                            default:
                                                background = '';
                                        }
                                    }

                                    detailsHtml += `
                    <tr>
                        <td style="padding: 8px;">${additional.keterangan_ad ?? '-'}</td>
                        <td style="padding: 8px;">${additional.deskripsi_ad ?? '-'}</td>
                        <td style="${background} padding: 8px;">${judulKeterangan}</td>
                        <td style="padding: 8px;">${deskripsi1}</td>
                        <td style="padding: 8px;">${deskripsi2}</td>
                        <td style="padding: 8px;">${deskripsi3}</td>
                        <td style="padding: 8px;">${deskripsi4}</td>
                        <td style="padding: 8px;">${additional.nilai ?? '-'}</td>
                    </tr>`;
                                });
                                detailsHtml += `
                    </tbody>
                </table>`;
                            }

                            // Menampilkan hasil ke dalam div
                            $('#details').html(detailsHtml);
                        }
                    });
                } else {
                    $('#details').html('');
                }


            }
        </script>
    </main>
@endsection
