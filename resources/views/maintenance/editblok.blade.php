@extends('layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
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

                            <form id="eventForm" action="{{ route('events.updateIssue', $event->id) }}" method="POST" enctype="multipart/form-data">
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
                                            <span class="input-group-text">
                                                <input type="checkbox" name="checked[]" value="{{ $key }}" @if($checkedIssues[$key]==1) checked @endif>
                                            </span>
                                            <input type="text" class="form-control" name="issue[]" value="{{ $issue }}">
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
                                    <label for="end" class="form-label">
                                        Actual Plan<span style="color: red;">*</span>
                                    </label>
                                    <input type="datetime-local" class="form-control" id="end" name="end" value="{{$event->end}}">
                                </div>

                                <input type="hidden" name="confirmed_event" id="confirmed_event" value='0'>
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-secondary">Save</button>
                                    <!-- Tombol Finish yang tersembunyi -->
                                    <button type="button" class="btn btn-primary" id="finishButton" onclick="handleFinishButtonClick()">Finish</button>
                                    <a href="{{route('blokMaintanence') }}" class="btn btn-primary">Cancel</a>
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

<script>
    function handleFinishButtonClick() {
        // Validate start against current date
        var startValue = new Date(document.getElementById('start').value.replace('T', ' '));
        var endValue = new Date(document.getElementById('end').value.replace('T', ' '));
        var currentDate = new Date();

        // Check if end date is empty
        if (!document.getElementById('end').value) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Actual Plan tidak boleh kosong.'
            });
            return;
        }
        // Check if all issues are checked
        var allChecked = true;
        var issueCheckboxes = document.querySelectorAll('input[name="checked[]"]');
        issueCheckboxes.forEach(function(checkbox) {
            if (!checkbox.checked) {
                allChecked = false;
            }
        });

        // Show SweetAlert confirmation if all issues are checked and end date is not empty
        if (allChecked && document.getElementById('end').value) {
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin mengubah status menjadi Finish?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Set the value of confirmed_finish to 1 before submitting the form
                    document.getElementById('confirmed_event').value = '1';

                    // Show success notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Status berhasil diubah!',
                        showConfirmButton: false,
                        timer: 1500, // Durasi notifikasi dalam milidetik
                        didClose: () => {
                            // Submit the form after the success notification is closed
                            document.getElementById('eventForm').submit();
                        }
                    });
                }
            });
        } else if (!allChecked) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Semua issue harus dichecklist sebelum menyelesaikan acara.'
            });
        }
    }
</script>
