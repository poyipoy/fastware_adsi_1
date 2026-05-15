@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Persetujuan Claim Submission</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Persetujuan Claim Submission</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Data Claim Submission - Procurement</h5>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#filterClaimModal">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="{{ route('claim.exportExcelProc', request()->only(['status', 'pic', 'category', 'supplier', 'date_from', 'date_to'])) }}" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </a>
                                </div>
                            </div>

                            <div class="modal fade" id="filterClaimModal" tabindex="-1" aria-labelledby="filterClaimModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content shadow border-0">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title" id="filterClaimModalLabel">Filter Claim Submission</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <form method="GET" action="{{ route('claim.indexProc') }}">
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label for="pic" class="form-label">PIC</label>
                                                        <input type="text" id="pic" name="pic" class="form-control"
                                                            value="{{ request('pic') }}" placeholder="Nama PIC">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="category" class="form-label">Category</label>
                                                        <select id="category" name="category" class="form-select">
                                                            <option value="">Semua Category</option>
                                                            <option value="Barang Rusak (NG)" {{ request('category') === 'Barang Rusak (NG)' ? 'selected' : '' }}>Barang Rusak (NG)</option>
                                                            <option value="Tidak Sesuai Spesifikasi" {{ request('category') === 'Tidak Sesuai Spesifikasi' ? 'selected' : '' }}>Tidak Sesuai Spesifikasi</option>
                                                            <option value="Barang Salah Kirim (Item Mismatch)" {{ request('category') === 'Barang Salah Kirim (Item Mismatch)' ? 'selected' : '' }}>Barang Salah Kirim (Item Mismatch)</option>
                                                            <option value="Lainnya" {{ request('category') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="supplier" class="form-label">Supplier</label>
                                                        <input type="text" id="supplier" name="supplier"
                                                            class="form-control" value="{{ request('supplier') }}"
                                                            placeholder="Nama Supplier">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="date_from" class="form-label">Submission Date From</label>
                                                        <input type="date" id="date_from" name="date_from"
                                                            class="form-control" value="{{ request('date_from') }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="date_to" class="form-label">Submission Date To</label>
                                                        <input type="date" id="date_to" name="date_to"
                                                            class="form-control" value="{{ request('date_to') }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="{{ route('claim.indexProc') }}" class="btn btn-outline-secondary">Reset</a>
                                                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="claim-datatable-wrapper datatable-wrapper no-header">
                                <div class="datatable-container table-responsive" style="height: 100%; overflow-y: auto;">

                                <table class="datatable-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px" data-sortable="false">NO</th>
                                            <th class="text-center" width="50px" data-sortable="false">Info</th>
                                            <th class="text-center" width="100px">PIC</th>
                                            <th class="text-center" width="100px">No. PR</th>
                                            <th class="text-center" width="150px">Nama Produk</th>
                                            <th class="text-center" width="120px">Submission Date</th>
                                            <th class="text-center" width="120px">Category</th>
                                            <th class="text-center" width="150px">Supplier</th>
                                            <th class="text-center" width="200px">Description of Issue</th>
                                            <th class="text-center" width="200px">Proposed Solution</th>
                                            <th class="text-center" width="100px">Status</th>
                                            <th class="text-center" width="100px" data-sortable="false">Foto/File</th>
                                            <th class="text-center" width="100px" data-sortable="false">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($claims as $key => $claim)
                                            <tr>
                                                <td class="text-center">{{ ($claims->firstItem() ?? 1) + $key }}</td>
                                                <td class="text-center">
                                                    @php
                                                        $tgl_submit = $claim->submission_date ?? $claim->created_at;
                                                        $current_date = now();
                                                        $diff_days = $tgl_submit ? $tgl_submit->diffInDays($current_date) : 0;
                                                    @endphp
                                                    @if ($claim->status === 'finished')
                                                        <span style="color: green; font-size: 24px;">●</span>
                                                    @elseif ($diff_days > 3)
                                                        <span style="color: red; font-size: 24px;">●</span>
                                                    @else
                                                        <span style="color: green; font-size: 24px;">●</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $claim->modified_at }}</td>
                                                <td class="text-center">{{ $claim->no_pr }}</td>
                                                <td class="text-center">{{ $claim->nama_produk }}</td>
                                                <td class="text-center" data-order="{{ $claim->submission_date ? $claim->submission_date->timestamp : 0 }}">
                                                    {{ $claim->submission_date ? $claim->submission_date->format('d-m-Y') : '-' }}
                                                </td>
                                                <td class="text-center">{{ $claim->category }}</td>
                                                <td class="text-center">{{ $claim->supplier ?? '-' }}</td>
                                                <td class="text-center">{{ Str::limit($claim->description_of_issue, 50) }}</td>
                                                <td class="text-center">{{ Str::limit($claim->proposed_solution, 50) }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ $claim->status_badge }} align-items-center"
                                                        style="font-size: 16px;">{{ $claim->status_label }}</span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    @if ($claim->file)
                                                        <a href="{{ asset($claim->file) }}" target="_blank"
                                                            class="btn btn-outline-secondary btn-sm"
                                                            data-bs-toggle="tooltip" title="{{ $claim->file_name }}">
                                                            <i class="fas fa-file-image"></i> Lihat
                                                        </a>
                                                    @else
                                                        <span class="text-muted fst-italic">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('claim.editProc', $claim->id) }}"
                                                        class="btn btn-sm btn-primary" title="Proses Claim">
                                                        <i class="fa-regular fa-folder-open"></i>
                                                    </a>
                                                    <a href="{{ route('claim.view', $claim->id) }}"
                                                        class="btn btn-sm btn-info" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>

                                <div class="datatable-bottom">
                                    <div class="datatable-info">
                                        <div class="d-flex align-items-center gap-2">
                                            <label for="perPageSelect" class="mb-0">Show entries:</label>
                                            <select id="perPageSelect" class="form-select" style="width: auto;" onchange="changePerPage(this.value)">
                                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                            </select>
                                        </div>
                                        <div style="margin-top: 8px;">
                                            @if ($claims->count() > 0)
                                                Menampilkan {{ $claims->firstItem() }} - {{ $claims->lastItem() }} dari {{ $claims->total() }} data
                                            @else
                                                Menampilkan 0 data
                                            @endif
                                        </div>
                                    </div>

                                    @if ($claims->hasPages())
                                        <div class="datatable-pagination">
                                            {{ $claims->onEachSide(1)->links('pagination::bootstrap-5') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <style>
            .claim-datatable-wrapper .datatable-container {
                border-top: 1px solid #d9d9d9;
                border-bottom: 1px solid #d9d9d9;
            }

            .claim-datatable-wrapper .datatable-bottom {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
                padding: 8px 10px;
            }

            .claim-datatable-wrapper .datatable-info {
                margin: 0;
                font-size: 8pt;
            }

            .claim-datatable-wrapper .pagination {
                margin: 0;
            }

            .claim-datatable-wrapper .page-link {
                border: 1px solid transparent;
                margin-left: 2px;
                padding: 6px 12px;
                color: #333;
            }

            .claim-datatable-wrapper .page-link:hover {
                background-color: #d9d9d9;
                color: #333;
            }

            .claim-datatable-wrapper .page-item.active .page-link,
            .claim-datatable-wrapper .page-item.active .page-link:hover {
                background-color: #d9d9d9;
                border-color: transparent;
                color: #333;
            }

            .claim-datatable-wrapper .page-item.disabled .page-link {
                opacity: 0.4;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script>
            function changePerPage(value) {
                const url = new URL(window.location);
                url.searchParams.set('per_page', value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }

            $(document).ready(function() {
                $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });

            (function() {
                var filterModalEl = document.getElementById('filterClaimModal');
                if (!filterModalEl) return;

                function forceCloseFilterModal() {
                    if (window.bootstrap && bootstrap.Modal) {
                        var instance = bootstrap.Modal.getInstance(filterModalEl);
                        if (instance) {
                            instance.hide();
                        }
                    }

                    filterModalEl.classList.remove('show');
                    filterModalEl.setAttribute('aria-hidden', 'true');
                    filterModalEl.style.display = 'none';

                    document.body.classList.remove('modal-open');
                    document.body.style.paddingRight = '';
                    document.querySelectorAll('.modal-backdrop').forEach(function(el) {
                        el.remove();
                    });
                }

                window.addEventListener('pageshow', function() {
                    forceCloseFilterModal();
                });

                window.addEventListener('popstate', function() {
                    forceCloseFilterModal();
                });

                var filterForm = filterModalEl.querySelector('form');
                if (filterForm) {
                    filterForm.addEventListener('submit', function() {
                        forceCloseFilterModal();
                    });
                }
            })();
        </script>

    </main>
@endsection
