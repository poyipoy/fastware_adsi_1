@extends('layout')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <main id="main" class="main">

        <div class="pagetitle">
            <h1>User Maintenance</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Tabel Preventif</li>
                    <li class="breadcrumb-item active">Ubah Jadwal Preventif</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Ubah Jadwal Preventif</h5>

                                <form id="preventiveForm" method="POST"
                                    action="{{ route('preventives.updateIssue', $preventive->id) }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-3">
                                        <label for="mesin" class="form-label">Pilih Mesin<span
                                                style="color: red;">*</span></label>
                                        <select class="form-control" id="mesin" name="mesin" disabled>
                                            <option value="">Pilih Mesin</option>
                                            @foreach ($mesins as $mesin)
                                                <option value="{{ $mesin->no_mesin }}" data-tipe="{{ $mesin->tipe }}"
                                                    {{ $selected_mesin_nomor == $mesin->no_mesin ? 'selected' : '' }}>
                                                    {{ $mesin->section }} | {{ $mesin->tipe }} | {{ $mesin->no_mesin }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tipe" class="form-label">
                                            Tipe<span style="color: red;">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="tipe" name="tipe"
                                            value="{{ $preventive->tipe }}" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="jadwal_rencana" class="form-label">
                                            Jadwal Rencana<span style="color: red;">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="jadwal_rencana" name="jadwal_rencana"
                                            value="{{ $preventive->jadwal_rencana }}" readonly>

                                    </div>

                                    <div class="mb-3">
                                        <label for="jadwal_aktual" class="form-label">
                                            Jadwal Aktual <span style="color: red;">*</span>
                                        </label>
                                        <input type="date"
                                            class="form-control @error('jadwal_aktual') is-invalid @enderror"
                                            id="jadwal_aktual"
                                            name="jadwal_aktual"
                                            @php
                                                use Carbon\Carbon;
                                            @endphp

                                            value="{{ old('jadwal_aktual', $preventive->jadwal_aktual ? Carbon::parse($preventive->jadwal_aktual)->format('Y-m-d') : '') }}"

                                            required>
                                        @error('jadwal_aktual')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>


                                    <!-- Input issue -->
                                    <div id="input-container">
                                    <label class="form-label">Isu<span style="color:red;">*</span></label>

                                        @foreach ($issues as $key => $issue)
                                            <div class="mb-3">
                                                <div class="input-group">

                                                    {{-- Kotak centang di kiri --}}
                                                    <span class="input-group-text">
                                                        <input type="checkbox" name="checked[]" value="{{ $key }}"
                                                            @if ($checkedIssues[$key] == 1) checked @endif>
                                                    </span>

                                                    {{-- Input teks issue --}}
                                                    <input type="text" class="form-control" name="issue[]"
                                                        value="{{ $issue }}">

                                                    {{-- Tanggal updated_at **hanya** jika sudah dicentang --}}
                                                    @if ($checkedIssues[$key] == 1 && !empty($updatedAts[$key]))
                                                        <span class="input-group-text bg-light">
                                                            {{ \Carbon\Carbon::parse($updatedAts[$key])->format('Y-m-d') }}
                                                        </span>
                                                    @endif

                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mb-3">
                                        <label for="keterangan" class="form-label">
                                            Keterangan <span style="color:red;">*</span>
                                        </label>

                                        <textarea
                                            class="form-control @error('keterangan') is-invalid @enderror"
                                            id="keterangan"
                                            name="keterangan"
                                            rows="3"
                                            required>{{ $preventive->keterangan }}</textarea>

                                        @error('keterangan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>


                                    <input type="hidden" name="confirmed_event" id="confirmed_event" value='0'>

                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-secondary">Simpan</button>
                                        <!-- Tombol Finish dengan event onclick yang dipanggil handleFinishButtonClick() -->
                                        <button type="button" class="btn btn-primary" id="finishButton"
                                            onclick="handleFinishButtonClick()">Selesai</button>
                                        <a href="{{ route('dashboardPreventiveMaintenance') }}"
                                            class="btn btn-primary">Batal</a>
                                    </div>
                                </form>
                                {{-- Wrapper utama untuk log --}}
                                  <div class="mt-4 border rounded p-3">
                                @if($logs->isNotEmpty())
                                    @php $first = $logs->first(); @endphp

                                    <div class="mb-3">
                                        <h5>{{ $first->userprev->name ?? 'Log Aktivitas' }}</h5>
                                        <p>{{ $first->keterangan ?? '-' }}</p>
                                        <small>{{ \Carbon\Carbon::parse($first->created_at)->format('d M Y, H:i') }}</small>
                                    </div>

                                    {{-- Collapse --}}
                                    <div class="collapse" id="logCollapse">
                                        @foreach($logs->skip(1) as $log)
                                            <div class="mb-3">
                                                <h6>{{ $log->userprev->name ?? 'Log Aktivitas' }}</h6>
                                                <p>{{ $log->keterangan ?? '-' }}</p>
                                                <small>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, H:i') }}</small>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Tombol toggle --}}
                                    <button 
                                        class="btn btn-outline-secondary d-flex align-items-center" 
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#logCollapse"
                                        aria-expanded="false"
                                        aria-controls="logCollapse">
                                        <span class="me-2">Tampilkan log lain</span>
                                        <svg id="logIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                @else
                                    <p class="text-muted">Tidak ada log aktivitas.</p>
                                @endif
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
    {{-- Script Bootstrap --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const icon = document.getElementById('logIcon');
    const collapseEl = document.getElementById('logCollapse');

    collapseEl.addEventListener('show.bs.collapse', function () {
        icon.style.transform = 'rotate(180deg)';
    });

    collapseEl.addEventListener('hide.bs.collapse', function () {
        icon.style.transform = 'rotate(0deg)';
    });
});
</script>

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
        var allChecked = true;
        var issueCheckboxes = document.querySelectorAll('input[name="checked[]"]');
        issueCheckboxes.forEach(function(checkbox) {
            if (!checkbox.checked) {
                allChecked = false;
            }
        });

        if (allChecked) {
            // Tampilkan konfirmasi SweetAlert
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin mengubah status menjadi Finish?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Set nilai confirmed_event menjadi '1'
                    document.getElementById('confirmed_event').value = '1';

                    // Submit formulir
                    document.getElementById('preventiveForm').submit();
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Semua isu harus diceklis.'
            });
        }
    }
</script>
