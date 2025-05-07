@extends('layout')

@section('content')
    <style>
        .responsive-image-container {
            width: 100%;
            margin-bottom: 20px;
            /* Tambahkan jarak antara gambar dan card */
        }

        .responsive-image-container img {
            width: 100%;
            height: auto;
            /* Memastikan gambar menyesuaikan lebar container tanpa distorsi */
        }

        .custom-carousel {
            height: 300px;
            /* Sesuaikan tinggi carousel sesuai kebutuhan */
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
            /* Mengatur agar gambar memenuhi container tanpa distorsi */
        }



        .custom-carousel {
            height: 300px;
            /* Sesuaikan tinggi carousel sesuai kebutuhan */
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
            /* Mengatur agar gambar memenuhi container tanpa distorsi */
        }

        .modal-header.bg-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-close.text-white {
            filter: invert(1);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .modal-footer .btn {
            margin-left: 10px;
        }

        .modal-body {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }

        .modal-xl {
            max-width: 100%;
            margin: 0;
        }

        .modal-body iframe {
            height: calc(100vh - 210px);
        }

        #pdfViewerContainer {
            width: 100%;
            height: 1000px;
        }

        .pdf-page {
            margin-bottom: 20px;
        }

        canvas {
            width: 100%;
            height: 100%;
        }

        .liked {
            color: blue;
        }

        .leaderboard {
            max-width: 800px;
            margin: 0 auto;
        }

        .leaderboard-item {
            border-radius: 10px;
            transition: transform 0.2s ease-in-out;
        }

        .leaderboard-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .leaderboard-rank {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .leaderboard-avatar img {
            border: 2px solid #ddd;
        }

        .bg-warning {
            background-color: #ffeb3b !important;
        }

        .bg-secondary {
            background-color: #c0c0c0 !important;
        }

        .bg-danger {
            background-color: #f44336 !important;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }
    </style>
    <main id="main" class="main">
        <section class="section dashboard">
            <div class="row">
                {{-- <h3 style="display: flex; justify-content: center;">Dashboard Knowledge</h3> --}}
                <!-- Left side columns -->
                <!-- Responsive Image -->
                <div class="responsive-image-container">
                    <img src="assets/img/KmAdasi.png" class="img-fluid" alt="Responsive Image">
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">E-Mading</h5>

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
                                <!-- Example Image Slide -->
                                <div class="carousel-item active">
                                    <img src="assets/img/slides-1.jpg" class="d-block w-100 custom-carousel-img"
                                        alt="Image Slide">
                                </div>

                                <!-- Example PDF Slide -->
                                <div class="carousel-item">
                                    <h5>Informasi Libur Lebaran</h5>
                                    <div class="d-block w-100 custom-carousel-img text-center">
                                        <!-- PDF Icon -->
                                        <i class="fas fa-file-pdf fa-5x text-danger mb-3"></i>

                                        <!-- Button to Open PDF -->
                                        <a href="assets/files/(001-055) Surat Ijin Seminar PK ttd.pdf"
                                            class="btn btn-primary" target="_blank">
                                            <i class="fas fa-download"></i> Open PDF
                                        </a>

                                        <!-- Optional Description -->
                                        <p class="mt-2">PDF: Example Document</p>
                                    </div>
                                </div>

                                <!-- Example Image Slide -->
                                <div class="carousel-item">
                                    <img src="assets/img/slides-3.jpg" class="d-block w-100 custom-carousel-img"
                                        alt="Image Slide">
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
                    </div>
                </div>

                <div class="container">
                    <div class="row">
                        <div class="mb-4 d-flex">
                            <input type="text" id="searchInput" onkeyup="searchData()"
                                placeholder="Silahkan cari buku atau kategori yang dicari..." class="form-control mr-2">
                            <button type="button" class="btn btn-secondary ml-2" onclick="toggleSort()">
                                <i class="fas fa-sort"></i> Sort by Reads
                            </button>
                            <button type="button" class="btn btn-success ml-2" onclick="filterStatus('Belum Membaca')">
                                <i class="fas fa-book-open"></i> Filter Belum Membaca
                            </button>
                            <button type="button" class="btn btn-warning ml-2" onclick="filterStatus('Sedang Dibaca')">
                                <i class="fas fa-book-reader"></i> Filter Sedang Dibaca
                            </button>
                        </div>


                        <div class="col-md-3 mb-2">
                            <div class="card">
                                <h3 class="text-center mb-4">Leaderboard</h3>
                                <div class="card shadow-sm">
                                    <div class="card-body p-0">
                                        <div class="leaderboard" style="max-height: 400px; overflow-y: auto;">
                                            @foreach ($leaderboard as $index => $user)
                                                <div
                                                    class="leaderboard-item d-flex align-items-center mb-3 p-3 {{ $index == 0 ? 'bg-warning' : ($index == 1 ? 'bg-secondary' : ($index == 2 ? 'bg-danger' : 'bg-light') . ' rounded shadow-sm') }}">
                                                    <div class="leaderboard-rank text-center mr-3">
                                                        <span
                                                            class="badge badge-pill badge-dark p-2">{{ $index + 1 }}</span>
                                                    </div>
                                                    <div class="leaderboard-avatar mr-3">
                                                        <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                    </div>
                                                    <div class="leaderboard-info">
                                                        <h5 class="mb-0 font-weight-bold">{{ $user->name }}</h5>
                                                        <small class="text-muted">{{ $user->km_total_poin ?? 0 }}
                                                            Points</small>
                                                    </div>
                                                    <div class="ml-auto">
                                                        @if ($index == 0)
                                                            <i class="fas fa-crown text-warning fa-2x"></i>
                                                        @elseif($index == 1)
                                                            <i class="fas fa-crown text-secondary fa-2x"></i>
                                                        @elseif($index == 2)
                                                            <i class="fas fa-crown text-danger fa-2x"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @foreach ($pengajuans as $pengajuan)
                            @php
                                $transaksi = $pengajuan->kmTransaksi->first();
                                $jumlahLihat = $pengajuan->kmLihatBukus->sum('jumlah_lihat');
                                $status = 'Tidak Diketahui';
                                if ($transaksi) {
                                    switch ($transaksi->status) {
                                        case 2:
                                            $status = 'Sedang Dibaca';
                                            break;
                                    }
                                } else {
                                    $status = 'Belum Membaca';
                                }
                            @endphp
                            <div class="col-md-4" data-views="{{ $jumlahLihat }}" data-status="{{ $status }}">
                                <div class="card mb-4">
                                    <div class="card-body d-flex flex-column justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">
                                                {{ $pengajuan->judul }} - {{ $pengajuan->kmKategori->nama_kategori }} -
                                                <i class="fas fa-eye"></i>
                                                ({{ $jumlahLihat }})
                                            </h5>

                                            <p class="text-center">
                                                <img src="{{ asset('assets/image/' . $pengajuan->image) }}"
                                                    alt="Pengajuan Image"
                                                    style="max-width: 100%; height: 500px; object-fit: cover;">
                                            </p>

                                            <h5>
                                                <p>
                                                    @if ($pengajuan->kmTransaksi->isNotEmpty())
                                                        @php
                                                            $transaksi = $pengajuan->kmTransaksi->first();
                                                        @endphp
                                                        @if ($transaksi->status == 1)
                                                            <span style="color: green; font-weight: bold;">Status:
                                                                Belum
                                                                Dibaca </span>
                                                        @elseif ($transaksi->status == 2)
                                                            <span style="color: red; font-weight: bold;">Status: Sedang
                                                                Dibaca </span>
                                                        @elseif ($transaksi->status == 3)
                                                            <span style="color: blue; font-weight: bold;">Status:
                                                                Selesai
                                                                Dibaca </span>
                                                        @else
                                                            <span style="font-weight: bold;">Status: Tidak
                                                                Diketahui</span>
                                                        @endif
                                                    @else
                                                        <span style="color: green; font-weight: bold;">Status: Belum
                                                            Membaca</span>
                                                    @endif
                                                </p>
                                            </h5>
                                        </div>
                                        <div class="mt-auto d-flex justify-content-end w-100">
                                            <!-- Tombol untuk menampilkan modal keterangan -->
                                            <button type="button" class="btn btn-secondary" data-toggle="modal"
                                                data-target="#keteranganModal{{ $pengajuan->id }}">
                                                Lihat Sinopsis
                                            </button>
                                            <form id="markAsReadForm{{ $pengajuan->id }}"
                                                action="{{ route('kmTransaksi.markAsRead') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="id_km_pengajuan"
                                                    value="{{ $pengajuan->id }}">
                                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                                    data-target="#pdfModal{{ $pengajuan->id }}"
                                                    onclick="markAsRead({{ $pengajuan->id }}); loadPDF('{{ asset('assets/image/' . $pengajuan->file) }}', {{ $pengajuan->id }});">
                                                    Lihat Buku
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between">
                                        <div>
                                            <span
                                                id="like-count-{{ $pengajuan->id }}">{{ $pengajuan->km_sukas_count }}</span>
                                            <button type="button" class="btn btn-light"
                                                id="like-button-{{ $pengajuan->id }}"
                                                onclick="toggleLike({{ $pengajuan->id }})">
                                                <i class="fas fa-thumbs-up"></i> Like
                                            </button>

                                            <button type="button" class="btn btn-light" data-toggle="modal"
                                                data-target="#insightModal{{ $pengajuan->id }}">
                                                <i class="fas fa-comment"></i> Insight
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal untuk menampilkan keterangan -->
                            <div class="modal fade" id="keteranganModal{{ $pengajuan->id }}" tabindex="-1"
                                role="dialog" aria-labelledby="keteranganModalLabel{{ $pengajuan->id }}"
                                aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="keteranganModalLabel{{ $pengajuan->id }}">
                                                Keterangan</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <textarea class="form-control" rows="10" readonly>{{ $pengajuan->keterangan }}</textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Insight Modal -->
                            <div class="modal fade" id="insightModal{{ $pengajuan->id }}" tabindex="-1" role="dialog"
                                aria-labelledby="insightModalLabel{{ $pengajuan->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="insightModalLabel{{ $pengajuan->id }}">
                                                Insights
                                                for
                                                {{ $pengajuan->judul }}</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">

                                            <!-- List of insights -->
                                            <div class="insight-list">
                                                @foreach ($pengajuan->insights as $insight)
                                                    <div class="insight-item mb-3">
                                                        <p><strong>{{ $insight->user->name }}</strong> <small
                                                                class="text-muted">{{ $insight->created_at->format('d M Y H:i') }}</small>
                                                        </p>
                                                        <p>{{ $insight->content }}</p>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Form to add new insight -->
                                            <form action="{{ route('insights.add') }}" method="POST" class="mb-4">
                                                @csrf
                                                <input type="hidden" name="id_km_pengajuan"
                                                    value="{{ $pengajuan->id }}">
                                                <div class="form-group">
                                                    <label for="insightContent{{ $pengajuan->id }}">Add
                                                        Insight</label>
                                                    <textarea class="form-control" id="insightContent{{ $pengajuan->id }}" name="content" rows="3" required></textarea>
                                                </div>
                                                <!-- Submit button outside the textarea -->
                                                <button type="submit" class="btn btn-primary">Submit Insight</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="pdfModal{{ $pengajuan->id }}" tabindex="-1" role="dialog"
                                aria-labelledby="exampleModalLabel{{ $pengajuan->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-xl" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel{{ $pengajuan->id }}">
                                                {{ $pengajuan->judul }}</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Tutup">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div id="pdfViewerContainer{{ $pengajuan->id }}"></div>
                                        </div>
                                        <div class="modal-footer">
                                            @if ($pengajuan->kmTransaksi->isNotEmpty() && $pengajuan->kmTransaksi->first()->status !== 3)
                                                <button type="button" class="btn btn-primary"
                                                    onclick="confirmFinish({{ $pengajuan->id }})">Selesai</button>
                                            @endif
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                                onclick="reloadPage()">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.worker.min.js"></script>
        <script src="https://mozilla.github.io/pdf.js/build/pdf.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            // btnSelesai
            function confirmFinish(idKmPengajuan) {
                Swal.fire({
                    title: 'Apakah Anda selesai membaca?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Tidak'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Make AJAX request to save data
                        $.ajax({
                            url: '{{ route('kmTransaksi.saveTransaction') }}',
                            type: 'POST',
                            data: {
                                id_km_pengajuan: idKmPengajuan,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Berhasil!', 'Anda Telah Selesai Membaca.', 'success');
                                    window.location.href = '{{ route('dsKnowlege') }}';
                                } else {
                                    Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
                            }
                        });
                    }
                });
            }

            //btnBaca
            function markAsRead(pengajuanId) {
                const form = document.getElementById(`markAsReadForm${pengajuanId}`);
                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        // You can update the status text here if needed
                    }
                }).catch(error => console.error('Error:', error));
            }

            function loadPDF(url, pengajuanId) {
                var pdfjsLib = window['pdfjs-dist/build/pdf'];
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://mozilla.github.io/pdf.js/build/pdf.worker.js';

                var loadingTask = pdfjsLib.getDocument(url);
                loadingTask.promise.then(function(pdf) {
                    // Menghapus kontainer sebelumnya
                    var container = document.getElementById('pdfViewerContainer' + pengajuanId);
                    container.innerHTML = '';

                    // Menampilkan semua halaman
                    for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                        pdf.getPage(pageNum).then(function(page) {
                            var scale = 2; // Zoom level
                            var viewport = page.getViewport({
                                scale: scale
                            });

                            var canvas = document.createElement('canvas');
                            var context = canvas.getContext('2d');
                            canvas.height = viewport.height;
                            canvas.width = viewport.width;

                            var renderContext = {
                                canvasContext: context,
                                viewport: viewport
                            };
                            page.render(renderContext);

                            container.appendChild(canvas);
                        });
                    }
                });
            }

            function reloadPage() {
                window.location.href = '{{ route('dsKnowlege') }}';
            }

            //btnLike
            function toggleLike(id) {
                const likeButton = document.getElementById('like-button-' + id);
                const likeCount = document.getElementById('like-count-' + id);
                const isLiked = likeButton.classList.contains('liked');

                const url = isLiked ? '{{ route('kmSuka.unlike') }}' : '{{ route('kmSuka.like') }}';
                const method = isLiked ? 'unlike' : 'like';

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id_km_pengajuan: id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.message) {
                            likeCount.textContent = data.like_count;
                            if (method === 'like') {
                                likeButton.classList.add('liked');
                            } else {
                                likeButton.classList.remove('liked');
                            }
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            var sortOrder = 'asc'; // Track the sorting order, starting with ascending

            function searchData() {
                var input, filter, cards, title, i, txtValue;
                input = document.getElementById('searchInput');
                filter = input.value.toLowerCase();
                cards = document.getElementsByClassName('col-md-4');

                for (i = 0; i < cards.length; i++) {
                    title = cards[i].getElementsByClassName('card-title')[0];
                    txtValue = title.textContent || title.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        cards[i].style.display = "";
                    } else {
                        cards[i].style.display = "none";
                    }
                }
            }

            function toggleSort() {
                if (sortOrder === 'asc') {
                    sortData('desc');
                    sortOrder = 'desc';
                } else {
                    sortData('asc');
                    sortOrder = 'asc';
                }
            }

            function sortData(order) {
                var cards, i, switching, b, shouldSwitch;
                cards = document.getElementsByClassName('col-md-4');
                switching = true;
                while (switching) {
                    switching = false;
                    for (i = 0; i < (cards.length - 1); i++) {
                        shouldSwitch = false;
                        if (order === 'asc' && parseInt(cards[i].getAttribute('data-views')) > parseInt(cards[i + 1]
                                .getAttribute('data-views'))) {
                            shouldSwitch = true;
                            break;
                        } else if (order === 'desc' && parseInt(cards[i].getAttribute('data-views')) < parseInt(cards[i + 1]
                                .getAttribute('data-views'))) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                    if (shouldSwitch) {
                        cards[i].parentNode.insertBefore(cards[i + 1], cards[i]);
                        switching = true;
                    }
                }
            }

            function filterStatus(status) {
                var cards, i, cardStatus;
                cards = document.getElementsByClassName('col-md-4');

                for (i = 0; i < cards.length; i++) {
                    cardStatus = cards[i].getAttribute('data-status');
                    if (cardStatus === status || status === 'All') {
                        cards[i].style.display = "";
                    } else {
                        cards[i].style.display = "none";
                    }
                }
            }
        </script>
    </main>
@endsection
