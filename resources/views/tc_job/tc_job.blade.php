<!-- File ini sudah tidak digunakan lagi, karena sudah di ganti/dipecah menjadi folder mst_job_position dan user_job_position -->
@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Job Position</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Job Position</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container-fluid">
                <button class="btn btn-success rounded-pill shadow-sm mb-4 px-4 py-2" data-bs-toggle="modal" data-bs-target="#addJobPositionModal">
                    <i class="fas fa-plus me-1"></i> Add Job Position
                </button>
                
                <div class="card shadow-sm rounded-4 border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="datatable table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center">NO</th>
                                        <th scope="col">Job Position</th>
                                        <th scope="col" class="text-center">Status</th>

                                        <th scope="col" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($jobPositions as $jobPosition)
                                        <tr>
                                            <th scope="row" class="text-center">{{ $loop->iteration }}</th>
                                            <td class="fw-semibold text-secondary">{{ $jobPosition->job_position }}</td>
                                            <td class="text-center">
                                                @if ($jobPosition->status == 1)
                                                    <span class="badge rounded-pill text-bg-primary px-3 py-2 shadow-sm">Aktif</span>
                                                @else
                                                    <span class="badge rounded-pill text-bg-secondary px-3 py-2 shadow-sm">Tidak Aktif</span>
                                                @endif
                                            </td>

                                            <td class="text-center">
                                                @if ($jobPosition->status == 1)
                                                    <button type="button" class="btn btn-warning rounded-pill shadow-sm px-3 mb-1"
                                                        onclick="window.location.href='{{ route('getJobPosition', $jobPosition->id) }}'">
                                                        <i class="fas fa-pencil-alt me-1"></i> Edit
                                                    </button>

                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Job Position Modal -->
            <div class="modal fade" id="addJobPositionModal" tabindex="-1" role="dialog"
                aria-labelledby="addJobPositionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow rounded-4">
                        <div class="modal-header border-bottom-0 pb-0">
                            <h5 class="modal-title fw-bold" id="addJobPositionModalLabel">Add Job Position</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4 pt-3 pb-4">
                            <form id="jobPositionForm" action="{{ route('jobPositions.store') }}" method="POST">
                                @csrf
                                
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control shadow-none" id="job_position" name="job_position" placeholder="Job Position" required>
                                    <label for="job_position">Job Position <span class="text-danger">*</span></label>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input class="form-control shadow-none" list="departmentOptions" id="department" name="department" placeholder="Department" required>
                                            <label for="department">Department <span class="text-danger">*</span></label>
                                            <datalist id="departmentOptions">
                                                @foreach($departments as $dept)
                                                    <option value="{{ $dept }}">
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input class="form-control shadow-none" list="sectionOptions" type="text" id="section" name="section" placeholder="Section" required>
                                            <label for="section">Section <span class="text-danger">*</span></label>
                                            <datalist id="sectionOptions">
                                                @foreach($sections as $sec)
                                                    <option value="{{ $sec }}">
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-select shadow-none" id="section_head_name" name="section_head_name" required>
                                                <option value="" disabled selected>Pilih Section Head...</option>
                                                @foreach($jobPositions as $jp)
                                                    <option value="{{ $jp->job_position }}">{{ $jp->job_position }}</option>
                                                @endforeach
                                            </select>
                                            <label for="section_head_name">Section Head <span class="text-danger">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-select shadow-none" id="department_head_name" name="department_head_name" required>
                                                <option value="" disabled selected>Pilih Dept Head...</option>
                                                @foreach($jobPositions as $jp)
                                                    <option value="{{ $jp->job_position }}">{{ $jp->job_position }}</option>
                                                @endforeach
                                            </select>
                                            <label for="department_head_name">Department Head <span class="text-danger">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <select class="form-select shadow-none" id="div_head_name" name="div_head_name">
                                                <option value="" selected>Tidak Ada (Opsional)</option>
                                                @foreach($jobPositions as $jp)
                                                    <option value="{{ $jp->job_position }}">{{ $jp->job_position }}</option>
                                                @endforeach
                                            </select>
                                            <label for="div_head_name">Div Head (Opsional)</label>
                                        </div>
                                    </div>
                                </div>

                                <datalist id="userOptions">
                                    @foreach($users as $user)
                                        <option value="{{ $user->name }}">
                                    @endforeach
                                </datalist>

                                <hr class="border-secondary opacity-10 my-4">

                                <h6 class="fw-semibold mb-3">User Mapping</h6>
                                
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control shadow-none" id="userSearch" placeholder="Search user...">
                                    <label for="userSearch"><i class="fas fa-search text-muted me-1"></i> Cari User</label>
                                </div>

                                <div id="dynamicRowsContainer">
                                    <div class="row dynamic-row mb-2">
                                        <div class="col-12">
                                            <select class="form-select shadow-none py-3" name="id_user[]">
                                                <option value="" disabled selected>Pilih User...</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mb-4 mt-2">
                                    <button type="button" class="btn btn-link text-decoration-none btn-sm px-0" id="addRowBtn">
                                        <i class="fas fa-plus"></i> Tambah User
                                    </button>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <button type="button" class="btn btn-light shadow-sm" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary shadow-sm px-4">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @push('scripts')
            <!-- jQuery -->
            <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            {{-- excel --}}
            <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
            <!-- SimpleDataTables JS -->
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
            document.addEventListener('DOMContentLoaded', function() {
                const searchBox = document.getElementById('userSearch');

                searchBox.addEventListener('keyup', function() {
                    const searchQuery = searchBox.value.toLowerCase();
                    const dropdowns = document.querySelectorAll('select[name="id_user[]"]');

                    dropdowns.forEach(dropdown => {
                        const options = dropdown.querySelectorAll('option');

                        // Reset all options visibility before applying the filter
                        options.forEach(option => {
                            option.style.display = '';
                        });

                        // Apply the filter to the current dropdown
                        options.forEach(option => {
                            if (option.value) { // Skip placeholder option if any
                                const optionText = option.textContent.toLowerCase();
                                option.style.display = optionText.includes(searchQuery) ? '' :
                                    'none';
                            }
                        });
                    });
                });

                // Handle the Add Row functionality
                document.getElementById('addRowBtn').addEventListener('click', function() {
                    const dynamicRowsContainer = document.getElementById('dynamicRowsContainer');
                    const newRow = document.createElement('div');
                    newRow.classList.add('row', 'dynamic-row');

                    newRow.innerHTML = `
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>User</label>
                                <select class="form-control" name="id_user[]">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    `;

                    dynamicRowsContainer.appendChild(newRow);
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                // Pass the route URL from Blade to JavaScript
                const editJobPositionUrl = `{{ route('getJobPosition', ['id' => ':id']) }}`;

                function loadEditModalData(jobPositionId) {
                    // Replace the placeholder with the actual jobPositionId
                    const url = editJobPositionUrl.replace(':id', jobPositionId);

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                return;
                            }

                            const {
                                jobPosition,
                                relatedUsers
                            } = data;
                            const modal = document.getElementById(`editJobPositionModal-${jobPositionId}`);

                            // Populate job position input
                            modal.querySelector('input[name="job_position"]').value = jobPosition.job_position;
                            if (modal.querySelector('input[name="department"]')) {
                                modal.querySelector('input[name="department"]').value = jobPosition.department || '';
                            }

                            // Clear and repopulate users dropdown
                            const dynamicRowsContainer = modal.querySelector(
                                `#dynamicRowsContainer-${jobPositionId}`);
                            dynamicRowsContainer.innerHTML = ''; // Clear existing rows

                            relatedUsers.forEach(user => {
                                const rowHtml = createDynamicRow(relatedUsers, user.id);
                                dynamicRowsContainer.insertAdjacentHTML('beforeend', rowHtml);
                            });

                            // Add functionality for adding new rows
                            const addRowButton = modal.querySelector(`#addRowBtn-${jobPositionId}`);
                            addRowButton.addEventListener('click', function() {
                                const newRow = createDynamicRow(relatedUsers);
                                dynamicRowsContainer.insertAdjacentHTML('beforeend', newRow);
                            });
                        })
                        .catch(error => console.error('Error fetching job position data:', error));
                }

                // Function to create a dynamic row with user options
                function createDynamicRow(relatedUsers, selectedUserId = null) {
                    const userOptions = relatedUsers.map(user => `
            <option value="${user.id}" ${user.id == selectedUserId ? 'selected' : ''}>
                ${user.name}
            </option>
        `).join('');

                    return `
            <div class="row dynamic-row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>User</label>
                        <select class="form-control" name="id_user[]">
                            ${userOptions}
                        </select>
                    </div>
                </div>
            </div>
        `;
                }

                // Attach event listeners to all edit buttons
                document.querySelectorAll('[data-edit-job-position]').forEach(button => {
                    button.addEventListener('click', function() {
                        const jobPositionId = this.dataset.editJobPosition;
                        loadEditModalData(jobPositionId);
                    });
                });
            });


            function confirmDelete(jobPositionId) {
                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Anda Tidak Dapat Aktifkan Kembali Data Ini!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Iya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit the form to delete the job position
                        document.getElementById(`delete-form-${jobPositionId}`).submit();
                    }
                });
            }

            // [1] Approve Job Position
            function approveJobPosition(jobPositionId) {
                Swal.fire({
                    title: 'Approve Posisi?',
                    text: "Anda akan menyetujui posisi ini untuk diteruskan ke tahap berikutnya.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/job-positions/${jobPositionId}/approve`,
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Berhasil!', response.message, 'success')
                                        .then(() => location.reload());
                                } else {
                                    Swal.fire('Gagal!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan sistem.';
                                Swal.fire('Gagal!', msg, 'error');
                            }
                        });
                    }
                });
            }

            // [1] Dynamic Approver Loading (berbasis posisi)
            $(document).ready(function() {
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
        @endpush

    </main><!-- End #main -->
@endsection
