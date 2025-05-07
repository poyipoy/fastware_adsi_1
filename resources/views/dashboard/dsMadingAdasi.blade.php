@extends('layout')

@section('content')
    <style>
        .responsive-image-container {
            width: 100%;
            margin-bottom: 20px;
        }

        .responsive-image-container img {
            width: 100%;
            height: auto;
        }

        .custom-carousel {
            height: 300px;
        }

        .custom-carousel .carousel-inner {
            height: 100%;
        }

        .custom-carousel .carousel-item {
            height: 100%;
        }

        .custom-carousel .carousel-item img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        .container {
            max-width: 1200px;
        }

        .border-purple {
            border: 2px solid purple;
            border-radius: 5px;
        }

        .info-card {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .info-card .icon {
            margin-right: 10px;
        }

        .pdf-icon {
            font-size: 50px;
            /* Ukuran ikon PDF */
            color: red;
            /* Warna ikon PDF */
        }

        .text p {
            margin: 0;
            font-weight: bold;
        }
    </style>
    <main id="main" class="main">
        <section class="section dashboard">
            <div class="card">
                <div class="card-body">
                    <h1 class="card-title">E-Mading</h1>
                    <!-- E-Mading Carousel with Images and Files -->
                    <div id="eMadingCarousel" class="carousel slide custom-carousel" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#eMadingCarousel" data-bs-slide-to="0" class="active"
                                aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#eMadingCarousel" data-bs-slide-to="1"
                                aria-label="Slide 2"></button>
                            <button type="button" data-bs-target="#eMadingCarousel" data-bs-slide-to="2"
                                aria-label="Slide 3"></button>
                        </div>
                        <div class="carousel-inner">
                            <!-- Example Random Image Slide -->
                            <div class="carousel-item active">
                                <img src="https://picsum.photos/800/400?random=1" class="d-block w-100 custom-carousel-img"
                                    alt="Random Image Slide"
                                    onclick="openImageModal('https://picsum.photos/800/400?random=1')">
                            </div>

                            <!-- Example Random Image Slide -->
                            <div class="carousel-item">
                                <img src="https://picsum.photos/800/400?random=2" class="d-block w-100 custom-carousel-img"
                                    alt="Random Image Slide"
                                    onclick="openImageModal('https://picsum.photos/800/400?random=2')">
                            </div>

                            <!-- Example Random Image Slide -->
                            <div class="carousel-item">
                                <img src="https://picsum.photos/800/400?random=3" class="d-block w-100 custom-carousel-img"
                                    alt="Random Image Slide"
                                    onclick="openImageModal('https://picsum.photos/800/400?random=3')">
                            </div>
                        </div>

                        <button class="carousel-control-prev" type="button" data-bs-target="#eMadingCarousel"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#eMadingCarousel"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>

                    <!-- Modal Bootstrap untuk menampilkan gambar besar -->
                    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="imageModalLabel">Preview Image</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <img id="modalImage" src="" class="img-fluid" alt="Selected Image">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Cards -->
            <div class="container my-5">
                <h2 class="text-center">File Informasi</h2>
                <div class="row text-center border border-purple p-4">
                    <!-- Minggu Ini -->
                    <div class="col-md-4">
                        <h4>Minggu Ini</h4>
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-file-pdf pdf-icon"></i>
                            </div>
                            <div class="text">
                                <p>Judul Informasi</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-file-pdf pdf-icon"></i>
                            </div>
                            <div class="text">
                                <p>Judul Informasi</p>
                            </div>
                        </div>
                        <!-- Add more cards as needed -->
                    </div>

                    <!-- Bulan Ini -->
                    <div class="col-md-4">
                        <h4>Bulan Ini</h4>
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-file-pdf pdf-icon"></i>
                            </div>
                            <div class="text">
                                <p>Judul Informasi</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-file-pdf pdf-icon"></i>
                            </div>
                            <div class="text">
                                <p>Judul Informasi</p>
                            </div>
                        </div>
                        <!-- Add more cards as needed -->
                    </div>

                    <!-- Bulan Sebelumnya -->
                    <div class="col-md-4">
                        <h4>Bulan Sebelumnya</h4>
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-file-pdf pdf-icon"></i>
                            </div>
                            <div class="text">
                                <p>Judul Informasi</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-file-pdf pdf-icon"></i>
                            </div>
                            <div class="text">
                                <p>Judul Informasi</p>
                            </div>
                        </div>
                        <!-- Add more cards as needed -->
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
        <script>
            function openImageModal(imageUrl) {
                // Set image src di dalam modal
                document.getElementById('modalImage').src = imageUrl;
                // Tampilkan modal
                var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                imageModal.show();
            }
        </script>
    </main>
@endsection
