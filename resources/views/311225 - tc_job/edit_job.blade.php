@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Job Position</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu Edit Job Position</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Edit Job Position</h5>
                            <a href="{{ route('jobShow') }}" class="btn-close" aria-label="Close"></a>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('jobPositions.update', $jobPosition->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label for="job_position">Job Position</label>
                                    <input type="text" id="job_position" class="form-control" name="job_position"
                                        value="{{ $jobPosition->job_position }}" required>
                                    <input type="hidden" id="id_job_position" class="form-control" name="job_position_id"
                                        value="{{ $jobPosition->id }}">
                                </div>


                                <div id="dynamicRowsContainer" class="mt-3">
                                    @foreach ($relatedUsers as $user)
                                        <div class="row dynamic-row mb-2" data-job-position-id="{{ $jobPosition->id }}"
                                            data-job-position-name="{{ $jobPosition->job_position }}">
                                            <div class="col-md-10">
                                                <div class="form-group">
                                                    <label>Nama Karyawan</label>
                                                    <input type="text" class="form-control user-search"
                                                        placeholder="Search user...">
                                                    <select class="form-control user-dropdown" name="id_user[]">
                                                        @foreach ($allUsers as $allUser)
                                                            <option value="{{ $allUser->id }}"
                                                                data-job-position-ids="{{ json_encode($jobPositionIds) }}"
                                                                {{ $user->id == $allUser->id ? 'selected' : '' }}>
                                                                {{ $allUser->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-center">
                                                <button type="button" class="btn btn-danger"
                                                    onclick="removeField(this)">-</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="d-flex justify-content-end mt-2">
                                    <button type="button" class="btn btn-secondary" id="addRowBtn">
                                        <i class="fas fa-plus"></i> Tambah Baris
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                    <a href="{{ route('jobShow') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </a>
                                </div>
                            </form>
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
        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const addRowBtn = document.getElementById('addRowBtn');
                const dynamicRowsContainer = document.getElementById('dynamicRowsContainer');

                // Add Row Functionality
                addRowBtn.addEventListener('click', function() {
                    const newRow = `
                        <div class="row dynamic-row mb-2">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label>User</label>
                                    <input type="text" class="form-control user-search" placeholder="Search user...">
                                    <select class="form-control user-dropdown" name="id_user[]">
                                        @foreach ($allUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <button type="button" class="btn btn-danger btn-sm removeRowBtn">-</button>
                            </div>
                        </div>
                    `;
                    dynamicRowsContainer.insertAdjacentHTML('beforeend', newRow);
                });
                // Search Functionality for Each Dropdown
                dynamicRowsContainer.addEventListener('input', function(e) {
                    if (e.target.classList.contains('user-search')) {
                        const searchInput = e.target.value.toLowerCase();
                        const dropdown = e.target.nextElementSibling;
                        const options = dropdown.querySelectorAll('option');

                        options.forEach(option => {
                            if (option.text.toLowerCase().includes(searchInput)) {
                                option.style.display = '';
                            } else {
                                option.style.display = 'none';
                            }
                        });
                    }
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form');

                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Mencegah submit form secara default

                    const formData = new FormData(form); // Mengambil data form

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content');

                    fetch("{{ route('jobPositions.update', $jobPosition->id) }}", {
                            method: 'POST', // Menggunakan metode POST karena AJAX request biasanya di-handle dengan POST
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-HTTP-Method-Override': 'PUT' // Menggunakan HTTP override untuk metode PUT
                            }
                        })
                        .then(response => {
                            if (response.ok) {
                                // Jika update berhasil, langsung redirect ke halaman jobShow
                                window.location.href = "{{ route('jobShow') }}";
                            } else {
                                // Jika ada error, tampilkan pesan error
                                return response.json().then(data => {
                                    alert('Error: ' + data.message);
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                });
            });

            function removeField(button) {
                const row = button.closest('.dynamic-row');
                const jobPositionId = row.getAttribute('data-job-position-name');
                const userId = row.querySelector('.user-dropdown').value; // Ambil id_user dari elemen dropdown

                if (jobPositionId && userId) {
                    // Gunakan SweetAlert untuk konfirmasi
                    Swal.fire({
                        title: 'Konfirmasi',
                        text: `Apakah Anda yakin ingin menghapus id_user pada job position ini?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const url = `{{ route('jobPositions.deleteRow') }}`;

                            fetch(url, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                            .getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        jobPositionId: jobPositionId, // Kirim job_position ID
                                        userId: userId // Kirim id_user
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        row.remove(); // Hapus baris dari tampilan
                                        Swal.fire('Deleted!', 'Karyawan berhasil dihapus.', 'success').then(() => {
                                            // Redirect ke route jobShow setelah berhasil
                                            window.location.href = '{{ route('jobShow') }}';
                                        });
                                    } else {
                                        Swal.fire('Error!', data.message, 'error'); // Tampilkan pesan error
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire('Gagal!', 'Gagal menghapus data. Silakan coba lagi.',
                                        'error'); // Tampilkan pesan error
                                });
                        }
                    });
                } else {
                    row.remove(); // Jika jobPositionId atau userId tidak tersedia, hapus baris dari tampilan
                }
            }
        </script>

    </main><!-- End #main -->
@endsection
