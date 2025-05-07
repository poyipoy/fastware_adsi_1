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
                <li class="breadcrumb-item active">Edit Event</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Form Update Event</h5>

                            <form id="eventForm" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="nama_mesin" class="form-label">
                                        Nama Mesin<span style="color: red;">*</span>
                                    </label>
                                    <select class="form-control" id="nama_mesin" name="nama_mesin" disabled>
                                        <option value="">Pilih Mesin</option>
                                        @foreach($mesins as $mesin)
                                        @if($selected_mesin_id == $mesin->id)
                                        <option value="{{ $mesin->id }}" data-nama_mesin="{{$mesin->nama_mesin}}" data-type="{{ $mesin->type }}" data-no_mesin="{{ $mesin->no_mesin }}" data-mfg_date="{{ $mesin->mfg_date }}" selected>
                                            {{ $mesin->nama_mesin }}
                                        </option>
                                        @else
                                        <option value="{{ $mesin->id }}" data-nama_mesin="{{$mesin->nama_mesin}}" data-type="{{ $mesin->type }}" data-no_mesin="{{ $mesin->no_mesin }}" data-mfg_date="{{ $mesin->mfg_date }}">
                                            {{ $mesin->nama_mesin }}
                                        </option>
                                        @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3" hidden>
                                    <label for="nama_mesin2" class="form-label">
                                        Nama Mesin 2<span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nama_mesin2" name="nama_mesin2" value="{{$mesin->nama_mesin2}}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="type" class="form-label">
                                        Type<span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="type" name="type" value="{{$mesin->type}}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="no_mesin" class="form-label">
                                        Nomor Mesin<span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="no_mesin" name="no_mesin" value="{{$mesin->no_mesin}}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="mfg_date" class="form-label">
                                        Manufacturing Date<span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="mfg_date" name="mfg_date" value="{{$mesin->mfg_date}}" readonly>
                                </div>

                                <!-- Input issue -->
                                <div id="input-container">
                                    <!-- Input awal issue -->
                                    <label for="issues[]" class="form-label">
                                        Issue<span style="color: red;">*</span>
                                    </label>
                                    @foreach ($issues as $key => $issue)
                                    <div class="mb-3">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="issue[]" value="{{ $issue }}" disabled>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="mb-3">
                                    <label for="start" class="form-label">
                                        Schedule Plan<span style="color: red;">*</span>
                                    </label>
                                    <input type="datetime-local" class="form-control" id="start" name="start" value="{{$event->start}}" readonly>
                                </div>

                                <div class="mb-3">
                                    <a href="{{ route('blokDeptMaintenance') }}" class="btn btn-primary">Cancel</a>
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

</main><!-- End #main -->
@endsection

<script>
    function deleteEvent(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Data that is deleted cannot be recovered!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the delete form
                document.getElementById('deleteEventForm').submit();
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire(
                    'Cancelled',
                    'Your data is safe :)',
                    'info'
                );
            }
        });
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Menangani perubahan nilai pada input End Date
        document.getElementById('end').addEventListener('change', function() {
            // Mendapatkan nilai Start Date
            var startDateValue = new Date(document.getElementById('start').value.replace('T', ' '));
            // Mendapatkan nilai End Date
            var endDateValue = new Date(this.value.replace('T', ' '));

            // Membandingkan nilai End Date dengan Start Date
            if (endDateValue < startDateValue) {
                // Menampilkan pesan kesalahan menggunakan SweetAlert
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'End Date tidak boleh lebih kecil dari Start Date',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    // Set nilai End Date kembali ke nilai Start Date
                    this.value = document.getElementById('start').value;
                });
            }
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
        } else {
            // Jika validasi berhasil, tambahkan isian issue ke dalam textarea di form kedua
            const issues = document.getElementsByName('issue[]');
            const issueValues = Array.from(issues).map(issue => issue.value.trim());
            const combinedIssues = issueValues.join('\n');
            document.getElementById('issue').value = combinedIssues;
        }
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil elemen-elemen yang diperlukan
        var namaMesinSelect = document.getElementById('nama_mesin');
        var namaMesin2Input = document.getElementById('nama_mesin2');
        var typeInput = document.getElementById('type');
        var noMesinInput = document.getElementById('no_mesin');
        var mfgDateInput = document.getElementById('mfg_date');

        // Tambahkan event listener untuk perubahan pada pilihan nama_mesin
        namaMesinSelect.addEventListener('change', function() {
            // Ambil opsi yang dipilih
            var selectedOption = namaMesinSelect.options[namaMesinSelect.selectedIndex];

            // Set nilai type, no_mesin, dan mfg_date sesuai data yang dipilih
            namaMesin2Input.value = selectedOption.getAttribute('data-nama_mesin');
            typeInput.value = selectedOption.getAttribute('data-type');
            noMesinInput.value = selectedOption.getAttribute('data-no_mesin');
            mfgDateInput.value = selectedOption.getAttribute('data-mfg_date');
        });
    });
</script>
