@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Edit Claim Submission</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('claim.indexUser') }}">Claim Submission</a></li>
                    <li class="breadcrumb-item active">Edit Claim</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5>Form Edit Claim Submission</h5>
                    </div>

                    <div class="card-body" style="margin-top: 3%">
                        <form id="claimEditForm" action="{{ route('claim.update', $claim->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <!-- Nama PIC -->
                            <div class="mb-3">
                                <label for="pic_display" class="form-label">Nama PIC<span style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="pic_display"
                                    value="{{ $claim->modified_at ?? (auth()->user()->name ?? '-') }}" readonly>
                                <small class="text-muted">Diisi otomatis dari user login.</small>
                            </div>


                            <!-- No. PR -->
                            <div class="mb-3">
                                <label for="no_pr" class="form-label">No. PR<span style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="no_pr" name="no_pr"
                                    value="{{ $claim->no_pr }}" placeholder="Masukkan No. PR" required>
                            </div>

                            <!-- Nama Produk -->
                            <div class="mb-3">
                                <label for="nama_produk" class="form-label">Nama Produk</label>
                                <input type="text" class="form-control" id="nama_produk" name="nama_produk"
                                    value="{{ $claim->nama_produk }}" placeholder="Masukkan Nama Produk">
                            </div>


                            <!-- Submission Date -->
                            <div class="mb-3">
                                <label for="submission_date" class="form-label">Submission Date<span
                                        style="color: red;">*</span></label>
                                <div class="col-md-2">
                                <input type="date" class="form-control" id="submission_date" name="submission_date"
                                    value="{{ $claim->submission_date ? $claim->submission_date->format('Y-m-d') : '' }}"
                                    required>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Pilih Category</option>
                                    <option value="Barang Rusak (NG)" {{ $claim->category == 'Barang Rusak (NG)' ? 'selected' : '' }}>Barang Rusak (NG)</option>
                                    <option value="Tidak Sesuai Spesifikasi" {{ $claim->category == 'Tidak Sesuai Spesifikasi' ? 'selected' : '' }}>Tidak Sesuai Spesifikasi</option>
                                    <option value="Barang Salah Kirim (Item Mismatch)" {{ $claim->category == 'Barang Salah Kirim (Item Mismatch)' ? 'selected' : '' }}>Barang Salah Kirim (Item Mismatch)</option>
                                    <option value="Lainnya" {{ $claim->category == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>

                            <!-- Description of Issue -->
                            <div class="mb-3">
                                <label for="description_of_issue" class="form-label">Description of Issue<span
                                        style="color: red;">*</span></label>
                                <textarea class="form-control" id="description_of_issue" name="description_of_issue" rows="4"
                                    placeholder="Jelaskan deskripsi masalah / ketidaksesuaian barang" required>{{ $claim->description_of_issue }}</textarea>
                            </div>

                            <!-- Proposed Solution -->
                            <div class="mb-3">
                                <label for="proposed_solution" class="form-label">Proposed Solution</label>
                                <textarea class="form-control" id="proposed_solution" name="proposed_solution" rows="4"
                                    placeholder="Masukkan solusi yang diusulkan (opsional)">{{ $claim->proposed_solution }}</textarea>
                            </div>

                            <!-- File / Foto -->
                            <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                                <label for="file" class="form-label fw-bold text-primary">
                                    <i class="fas fa-camera"></i> Upload Foto / Bukti
                                </label>

                                @if ($claim->file)
                                    <div class="mb-2">
                                        <p class="fw-bold text-secondary mb-1">File saat ini:</p>
                                        <a href="{{ asset($claim->file) }}" target="_blank"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-file-alt me-1"></i> {{ $claim->file_name }}
                                        </a>
                                    </div>
                                @endif

                                <input type="file" class="form-control" id="file" name="file"
                                     accept="image/*,*/*">
                                <small class="text-muted">Pilih file baru untuk mengganti. Maksimal 10 MB.</small>
                                <div id="imagePreview" class="mt-2"></div>
                            </div>

                            <!-- Tombol Submit -->
                            <div class="d-flex justify-content-end">
                                <button id="saveButton" type="submit" class="btn btn-primary mb-4 me-3">
                                    <i class="fas fa-save"></i> Update
                                </button>
                                <a href="{{ route('claim.indexUser') }}" class="btn btn-secondary mb-4 me-2">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            $(document).ready(function() {
                $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });

            // Preview gambar
            document.getElementById('file').addEventListener('change', function(event) {
                let preview = document.getElementById('imagePreview');
                preview.innerHTML = '';
                let file = event.target.files[0];
                if (file) {
                    if (file.type.startsWith('image/')) {
                        let img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        img.style.maxWidth = '300px';
                        img.style.maxHeight = '200px';
                        img.classList.add('rounded', 'border', 'shadow-sm');
                        preview.appendChild(img);
                    } else {
                        let p = document.createElement('p');
                        p.textContent = 'File dipilih: ' + file.name;
                        p.classList.add('fw-bold', 'text-secondary');
                        preview.appendChild(p);
                    }
                }
            });

            document.getElementById('claimEditForm').addEventListener('submit', function(event) {
                var fileInput = document.getElementById('file');
                var file = fileInput.files[0];

                if (file && file.size > 10 * 1024 * 1024) {
                    event.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Ukuran File Terlalu Besar',
                        text: 'Ukuran file tidak boleh lebih dari 10 MB!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    return;
                }

                event.preventDefault();
                Swal.fire({
                    icon: 'success',
                    title: 'Data Berhasil Diperbarui',
                    showConfirmButton: false,
                    timer: 1500
                });

                setTimeout(() => {
                    this.submit();
                }, 1600);
            });
        </script>

    </main>
@endsection
