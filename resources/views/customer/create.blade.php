@extends('layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<main id="main" class="main">

    <div class="pagetitle">
        <h1>Super Admin</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Dashboard Admin</li>
                <li class="breadcrumb-item">Tambah List Customer</li>
            </ol>
        </nav>

    </div><!-- End Page Title -->

    <section class="section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Form Customer</h5>

                            <form id="customerForm" action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="customer_code" class="form-label">
                                        Kode Customer<span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="customer_code" name="customer_code">
                                </div>

                                <div class="mb-3">
                                    <label for="name_customer" class="form-label">
                                        Nama Customer <span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name_customer" name="name_customer">
                                </div>

                                <div class="mb-3">
                                    <label for="area" class="form-label">
                                        Area<span style="color: red;">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="area" name="area">
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        Email<span style="color: red;">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>

                                <div class="mb-3">
                                    <label for="no_telp" class="form-label">
                                        Nomor Telepon<span style="color: red;">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="no_telp" name="no_telp">
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <a href="{{ route('customers.index') }}" class="btn btn-danger">Cancel</a>
                                </div>
                            </form>
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

    <script>
        function resetForm() {
            document.getElementById('customerForm').reset();
        }
    </script>
    <script>
        function handleFormSubmission() {
            // Mendapatkan nilai input dari formulir
            var customer_code = document.getElementById('customer_code').value.trim();
            var name_customer = document.getElementById('name_customer').value.trim();
            var area = document.getElementById('area').value.trim();
            // var email = document.getElementById('email').value.trim();
            // var no_telp = document.getElementById('no_telp').value.trim();

            // Melakukan validasi input
            if (customer_code === '' || name_customer === '' || area === '') {
                // Menampilkan SweetAlert jika ada isian yang kosong
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Harap lengkapi semua isian yang diperlukan.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return false; // Menghentikan pengiriman formulir karena ada isian yang kosong
            }

            // Jika semua isian sudah terisi, lanjutkan pengiriman formulir
            document.getElementById('customerForm').submit();
        }

        // Event listener untuk submit form
        document.getElementById('customerForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah pengiriman formulir secara default

            // Memanggil fungsi untuk menangani pengiriman formulir dan menampilkan SweetAlert
            handleFormSubmission();
        });
    </script>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</main><!-- End #main -->

@endsection