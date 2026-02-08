@extends('layout')

@section('content')
{{-- CSS Kustom untuk Halaman Ini --}}
<style>
    /* Timeline untuk Log Aktivitas */
    .timeline { list-style: none; padding: 0; position: relative; }
    .timeline::before { content: ''; position: absolute; top: 0; bottom: 0; width: 4px; background: #e9ecef; left: 31px; margin: 0; }
    .timeline-item { margin-bottom: 20px; position: relative; }
    .timeline-item::after, .timeline-item::before { content: ""; display: table; clear: both; }
    .timeline-icon { position: absolute; top: 0; left: 15px; width: 32px; height: 32px; border-radius: 50%; background: #6c757d; color: #fff; display: flex; align-items: center; justify-content: center; z-index: 1; }
    .timeline-body { margin-left: 60px; background: #f8f9fa; border-radius: 0.5rem; padding: 15px; position: relative; }
    .timeline-body::before { content: ''; position: absolute; top: 10px; left: -10px; border-style: solid; border-width: 10px 10px 10px 0; border-color: transparent #f8f9fa transparent transparent; }

    /* Styling untuk Tampilan Detail Data */
    .detail-item { margin-bottom: 0.8rem; }
    .detail-label { font-weight: 600; color: #444; }
    .detail-value { color: #212529; }

    .status-text {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .status-text.approved { color: #059669; }
        .status-text.rejected { color: #dc2626; }
    
    /* CSS untuk Collapse Log */
    .log-collapse-trigger {
        cursor: pointer;
        display: block;
        text-decoration: none;
        color: #212529;
    }
    .log-collapse-trigger .icon-toggle {
        transition: transform 0.3s ease-in-out;
    }
    /* Putar ikon saat collapse terbuka */
    .log-collapse-trigger[aria-expanded="true"] .icon-toggle {
        transform: rotate(180deg);
    }

    /* Print Styles */
    @media print {
        body * { visibility: hidden; }
        #printableArea, #printableArea * { visibility: visible; }
        #printableArea { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    }

    .badge-pending {
        background-color: #ffc107; /* kuning */
        color: #212529;
    }
    .badge-approved {
        background-color: #28a745; /* hijau */
    }
    .badge-rejected {
        background-color: #dc3545; /* merah */
    }
</style>

<main id="main" class="main">
    <div class="pagetitle no-print">
        <h1>Detail Form Supplier</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('supplierform.index') }}">Supplier</a></li>
                <li class="breadcrumb-item active">Detail Form</li>
            </ol>
        </nav>
    </div><section class="section">
        <div class="row">
            <div class="col-lg-8">
                <div id="printableArea">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <a href="{{ route('supplierform.index') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-2"></i> Keluar</a>
                                    <h4 class="fw-bold mb-1">{{ $form->supplier->supplier_name ?? 'Nama Supplier Belum Ada' }}</h4>
                                    <p class="text-muted mb-0">{{ $form->supplier->supplier_kode }}</p>
                                </div>

                                <div>
                                    @if($form->trial_file)
                                        <a href="{{ route('supplierform.download', $form->id) }}" 
                                           class="btn btn-sm btn-success" 
                                           title="Lihat File Visit"
                                           target="_blank">
                                            <i class="fas fa-eye fa-fw"></i> Lihat Trial
                                        </a>
                                    @endif
                                    

                                    <a href="{{ route('supplierform.print', $form->mst_supplier) }}" class="btn btn-outline-primary no-print ms-2" target="_blank">
                                        <i class="fas fa-print me-2"></i> Cetak Laporan
                                    </a>
                                </div>
                            </div>
                            <hr>

                            <h5 class="fw-semibold mb-3">1. Informasi Dasar Perusahaan</h5>
                            <div class="row">
                                <div class="col-md-12 detail-item"><span class="detail-label">Alamat:</span> <span class="detail-value">{{ $form->supplier->alamat ?? '-' }}</span></div>
                                <div class="col-md-6 detail-item"><span class="detail-label">Telepon:</span> <span class="detail-value">{{ $form->supplier->telp ?? '-' }}</span></div>
                                <div class="col-md-6 detail-item"><span class="detail-label">NPWP:</span> <span class="detail-value">{{ $form->supplier->npwp ?? '-' }}</span></div>
                                <div class="col-md-6 detail-item"><span class="detail-label">Pimpinan:</span> <span class="detail-value">{{ $form->supplier->director ?? '-' }}</span></div>
                                <div class="col-md-6 detail-item"><span class="detail-label">PIC/Marketing:</span> <span class="detail-value">{{ $form->supplier->pic ?? '-' }}</span></div>
                            </div>
                            <hr>

                            <h5 class="fw-semibold mb-3">2. Jawaban Kuesioner</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">Memiliki standar kualitas? <span class="badge bg-secondary">{{ $form->supplier->has_quality_standard ? 'Ya' : 'Tidak' }}</span></li>
                                @if($form->supplier->has_quality_standard)
                                <li class="list-group-item ps-4"><small><strong>Jenis Sertifikat:</strong> {{ $form->supplier->quality_certificate ?: '-' }}</small></li>
                                <li class="list-group-item ps-4"><small><strong>Dikeluarkan oleh:</strong> {{ $form->supplier->quality_certificate_from ?: '-' }}</small></li>
                                @endif

                                <li class="list-group-item d-flex justify-content-between align-items-center">Memiliki penanggung jawab kualitas? <span class="badge bg-secondary">{{ $form->supplier->has_quality_responsible ? 'Ya' : 'Tidak' }}</span></li>
                                @if($form->supplier->has_quality_responsible)
                                <li class="list-group-item ps-4"><small><strong>Nama PIC Kualitas:</strong> {{ $form->supplier->quality_responsible_name ?: '-' }}</small></li>
                                @endif
                                
                                <li class="list-group-item d-flex justify-content-between align-items-center">Memiliki MSDS? <span class="badge bg-secondary">{{ $form->supplier->has_material_safety ? 'Ya' : 'Tidak' }}</span></li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">Produk/Jasa terjamin aman? <span class="badge bg-secondary">{{ $form->supplier->has_safety ? 'Ya' : 'Tidak' }}</span></li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">Mempekerjakan anak di bawah umur? <span class="badge bg-secondary">{{ $form->supplier->employs_underage ? 'Ya' : 'Tidak' }}</span></li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">Membayar upah sesuai UMR? <span class="badge bg-secondary">{{ $form->supplier->pays_min_wage ? 'Ya' : 'Tidak' }}</span></li>
                            </ul>
                            <hr>

                            <h5 class="fw-semibold mb-3">3. Dokumen Pendukung</h5>
                                @php
                                    // Definisikan semua file yang ingin ditampilkan dalam sebuah array
                                    $supplierFiles = [
                                        ['type' => 'npwp',  'column' => 'npwp_file',  'label' => 'NPWP'],
                                        ['type' => 'sppkp', 'column' => 'sppkp_file', 'label' => 'SPPKP'],
                                        ['type' => 'nib',   'column' => 'nib_file',   'label' => 'NIB'],
                                        ['type' => 'rek',   'column' => 'rek_bank',   'label' => 'Rek'],
                                    ];
                                @endphp

                                @foreach ($supplierFiles as $file)
                                    <a href="{{ route('supplier.file.download', [$form->supplier->id, $file['type']]) }}"
                                    class="btn btn-sm btn-outline-secondary me-2 {{ !$form->supplier->{$file['column']} ? 'disabled' : '' }}"
                                    target="_blank">
                                        Lihat {{ $file['label'] }}
                                    </a>
                                @endforeach
                        </div>
                    </div>
                </div>

                <div class="card no-print">
                    <div class="card-body p-4">
                        <h5 class="fw-semibold mb-3">Tindakan Lanjutan</h5>
                        <hr>
                        {{-- tetapkan form/status 1 & 2 persis seperti semula --}}
                        @if($form->status == 1)
                            <form action="{{ route('supplierform.update.category', $form->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="mb-3">
                                    <label for="kategori" class="form-label">Input Kategori Supplier</label>
                                    <input type="text" name="kategori" id="kategori" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type Supplier</label>
                                    <select class="form-select" name="type" id="type" required>
                                        <option value="">-- Pilih Type --</option>
                                        <option value="Trade">Trade</option>
                                        <option value="Non Trade">Non Trade</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan & Lanjutkan</button>
                            </form>
                        @endif

                        @if($form->status == 2)
                            <div class="d-flex gap-2 align-items-start">
                                <form action="{{ route('supplierform.choose.action', $form->id) }}" method="POST" class="flex-grow-1">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label d-block">Pilih Tindakan Selanjutnya</label>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="tindakan" value="visit" required><label class="form-check-label">Hanya Visit</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="tindakan" value="trial"><label class="form-check-label">Hanya Trial</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="tindakan" value="visit_trial"><label class="form-check-label">Visit dan Trial</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="tindakan" value="none"><label class="form-check-label">Tidak Keduanya (Lanjut Persetujuan)</label></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Simpan Pilihan</button>
                                </form>
                            </div>
                        @endif

                        {{-- ========================= VISIT CARD ========================= --}}
                        @if(in_array($form->status, [3,4,5]))
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="fw-semibold mb-3">Visit</h5>

                                {{-- Status 3 --}}
                                @if($form->status == 3)
                                    @if($form->visit)
                                        @if(is_null($form->visit_schedule))
                                            <form action="{{ route('supplierform.schedule.visit', $form->id) }}" method="POST">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="visit_schedule" class="form-label">Jadwalkan Visit</label>
                                                    <input type="date" class="form-control" name="visit_schedule" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Simpan Jadwal Visit</button>
                                            </form>
                                        @elseif(!$form->visit_file)
                                            <div class="alert alert-info">
                                                Visit dijadwalkan pada <strong>{{ \Carbon\Carbon::parse($form->visit_schedule)->format('d M Y') }}</strong>.
                                            </div>
                                            <a href="{{ route('assessment.visit.create', $form->id) }}" class="btn btn-success">
                                                <i class="bi bi-file-earmark-text"></i> Laporan Visit
                                            </a>
                                        @endif
                                    @endif
                                @endif

                                {{-- Status 3–5: edit laporan jika sudah ada --}}
                                @if(in_array($form->status, [3,4,5]) && $form->visit == 1 && $form->visit_file)
                                    <a href="{{ route('assessment.visit.edit', $form->id) }}" class="btn btn-success mt-2">
                                        <i class="bi bi-file-earmark-text"></i> Laporan Visit
                                    </a>
                                @endif

                                {{-- Approve/Reject Visit --}}
                                @if($form->mst_visit && is_null($form->visit_approval))
                                    <div class="mt-3">
                                        <button class="btn btn-success btn-sm open-approval-modal"
                                                data-type="visit"
                                                data-action="approve"
                                                data-url="{{ route('supplier.visit.approval', $form->id) }}">
                                            Approve Visit
                                        </button>
                                        <button class="btn btn-danger btn-sm open-approval-modal"
                                                data-type="visit"
                                                data-action="reject"
                                                data-url="{{ route('supplier.visit.approval', $form->id) }}">
                                            Reject Visit
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ========================= TRIAL CARD ========================= --}}
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="fw-semibold mb-3">Trial</h5>

                                {{-- Status 4: jadwal & tombol modal upload --}}
                                @if($form->status == 4 && $form->trial || $form->status == 5 && $form->trial && is_null($form->trial_file))
                                    @if(is_null($form->trial_schedule))
                                        <form action="{{ route('supplierform.schedule.trial', $form->id) }}" method="POST">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="trial_schedule" class="form-label">Jadwalkan Trial</label>
                                                <input type="date" class="form-control" name="trial_schedule" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Simpan Jadwal Trial</button>
                                        </form>
                                    @elseif(!$form->trial_file)
                                        <div class="alert alert-info">
                                            Trial dijadwalkan pada <strong>{{ \Carbon\Carbon::parse($form->trial_schedule)->format('d M Y') }}</strong>.
                                        </div>
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#trialUploadModal">
                                            <i class="bi bi-upload"></i> Unggah Bukti Trial
                                        </button>
                                    @endif
                                @endif

                                {{-- Status 5: sudah ada file, tampil + tombol modal edit --}}
                                @if($form->status == 5 && $form->trial_file)
                                    <div class="alert alert-success">
                                        Trial selesai pada <strong>{{ \Carbon\Carbon::parse($form->trial_actual)->format('d M Y') }}</strong>.
                                    </div>
                                    <a href="{{ route('supplierform.download.trial', $form->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark"></i> Lihat File
                                    </a>

                                    <form action="{{ route('assessment.trial.delete', $form->id) }}" method="POST" class="d-inline ms-2"
                                        onsubmit="return confirm('Yakin ingin menghapus file bukti trial?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                    <button class="btn btn-warning btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#trialEditModal">
                                        <i class="bi bi-pencil-square"></i> Edit Bukti Trial
                                    </button>
                                @endif

                                {{-- Approve/Reject Trial --}}
                                @if($form->trial_file && is_null($form->trial_approval))
                                    <div class="mt-3">
                                        <button class="btn btn-success btn-sm open-approval-modal"
                                                data-type="trial"
                                                data-action="approve"
                                                data-url="{{ route('supplier.trial.approval', $form->id) }}">
                                            Approve Trial
                                        </button>
                                        <button class="btn btn-danger btn-sm open-approval-modal"
                                                data-type="trial"
                                                data-action="reject"
                                                data-url="{{ route('supplier.trial.approval', $form->id) }}">
                                            Reject Trial
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ========================= MODAL UPLOAD TRIAL ========================= --}}
                        <div class="modal fade" id="trialUploadModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="{{ route('assessment.trial.store', $form->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Unggah Bukti Trial</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="trial_actual" class="form-label"><strong>Tanggal Trial</strong></label>
                                                <input type="date" class="form-control" name="trial_actual" id="trial_actual" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="trial_file" class="form-label"><strong>File Bukti Trial</strong></label>
                                                <input type="file" class="form-control" name="trial_file" id="trial_file" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-success">Unggah & Selesaikan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- ========================= MODAL EDIT TRIAL ========================= --}}
                        <div class="modal fade" id="trialEditModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="{{ route('assessment.trial.update', $form->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Bukti Trial</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="trial_actual_edit" class="form-label"><strong>Tanggal Trial</strong></label>
                                                <input type="date" class="form-control" name="trial_actual" id="trial_actual_edit"
                                                    value="{{ old('trial_actual', $form->trial_actual) }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="trial_file_edit" class="form-label"><strong>File Bukti Trial (Opsional)</strong></label>
                                                <input type="file" class="form-control" name="trial_file" id="trial_file_edit">
                                                <div class="form-text">Kosongkan jika tidak ingin mengganti file.</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-warning">Update Bukti Trial</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Final approval tetap di bawah (tidak diubah) --}}
                        @if($form->status == 5)
                            <div class="alert alert-warning">Semua proses telah selesai. Mohon berikan persetujuan akhir.</div>
                            <div class="d-flex gap-2">
                                <form action="{{ route('supplierform.approve', $form->id) }}" method="POST">@csrf @method('PATCH')<button type="submit" class="btn btn-success">Setuju</button></form>
                                <form action="{{ route('supplierform.disapprove', $form->id) }}" method="POST">@csrf @method('PATCH')<button type="submit" class="btn btn-danger">Tolak (Reject)</button></form>
                            </div>
                        @endif

                        @if($form->status == 6 && $form->trial == 1 && $form->trial_file)
                            <a href="{{ route('supplierform.download.trial', $form->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-file-earmark"></i> Lihat File Trial
                            </a>
                        @endif
                        
                        

                    </div>
                </div>

            </div>

            

            

            <div class="col-lg-4 no-print">
                <!-- Card Status Approval -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="fw-semibold mb-0">Status Approval</h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Status Visit -->
                        <div class="mb-4">
                            @if($form->visit == 1)
                                <h6 class="fw-semibold">Visit</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2">Status:</span>
                                    @if(is_null($form->visit_approval))
                                        <span class="badge badge-pending">Belum Diproses</span>
                                    @elseif($form->visit_approval == 1)
                                        <span class="badge badge-approved">Disetujui</span>
                                    @else
                                        <span class="badge badge-rejected">Ditolak</span>
                                    @endif
                                </div>
                                @if(!is_null($form->visit_approval) && $form->visit_ket)
                                    <div class="mt-2">
                                        <small class="text-muted">Keterangan:</small>
                                        <p class="mb-0">{{ $form->visit_ket }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                        
                        <!-- Status Trial -->
                        <div>
                            @if($form->trial == 1)
                                <h6 class="fw-semibold">Trial</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2">Status:</span>
                                    @if(is_null($form->trial_approval))
                                        <span class="badge badge-pending">Belum Diproses</span>
                                    @elseif($form->trial_approval == 1)
                                        <span class="badge badge-approved">Disetujui</span>
                                    @else
                                        <span class="badge badge-rejected">Ditolak</span>
                                    @endif
                                </div>
                                @if(!is_null($form->trial_approval) && $form->trial_ket)
                                    <div class="mt-2">
                                        <small class="text-muted">Keterangan:</small>
                                        <p class="mb-0">{{ $form->trial_ket }}</p>
                                    </div>
                                @endif
                            @endif

                            @if($form->status == 6)
                        <div class="section approval-section">
                                <div class="section-title">Hasil Akhir Evaluasi</div>
                                <div class="approval-result">
                                    @if(!empty($form->supplier_kode))
                                        <div class="status-text approved">DISETUJUI</div>
                                        <p class="description">Supplier telah memenuhi kriteria dan berhasil disetujui.</p>
                                    @else
                                        <div class="status-text rejected">DITOLAK</div>
                                        <p class="description">Berdasarkan hasil evaluasi, supplier ini belum memenuhi kriteria.</p>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <a href="#activityLogCollapse" 
                           data-bs-toggle="collapse" 
                           role="button" 
                           aria-expanded="false" 
                           aria-controls="activityLogCollapse"
                           class="log-collapse-trigger d-flex justify-content-between align-items-center">
                        
                           <h5 class="fw-semibold mb-0">Log Aktivitas</h5>
                           <i class="fas fa-chevron-down icon-toggle"></i>
                        </a>
                    </div>
                    
                    <div class="collapse" id="activityLogCollapse">
                        <div class="card-body p-4">
                            <ul class="timeline">
                                @forelse($form->logs as $log)
                                <li class="timeline-item">
                                    <div class="timeline-icon bg-primary"><i class="fas fa-info"></i></div>
                                    <div class="timeline-body">
                                        <div class="fw-bold">{{ $log->keterangan }}</div>
                                        <div class="text-muted small">{{ $log->created_at->format('d M Y, H:i') }}</div>
                                        @if(isset($log->status_from) && isset($log->status_to))
                                            <div class="small">Status: <strong>{{ $log->status_from }} &rarr; {{ $log->status_to }}</strong></div>
                                        @endif
                                    </div>
                                </li>
                                @empty
                                <li class="timeline-item">
                                    <div class="timeline-icon"><i class="fas fa-times"></i></div>
                                    <div class="timeline-body">Belum ada aktivitas tercatat.</div>
                                </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="approvalModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" id="approvalForm">
                    @csrf
                    <input type="hidden" name="visit_approval" id="visitApprovalInput">
                    <input type="hidden" name="trial_approval" id="trialApprovalInput">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approvalModalTitle"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label id="approvalLabel"></label>
                            <textarea class="form-control" name="" id="approvalTextarea" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Kirim</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

@endsection

    {{-- 1. Muat jQuery (diperlukan untuk script custom Anda) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    {{-- 2. Muat Bootstrap JS Bundle (PENTING untuk fitur seperti collapse/modal) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- 3. Semua script custom Anda digabung di sini agar rapi --}}

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
        

        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
            const form = document.getElementById('approvalForm');
            const visitInput = document.getElementById('visitApprovalInput');
            const trialInput = document.getElementById('trialApprovalInput');
            const textarea = document.getElementById('approvalTextarea');
            const title = document.getElementById('approvalModalTitle');
            const label = document.getElementById('approvalLabel');

            document.querySelectorAll('.open-approval-modal').forEach(btn => {
                btn.addEventListener('click', function () {
                    const type = this.dataset.type;      // visit / trial
                    const action = this.dataset.action;  // approve / reject
                    const url = this.dataset.url;

                    // reset input
                    visitInput.name = "";
                    trialInput.name = "";
                    textarea.name = "";

                    // Tentukan judul & label
                    title.textContent = (action === "approve" ? "Approve " : "Reject ") + type.charAt(0).toUpperCase() + type.slice(1);
                    label.textContent = (action === "approve" ? "Keterangan" : "Alasan Reject");

                    // Set form action
                    form.action = url;

                    // Set hidden input & textarea sesuai tipe
                    if (type === "visit") {
                        visitInput.name = "visit_approval";
                        visitInput.value = (action === "approve" ? 1 : 0);
                        textarea.name = "visit_ket";
                    } else {
                        trialInput.name = "trial_approval";
                        trialInput.value = (action === "approve" ? 1 : 0);
                        textarea.name = "trial_ket";
                    }

                    // Kosongkan textarea tiap kali modal dibuka
                    textarea.value = "";

                    // Buka modal
                    modal.show();
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
            const form = document.getElementById('approvalForm');
            const visitInput = document.getElementById('visitApprovalInput');
            const trialInput = document.getElementById('trialApprovalInput');
            const textarea = document.getElementById('approvalTextarea');
            const title = document.getElementById('approvalModalTitle');
            const label = document.getElementById('approvalLabel');

            document.querySelectorAll('.open-approval-modal').forEach(btn => {
                btn.addEventListener('click', function () {
                    const type = this.dataset.type;      // visit / trial
                    const action = this.dataset.action;  // approve / reject
                    const url = this.dataset.url;

                    // reset input
                    visitInput.name = "";
                    trialInput.name = "";
                    textarea.name = "";

                    // Tentukan judul & label
                    title.textContent = (action === "approve" ? "Approve " : "Reject ") + type.charAt(0).toUpperCase() + type.slice(1);
                    label.textContent = (action === "approve" ? "Keterangan" : "Alasan Reject");

                    // Set form action
                    form.action = url;

                    // Set hidden input & textarea sesuai tipe
                    if (type === "visit") {
                        visitInput.name = "visit_approval";
                        visitInput.value = (action === "approve" ? 1 : 0);
                        textarea.name = "visit_ket";
                    } else {
                        trialInput.name = "trial_approval";
                        trialInput.value = (action === "approve" ? 1 : 0);
                        textarea.name = "trial_ket";
                    }

                    // Kosongkan textarea tiap kali modal dibuka
                    textarea.value = "";

                    // Buka modal
                    modal.show();
                });
            });
        });
    </script>

@section('styles')
    {{-- Muat FontAwesome CDN untuk ikon --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">