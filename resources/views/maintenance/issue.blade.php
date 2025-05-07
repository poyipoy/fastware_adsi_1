@extends('layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<main id="main" class="main">

    <div class="pagetitle">
        <h1>Form Elements</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                <li class="breadcrumb-item">Forms</li>
                <li class="breadcrumb-item active">Edit Issue</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->
    <section class="section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Form Edit Issue {{$mesin->nama_mesin}}</h5>
                            <form id="IssueForm" action="{{ route('detailpreventives.updateIssue', $mesin->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <!-- Input issue -->
                                <div id="input-container">
                                    <!-- Input awal issue -->
                                    @foreach ($issues as $key => $issue)
                                    <div class="mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <input type="checkbox" name="checked[]" value="{{ $key }}" @if($checkedIssues[$key]==1) checked @endif>
                                            </span>
                                            <input type="text" class="form-control" name="issue[]" value="{{ $issue }}">
                                            <button type="button" class="btn btn-danger delete-input" onclick="removeInput(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <!-- Tombol Tambah Input -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary" onclick="addInput()">
                                        <i class="bi bi-plus"></i> Tambah Input
                                    </button>
                                </div>

                                <!-- Tombol Submit, Reset, dan Cancel -->
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <a href="{{ route('maintenance.dashpreventive') }}" class="btn btn-danger">Cancel</a>
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
    <script>
        function addInput() {
            const inputContainer = document.getElementById('input-container');
            const newInput = document.createElement('div');
            newInput.classList.add('mb-3');
            newInput.innerHTML = `
            <div class="input-group">
                <span class="input-group-text">
                    <input type="checkbox" name="checked[]" value=1>
                </span>
                <input type="text" class="form-control" name="issue[]">
                <button type="button" class="btn btn-danger" onclick="removeInput(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
            inputContainer.appendChild(newInput);
        }

        function removeInput(button) {
            const inputGroup = button.closest('.input-group'); // Temukan elemen input-group terdekat
            inputGroup.parentNode.remove(); // Hapus elemen input-group dari parent node
        }

        function resetForm() {
            document.getElementById('IssueForm').reset();
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
                    text: 'Terdapat isian issue yang sama. Silakan periksa kembali!',
                });
                return false; // Prevent form submission
            }

            // Check if there are no inputs added
            const inputCount = issues.length;
            if (inputCount === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Peringatan!!!',
                    text: 'Mohon tambahkan setidaknya satu input sebelum mengirimkan formulir.',
                });
                return false; // Prevent form submission
            }

            return true; // Allow form submission
        }


        document.getElementById('IssueForm').addEventListener('submit', function(event) {
            if (!validateForm()) {
                event.preventDefault(); // Prevent form submission if validation fails
            }
        });
    </script>
</main>

@endsection
