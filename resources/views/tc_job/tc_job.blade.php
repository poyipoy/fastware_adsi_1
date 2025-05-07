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
            <div class="container">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addJobPositionModal">Add Job
                    Position</button>
                <table class="datatable table">
                    <thead>
                        <tr>
                            <th scope="col">NO</th>
                            <th scope="col">Job Position</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jobPositions as $jobPosition)
                            <tr>
                                <th scope="row">{{ $loop->iteration }}</th>
                                <td>{{ $jobPosition->job_position }}</td>
                                <td>
                                    @if ($jobPosition->status == 1)
                                        <span class="badge rounded-pill bg-primary">Aktif</span>
                                    @else
                                        <span class="badge rounded-pill bg-success">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($jobPosition->status == 1)
                                        <!-- Example button for opening edit modal -->
                                        <button type="button" class="btn btn-warning"
                                            onclick="window.location.href='{{ route('getJobPosition', $jobPosition->id) }}'">
                                            <i class="fas fa-pencil-alt"></i>
                                            Edit
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Add Job Position Modal -->
            <div class="modal fade" id="addJobPositionModal" tabindex="-1" role="dialog"
                aria-labelledby="addJobPositionModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addJobPositionModalLabel">Add Job Position</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="jobPositionForm" action="{{ route('jobPositions.store') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Job Position</label>
                                    <input type="text" class="form-control" id="job_position" name="job_position"
                                        placeholder="Contoh Isi (Sales Admin)" required>
                                </div>

                                <!-- Search User (outside dynamic rows) -->
                                <div class="form-group">
                                    <label for="userSearch">Search User:</label>
                                    <input type="text" class="form-control" id="userSearch"
                                        placeholder="Type to search...">
                                </div>

                                <!-- Container for dynamic rows -->
                                <div id="dynamicRowsContainer">
                                    <!-- Initial row -->
                                    <div class="row dynamic-row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>User</label>
                                                <select class="form-control" name="id_user[]">
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">
                                                            {{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-12 d-flex justify-content-end">
                                        <button type="button" class="btn btn-secondary" id="addRowBtn">+ Add Row</button>
                                    </div>
                                </div>

                                <div class="modal-footer mt-3">
                                    <button type="submit" class="btn btn-primary">Save changes</button>
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

        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>

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
        </script>

    </main><!-- End #main -->
@endsection
