@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan KM</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Pengajuan KM</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tampilan Data Knowlege Management</h5>
                    <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#kmModal">Form
                        KM</button>
                    @if ($km->isEmpty())
                        <p>Tidak ada data yang ditemukan.</p>
                    @else
                        <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                            <table class="datatable table">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">PIC</th>
                                        <th scope="col">Judul</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($km as $item)
                                        <tr>
                                            <th scope="row">{{ $loop->iteration }}</th>
                                            <td>{{ $item->user->name }}</td>
                                            <td>{{ $item->judul }}</td>
                                            <td class="text-center py-4"
                                                style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                @if ($item->status == 0)
                                                    <span class="badge bg-danger align-items-center"
                                                        style="font-size: 18px;">Tidak Aktif</span>
                                                @elseif ($item->status == 1)
                                                    <span class="badge bg-secondary align-items-center"
                                                        style="font-size: 18px;">Draf</span>
                                                @elseif($item->status == 2)
                                                    <span class="badge bg-warning align-items-center"
                                                        style="font-size: 18px;">Menunggu <br> Persetujuan HR</span>
                                                @elseif($item->status == 3)
                                                    <span class="badge bg-success align-items-center"
                                                        style="font-size: 18px;">Publish</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->status != 0 && $item->status != 2 && $item->status != 3)
                                                    <button type="button" class="btn btn-primary"
                                                        onclick="openEditKmModal({{ $item->id }})"
                                                        data-id="{{ $item->id }}" data-judul="{{ $item->judul }}"
                                                        data-keterangan="{{ $item->keterangan }}"
                                                        data-thumbnail="{{ $item->image }}"> <!-- Pass the image data -->
                                                        Edit
                                                    </button>

                                                    <a class="btn btn-danger mt-1" title="Nonaktifkan"
                                                        onclick="updateStatusKm({{ $item->id }})">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </a>
                                                    <a class="btn btn-info mt-1" title="Kirim"
                                                        onclick="kirimKM({{ $item->id }})">
                                                        <i class="bi bi-cursor-fill"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Modal Add-->
                <div class="modal fade" id="kmModal" tabindex="-1" aria-labelledby="kmModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="inquiryModalLabel">Form input Knowledge Management</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('storeKM') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="judul" class="form-label">Judul</label>
                                        <input type="text" class="form-control" id="judul" name="judul" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="keterangan" class="form-label">Sinopsis Isi Buku</label>
                                        <textarea class="form-control" id="keterangan" name="keterangan" rows="4" required></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="inputNumber" class="form-label">File Pengajuan<span
                                                style="color: red;">*</span></label>
                                        <div class="col-sm-10">
                                            <input class="form-control" type="file" id="file" name="file"
                                                accept=".ppt,.pptx,.pdf" width="100%" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="thumbnail" class="form-label">Upload Thumbnail</label>
                                        <div class="col-sm-10">
                                            <input class="form-control" type="file" id="image" name="image"
                                                accept=".jpg,.jpeg,.png" width="100%">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- End Modal -->
                <!-- Edit Modal -->
                <div class="modal fade" id="editKmModal" tabindex="-1" aria-labelledby="editKmModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editKmModalLabel">Edit Knowledge Management</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('updateKM') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" id="editId" name="id">
                                    <div class="mb-3">
                                        <label for="editJudul" class="form-label">Judul</label>
                                        <input type="text" class="form-control" id="editJudul" name="judul"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editKeterangan" class="form-label">Sinopsis Isi Buku</label>
                                        <textarea class="form-control" id="editKeterangan" name="keterangan" rows="4" required></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="editFile" class="form-label">File Pengajuan</label>
                                        <div class="col-sm-10">
                                            <input class="form-control" type="file" id="editFile" name="file"
                                                accept=".ppt,.pptx,.pdf" width="100%">
                                            <div id="editFileLink" style="display: none;">
                                                <a id="editFileName" href="#">Lihat File</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="editThumbnail" class="form-label">Upload Thumbnail</label>
                                        <div class="col-sm-10">
                                            <input class="form-control" type="file" id="editThumbnail" name="image"
                                                accept=".jpg,.jpeg,.png" width="100%">
                                        </div>
                                        <div class="col-sm-10">
                                            <img id="currentThumbnail" src="" alt="Current Thumbnail"
                                                style="max-width: 100%; margin-top: 10px;">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Modal Edit -->
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
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>


        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>

        <script>
            function openEditKmModal(id) {
                // Membuat panggilan AJAX untuk mengambil data
                $.ajax({
                    url: `{{ route('editKM', ['id' => ':id']) }}`.replace(':id', id),
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // Mengisi nilai form pada modal edit
                        $('#editId').val(data.id);
                        $('#editJudul').val(data.judul);
                        $('#editKeterangan').val(data.keterangan);

                        // Menampilkan informasi file jika ada
                        if (data.file_name) {
                            $('#editFileLabel').text('File tersimpan:');
                            $('#editFileName').text(data
                                .file_name); // Memperbarui dengan file_name yang diterima dari server
                            $('#editFileLink').show();
                            $('#editFile').prop('required', false);
                        } else {
                            $('#editFileLabel').text('Pilih file:');
                            $('#editFileLink').hide();
                            $('#editFile').prop('required', true);
                        }

                        // Menampilkan thumbnail jika ada
                        if (data.image) {
                            $('#currentThumbnail').attr('src', '/assets/image/' + data.image).show();
                        } else {
                            $('#currentThumbnail').hide();
                        }

                        // Menampilkan modal edit
                        $('#editKmModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }

            function updateStatusKm(id) {
                if (confirm('Anda yakin ingin menonaktifkan data ini?')) {
                    $.ajax({
                        url: `{{ route('updateStatusKM', ['id' => ':id']) }}`.replace(':id', id),
                        type: 'PATCH', // atau 'PUT' sesuai kebutuhan
                        dataType: 'json',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            alert(response.message);
                            // Lakukan update atau refresh halaman jika diperlukan
                            // Contoh: reload halaman setelah penghapusan
                            window.location.reload();
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat memperbarui status data.');
                        }
                    });
                }
            }

            function kirimKM(id) {
                if (confirm('Anda yakin ingin mengirim data ini?')) {
                    $.ajax({
                        url: `{{ route('kirimKM', ['id' => ':id']) }}`.replace(':id', id),
                        type: 'POST',
                        dataType: 'json',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            alert(response.message);
                            window.location.reload();
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                alert(xhr.responseJSON.message);
                            } else {
                                alert('Terjadi kesalahan saat mengirim data.');
                            }
                        }
                    });
                }
            }
        </script>

    </main><!-- End #main -->
@endsection
