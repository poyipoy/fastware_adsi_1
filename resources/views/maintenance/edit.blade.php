@extends('layout')

@section('content')
    <!-- SweetAlert2 for alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Bootstrap 5 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add these scripts after Bootstrap CSS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>


    <!-- jQuery and Popper.js (required by Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-qzQ9pyH1/Nkq4ysbr8yjBq44xDG/BaUkmUamJsIviGniGRC3plUSllPPe9wCJlY6k4t5IfMEO/A7R5Q2TDe2iQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <main id="main" class="main">

        <!-- New Form Content -->
        <section class="section">
            <div class="row">
                <div class="col-mt-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="accordion">
                                <h5 class="card-title">
                                    Form Permintaan Perbaikan
                                </h5>

                                <div class="collapse" id="accordion{{ $formperbaikan->id }}">
                                    <form id="FPPForm" action="{{ route('formperbaikans.update', $formperbaikan->id) }}"
                                        method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')

                                        <div class="mb-3">
                                            <label for="pemohon" class="form-label">
                                                Pemohon<span style="color: red;">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="pemohon" name="pemohon"
                                                value="{{ $formperbaikan->pemohon }}" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="date" class="form-label">
                                                Tanggal<span style="color: red;">*</span>
                                            </label>
                                            <input type="date" class="form-control" id="tanggal" name="tanggal"
                                                value="{{ $formperbaikan->tanggal }}" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="mesin" class="form-label">
                                                Mesin<span style="color: red;">*</span>
                                            </label>
                                            <select class="form-select" id="mesin" name="mesin" disabled>
                                                <option value="{{ $formperbaikan->mesin }}" selected>
                                                    {{ $formperbaikan->mesin }}</option>
                                            </select>
                                            <input type="hidden" name="mesin" value="{{ $formperbaikan->mesin }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="section" class="form-label">
                                                Bagian<span style="color: red;">*</span>
                                            </label>
                                            <select class="form-select" id="section" name="section" disabled>
                                                <option value="{{ $formperbaikan->section }}" selected>
                                                    {{ $formperbaikan->section }}</option>
                                            </select>
                                            <input type="hidden" name="section" value="{{ $formperbaikan->section }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="lokasi" class="form-label">
                                                Lokasi Mesin<span style="color: red;">*</span>
                                            </label>
                                            <select class="form-select" id="lokasi" name="lokasi" disabled>
                                                <option value="{{ $formperbaikan->lokasi }}" selected>
                                                    {{ $formperbaikan->lokasi }}</option>
                                            </select>
                                            <input type="hidden" name="lokasi" value="{{ $formperbaikan->lokasi }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="kendala" class="form-label">
                                                Kendala<span style="color: red;">*</span>
                                            </label>
                                            <textarea class="form-control" id="kendala" name="kendala" readonly>{{ $formperbaikan->kendala }}</textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="gambar" class="form-label">Gambar</label>
                                            <div id="gambarPreviewContainer">
                                                @if ($formperbaikan->gambar)
                                                    <img id="gambarPreview" src="{{ asset($formperbaikan->gambar) }}"
                                                        alt="Preview Gambar" style="max-width: 200px; cursor: pointer;">
                                                @else
                                                    <p>No image available</p>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Modal -->
                                        <div id="imageModal" class="modal">
                                            <span class="close" onclick="closeImageModal()">&times;</span>
                                            <img class="modal-content" id="img01">
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-mt-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="accordion">
                                <h5 class="card-title">
                                    Perbarui Progres
                                </h5>

                                <div class="collapse" id="updateProgress">
                                    <!-- Form Update Progress -->
                                    <form action="{{ route('formperbaikans.update', $formperbaikan->id) }}"
                                        method="POST" enctype="multipart/form-data"
                                        onsubmit="handleFormSubmission(event)">
                                        @csrf
                                        @method('PUT')

                                        <div class="mb-3">
                                            <label for="tindak_lanjut" class="form-label">Tindak Lanjut</label>
                                            <textarea class="form-control" id="tindak_lanjut" name="tindak_lanjut" rows="3" disabled></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="due_date" class="form-label">Tanggal Jatuh Tempo</label>
                                            <input type="date" class="form-control" id="due_date" name="due_date"
                                                disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label for="schedule_pengecekan" class="form-label">Jadwal Pengecekan</label>
                                            <input type="date" class="form-control" id="schedule_pengecekan"
                                                name="schedule_pengecekan" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label for="attachment_file" class="form-label">Unggah Data</label>
                                            <input type="file" class="form-control" id="attachment_file"
                                                name="attachment_file" disabled>
                                        </div>

                                        <input type="hidden" name="confirmed_finish5" id="confirmed_finish5"
                                            value='0'>

                                        <div class="text-end">
                                            <!-- Change the button type to 'button' to prevent default form submission -->
                                            <button type="button" class="btn btn-primary"
                                                onclick="handleFormSubmission()">Konfirmasi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="accordion">
                                <h5 class="card-title">
                                    Tabel Riwayat Progres
                                </h5>

                                <div class="collapse" id="historyProgress">
                                    <!-- Tabel History Progress -->
                                    <div class="table-responsive">
                                        <table class="datatables datatable" style="table-layout: responsive;">
                                            <thead class="bg-primary text-white">
                                                <tr>
                                                    <th scope="col">No</th>
                                                    <th scope="col">Tindak Lanjut</th>
                                                    <th scope="col">Jadwal Pengecekan</th>
                                                    <th scope="col">Penanggung Jawab</th>
                                                    <th scope="col">Tanggal Jatuh Tempo</th>
                                                    <th scope="col">Unggahan Data</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Catatan</th>
                                                    <th scope="col">Modifikasi Terakhir</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($formperbaikan->tindaklanjuts as $tindaklanjut)
                                                    <tr>
                                                        <td>{{ ++$i }}</td>
                                                        <td>{{ $tindaklanjut->tindak_lanjut }}</td>
                                                        <td>{{ $tindaklanjut->schedule_pengecekan }}</td>
                                                        <td>{{ $tindaklanjut->pic }}</td>
                                                        <td>{{ $tindaklanjut->due_date }}</td>
                                                        <td>
                                                            @if ($tindaklanjut->attachment_file)
                                                                @php
                                                                    $fileName = basename(
                                                                        $tindaklanjut->attachment_file,
                                                                    );
                                                                    $buttonClass = $tindaklanjut->getAttachmentButtonClass();
                                                                    $buttonIcon = $tindaklanjut->getAttachmentButtonIcon();
                                                                @endphp
                                                                <a href="{{ route('download.attachment', $tindaklanjut) }}"
                                                                    target="_blank" class="{{ $buttonClass }}">
                                                                    <i class="{{ $buttonIcon }}"></i>
                                                                    {{ $fileName }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">N/A</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div
                                                                style="background-color: {{ $tindaklanjut->status_background_color }};
                                            border-radius: 5px; /* Rounded corners */
                                            padding: 5px 10px; /* Padding inside the div */
                                            color: white; /* Text color, adjust as needed */
                                            font-weight: bold; /* Bold text */
                                            text-align: center; /* Center-align text */
                                            text-transform: uppercase; /* Uppercase text */
                                            ">
                                                                {{ $tindaklanjut->ubahtext() }}
                                                            </div>
                                                        </td>
                                                        </td>
                                                        <td>
                                                            <div
                                                                style="background-color: {{ $tindaklanjut->note_background_color }};
                        border-radius: 5px; /* Rounded corners */
                        padding: 5px 10px; /* Padding inside the div */
                        color: black; /* Text color, adjust as needed */
                        font-weight: bold; /* Bold text */
                        text-align: center; /* Center-align text */
                        text-transform: uppercase; /* Uppercase text */
                        ">
                                                                {{ $tindaklanjut->note }}
                                                            </div>
                                                        </td>
                                                        <td>{{ $tindaklanjut->updated_at }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
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

    </main>
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var gambarPreview = document.getElementById('gambarPreview');
        var modal = document.getElementById("imageModal");
        var modalImg = document.getElementById("img01");

        function toggleImageModal() {
            if (modal.style.display === "block") {
                closeImageModal();
            } else {
                modal.style.display = "block";
                modalImg.src = gambarPreview.src;
            }
        }

        function closeImageModal() {
            modal.style.display = "none";
        }

        // Menambahkan event listener untuk menutup modal saat tombol "X" diklik
        var closeButton = document.querySelector(".close");
        closeButton.addEventListener('click', function() {
            closeImageModal();
        });

        // Menambahkan event listener untuk menutup modal saat gambar pratinjau diklik kembali
        gambarPreview.addEventListener('click', function() {
            toggleImageModal();
        });

        // Menambahkan event listener untuk menutup modal saat area di luar modal diklik
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                closeImageModal();
            }
        });
    });
