@extends('layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-qzQ9pyH1/Nkq4ysbr8yjBq44xDG/BaUkmUamJsIviGniGRC3plUSllPPe9wCJlY6k4t5IfMEO/A7R5Q2TDe2iQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />


<main id="main" class="main">

    <div class="pagetitle">
        <h1>Form Elements</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                <li class="breadcrumb-item">Forms</li>
                <li class="breadcrumb-item active">Create Event</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="accordion">
                        <div class="card">
                            <div class="card-header" id="headingTwo">
                                <h2 class="mb-0">
                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseEventForm" aria-expanded="true" aria-controls="collapseEventForm">
                                        Form Create Event
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseEventForm" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
                                <div class="card-body">
                                    <form id="eventForm" action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                                        @csrf
                                        
                                        <div class="mb-3">
                                            <label for="nama_mesin" class="form-label">
                                                Nama Mesin<span style="color: red;">*</span>
                                            </label>
                                            <select class="form-control" id="nama_mesin" name="nama_mesin">
                                                <option value="">Pilih Mesin</option>
                                                @foreach($mesins as $mesin)
                                                <option value="{{ $mesin->id }}" data-nama_mesin="{{$mesin->nama_mesin}}" data-type="{{ $mesin->type }}" data-no_mesin="{{ $mesin->no_mesin }}" data-mfg_date="{{ $mesin->mfg_date }}">
                                                    {{ $mesin->nama_mesin }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3" hidden>
                                            <label for="nama_mesin2" class="form-label">
                                                Nama Mesin 2<span style="color: red;">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="nama_mesin2" name="nama_mesin2" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="type" class="form-label">
                                                Type<span style="color: red;">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="type" name="type" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="no_mesin" class="form-label">
                                                Nomor Mesin<span style="color: red;">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="no_mesin" name="no_mesin" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="mfg_date" class="form-label">
                                                Manufacturing Date<span style="color: red;">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="mfg_date" name="mfg_date" readonly>
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

                                        <div class="mb-3">
                                            <label for="start" class="form-label">
                                                Schedule Plan<span style="color: red;">*</span>
                                            </label>
                                            <input type="datetime-local" class="form-control" id="start" name="start" required>
                                        </div>

                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                            <a href="{{ route('blokDeptMaintenance') }}" class="btn btn-danger">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
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
