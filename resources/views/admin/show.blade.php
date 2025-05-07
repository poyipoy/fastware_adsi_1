@extends('layout')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Super Admin</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Dashboard Admin</li>
                    <li class="breadcrumb-item active">Lihat Data Pengguna</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Form Lihat Data Pengguna</h5>

                                <form id="userForm" action="{{ route('users.update', $user->id) }}" method="POST"
                                    enctype="multipart/form-data">

                                    <div class="mb-3">
                                        <label for="role_id" class="form-label">Role Pengguna<span
                                                style="color: red;">*</span></label>
                                        <select class="form-control" id="role_id" name="role_id" disabled>
                                            <option value="">Pilih Role</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    {{ $user->role_id == $role->id ? 'selected' : '' }}>{{ $role->role }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nama Pengguna<span
                                                style="color: red;">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ $user->name }}" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="npk" class="form-label">NPK<span
                                                style="color: red;">*</span></label>
                                        <input type="text" class="form-control" id="npk" name="npk"
                                            value="{{ $user->npk }}" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username<span
                                                style="color: red;">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username"
                                            value="{{ $user->username }}" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="pass" class="form-label">Password<span
                                                style="color: red;">*</span></label>
                                        <input type="text" class="form-control" id="pass" name="pass"
                                            value="{{ $user->pass }}" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email<span
                                                style="color: red;">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="{{ $user->email }}" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="telp" class="form-label">Nomor Telepon<span
                                                style="color: red;">*</span></label>
                                        <input type="number" class="form-control" id="telp" name="telp"
                                            value="{{ $user->telp }}" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <a href="{{ route('users.index') }}" class="btn btn-danger">Cancel</a>
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
