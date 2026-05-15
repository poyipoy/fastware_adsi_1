@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Detail Claim Submission</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('claim.indexUser') }}">Claim Submission</a></li>
                    <li class="breadcrumb-item active">Detail Claim</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5>Detail Claim Submission</h5>
                    </div>

                    <div class="card-body" style="margin-top: 3%">

                        <!-- Nama PIC -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama PIC</label>
                            <input type="text" class="form-control" value="{{ $claim->modified_at ?? '-' }}" disabled>
                        </div>

                        <!-- No. PR -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">No. PR</label>
                            <input type="text" class="form-control" value="{{ $claim->no_pr }}" disabled>
                        </div>

                        <!-- Nama Produk -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Produk</label>
                            <input type="text" class="form-control" value="{{ $claim->nama_produk ?? '-' }}" disabled>
                        </div>


                        <!-- Category -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <input type="text" class="form-control" value="{{ $claim->category ?? '-' }}" disabled>
                        </div>

                        <!-- Submission Date -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Submission Date</label>
                            <input type="text" class="form-control"
                                value="{{ $claim->submission_date ? $claim->submission_date->format('d-m-Y') : '-' }}"
                                disabled>
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
                            <label class="form-label fw-bold">Status</label>
                            <div>
                                <span class="badge {{ $claim->status_badge }}"
                                    style="font-size: 18px;">{{ $claim->status_label }}</span>
                            </div>
                        </div>

                        <!-- Supplier -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Supplier</label>
                            <input type="text" class="form-control" value="{{ $claim->supplier ?? '-' }}" disabled>
                        </div>

                        <!-- Catatan Procurement -->
                        @if ($claim->catatan_procurement)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Catatan dari Procurement</label>
                                <textarea class="form-control" rows="3" disabled>{{ $claim->catatan_procurement }}</textarea>
                            </div>
                        @endif

                        <!-- File / Foto -->
                        <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                            <label class="form-label fw-bold text-primary">
                                <i class="fas fa-paperclip"></i> Foto / Bukti
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

                        <!-- Tombol -->
                        <div class="d-flex justify-content-between" style="margin-top: 3%">
                            <div>
                                <button type="button" class="btn btn-warning mb-4"
                                    onclick="toggleAccordion()">
                                    <i class="fas fa-eye"></i> Lihat Histori
                                </button>
                                <button type="button" class="btn btn-primary mb-4 ms-2"
                                    onclick="printClaimDetail()">
                                    <i class="fas fa-print"></i> Cetak Laporan
                                </button>
                            </div>
                            <div>
                                <a href="{{ route('claim.indexUser') }}" class="btn btn-secondary mb-4">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                        <!-- End Tombol -->
                    </div>
                </div>
            </div>
        </section>

        <!-- Accordion History -->
        <div class="accordion mt-4" id="accordionHistory">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#historyAccordion" aria-expanded="false" aria-controls="historyAccordion">
                        <i class="fas fa-history me-2"></i> Histori Claim Submission
                    </button>
                </h2>
                <div id="historyAccordion" class="accordion-collapse collapse" data-bs-parent="#accordionHistory">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover history-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="history-col-no">No</th>
                                        <th class="history-col-keterangan">Keterangan</th>
                                        <th>Status</th>
                                        <th>Oleh</th>
                                        <th id="historyDateHeader" style="cursor: pointer; user-select: none;" onclick="toggleHistorySort()" title="Klik untuk mengurutkan">
                                            Tanggal <span id="historyDateSortIcon">↓</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Klik tombol Lihat Histori untuk memuat data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            #accordionHistory .history-table {
                table-layout: fixed;
                width: 100%;
                margin-bottom: 0;
            }

            #accordionHistory .history-table th,
            #accordionHistory .history-table td {
                white-space: normal;
                word-break: break-word;
                overflow-wrap: anywhere;
                vertical-align: top;
                padding: 12px;
            }

            #accordionHistory .history-table tbody tr:hover {
                background-color: #f8f9fa;
            }

            #accordionHistory .history-keterangan-cell {
                max-width: 0;
            }

            #accordionHistory .history-table .history-col-no {
                width: 56px;
                text-align: center;
            }

            #accordionHistory .history-table .history-col-keterangan {
                width: 52%;
            }
        </style>

        <!-- Modal Preview PDF -->
        <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfPreviewModalLabel">Preview Laporan Claim Submission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <iframe id="pdfPreviewFrame" src="" style="width:100%;height:82vh;border:none;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="downloadPdfBtn">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- jQuery --}}
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        {{-- jsPDF CDN --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

        <script>
            // Inisialisasi jsPDF dari namespace UMD
            if (window.jspdf && window.jspdf.jsPDF) {
                window.jsPDF = window.jspdf.jsPDF;
            }

            function printClaimDetail() {
                var doc        = new jsPDF({ unit: 'mm', format: 'a4' });
                var pageWidth  = doc.internal.pageSize.getWidth();
                var pageHeight = doc.internal.pageSize.getHeight();
                var margin     = 15;
                var y          = 15;

                // ── HEADER: Logo + Nama Perusahaan ──────────────────────────
                try {
                    var logoImg        = new Image();
                    logoImg.crossOrigin = 'anonymous';
                    logoImg.src        = window.location.origin + '/assets/img/logo-adasi.png';
                    doc.addImage(logoImg, 'PNG', margin, y, 22, 22);
                } catch (e) { /* logo tidak tersedia, lanjut */ }

                var textX = margin + 26;
                doc.setFontSize(15);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(0, 0, 0);
                doc.text('PT. ASTRA DAIDO STEEL INDONESIA', textX, y + 7);

                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(60, 60, 60);
                doc.text('Kawasan Industri Delta Silicon 8', textX, y + 13);
                doc.text('Jl. Albasia Raya K.07 No. 003, Lippo Cikarang, Desa Cicau, Cikarang Pusat - Kab. Bekasi, 17816', textX, y + 18);
                doc.text('Telp. (+62) 21 3950 6699', textX, y + 23);

                y += 30;

                // Garis pemisah header
                doc.setDrawColor(0, 0, 0);
                doc.setLineWidth(0.5);
                doc.line(margin, y, pageWidth - margin, y);
                y += 12;

                // ── JUDUL ────────────────────────────────────────────────────
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(0, 0, 0);
                doc.text('LAPORAN CLAIM SUBMISSION', pageWidth / 2, y, { align: 'center' });
                y += 7;

                // Nomor dokumen & tanggal cetak
                var now      = new Date();
                var bulan    = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                var tglCetak = now.getDate() + ' ' + bulan[now.getMonth()] + ' ' + now.getFullYear();
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(80, 80, 80);
                doc.text(
                    'Tanggal: ' + tglCetak,
                    pageWidth / 2, y, { align: 'center' }
                );
                y += 12;

                // ── HELPER: Section Header ───────────────────────────────────
                function drawSectionHeader(title) {
                    if (y > pageHeight - 40) { doc.addPage(); y = 20; }
                    // Aksen abu-abu di kiri (sesuai template laporan supplier)
                    doc.setFillColor(150, 150, 150);
                    doc.rect(margin, y - 4, 0.5, 9, 'F');
                    doc.setFontSize(11);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(0, 0, 0);
                    doc.text(title, margin + 3.2, y + 2);
                    y += 10;
                }

                // ── HELPER: Field berkotak – label di atas, nilai di dalam box ─
                function drawField(label, value, x, fieldWidth) {
                    if (y > pageHeight - 30) { doc.addPage(); y = 20; }

                    var textVal  = String(value ?? '-');
                    var maxTextW = fieldWidth - 6;
                    var lines    = doc.splitTextToSize(textVal, maxTextW);
                    var boxH     = Math.max(9, lines.length * 5.5 + 4);

                    // Label
                    doc.setFontSize(8);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(40, 40, 40);
                    doc.text(label, x, y);
                    y += 4;

                    // Box
                    doc.setDrawColor(180, 180, 180);
                    doc.setLineWidth(0.3);
                    doc.setFillColor(255, 255, 255);
                    doc.roundedRect(x, y, fieldWidth, boxH, 1.5, 1.5, 'FD');

                    // Nilai
                    doc.setFontSize(9);
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(0, 0, 0);
                    doc.text(lines, x + 3, y + 5.5);

                    y += boxH + 5;
                }

                // ── HELPER: Dua field sejajar (2 kolom) ─────────────────────
                function drawFieldRow(label1, value1, label2, value2) {
                    if (y > pageHeight - 30) { doc.addPage(); y = 20; }

                    var colW   = (pageWidth - margin * 2 - 5) / 2;
                    var lines1 = doc.splitTextToSize(String(value1 ?? '-'), colW - 6);
                    var lines2 = doc.splitTextToSize(String(value2 ?? '-'), colW - 6);
                    var boxH   = Math.max(9, Math.max(lines1.length, lines2.length) * 5.5 + 4);

                    // Label kiri & kanan
                    doc.setFontSize(8);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(40, 40, 40);
                    doc.text(label1, margin, y);
                    doc.text(label2, margin + colW + 5, y);
                    y += 4;

                    // Box kiri
                    doc.setDrawColor(180, 180, 180);
                    doc.setLineWidth(0.3);
                    doc.setFillColor(255, 255, 255); // ← reset putih
                    doc.roundedRect(margin, y, colW, boxH, 1.5, 1.5, 'FD');
                    doc.setFontSize(9);
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(0, 0, 0);
                    doc.text(lines1, margin + 3, y + 5.5);

                    // Box kanan — WAJIB reset ulang sebelum draw
                    doc.setDrawColor(180, 180, 180);
                    doc.setLineWidth(0.3);
                    doc.setFillColor(255, 255, 255); // ← ini yang kurang, penyebab hitam
                    doc.roundedRect(margin + colW + 5, y, colW, boxH, 1.5, 1.5, 'FD');
                    doc.setFontSize(9);
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(0, 0, 0);
                    doc.text(lines2, margin + colW + 5 + 3, y + 5.5);

                    y += boxH + 5;
                }

                // ── SECTION: Informasi Claim ─────────────────────────────────
                drawSectionHeader('Informasi Claim');

                drawFieldRow(
                    'No. PR',
                    "{{ $claim->no_pr }}",
                    'Nama Produk',
                    "{{ $claim->nama_produk ?? '-' }}"
                );

                drawFieldRow(
                    'Kategori',
                    "{{ $claim->category ?? '-' }}",
                    'Tanggal Pengajuan',
                    "{{ $claim->submission_date ? $claim->submission_date->format('d-m-Y') : '-' }}"
                );

                drawField(
                    'Nama PIC',
                    "{{ $claim->modified_at ?? '-' }}",
                    margin,
                    pageWidth - margin * 2
                );

                drawField(
                    'Deskripsi Masalah',
                    `{{ str_replace(["\r", "\n"], [' ', ' '], $claim->description_of_issue) }}`,
                    margin,
                    pageWidth - margin * 2
                );

                drawField(
                    'Solusi yang Diusulkan',
                    `{{ str_replace(["\r", "\n"], [' ', ' '], $claim->proposed_solution) }}`,
                    margin,
                    pageWidth - margin * 2
                );

                drawFieldRow(
                    'Status',
                    "{{ $claim->status_label }}",
                    'Supplier',
                    "{{ $claim->supplier ?? '-' }}"
                );

                @if ($claim->catatan_procurement)
                // ── SECTION: Catatan Procurement ────────────────────────────
                drawSectionHeader('Catatan Procurement');
                drawField(
                    'Catatan dari Procurement',
                    `{{ str_replace(["\r", "\n"], [' ', ' '], $claim->catatan_procurement) }}`,
                    margin,
                    pageWidth - margin * 2
                );
                @endif
            
            function finalizePdf() {
                // Footer
                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(130, 130, 130);
                doc.text('Dicetak pada: ' + now.toLocaleString('id-ID'), margin, y + 5);

                // Preview di modal
                var pdfBlob = doc.output('bloburl');
                document.getElementById('pdfPreviewFrame').src = pdfBlob;
                var previewModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
                previewModal.show();

                document.getElementById('downloadPdfBtn').onclick = function () {
                    doc.save('Laporan Claim Submission_{{ $claim->id }}.pdf');
                };
            }

                @if ($claim->file_name)
                // ── SECTION: File / Bukti ────────────────────────────────────
                drawSectionHeader('File / Bukti');

                @php
                    $ext = strtolower(pathinfo($claim->file, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                @endphp

                @if ($isImage)
                    // Gambar: load lalu embed ke PDF
                    var imageUrl = "{{ asset($claim->file) }}";
                    var imgEl = new Image();
                    imgEl.crossOrigin = 'anonymous';
                    imgEl.onload = function () {
                        // Konversi ke base64 via canvas
                        var canvas = document.createElement('canvas');
                        canvas.width  = imgEl.naturalWidth;
                        canvas.height = imgEl.naturalHeight;
                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(imgEl, 0, 0);
                        var base64 = canvas.toDataURL('image/jpeg');

                        // Hitung dimensi agar proporsional & tidak melebihi lebar halaman
                        var maxW   = pageWidth - margin * 2;
                        var maxH   = 80; // tinggi maksimal gambar di PDF (mm)
                        var ratio  = imgEl.naturalWidth / imgEl.naturalHeight;
                        var imgW   = Math.min(maxW, maxH * ratio);
                        var imgH   = imgW / ratio;

                        if (y + imgH > pageHeight - 20) { doc.addPage(); y = 20; }

                        // Label
                        doc.setFontSize(8);
                        doc.setFont('helvetica', 'bold');
                        doc.setTextColor(40, 40, 40);
                        doc.text('Foto / Bukti', margin, y);
                        y += 4;

                        // Border box di sekitar gambar
                        doc.setDrawColor(180, 180, 180);
                        doc.setLineWidth(0.3);
                        doc.setFillColor(255, 255, 255);
                        doc.roundedRect(margin, y, imgW + 4, imgH + 4, 1.5, 1.5, 'FD');

                        // Embed gambar
                        doc.addImage(base64, 'JPEG', margin + 2, y + 2, imgW, imgH);
                        y += imgH + 10;

                        // Footer & preview setelah gambar selesai dimuat
                        finalizePdf();
                    };
                    imgEl.onerror = function () {
                        // Fallback jika gambar gagal dimuat
                        drawField('Foto / Bukti (gagal dimuat)', "{{ $claim->file_name }}", margin, pageWidth - margin * 2);
                        finalizePdf();
                    };
                    imgEl.src = imageUrl;
                @else
                    // Bukan gambar, tampilkan nama file saja
                    drawField('Nama File', "{{ $claim->file_name }}", margin, pageWidth - margin * 2);
                    finalizePdf();
                @endif
                @else
                    finalizePdf();
                @endif
            }

            // ── jQuery ready ─────────────────────────────────────────────────
            $(document).ready(function () {
                $('.nav-item.dropdown').hover(function () {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function () {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });

                var historyCollapseEl = document.getElementById('historyAccordion');
                if (historyCollapseEl) {
                    historyCollapseEl.addEventListener('shown.bs.collapse', function () {
                        if (historyRawData.length === 0) {
                            loadHistory({{ $claim->id }});
                        }
                    });
                }
            });

            // ── Show History ─────────────────────────────────────────────────
            let historySortOrder = 'desc';
            let historyRawData = [];

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

                const sortedData = [...historyRawData].sort(function (a, b) {
                    const tsA = parseHistoryDate(a.created_at);
                    const tsB = parseHistoryDate(b.created_at);
                    return historySortOrder === 'asc' ? tsA - tsB : tsB - tsA;
                });

                sortedData.forEach(function (item, index) {
                    const keterangan = formatHistoryText(item.keterangan);
                    const statusClass = getStatusBadgeClass(item.status);
                    const statusText = escapeHtml(item.status || '-');
                    const modifiedBy = escapeHtml(item.modified_at || '-');
                    const createdAt = escapeHtml(item.created_at || '-');
                    tbody.append(
                        '<tr>' +
                        '<td class="history-col-no">' + (index + 1) + '</td>' +
                        '<td class="history-keterangan-cell">' + keterangan + '</td>' +
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

            function toggleAccordion() {
                const collapseDiv = document.getElementById('historyAccordion');
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseDiv, { toggle: false });
                const isOpen = collapseDiv.classList.contains('show');
                const claimId = {{ $claim->id }};

                if (!isOpen) {
                    if (historyRawData.length === 0) {
                        loadHistory(claimId);
                    }
                    bsCollapse.show();
                } else {
                    bsCollapse.hide();
                }
            }

            function loadHistory(id) {
                if (historyRawData.length > 0) {
                    return;
                }

                const tbody = $('#historyTableBody');
                tbody.empty();
                tbody.append('<tr><td colspan="5" class="text-center text-muted">Memuat histori...</td></tr>');

                $.ajax({
                    url: '{{ route('claim.history', $claim->id) }}',
                    type: 'GET',
                    success: function (data) {
                        historyRawData = Array.isArray(data) ? data : [];
                        historySortOrder = 'desc';
                        $('#historyDateSortIcon').text('↓');
                        renderHistoryRows();
                    },
                    error: function () {
                        tbody.empty();
                        tbody.append('<tr><td colspan="5" class="text-center text-danger">Gagal memuat histori</td></tr>');
                    }
                });
            }
        </script>

    </main>
@endsection
