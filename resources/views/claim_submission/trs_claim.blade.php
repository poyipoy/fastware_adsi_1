@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Proses Claim Submission</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('claim.indexProc') }}">Persetujuan Claim Submission</a>
                    </li>
                    <li class="breadcrumb-item active">Proses Claim</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5>Form Proses Claim Submission</h5>
                    </div>

                    <div class="card-body" style="margin-top: 3%">
                        <!-- No. PR -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">No. PR</label>
                            <input type="text" class="form-control" value="{{ $claim->no_pr }}" disabled>
                        </div>

                        <!-- Nama Produk -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Produk</label>
                            <input type="text" class="form-control" value="{{ $claim->nama_produk }}" disabled>
                        </div>

                        <!-- PIC -->

                        <div class="mb-3">
                            <label class="form-label fw-bold">PIC (Pengaju)</label>
                            <input type="text" class="form-control" value="{{ $claim->modified_at }}" disabled>
                        </div>

                        <!-- Supplier (editable by procurement) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Supplier</label>
                            <input type="text" class="form-control" id="supplier" name="supplier" value="{{ $claim->supplier }}" placeholder="Masukkan nama supplier" {{ $claim->status === 'finished' ? 'disabled' : '' }}>
                        </div>

                        <!-- Submission Date -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Submission Date</label>
                            <input type="text" class="form-control"
                                value="{{ $claim->submission_date ? $claim->submission_date->format('d-m-Y') : '-' }}"
                                disabled>
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <input type="text" class="form-control" value="{{ $claim->category }}" disabled>
                        </div>

                        <!-- Description of Issue -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description of Issue</label>
                            <textarea class="form-control" rows="4" disabled>{{ $claim->description_of_issue }}</textarea>
                        </div>

                        <!-- Proposed Solution -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Proposed Solution</label>
                            <textarea class="form-control" rows="4" disabled>{{ $claim->proposed_solution }}</textarea>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status Saat Ini</label>
                            <div>
                                <span class="badge {{ $claim->status_badge }}"
                                    style="font-size: 18px;">{{ $claim->status_label }}</span>
                            </div>
                        </div>

                        <!-- File / Foto -->
                        <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                            <label class="form-label fw-bold text-primary">
                                <i class="fas fa-paperclip"></i> Foto / Bukti dari User
                            </label>
                            @if ($claim->file)
                                <div class="mt-2">
                                    @if (in_array(pathinfo($claim->file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                        <a href="{{ asset($claim->file) }}" target="_blank">
                                            <img src="{{ asset($claim->file) }}" alt="Bukti Claim"
                                                style="max-width: 400px; max-height: 300px;"
                                                class="rounded border shadow-sm">
                                        </a>
                                    @else
                                        <a href="{{ asset($claim->file) }}" target="_blank"
                                            class="btn btn-outline-secondary">
                                            <i class="fas fa-file-alt fa-lg me-2"></i> {{ $claim->file_name }}
                                        </a>
                                    @endif
                                </div>
                            @else
                                <p class="text-muted fst-italic mt-2">Tidak ada file yang dilampirkan</p>
                            @endif
                        </div>

                        <hr>

                        <!-- Catatan Procurement -->
                        <div class="mb-3">
                            <label for="catatan_procurement" class="form-label fw-bold">Catatan Procurement</label>
                            <textarea class="form-control" id="catatan_procurement" name="catatan_procurement" rows="4"
                                placeholder="Masukkan catatan / respon dari procurement">{{ $claim->catatan_procurement }}</textarea>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex justify-content-between" style="margin-top: 3%">
                            <div>
                                <button type="button" class="btn btn-secondary mb-4"
                                    onclick="showHistory({{ $claim->id }})">
                                    <i class="fas fa-eye"></i> Lihat Histori
                                </button>
                            </div>
                            <div class="d-flex">
                                <button type="button" class="btn btn-info mb-4 me-2" onclick="simpanPerubahan({{ $claim->id }})">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                                @if ($claim->status === 'open')
                                    <button type="button" class="btn btn-warning mb-4 me-2"
                                        onclick="prosesData({{ $claim->id }})">
                                        <i class="fas fa-play"></i> Proses (On Progress)
                                    </button>
                                @elseif ($claim->status === 'on_progress')
                                    <button type="button" class="btn btn-success mb-4 me-2"
                                        onclick="finishClaim({{ $claim->id }})">
                                        <i class="fas fa-check"></i> Selesai (Finish)
                                    </button>
                                @endif
                                <a href="{{ route('claim.indexProc') }}" class="btn btn-secondary mb-4">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal History -->
        <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="historyModalLabel">Histori Claim Submission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered align-middle history-table">
                            <thead>
                                <tr>
                                    <th class="history-col-no">No</th>
                                    <th class="history-col-keterangan">Keterangan</th>
                                    <th>Status</th>
                                    <th>Oleh</th>
                                    <th id="historyDateHeader" style="cursor: pointer; user-select: none;" onclick="toggleHistorySort()">
                                        Tanggal <span id="historyDateSortIcon">↓</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            #historyModal .modal-body {
                overflow-x: auto;
            }

            #historyModal .history-table {
                table-layout: fixed;
                width: 100%;
            }

            #historyModal .history-table th,
            #historyModal .history-table td {
                white-space: normal;
                word-break: break-word;
                overflow-wrap: anywhere;
                vertical-align: top;
            }

            #historyModal .history-keterangan-cell {
                max-width: 0;
            }

            #historyModal .history-table .history-col-no {
                width: 56px;
                text-align: center;
            }

            #historyModal .history-table .history-col-keterangan {
                width: 52%;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script>
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatHistoryText(text) {
            return (text || '-')
                .split(';')
                .map(function (part) {
                    return part.trim();
                })
                .filter(function (part) {
                    return part.length > 0;
                })
                .map(function (part) {
                    return '<div class="mb-1">' + escapeHtml(part) + '</div>';
                })
                .join('') || '-';
        }

        function getStatusBadgeClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'open':
                    return 'bg-primary';
                case 'on progress':
                case 'on_progress':
                    return 'bg-warning text-dark';
                case 'finished':
                    return 'bg-success';
                default:
                    return 'bg-secondary';
            }
        }

        let historySortOrder = 'desc';
        let historyRawData = [];

        function parseHistoryDate(dateString) {
            if (!dateString) return 0;
            const parts = dateString.split(' ');
            if (parts.length !== 2) return 0;

            const dateParts = parts[0].split('-');
            const timeParts = parts[1].split(':');
            if (dateParts.length !== 3 || timeParts.length !== 3) return 0;

            const day = parseInt(dateParts[0], 10);
            const month = parseInt(dateParts[1], 10) - 1;
            const year = parseInt(dateParts[2], 10);
            const hour = parseInt(timeParts[0], 10);
            const minute = parseInt(timeParts[1], 10);
            const second = parseInt(timeParts[2], 10);

            return new Date(year, month, day, hour, minute, second).getTime();
        }

        function renderHistoryRows() {
            const tbody = $('#historyTableBody');
            tbody.empty();

            if (!historyRawData.length) {
                tbody.append('<tr><td colspan="5" class="text-center">Belum ada histori</td></tr>');
                return;
            }

            const sortedData = [...historyRawData].sort(function(a, b) {
                const tsA = parseHistoryDate(a.created_at);
                const tsB = parseHistoryDate(b.created_at);
                return historySortOrder === 'asc' ? tsA - tsB : tsB - tsA;
            });

            sortedData.forEach(function(item, index) {
                const ket = formatHistoryText(item.keterangan);
                const statusClass = getStatusBadgeClass(item.status);
                const statusText = escapeHtml(item.status || '-');
                const modifiedBy = escapeHtml(item.modified_at || '-');
                const createdAt = escapeHtml(item.created_at || '-');
                tbody.append(
                    '<tr>' +
                    '<td class="text-center history-col-no">' + (index + 1) + '</td>' +
                    '<td class="history-keterangan-cell">' + ket + '</td>' +
                    '<td><span class="badge ' + statusClass + '">' + statusText + '</span></td>' +
                    '<td>' + modifiedBy + '</td>' +
                    '<td>' + createdAt + '</td>' +
                    '</tr>'
                );
            });
        }

        function toggleHistorySort() {
            historySortOrder = historySortOrder === 'asc' ? 'desc' : 'asc';
            $('#historyDateSortIcon').text(historySortOrder === 'asc' ? '↑' : '↓');
            renderHistoryRows();
        }

        // Gabung simpan supplier & catatan
        function simpanPerubahan(id) {
            var catatan = $('#catatan_procurement').val();
            var supplier = $('#supplier').val();
            $.ajax({
                url: '/claim-submission/' + id + '/submit-proc',
                method: 'POST',
                data: {
                    catatan_procurement: catatan,
                    supplier: supplier,
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    Swal.fire('Berhasil', res.message || 'Perubahan berhasil disimpan', 'success');
                },
                error: function (err) {
                    const message = err.responseJSON?.message || 'Gagal menyimpan perubahan';

                    if (String(message).toLowerCase() === 'belum ada data yang berubah') {
                        Swal.fire('Info', message, 'info');
                        return;
                    }

                    Swal.fire('Gagal', message, 'error');
                }
            });
        }
        function finishClaim(id) {
            const supplier = ($('#supplier').val() || '').trim();
            const catatan = $('#catatan_procurement').val();

            if (!supplier) {
                Swal.fire('Nama Supplier wajib diisi', 'Isi nama supplier sebelum menyelesaikan claim.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Selesaikan Claim?',
                text: 'Status akan diubah menjadi Finished. Aksi ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, selesaikan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/claim-submission/' + id + '/finish',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            supplier: supplier,
                            catatan_procurement: catatan
                        },
                        success: function(response) {
                            Swal.fire('Berhasil!', response.message, 'success')
                                .then(() => location.reload());
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                        }
                    });
                }
            });
        }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            $(document).ready(function() {
                $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });

            function showHistory(id) {
                $.ajax({
                    url: '/claim-submission/' + id + '/history',
                    type: 'GET',
                    success: function(data) {
                        historyRawData = Array.isArray(data) ? data : [];
                        historySortOrder = 'desc';
                        $('#historyDateSortIcon').text('↓');
                        renderHistoryRows();
                        $('#historyModal').modal('show');
                    },
                    error: function() {
                        alert('Gagal memuat histori');
                    }
                });
            }

            function prosesData(id) {
                const supplier = ($('#supplier').val() || '').trim();
                const catatan = $('#catatan_procurement').val();
                Swal.fire({
                    title: 'Proses Claim?',
                    text: "Status akan diubah menjadi On Progress",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, proses!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/claim-submission/' + id + '/proses',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                supplier: supplier,
                                catatan_procurement: catatan
                            },
                            success: function(response) {
                                Swal.fire('Berhasil!', response.message, 'success')
                                    .then(() => location.reload());
                            },
                            error: function(xhr) {
                                Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                            }
                        });
                    }
                });
            }
        </script>

    </main>
@endsection