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
                            <!-- Table with stripped rows -->
                            <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                <table class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="30px">NO</th>
                                            <th class="text-center" width="100px">Nama</th>
                                            <th class="text-center" width="40px">NPK</th>
                                            <th class="text-center" width="100px">Bagian</th>
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
                                    <tbody>
                                        @foreach ($data as $item)
                                            <tr>
                                                <th scope="row" class="text-center">{{ $loop->iteration }}</th>
                                                <td class="text-center py-3">{{ $item->name }}</td>
                                                <td class="text-center py-3">{{ $item->npk }}</td>
                                                <td class="text-center py-3">{{ $usersRoles[$item->id_user] ?? '' }}</td>
                                                <td class="text-center py-3">{{ $item->plant }}</td>
                                                <td class="text-center py-3">{{ $item->judul }}</td>
                                                <td class="text-center py-3" style="height: 50px;">{{ $item->nilai }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->tambahan_nilai }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ $item->total_nilai }}</td>
                                                <td class="text-center py-3" style="height: 50px;">
                                                    {{ 'Rp ' . number_format($item->hasil_akhir, 0, ',', '.') }}</td>
                                                <td class="text-center py-3">{{ $item->tgl_pengajuan_ide }}</td>
                                                <td class="text-center py-3">{{ $item->lokasi_ide }}</td>
                                                <td class="text-center py-3">{{ $item->tgl_diterapkan }}</td>
                                                <td class="text-center py-3">{{ $item->updated_at }}</td>
                                                <td class="text-center py-4"
                                                    style="max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                    title="@if ($item->status == 1) Draf @elseif ($item->status == 2) Menunggu Approve Foreman @elseif($item->status == 3) Menunggu Approve Dept. Head @elseif($item->status == 4) Direksi @endif">
                                                    @if ($item->status == 1)
                                                        <span class="badge bg-secondary align-items-center"
                                                            style="font-size: 18px;">Draf</span>
                                                    @elseif ($item->status == 2)
                                                        <span class="badge bg-warning align-items-center"
                                                            style="font-size: 18px;">Menunggu<br>Konfirmasi Sec. Head</span>
                                                    @elseif($item->status == 3)
                                                        <span class="badge bg-warning align-items-center"
                                                            style="font-size: 18px;">Menunggu<br>Konfirmasi Dept.
                                                            Head</span>
                                                    @elseif($item->status == 4)
                                                        <span class="badge bg-warning align-items-center"
                                                            style="font-size: 18px;">Menunggu<br>Konfirmasi Komite</span>
                                                    @elseif($item->status == 5)
                                                        <span class="badge bg-info align-items-center"
                                                            style="font-size: 18px;">SS sudah dinilai</span>
                                                    @elseif($item->status == 6)
                                                        <span class="badge bg-info align-items-center"
                                                            style="font-size: 18px;">SS sudah Verivikasi</span>
                                                    @elseif($item->status == 7)
                                                        <span class="badge bg-success align-items-center"
                                                            style="font-size: 18px;">SS Terbayar</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (
                                                        $item->status != 2 &&
                                                            $item->status != 3 &&
                                                            $item->status != 4 &&
                                                            $item->status != 5 &&
                                                            $item->status != 6 &&
                                                            $item->status != 7)
                                                        @if (Auth::user()->role_id != 20)
                                                            <button class="btn btn-primary btn-sm"
                                                                onclick="openEditModal({{ $item->id }})"
                                                                title="Edit">
                                                                <i class="fa-solid fa-edit fa-1x"></i>
                                                            </button>
                                                            <button class="btn btn-danger btn-sm"
                                                                onclick="confirmDelete('{{ $item->id }}')"
                                                                title="Hapus">
                                                                <i class="fas fa-trash fa-1x"></i>
                                                            </button>
                                                            <button class="btn btn-primary btn-sm"
                                                                onclick="confirmKirim({{ $item->id }})"
                                                                data-id="{{ $item->id }}" title="Kirim">
                                                                <i class="fa-solid fa fa-paper-plane fa-1x"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                    <button class="btn btn-success btn-sm" id="fetchDataButton"
                                                        onclick="viewFormSS({{ $item->id }})" title="lihat">
                                                        <i class="fa-solid fa-eye fa-1x"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
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
        
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script>
            // ADD
            function submitForm() {
                // Ambil elemen formulir
                var form = document.getElementById('sumbangSaranForm');

                // Periksa field yang required
                var requiredFields = form.querySelectorAll('[required]');
                var valid = true;
                var firstInvalidField = null;
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
                        confirmButtonText: 'OK'
                    }).then(() => {
                        if (firstInvalidField) {
                            firstInvalidField.focus();
                        }
                    });
                    return;
                }

                // Buat objek formData
                var formData = new FormData(form);

                // Kirim permintaan AJAX
                $.ajax({
                    url: '{{ route('simpanSS') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Handle sukses
                        console.log(response);
                        // Tampilkan alert sukses
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Data berhasil disimpan.',
                            icon: 'success',
                            timer: 1000, // Waktu dalam milidetik sebelum alert otomatis tertutup
                            showConfirmButton: false
                        }).then(() => {
                            // Tutup modal
                            $('#sumbangSaranModal').modal('hide');
                            // Reset formulir
                            form.reset();
                            // Redirect ke showSS
                            window.location.href = '{{ route('showSS') }}';
                        });
                    },
                    error: function(xhr, status, error) {
                        // Handle error
                        console.log(xhr.responseText);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }

            $(document).ready(function() {
                $('.image-popup').magnificPopup({
                    type: 'image',
                    closeOnContentClick: true,
                    gallery: {
                        enabled: true
                    },
                    zoom: {
                        enabled: true,
                        duration: 300,
                        easing: 'ease-in-out'
                    }
                });
            });

            function showImageInModal(imageLink) {
                $('#editImageModal').modal('show');
                $('#editImageModal img').attr('src', imageLink);
            }

            function openEditModal(id) {
                // Panggil endpoint untuk mendapatkan data sumbang saran berdasarkan ID
                $.ajax({
                    url: '{{ route('editSS', ['id' => ':id']) }}'.replace(':id', id),
                    type: 'GET',
                    success: function(response) {
                        // Isi form modal dengan data yang diperoleh
                        $('#editnama').val(response.user.name); // Set nama
                        $('#editnpk').val(response.user.npk); // Set npk
                        $('#editTglPengajuan').val(response.tgl_pengajuan_ide);
                        $('#editLokasiIde').val(response.lokasi_ide);
                        $('#editTglDiterapkan').val(response.tgl_diterapkan);
                        $('#editPlant').val(response.plant); // Set plant
                        $('#editJudulIde').val(response.judul);
                        $('#editKeadaanSebelumnya').val(response.keadaan_sebelumnya);
                        $('#editUsulanIde').val(response.usulan_ide);
                        $('#editSumbangSaranId').val(response.id);
                        $('#editKeuntungan').val(response.keuntungan_ide);

                        // Menampilkan file pertama
                        var fileExtension1 = response.file_name.split('.').pop().toLowerCase();
                        var fileLink1 = '{{ asset('assets/image/') }}/' + response.image;

                        if (['jpg', 'jpeg', 'png'].includes(fileExtension1)) {
                            $('#editImagePreview').attr('src', fileLink1).attr('width', '150').attr('height', '150')
                                .show();
                            $('#editFilePreview').hide();
                            $('#editFileName').text('');
                            $('#editImagePreview').click(function() {
                                showImageInModal(fileLink1);
                            });
                        } else {
                            $('#editImagePreview').hide();
                            $('#editFilePreview').attr('href', fileLink1).attr('download', response.file_name)
                                .show();
                            $('#editFileName').text(response.file_name);
                        }

                        // Menampilkan file kedua
                        var fileExtension2 = response.file_name_2.split('.').pop().toLowerCase();
                        var fileLink2 = '{{ asset('assets/image/') }}/' + response.image_2;

                        if (['jpg', 'jpeg', 'png'].includes(fileExtension2)) {
                            $('#editImage2Preview').attr('src', fileLink2).attr('width', '150').attr('height',
                                '150').show();
                            $('#editFile2Preview').hide();
                            $('#editFile2Name').text('');
                            $('#editImage2Preview').click(function() {
                                showImageInModal(fileLink2);
                            });
                        } else {
                            $('#editImage2Preview').hide();
                            $('#editFile2Preview').attr('href', fileLink2).attr('download', response.file_name_2)
                                .show();
                            $('#editFile2Name').text(response.file_name_2);
                        }

                        // Tampilkan modal
                        $('#editSumbangSaranModal').modal('show');
                    },
                    error: function(xhr) {
                        // Tangani kesalahan jika ada
                        console.log(xhr.responseText);
                    }
                });
            }

            function submitEditForm() {
                var formData = new FormData($('#editSumbangSaranForm')[0]);
                var id = $('#editSumbangSaranId').val();
                $.ajax({
                    url: 'updateSS/' + id,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Data berhasil diperbarui.',
                            timer: 1000,
                            showConfirmButton: false
                        }).then((result) => {
                            $('#editSumbangSaranModal').modal('hide');
                            window.location.href = '{{ route('showSS') }}';
                        });
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            }

            function confirmDelete(id) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Setelah dihapus, Anda tidak akan dapat memulihkan data ini!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('deleteSS', ['id' => ':id']) }}'.replace(':id', id),
                            type: 'DELETE', // Ganti dengan DELETE
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(data) {
                                if (data.message === 'Data berhasil dihapus') {
                                    Swal.fire(
                                        'Dihapus!',
                                        'Data berhasil dihapus.',
                                        'success'
                                    ).then(() => {
                                        window.location.href = '{{ route('showSS') }}';
                                    });
                                } else {
                                    Swal.fire(
                                        'Gagal!',
                                        'Terjadi kesalahan saat menghapus data.',
                                        'error'
                                    );
                                }
                            },
                            error: function(xhr) {
                                console.error('Error:', xhr.responseText);
                                Swal.fire(
                                    'Gagal!',
                                    'Terjadi kesalahan saat menghapus data.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            }

            function confirmKirim(id) {
                Swal.fire({
                    title: 'Apakah data sudah benar?',
                    text: 'Setelah dikirim, Anda tidak akan dapat mengubah data ini!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, kirim!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('kirimSS', ['id' => ':id']) }}'.replace(':id', id),
                            type: 'POST', // Ganti dengan DELETE
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(data) {
                                if (data.message === 'Data berhasil dikirim') {
                                    Swal.fire(
                                        'Dikirim!',
                                        'Data berhasil dikirim.',
                                        'success'
                                    ).then(() => {
                                        window.location.href = '{{ route('showSS') }}';
                                    });
                                }
                            }
                        });
                    }
                });
            }
        </script>

        <script>
            //viewadd
            function previewFile(input, previewElementId) {
                const previewElement = document.getElementById(previewElementId);
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

                if (file) {
                    reader.readAsDataURL(file);
                }
            }

            function openImageModal(imageSrc) {
                const modalImage = document.getElementById('modalImage');
                modalImage.src = imageSrc;
                const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                imageModal.show();
            }

            document.getElementById('image').addEventListener('change', function() {
                previewFile(this, 'image-preview');
            });

            document.getElementById('image_2').addEventListener('change', function() {
                previewFile(this, 'image_2-preview');
            });

            function viewFormSS(id) {
                $.ajax({
                    url: '{{ route('sechead.show', ':id') }}'.replace(':id', id),
                    type: 'GET',
                    success: function(response) {
                        console.log(response); // Debug: Check response

                        if (response) {
                            $('#viewname').val(response.user.name);
                            $('#viewnpk').val(response.user.npk);
                            $('#viewTglPengajuan').val(response.tgl_pengajuan_ide);
                            $('#viewPlant').val(response.plant);
                            $('#viewLokasiIde').val(response.lokasi_ide);
                            $('#viewTglDiterapkan').val(response.tgl_diterapkan);
                            $('#viewJudulIde').val(response.judul);
                            $('#viewKeadaanSebelumnya').val(response.keadaan_sebelumnya);
                            $('#viewUsulanIde').val(response.usulan_ide);
                            $('#viewKeuntungan').val(response.keuntungan_ide);
                            $('#viewSumbangSaranId').val(response.id);

                            // Menampilkan file pertama
                            if (response.file_name && response.image) {
                                var fileExtension1 = response.file_name.split('.').pop().toLowerCase();
                                var fileLink1 = '{{ asset('assets/image/') }}/' + response.image;
                                if (['jpg', 'jpeg', 'png'].includes(fileExtension1)) {
                                    $('#view-image-preview').html('<img src="' + fileLink1 +
                                        '" class="img-fluid rounded clickable-view-image" style="max-width: 200px; height: auto;" data-img-src="' +
                                        fileLink1 + '">');
                                    $('#view-image-preview img').click(function() {
                                        showImageInModal2(fileLink1, 'view');
                                    });
                                } else {
                                    $('#view-image-preview').html('<a href="' + fileLink1 + '" download="' +
                                        response.file_name + '">' + response.file_name + '</a>');
                                }
                            } else {
                                $('#view-image-preview').html('');
                            }

                            // Menampilkan file kedua
                            if (response.file_name_2 && response.image_2) {
                                var fileExtension2 = response.file_name_2.split('.').pop().toLowerCase();
                                var fileLink2 = '{{ asset('assets/image/') }}/' + response.image_2;
                                if (['jpg', 'jpeg', 'png'].includes(fileExtension2)) {
                                    $('#view-image2-preview').html('<img src="' + fileLink2 +
                                        '" class="img-fluid rounded clickable-view-image" style="max-width: 200px; height: auto;" data-img-src="' +
                                        fileLink2 + '">');
                                    $('#view-image2-preview img').click(function() {
                                        showImageInModal2(fileLink2, 'view');
                                    });
                                } else {
                                    $('#view-image2-preview').html('<a href="' + fileLink2 + '" download="' +
                                        response.file_name_2 + '">' + response.file_name_2 + '</a>');
                                }
                            } else {
                                $('#view-image2-preview').html('');
                            }

                            $('#viewSumbangSaranModal').modal('show');
                        } else {
                            console.error('Tidak ada data respons');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }

            // Fungsi untuk menampilkan gambar dalam modal
            function showImageInModal2(imageLink, modalType) {
                if (modalType === 'view') {
                    $('#viewImageModal').modal('show');
                    $('#viewModalImage').attr('src', imageLink);
                } else {
                    console.error('Modal type not recognized');
                }
            }
        </script>

    </main><!-- End #main -->
@endsection
