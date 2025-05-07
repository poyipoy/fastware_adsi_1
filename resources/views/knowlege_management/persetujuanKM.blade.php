@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Persetujuan KM</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Persetujuan KM</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tampilan Data Knowlege Management</h5>
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
                                        @if ($item->status == 2 || $item->status == 3)
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
                                                        <span class="badge bg-primary align-items-center"
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
                                                    @if ($item->status != 4)
                                                        <a class="btn btn-primary mt-1" title="Edit"
                                                            onclick="openEditKmModal({{ $item->id }})">
                                                            <i class="bi bi-folder2-open"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <!-- Modal Edit-->
                <div class="modal fade" id="editKmModal" tabindex="-1" aria-labelledby="editKmModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editKmModalLabel">Persetujuan Knowledge Management</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('approveKM') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" id="editId" name="id">
                                    <div class="mb-3">
                                        <label for="editJudul" class="form-label">Judul</label>
                                        <input type="text" class="form-control" id="editJudul" name="judul">
                                    </div>
                                    <div class="mb-3">
                                        <label for="editKeterangan" class="form-label">Sinopsis Isi Buku</label>
                                        <textarea class="form-control" id="editKeterangan" name="keterangan" rows="4"></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-10">
                                            <div id="editFileLink" style="margin-top: 10px; display: none;">
                                                <button id="editFileButton" class="btn btn-primary"
                                                    onclick="openPdf()">Tampilkan Buku</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editPosisi" class="form-label">Bagian</label>
                                        <select id="editPosisi" name="posisi" class="form-select">
                                            <option value="">----- Pilih Bagian -----</option>
                                            <option value="HR">HR</option>
                                            <option value="Dept. Head">Dept. Head</option>
                                            <option value="Sec. Head">Sec. Head</option>
                                            <option value="All Employee">All Employee</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editKategori" class="form-label">Kategori</label>
                                        <select id="editKategori" class="form-select" name="id_km_kategori">
                                            <option value="">----- Pilih Kategori -----</option>
                                            <!-- Option akan diisi oleh JavaScript -->
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="approve" class="btn btn-primary">Disetujui</button>
                                        <button type="submit" name="reject" class="btn btn-danger">Ditolak</button>
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
            var fileUrl = '';

            function openEditKmModal(id) {
                // Membuat panggilan AJAX untuk mengambil data
                $.ajax({
                    url: `{{ route('showPersetujuan', ['id' => ':id']) }}`.replace(':id', id),
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        console.log(data); // Log ini untuk memastikan data diterima

                        // Mengisi nilai form pada modal edit
                        $('#editId').val(data.km.id);
                        $('#editJudul').val(data.km.judul);
                        $('#editKeterangan').val(data.km.keterangan);

                        // Menampilkan informasi file jika ada
                        if (data.km.file) {
                            fileUrl = `{{ asset('assets/image/') }}/${data.km.file}`;
                            $('#editFileLink').show();
                        } else {
                            $('#editFileLink').hide();
                        }

                        // Mengisi dropdown kategori
                        var kategoriSelect = $('#editKategori');
                        kategoriSelect.empty(); // Kosongkan dropdown
                        kategoriSelect.append(
                            '<option value="">----- Pilih Kategori -----</option>'); // Tambahkan opsi default
                        $.each(data.kategoris, function(index, kategori) {
                            kategoriSelect.append(
                                $('<option>', {
                                    value: kategori.id,
                                    text: kategori.nama_kategori
                                })
                            );
                        });

                        // Set nilai kategori yang dipilih
                        kategoriSelect.val(data.km.id_km_kategori);

                        // Set nilai posisi yang dipilih
                        $('#editPosisi').val(data.km.posisi);

                        // Menampilkan modal edit
                        $('#editKmModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }

            function openPdf() {
                if (fileUrl) {
                    window.open(fileUrl, '_blank');
                }
            }
        </script>

    </main><!-- End #main -->
@endsection
