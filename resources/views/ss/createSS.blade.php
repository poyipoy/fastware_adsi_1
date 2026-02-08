@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Form SS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Form SS</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Table View SS</h5>
                            <br>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#sumbangSaranModal"><i class="fas fa-plus"></i> Tambah</button>
                            <div class="row g-3 align-items-end mb-3 mt-2">
                                <div class="col-md-3">
                                    <label for="filterStartDate" class="form-label">Tanggal Dari</label>
                                    <input type="date" id="filterStartDate" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label for="filterEndDate" class="form-label">Tanggal Sampai</label>
                                    <input type="date" id="filterEndDate" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label for="filterRole" class="form-label">Role/Bagian</label>
                                    <select id="filterRole" class="form-select">
                                        <option value="">Semua Role</option>
                                        @foreach (($roles ?? []) as $role)
                                            <option value="{{ $role }}">{{ $role }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex gap-2">
                                    <button type="button" class="btn btn-primary flex-grow-1" id="applyFilters">Terapkan</button>
                                    <button type="button" class="btn btn-secondary flex-grow-1" id="resetFilters">Reset</button>
                                </div>
                            </div>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                <table id="sumbangSaranTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="30px">NO</th>
                                            <th class="text-center" width="100px">Nama</th>
                                            <th class="text-center" width="40px">NPK</th>
                                            <th class="text-center" width="100px">Bagian</th>
                                            <th class="text-center" width="120px">Departemen</th>
                                            <th class="text-center" width="100px">Plant</th>
                                            <th class="text-center" width="100px">Judul Ide</th>
                                            <th class="text-center" width="100px">Poin</th>
                                            <th class="text-center" width="100px">+poin</th>
                                            <th class="text-center" width="100px">Nilai</th>
                                            <th class="text-center" width="100px">amount</th>
                                            <th class="text-center" width="90px">Tanggal Pengajuan Ide</th>
                                            <th class="text-center" width="100px">Lokasi</th>
                                            <th class="text-center" width="100px">Tanggal Diterapkan</th>
                                            <th class="text-center" width="100px">Pembaruan Terakhir</th>
                                            <th class="text-center" width="150px">Status</th>
                                            <th class="text-center" width="140px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <!-- End Table with stripped rows -->
                        </div>
                    </div>
                </div>
            </div>
            {{-- Add SS --}}
            <div class="modal fade" id="sumbangSaranModal" tabindex="-1" aria-labelledby="sumbangSaranModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 90%;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="sumbangSaranModalLabel">Form Tambah SS</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="sumbangSaranForm" enctype="multipart/form-data">
                                @csrf
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-2 col-form-label">Nama<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="hidden" id="id_user" name="id_user"
                                            value="{{ Auth::user()->id }}">
                                        <input type="text" class="form-control"
                                            placeholder="{{ Auth::user()->name }}" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-2 col-form-label">NPK<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="npk" name="npk"
                                            placeholder="{{ Auth::user()->npk }}" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputDate" class="col-sm-2 col-form-label">Tgl. Pengajuan Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" id="tgl_pengajuan_ide"
                                            name="tgl_pengajuan_ide" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editLokasiIde" class="col-sm-2 col-form-label">Plant<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <select class="form-control" id="plant" name="plant" required>
                                            <option value=""> ----- Pilih Plant -----</option>
                                            <option value="DS8">DS8</option>
                                            <option value="Deltamas">Deltamas</option>
                                            <option value="Tangerang">Tangerang</option>
                                            <option value="Semarang">Semarang</option>
                                            <option value="Surabaya">Surabaya</option>
                                            <option value="Bandung">Bandung</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-2 col-form-label">Lokasi Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="lokasi_ide" name="lokasi_ide"
                                            required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputDate" class="col-sm-2 col-form-label">Tgl. Diterapkan</label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" id="tgl_diterapkan"
                                            name="tgl_diterapkan">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-2 col-form-label">Judul Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="judul" name="judul"
                                            required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputPassword" class="col-sm-2 col-form-label">Keadaan Sebelumnya
                                        (Permasalahan) <span style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="keadaan_sebelumnya" name="keadaan_sebelumnya" required></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputNumber" class="col-sm-2 col-form-label">File Upload (Sebelum) <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input class="form-control" type="file" id="image" name="image"
                                            accept="*/*" required>
                                        <div id="image-preview" style="display:none; margin-top: 10px;"></div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputPassword" class="col-sm-2 col-form-label">Usulan Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="usulan_ide" name="usulan_ide" required></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputNumber" class="col-sm-2 col-form-label">File Upload (Sesudah)<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input class="form-control" type="file" id="image_2" name="image_2"
                                            accept="*/*" required>
                                        <div id="image_2-preview" style="display:none; margin-top: 10px;"></div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputPassword" class="col-sm-2 col-form-label">Keuntungan Dari Penerapan
                                        Ide <span style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="keuntungan_ide" name="keuntungan_ide" required></textarea>
                                    </div>
                                </div>
                            </form>

                            <!-- Modal for Image Popup -->
                            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="imageModalLabel">Preview Gambar</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <img id="modalImage" src="" alt="Image Preview" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="submitForm()">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="editSumbangSaranModal" tabindex="-1"
                aria-labelledby="editSumbangSaranModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 90%;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editSumbangSaranModalLabel">Form Edit SS</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form Edit Sumbang Saran -->
                            <form id="editSumbangSaranForm" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" id="editSumbangSaranId" name="id">
                                <div class="row mb-3">
                                    <label for="editLokasiIde" class="col-sm-2 col-form-label">Nama<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="editnama" name="nama"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editLokasiIde" class="col-sm-2 col-form-label">Npk<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="editnpk" name="npk"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editTglPengajuan" class="col-sm-2 col-form-label">Tgl. pengajuan Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" id="editTglPengajuan"
                                            name="tgl_pengajuan_ide" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editPlant" class="col-sm-2 col-form-label">Plant<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <select class="form-control" id="editPlant" name="plant" required>
                                            <option value="">----- Pilih Plant -----</option>
                                            <option value="DS8">DS8</option>
                                            <option value="Deltamas">Deltamas</option>
                                            <option value="Tangerang">Tangerang</option>
                                            <option value="Semarang">Semarang</option>
                                            <option value="Surabaya">Surabaya</option>
                                            <option value="Bandung">Bandung</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editLokasiIde" class="col-sm-2 col-form-label">Lokasi Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="editLokasiIde" name="lokasi_ide"
                                            required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editTglDiterapkan" class="col-sm-2 col-form-label">Tgl. Diterapkan</label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" id="editTglDiterapkan"
                                            name="tgl_diterapkan">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editJudulIde" class="col-sm-2 col-form-label">Judul Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="editJudulIde" name="judul"
                                            required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editKeadaanSebelumnya" class="col-sm-2 col-form-label">Keadaan Sebelumnya
                                        (Permasalahan) <span style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="editKeadaanSebelumnya" name="keadaan_sebelumnya" required></textarea>
                                    </div>
                                </div>
                                <!-- Input File Upload 1 -->
                                <div class="row mb-3">
                                    <label for="editImage" class="col-sm-2 col-form-label">File Upload (Sebelum) <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="file" class="form-control mt-2" id="editImage"
                                            name="edit_image">
                                        <img id="editImagePreview" class="img-fluid rounded mt-2" style="display: none;">
                                        <a id="editFilePreview" class="btn btn-primary mt-2" style="display: none;"
                                            target="_blank">Download</a>
                                        <span id="editFileName"></span>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="editUsulanIde" class="col-sm-2 col-form-label">Usulan Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="editUsulanIde" name="usulan_ide" required></textarea>
                                    </div>
                                </div>
                                <!-- Input File Upload 2 -->
                                <div class="row mb-3">
                                    <label for="editImage2" class="col-sm-2 col-form-label">File Upload (Sesudah) <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="file" class="form-control mt-2" id="editImage2"
                                            name="edit_image_2">
                                        <img id="editImage2Preview" class="img-fluid rounded mt-2"
                                            style="display: none;">
                                        <a id="editFile2Preview" class="btn btn-primary mt-2" style="display: none;"
                                            target="_blank">Download</a>
                                        <span id="editFile2Name"></span>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editKeuntungan" class="col-sm-2 col-form-label">Keuntungan Dari Penerapan
                                        Ide <span style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="editKeuntungan" name="keuntungan_ide"></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary"
                                        onclick="submitEditForm()">Save</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
            <!-- Modal untuk menampilkan gambar secara besar -->
            <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <img src="" class="img-fluid" alt="Large Image">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editImageModal" tabindex="-1" role="dialog"
                aria-labelledby="editImageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <img src="" class="img-fluid" alt="Large Image">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Readonly Modal Form View Sumbang Saran -->
            <div class="modal fade" id="viewSumbangSaranModal" tabindex="-1"
                aria-labelledby="viewSumbangSaranModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 90%;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewSumbangSaranModalLabel">Form View SS</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form View Sumbang Saran -->
                            <form id="viewSumbangSaranForm" enctype="multipart/form-data">
                                @csrf
                                <div class="row mb-3">
                                    <label for="editLokasiIde" class="col-sm-2 col-form-label">Nama<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="viewname" name="nama"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="editLokasiIde" class="col-sm-2 col-form-label">Npk<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="viewnpk" name="npk"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewTglPengajuan" class="col-sm-2 col-form-label">Tgl. pengajuan Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" id="viewTglPengajuan"
                                            name="tgl_pengajuan_ide" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewPlant" class="col-sm-2 col-form-label">Plant<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <select class="form-control" id="viewPlant" name="plant" disabled required>
                                            <option value="">----- Pilih Plant -----</option>
                                            <option value="DS8">DS8</option>
                                            <option value="Deltamas">Deltamas</option>
                                            <option value="Tangerang">Tangerang</option>
                                            <option value="Semarang">Semarang</option>
                                            <option value="Surabaya">Surabaya</option>
                                            <option value="Bandung">Bandung</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewLokasiIde" class="col-sm-2 col-form-label">Lokasi Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="viewLokasiIde" name="lokasi_ide"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewTglDiterapkan" class="col-sm-2 col-form-label">Tgl. Diterapkan<span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" id="viewTglDiterapkan"
                                            name="tgl_diterapkan" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewJudulIde" class="col-sm-2 col-form-label">Judul Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="viewJudulIde" name="judul"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewKeadaanSebelumnya" class="col-sm-2 col-form-label">Keadaan Sebelumnya
                                        (Permasalahan) <span style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="viewKeadaanSebelumnya" name="keadaan_sebelumnya" disabled></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewImage" class="col-sm-2 col-form-label">File Upload
                                        (Sebelumnya)</label>
                                    <div class="col-sm-10">
                                        <div id="view-image-preview" style="margin-top: 10px;"></div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewUsulanIde" class="col-sm-2 col-form-label">Usulan Ide <span
                                            style="color: red;">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="viewUsulanIde" name="usulan_ide" disabled></textarea>
                                    </div>
                                </div>
                                <!-- Input File Upload 2 -->
                                <div class="row mb-3">
                                    <label for="viewImage2" class="col-sm-2 col-form-label">File Upload (Sesudah)</label>
                                    <div class="col-sm-10">
                                        <div id="view-image2-preview" style="margin-top: 10px;"></div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="viewKeuntungan" class="col-sm-2 col-form-label">Keuntungan Dari Penerapan
                                        Ide</label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" style="height: 100px" id="viewKeuntungan" name="keuntungan_ide" disabled></textarea>
                                    </div>
                                </div>
                                <input type="hidden" id="viewSumbangSaranId" name="id">
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="viewImageModal" tabindex="-1" aria-labelledby="viewImageModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewImageModalLabel">Gambar</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="viewModalImage" src="" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </main><!-- End #main -->
@endsection

@section('scripts')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>
    <script>
        (function($) {
            'use strict';

            function initNavHover() {
                const dropdowns = $('.nav-item.dropdown');

                if (!dropdowns.length) {
                    return;
                }

                dropdowns.hover(
                    function() {
                        $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                    },
                    function() {
                        $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                    }
                );
            }

            function previewFile(input, previewElementId) {
                const previewElement = document.getElementById(previewElementId);

                if (!previewElement || !input || !input.files || !input.files[0]) {
                    return;
                }

                const file = input.files[0];
                const reader = new FileReader();

                reader.addEventListener('load', function() {
                    let content = '';

                    if (file.type.startsWith('image/')) {
                        content =
                            `<img src="${reader.result}" alt="File Preview" style="max-height: 200px; cursor: pointer;" onclick="openImageModal('${reader.result}')">`;
                    } else {
                        content = `<a href="${reader.result}" download="${file.name}">Download ${file.name}</a>`;
                    }

                    previewElement.innerHTML = content;
                    previewElement.style.display = 'block';
                });

                reader.readAsDataURL(file);
            }

            function registerFilePreviewHandlers() {
                const firstImage = document.getElementById('image');
                const secondImage = document.getElementById('image_2');

                if (firstImage) {
                    firstImage.addEventListener('change', function() {
                        previewFile(this, 'image-preview');
                    });
                }

                if (secondImage) {
                    secondImage.addEventListener('change', function() {
                        previewFile(this, 'image_2-preview');
                    });
                }
            }

            function initMagnific() {
                if (!$.fn.magnificPopup) {
                    return;
                }

                $('.image-popup').magnificPopup({
                    type: 'image',
                    closeOnContentClick: true,
                    gallery: { enabled: true },
                    zoom: {
                        enabled: true,
                        duration: 300,
                        easing: 'ease-in-out',
                    },
                });
            }

            function initDataTable() {
                if (!$.fn.DataTable) {
                    console.error('DataTables plugin is not loaded.');
                    return null;
                }

                const table = $('#sumbangSaranTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('showSS.data') }}',
                        data: function(data) {
                            data.start_date = $('#filterStartDate').val();
                            data.end_date = $('#filterEndDate').val();
                            data.role = $('#filterRole').val();
                        },
                    },
                    order: [[14, 'desc']],
                    columns: [
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle',
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            },
                        },
                        { data: 'name', name: 'name', className: 'text-center align-middle' },
                        { data: 'npk', name: 'npk', className: 'text-center align-middle' },
                        { data: 'bagian', name: 'bagian', className: 'text-center align-middle' },
                        { data: 'department', name: 'department', className: 'text-center align-middle' },
                        { data: 'plant', name: 'plant', className: 'text-center align-middle' },
                        { data: 'judul', name: 'judul', className: 'text-center align-middle' },
                        { data: 'nilai', name: 'nilai', className: 'text-center align-middle' },
                        { data: 'tambahan_nilai', name: 'tambahan_nilai', className: 'text-center align-middle' },
                        {
                            data: 'total_nilai',
                            name: 'total_nilai',
                            orderable: false,
                            className: 'text-center align-middle',
                        },
                        {
                            data: 'hasil_akhir',
                            name: 'hasil_akhir',
                            orderable: false,
                            className: 'text-center align-middle',
                            render: function(data, type, row) {
                                if (type === 'display' || type === 'filter') {
                                    return row.hasil_akhir_formatted;
                                }

                                return data ?? 0;
                            },
                        },
                        { data: 'tgl_pengajuan_ide', name: 'tgl_pengajuan_ide', className: 'text-center align-middle' },
                        { data: 'lokasi_ide', name: 'lokasi_ide', className: 'text-center align-middle' },
                        { data: 'tgl_diterapkan', name: 'tgl_diterapkan', className: 'text-center align-middle' },
                        { data: 'updated_at', name: 'updated_at', className: 'text-center align-middle' },
                        {
                            data: 'status_badge',
                            name: 'status_badge',
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle',
                            render: function(data) {
                                return data;
                            },
                        },
                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle',
                            render: function(data) {
                                return data;
                            },
                        },
                    ],
                    createdRow: function(row, data) {
                        const statusCell = $('td', row).eq(15);
                        statusCell.attr('title', data.status_title || '');
                        statusCell.css({
                            'max-width': '100%',
                            overflow: 'hidden',
                            'text-overflow': 'ellipsis',
                            'white-space': 'nowrap',
                        });
                    },
                });

                return table;
            }

            window.openImageModal = function(imageSrc) {
                const modalImage = document.getElementById('modalImage');
                const modalElement = document.getElementById('imageModal');

                if (!modalImage || !modalElement) {
                    return;
                }

                modalImage.src = imageSrc;
                const imageModal = new bootstrap.Modal(modalElement);
                imageModal.show();
            };

            window.submitForm = function() {
                const form = document.getElementById('sumbangSaranForm');

                if (!form) {
                    return;
                }

                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;
                let firstInvalidField = null;

                requiredFields.forEach(function(field) {
                    if (!field.value.trim()) {
                        valid = false;
                        if (!firstInvalidField) {
                            firstInvalidField = field;
                        }
                    }
                });

                if (!valid) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Harap isi semua field yang wajib diisi.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                    }).then(() => {
                        if (firstInvalidField) {
                            firstInvalidField.focus();
                        }
                    });

                    return;
                }

                const formData = new FormData(form);

                $.ajax({
                    url: '{{ route('simpanSS') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function() {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Data berhasil disimpan.',
                            icon: 'success',
                            timer: 1000,
                            showConfirmButton: false,
                        }).then(() => {
                            $('#sumbangSaranModal').modal('hide');
                            form.reset();
                            window.location.href = '{{ route('showSS') }}';
                        });
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                        });
                    },
                });
            };

            window.showImageInModal = function(imageLink) {
                const modal = $('#editImageModal');

                if (!modal.length) {
                    return;
                }

                modal.modal('show');
                modal.find('img').attr('src', imageLink);
            };

            window.openEditModal = function(id) {
                const url = '{{ route('editSS', ['id' => ':id']) }}'.replace(':id', id);

                $.ajax({
                    url,
                    type: 'GET',
                    success: function(response) {
                        if (!response) {
                            return;
                        }

                        $('#editnama').val(response.user.name ?? '');
                        $('#editnpk').val(response.user.npk ?? '');
                        $('#editTglPengajuan').val(response.tgl_pengajuan_ide ?? '');
                        $('#editLokasiIde').val(response.lokasi_ide ?? '');
                        $('#editTglDiterapkan').val(response.tgl_diterapkan ?? '');
                        $('#editPlant').val(response.plant ?? '');
                        $('#editJudulIde').val(response.judul ?? '');
                        $('#editKeadaanSebelumnya').val(response.keadaan_sebelumnya ?? '');
                        $('#editUsulanIde').val(response.usulan_ide ?? '');
                        $('#editSumbangSaranId').val(response.id ?? '');
                        $('#editKeuntungan').val(response.keuntungan_ide ?? '');

                        const fileLink1 = response.image ? '{{ asset('assets/image/') }}/' + response.image : '';
                        const fileLink2 = response.image_2 ? '{{ asset('assets/image/') }}/' + response.image_2 : '';

                        const fileName1 = response.file_name || '';
                        const fileName2 = response.file_name_2 || '';

                        if (fileName1 && fileLink1 && ['jpg', 'jpeg', 'png'].includes(fileName1.split('.').pop().toLowerCase())) {
                            $('#editImagePreview')
                                .attr('src', fileLink1)
                                .attr('width', '150')
                                .attr('height', '150')
                                .show()
                                .off('click')
                                .on('click', function() {
                                    showImageInModal(fileLink1);
                                });
                            $('#editFilePreview').hide();
                            $('#editFileName').text('');
                        } else {
                            $('#editImagePreview').hide();
                            if (fileLink1) {
                                $('#editFilePreview').attr('href', fileLink1).attr('download', fileName1).show();
                                $('#editFileName').text(fileName1);
                            } else {
                                $('#editFilePreview').hide();
                                $('#editFileName').text('');
                            }
                        }

                        if (fileName2 && fileLink2 && ['jpg', 'jpeg', 'png'].includes(fileName2.split('.').pop().toLowerCase())) {
                            $('#editImage2Preview')
                                .attr('src', fileLink2)
                                .attr('width', '150')
                                .attr('height', '150')
                                .show()
                                .off('click')
                                .on('click', function() {
                                    showImageInModal(fileLink2);
                                });
                            $('#editFile2Preview').hide();
                            $('#editFile2Name').text('');
                        } else {
                            $('#editImage2Preview').hide();
                            if (fileLink2) {
                                $('#editFile2Preview').attr('href', fileLink2).attr('download', fileName2).show();
                                $('#editFile2Name').text(fileName2);
                            } else {
                                $('#editFile2Preview').hide();
                                $('#editFile2Name').text('');
                            }
                        }

                        $('#editSumbangSaranModal').modal('show');
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    },
                });
            };

            window.submitEditForm = function() {
                const formElement = document.getElementById('editSumbangSaranForm');
                const id = $('#editSumbangSaranId').val();

                if (!formElement || !id) {
                    return;
                }

                const formData = new FormData(formElement);

                $.ajax({
                    url: 'updateSS/' + id,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function() {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Data berhasil diperbarui.',
                            icon: 'success',
                            timer: 1000,
                            showConfirmButton: false,
                        }).then(() => {
                            $('#editSumbangSaranModal').modal('hide');
                            window.location.href = '{{ route('showSS') }}';
                        });
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    },
                });
            };

            window.confirmDelete = function(id) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Setelah dihapus, Anda tidak akan dapat memulihkan data ini!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: '{{ route('deleteSS', ['id' => ':id']) }}'.replace(':id', id),
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        success: function(data) {
                            if (data.message === 'Data berhasil dihapus') {
                                Swal.fire('Dihapus!', 'Data berhasil dihapus.', 'success').then(() => {
                                    window.location.href = '{{ route('showSS') }}';
                                });
                            } else {
                                Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                            }
                        },
                        error: function(xhr) {
                            console.error('Error:', xhr.responseText);
                            Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                        },
                    });
                });
            };

            window.confirmKirim = function(id) {
                Swal.fire({
                    title: 'Apakah data sudah benar?',
                    text: 'Setelah dikirim, Anda tidak akan dapat mengubah data ini!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, kirim!',
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: '{{ route('kirimSS', ['id' => ':id']) }}'.replace(':id', id),
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        success: function(data) {
                            if (data.message === 'Data berhasil dikirim') {
                                Swal.fire('Dikirim!', 'Data berhasil dikirim.', 'success').then(() => {
                                    window.location.href = '{{ route('showSS') }}';
                                });
                            }
                        },
                    });
                });
            };

            window.viewFormSS = function(id) {
                const url = '{{ route('sechead.show', ':id') }}'.replace(':id', id);

                $.ajax({
                    url,
                    type: 'GET',
                    success: function(response) {
                        if (!response) {
                            console.error('Tidak ada data respons');
                            return;
                        }

                        $('#viewname').val(response.user?.name ?? '');
                        $('#viewnpk').val(response.user?.npk ?? '');
                        $('#viewTglPengajuan').val(response.tgl_pengajuan_ide ?? '');
                        $('#viewPlant').val(response.plant ?? '');
                        $('#viewLokasiIde').val(response.lokasi_ide ?? '');
                        $('#viewTglDiterapkan').val(response.tgl_diterapkan ?? '');
                        $('#viewJudulIde').val(response.judul ?? '');
                        $('#viewKeadaanSebelumnya').val(response.keadaan_sebelumnya ?? '');
                        $('#viewUsulanIde').val(response.usulan_ide ?? '');
                        $('#viewKeuntungan').val(response.keuntungan_ide ?? '');
                        $('#viewSumbangSaranId').val(response.id ?? '');

                        const fileLink1 = response.image ? '{{ asset('assets/image/') }}/' + response.image : '';
                        const fileLink2 = response.image_2 ? '{{ asset('assets/image/') }}/' + response.image_2 : '';

                        if (response.file_name && fileLink1) {
                            const ext1 = response.file_name.split('.').pop().toLowerCase();
                            if (['jpg', 'jpeg', 'png'].includes(ext1)) {
                                $('#view-image-preview').html(
                                    `<img src="${fileLink1}" class="img-fluid rounded clickable-view-image" style="max-width: 200px; height: auto;" data-img-src="${fileLink1}">`
                                );
                                $('#view-image-preview img')
                                    .off('click')
                                    .on('click', function() {
                                        showImageInModal2(fileLink1, 'view');
                                    });
                            } else {
                                $('#view-image-preview').html(
                                    `<a href="${fileLink1}" download="${response.file_name}">${response.file_name}</a>`
                                );
                            }
                        } else {
                            $('#view-image-preview').html('');
                        }

                        if (response.file_name_2 && fileLink2) {
                            const ext2 = response.file_name_2.split('.').pop().toLowerCase();
                            if (['jpg', 'jpeg', 'png'].includes(ext2)) {
                                $('#view-image2-preview').html(
                                    `<img src="${fileLink2}" class="img-fluid rounded clickable-view-image" style="max-width: 200px; height: auto;" data-img-src="${fileLink2}">`
                                );
                                $('#view-image2-preview img')
                                    .off('click')
                                    .on('click', function() {
                                        showImageInModal2(fileLink2, 'view');
                                    });
                            } else {
                                $('#view-image2-preview').html(
                                    `<a href="${fileLink2}" download="${response.file_name_2}">${response.file_name_2}</a>`
                                );
                            }
                        } else {
                            $('#view-image2-preview').html('');
                        }

                        $('#viewSumbangSaranModal').modal('show');
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    },
                });
            };

            window.showImageInModal2 = function(imageLink, modalType) {
                if (modalType !== 'view') {
                    console.error('Modal type not recognized');
                    return;
                }

                $('#viewImageModal').modal('show');
                $('#viewModalImage').attr('src', imageLink);
            };

            $(function() {
                initNavHover();

                const table = initDataTable();

                if (table) {
                    $('#applyFilters').on('click', function() {
                        table.ajax.reload();
                    });

                    $('#resetFilters').on('click', function() {
                        $('#filterStartDate').val('');
                        $('#filterEndDate').val('');
                        $('#filterRole').val('');
                        table.ajax.reload();
                    });

                    table.on('draw', initMagnific);
                }

                registerFilePreviewHandlers();
                initMagnific();
            });
        })(jQuery);
    </script>
@endsection