</script>

<style>
    /* CSS untuk modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
        align-items: center;
        /* Mengatur penempatan vertikal ke tengah */
        justify-content: center;
        /* Mengatur penempatan horizontal ke tengah */
    }

    /* Konten dalam modal */
    .modal-content {
        max-width: 80%;
        max-height: 80%;
        background-color: white;
        padding: 20px;
        border-radius: 4px;
        position: relative;
    }

    /* Tombol close */
    .close {
        position: absolute;
        top: 10px;
        right: 10px;
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
    }

    /* CSS untuk gambar di dalam modal */
    .modal-content img {
        width: 100%;
        height: auto;
        display: block;
        margin: 0 auto;
    }
</style>
<script>
    $(document).ready(function() {
        $('.datatable').DataTable();
    });
</script>
<script>
    function handleFormSubmission(event) {
        // Display SweetAlert confirmation
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin mengubah status menjadi On Progress?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Tidak'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('confirmed_finish5').value = '1';
                // If confirmed, submit the form
                if (event) {
                    event.preventDefault(); // Prevent the default form submission
                }
                // Display success notification and execute additional code after clicking "OK"
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Status berhasil diubah menjadi On Progress.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    document.getElementById('FPPForm').submit();

                    // Additional code to execute after clicking "OK"
                    console.log('Additional code executed after clicking OK');
                    // You can add more code or redirect to another page here
                });
            }
        });
    }
</script>
