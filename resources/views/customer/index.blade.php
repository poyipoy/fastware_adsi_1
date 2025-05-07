@extends('layout')
@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.20.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">


    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Super Admin</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Dashboard Admin</li>
                    <li class="breadcrumb-item">Daftar Pelanggan</li>
                </ol>
            </nav>

        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Daftar Customer</h5>
                            <div class="text-right mb-3">
                                <a class="btn btn-success float-right" href="{{ route('customers.create') }}">
                                    <i class="bi bi-plus"></i> Tambah Pelanggan
                                </a>
                            </div>

                            <!-- Table with stripped rows -->
                            <div class="table-responsive">
                                <table class="datatables datatable" style="table-layout: responsive;">
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">Customer Code</th>
                                            <th scope="col">Nama Customer</th>
                                            <th scope="col">Area</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Nomor Telepon</th>
                                            <th scope="col">Created At</th>
                                            <th scope="col">Updated At</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($customers as $customer)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>{{ $customer->customer_code }}</td>
                                                <td>{{ $customer->name_customer }}</td>
                                                <td>{{ $customer->area }}</td>
                                                <td>{{ $customer->email }}</td>
                                                <td>{{ $customer->no_telp }}</td>
                                                <td>{{ $customer->created_at }}</td>
                                                <td>{{ $customer->updated_at }}</td>
                                                <td>
                                                    <a class="btn btn-primary"
                                                        href="{{ route('customers.edit', $customer->id) }}">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </a>
                                                    <a class="btn btn-warning"
                                                        href="{{ route('customers.show', $customer->id) }}">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger"
                                                        onclick="deletecustomer({{ $customer->id }})">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                    <form id="customerForm_{{ $customer->id }}"
                                                        action="{{ route('customers.destroy', $customer->id) }}"
                                                        method="POST" style="display: none;">
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
    function deletecustomer(id) {
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
                // If customer clicks "Ya, hapus!" button, perform the deletion
                Swal.fire({
                    title: "Berhasil!",
                    text: "Nomor customer Berhasil dihapus.",
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
                        // Check if the customer clicked "OK"
                        if (okResult.isConfirmed) {
                            // Submit the form
                            document.getElementById('customerForm_' + id).submit();
                        }
                    });
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire({
                    title: "Gagal",
                    text: "Nomor customer gagal dihapus",
                    icon: "error"
                });
            }
        });
    }
</script>
