@extends('layout')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Karyawan</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">List Safety Patrol</li>
                    <li class="breadcrumb-item active">Tambah Safety Patrol</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Form Tambah Safety Patrol</h5>

                                <form id="patrolForm" action="{{ route('patrols.simpanPatrol') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="tanggal_patrol" class="form-label">
                                            Tanggal Patrol<span style="color: red;">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="tanggal_patrol"
                                            name="tanggal_patrol">
                                    </div>

                                    <div class="mb-3">
                                        <label for="area_patrol" class="form-label">
                                            Area Patrol<span style="color: red;">*</span>
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="heat_treatment"
                                                name="area_patrol" value="HEAT TREATMENT" required>
                                            <label class="form-check-label" for="heat_treatment">
                                                HEAT TREATMENT
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="cutting_productions"
                                                name="area_patrol" value="CUTTING PRODUCTIONS" required>
                                            <label class="form-check-label" for="cutting_productions">
                                                CUTTING PRODUCTIONS
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="machining_custom"
                                                name="area_patrol" value="MACHINING CUSTOM" required>
                                            <label class="form-check-label" for="machining_custom">
                                                MACHINING CUSTOM
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="machining" name="area_patrol"
                                                value="MACHINING" required>
                                            <label class="form-check-label" for="machining">
                                                MACHINING
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="delivery" name="area_patrol"
                                                value="DELIVERY" required>
                                            <label class="form-check-label" for="delivery">
                                                DELIVERY
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="maintenance"
                                                name="area_patrol" value="MAINTENANCE" required>
                                            <label class="form-check-label" for="maintenance">
                                                MAINTENANCE
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="warehouse" name="area_patrol"
                                                value="WAREHOUSE" required>
                                            <label class="form-check-label" for="warehouse">
                                                WAREHOUSE
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="pic_area" class="form-label">
                                            PIC Area<span style="color: red;">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="pic_area" name="pic_area">
                                    </div>

                                    <div class="mb-3">
                                        <label for="petugas_patrol" class="form-label">
                                            Petugas Patrol<span style="color: red;">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="petugas_patrol"
                                            name="petugas_patrol">
                                    </div>

                                    <h5><b>Kategori 5S/5R</b></h5>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Kelengkapan alat 5S/5R<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="kategori_1"
                                                        id="kategori_1{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="kategori_1{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Kerapihan area kerja<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="kategori_2"
                                                        id="kategori_2{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="kategori_2{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Kondisi Lingkungan Kerja<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="kategori_3"
                                                        id="kategori_3{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="kategori_3{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Penempatan Alat / Barang<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="kategori_4"
                                                        id="kategori_4{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="kategori_4{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Praktik 5S / 5R Oleh Pekerja<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="kategori_5"
                                                        id="kategori_5{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="kategori_5{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="kategori_catatan" class="form-label">
                                            Catatan untuk Kategori 5S/5R (Jika Ada)
                                        </label>
                                        <textarea class="form-control" id="kategori_catatan" name="kategori_catatan"></textarea>
                                    </div>

                                    <!-- Safety -->
                                    <h5><b>Safety</b></h5>
                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Checksheet APAR<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="safety_1"
                                                        id="safety_1{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="safety_1{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Penggunaan APD<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="safety_2"
                                                        id="safety_2{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="safety_2{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Potensi Bahaya<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="safety_3"
                                                        id="safety_3{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="safety_3{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Pemeliharaan APD<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="safety_4"
                                                        id="safety_4{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="safety_4{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Kelengkapan APD<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="safety_5"
                                                        id="safety_5{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="safety_5{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="safety_catatan" class="form-label">
                                            Catatan untuk Safety (Jika Ada)
                                        </label>
                                        <textarea class="form-control" id="safety_catatan" name="safety_catatan"></textarea>
                                    </div>

                                    <!-- Lingkungan -->
                                    <h5><b>Lingkungan</b></h5>
                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Pengelolaan Jenis & Kriteria Limbah<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="lingkungan_1"
                                                        id="lingkungan_1{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="lingkungan_1{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Kebersihan Lingkungan<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="lingkungan_2"
                                                        id="lingkungan_2{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="lingkungan_2{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Penyimpanan Limbah<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="lingkungan_3"
                                                        id="lingkungan_3{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="lingkungan_3{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3 radio-group">
                                        <label class="form-label">
                                            Tempat Sampah<span style="color: red;">*</span>
                                        </label>
                                        <div class="radio-group-inline">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="lingkungan_4"
                                                        id="lingkungan_4{{ $i }}" value="{{ $i }}"
                                                        required>
                                                    <label class="form-check-label"
                                                        for="lingkungan_4{{ $i }}">{{ $i }}</label>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="lingkungan_catatan" class="form-label">
                                            Catatan untuk Lingkungan (Jika Ada)
                                        </label>
                                        <textarea class="form-control" id="lingkungan_catatan" name="lingkungan_catatan"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary"
                                            onclick="showSuccessAlert()">Submit</button>
                                        <a href="{{ route('listpatrol') }}" class="btn btn-danger">Cancel</a>
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

        <style>
            .radio-group-inline {
                display: flex;
                flex-wrap: wrap;
            }

            .form-check {
                margin-right: 20px;
                /* Atur jarak antara radio button */
            }
        </style>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


        <!-- Di bagian body -->
        <script>
            // Function to display Sweet Alert
            function showSuccessAlert(event) {
                event.preventDefault(); // Prevent form from submitting immediately

                var isValid = true;

                // Validation for tanggal patrol
                var tanggalPatrol = document.getElementById("tanggal_patrol").value;
                if (tanggalPatrol === "") {
                    isValid = false;
                }

                // Validation for area patrol
                var areaPatrol = document.querySelector('input[name="area_patrol"]:checked');
                if (areaPatrol === null) {
                    isValid = false;
                }

                // Validation for PIC Area
                var picArea = document.getElementById("pic_area").value;
                if (picArea === "") {
                    isValid = false;
                }

                // Validation for Petugas Patrol
                var petugasPatrol = document.getElementById("petugas_patrol").value;
                if (petugasPatrol === "") {
                    isValid = false;
                }

                // Validation for Kategori 5S/5R
                var kategori1 = document.querySelector('input[name="kategori_1"]:checked');
                var kategori2 = document.querySelector('input[name="kategori_2"]:checked');
                var kategori3 = document.querySelector('input[name="kategori_3"]:checked');
                var kategori4 = document.querySelector('input[name="kategori_4"]:checked');
                var kategori5 = document.querySelector('input[name="kategori_5"]:checked');
                if (kategori1 === null || kategori2 === null || kategori3 === null || kategori4 === null || kategori5 ===
                    null) {
                    isValid = false;
                }

                // Validation for Safety
                var safety1 = document.querySelector('input[name="safety_1"]:checked');
                var safety2 = document.querySelector('input[name="safety_2"]:checked');
                var safety3 = document.querySelector('input[name="safety_3"]:checked');
                var safety4 = document.querySelector('input[name="safety_4"]:checked');
                var safety5 = document.querySelector('input[name="safety_5"]:checked');

                if (safety1 === null || safety2 === null || safety3 === null || safety4 === null || safety5 === null) {
                    isValid = false;
                }

                // Validation for Lingkungan
                var lingkungan1 = document.querySelector('input[name="lingkungan_1"]:checked');
                var lingkungan2 = document.querySelector('input[name="lingkungan_2"]:checked');
                var lingkungan3 = document.querySelector('input[name="lingkungan_3"]:checked');
                var lingkungan4 = document.querySelector('input[name="lingkungan_4"]:checked');
                if (lingkungan1 === null || lingkungan2 === null || lingkungan3 === null || lingkungan4 === null) {
                    isValid = false;
                }

                if (isValid) {
                    Swal.fire({
                        title: "Berhasil!",
                        text: "Form Safety Patrol berhasil disimpan!",
                        icon: "success",
                        showCancelButton: false,
                    }).then(() => {
                        document.getElementById("patrolForm").submit();
                    });
                } else {
                    showErrorAlert();
                }
            }

            function showErrorAlert() {
                Swal.fire("Perhatian!", "Harap isi semua kolom yang diperlukan!", "warning");
            }

            // Attach the event listener to the form's submit button
            document.querySelector('button[type="submit"]').addEventListener('click', showSuccessAlert);
        </script>

    </main><!-- End #main -->
@endsection
