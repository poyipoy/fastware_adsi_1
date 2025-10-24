<?php $__env->startSection('content'); ?>
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Overview Sumbang Saran</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo e(route('dashboardHandling')); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Overview Sumbang Saran</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="mb-4 d-flex flex-column flex-md-row">
            <input type="text" id="searchInput" class="form-control mb-2 mb-md-0 me-md-2"
                placeholder="Cari postingan..." value="<?php echo e(request()->input('search')); ?>">
            <div class="btn-group flex-md-grow-1">
                <button class="btn btn-secondary me-2" id="filterLikesHigh" title="Like Terbanyak">
                    <i class="fas fa-crown"></i>
                </button>
                <button class="btn btn-secondary me-2" id="filterNewest" title="SS Terbaru">
                    <i class="fas fa-sort-amount-down"></i>
                </button>
                <button class="btn btn-secondary" id="filterOldest" title="SS Terlama">
                    <i class="fas fa-sort-amount-up"></i>
                </button>
            </div>
        </div>
        <div class="row" id="postsContainer">
            <div class="container">
                <div class="row">
                    <!-- First Place -->
                    <?php if($data->count() > 0): ?>
                    <div class="col-12 col-md-4 mb-4 post-item" data-likes="<?php echo e($data[0]->suka); ?>"
                        data-date="<?php echo e($data[0]->tgl_pengajuan_ide); ?>" data-title="<?php echo e(strtolower($data[0]->judul)); ?>"
                        data-user="<?php echo e(strtolower($data[0]->user->name)); ?>">
                        <div class="card position-relative"
                            style="background: linear-gradient(to right, #f35129, #6b51ff);">
                            <div class="position-absolute top-0 end-0 m-2 badge rounded-pill text-warning fs-5"
                                style="background-color: #333; font-size: 1.5rem; padding: 1rem;">
                                🥇 1st Place
                            </div>
                            <div class="card-body mt-3">
                                <div class="mb-3 d-flex">
                                    <img src="assets/img/user.png" alt="User Avatar" class="rounded-circle me-2"
                                        style="width: 50px; height: 50px;">
                                    <div>
                                        <h6 class="mb-0"><?php echo e($data[0]->user->name); ?></h6>
                                        <small class="text-muted"><?php echo e(\Carbon\Carbon::parse($data[0]->tgl_pengajuan_ide)->diffForHumans()); ?></small>
                                    </div>
                                </div>
                                <p class="mb-4"><?php echo e($data[0]->judul); ?></p>
                                <div class="">
                                    <button class="btn btn-primary btn-sm like-button" data-id="<?php echo e($data[0]->id); ?>">
                                        <i class="fas fa-thumbs-up"></i> <span class="like-count"><?php echo e($data[0]->suka); ?></span>
                                    </button>
                                    <span class="ms-2" id="likeEmote"><?php echo e($data[0]->suka > 9 ? '🔥' : ''); ?></span>
                                    <button class="btn btn-success btn-sm" id="fetchDataButton"
                                        onclick="viewFormSS(<?php echo e($data[0]->id); ?>)" title="Lihat">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Second Place -->
                    <?php if($data->count() > 1): ?>
                    <div class="col-12 col-md-4 mb-4 post-item" data-likes="<?php echo e($data[1]->suka); ?>"
                        data-date="<?php echo e($data[1]->tgl_pengajuan); ?>" data-title="<?php echo e(strtolower($data[1]->judul)); ?>"
                        data-user="<?php echo e(strtolower($data[1]->user->name)); ?>">
                        <div class="card position-relative"
                            style="background: linear-gradient(to right,#FFA500, #FFD700 );">
                            <div class="position-absolute top-0 end-0 m-2 badge rounded-pill text-danger fs-6"
                                style="background-color: #333333; font-size: 1.5rem; padding: 1rem;">
                                🥈 2nd Place
                            </div>

                            <style>
                                .card {
                                    border-radius: 10px;
                                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                                    height: 200px;
                                    display: flex;
                                    justify-content: center;
                                }

                                .card:hover {
                                    transform: scale(1.05);
                                    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
                                }
                            </style>
                            <div class="card-body mt-3">
                                <div class="mb-3 d-flex">
                                    <img src="assets/img/user.png" alt="User Avatar" class="rounded-circle me-2"
                                        style="width: 50px; height: 50px;">
                                    <div>
                                        <h6 class="mb-0"><?php echo e($data[1]->user->name); ?></h6>
                                        <small class="text-muted"><?php echo e(\Carbon\Carbon::parse($data[1]->tgl_pengajuan)->diffForHumans()); ?></small>
                                    </div>
                                </div>
                                <p class="mb-4"><?php echo e($data[1]->judul); ?></p>
                                <div class="">
                                    <button class="btn btn-primary btn-sm like-button" data-id="<?php echo e($data[1]->id); ?>">
                                        <i class="fas fa-thumbs-up"></i> <span class="like-count"><?php echo e($data[1]->suka); ?></span>
                                    </button>
                                    <span class="ms-2" id="likeEmote"><?php echo e($data[1]->suka > 9 ? '🔥' : ''); ?></span>
                                    <button class="btn btn-success btn-sm" id="fetchDataButton"
                                        onclick="viewFormSS(<?php echo e($data[1]->id); ?>)" title="Lihat">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Third Place -->
                    <?php if($data->count() > 2): ?>
                    <div class="col-12 col-md-4 mb-4 post-item" data-likes="<?php echo e($data[2]->suka); ?>"
                        data-date="<?php echo e($data[2]->tgl_pengajuan); ?>" data-title="<?php echo e(strtolower($data[2]->judul)); ?>"
                        data-user="<?php echo e(strtolower($data[2]->user->name)); ?>">
                        <div class="card position-relative"
                            style="background: linear-gradient(to right, #f85b00, #dbaa3f);">
                            <div class="position-absolute top-0 end-0 m-2 badge rounded-pill text-info fs-6"
                                style="background-color: #333; font-size: 1.5rem; padding: 1rem;">
                                🥉 3rd Place
                            </div>
                            <div class="card-body mt-3">
                                <div class="mb-3 d-flex">
                                    <img src="assets/img/user.png" alt="User Avatar" class="rounded-circle me-2"
                                        style="width: 50px; height: 50px;">
                                    <div>
                                        <h6 class="mb-0"><?php echo e($data[2]->user->name); ?></h6>
                                        <small class="text-muted"><?php echo e(\Carbon\Carbon::parse($data[2]->tgl_pengajuan)->diffForHumans()); ?></small>
                                    </div>
                                </div>
                                <p class="mb-4"><?php echo e($data[2]->judul); ?></p>
                                <div class="">
                                    <button class="btn btn-primary btn-sm like-button" data-id="<?php echo e($data[2]->id); ?>">
                                        <i class="fas fa-thumbs-up"></i> <span class="like-count"><?php echo e($data[2]->suka); ?></span>
                                    </button>
                                    <span class="ms-2" id="likeEmote"><?php echo e($data[2]->suka > 9 ? '🔥' : ''); ?></span>
                                    <button class="btn btn-success btn-sm" id="fetchDataButton"
                                        onclick="viewFormSS(<?php echo e($data[2]->id); ?>)" title="Lihat">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Other Posts -->
                    <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($index > 2): ?>
                    <div class="col-12 col-md-6 col-lg-3 mb-4 post-item" data-likes="<?php echo e($post->suka); ?>"
                        data-date="<?php echo e($post->tgl_pengajuan); ?>" data-title="<?php echo e(strtolower($post->judul)); ?>"
                        data-user="<?php echo e(strtolower($post->user->name)); ?>">
                        <div class="card">
                            <div class="card-body mt-3">
                                <div class="mb-3 d-flex">
                                    <img src="assets/img/user.png" alt="User Avatar" class="rounded-circle me-2"
                                        style="width: 50px; height: 50px;">
                                    <div>
                                        <h6 class="mb-0"><?php echo e($post->user->name); ?></h6>
                                        <small class="text-muted"><?php echo e(\Carbon\Carbon::parse($post->tgl_pengajuan)->diffForHumans()); ?></small>
                                    </div>
                                </div>
                                <p class="mb-4"><?php echo e($post->judul); ?></p>
                                <div class="">
                                    <button class="btn btn-primary btn-sm like-button" data-id="<?php echo e($post->id); ?>">
                                        <i class="fas fa-thumbs-up"></i> <span class="like-count"><?php echo e($post->suka); ?></span>
                                    </button>
                                    <span class="ms-2" id="likeEmote"><?php echo e($post->suka > 9 ? '🔥' : ''); ?></span>
                                    <button class="btn btn-success btn-sm" id="fetchDataButton"
                                        onclick="viewFormSS(<?php echo e($post->id); ?>)" title="Lihat">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
        <!-- Readonly Modal Form View Sumbang Saran -->
        <div class="modal fade" id="viewSumbangSaranModal" tabindex="-1" aria-labelledby="viewSumbangSaranModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 90%;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewSumbangSaranModalLabel">Form View SS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form View Sumbang Saran -->
                        <form id="viewSumbangSaranForm" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <div class="row mb-3">
                                <label for="editLokasiIde" class="col-sm-2 col-form-label">Nama<span
                                        style="color: red;">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="viewname" name="nama" disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="editLokasiIde" class="col-sm-2 col-form-label">Npk<span
                                        style="color: red;">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="viewnpk" name="npk" disabled>
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
                                <label for="editPlant" class="col-sm-2 col-form-label">Plant<span
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
                                <label for="viewTglDiterapkan" class="col-sm-2 col-form-label">Tgl. Diterapkan</label>
                                <div class="col-sm-10">
                                    <input type="date" class="form-control" id="viewTglDiterapkan" name="tgl_diterapkan"
                                        disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="viewJudulIde" class="col-sm-2 col-form-label">Judul Ide <span
                                        style="color: red;">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="viewJudulIde" name="judul" disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="viewKeadaanSebelumnya" class="col-sm-2 col-form-label">Keadaan Sebelumnya
                                    (Permasalahan) <span style="color: red;">*</span></label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" style="height: 100px" id="viewKeadaanSebelumnya"
                                        name="keadaan_sebelumnya" disabled></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="viewImage" class="col-sm-2 col-form-label">File Upload
                                    (Sebelumnya) <span style="color: red;">*</span></label>
                                <div class="col-sm-10">
                                    <div id="view-image-preview" style="margin-top: 10px;"></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="viewUsulanIde" class="col-sm-2 col-form-label">Usulan Ide <span
                                        style="color: red;">*</span></label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" style="height: 100px" id="viewUsulanIde"
                                        name="usulan_ide" disabled></textarea>
                                </div>
                            </div>
                            <!-- Input File Upload 2 -->
                            <div class="row mb-3">
                                <label for="viewImage2" class="col-sm-2 col-form-label">File Upload (Sesudah) <span
                                        style="color: red;">*</span></label>
                                <div class="col-sm-10">
                                    <div id="view-image2-preview" style="margin-top: 10px;"></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="viewKeuntungan" class="col-sm-2 col-form-label">Keuntungan Dari Penerapan
                                    Ide <span style="color: red;">*</span></label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" style="height: 100px" id="viewKeuntungan"
                                        name="keuntungan_ide" disabled></textarea>
                                </div>
                            </div>
                            <input type="hidden" id="viewSumbangSaranId" name="id">
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Gambar -->
        <div class="modal fade" id="viewImageModal" tabindex="-1" aria-labelledby="viewImageModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewImageModalLabel">Gambar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="viewModalImage" src="" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .btn-group.flex-md-grow-1 .btn {
            flex-grow: 1;
        }

        @media (max-width: 768px) {
            .btn-group.flex-md-grow-1 .btn {
                margin-bottom: 5px;
            }
        }
    </style>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="<?php echo e(asset('assets/vendor/simple-datatables/simple-datatables.js')); ?>"></script>
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
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('searchInput').addEventListener('input', function() {
                let searchQuery = this.value.toLowerCase();
                let params = new URLSearchParams(window.location.search);
                params.set('search', searchQuery);
                window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                filterPosts();
            });

            document.getElementById('filterLikesHigh').addEventListener('click', function() {
                let params = new URLSearchParams(window.location.search);
                params.set('sort', 'likesHigh');
                window.location.search = params.toString();
            });

            document.getElementById('filterNewest').addEventListener('click', function() {
                let params = new URLSearchParams(window.location.search);
                params.set('sort', 'newest');
                window.location.search = params.toString();
            });

            document.getElementById('filterOldest').addEventListener('click', function() {
                let params = new URLSearchParams(window.location.search);
                params.set('sort', 'oldest');
                window.location.search = params.toString();
            });

            function filterPosts() {
                let searchQuery = new URLSearchParams(window.location.search).get('search') || '';
                let posts = document.querySelectorAll('.post-item');
                posts.forEach(function(post) {
                    let title = post.getAttribute('data-title');
                    let user = post.getAttribute('data-user');
                    if (title.includes(searchQuery) || user.includes(searchQuery)) {
                        post.style.display = '';
                    } else {
                        post.style.display = 'none';
                    }
                });
            }

            // Call filterPosts on page load to apply search filter if exists
            document.addEventListener('DOMContentLoaded', function() {
                filterPosts();
            });
    </script>
    <script>
        $(document).ready(function() {
                $('.like-button').on('click', function() {
                    var postId = $(this).data('id');
                    var likeButton = $(this);

                    // Check if the post is already liked or not
                    var isLiked = likeButton.hasClass('liked');

                    $.ajax({
                        url: isLiked ? '<?php echo e(url('/sumbangsaran/unlike')); ?>/' + postId :
                            '<?php echo e(url('/sumbangsaran/like')); ?>/' + postId,
                        type: 'POST',
                        data: {
                            _token: '<?php echo e(csrf_token()); ?>'
                        },
                        success: function(response) {
                            if (isLiked) {
                                likeButton.removeClass('liked');
                            } else {
                                likeButton.addClass('liked');
                            }
                            likeButton.find('.like-count').text(response.suka);
                        }
                    });
                });
            });

            //viewmodal
            function viewFormSS(id) {
                $.ajax({
                    url: '<?php echo e(route('sechead.show', ':id')); ?>'.replace(':id', id),
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
                                var fileLink1 = '<?php echo e(asset('assets/image/')); ?>/' + response.image;
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
                                var fileLink2 = '<?php echo e(asset('assets/image/')); ?>/' + response.image_2;
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

            document.addEventListener('DOMContentLoaded', function() {
                var likeCounts = document.querySelectorAll('.like-count');

                likeCounts.forEach(function(count) {
                    var likes = parseInt(count.textContent);
                    if (likes > 10) {
                        var emoteSpan = count.parentElement.nextElementSibling.querySelector('#likeEmote');
                        emoteSpan.innerHTML = '🔥'; // Menampilkan emoji api jika suka > 10
                    }
                });
            });

            // Ambil nama user dari server-side (Laravel)
            // const userName = "<?php echo e(Auth::user()->name); ?>";
            // // Fungsi untuk menampilkan pesan ketika halaman selesai dimuat
            // window.onload = function() {
            //     const userName = "<?php echo e(Auth::user()->name); ?>"; // Ambil nama pengguna

            //     Swal.fire({
            //         title: 'Selamat Datang!',
            //         text: `Selamat datang, ${userName}. Semoga harimu menyenangkan!`,
            //         icon: 'info',
            //         showConfirmButton: false, // Menghilangkan tombol OK
            //         timer: 3000, // Durasi pesan (5 detik)
            //         timerProgressBar: true, // Menampilkan progress bar
            //     });
            // };
    </script>
</main>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\fastware_adsi_1\resources\views/ss/forumSS.blade.php ENDPATH**/ ?>