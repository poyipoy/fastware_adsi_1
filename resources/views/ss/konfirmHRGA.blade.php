@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Penilaian SS by HRGA</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Penilaian SS</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Table View Penilaian</h5>
                            <!-- Table with stripped rows -->
                            <div class="row">
                                <div class="col-lg-2">
                                    <label for="start_periode">Bulan Mulai:</label>
                                    <input type="date" id="start_periode" name="start_periode" class="form-control">
                                </div>
                                <div class="col-lg-2">
                                    <label for="end_periode">Bulan Akhir:</label>
                                    <input type="date" id="end_periode" name="end_periode" class="form-control">
                                </div>
                                <div class="col-lg-2 d-flex align-items-end">
                                    <div class="w-100">
                                        <label for="export" class="invisible">Export Excel</label>
                                        <button type="submit" class="btn btn-success w-50" id="export-button"
                                            onclick="exportTable()">
                                            <i class="fa fa-file-excel"></i> Export Excel
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-lg-2">
                                    <label for="bayar" class="invisible">Bayar</label>
                                    <button id="btnBayar" type="submit" class="btn btn-success w-100" id="bayar-button"
                                        onclick="">
                                        <i class="fa fa-usd" aria-hidden="true"></i>Bayar
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive" style="height: 100%; overflow-y: auto; margin-top:2%">
                                <table class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="30px"><input type="checkbox" id="select-all">
                                            </th> <!-- Checkbox for Select All -->
                                            <th class="text-center" width="30px">NO</th>
                                            <th class="text-center" width="100px">Nama</th>
                                            <th class="text-center" width="100px">NPK</th>
                                            <th class="text-center" width="100px">Bagian</th>
                                            <th class="text-center" width="100px">Plant</th>
                                            <th class="text-center" width="100px">Judul Ide</th>
                                            <th class="text-center" width="100px">Poin</th>
                                            <th class="text-center" width="100px">+poin</th>
                                            <th class="text-center" width="100px">Nilai</th>
                                            <th class="text-center" width="100px">amount</th>
                                            <th class="text-center" width="90px">Tanggal Pengajuan Ide</th>
                                            <th class="text-center" width="70px">Lokasi</th>
                                            <th class="text-center" width="100px">Tanggal Diterapkan</th>
                                            <th class="text-center" width="100px">Tanggal verifikasi</th>
                                            <th class="text-center" width="100px">Pembaruan Terakhir</th>
                                            <th class="text-center" width="190px">Status</th>
                                            <th class="text-center" width="150px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $item)
                                            <tr>
                                                <td class="text-center">
                                                    @if ($item->status != 7)
                                                        <input type="checkbox" name="select-item"
                                                            value="{{ $item->id }}">
                                                    @endif
                                                </td>
                                                <th scope="row" class="text-center">{{ $loop->iteration }}</th>
                                                <!-- Checkbox for each row -->
                                                <td class="text-center py-3" style="height: 50px;">{{ $item->name }}</td>
                                                <td class="text-center py-3" style="height: 50px;">{{ $item->npk }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $usersRoles[$item->id_user] ?? '' }}</td>
                                                <td class="text-center py-3" style="height: 50px;">{{ $item->plant }}</td>
                                                <td class="text-center py-3" style="height: 50px;">{{ $item->judul }}
                                                </td>
                                                <td class="text-center py-3" style="height: 50px;">{{ $item->nilai }}
                                                </td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->tambahan_nilai }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->total_nilai }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ 'Rp ' . number_format($item->hasil_akhir, 0, ',', '.') }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->tgl_pengajuan_ide }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->lokasi_ide }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->tgl_diterapkan }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->tgl_verifikasi }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->created_at }}</td>
                                                <td class="text-center py-4 status-badge"
                                                    style="height: 50px; max-width: 70%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                    title="@if ($item->status == 1) Draf @elseif ($item->status == 2) Menunggu Approve Foreman @elseif($item->status == 3) Menunggu Approve Dept. Head @elseif($item->status == 4) Direksi @endif"
                                                    data-status="{{ $item->status }}">
                                                    @if ($item->status == 1)
                                                        <span class="badge bg-secondary align-items-center"
                                                            style="font-size: 18px;">Draf</span>
                                                    @elseif ($item->status == 2)
                                                        <span class="badge bg-warning align-items-center"
                                                            style="font-size: 18px;">Menunggu<br>Konfirmasi Sec.
                                                            Head</span>
                                                    @elseif($item->status == 3)
                                                        <span class="badge bg-warning align-items-center"
                                                            style="font-size: 18px;">Menunggu<br>Konfirmasi Dept.
                                                            Head</span>
                                                    @elseif($item->status == 4)
                                                        <span class="badge bg-warning align-items-center"
                                                            style="font-size: 18px;">Menunggu<br>Konfirmasi Komite</span>
                                                    @elseif($item->status == 5)
                                                        <span class="badge bg-info align-items-center"
                                                            style="font-size: 18px;">SS sudah dinilai</span>
                                                    @elseif($item->status == 6)
                                                        <span class="badge bg-info align-items-center"
                                                            style="font-size: 18px;">SS sudah Verivikasi</span>
                                                    @elseif($item->status == 7)
                                                        <span class="badge bg-success align-items-center"
                                                            style="font-size: 18px;">SS Terbayar</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($item->status != 6 && $item->status != 7)
                                                        <button class="btn btn-primary btn-sm"
                                                            onclick="openFormTambahNilai({{ $item->id }})"
                                                            data-id="{{ $item->id }}" title="Tambahan Nilai">
                                                            <i class="fa-solid fa-tasks fa-1x"></i>
                                                        </button>
                                                    @endif
                                                    <button class="btn btn-success btn-sm"
                                                        onclick="viewFormSS({{ $item->id }})"
                                                        data-id="{{ $item->id }}" title="lihat">
                                                        <i class="fa-solid fa-eye fa-1x"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <!-- End Table with stripped rows -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="editTambahNilai" tabindex="-1" aria-labelledby="editTambahNilai"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="editSumbangSaranModalLabel">Form Tambah +Poin Sumbang Saran</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formTambahNilai" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" id="editSumbangSaranId" name="id">
                                <input type="hidden" id="ss_id" name="ss_id">
                                <div class="mb-3">
                                    <label for="tambahan_nilai" class="form-label">Tambah +Poin</label>
                                    <input type="text" class="form-control" id="tambahan_nilai" name="tambahan_nilai"
                                        required>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary"
                                        onclick="submitFormTambahNilai()">Verifikasi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="viewSumbangSaranModal" tabindex="-1"
                aria-labelledby="viewSumbangSaranModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 90%;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewSumbangSaranModalLabel">Form View SS</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form View Sumbang Saran -->
                            <form id="viewSumbangSaranForm" enctype="multipart/form-data">
                                @csrf
                                <div class="row mb-3">
                                    <label for="editLokasiIde" class="col-sm-2 col-form-label">Nama<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="viewname" name="nama"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editLokasiIde" class="col-sm-2 col-form-label">Npk<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="viewnpk" name="npk"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewTglPengajuan" class="col-sm-2 col-form-label">Tgl. pengajuan Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" id="viewTglPengajuan"
                                            name="tgl_pengajuan_ide" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewLokasiIde" class="col-sm-2 col-form-label">Lokasi Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="viewLokasiIde" name="lokasi_ide"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewTglDiterapkan" class="col-sm-2 col-form-label">Tgl. Diterapkan<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" id="viewTglDiterapkan"
                                            name="tgl_diterapkan" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewJudulIde" class="col-sm-2 col-form-label">Judul Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="viewJudulIde" name="judul"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewKeadaanSebelumnya" class="col-sm-2 col-form-label">Keadaan Sebelumnya
                                        (Permasalahan) <span style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="viewKeadaanSebelumnya" name="keadaan_sebelumnya" disabled></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewImage" class="col-sm-2 col-form-label">File Upload
                                        (Sebelumnya)</label>
                                    <div class="col-sm-10">
                                        <div id="view-image-preview" style="margin-top: 10px;"></div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewUsulanIde" class="col-sm-2 col-form-label">Usulan Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="viewUsulanIde" name="usulan_ide" disabled></textarea>
                                    </div>
                                </div>
                                <!-- Input File Upload 2 -->
                                <div class="row mb-3">
                                    <label for="viewImage2" class="col-sm-2 col-form-label">File Upload (Sesudah)</label>
                                    <div class="col-sm-10">
                                        <div id="view-image2-preview" style="margin-top: 10px;"></div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewKeuntungan" class="col-sm-2 col-form-label">Keuntungan Dari Penerapan
                                        Ide</label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="viewKeuntungan" name="keuntungan_ide" disabled></textarea>
                                    </div>
                                </div>
                                <input type="hidden" id="viewSumbangSaranId" name="id">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Gambar -->
            <div class="modal fade" id="viewImageModal" tabindex="-1" aria-labelledby="viewImageModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewImageModalLabel">Gambar</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="viewModalImage" src="" class="img-fluid">
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
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.2/xlsx.full.min.js"></script>

        <script>
            document.getElementById('select-all').addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('input[name="select-item"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            document.getElementById('btnBayar').addEventListener('click', function() {
                var selectedIds = [];
                var invalidIds = [];
                var checkboxes = document.querySelectorAll('input[name="select-item"]:checked');

                checkboxes.forEach(checkbox => {
                    var row = checkbox.closest('tr');
                    var statusBadge = row.querySelector('.status-badge');
                    var status = statusBadge ? statusBadge.getAttribute('data-status') : '';

                    if (status === '7') {
                        invalidIds.push(checkbox.value);
                    } else {
                        selectedIds.push(checkbox.value);
                    }
                });

                if (invalidIds.length > 0) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Beberapa item sudah terbayar dan tidak dapat diproses lagi.',
                        icon: 'error',
                        showConfirmButton: false,
                        timer: 2000 // Alert will auto-close after 2 seconds
                    });
                    return;
                }

                if (selectedIds.length === 0) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Tidak ada data yang dipilih.',
                        icon: 'error',
                        showConfirmButton: false,
                        timer: 2000 // Alert will auto-close after 2 seconds
                    });
                    return;
                }

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda akan mengubah status menjadi Terbayar.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, bayar!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('updateStatusToBayar') }}', // Make sure this route exists in your web.php
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                ids: selectedIds
                            },
                            success: function(response) {
                                console.log('Response:', response); // Log response
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Status berhasil diperbarui.',
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 2000 // Alert will auto-close after 2 seconds
                                }).then(() => {
                                    location
                                        .reload(); // Reload the page or update the UI accordingly
                                });
                            },
                            error: function(response) {
                                console.error('Error:', response); // Log error response
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan saat memperbarui status.',
                                    icon: 'error',
                                    showConfirmButton: false,
                                    timer: 2000 // Alert will auto-close after 2 seconds
                                });
                            }
                        });
                    }
                });
            });

            function openFormTambahNilai(id) {
                // Panggil endpoint untuk mendapatkan data sumbang saran berdasarkan ID
                $.ajax({
                    url: '{{ route('getTambahNilai', ['id' => ':id']) }}'.replace(':id', id),
                    type: 'GET',
                    success: function(response) {
                        console.log('Response received:', response); // Log response for debugging

                        var sumbangSaran = response.sumbangSaran; // Objek sumbangSaran dari respons
                        var tambahan_nilai = response.tambahan_nilai; // Nilai tambahan_nilai dari respons

                        console.log('SumbangSaran:', sumbangSaran); // Log sumbangSaran for debugging
                        console.log('tambahan_nilai:', tambahan_nilai); // Log tambahan_nilai for debugging

                        $('#editSumbangSaranId').val(sumbangSaran.id);
                        $('#tambahan_nilai').val(tambahan_nilai); // Isi nilai ke input field

                        // Set nilai ss_id ke input tersembunyi dalam modal
                        $('#ss_id').val(sumbangSaran.id);
                        // Tampilkan modal
                        $('#editTambahNilai').modal('show');
                    },
                    error: function(xhr) {
                        // Tangani kesalahan jika ada
                        console.log('Error:', xhr.responseText); // Log error for debugging
                    }
                });
            }

            document.getElementById('formTambahNilai').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent form from submitting normally

                var form = event.target;
                var requiredFields = form.querySelectorAll('[required]');
                var valid = true;
                var firstInvalidField = null;

                requiredFields.forEach(function(field) {
                    if (!field.value.trim()) {
                        valid = false;
                        if (!firstInvalidField) {
                            firstInvalidField = field;
                        }
                    }
                });

                if (!valid) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Harap isi semua field yang wajib diisi.',
                        icon: 'error',
                        showConfirmButton: false,
                        timer: 1000 // Alert will auto-close after 1 second
                    }).then(() => {
                        if (firstInvalidField) {
                            firstInvalidField.focus();
                        }
                    });
                    return;
                }

                var formData = new FormData(form);

                $.ajax({
                    url: '{{ route('submitTambahNilai') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Response:', response); // Log response
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Data berhasil disimpan.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000 // Alert will auto-close after 2 seconds
                        }).then((result) => {
                            if (result.dismiss === Swal.DismissReason.timer) {
                                // Perform any additional actions if needed
                                location.reload(); // Reload the page or update the UI accordingly
                            }
                        });
                    },
                    error: function(response) {
                        console.error('Error:', response); // Log error response
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menyimpan data.',
                            icon: 'error',
                            showConfirmButton: false,
                            timer: 2000 // Alert will auto-close after 2 seconds
                        });
                    }
                });
            });

            //viewmodal
            function viewFormSS(id) {
                $.ajax({
                    url: '{{ route('sechead.show', ':id') }}'.replace(':id', id),
                    type: 'GET',
                    success: function(response) {
                        console.log(response); // Debug: Check response

                        if (response) {
                            $('#viewname').val(response.user.name);
                            $('#viewnpk').val(response.user.npk);
                            $('#viewTglPengajuan').val(response.tgl_pengajuan_ide);
                            $('#viewPlant').val(response.plant);
                            $('#viewLokasiIde').val(response.lokasi_ide);
                            $('#viewTglDiterapkan').val(response.tgl_diterapkan);
                            $('#viewJudulIde').val(response.judul);
                            $('#viewKeadaanSebelumnya').val(response.keadaan_sebelumnya);
                            $('#viewUsulanIde').val(response.usulan_ide);
                            $('#viewKeuntungan').val(response.keuntungan_ide);
                            $('#viewSumbangSaranId').val(response.id);

                            // Menampilkan file pertama
                            if (response.file_name && response.image) {
                                var fileExtension1 = response.file_name.split('.').pop().toLowerCase();
                                var fileLink1 = '{{ asset('assets/image/') }}/' + response.image;
                                if (['jpg', 'jpeg', 'png'].includes(fileExtension1)) {
                                    $('#view-image-preview').html('<img src="' + fileLink1 +
                                        '" class="img-fluid rounded clickable-view-image" style="max-width: 200px; height: auto;" data-img-src="' +
                                        fileLink1 + '">');
                                    $('#view-image-preview img').click(function() {
                                        showImageInModal2(fileLink1, 'view');
                                    });
                                } else {
                                    $('#view-image-preview').html('<a href="' + fileLink1 + '" download="' +
                                        response.file_name + '">' + response.file_name + '</a>');
                                }
                            } else {
                                $('#view-image-preview').html('');
                            }

                            // Menampilkan file kedua
                            if (response.file_name_2 && response.image_2) {
                                var fileExtension2 = response.file_name_2.split('.').pop().toLowerCase();
                                var fileLink2 = '{{ asset('assets/image/') }}/' + response.image_2;
                                if (['jpg', 'jpeg', 'png'].includes(fileExtension2)) {
                                    $('#view-image2-preview').html('<img src="' + fileLink2 +
                                        '" class="img-fluid rounded clickable-view-image" style="max-width: 200px; height: auto;" data-img-src="' +
                                        fileLink2 + '">');
                                    $('#view-image2-preview img').click(function() {
                                        showImageInModal2(fileLink2, 'view');
                                    });
                                } else {
                                    $('#view-image2-preview').html('<a href="' + fileLink2 + '" download="' +
                                        response.file_name_2 + '">' + response.file_name_2 + '</a>');
                                }
                            } else {
                                $('#view-image2-preview').html('');
                            }

                            $('#viewSumbangSaranModal').modal('show');
                        } else {
                            console.error('Tidak ada data respons');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }

            // Fungsi untuk menampilkan gambar dalam modal
            function showImageInModal2(imageLink, modalType) {
                if (modalType === 'view') {
                    $('#viewImageModal').modal('show');
                    $('#viewModalImage').attr('src', imageLink);
                } else {
                    console.error('Modal type not recognized');
                }
            }

            function exportTable() {
                let startPeriode = $('#start_periode').val();
                let endPeriode = $('#end_periode').val();

                if (!startPeriode || !endPeriode) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Silakan pilih tanggal mulai dan tanggal akhir.'
                    });
                    return;
                }

                $.ajax({
                    url: '{{ route('export-konfirmasi-hrga') }}',
                    type: 'POST',
                    data: {
                        start_periode: startPeriode,
                        end_periode: endPeriode,
                        _token: '{{ csrf_token() }}' // CSRF token untuk Laravel
                    },
                    success: function(data) {
                        if (data.length === 0) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Tidak ada data',
                                text: 'Tidak ada data yang ditemukan untuk rentang tanggal yang dipilih.'
                            });
                            return;
                        }

                        let headers = ['NO', 'Nama', 'NPK', 'Bagian', 'Judul Ide', 'Poin', '+poin', 'Nilai',
                            'Amount', 'Tanggal Pengajuan Ide', 'Lokasi', 'Tanggal Diterapkan',
                            'Tanggal Verifikasi', 'Status'
                        ];
                        let worksheetData = [headers];

                        data.forEach((row, index) => {
                            let status = '';
                            switch (row.status) {
                                case 1:
                                    status = 'Draf';
                                    break;
                                case 2:
                                    status = 'Menunggu Konfirmasi Foreman';
                                    break;
                                case 3:
                                    status = 'Menunggu Konfirmasi Dept. Head';
                                    break;
                                case 4:
                                    status = 'Menunggu Konfirmasi Komite';
                                    break;
                                case 5:
                                    status = 'SS sudah dinilai';
                                    break;
                                case 6:
                                    status = 'SS sudah Verifikasi';
                                    break;
                                case 7:
                                    status = 'SS Terbayar';
                                    break;
                                default:
                                    status = '';
                            }

                            worksheetData.push([
                                index + 1,
                                row.name,
                                row.npk,
                                row.bagian, // Menggunakan kolom bagian untuk peran (role)
                                row.judul,
                                row.nilai,
                                row.tambahan_nilai,
                                row.total_nilai,
                                formatRupiah(row.hasil_akhir),
                                row.tgl_pengajuan_ide,
                                row.lokasi_ide,
                                row.tgl_diterapkan,
                                row.tgl_verifikasi,
                                status // Menambahkan kolom status
                            ]);
                        });

                        let ws = XLSX.utils.aoa_to_sheet(worksheetData);

                        // Konfigurasi lebar kolom
                        const columnWidths = [{
                                wch: 5
                            }, // Lebar kolom NO
                            {
                                wch: 20
                            }, // Lebar kolom Nama
                            {
                                wch: 10
                            }, // Lebar kolom NPK
                            {
                                wch: 15
                            }, // Lebar kolom Bagian
                            {
                                wch: 30
                            }, // Lebar kolom Judul Ide
                            {
                                wch: 8
                            }, // Lebar kolom Poin
                            {
                                wch: 8
                            }, // Lebar kolom +poin
                            {
                                wch: 8
                            }, // Lebar kolom Nilai
                            {
                                wch: 20
                            }, // Lebar kolom Amount
                            {
                                wch: 20
                            }, // Lebar kolom Tanggal Pengajuan Ide
                            {
                                wch: 15
                            }, // Lebar kolom Lokasi
                            {
                                wch: 20
                            }, // Lebar kolom Tanggal Diterapkan
                            {
                                wch: 20
                            }, // Lebar kolom Tanggal Verifikasi
                            {
                                wch: 15
                            } // Lebar kolom Status
                        ];

                        // Terapkan konfigurasi lebar kolom
                        ws['!cols'] = columnWidths;

                        // Tambahkan filter ke semua header
                        ws['!autofilter'] = {
                            ref: XLSX.utils.encode_range(XLSX.utils.decode_range(ws['!ref']))
                        };

                        let wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

                        XLSX.writeFile(wb, 'Reporting_SS.xlsx');

                        // Tampilkan pesan sukses
                        Swal.fire({
                            icon: 'success',
                            title: 'Sukses',
                            text: 'Data berhasil diekspor.',
                            timer: 2000, // Tampilan pesan sukses akan otomatis tertutup dalam 2 detik
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Kesalahan',
                            text: 'Terjadi kesalahan saat mengekspor data.'
                        });
                    }
                });
            }

            // Fungsi untuk memformat angka menjadi format rupiah
            function formatRupiah(angka, prefix = 'Rp') {
                let number_string = angka.toString().replace(/[^,\d]/g, ''),
                    split = number_string.split(','),
                    sisa = split[0].length % 3,
                    rupiah = split[0].substr(0, sisa),
                    ribuan = split[0].substr(sisa).match(/\d{3}/gi);

                if (ribuan) {
                    let separator = sisa ? '.' : '';
                    rupiah += separator + ribuan.join('.');
                }

                rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
                return prefix + ' ' + rupiah;
            }
        </script>

    </main><!-- End #main -->
@endsection
