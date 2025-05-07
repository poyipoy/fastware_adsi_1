@extends('layout')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Dept.Head Maintenance</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Tabel Preventif</li>
                    <li class="breadcrumb-item active">Tambah Jadwal Preventif</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Buat Jadwal Preventif</h5>
                                <form id="preventiveForm" method="POST" action="{{ route('preventives.store') }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="mesin" class="form-label">Pilih Mesin<span
                                                style="color: red;">*</span></label>
                                        <select class="form-control" id="mesin" name="mesin">
                                            <option value="">Pilih Mesin</option>
                                            @foreach ($mesins as $mesin)
                                                <option value="{{ $mesin->no_mesin }}" data-tipe="{{ $mesin->tipe }}">
                                                    {{ $mesin->section }} | {{ $mesin->no_mesin }} | {{ $mesin->tipe }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tipe" class="form-label">Tipe<span
                                                style="color: red;">*</span></label>
                                        <input type="text" class="form-control" id="tipe" name="tipe" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="jadwal_rencana" class="form-label">Jadwal Rencana<span
                                                style="color: red;">*</span></label>
                                        <input type="date" class="form-control" id="jadwal_rencana"
                                            name="jadwal_rencana">
                                    </div>
                                    <!-- Input issue -->
                                    <div id="input-container">
                                        <!-- Input awal issue -->
                                        <label for="issues[]" class="form-label">Isu<span
                                                style="color: red;">*</span></label>
                                        @foreach ($issues as $key => $issue)
                                            <div class="mb-3">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="issue[]"
                                                        value="{{ $issue }}">
                                                    <button type="button" class="btn btn-danger delete-input"
                                                        onclick="removeInput(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <!-- Tombol Tambah Isu -->
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-primary" onclick="addInput()">
                                            <i class="bi bi-plus"></i> Tambah Isu
                                        </button>
                                    </div>
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                        <a href="{{ route('dashboardPreventive') }}" class="btn btn-danger">Batal</a>
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
            function addInput() {
                const inputContainer = document.getElementById('input-container');
                const newInput = document.createElement('div');
                newInput.classList.add('mb-3');
                newInput.innerHTML = `
                    <div class="input-group">
                        <input type="text" class="form-control" name="issue[]">
                        <button type="button" class="btn btn-danger" onclick="removeInput(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                inputContainer.appendChild(newInput);
            }

            function removeInput(button) {
                const inputGroup = button.closest('.input-group');
                inputGroup.parentNode.remove();
            }

            function resetForm() {
                document.getElementById('preventiveForm').reset();
                document.getElementById('input-container').innerHTML = ''; // Clear dynamically added inputs
            }

            function validateForm() {
                const issues = document.getElementsByName('issue[]');
                const issueValues = Array.from(issues).map(issue => issue.value.trim());

                // Check if any issue field is empty
                const isEmpty = issueValues.some(value => value === '');
                if (isEmpty) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Peringatan!!!',
                        text: 'Mohon isi semua input sebelum mengirimkan formulir.',
                    });
                    return false; // Prevent form submission
                }

                // Check for duplicate issue values
                const isDuplicate = issueValues.some((value, index) => issueValues.indexOf(value) !== index);
                if (isDuplicate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Peringatan!!!',
                        text: 'Terdapat isian isu yang sama. Silakan periksa kembali!',
                    });
                    return false; // Prevent form submission
                }

                // Check if there are no inputs added
                const inputCount = issues.length;
                if (inputCount === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Harap isi semua inputan.',
                    });
                    return false; // Prevent form submission
                }

                return true; // Allow form submission
            }

            document.getElementById('preventiveForm').addEventListener('submit', function(event) {
                if (!validateForm()) {
                    event.preventDefault(); // Prevent form submission if validation fails
                } else {
                    // Show success notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Form Jadwal Preventif berhasil dibuat',
                    }).then(() => {
                        document.getElementById('preventiveForm')
                            .submit(); // Submit the form after showing the notification
                    });

                    event.preventDefault(); // Prevent the default form submission to show the success notification
                }
            });

            document.getElementById('mesin').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const tipe = selectedOption.getAttribute('data-tipe');
                document.getElementById('tipe').value = tipe;
            });
        </script>







    </main><!-- End #main -->


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ambil elemen-elemen yang diperlukan
            var MesinSelect = document.getElementById('mesin');
            var tipeInput = document.getElementById('tipe');
            // Tambahkan event listener untuk perubahan pada pilihan nama_mesin
            MesinSelect.addEventListener('change', function() {
                // Ambil opsi yang dipilih
                var selectedOption = MesinSelect.options[MesinSelect.selectedIndex];

                // Set nilai type, no_mesin, dan mfg_date sesuai data yang dipilih
                tipeInput.value = selectedOption.getAttribute('data-tipe');
            });
        });
    </script>
@endsection
