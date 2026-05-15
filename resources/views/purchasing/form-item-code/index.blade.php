@extends('layout')

@section('content')
    <main id="main" class="main">
        @php
            $activeTab = $activeTab ?? 'new_product';
            $perPage = (int) ($perPage ?? 20);
            $filters = $filters ?? ['q' => null, 'status' => null];
            $statsByType = $statsByType ?? [
                'new_product' => ['total' => 0, 'draft' => 0, 'submitted' => 0, 'approved_1' => 0, 'approved_2' => 0, 'finished' => 0],
                'update_price' => ['total' => 0, 'draft' => 0, 'submitted' => 0, 'approved_1' => 0, 'approved_2' => 0, 'finished' => 0],
            ];
            $statusClassMap = [
                'draft' => 'secondary',
                'submitted' => 'warning text-dark',
                'approved_1' => 'info',
                'approved_2' => 'primary',
                'finished' => 'success',
            ];
        @endphp

        <div class="pagetitle">
            <h1>Form Item Code</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Item Code - Form Item Code</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="card purchasing-section-card">
                <div class="card-header pb-0">
                    <ul class="nav nav-tabs card-header-tabs purchasing-tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'new_product' ? 'active' : '' }}"
                                href="{{ route('item-code.form', ['tab' => 'new_product']) }}">
                                Produk Baru
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'update_price' ? 'active' : '' }}"
                                href="{{ route('item-code.form', ['tab' => 'update_price']) }}">
                                Update Harga
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body pt-3">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Validasi gagal:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="{{ $activeTab === 'new_product' ? '' : 'd-none' }}">
                        @php
                            $newStats = $statsByType['new_product'] ?? ['total' => 0, 'draft' => 0, 'submitted' => 0, 'approved_1' => 0, 'approved_2' => 0, 'finished' => 0];
                            $newFilterParams = ['tab' => 'new_product'];

                            if ($activeTab === 'new_product') {
                                if (!empty($filters['q'])) {
                                    $newFilterParams['q'] = $filters['q'];
                                }

                                if (!empty($filters['status'])) {
                                    $newFilterParams['status'] = $filters['status'];
                                }

                                if (!empty($filters['start_date'])) {
                                    $newFilterParams['start_date'] = $filters['start_date'];
                                }

                                if (!empty($filters['end_date'])) {
                                    $newFilterParams['end_date'] = $filters['end_date'];
                                }
                            }
                        @endphp

                        <div class="purchasing-toolbar">
                            <div class="d-flex flex-wrap gap-2 purchasing-toolbar-stats">
                                <span class="badge rounded-pill text-bg-light border">Total: {{ $newStats['total'] }}</span>
                                <span class="badge rounded-pill text-bg-secondary">Draft: {{ $newStats['draft'] }}</span>
                                <span class="badge rounded-pill text-bg-warning">Submitted: {{ $newStats['submitted'] }}</span>
                                <span class="badge rounded-pill text-bg-info">Approved 1: {{ $newStats['approved_1'] ?? 0 }}</span>
                                <span class="badge rounded-pill text-bg-primary">Approved 2: {{ $newStats['approved_2'] ?? 0 }}</span>
                                <span class="badge rounded-pill text-bg-success">Finished: {{ $newStats['finished'] }}</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center purchasing-toolbar-controls">
                                <form method="GET" action="{{ route('item-code.form') }}"
                                    class="d-flex flex-wrap gap-2 align-items-center purchasing-filter-form" data-auto-filter-form>
                                    <input type="hidden" name="tab" value="new_product">
                                    <input type="hidden" name="per_page" value="{{ $perPage }}" data-per-page-hidden>

                                    <div class="input-group input-group-sm purchasing-filter-input">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" name="q" class="form-control"
                                            value="{{ $activeTab === 'new_product' ? ($filters['q'] ?? '') : '' }}"
                                            placeholder="Cari Nomor / Supplier / Product Code / Description / Nama">
                                    </div>

                                    <select class="form-select form-select-sm" name="status">
                                        @php
                                            $newSelectedStatus = $activeTab === 'new_product' ? ($filters['status'] ?? '') : '';
                                        @endphp
                                        <option value="" {{ $newSelectedStatus === '' ? 'selected' : '' }}>Semua Status</option>
                                        <option value="draft" {{ $newSelectedStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="submitted" {{ $newSelectedStatus === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                        <option value="approved_1" {{ $newSelectedStatus === 'approved_1' ? 'selected' : '' }}>Approved 1</option>
                                        <option value="approved_2" {{ $newSelectedStatus === 'approved_2' ? 'selected' : '' }}>Approved 2</option>
                                        <option value="finished" {{ $newSelectedStatus === 'finished' ? 'selected' : '' }}>Finished</option>
                                    </select>

                                    <div class="input-group input-group-sm purchasing-date-range">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" name="start_date" class="form-control"
                                            value="{{ $activeTab === 'new_product' ? ($filters['start_date'] ?? '') : '' }}"
                                            aria-label="Tanggal mulai">
                                        <span class="input-group-text range-separator">s/d</span>
                                        <input type="date" name="end_date" class="form-control"
                                            value="{{ $activeTab === 'new_product' ? ($filters['end_date'] ?? '') : '' }}"
                                            aria-label="Tanggal selesai">
                                    </div>

                                    <a href="{{ route('item-code.form', ['tab' => 'new_product', 'per_page' => $perPage]) }}"
                                        class="btn btn-sm btn-outline-secondary">Reset</a>

                                    <div class="w-100"></div>

                                    <div class="d-flex align-items-center gap-1 small text-muted">
                                        <span>Show</span>
                                        <select class="form-select form-select-sm" data-per-page-select style="width: auto;">
                                            <option value="10" {{ $perPage === 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ $perPage === 20 ? 'selected' : '' }}>20</option>
                                            <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50</option>
                                        </select>
                                        <span>entries</span>
                                    </div>
                                </form>

                                <div class="purchasing-toolbar-actions">

                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#modalImportItemCode" onclick="openImportModal('new_product')">
                                    Import
                                    </button>

                                    <a href="{{ route('item-code.exportForm', $newFilterParams) }}"
                                        class="btn btn-sm btn-outline-success">
                                        Export
                                    </a>

                                    <form action="{{ route('item-code.submitAll') }}" method="POST" class="d-inline"
                                        data-submit-all-form
                                        data-confirm-title="Submit semua data Draft Produk Baru?"
                                        data-confirm-text="Semua data berstatus Draft pada tab Produk Baru akan disubmit untuk approval.">
                                        @csrf
                                        <input type="hidden" name="tab" value="new_product">
                                        <button type="submit" class="btn btn-sm btn-primary"
                                            {{ (int) ($newStats['draft'] ?? 0) < 1 ? 'disabled' : '' }}>
                                            Submit All Draft
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                        data-bs-target="#modalNewProduct" onclick="openCreateNewProduct()">
                                        + Tambah Data
                                    </button>
                                </div>
                                </div>
                            </div>

                        <div class="table-responsive purchasing-table-wrap">
                            <table id="new-product-table" class="table table-bordered table-striped align-middle purchasing-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nomor Pengajuan</th>
                                        <th>Tanggal</th>
                                        <th>Nama</th>
                                        <th>Category</th>
                                        <th>Supplier</th>
                                        <th>Product Code</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Currency</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th style="width:120px; min-width:120px; max-width:130px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($itemsNewProduct as $item)
                                        @php
                                            $isRejectedDraft = $item->status === 'draft' && (int) ($item->rejected_histories_count ?? 0) > 0;
                                            $badgeClass = $isRejectedDraft ? 'danger' : ($statusClassMap[$item->status] ?? 'secondary');

                                            $labelFirst = 'Approved by';

                                            if ($isRejectedDraft) {
                                                $statusLabel = 'Draft (Rejected)';
                                                $statusLabelHtml = e($statusLabel);
                                            } elseif ($item->status === 'approved_1') {
                                                $statusLabel = 'Approved by Jessica';
                                                $statusLabelHtml = $labelFirst . '<br>Jessica';
                                            } elseif ($item->status === 'approved_2') {
                                                $statusLabel = 'Approved by Cahyo';
                                                $statusLabelHtml = $labelFirst . '<br>Cahyo';
                                            } else {
                                                $statusLabel = ucfirst($item->status);
                                                $statusLabelHtml = e($statusLabel);
                                            }
                                            $typeLabel = $item->type === 'new_product' ? 'Produk Baru' : 'Update Harga';
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ method_exists($itemsNewProduct, 'firstItem') && $itemsNewProduct->firstItem() !== null ? $itemsNewProduct->firstItem() + $loop->index : $loop->iteration }}
                                            </td>
                                            <td>{{ $item->nomor_pengajuan ?: '-' }}</td>
                                            <td>{{ optional($item->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ optional($item->creator)->name ?: '-' }}</td>
                                            <td>{{ $item->category ?: '-' }}</td>
                                            <td>{{ $item->supplier ?: '-' }}</td>
                                            <td class="text-primary-emphasis fw-semibold">{{ $item->product_code }}</td>
                                            <td>{{ $item->description }}</td>
                                            <td class="text-end">{{ number_format((float) $item->qty, 0, '.', '') }}</td>
                                            <td>{{ $item->unit ?: '-' }}</td>
                                            <td>{{ $item->currency }}</td>
                                            <td class="text-end">{{ number_format((float) $item->price_per_pcs) }}</td>
                                            <td>
                                                <span class="badge purchasing-status-badge bg-{{ $badgeClass }}">{!! $statusLabelHtml !!}</span>
                                            </td>
                                            <td class="action-cell">
                                                @php
                                                    $detailItem = [
                                                        'id' => $item->id,
                                                        'nomor_pengajuan' => $item->nomor_pengajuan,
                                                        'type' => $item->type,
                                                        'type_label' => $typeLabel,
                                                        'category' => $item->category,
                                                        'supplier' => $item->supplier,
                                                        'product_code' => $item->product_code,
                                                        'description' => $item->description,
                                                        'qty' => (int) round((float) $item->qty),
                                                        'unit' => $item->unit,
                                                        'price_per_pcs' => (int) $item->price_per_pcs,
                                                        'currency' => $item->currency,
                                                        'tanggal' => optional($item->tanggal)->format('d-m-Y'),
                                                        'tanggal_lama' => optional($item->tanggal_lama)->format('d-m-Y'),
                                                        'harga_baru' => $item->harga_baru !== null ? (int) $item->harga_baru : null,
                                                        'reason_new_price' => $item->reason_new_price,
                                                        'attachment_url' => $item->attachment ? route('item-code.attachment', $item->id) : null,
                                                        'selisih' => $item->selisih !== null ? (int) $item->selisih : null,
                                                        'tanggal_harga_baru' => optional($item->tanggal_harga_baru)->format('d-m-Y'),
                                                        'status' => $statusLabel,
                                                        'status_raw' => $item->status,
                                                        'status_html' => $statusLabelHtml,
                                                        'creator' => optional($item->creator)->name,
                                                        'approver' => optional($item->approver)->name,
                                                        'approver2' => optional($item->approver2)->name,
                                                        'finisher' => optional($item->finisher)->name,
                                                    ];
                                                @endphp
                                                
                                                    <div class="action-grid">
                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                            data-bs-toggle="modal" data-bs-target="#modalViewDetail"
                                                            data-item='@json($detailItem)'
                                                            onclick="openDetailModal(this)">
                                                            Detail
                                                        </button>

                                                        @if ($item->status === 'draft')
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                data-bs-toggle="modal" data-bs-target="#modalNewProduct"
                                                                data-item='@json($item)'
                                                                onclick="openEditNewProduct(this)">
                                                                Edit
                                                            </button>

                                                            <form action="{{ route('item-code.destroy', $item->id) }}" method="POST"
                                                                class="d-contents" data-delete-item-form>
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                            </form>

                                                            <form action="{{ route('item-code.submit', $item->id) }}" method="POST"
                                                                class="d-contents" data-submit-item-form
                                                                data-confirm-title="Submit data ini?"
                                                                data-confirm-text="Data akan disubmit untuk approval.">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-primary">Submit</button>
                                                            </form>
                                                        @else
                                                            {{-- placeholder kosong agar grid tetap rapi --}}
                                                            <span></span><span></span><span></span>
                                                        @endif
                                                    </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="js-empty-row">
                                            <td colspan="14" class="text-center text-muted">Belum ada data produk baru.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (method_exists($itemsNewProduct, 'links'))
                            <div class="mt-3">
                                {{ $itemsNewProduct->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </div>

                    <div class="{{ $activeTab === 'update_price' ? '' : 'd-none' }}">
                        @php
                            $updateStats = $statsByType['update_price'] ?? ['total' => 0, 'draft' => 0, 'submitted' => 0, 'approved_1' => 0, 'approved_2' => 0, 'finished' => 0];
                            $updateFilterParams = ['tab' => 'update_price'];

                            if ($activeTab === 'update_price') {
                                if (!empty($filters['q'])) {
                                    $updateFilterParams['q'] = $filters['q'];
                                }

                                if (!empty($filters['status'])) {
                                    $updateFilterParams['status'] = $filters['status'];
                                }

                                if (!empty($filters['start_date'])) {
                                    $updateFilterParams['start_date'] = $filters['start_date'];
                                }

                                if (!empty($filters['end_date'])) {
                                    $updateFilterParams['end_date'] = $filters['end_date'];
                                }
                            }
                        @endphp

                        <div class="purchasing-toolbar">
                            <div class="d-flex flex-wrap gap-2 purchasing-toolbar-stats">
                                <span class="badge rounded-pill text-bg-light border">Total: {{ $updateStats['total'] }}</span>
                                <span class="badge rounded-pill text-bg-secondary">Draft: {{ $updateStats['draft'] }}</span>
                                <span class="badge rounded-pill text-bg-warning">Submitted: {{ $updateStats['submitted'] }}</span>
                                <span class="badge rounded-pill text-bg-info">Approved 1: {{ $updateStats['approved_1'] ?? 0 }}</span>
                                <span class="badge rounded-pill text-bg-primary">Approved 2: {{ $updateStats['approved_2'] ?? 0 }}</span>
                                <span class="badge rounded-pill text-bg-success">Finished: {{ $updateStats['finished'] }}</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center purchasing-toolbar-controls">
                                <form method="GET" action="{{ route('item-code.form') }}"
                                    class="d-flex flex-wrap gap-2 align-items-center purchasing-filter-form" data-auto-filter-form>
                                    <input type="hidden" name="tab" value="update_price">
                                    <input type="hidden" name="per_page" value="{{ $perPage }}" data-per-page-hidden>

                                    <div class="input-group input-group-sm purchasing-filter-input">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" name="q" class="form-control"
                                            value="{{ $activeTab === 'update_price' ? ($filters['q'] ?? '') : '' }}"
                                            placeholder="Cari Nomor / Supplier / Product Code / Description / Nama">
                                    </div>

                                    <select class="form-select form-select-sm" name="status">
                                        @php
                                            $updateSelectedStatus = $activeTab === 'update_price' ? ($filters['status'] ?? '') : '';
                                        @endphp
                                        <option value="" {{ $updateSelectedStatus === '' ? 'selected' : '' }}>Semua Status</option>
                                        <option value="draft" {{ $updateSelectedStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="submitted" {{ $updateSelectedStatus === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                        <option value="approved_1" {{ $updateSelectedStatus === 'approved_1' ? 'selected' : '' }}>Approved 1</option>
                                        <option value="approved_2" {{ $updateSelectedStatus === 'approved_2' ? 'selected' : '' }}>Approved 2</option>
                                        <option value="finished" {{ $updateSelectedStatus === 'finished' ? 'selected' : '' }}>Finished</option>
                                    </select>

                                    <div class="input-group input-group-sm purchasing-date-range">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" name="start_date" class="form-control"
                                            value="{{ $activeTab === 'update_price' ? ($filters['start_date'] ?? '') : '' }}"
                                            aria-label="Tanggal mulai">
                                        <span class="input-group-text range-separator">s/d</span>
                                        <input type="date" name="end_date" class="form-control"
                                            value="{{ $activeTab === 'update_price' ? ($filters['end_date'] ?? '') : '' }}"
                                            aria-label="Tanggal selesai">
                                    </div>

                                    <a href="{{ route('item-code.form', ['tab' => 'update_price', 'per_page' => $perPage]) }}"
                                        class="btn btn-sm btn-outline-secondary">Reset</a>

                                    <div class="w-100"></div>

                                    <div class="d-flex align-items-center gap-1 small text-muted">
                                        <span>Show</span>
                                        <select class="form-select form-select-sm" data-per-page-select style="width: auto;">
                                            <option value="10" {{ $perPage === 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ $perPage === 20 ? 'selected' : '' }}>20</option>
                                            <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50</option>
                                        </select>
                                        <span>entries</span>
                                    </div>

                                </form>

                                <div class="purchasing-toolbar-actions">

                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#modalImportItemCode" onclick="openImportModal('update_price')">
                                    Import
                                </button>

                                <a href="{{ route('item-code.exportForm', $updateFilterParams) }}"
                                    class="btn btn-sm btn-outline-success">
                                    Export
                                </a>

                                <form action="{{ route('item-code.submitAll') }}" method="POST" class="d-inline"
                                    data-submit-all-form
                                    data-confirm-title="Submit semua data Draft Update Harga?"
                                    data-confirm-text="Semua data berstatus Draft pada tab Update Harga akan disubmit untuk approval.">
                                    @csrf
                                    <input type="hidden" name="tab" value="update_price">
                                    <button type="submit" class="btn btn-sm btn-primary"
                                        {{ (int) ($updateStats['draft'] ?? 0) < 1 ? 'disabled' : '' }}>
                                        Submit All Draft
                                    </button>
                                </form>

                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                    data-bs-target="#modalUpdatePrice" onclick="openCreateUpdatePrice()">
                                    + Tambah Data
                                </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive purchasing-table-wrap">
                            <table id="update-price-table" class="table table-bordered table-striped align-middle purchasing-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nomor Pengajuan</th>
                                        <th>Tanggal</th>
                                        <th>Nama</th>
                                        <th>Category</th>
                                        <th>Supplier</th>
                                        <th>Product <br>Code</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Currency</th>
                                        <th>Eff. Date <br>(Current)</th>
                                        <th>Current <br>Price</th>
                                        <th>Eff. Date<br> (New)</th>
                                        <th>New <br>Price</th>
                                        <th>Lihat <br>File</th>
                                        <th>Selisih</th>
                                        <th>Status</th>
                                        <th style="width:120px; min-width:120px; max-width:120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($itemsUpdatePrice as $item)
                                        @php
                                            $isRejectedDraft = $item->status === 'draft' && (int) ($item->rejected_histories_count ?? 0) > 0;
                                            $badgeClass = $isRejectedDraft ? 'danger' : ($statusClassMap[$item->status] ?? 'secondary');

                                            $labelFirst = 'Approved by';

                                            if ($isRejectedDraft) {
                                                $statusLabel = 'Draft (Rejected)';
                                                $statusLabelHtml = e($statusLabel);
                                            } elseif ($item->status === 'approved_1') {
                                                $statusLabel = 'Approved by Jessica';
                                                $statusLabelHtml = $labelFirst . '<br>Jessica';
                                            } elseif ($item->status === 'approved_2') {
                                                $statusLabel = 'Approved by Cahyo';
                                                $statusLabelHtml = $labelFirst . '<br>Cahyo';
                                            } else {
                                                $statusLabel = ucfirst($item->status);
                                                $statusLabelHtml = e($statusLabel);
                                            }

                                            $typeLabel = $item->type === 'new_product' ? 'Produk Baru' : 'Update Harga';
                                            $hargaBaruValue = $item->harga_baru !== null ? (float) $item->harga_baru : null;
                                            $selisihValue = $item->selisih !== null
                                                ? (float) $item->selisih
                                                : ($hargaBaruValue !== null ? (float) $item->price_per_pcs - $hargaBaruValue : null);
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ method_exists($itemsUpdatePrice, 'firstItem') && $itemsUpdatePrice->firstItem() !== null ? $itemsUpdatePrice->firstItem() + $loop->index : $loop->iteration }}
                                            </td>
                                            <td>{{ $item->nomor_pengajuan ?: '-' }}</td>
                                            <td>{{ optional($item->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ optional($item->creator)->name ?: '-' }}</td>
                                            <td>{{ $item->category ?: '-' }}</td>
                                            <td>{{ $item->supplier ?: '-' }}</td>
                                            <td class="text-primary-emphasis fw-semibold">{{ $item->product_code }}</td>
                                            <td>{{ $item->description }}</td>
                                            <td class="text-end">{{ number_format((float) $item->qty, 0, '.', '') }}</td>
                                            <td>{{ $item->unit ?: '-' }}</td>
                                            <td>{{ $item->currency }}</td>
                                            <td>{{ optional($item->tanggal_lama)->format('d-m-Y') ?: '-' }}</td>
                                            <td class="text-end">{{ number_format((int) $item->price_per_pcs) }}</td>
                                            <td>{{ optional($item->tanggal_harga_baru)->format('d-m-Y') ?: '-' }}</td>
                                            <td class="text-end">{{ $hargaBaruValue !== null ? number_format($hargaBaruValue) : '-' }}</td>
                                            <td>
                                                @if (!empty($item->attachment))
                                                    <a href="{{ route('item-code.attachment', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $selisihValue !== null ? number_format($selisihValue) : '-' }}</td>
                                            <td>
                                                <span class="badge purchasing-status-badge bg-{{ $badgeClass }}">{!! $statusLabelHtml !!}</span>
                                            </td>
                                            <td class="action-cell">
                                                @php
                                                    $detailItem = [
                                                        'id' => $item->id,
                                                        'nomor_pengajuan' => $item->nomor_pengajuan,
                                                        'type' => $item->type,
                                                        'type_label' => $typeLabel,
                                                        'category' => $item->category,
                                                        'supplier' => $item->supplier,
                                                        'product_code' => $item->product_code,
                                                        'description' => $item->description,
                                                        'qty' => (int) round((float) $item->qty),
                                                        'unit' => $item->unit,
                                                        'price_per_pcs' => (float) $item->price_per_pcs,
                                                        'currency' => $item->currency,
                                                        'tanggal' => optional($item->tanggal)->format('d-m-Y'),
                                                        'tanggal_lama' => optional($item->tanggal_lama)->format('d-m-Y'),
                                                        'harga_baru' => $hargaBaruValue,
                                                        'reason_new_price' => $item->reason_new_price,
                                                        'attachment_url' => $item->attachment ? route('item-code.attachment', $item->id) : null,
                                                        'selisih' => $selisihValue,
                                                        'tanggal_harga_baru' => optional($item->tanggal_harga_baru)->format('d-m-Y'),
                                                        'status' => $statusLabel,
                                                        'status_raw' => $item->status,
                                                        'status_html' => $statusLabelHtml,
                                                        'creator' => optional($item->creator)->name,
                                                        'approver' => optional($item->approver)->name,
                                                        'approver2' => optional($item->approver2)->name,
                                                        'finisher' => optional($item->finisher)->name,
                                                    ];
                                                @endphp
                                                <div class="action-grid">
                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                            data-bs-toggle="modal" data-bs-target="#modalViewDetail"
                                                            data-item='@json($detailItem)'
                                                            onclick="openDetailModal(this)">
                                                            Detail
                                                        </button>

                                                        @if ($item->status === 'draft')
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                data-bs-toggle="modal" data-bs-target="#modalNewProduct"
                                                                data-item='@json($item)'
                                                                onclick="openEditNewProduct(this)">
                                                                Edit
                                                            </button>

                                                            <form action="{{ route('item-code.destroy', $item->id) }}" method="POST"
                                                                class="d-contents" data-delete-item-form>
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                            </form>

                                                            <form action="{{ route('item-code.submit', $item->id) }}" method="POST"
                                                                class="d-contents" data-submit-item-form
                                                                data-confirm-title="Submit data ini?"
                                                                data-confirm-text="Data akan disubmit untuk approval.">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-primary">Submit</button>
                                                            </form>
                                                        @else
                                                            {{-- placeholder kosong agar grid tetap rapi --}}
                                                            <span></span><span></span><span></span>
                                                        @endif
                                                    </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="js-empty-row">
                                            <td colspan="19" class="text-center text-muted">Belum ada data update harga.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (method_exists($itemsUpdatePrice, 'links'))
                            <div class="mt-3">
                                {{ $itemsUpdatePrice->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @include('purchasing.partials.modal-new-product')
        @include('purchasing.partials.modal-update-price')
        @include('purchasing.partials.modal-import-item-code')
        @include('purchasing.partials.modal-view-detail')

        <style>
            .purchasing-table .action-cell {
    width: 130px !important;
    min-width: 130px !important;
    max-width: 130px !important;
    padding: 0.25rem 0.2rem !important;
}

.action-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.15rem;
}

.action-grid .btn {
    font-size: 0.6rem !important;
    padding: 0.1rem 0.1rem !important;
    font-weight: 600;
    width: 100% !important;
    line-height: 1.3;
    white-space: normal !important;   /* ← INI kunci utamanya */
    word-break: break-word;
    text-align: center;
        }   

            .purchasing-section-card {
                border: 1px solid #e5e9f2;
                box-shadow: 0 8px 22px rgba(27, 39, 51, 0.06);
            }

            .purchasing-tabs .nav-link {
                color: #52606d;
                font-weight: 600;
                border: none;
                border-bottom: 2px solid transparent;
                border-radius: 0;
                padding-left: 0.9rem;
                padding-right: 0.9rem;
            }

            .purchasing-tabs .nav-link.active {
                color: #0d6efd;
                border-bottom-color: #0d6efd;
                background: transparent;
            }

            .purchasing-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 0.55rem;
                flex-wrap: wrap;
                padding: 0.62rem;
                border: 1px solid #e9edf5;
                border-radius: 0.75rem;
                background: linear-gradient(180deg, #fbfcfe 0%, #f5f8fc 100%);
                margin-bottom: 0.75rem;
            }

            .purchasing-toolbar-stats {
                flex: 1 1 100%;
            }

            .purchasing-toolbar-stats .badge {
                font-size: 0.72rem;
                font-weight: 600;
                padding: 0.33rem 0.56rem;
            }

            .purchasing-toolbar-controls {
                width: 100%;
                justify-content: space-between;
                align-items: center;
                gap: 0.45rem 0.7rem;
            }

            .purchasing-filter-form {
                flex: 1 1 500px;
                max-width: 100%;
                min-width: 260px;
            }

            .purchasing-toolbar-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 0.4rem;
            }

            .purchasing-toolbar-actions .btn,
            .purchasing-toolbar-actions form .btn {
                white-space: nowrap;
                padding: 0.3rem 0.58rem;
            }

            .purchasing-filter-input {
                min-width: 170px;
                max-width: 280px;
                flex: 1 1 260px;
            }

            .purchasing-filter-form select[name="status"] {
                width: 130px;
                min-width: 130px;
                flex: 0 0 130px;
            }

            .purchasing-date-range {
                flex: 1 1 240px;
                max-width: 250px;
                min-width: 210px;
            }

            .purchasing-date-range .form-control {
                min-width: 0;
            }

            .purchasing-date-range .range-separator {
                padding-left: 0.45rem;
                padding-right: 0.45rem;
                font-size: 0.74rem;
            }

            .purchasing-filter-form .input-group-text,
            .purchasing-filter-form .form-control,
            .purchasing-filter-form .form-select {
                border: 1.2px solid #9fb2cc;
                box-shadow: none;
                background-color: #ffffff;
            }

            .purchasing-filter-form .input-group-text {
                color: #5b6f87;
                background-color: #f6f9fd;
                border-right: 0;
            }

            .purchasing-filter-form .purchasing-filter-input .form-control {
                border-left: 0;
            }

            .purchasing-filter-form .form-control:focus,
            .purchasing-filter-form .form-select:focus {
                border-color: #6f95c6;
                box-shadow: 0 0 0 0.16rem rgba(31, 111, 209, 0.12);
            }

            .purchasing-table-wrap {
                border: 1.5px solid #b8c6da;
                border-radius: 0.85rem;
                background: #ffffff;
                box-shadow: 0 10px 20px rgba(21, 33, 54, 0.06);
            }

            .purchasing-table {
                margin-bottom: 0;
                min-width: 1080px;
                border-collapse: separate;
                border-spacing: 0;
                border: 1px solid #b8c6da;
            }

            .purchasing-table th,
            .purchasing-table td {
                border-color: #b8c6da;
                border-style: solid;
                border-width: 1px;
            }

            .purchasing-table th {
                position: sticky;
                top: 0;
                z-index: 2;
                white-space: nowrap;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                color: #ffffff;
                background: linear-gradient(180deg, #1f6fd1 0%, #1252ab 100%);
                padding: 0.6rem 0.56rem;
                border-bottom: 2px solid #0f3f84;
            }

            .purchasing-table td {
                font-size: 0.84rem;
                color: #2a3542;
                background: #ffffff;
                padding: 0.54rem 0.56rem;
            }

            .purchasing-table tbody tr:nth-child(even) td {
                background: #fbfdff;
            }

            .purchasing-table tbody tr:hover td {
                background: #eaf3ff !important;
            }

            .purchasing-table tbody td:first-child {
                text-align: center;
                font-weight: 700;
                color: #607086;
                width: 56px;
                background: #f3f7fd;
            }

            .purchasing-table tbody tr:nth-child(even) td:first-child {
                background: #edf3fb;
            }

            .purchasing-table td.text-end {
                font-variant-numeric: tabular-nums;
                font-weight: 600;
            }

            .purchasing-table th:last-child {
                position: sticky;
                right: 0;
                z-index: 4;
                background: linear-gradient(180deg, #1b66c1 0%, #104b9b 100%);
                border-left: 1px solid #b8c6da;
            }

            .purchasing-table td:last-child {
                position: sticky;
                right: 0;
                z-index: 1;
                background: #ffffff;
                border-left: 1px solid #b8c6da;
                box-shadow: -8px 0 10px -10px rgba(35, 53, 77, 0.7);
            }

            .purchasing-table tbody tr:nth-child(even) td:last-child {
                background: #fbfdff;
            }

            .purchasing-table tbody tr:hover td:last-child {
                background: #eaf3ff !important;
            }

            /* Make Status column (second-to-last) sticky to the left of the Action column */
            .purchasing-table th:nth-last-child(2) {
                position: sticky;
                top: 0;
                right: 130px; /* matches action column max-width */
                z-index: 3;
                background: linear-gradient(180deg, #1b66c1 0%, #104b9b 100%);
                border-left: 1px solid #b8c6da;
            }

            .purchasing-table td:nth-last-child(2) {
                position: sticky;
                right: 130px; /* matches action column max-width */
                z-index: 2;
                background: #ffffff;
                border-left: 1px solid #b8c6da;
                box-shadow: -6px 0 8px -8px rgba(35, 53, 77, 0.45);
            }

            .purchasing-table tbody tr:nth-child(even) td:nth-last-child(2) {
                background: #fbfdff;
            }

            .purchasing-table tbody tr:hover td:nth-last-child(2) {
                background: #eaf3ff !important;
            }

            .purchasing-status-badge {
                min-width: 72px;
                padding: 0.28rem 0.5rem;
                font-size: 0.72rem;
                text-align: center;
                font-weight: 600;
                letter-spacing: 0.01em;
            }

            .purchasing-table .action-cell {
                width: 120px;
                min-width: 120px;
                max-width: 130px;
            }

            .purchasing-table .js-empty-row td {
                background: #fffdf8;
                font-weight: 600;
            }

            .action-cell .btn {
                font-weight: 600;
            }

            @media (max-width: 992px) {
                .purchasing-toolbar-controls {
                    align-items: stretch;
                }

                .purchasing-filter-form {
                    min-width: 100%;
                    width: 100%;
                }

                .purchasing-toolbar-actions {
                    width: 100%;
                    justify-content: flex-start;
                }
            }

            @media (max-width: 768px) {
                .purchasing-filter-input {
                    min-width: 100%;
                }

                .purchasing-date-range {
                    min-width: 100%;
                    max-width: 100%;
                    flex-basis: 100%;
                }

                .purchasing-toolbar-controls {
                    flex-direction: column;
                    align-items: stretch;
                }

                .purchasing-filter-form {
                    min-width: 100%;
                    width: 100%;
                }

                .purchasing-toolbar-actions {
                    width: 100%;
                    justify-content: flex-start;
                }

                .purchasing-table {
                    min-width: 880px;
                }
            }
        </style>

        <script>
            const purchasingStoreUrl = @json(route('item-code.store'));
            const purchasingUpdateUrlTemplate = @json(route('item-code.update', ['id' => '__ID__']));
            const purchasingHistoryUrlTemplate = @json(route('item-code.history', ['id' => '__ID__']));
            const purchasingNextNomorUrlTemplate = @json(route('item-code.nextNomor', ['type' => '__TYPE__']));
            const purchasingSearchFocusStorageKey = 'purchasing_item_code_search_focus';
            const purchasingMinSearchKeywordLength = 3;

            let activeDetailItemId = null;
            let itemHistoryRawData = [];
            let itemHistorySortOrder = 'desc';
            let itemHistoryAbortController = null;
            let itemHistoryRequestToken = 0;

            function submitFormSafely(form) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                form.submit();
            }

            function saveSearchFocusState(form, input) {
                if (!form || !input || typeof window.sessionStorage === 'undefined') {
                    return;
                }

                const tabInput = form.querySelector('input[name="tab"]');
                const tabValue = tabInput ? tabInput.value : '';

                window.sessionStorage.setItem(
                    purchasingSearchFocusStorageKey,
                    JSON.stringify({
                        path: window.location.pathname,
                        tab: tabValue,
                    })
                );
            }

            function restoreSearchFocusState() {
                if (typeof window.sessionStorage === 'undefined') {
                    return;
                }

                const rawState = window.sessionStorage.getItem(purchasingSearchFocusStorageKey);
                if (!rawState) {
                    return;
                }

                window.sessionStorage.removeItem(purchasingSearchFocusStorageKey);

                let state = null;
                try {
                    state = JSON.parse(rawState);
                } catch (error) {
                    return;
                }

                if (!state || state.path !== window.location.pathname) {
                    return;
                }

                const forms = document.querySelectorAll('[data-auto-filter-form]');
                let targetInput = null;

                forms.forEach((form) => {
                    if (targetInput) {
                        return;
                    }

                    const tabInput = form.querySelector('input[name="tab"]');
                    const tabValue = tabInput ? tabInput.value : '';

                    if (tabValue !== String(state.tab || '')) {
                        return;
                    }

                    targetInput = form.querySelector('input[name="q"]');
                });

                if (!targetInput) {
                    return;
                }

                targetInput.focus();
                const textLength = targetInput.value.length;
                if (typeof targetInput.setSelectionRange === 'function') {
                    targetInput.setSelectionRange(textLength, textLength);
                }
            }

            function initAutoFilterForms() {
                const forms = document.querySelectorAll('[data-auto-filter-form]');

                forms.forEach((form) => {
                    const keywordInput = form.querySelector('input[name="q"]');
                    const statusSelect = form.querySelector('select[name="status"]');
                    const startDateInput = form.querySelector('input[name="start_date"]');
                    const endDateInput = form.querySelector('input[name="end_date"]');
                    const perPageSelect = form.querySelector('select[data-per-page-select]');
                    const perPageHidden = form.querySelector('input[data-per-page-hidden]');
                    let debounceTimer = null;
                    let lastQueryString = new URLSearchParams(new FormData(form)).toString();

                    const hasValidSearchKeyword = () => {
                        if (!keywordInput) {
                            return true;
                        }

                        const keywordLength = keywordInput.value.trim().length;

                        return keywordLength === 0 || keywordLength >= purchasingMinSearchKeywordLength;
                    };

                    const submitIfChanged = () => {
                        if (!hasValidSearchKeyword()) {
                            return;
                        }

                        const nextQueryString = new URLSearchParams(new FormData(form)).toString();
                        if (nextQueryString === lastQueryString) {
                            return;
                        }

                        saveSearchFocusState(form, keywordInput);
                        lastQueryString = nextQueryString;
                        submitFormSafely(form);
                    };

                    if (keywordInput) {
                        keywordInput.addEventListener('input', () => {
                            window.clearTimeout(debounceTimer);
                            debounceTimer = window.setTimeout(submitIfChanged, 350);
                        });
                    }

                    if (statusSelect) {
                        statusSelect.addEventListener('change', submitIfChanged);
                    }

                    if (startDateInput) {
                        startDateInput.addEventListener('change', submitIfChanged);
                    }

                    if (endDateInput) {
                        endDateInput.addEventListener('change', submitIfChanged);
                    }

                    if (perPageSelect) {
                        perPageSelect.addEventListener('change', () => {
                            if (perPageHidden) {
                                perPageHidden.value = perPageSelect.value;
                            }

                            submitIfChanged();
                        });
                    }

                    form.addEventListener('submit', (event) => {
                        if (!hasValidSearchKeyword()) {
                            event.preventDefault();
                            return;
                        }

                        saveSearchFocusState(form, keywordInput);
                    });
                });

                restoreSearchFocusState();
            }

            function initDeleteAlerts() {
                const deleteForms = document.querySelectorAll('[data-delete-item-form]');

                deleteForms.forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();

                        if (typeof Swal === 'undefined') {
                            if (window.confirm('Yakin ingin menghapus data ini?')) {
                                form.submit();
                            }
                            return;
                        }

                        Swal.fire({
                            title: 'Hapus data item?',
                            text: 'Data yang dihapus tidak dapat dikembalikan.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, hapus',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });
            }

            function initSubmitItemAlerts() {
                const submitItemForms = document.querySelectorAll('[data-submit-item-form]');

                submitItemForms.forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();

                        const confirmTitle = form.getAttribute('data-confirm-title') || 'Submit data ini?';
                        const confirmText = form.getAttribute('data-confirm-text') || 'Data akan disubmit untuk approval.';

                        if (typeof Swal === 'undefined') {
                            if (window.confirm(confirmText)) {
                                form.submit();
                            }
                            return;
                        }

                        Swal.fire({
                            title: confirmTitle,
                            text: confirmText,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#0d6efd',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, submit',
                            cancelButtonText: 'Batal',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });
            }

            function initSubmitAllAlerts() {
                const submitAllForms = document.querySelectorAll('[data-submit-all-form]');

                submitAllForms.forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();

                        const confirmTitle = form.getAttribute('data-confirm-title') || 'Submit semua data Draft?';
                        const confirmText = form.getAttribute('data-confirm-text') || 'Semua data Draft akan disubmit untuk approval.';

                        if (typeof Swal === 'undefined') {
                            if (window.confirm(confirmText)) {
                                form.submit();
                            }
                            return;
                        }

                        Swal.fire({
                            title: confirmTitle,
                            text: confirmText,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#0d6efd',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, submit semua',
                            cancelButtonText: 'Batal',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });
            }

            function parseButtonData(button) {
                try {
                    return JSON.parse(button.getAttribute('data-item'));
                } catch (error) {
                    return {};
                }
            }

            function setFormMethod(methodFieldId, isPutMethod) {
                const methodField = document.getElementById(methodFieldId);
                methodField.disabled = !isPutMethod;
                methodField.value = isPutMethod ? 'PUT' : '';
            }

            function fillInput(id, value) {
                const el = document.getElementById(id);
                if (!el) {
                    return;
                }
                el.value = value ?? '';
            }

            async function fillNextNomorPengajuan(type, targetInputId) {
                const targetInput = document.getElementById(targetInputId);
                if (!targetInput) {
                    return;
                }

                targetInput.value = 'Generating...';

                try {
                    const response = await fetch(
                        purchasingNextNomorUrlTemplate.replace('__TYPE__', encodeURIComponent(type)),
                        {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Gagal mengambil nomor pengajuan');
                    }

                    const payload = await response.json();
                    targetInput.value = payload.nomor_pengajuan || '';
                } catch (error) {
                    targetInput.value = '-';
                }
            }

            function toDateInputValue(value) {
                if (!value) {
                    return '';
                }

                if (typeof value === 'string' && value.includes('T')) {
                    return value.split('T')[0];
                }

                return value;
            }

            function getTodayDateValue() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }

            function openCreateNewProduct(shouldAutofillNomor = true) {
                const form = document.getElementById('formNewProduct');
                form.reset();
                form.action = purchasingStoreUrl;
                const today = getTodayDateValue();

                setFormMethod('new_product_method', false);
                fillInput('new_product_type', 'new_product');
                fillInput('new_category', 'Material');
                fillInput('new_supplier', '');
                fillInput('new_price_per_pcs', '');
                fillInput('new_tanggal', today);
                fillInput('new_nomor_pengajuan', '');
                fillInput('new_qty', 1);

                if (shouldAutofillNomor) {
                    fillNextNomorPengajuan('new_product', 'new_nomor_pengajuan');
                }

                document.getElementById('new_product_modal_title').textContent = 'Tambah Produk Baru';
                document.getElementById('new_product_submit_label').textContent = 'Simpan';
            }

            function openImportModal(type) {
                const importType = type === 'update_price' ? 'update_price' : 'new_product';
                const isUpdatePrice = importType === 'update_price';
                const importTemplateBaseUrl = @json(route('item-code.importTemplate'));

                fillInput('import_type', importType);
                fillInput('import_tab', importType);

                const title = isUpdatePrice
                    ? 'Import Data - Update Harga'
                    : 'Import Data - Produk Baru';
                const hint = isUpdatePrice
                    ? 'Template Update Harga wajib persis urutan sistem. Kolom nomor_pengajuan boleh dikosongkan untuk auto-generate.'
                    : 'Template Produk Baru wajib persis urutan sistem. Kolom nomor_pengajuan boleh dikosongkan untuk auto-generate.';
                const columnsText = isUpdatePrice
                    ? 'nomor_pengajuan, tanggal, creator, category, supplier, product_code, description, qty, unit, currency, effective_date_current, current_price, effective_date_new, new_price, reason_new_price, selisih'
                    : 'nomor_pengajuan, tanggal, creator, category, supplier, product_code, description, qty, unit, currency, price';
                const columnsNote = isUpdatePrice
                    ? 'Kolom wajib persis urutan sistem Update Harga.'
                    : 'Kolom wajib persis urutan sistem Produk Baru.';

                const titleEl = document.getElementById('import_modal_title');
                const hintEl = document.getElementById('import_modal_hint');
                const columnsEl = document.getElementById('import_columns_text');
                const columnsNoteEl = document.getElementById('import_columns_note');
                if (titleEl) {
                    titleEl.textContent = title;
                }
                if (hintEl) {
                    hintEl.textContent = hint;
                }
                if (columnsEl) {
                    columnsEl.textContent = columnsText;
                }
                if (columnsNoteEl) {
                    columnsNoteEl.textContent = columnsNote;
                }

                const templateLinkEl = document.getElementById('import_template_link');
                if (templateLinkEl) {
                    templateLinkEl.href = `${importTemplateBaseUrl}?type=${importType}`;
                }

                const fileInput = document.getElementById('import_file');
                if (fileInput) {
                    fileInput.value = '';
                }
            }

            function openEditNewProduct(button) {
                const item = parseButtonData(button);
                openCreateNewProduct(false);

                const form = document.getElementById('formNewProduct');
                form.action = purchasingUpdateUrlTemplate.replace('__ID__', item.id);
                setFormMethod('new_product_method', true);

                fillInput('new_product_code', item.product_code);
                fillInput('new_description', item.description);
                fillInput('new_category', item.category || 'Material');
                fillInput('new_supplier', item.supplier || '');
                fillInput('new_nomor_pengajuan', item.nomor_pengajuan || '');
                fillInput('new_qty', item.qty !== null && item.qty !== undefined && Number.isFinite(Number(item.qty))
                    ? Math.round(Number(item.qty))
                    : item.qty);
                fillInput('new_unit', item.unit);
                fillInput('new_price_per_pcs', Number.isFinite(Number(item.price_per_pcs))
                    ? Math.round(Number(item.price_per_pcs))
                    : item.price_per_pcs);
                fillInput('new_currency', item.currency);
                fillInput('new_tanggal', toDateInputValue(item.tanggal));

                document.getElementById('new_product_modal_title').textContent = 'Edit Produk Baru';
                document.getElementById('new_product_submit_label').textContent = 'Update';
            }

            function openCreateUpdatePrice(shouldAutofillNomor = true) {
                const form = document.getElementById('formUpdatePrice');
                form.reset();
                form.action = purchasingStoreUrl;
                const today = getTodayDateValue();

                setFormMethod('update_price_method', false);
                fillInput('update_price_type', 'update_price');
                fillInput('update_category', 'Material');
                fillInput('update_supplier', '');
                fillInput('update_price_per_pcs', '');
                fillInput('update_harga_baru', '');
                fillInput('update_reason_new_price', '');
                fillInput('update_tanggal', today);
                fillInput('update_nomor_pengajuan', '');
                fillInput('update_qty', 1);

                if (shouldAutofillNomor) {
                    fillNextNomorPengajuan('update_price', 'update_nomor_pengajuan');
                }

                document.getElementById('update_price_modal_title').textContent = 'Tambah Update Harga';
                document.getElementById('update_price_submit_label').textContent = 'Simpan';
            }

            function openEditUpdatePrice(button) {
                const item = parseButtonData(button);
                openCreateUpdatePrice(false);

                const form = document.getElementById('formUpdatePrice');
                form.action = purchasingUpdateUrlTemplate.replace('__ID__', item.id);
                setFormMethod('update_price_method', true);

                fillInput('update_product_code', item.product_code);
                fillInput('update_description', item.description);
                fillInput('update_category', item.category || 'Material');
                fillInput('update_supplier', item.supplier || '');
                fillInput('update_nomor_pengajuan', item.nomor_pengajuan || '');
                fillInput('update_qty', item.qty !== null && item.qty !== undefined && Number.isFinite(Number(item.qty))
                    ? Math.round(Number(item.qty))
                    : item.qty);
                fillInput('update_unit', item.unit);
                fillInput('update_price_per_pcs', Number.isFinite(Number(item.price_per_pcs))
                    ? Math.round(Number(item.price_per_pcs))
                    : item.price_per_pcs);
                fillInput('update_currency', item.currency);
                fillInput('update_tanggal', toDateInputValue(item.tanggal));
                fillInput('update_tanggal_lama', toDateInputValue(item.tanggal_lama));
                fillInput('update_harga_baru', Number.isFinite(Number(item.harga_baru))
                    ? Math.round(Number(item.harga_baru))
                    : item.harga_baru);
                fillInput('update_tanggal_harga_baru', toDateInputValue(item.tanggal_harga_baru));
                fillInput('update_reason_new_price', item.reason_new_price || '');

                document.getElementById('update_price_modal_title').textContent = 'Edit Update Harga';
                document.getElementById('update_price_submit_label').textContent = 'Update';
            }

            function calculateSelisih(hargaLamaValue, hargaBaruValue) {
                const hargaLama = Number.parseFloat(hargaLamaValue);
                const hargaBaru = Number.parseFloat(hargaBaruValue);

                if (!Number.isFinite(hargaLama) || !Number.isFinite(hargaBaru)) {
                    return null;
                }

                return hargaLama - hargaBaru;
            }

            function setDetail(id, value) {
                document.getElementById(id).textContent = value ?? '-';
            }

            function setDetailAttachment(url) {
                const target = document.getElementById('detail_attachment');
                if (!target) {
                    return;
                }

                if (!url) {
                    target.textContent = '-';
                    return;
                }

                target.innerHTML = `<a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>`;
            }

            function setStatusBadge(statusRawValue, labelHtml) {
                const badge = document.getElementById('detail_status');
                if (!badge) return;

                const rawStatus = (statusRawValue || '-').toString();
                const status = rawStatus.toLowerCase();
                const classMap = {
                    draft: 'text-bg-secondary',
                    submitted: 'text-bg-warning',
                    approved_1: 'text-bg-info',
                    approved_2: 'text-bg-primary',
                    finished: 'text-bg-success',
                    rejected: 'text-bg-danger',
                };
                const statusClass = status.includes('rejected') ? 'text-bg-danger' : (classMap[status] || 'text-bg-secondary');

                badge.className = `badge ${statusClass}`;
                if (typeof labelHtml !== 'undefined' && labelHtml !== null) {
                    badge.innerHTML = labelHtml;
                } else {
                    badge.textContent = rawStatus;
                }
            }

            function openDetailModal(button) {
                const item = parseButtonData(button);
                const detailPriceLabel = document.getElementById('detail_price_label');
                const historyButton = document.getElementById('btnOpenItemHistory');
                const isUpdatePrice = item.type === 'update_price';

                if (detailPriceLabel) {
                    detailPriceLabel.textContent = isUpdatePrice ? 'Current Price' : 'Price/Pcs';
                }

                activeDetailItemId = item.id || null;

                if (historyButton) {
                    historyButton.disabled = !activeDetailItemId;
                }

                const selisih = item.selisih !== null && item.selisih !== undefined
                    ? Number(item.selisih)
                    : calculateSelisih(item.price_per_pcs, item.harga_baru);

                setDetail('detail_type', item.type_label || '-');
                setDetail('detail_nomor_pengajuan', item.nomor_pengajuan || '-');
                setDetail('detail_category', item.category || '-');
                setDetail('detail_supplier', item.supplier || '-');
                setDetail('detail_product_code', item.product_code || '-');
                setDetail('detail_description', item.description || '-');
                setDetail('detail_qty', item.qty !== null && item.qty !== undefined ? Number(item.qty).toFixed(0) : '-');
                setDetail('detail_unit', item.unit || '-');
                setDetail('detail_price_per_pcs', item.price_per_pcs !== null && item.price_per_pcs !== undefined ? Number(item.price_per_pcs).toFixed(2) : '-');
                setDetail('detail_currency', item.currency || '-');
                setDetail('detail_tanggal', item.tanggal || '-');
                setDetail('detail_tanggal_lama', item.tanggal_lama || '-');
                setDetail('detail_harga_baru', item.harga_baru !== null && item.harga_baru !== undefined ? Number(item.harga_baru).toFixed(2) : '-');
                setDetail('detail_reason_new_price', item.reason_new_price || '-');
                setDetailAttachment(item.attachment_url || null);
                setDetail('detail_selisih', selisih !== null && Number.isFinite(selisih) ? selisih.toFixed(2) : '-');
                setDetail('detail_tanggal_harga_baru', item.tanggal_harga_baru || '-');
                setStatusBadge(item.status_raw || item.status || '-', item.status_html || item.status || '-');
                setDetail('detail_creator', item.creator || '-');
                setDetail('detail_approver', item.approver || '-');
                setDetail('detail_approver2', item.approver2 || '-');
                setDetail('detail_finisher', item.finisher || '-');
            }

            function escapeHtml(value) {
                return String(value ?? '-')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function getStatusBadgeClass(status) {
                const normalized = String(status || '').toLowerCase();

                switch (normalized) {
                    case 'draft':
                        return 'bg-secondary';
                    case 'submitted':
                        return 'bg-warning text-dark';
                    case 'approved 1':
                    case 'approved_1':
                        return 'bg-info';
                    case 'approved 2':
                    case 'approved_2':
                        return 'bg-primary';
                    case 'finished':
                        return 'bg-success';
                    default:
                        return 'bg-secondary';
                }
            }

            function parseHistoryDate(dateString) {
                if (!dateString) {
                    return 0;
                }

                const parts = dateString.split(' ');
                if (parts.length !== 2) {
                    return 0;
                }

                const dateParts = parts[0].split('-');
                const timeParts = parts[1].split(':');
                if (dateParts.length !== 3 || timeParts.length !== 3) {
                    return 0;
                }

                const day = Number.parseInt(dateParts[0], 10);
                const month = Number.parseInt(dateParts[1], 10) - 1;
                const year = Number.parseInt(dateParts[2], 10);
                const hour = Number.parseInt(timeParts[0], 10);
                const minute = Number.parseInt(timeParts[1], 10);
                const second = Number.parseInt(timeParts[2], 10);

                return new Date(year, month, day, hour, minute, second).getTime();
            }

            function formatHistoryText(text) {
                const escaped = escapeHtml(text || '-');
                return escaped.replace(/;\s*|\s\|\s/g, '<br>');
            }

            function renderItemHistoryRows() {
                const tableBody = document.getElementById('itemHistoryTableBody');
                if (!tableBody) {
                    return;
                }

                tableBody.innerHTML = '';

                if (!itemHistoryRawData.length) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Belum ada histori</td></tr>';
                    return;
                }

                const sortedData = [...itemHistoryRawData].sort((a, b) => {
                    const tsA = parseHistoryDate(a.created_at);
                    const tsB = parseHistoryDate(b.created_at);
                    return itemHistorySortOrder === 'asc' ? tsA - tsB : tsB - tsA;
                });

                sortedData.forEach((item, index) => {
                    const statusClass = getStatusBadgeClass(item.status);
                    const row = document.createElement('tr');

                    row.innerHTML = `
                        <td class="history-col-no">${index + 1}</td>
                        <td class="history-keterangan-cell">${formatHistoryText(item.keterangan)}</td>
                        <td><span class="badge ${statusClass}">${escapeHtml(item.status || '-')}</span></td>
                        <td>${escapeHtml(item.modified_at || '-')}</td>
                        <td>${escapeHtml(item.created_at || '-')}</td>
                    `;

                    tableBody.appendChild(row);
                });
            }

            function toggleItemHistorySort() {
                itemHistorySortOrder = itemHistorySortOrder === 'asc' ? 'desc' : 'asc';

                const sortIcon = document.getElementById('itemHistoryDateSortIcon');
                if (sortIcon) {
                    sortIcon.textContent = itemHistorySortOrder === 'asc' ? '↑' : '↓';
                }

                renderItemHistoryRows();
            }

            function cleanupModalState() {
                if (document.querySelector('.modal.show')) {
                    return;
                }

                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
            }

            function attachModalCleanupHandlers() {
                ['modalViewDetail', 'modalItemHistory'].forEach((modalId) => {
                    const modalEl = document.getElementById(modalId);
                    if (!modalEl) {
                        return;
                    }

                    modalEl.addEventListener('hidden.bs.modal', cleanupModalState);
                });
            }

            function hideDetailModalThen(callback) {
                const detailModalEl = document.getElementById('modalViewDetail');
                if (!detailModalEl || !detailModalEl.classList.contains('show') || typeof bootstrap === 'undefined') {
                    callback();
                    return;
                }

                const detailModal = bootstrap.Modal.getOrCreateInstance(detailModalEl);
                const handleHidden = () => {
                    detailModalEl.removeEventListener('hidden.bs.modal', handleHidden);
                    callback();
                };

                detailModalEl.addEventListener('hidden.bs.modal', handleHidden);
                detailModal.hide();
            }

            async function showItemHistory(itemId) {
                const tableBody = document.getElementById('itemHistoryTableBody');
                const sortIcon = document.getElementById('itemHistoryDateSortIcon');
                const modalEl = document.getElementById('modalItemHistory');

                if (!tableBody) {
                    return;
                }

                if (modalEl && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }

                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Memuat histori...</td></tr>';
                itemHistoryRawData = [];
                itemHistorySortOrder = 'desc';
                const requestToken = ++itemHistoryRequestToken;

                if (itemHistoryAbortController) {
                    itemHistoryAbortController.abort();
                }

                itemHistoryAbortController = new AbortController();
                const timeoutId = window.setTimeout(() => {
                    itemHistoryAbortController?.abort();
                }, 15000);

                if (sortIcon) {
                    sortIcon.textContent = '↓';
                }

                try {
                    const response = await fetch(
                        purchasingHistoryUrlTemplate.replace('__ID__', String(itemId)),
                        {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            signal: itemHistoryAbortController.signal,
                        }
                    );

                    if (requestToken !== itemHistoryRequestToken) {
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('Gagal memuat histori item code.');
                    }

                    const payload = await response.json();
                    itemHistoryRawData = Array.isArray(payload) ? payload : [];
                    renderItemHistoryRows();
                } catch (error) {
                    if (requestToken !== itemHistoryRequestToken) {
                        return;
                    }

                    const isTimeout = error && error.name === 'AbortError';
                    tableBody.innerHTML = isTimeout
                        ? '<tr><td colspan="5" class="text-center text-danger">Memuat histori timeout. Coba lagi.</td></tr>'
                        : '<tr><td colspan="5" class="text-center text-danger">Gagal memuat histori.</td></tr>';
                } finally {
                    window.clearTimeout(timeoutId);
                    if (requestToken === itemHistoryRequestToken) {
                        itemHistoryAbortController = null;
                    }
                }
            }

            function openItemHistoryFromDetail() {
                if (!activeDetailItemId) {
                    return;
                }

                hideDetailModalThen(() => {
                    showItemHistory(activeDetailItemId);
                });
            }

            initAutoFilterForms();
            initDeleteAlerts();
            initSubmitItemAlerts();
            initSubmitAllAlerts();
            attachModalCleanupHandlers();

        </script>
    </main>
@endsection
