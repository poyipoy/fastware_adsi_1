@extends('layout')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<main id="main" class="main">

    <div class="pagetitle">
        <h1>Dept.Head Maintenance</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Tabel Preventif</li>
                <li class="breadcrumb-item active">Lihat Jadwal Preventif</li>
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

                            <form id="preventiveForm" method="POST" action="{{ route('preventives.updateIssue', $preventive->id) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="mesin" class="form-label">Pilih Mesin<span style="color: red;">*</span></label>
                                    <select class="form-control" id="mesin" name="mesin" disabled>
                                        <option value="">Pilih Mesin</option>
                                        @foreach($mesins as $mesin)
                                        <option value="{{ $mesin->no_mesin }}" data-tipe="{{ $mesin->tipe }}" {{ $selected_mesin_nomor == $mesin->no_mesin ? 'selected' : '' }}>
                                            {{ $mesin->section }} | {{ $mesin->no_mesin }} | {{ $mesin->tipe }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="tipe" class="form-label">
                                        Type<span style="color: red;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="tipe" name="tipe" value="{{$preventive->tipe}}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="jadwal_rencana" class="form-label">
                                        Schedule Plan<span style="color: red;">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="jadwal_rencana" name="jadwal_rencana" value="{{$preventive->jadwal_rencana}}" readonly>

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
                                                <input type="checkbox" name="checked[]" value="{{ $key }}" @if($checkedIssues[$key]==1) checked @endif disabled>
                                            </span>
                                            <input type="text" class="form-control" name="issue[]" value="{{ $issue }}" readonly>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <input type="hidden" name="confirmed_event" id="confirmed_event" value='0'>

                                <div class="mb-3">
                                    <a href="{{route('dashboardPreventive') }}" class="btn btn-primary">Cancel</a>
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
        var MesinSelect = document.getElementById('mesin');
        var jadwalRencanaInput = document.getElementById('jadwal_rencana');
        var tipeInput = document.getElementById('tipe');
        // Tambahkan event listener untuk perubahan pada pilihan nama_mesin
        MesinSelect.addEventListener('change', function() {
            // Ambil opsi yang dipilih
            var selectedOption = MesinSelect.options[MesinSelect.selectedIndex];

            // Set nilai type, no_mesin, dan mfg_date sesuai data yang dipilih
            jadwalRencanaInput.value = selectedOption.getAttribute('data-jadwalRencana');
            tipeInput.value = selectedOption.getAttribute('data-tipe');
        });
    });
</script>

<script>
    function handleFinishButtonClick() {
        // Check if all issues are checked
        var allChecked = true;
        var issueCheckboxes = document.querySelectorAll('input[name="checked[]"]');
        issueCheckboxes.forEach(function(checkbox) {
            if (!checkbox.checked) {
                allChecked = false;
            }
        });

        // Show SweetAlert confirmation if all issues are checked and end date is not empty
        if (allChecked) {
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
                    document.getElementById('confirmed_event').value = '1';;

                    // Show success notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Status berhasil diubah!',
                        showConfirmButton: false,
                        timer: 1500, // Durasi notifikasi dalam milidetik
                        didClose: () => {
                            // Submit the form after the success notification is closed
                            document.getElementById('preventiveForm').submit();
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
