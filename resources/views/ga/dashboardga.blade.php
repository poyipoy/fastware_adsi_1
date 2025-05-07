@extends('layout')

@section('content')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<main id="main" class="main">

    <div class="pagetitle">
        <h1>Maintenance</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Received FPP & Jadwal Preventif</li>
                <li class="breadcrumb-item">Data Received FPP</li>
            </ol>
        </nav>

    </div><!-- End Page Title -->
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Data Received FPP</h5>
                        <div class="table-responsive">
                            <table id="" class="display" style="table-layout: fixed;">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Mesin</th>
                                        <th scope="col">Section</th>
                                        <th scope="col">Lokasi</th>
                                        <th scope="col">Kendala</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Tanggal Dibuat</th>
                                        <th scope="col">Last Update</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($formperbaikans as $formperbaikan)
                                    <tr>
                                        <td>{{ ++$i }}</td>
                                        <td>{{ $formperbaikan->mesin }}</td>
                                        <td>{{ $formperbaikan->section }}</td>
                                        <td>{{ $formperbaikan->lokasi }}</td>
                                        <td>{{ $formperbaikan->kendala }}</td>
                                        <td>
                                            <div style="background-color: {{ $formperbaikan->status_background_color }};
                                            border-radius: 5px; /* Rounded corners */
                                            padding: 5px 10px; /* Padding inside the div */
                                            color: white; /* Text color, adjust as needed */
                                            font-weight: bold; /* Bold text */
                                            text-align: center; /* Center-align text */
                                            text-transform: uppercase; /* Uppercase text */
                                            ">
                                                {{ $formperbaikan->ubahtext() }}
                                            </div>
                                        </td>

                                        <td>{{ $formperbaikan->created_at }}</td>
                                        <td>{{ $formperbaikan->updated_at }}</td>
                                        <td>
                                            <a class="btn btn-warning" href="{{ route('sales.lihat', $formperbaikan->id) }}">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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


</main><!-- End #main -->
@endsection
<script>
    function deleteSurat(id) {
        Swal.fire({
            title: 'Apakah Anda Yakin?',
            text: "Data yang dihapus tidak bisa dipakai kembali!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // If user clicks "Ya, hapus!" button, perform the deletion
                Swal.fire({
                    title: "Berhasil!",
                    text: "Nomor Surat Berhasil dihapus.",
                    icon: "success",
                    timer: 1000, // Penundaan (delay) sebelum mengeksekusi aksi berikutnya (dalam milidetik)
                    showConfirmButton: false // Menyembunyikan tombol OK
                }).then(() => {
                    // Add a button "OK" after the confirmation
                    Swal.fire({
                        title: "Info",
                        text: "Data berhasil dihapus!",
                        icon: "info",
                        showConfirmButton: true,
                        confirmButtonText: "OK"
                    }).then((okResult) => {
                        // Check if the user clicked "OK"
                        if (okResult.isConfirmed) {
                            // Submit the form
                            document.getElementById('suratForm_' + id).submit();
                        }
                    });
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire({
                    title: "Gagal",
                    text: "Nomor Surat gagal dihapus",
                    icon: "error"
                });
            }
        });
    }
</script>
