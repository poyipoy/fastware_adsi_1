@extends('layout')

@section('content')

    
    <main id="main" class="main">


        <style>
    body {
      background-color: #edf2f7;
      color: #1e293b;
      font-family: 'Inter', sans-serif, system-ui, -apple-system, BlinkMacSystemFont,
        "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      padding: 2rem 1rem;
    }
    h1 {
      font-weight: 800;
      font-size: 1.5rem;
    }
    .record-id {
      color: #64748b;
      font-size: 0.875rem;
      margin-top: 0.25rem;
    }
    .card-custom {
      background: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      overflow-x: auto;
      height: 1000px;
      display: flex;
      flex-direction: column;
    }
    .card-custom1 {
      background: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      overflow-x: auto;
      display: flex;
      flex-direction: column;
    }
    .badge-active {
      background-color: #d1fae5;
      color: #065f46;
      font-size: 0.625rem;
      font-weight: 600;
      padding: 0.15rem 0.5rem;
      border-radius: 9999px;
      white-space: nowrap;
    }
    .badge-completed {
      background-color: #bfdbfe;
      color: #1e40af;
      font-size: 0.625rem;
      font-weight: 600;
      padding: 0.15rem 0.5rem;
      border-radius: 9999px;
      white-space: nowrap;
    }
    table th,
    table td {
      vertical-align: middle !important;
      white-space: nowrap;
    }
    table thead th {
      background-color: #f1f5f9;
      font-weight: 600;
      color: #1e293b;
      border: 1px solid #e2e8f0 !important;
    }
    table tbody td {
      border: 1px solid #e2e8f0 !important;
      color: #1e293b;
      font-weight: 600;
    }
    .badge-pending {
      background-color: #fef3c7;
      color: #92400e;
      font-size: 0.625rem;
      font-weight: 600;
      padding: 0.15rem 0.5rem;
      border-radius: 9999px;
      white-space: nowrap;
    }
    .badge-approved {
      background-color: #d1fae5;
      color: #065f46;
      font-size: 0.625rem;
      font-weight: 600;
      padding: 0.15rem 0.5rem;
      border-radius: 9999px;
      white-space: nowrap;
    }
    .file-list-item {
      border: 1px solid #e2e8f0;
      border-radius: 0.375rem;
      padding: 0.75rem 1rem;
      margin-bottom: 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .file-info {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .file-name {
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 0;
    }
    .file-meta {
      font-size: 0.75rem;
      color: #94a3b8;
      margin-bottom: 0;
    }
    .activity-log {
      overflow-y: auto;
      flex-grow: 1;
    }
    .activity-item {
      margin-bottom: 1.5rem;
      color: #475569;
      font-size: 0.875rem;
    }
    .activity-title {
      font-weight: 600;
      color: #1e293b;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.25rem;
    }
    .activity-badge {
      width: 0.75rem;
      height: 0.75rem;
      border-radius: 50%;
      display: inline-block;
    }
    .badge-blue {
      background-color: #2563eb;
    }
    .badge-green {
      background-color: #16a34a;
    }
    .badge-purple {
      background-color: #8b5cf6;
    }
    .badge-orange {
      background-color: #d97706;
    }
    .activity-date {
      font-size: 0.75rem;
      color: #94a3b8;
      margin-left: 0.5rem;
      font-weight: 400;
    }
    .activity-desc strong {
      font-weight: 700;
      color: #1e293b;
    }
    .code-block {
      background-color: #f1f5f9;
      border-radius: 0.375rem;
      padding: 0.5rem 0.75rem;
      font-family: monospace;
      font-size: 0.75rem;
      color: #475569;
      margin-top: 0.25rem;
      display: flex;
      justify-content: space-between;
    }
    .code-block .old {
      color: #ef4444;
    }
    .code-block .new {
      color: #22c55e;
    }
    .btn-blue {
      background-color: #2563eb;
      color: white;
      font-weight: 600;
      padding: 0.375rem 1rem;
      border-radius: 0.375rem;
      border: none;
    }
    .btn-blue:hover {
      background-color: #1e40af;
      color: white;
    }
    .icon-btn {
      background: none;
      border: none;
      color: #64748b;
      font-size: 1rem;
      cursor: pointer;
      padding: 0 0.25rem;
    }
    .icon-btn:hover {
      color: #2563eb;
    }
    .icon-btn.cancel:hover {
      color: #ef4444;
    }
    .btn-modal {
        background-color: #007bff; /* Warna latar belakang biru */
        color: white; /* Warna teks putih */
        font-weight: 600; /* Tebal untuk teks */
        padding: 0.5rem 1.5rem; /* Padding yang nyaman */
        border: none; /* Hilangkan border default */
        border-radius: 0.375rem; /* Rounded corners */
        transition: background-color 0.3s, transform 0.2s; /* Transisi untuk hover */
        cursor: pointer; /* Pointer cursor untuk menunjukkan interaksi */
    }

  </style>

        <div class="pagetitle">
            <h1>Halaman View Data</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('indexSales') }}">Menu Pengajuan Custom</a></li>
                    <li class="breadcrumb-item active">Halaman View Data</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <section class="card-custom1 mb-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 text-nowrap">
                            <thead>
                                <tr>
                                    <th>PIC</th>
                                    <th>Nama Customer</th>
                                    <th>Nama Project</th>
                                    <th>Keterangan</th>
                                    <th>Jenis Proses</th>
                                    <th>Tgl Pengajuan</th>
                                    <th>Status</th>
                                    <th>Cost Process</th>
                                    <th>Selling Price</th>
                                    <th>Profit (%)</th>
                                    <th>Custom</th>
                                    <th>Marketing Dept Head</th>
                                    <th>Marketing Approval</th>
                                    <th>Finance Dept Head</th>
                                    <th>Finance Approval</th>
                                    <th>Quotation Subcont</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $materials->modified_at ? $materials->modified_at : '' }}</td>
                                    <td>{{ $materials->nama_customer }}</td>
                                    <td>{{ $materials->nama_project }}</td>
                                    <td>{{ $materials->keterangan }}</td>
                                    <td>
                                        {{ $materials->jenis_proses_subcont !== 'Null' ? $materials->jenis_proses_subcont : '' }}
                                    </td>
                                    <td>{{ $materials->created_at->format('d-m-Y') }}</td>
                                    <td class="text-center">
                                                    @php
                                                        $statusClasses = [
                                                            1 => ['bg' => 'bg-secondary', 'label' => 'Draft'], // Abu-abu
                                                            2 => ['bg' => 'bg-primary', 'label' => 'Open'], // Hijau
                                                            3 => ['bg' => 'bg-warning', 'label' => 'On Progress'], // Kuning
                                                            4 => ['bg' => 'bg-warning', 'label' => 'On Progress'], // Kuning (sama dengan status 3)
                                                            5 => ['bg' => 'bg-info', 'label' => 'Finish'], // Biru Muda
                                                        ];

                                                        // Menentukan status saat ini berdasarkan sec_line
                                                        if ($materials->sec_line == 1) {
                                                            // Jika sec_line == 1
                                                            switch ($materials->status_1) {
                                                                case 1:
                                                                    $currentStatus = $statusClasses[1]; // Draf
                                                                    break;
                                                                case 2:
                                                                    $currentStatus = $statusClasses[2]; // Open
                                                                    break;
                                                                case 3:
                                                                    $currentStatus = $statusClasses[3]; // On Progress
                                                                    break;
                                                                case 4:
                                                                    $currentStatus = $statusClasses[4]; // On Progress
                                                                    break;
                                                                case 5:
                                                                    $currentStatus = $statusClasses[5]; // Finish
                                                                    break;
                                                                default:
                                                                    $currentStatus = ['bg' => 'bg-danger', 'label' => 'Status Tidak Tersedia'];
                                                            }
                                                        } else {
                                                            // Jika sec_line == 2
                                                            switch ($materials->status_1) {
                                                                case 1:
                                                                case 2:
                                                                    $currentStatus = $statusClasses[2]; // Open
                                                                    break;
                                                                case 3:
                                                                    $currentStatus = $statusClasses[3]; // On Progress
                                                                    break;
                                                                case 4:
                                                                    $currentStatus = $statusClasses[4]; // On Progress
                                                                    break;
                                                                case 5:
                                                                    $currentStatus = $statusClasses[5]; // Finish
                                                                    break;
                                                                default:
                                                                    $currentStatus = ['bg' => 'bg-danger', 'label' => 'Status Tidak Tersedia'];
                                                            }
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $currentStatus['bg'] }}" style="font-size: 14px;">
                                                        {{ $currentStatus['label'] }}
                                                    </span>
                                                </td>
                                    <td>{{ $materials->harga_awal }}</td>
                                    <td>{{ $materials->harga_akhir }}</td>
                                    <td class="text-center profit-cell" data-harga-awal="{{ $materials->harga_awal }}" data-harga-akhir="{{ $materials->harga_akhir }}"></td>
                                    <td>{{ $materials->production ? $materials->production->name : '' }}</td>
                                    <td>{{ $materials->marketing ? $materials->marketing->name : '' }}</td>
                                    <td>{{ $materials->date_app_1 ? $materials->date_app_1 : '' }}</td>
                                    <td>{{ $materials->finance ? $materials->finance->name : '' }}</td>
                                    <td>{{ $materials->date_app_2 ? $materials->date_app_2 : '' }}</td>
                                    <td class="text-center align-middle">
                                        @if ($materials->quotation_file)
                                            <a href="{{ asset($materials->quotation_file) }}" target="_blank" 
                                               class="d-inline-block p-3 bg-white rounded border shadow-sm text-decoration-none" 
                                               style="color: inherit; transition: transform 0.2s ease;" 
                                               onmouseover="this.style.transform='scale(1.05)'" 
                                               onmouseout="this.style.transform='scale(1)'"
                                               data-bs-toggle="tooltip" title="Click to view or download the quotation file">
                                                <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                                                <p class="mb-0 fw-bold">View Quotation</p>
                                            </a>
                                        @else
                                            <span class="text-muted fst-italic">Quotation belum tersedia</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p></p>

                        <button type="button" class="btn btn-modal" onclick="window.history.back();">
                            Kembali
                        </button>
                      
                      {{-- @if ($materials->status_1 == 2)
                        <button type="button" class="btn btn-modal" data-bs-toggle="modal" data-bs-target="#inputDataModal">
                            Input
                        </button>
                      @endif --}}

                      {{-- @if ($materials->status_1 == 3)
                        <button type="button" class="btn btn-modal" data-bs-toggle="modal" data-bs-target="#inputDataModal1">
                            Input Data
                        </button>
                      @endif --}}
                    @if($materials->status_1 == 2)
                        <button type="button" class="btn btn-success" id="productionButton" data-id="{{ $materials->id }}">
                            <i class="fas fa-paper-plane"></i> Submit
                        </button>
                    @endif
                    @if (!is_null($pengajuan->harga_akhir) && $pengajuan->harga_akhir !== '')
                        <button type="button" class="btn btn-success marketingButton" data-id="{{ $pengajuan->id }}" data-harga-awal="{{ $pengajuan->harga_awal }}" data-harga-akhir="{{ $pengajuan->harga_akhir }}">
                            <i class="fas fa-paper-plane"></i> Submit
                        </button>
                    @endif

                      {{-- @if ($materials->status_1 == 1)
                        <button type="button" class="btn btn-success" id="productionButton" data-id="{{ $materials->id }}">
                            <i class="fas fa-paper-plane"></i> Submit
                        </button>
                      @endif --}}
                      </div>
                </section>

                <section class="d-flex gap-4" style="height: 1000px;">
                    <!-- Files Management -->
                    <article class="card-custom flex-fill d-flex flex-column">
                      <h2 class="h6 mb-3 d-flex align-items-center gap-2 flex-shrink-0">
                          <i class="fas fa-file-alt text-primary"></i> Upload File dan Quotation
                      </h2>
                      @if ($materials->status_1 == 2 || $materials->status_1 == 1)
                        <div class="border border-dashed border-secondary rounded p-5 text-center text-secondary mb-4 flex-shrink-0">
                            <i class="fas fa-cloud-upload-alt fs-2 mb-3"></i>
                            <p class="mb-3">Upload File Here</p>
                            <button class="btn btn-primary btn-blue" data-bs-toggle="modal" data-bs-target="#importModal">Browse Files</button>
                        </div>
                      @endif
                        <ul class="list-unstyled overflow-auto flex-grow-1">
                            @foreach ($files as $file)
                                <li class="file-list-item">
                                    <div class="file-info">
                                        <i class="fas {{ $file->type == 'pdf' ? 'fa-file-pdf text-primary' : 'fa-file-pdf text-success' }} fs-5"></i>
                                        <div>
                                            <p class="file-name mb-0">{{ $file->file_name }}</p>
                                            <p class="file-meta mb-0">Uploaded on {{ $file->created_at->format('d-m-Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($file->status == '3')
                                            <span class="badge-approved">Approved</span>
                                        @elseif ($file->status == '1')
                                            <span class="badge-rejected">Rejected</span>
                                        @elseif ($file->status == '4')
                                        <span class="badge-approved">Quotation</span>
                                        @else
                                            <span class="badge-pending">Pending</span>

                                            @if ($materials->status_1 == 2 && auth()->user()->name != $file->create_by)
                                                <form action="{{ route('cstm.fileapprove', $file->id) }}" method="POST" class="ml-2">
                                                    @csrf
                                                    <button type="submit" aria-label="Confirm" class="icon-btn" title="Confirm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('cstm.filerejected', $file->id) }}" method="POST" class="ml-2">
                                                    @csrf
                                                    <button type="submit" aria-label="Cancel" class="icon-btn cancel" title="Cancel">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        <a href="{{ route('file.download', $file->id) }}" aria-label="Download" class="icon-btn" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </article>

                    <!-- Activity Log -->
                    <article class="card-custom flex-fill d-flex flex-column">
                        <h2 class="h6 mb-3 d-flex align-items-center gap-2 flex-shrink-0">
                            <i class="fas fa-clock text-primary"></i> Activity Log
                        </h2>
                        <ul class="list-unstyled overflow-auto flex-grow-1">
                            @foreach ($activity_logs as $log)
                                <li class="activity-item">
                                    <div class="activity-title">
                                        @php
                                            // Memetakan status angka menjadi label dan kelas CSS
                                            $statusClasses = [
                                                1 => ['class' => 'badge-pending', 'label' => 'Draft'], // Abu-abu
                                                2 => ['class' => 'badge-approved', 'label' => 'Open'], // Hijau
                                                3 => ['class' => 'badge-active', 'label' => 'On Progress'], // Kuning
                                                4 => ['class' => 'badge-orange', 'label' => 'On Progress 2'], // Oren
                                                5 => ['class' => 'badge-completed', 'label' => 'Finish'], // Biru Muda
                                            ];
                                            $currentStatus = $statusClasses[$log->status] ?? ['class' => 'badge-red', 'label' => 'Status Tidak Tersedia'];
                                        @endphp
                                        <span class="activity-badge {{ $currentStatus['class'] }}"></span>
                                        {{ $currentStatus['label'] }} 
                                        <span class="activity-date">{{ $log->updated_at->format('M d, Y • H:i') }}</span>
                                    </div>
                                    <p class="activity-desc mb-2"><strong>{{ $log->keterangan }}</strong> Diperbarui oleh <strong>{{ $log->modified_at }}</strong></p>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                </section>
            </div>

            <div class="modal fade" id="inputDataModal" tabindex="-1" aria-labelledby="inputDataModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                  <form action="{{ route('CustomRequest.updateCstmReq', $materials->id) }}" method="POST">
                      @csrf
                      <div class="modal-content">
                          <div class="modal-header">
                              <h5 class="modal-title">Input</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                              {{-- <div class="mb-3">
                                  <label class="form-label fw-bold">Jenis Proses</label>
                                  <input type="text" name="jenis_proses_subcont" class="form-control" placeholder="Masukkan Jenis Proses..." required>
                              </div> --}}

                              <div class="mb-3">
                                  <label class="form-label fw-bold">Cost Process</label>
                                  <input type="number" name="harga_awal" class="form-control" placeholder="Masukkan Harga Awal..." required>
                              </div>
                          </div>
                          <div class="modal-footer">
                              <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                          </div>
                      </div>
                  </form>
              </div>
          </div>

          <div class="modal fade" id="uploadQuotationModal" tabindex="-1" aria-labelledby="uploadQuotationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
            <form id="uploadQuotationForm" action="{{ route('submit.quotation', $materials->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadQuotationModalLabel">Upload Quotation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="quotation_file" class="form-label fw-bold">File Quotation</label>
                            <input type="file" class="form-control" id="quotation_file" name="quotation_file" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan</label>
                            <input type="text" id="keterangan" name="keterangan" class="form-control" placeholder="Masukkan Keterangan..." required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

          <div class="modal fade" id="inputDataModal1" tabindex="-1" aria-labelledby="inputDataModalLabel1" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                  <form action="{{ route('CustomRequest.hargaakhir', $materials->id) }}" method="POST">
                      @csrf
                      <div class="modal-content">
                          <div class="modal-header">
                              <h5 class="modal-title">Input Data</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                              <div class="mb-3">
                                  <label class="form-label fw-bold">Selling Price</label>
                                  <input type="text" name="harga_akhir" class="form-control" placeholder="Masukkan Harga Akhir..." required>
                              </div>
                          </div>
                          <div class="modal-footer">
                              <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                          </div>
                      </div>
                  </form>
              </div>
          </div>

            <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form action="{{ route('cstm.fileupload', $materials->id) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                @if($hasStatusThree)
                                    <h5 class="modal-title" id="importModalLabel">Upload File dan Cost Process</h5>
                                @else
                                    <h5 class="modal-title" id="importModalLabel">Upload File</h5>
                                @endif
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @if(session('import_success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('import_success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif

                                @if(session('import_error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        {{ session('import_error') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <label for="file" class="form-label">File</label>
                                    <input type="file" name="file" id="file" 
                                        class="form-control @error('file') is-invalid @enderror" 
                                        accept=".pdf" required>
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Upload the file that was previously exported from the system.</div>
                                </div>

                                @if($hasStatusThree)
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Cost Process</label>
                                        <input type="number" name="harga_awal" class="form-control" placeholder="Masukkan Harga Awal..." required>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </div>
                        </div>
                    </form>
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
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
        <script>
          function uploadexcel() {
              document.getElementById('uploadForm').submit();
          }

            $(document).ready(function() {
                $('#productionButton').on('click', function() {
                    var materialsId = $(this).data('id');

                    // Menampilkan jendela konfirmasi
                    if (confirm("Apakah Anda yakin ingin mengirim ke marketing?")) {
                        // Mengirim permintaan AJAX ke server
                        $.ajax({
                            url: '{{ route('SubmitProduction', '') }}/' + materialsId, // Menggunakan route yang telah dibuat
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}' // Token CSRF untuk keamanan
                            },
                            success: function(response) {
                                // Menampilkan pesan sukses
                                alert(response.message); // Tampilkan pesan sukses
                                location.reload(); // Reload halaman
                            },
                            error: function(xhr) {
                                console.log(xhr);  // Tampilkan detail kesalahan di konsol
                                alert('An error occurred: ' + xhr.responseText); // Tampilkan pesan error
                            }
                        });
                    } else {
                        // Jika pengguna batal konfirmasi
                        alert("Pengiriman dibatalkan.");
                    }
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const hargaAwal = parseFloat(row.cells[8].innerText) || 0; // Kolom Harga Awal
                    const hargaAkhir = parseFloat(row.cells[9].innerText) || 0; // Kolom Harga Akhir
                    const profitCell = row.cells[10]; // Kolom Profit
                    
                    // Menghitung profit dan menampilkan hasil
                    const profitPercentage = calcProfit(hargaAwal, hargaAkhir);
                    
                    // Menampilkan profit dalam persen
                    if (profitPercentage <= 25) {
                        profitCell.innerHTML = `<span style="color: red;">${profitPercentage}%</span>`; // Tampilkan dalam warna merah jika kurang dari atau sama dengan 25%
                    } else {
                        profitCell.innerText = `${profitPercentage}%`; // Tampilkan hasil biasa jika profit lebih dari 25%
                    }
                    // Menambahkan event listener untuk tombol marketing
                    const marketingButton = row.querySelector('.marketingButton');
                    if (marketingButton) {
                        marketingButton.addEventListener('click', function() {
                            if (confirm("Apakah Anda yakin ingin mengirim?")) {
                                if (profitPercentage > 25) {
                                    if (confirm("Profit lebih dari 25%. Apakah Anda yakin ingin menyelesaikan pengajuan ini?")) {
                                        $.ajax({
                                            url: '{{ route('approveMarketing2', '') }}/' + this.dataset.id,
                                            method: 'POST',
                                            data: {
                                                _token: '{{ csrf_token() }}'
                                            },
                                            success: function(response) {
                                                alert(response.message);
                                                location.reload();
                                            },
                                            error: function(xhr) {
                                                alert('An error occurred: ' + xhr.responseText);
                                            }
                                        });
                                    }
                                } else {
                                    if (confirm("Profit kurang dari atau sama dengan 25%. Apakah Anda yakin ingin mengirim ke Finance?")) {
                                        $.ajax({
                                            url: '{{ route('approveMarketing', '') }}/' + this.dataset.id,
                                            method: 'POST',
                                            data: {
                                                _token: '{{ csrf_token() }}'
                                            },
                                            success: function(response) {
                                                alert(response.message);
                                                location.reload();
                                            },
                                            error: function(xhr) {
                                                alert('An error occurred: ' + xhr.responseText);
                                            }
                                        });
                                    }
                                }
                            } else {
                                alert("Pengiriman dibatalkan.");
                            }
                        });
                    }

                    // Menambahkan event listener untuk tombol reject
                    const rejectButton = row.querySelector('.rejectButton');
                    if (rejectButton) {
                        rejectButton.addEventListener('click', function() {
                            // Menampilkan jendela konfirmasi untuk penolakan
                            if (confirm("Apakah Anda yakin ingin menolak pengajuan ini?")) {
                                $.ajax({
                                    url: '{{ route('rejectMarketing', '') }}/' + this.dataset.id,
                                    method: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}'
                                    },
                                    success: function(response) {
                                        alert(response.message);
                                        location.reload(); // Reload halaman setelah berhasil
                                    },
                                    error: function(xhr) {
                                        alert('An error occurred: ' + xhr.responseText);
                                    }
                                });
                            } else {
                                alert("Penolakan pengajuan dibatalkan.");
                            }
                        });
                    }
                });
            });

            // $(document).ready(function() {
            //     $('#productionButton').on('click', function() {
            //         var materialsId = $(this).data('id');

            //         // Menampilkan jendela konfirmasi
            //         if (confirm("Apakah Anda yakin ingin mengirim ke Production?")) {
            //             // Mengirim permintaan AJAX ke server
            //             $.ajax({
            //                 url: '{{ route('kirimproduction', '') }}/' + materialsId, // Menggunakan route yang telah dibuat
            //                 method: 'POST',
            //                 data: {
            //                     _token: '{{ csrf_token() }}' // Token CSRF untuk keamanan
            //                 },
            //                 success: function(response) {
            //                     // Menampilkan pesan sukses
            //                     alert(response.message); // Tampilkan pesan sukses
            //                     location.reload(); // Reload halaman
            //                 },
            //                 error: function(xhr) {
            //                     console.log(xhr);  // Tampilkan detail kesalahan di konsol
            //                     alert('An error occurred: ' + xhr.responseText); // Tampilkan pesan error
            //                 }
            //             });
            //         } else {
            //             // Jika pengguna batal konfirmasi
            //             alert("Pengiriman dibatalkan.");
            //         }
            //     });
            // });


            // $(document).ready(function() {
            //     $('#ButtonSubmit').on('click', function() {
            //         var materialsId = $(this).data('id');

            //         // Menampilkan jendela konfirmasi
            //         if (confirm("Apakah Anda yakin ingin mengirim ke Marketing?")) {
            //             // Mengirim permintaan AJAX ke server
            //             $.ajax({
            //                 url: '{{ route('SubmitProduction', '') }}/' + materialsId, // Menggunakan route yang telah dibuat
            //                 method: 'POST',
            //                 data: {
            //                     _token: '{{ csrf_token() }}' // Token CSRF untuk keamanan
            //                 },
            //                 success: function(response) {
            //                     // Menampilkan pesan sukses
            //                     alert(response.message); // Tampilkan pesan sukses
            //                     location.reload(); // Reload halaman
            //                 },
            //                 error: function(xhr) {
            //                     console.log(xhr);  // Tampilkan detail kesalahan di konsol
            //                     alert('An error occurred: ' + xhr.responseText); // Tampilkan pesan error
            //                 }
            //             });
            //         } else {
            //             // Jika pengguna batal konfirmasi
            //             alert("Pengiriman dibatalkan.");
            //         }
            //     });
            // });

            $(document).ready(function() {
                $('#ButtonReject').on('click', function() {
                    var materialsId = $(this).data('id');

                    // Menampilkan jendela konfirmasi
                    if (confirm("Apakah Anda yakin ingin reject pengajuan ini?")) {
                        // Mengirim permintaan AJAX ke server
                        $.ajax({
                            url: '{{ route('RejectProduction', '') }}/' + materialsId, // Menggunakan route yang telah dibuat
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}' // Token CSRF untuk keamanan
                            },
                            success: function(response) {
                                // Menampilkan pesan sukses
                                alert(response.message); // Tampilkan pesan sukses
                                location.reload(); // Reload halaman
                            },
                            error: function(xhr) {
                                console.log(xhr);  // Tampilkan detail kesalahan di konsol
                                alert('An error occurred: ' + xhr.responseText); // Tampilkan pesan error
                            }
                        });
                    } else {
                        // Jika pengguna batal konfirmasi
                        alert("Pengiriman dibatalkan.");
                    }
                });
            });


//             function submitQuotationData(url) {
//     const quotationFileInput = document.getElementById('quotation_file');
//     const keteranganInput = document.getElementById('keterangan');

//     if (quotationFileInput.files.length === 0) {
//         Swal.fire({
//             icon: 'error',
//             title: 'File tidak ada',
//             text: 'Silakan pilih file untuk diunggah!'
//         });
//         return;
//     }

//     let formData = new FormData();
//     formData.append('quotation_file', quotationFileInput.files[0]);
//     formData.append('keterangan', keteranganInput.value); // Pastikan keterangan disertakan

//     Swal.fire({
//         title: 'Konfirmasi',
//         text: "Apakah Anda yakin ingin meng-upload data ini?",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33',
//         confirmButtonText: 'Ya, upload!'
//     }).then((result) => {
//         if (result.isConfirmed) {
//             fetch(url, {
//                 method: 'POST',
//                 headers: {
//                     'X-CSRF-TOKEN': '{{ csrf_token() }}' // Token CSRF untuk keamanan
//                 },
//                 body: formData
//             }).then(response => {
//                 if (response.ok) {
//                     Swal.fire(
//                         'Berhasil!',
//                         'Data telah berhasil diunggah.',
//                         'success'
//                     ).then(() => {
//                         window.location.reload(); // Reload halaman setelah sukses
//                     });
//                 } else {
//                     Swal.fire(
//                         'Gagal!',
//                         'Gagal mengirim data.',
//                         'error'
//                     );
//                 }
//             }).catch(error => {
//                 Swal.fire(
//                     'Kesalahan!',
//                     'Terjadi kesalahan saat mengirim data!',
//                     'error'
//                 );
//             });
//         }
//     });
// }

    // Fungsi untuk menghitung profit persentase
            function calcProfit(hargaAwal, hargaAkhir) {
                if (hargaAkhir > 0) {
                    let profit = ((hargaAkhir - hargaAwal) / hargaAkhir) * 100;
                    return {
                        value: profit.toFixed(2) + '%', // Mengembalikan hasil dengan format 2 decimal
                        isLow: profit < 25 // Menyimpan flag jika profit kurang dari 25%
                    };
                } else {
                    return {
                        value: 'N/A', // Tidak bisa dihitung jika harga akhir 0
                        isLow: false
                    };
                }
            }

            // Menjalankan perhitungan profit saat halaman dimuat
            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const hargaAwal = parseFloat(row.cells[7].innerText) || 0; // Kolom Harga Awal
                    const hargaAkhir = parseFloat(row.cells[8].innerText) || 0; // Kolom Harga Akhir
                    const profitCell = row.cells[9]; // Kolom Profit
                    
                    // Menghitung profit dan menampilkannya
                    const profitResult = calcProfit(hargaAwal, hargaAkhir);
                    
                    // Mengatur teks dan warna berdasarkan profit
                    if (profitResult.isLow) {
                        profitCell.innerHTML = `<span style="color: red;">${profitResult.value}</span>`; // Tampilkan dalam warna merah
                    } else {
                        profitCell.innerText = profitResult.value; // Tampilkan hasil biasa
                    }
                });
            });
        </script>

    </main><!-- End #main -->
@endsection
