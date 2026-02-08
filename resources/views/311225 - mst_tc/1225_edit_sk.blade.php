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
                <form id="combinedForm" action="{{ route('mst_sk.updateSoftSkills', $softSkill->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card">
                        <div class="card-body">
                            <h5 style="margin-top: 3%"><b>Edit Data Soft Skills</b></h5>

                            <div class="form-group" style="margin-top: 2%">
                                <label for="job_position_sk">Job Position</label>
                                <input type="hidden" name="sk[id_job_position]" value="{{ $softSkill->id_job_position }}">
                                <select name="sk[id_job_position]" id="job_position_sk" class="form-control" disabled>
                                    @foreach ($jobPositions as $position)
                                        <option value="{{ $position->id }}"
                                            {{ $position->id == $softSkill->id_job_position ? 'selected' : '' }}>
                                            {{ $position->job_position }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="fieldsContainer">
                                <button type="button" class="btn btn-success" style="margin-top: 2%" onclick="addField()">+
                                    Tambah Baris</button>
                                @foreach ($sameJobPositionData as $data)
                                    <div class="form-row" style="margin-top: 2%">
                                        <div class="form-group">
                                            <label for="keterangan_sk_{{ $data->id }}">Soft Skills</label>
                                            <input type="text" name="sk[keterangan_sk][]"
                                                id="keterangan_sk_{{ $data->id }}" class="form-control"
                                                value="{{ $data->keterangan_sk }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="deskripsi_sk_{{ $data->id }}">Deskripsi</label>
                                            <input type="text" name="sk[deskripsi_sk][]"
                                                id="deskripsi_sk_{{ $data->id }}" class="form-control"
                                                value="{{ $data->deskripsi_sk }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="id_poin_kategori_sk_{{ $data->id }}">Kategori Nilai</label>
                                            <select name="sk[id_poin_kategori][]"
                                                id="id_poin_kategori_sk_{{ $data->id }}" class="form-control">
                                                <option value="">---- Pilih Kategori Nilai ----</option>
                                                <option value="1"
                                                    {{ $data->id_poin_kategori == 1 ? 'selected' : '' }}>Skill of Process
                                                    Plant</option>
                                                <option value="2"
                                                    {{ $data->id_poin_kategori == 2 ? 'selected' : '' }}>Skill of Process
                                                    Office & Quality</option>
                                                <option value="3"
                                                    {{ $data->id_poin_kategori == 3 ? 'selected' : '' }}>Skill of EHS
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="nilai_{{ $data->id }}">Nilai</label>
                                            <select name="sk[nilai][]" id="nilai_{{ $data->id }}" class="form-control">
                                                @foreach (range(1, 4) as $nilai)
                                                    <option value="{{ $nilai }}"
                                                        {{ $data->nilai == $nilai ? 'selected' : '' }}>
                                                        {{ $nilai }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-danger"
                                            onclick="removeField(this, {{ $data->id }})">-</button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-group" style="margin-top: 2%">
                                <label for="job_position_tc">Deskripsi Technical Competency, Soft Skills, dan
                                    Additional</label>
                                <div class="row">
                                    <!-- Card 1: Deskripsi Technical Competency -->
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                {{ $dataTc1->judul_keterangan }}
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <td>1.</td>
                                                            <td>{{ $dataTc1->deskripsi_1 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>2.</td>
                                                            <td>{{ $dataTc1->deskripsi_2 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>3.</td>
                                                            <td>{{ $dataTc1->deskripsi_3 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>4.</td>
                                                            <td>{{ $dataTc1->deskripsi_4 }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card 2: Deskripsi Soft Skills -->
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header bg-success text-white">
                                                {{ $dataTc2->judul_keterangan }}
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <td>1.</td>
                                                            <td>{{ $dataTc2->deskripsi_1 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>2.</td>
                                                            <td>{{ $dataTc2->deskripsi_2 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>3.</td>
                                                            <td>{{ $dataTc2->deskripsi_3 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>4.</td>
                                                            <td>{{ $dataTc2->deskripsi_4 }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card 3: Deskripsi Additional -->
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header bg-info text-white">
                                                {{ $dataTc3->judul_keterangan }}
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <td>1.</td>
                                                            <td>{{ $dataTc3->deskripsi_1 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>2.</td>
                                                            <td>{{ $dataTc3->deskripsi_2 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>3.</td>
                                                            <td>{{ $dataTc3->deskripsi_3 }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>4.</td>
                                                            <td>{{ $dataTc3->deskripsi_4 }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                    'sk': {
                        'id_job_position': formData.get('sk[id_job_position]'),
                        'keterangan_sk': [],
                        'deskripsi_sk': [],
                        'id_poin_kategori': [],
                        'nilai': []
                    },
                    '_method': 'PUT',
                    '_token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                };

                // Gather all input for keterangan_sk
                document.querySelectorAll('[name="sk[keterangan_sk][]"]').forEach(input => {
                    data.sk.keterangan_sk.push(input.value);
                });

                // Gather all input for deskripsi_sk
                document.querySelectorAll('[name="sk[deskripsi_sk][]"]').forEach(input => {
                    data.sk.deskripsi_sk.push(input.value);
                });

                // Gather all input for id_poin_kategori
                document.querySelectorAll('[name="sk[id_poin_kategori][]"]').forEach(select => {
                    data.sk.id_poin_kategori.push(select.value);
                });

                // Gather all input for nilai
                document.querySelectorAll('[name="sk[nilai][]"]').forEach(select => {
                    data.sk.nilai.push(select.value);
                });

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
                    .then(responseData => {
                        if (responseData.success) {
                            alert('Data berhasil diperbarui.');
                            window.location.href = "{{ route('tcShow') }}"; // Redirect after update
                        } else {
                            console.error('Error:', responseData.message);
                            alert('Terjadi masalah saat memperbarui data: ' + responseData.message);
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
                newFieldGroup.className = 'form-row';
                newFieldGroup.style.marginTop = '2%';
                newFieldGroup.innerHTML = `
        <div class="form-group">
            <label for="keterangan_sk_${fieldCount}">Soft Skills</label>
            <input type="text" name="sk[keterangan_sk][]"
                id="keterangan_sk_${fieldCount}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="deskripsi_sk_${fieldCount}">Deskripsi</label>
            <input type="text" name="sk[deskripsi_sk][]"
                id="deskripsi_sk_${fieldCount}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="id_poin_kategori_sk_${fieldCount}">Kategori Nilai</label>
            <select name="sk[id_poin_kategori][]"
                id="id_poin_kategori_sk_${fieldCount}" class="form-control">
                <option value="">---- Pilih Kategori Nilai ----</option>
                <option value="1">Skill of Process Plant</option>
                <option value="2">Skill of Process Office & Quality</option>
                <option value="3">Skill of EHS</option>
            </select>
        </div>
        <div class="form-group">
            <label for="nilai_${fieldCount}">Nilai</label>
            <select name="sk[nilai][]"
                id="nilai_${fieldCount}" class="form-control">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
        </div>
        <button type="button" class="btn btn-danger" onclick="removeField(this)">-</button>
    `;
                container.appendChild(newFieldGroup);
                fieldCount++;
            }

            function removeField(button, id = null) {
                const fieldGroup = button.parentElement;

                if (id) {
                    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                        // Gunakan route helper untuk menghasilkan URL penghapusan
                        const url = `{{ route('sk.deleteRow', ['id' => '__ID__']) }}`.replace('__ID__', id);

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
                                    window.location.href = `{{ route('mst_tc.edit', $softSkill->id) }}`;
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
