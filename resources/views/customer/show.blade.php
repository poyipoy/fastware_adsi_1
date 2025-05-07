@extends('layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<main id="main" class="main">

    <div class="pagetitle">
        <h1>Super Admin</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Dashboard Admin</li>
                <li class="breadcrumb-item">Lihat Data Customer</li>
            </ol>
        </nav>

    </div><!-- End Page Title -->
    <section class="section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Form Lihat Data Customer</h5>

                            <form id="customerForm" action="{{ route('customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="customer_code" class="form-label">
                                        Kode Customer<span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="customer_code" name="customer_code" value="{{ $customer->customer_code }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="name_customer" class="form-label">
                                        Nama Customer <span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name_customer" name="name_customer" value="{{ $customer->name_customer }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="area" class="form-label">
                                        Area<span style="color: red;">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="area" name="area" value="{{ $customer->area }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        Email<span style="color: red;">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ $customer->email }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="no_telp" class="form-label">
                                        Nomor Telepon<span style="color: red;">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="no_telp" name="no_telp" value="{{ $customer->no_telp }}" readonly>
                                </div>

                                <div class="mb-3">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>



</main><!-- End #main -->
@endsection