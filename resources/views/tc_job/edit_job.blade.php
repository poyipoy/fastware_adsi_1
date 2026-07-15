<!-- File ini sudah tidak digunakan lagi, karena sudah di ganti/dipecah menjadi folder mst_job_position dan user_job_position -->
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

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" id="job_position" class="form-control" name="job_position" value="{{ $jobPosition->job_position }}" placeholder="Job Position" required>
                                            <label for="job_position">Job Position</label>
                                            <input type="hidden" id="id_job_position" class="form-control" name="job_position_id" value="{{ $jobPosition->id }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input class="form-control" list="departmentOptions" id="department" name="department" value="{{ $jobPosition->department }}" placeholder="Pilih atau ketik nama departemen..." required>
                                            <label for="department">Department</label>
                                            <datalist id="departmentOptions">
                                                @foreach($departments as $dept)
                                                    <option value="{{ $dept }}">
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <input class="form-control" list="sectionOptions" type="text" id="section" name="section" value="{{ $jobPosition->section }}" placeholder="Contoh: PDCA, Procurement, IT" required>
                                            <label for="section">Section (Mapping) <span class="text-danger">*</span></label>
                                            <datalist id="sectionOptions">
                                                @foreach($sections as $sec)
                                                    <option value="{{ $sec }}">
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-select shadow-none" id="section_head_name" name="section_head_name" required>
                                                <option value="" disabled>Pilih Section Head...</option>
                                                @foreach($jobPositions as $jp)
                                                    <option value="{{ $jp->job_position }}" {{ $jobPosition->section_head_name == $jp->job_position ? 'selected' : '' }}>{{ $jp->job_position }}</option>
                                                @endforeach
                                            </select>
                                            <label for="section_head_name">Section Head <span class="text-danger">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-select shadow-none" id="department_head_name" name="department_head_name" required>
                                                <option value="" disabled>Pilih Dept Head...</option>
                                                @foreach($jobPositions as $jp)
                                                    <option value="{{ $jp->job_position }}" {{ $jobPosition->department_head_name == $jp->job_position ? 'selected' : '' }}>{{ $jp->job_position }}</option>
                                                @endforeach
                                            </select>
                                            <label for="department_head_name">Department Head <span class="text-danger">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-select shadow-none" id="div_head_name" name="div_head_name">
                                                <option value="" {{ empty($jobPosition->div_head_name) ? 'selected' : '' }}>Tidak Ada (Opsional)</option>
                                                @foreach($jobPositions as $jp)
                                                    <option value="{{ $jp->job_position }}" {{ $jobPosition->div_head_name == $jp->job_position ? 'selected' : '' }}>{{ $jp->job_position }}</option>
                                                @endforeach
                                            </select>
                                            <label for="div_head_name">Div Head (Opsional)</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4 bg-light shadow-sm border-0">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold m-0 text-primary">Daftar Karyawan Terhubung</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                                                <i class="fas fa-plus"></i> Tambah Karyawan
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover bg-white" id="dynamicTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nama Karyawan</th>
                                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="dynamicRowsContainer">
                                                    @foreach ($relatedUsers as $user)
                                                        <tr class="dynamic-row" data-job-position-id="{{ $jobPosition->id }}" data-job-position-name="{{ $jobPosition->job_position }}">
                                                            <td>
                                                                <div class="d-flex gap-2">
                                                                    <input type="text" class="form-control user-search w-50" placeholder="Search user...">
                                                                    <select class="form-select user-dropdown w-50" name="id_user[]">
                                                                        @foreach ($allUsers as $allUser)
                                                                            <option value="{{ $allUser->id }}"
                                                                                data-job-position-ids="{{ json_encode($jobPositionIds) }}"
                                                                                {{ $user->id == $allUser->id ? 'selected' : '' }}>
                                                                                {{ $allUser->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeField(this)">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sticky Footer Actions -->
                                <div class="position-sticky bg-white p-3 shadow-lg z-3 border-top rounded-top-4 mt-5 d-flex justify-content-end gap-2" style="bottom: 60px;">
                                    <a href="{{ route('jobShow') }}" class="btn btn-secondary rounded-pill px-4">
                                        <i class="fas fa-arrow-left me-1"></i> Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                                        <i class="fas fa-save me-1"></i> Update
                                    </button>
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

                                // [1] Dynamic Approver Loading
                                $('#department, #section').on('change', function() {
                                    let dept = $('#department').val();
                                    let sec = $('#section').val();
                                    
                                    $.ajax({
                                        url: '{{ route("jobPositions.approvers") }}',
                                        type: 'GET',
                                        data: { department: dept, section: sec },
                                        success: function(res) {
                                            function populateSelect(id, data, emptyText) {
                                                let select = $(id);
                                                let currentVal = select.val();
                                                select.empty();
                                                select.append(`<option value="" selected>${emptyText}</option>`);
                                                data.forEach(pos => {
                                                    let selected = (currentVal === pos) ? 'selected' : '';
                                                    select.append(`<option value="${pos}" ${selected}>${pos}</option>`);
                                                });
                                            }
                                            
                                            populateSelect('#section_head_name', res.section_heads, 'Pilih Posisi Section Head...');
                                            populateSelect('#department_head_name', res.dept_heads, 'Pilih Posisi Dept Head...');
                                            populateSelect('#div_head_name', res.div_heads, 'Tidak Ada (Opsional)');
                                        }
                                    });
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
                        <tr class="dynamic-row">
                            <td>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control user-search w-50" placeholder="Search user...">
                                    <select class="form-select user-dropdown w-50" name="id_user[]">
                                        @foreach ($allUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-sm btn-outline-danger removeRowBtn" onclick="removeField(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
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
