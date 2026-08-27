@extends('layout')

@section('content')
    <main id="main" class="main">
        <style>
            .form-row {
                display: flex;
                align-items: flex-start;
                margin-bottom: 10px;
            }

            .form-row>div {
                flex: 1;
                margin-right: 10px;
            }

            .form-row>div:last-child {
                margin-right: 0;
            }

            .form-row .btn {
                margin-top: 30px;
            }
        </style>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Edit Data Competency</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container">
                <h3><b> Form Edit Data</b></h3>
                <form id="combinedForm" action="{{ route('mst_tc.update', $tc->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card">
                        <div class="card-body">
                            <h5 style="margin-top: 3%"><b>Edit Data TC</b></h5>

                            <div class="form-group" style="margin-top: 2%">
                                <label for="job_position_tc">Job Position</label>
                                <select name="tc[id_job_position]" id="job_position_tc" class="form-control">
                                    @foreach ($jobPositions as $position)
                                        <option value="{{ $position->id }}"
                                            {{ $position->id == $tc->id_job_position ? 'selected' : '' }}>
                                            {{ $position->job_position }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="fieldsContainer">
                                <button type="button" class="btn btn-success mb-3 rounded-pill shadow-sm" onclick="addField()">
                                    <i class="fas fa-plus"></i> Tambah Baris
                                </button>
                                @foreach ($sameJobPositionData as $data)
                                    <div class="row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm">
                                        <div class="col-md-11">
                                            <div class="row g-2">
                                                <div class="col-md-4 form-floating mb-2">
                                                    <input type="text" name="tc[keterangan_tc][]"
                                                        id="keterangan_tc_{{ $data->id }}" class="form-control bg-light"
                                                        value="{{ $data->keterangan_tc }}" placeholder="Technical Competency">
                                                    <label for="keterangan_tc_{{ $data->id }}">Technical Competency</label>
                                                </div>
                                                <div class="col-md-4 form-floating mb-2">
                                                    <select name="tc[id_poin_kategori][]"
                                                        id="id_poin_kategori_tc_{{ $data->id }}" class="form-select bg-light">
                                                        <option value="">---- Pilih Kategori Nilai ----</option>
                                                        <option value="1" {{ $data->id_poin_kategori == 1 ? 'selected' : '' }}>Skill of Process Plant</option>
                                                        <option value="2" {{ $data->id_poin_kategori == 2 ? 'selected' : '' }}>Skill of Process Office & Quality</option>
                                                        <option value="3" {{ $data->id_poin_kategori == 3 ? 'selected' : '' }}>Skill of EHS</option>
                                                    </select>
                                                    <label for="id_poin_kategori_tc_{{ $data->id }}">Kategori Nilai</label>
                                                </div>
                                                <div class="col-md-4 form-floating mb-2">
                                                    <select name="tc[nilai][]" id="nilai_{{ $data->id }}" class="form-select bg-light">
                                                        <option value="">-- Nilai --</option>
                                                        @foreach (range(1, 4) as $nilai)
                                                            <option value="{{ $nilai }}" {{ $data->nilai == $nilai ? 'selected' : '' }}>
                                                                {{ $nilai }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label for="nilai_{{ $data->id }}">Standar Nilai</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-1 d-flex justify-content-center">
                                            <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm"
                                                onclick="removeField(this, {{ $data->id }})">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('tcShow') }}" class="btn btn-secondary">Back</a>
                </form>
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

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            document.getElementById('combinedForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent default form submission

                const formData = new FormData(this);
                const data = {
                    'tc': {
                        'id_job_position': formData.get('tc[id_job_position]'),
                        'keterangan_tc': [],
                        'deskripsi_level_1': [],
                        'deskripsi_level_2': [],
                        'deskripsi_level_3': [],
                        'deskripsi_level_4': [],
                        'id_poin_kategori': [],
                        'nilai': []
                    },
                    '_method': 'PUT',
                    '_token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                };

                let emptyFields = false;

                // Gather all input for keterangan_tc
                document.querySelectorAll('[name="tc[keterangan_tc][]"]').forEach(input => {
                    if (input.value.trim() === '') emptyFields = true;
                    data.tc.keterangan_tc.push(input.value);
                });

                // Gather all input for id_poin_kategori
                document.querySelectorAll('[name="tc[id_poin_kategori][]"]').forEach(input => {
                    if (input.value === '') emptyFields = true;
                    data.tc.id_poin_kategori.push(input.value);
                });

                // Gather all input for nilai
                document.querySelectorAll('[name="tc[nilai][]"]').forEach(input => {
                    if (input.value === '') emptyFields = true;
                    data.tc.nilai.push(input.value);
                });

                if (emptyFields) {
                    Swal.fire('Peringatan', 'Harap isi semua field Kompetensi, Kategori, dan Nilai!', 'warning');
                    return;
                }

                console.log('Form Data:', data); // Log data being sent

                fetch(this.action, {
                        method: 'POST', // Use POST as FormData and _method are used to override with PUT
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': data._token
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Data berhasil diperbarui.');
                            window.location.href = "{{ route('tcShow') }}"; // Redirect after update
                        } else {
                            console.error('Error:', data.message);
                            alert('Terjadi masalah saat memperbarui data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });

            // Hitung jumlah baris yang sudah ada di server dan set initial value dari fieldCount
            let fieldCount = {{ count($sameJobPositionData) }};

            // Cek jumlah baris yang ada di halaman setelah dokumen dimuat
            document.addEventListener('DOMContentLoaded', function() {
                let existingFieldsCount = document.querySelectorAll('#fieldsContainer .form-row').length;

                // Jika jumlah baris yang ada di halaman lebih besar dari nilai yang dihitung dari server, update fieldCount
                if (existingFieldsCount > fieldCount) {
                    fieldCount = existingFieldsCount;
                }
            });

            function addField() {
                if (fieldCount >= 10) {
                    alert('Maksimal 10 field diperbolehkan.');
                    return;
                }

                const container = document.getElementById('fieldsContainer');
                const newFieldGroup = document.createElement('div');
                newFieldGroup.className = 'row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm mt-3';
                newFieldGroup.innerHTML = `
                    <div class="col-md-11">
                        <div class="row g-2">
                            <div class="col-md-4 form-floating mb-2">
                                <input type="text" name="tc[keterangan_tc][]" class="form-control bg-light" placeholder="Technical Competency">
                                <label>Technical Competency</label>
                            </div>
                            <div class="col-md-4 form-floating mb-2">
                                <select name="tc[id_poin_kategori][]" class="form-select bg-light">
                                    <option value="">---- Pilih Kategori ----</option>
                                    <option value="1">Skill of Process Plant</option>
                                    <option value="2">Skill of Process Office & Quality</option>
                                    <option value="3">Skill of EHS</option>
                                </select>
                                <label>Kategori Nilai</label>
                            </div>
                            <div class="col-md-4 form-floating mb-2">
                                <select name="tc[nilai][]" class="form-select bg-light">
                                    <option value="">-- Nilai --</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                                <label>Standar Nilai</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeNewField(this)">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                `;
                container.appendChild(newFieldGroup);
            }

            function removeNewField(button) {
                button.closest('.row').remove();
            };

            function removeField(button, id = null) {
                const fieldGroup = button.parentElement.parentElement;

                if (id) {
                    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                        // Gunakan route helper untuk menghasilkan URL penghapusan
                        const url = `{{ route('tc.deleteRow', ['id' => '__ID__']) }}`.replace('__ID__', id);

                        // Kirim request ke server untuk menghapus data dari database
                        fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                }
                            })
                            .then(response => {
                                if (response.ok) {
                                    return response.json();
                                } else {
                                    throw new Error('Gagal menghapus data.');
                                }
                            })
                            .then(data => {
                                if (data.success) {
                                    // Hapus baris dari tampilan jika penghapusan dari database berhasil
                                    fieldGroup.remove();
                                    fieldCount--;

                                    // Reload halaman ke rute edit setelah penghapusan
                                    window.location.href = `{{ route('mst_tc.edit', $tc->id) }}`;
                                } else {
                                    alert(data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Gagal menghapus data. Silakan coba lagi.');
                            });
                    }
                } else {
                    // Jika `id` tidak diberikan, hanya hapus baris dari tampilan
                    fieldGroup.remove();
                    fieldCount--;
                }
            }
        </script>
    </main><!-- End #main -->
@endsection
