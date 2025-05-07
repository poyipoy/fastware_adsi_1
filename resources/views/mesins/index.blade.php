@extends('layout')
@section('content')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.20.0/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">


<main id="main" class="main">

    <div class="pagetitle">
        <h1>Dept.Head Maintenance</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Engineering</li>
                <li class="breadcrumb-item active">Data Mesin</li>
            </ol>
        </nav>

    </div><!-- End Page Title -->
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Data mesin</h5>
                        <div class="text-right mb-3">
                            <a class="btn btn-success float-right" href="{{ route('mesins.create') }}">
                                <i class="bi bi-plus"></i> Tambah Mesin
                            </a>
                        </div>

                        <!-- Table with stripped rows -->
                        <div class="table-responsive">
                            <table class="datatables datatable" style="table-layout: responsive;">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Section</th>
                                        <th scope="col">Tipe</th>
                                        <th scope="col">No Mesin</th>
                                        <th scope="col">Tanggal Dibuat</th>
                                        <th scope="col">Umur</th>
                                        <th scope="col">Spesifikasi</th>
                                        <th scope="col">Lokasi</th>
                                        <th scope="col">Created At</th>
                                        <th scope="col">Last Update</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mesins as $mesin)
                                    <tr>
                                        <td>{{ ++$i }}</td>
                                        <td>{{ $mesin->section }}</td>
                                        <td>{{ $mesin->tipe }}</td>
                                        <td>{{ $mesin->no_mesin }}</td>
                                        <td>{{ $mesin->tanggal_dibuat }}</td>
                                        <td>{{ $mesin->umur }}</td>
                                        <td>{{ $mesin->spesifikasi }}</td>
                                        <td>{{ $mesin->lokasi }}</td>
                                        <td>{{ $mesin->created_at }}</td>
                                        <td>{{ $mesin->updated_at }}</td>
                                        <td>
                                            <a class="btn btn-primary" href="{{ route('mesins.edit', $mesin->id) }}">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a class="btn btn-warning" href="{{ route('mesins.show', $mesin->id) }}">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" onclick="deletemesin({{$mesin->id}})">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                            <form id="mesinForm_{{ $mesin->id }}" action="{{ route('mesins.destroy', $mesin->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
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


</main><!-- End #main -->
@endsection

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
<script>
    function deletemesin(id) {
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
                    text: "Data mesin Berhasil dihapus.",
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
                            document.getElementById('mesinForm_' + id).submit();
                        }
                    });
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire({
                    title: "Gagal",
                    text: "Data mesin gagal dihapus",
                    icon: "error"
                });
            }
        });
    }
</script>
