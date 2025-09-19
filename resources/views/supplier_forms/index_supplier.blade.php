{{-- ========================================================================= --}}
{{-- BAGIAN 1: KERANGKA LAYOUT (HANYA DIRENDER SAAT BUKAN AJAX)              --}}
{{-- ========================================================================= --}}
@if(!$is_ajax)
    @extends('layout')

    {{-- Menyisakan style custom yang spesifik untuk halaman ini --}}
    @push('styles')
    <style>
        /* Menggunakan font Inter untuk konsistensi */
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Latar belakang gradien untuk seluruh halaman */
        body.hold-transition {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
        }

        /* Menghilangkan background dari layout utama jika ada */
        .content-wrapper, main.main {
            background: none !important;
        }

        /* Efek glassmorphism untuk card */
        .card-glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem; /* 16px */
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .table {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .table thead {
            background-color: rgba(236, 242, 253, 0.8);
        }

        /* Menyesuaikan breadcrumb agar lebih kontras */
        .breadcrumb-item a {
            color: #012970;
            transition: 0.3s;
        }
        .breadcrumb-item.active {
            color: #51678f;
        }
    </style>
    @endpush

    @section('content')
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Form Supplier</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Supplier</li>
                    <li class="breadcrumb-item active">Daftar Form</li>
                </ol>
            </nav>
        </div><section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-glass">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                                <h5 class="card-title fw-bold text-dark mb-0" style="font-size: 1.25rem;">Daftar Pengajuan Form Supplier</h5>
                                <div class="d-flex flex-column flex-sm-row align-items-center gap-3 w-100 w-md-auto">
                                    <input type="text" id="search-input" value="{{ request('search') }}" placeholder="Cari supplier..." class="form-control">
                                    <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#generateLinkModal" style="background: linear-gradient(to right, #0d6efd, #800080); border: none;">
                                        <i class="fas fa-plus me-2"></i> Buat Link Form
                                    </button>
                                </div>
                            </div>

                            <div id="table_data_wrapper">
@endif

{{-- ========================================================================= --}}
{{-- BAGIAN 2: KONTEN TABEL (DIRENDER UNTUK REQUEST BIASA & AJAX)           --}}
{{-- ========================================================================= --}}
                                <div id="loading-indicator" class="text-center mb-3" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2 text-muted">Memuat data...</span>
                                </div>

                                <div id="table-content">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead>
                                                <tr>
                                                    <th class="py-3 px-3">Nama Supplier</th>
                                                    <th class="py-3 px-3">Kategori</th>
                                                    <th class="py-3 px-3">Type</th>
                                                    <th class="py-3 px-3">Kode Supplier</th>
                                                    <th class="py-3 px-3">Visit Schedule</th>
                                                    <th class="py-3 px-3">Visit Aktual</th>
                                                    <th class="py-3 px-3">Trial Schedule</th>
                                                    <th class="py-3 px-3">Trial Aktual</th>
                                                    <th class="py-3 px-3">Status</th>
                                                    <th class="text-center py-3 px-3">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($forms as $form)
                                                <tr>
                                                    <td class="px-3 fw-semibold text-primary">{{ $form->supplier->supplier_name ?? '-' }}</td>
                                                    <td class="px-3">{{ $form->supplier->kategori ?? '-' }}</td>
                                                    <td class="px-3">{{ $form->supplier->type ?? '-' }}</td>
                                                    <td class="px-3">{{ $form->supplier_kode ?? '-' }}</td>
                                                    <td class="px-3">{{ $form->visit_schedule ? \Carbon\Carbon::parse($form->visit_schedule)->format('d M Y') : '-' }}</td>
                                                    <td class="px-3">{{ optional($form->visitDetail)->tanggal_visit ? \Carbon\Carbon::parse($form->visitDetail->tanggal_visit)->format('d M Y') : '-' }}</td>
                                                    <td class="px-3">{{ $form->trial_schedule ? \Carbon\Carbon::parse($form->trial_schedule)->format('d M Y') : '-' }}</td>
                                                    <td class="px-3">{{ $form->trial_actual ? \Carbon\Carbon::parse($form->trial_actual)->format('d M Y') : '-' }}</td>
                                                    <td class="px-3">
                                                        @php
                                                            $statusLabels = [ 0 => ['Rejected', 'danger'], 1 => ['Open', 'secondary'], 2 => ['On Progress', 'warning'], 3 => ['On Progress', 'warning'], 4 => ['On Progress', 'warning'], 5 => ['On Progress', 'warning'], 6 => ['Finish', 'success'] ];
                                                            $status = $statusLabels[$form->status] ?? ['Unknown', 'dark'];
                                                        @endphp
                                                        <span class="badge bg-{{ $status[1] }} px-2 py-1">{{ $status[0] }}</span>
                                                    </td>
                                                    <td class="px-3">
                                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                                            <a href="{{ route('supplierform.show', $form->id) }}" class="btn btn-sm btn-info text-white" title="Lihat Detail"><i class="fas fa-eye fa-fw"></i></a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="10" class="text-center py-5">
                                                        <div class="fs-1 mb-3">📋</div>
                                                        <h3 class="fw-semibold text-muted mb-2">Data Tidak Ditemukan</h3>
                                                        <p class="text-muted">Tidak ada data untuk ditampilkan.</p>
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-4 d-flex justify-content-end">
                                        {{ $forms->links() }}
                                    </div>
                                </div>
@if(!$is_ajax)
                            </div> </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- ========================================================================= --}}
    {{-- BAGIAN 3: MODAL & SCRIPT (HANYA DIRENDER SAAT BUKAN AJAX)              --}}
    {{-- ========================================================================= --}}
    <div class="modal fade" id="generateLinkModal" tabindex="-1" aria-labelledby="generateLinkModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="generateLinkModalLabel">Generate Link Sekali Pakai</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted">Klik tombol di bawah untuk membuat link unik yang hanya bisa digunakan satu kali oleh supplier.</p>
            <div class="input-group mt-3">
              <input type="text" class="form-control" id="generatedLinkInput" placeholder="Link akan muncul di sini..." readonly>
              <button class="btn btn-outline-secondary" type="button" id="copyLinkBtn" disabled>
                <i class="fas fa-copy"></i> Salin
              </button>
            </div>
            <div id="copy-success-message" class="text-success mt-2 d-none">
                Link berhasil disalin!
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="button" class="btn btn-primary" id="generateLinkBtn">
              <span id="generate-link-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              <span id="generate-link-text">Generate Link</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- JavaScript Libraries --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- KODE JAVASCRIPT AJAX UNTUK TABEL --}}
    <script>
    $(document).ready(function() {
        let searchTimer;

        function fetchData(page = 1, search = '') {
            $('#loading-indicator').show();
            $('#table-content').css('opacity', 0.5);

            $.ajax({
                url: `{{ route('supplierform.index') }}?page=${page}&search=${search}`,
                type: 'GET',
                success: function(data) {
                    $('#table_data_wrapper').html(data);
                    $('#search-input').focus();
                },
                error: function() {
                    $('#loading-indicator').hide();
                    $('#table-content').css('opacity', 1);
                    alert('Gagal memuat data. Silakan coba lagi.');
                }
            });
        }

        $('#search-input').on('keyup', function() {
            clearTimeout(searchTimer);
            const search = $(this).val();
            searchTimer = setTimeout(() => {
                fetchData(1, search);
            }, 600);
        });

        $(document).on('click', '.pagination a', function(event) {
            event.preventDefault(); 
            const pageUrl = $(this).attr('href');
            const page = new URL(pageUrl).searchParams.get("page");
            const search = $('#search-input').val();
            fetchData(page, search);
        });
    });
    </script>
    
    {{-- SCRIPT UNTUK MODAL GENERATE LINK --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const generateBtn = document.getElementById('generateLinkBtn');
        const generateBtnText = document.getElementById('generate-link-text');
        const generateBtnSpinner = document.getElementById('generate-link-spinner');
        const linkInput = document.getElementById('generatedLinkInput');
        const copyBtn = document.getElementById('copyLinkBtn');
        const copySuccessMessage = document.getElementById('copy-success-message');
        const modalElement = document.getElementById('generateLinkModal');

        if (generateBtn) {
            generateBtn.addEventListener('click', function () {
                generateBtnText.textContent = 'Generating...';
                generateBtnSpinner.classList.remove('d-none');
                generateBtn.disabled = true;

                fetch("{{ route('supplierform.generate-link') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                })
                .then(response => response.ok ? response.json() : Promise.reject('Network response was not ok'))
                .then(data => {
                    if (data.success) {
                        linkInput.value = data.url;
                        copyBtn.disabled = false;
                    } else {
                        linkInput.value = 'Gagal membuat link!';
                        alert(data.message || 'Terjadi kesalahan.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    linkInput.value = 'Terjadi kesalahan!';
                    alert('Tidak dapat terhubung ke server. Periksa koneksi Anda.');
                })
                .finally(() => {
                    generateBtnText.textContent = 'Generate Link';
                    generateBtnSpinner.classList.add('d-none');
                    generateBtn.disabled = false;
                });
            });
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                if (linkInput.value) {
                    navigator.clipboard.writeText(linkInput.value).then(() => {
                        copySuccessMessage.classList.remove('d-none');
                        setTimeout(() => { copySuccessMessage.classList.add('d-none'); }, 2000);
                    }).catch(err => console.error('Gagal menyalin link: ', err));
                }
            });
        }

        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function () {
                linkInput.value = '';
                copyBtn.disabled = true;
                copySuccessMessage.classList.add('d-none');
            });
        }
    });
    </script>

    @endsection
@endif