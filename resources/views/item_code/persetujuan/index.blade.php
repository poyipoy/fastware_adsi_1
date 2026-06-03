@extends('layout')

@section('content')
    <main id="main" class="main">
        @php
            $activeTab = $activeTab ?? 'new_product';
            $filters = $filters ?? ['q' => null, 'status' => null];
            $statsByType = $statsByType ?? [
                'new_product' => ['total' => 0, 'submitted' => 0, 'approved_1' => 0, 'approved_2' => 0, 'finished' => 0],
                'update_price' => ['total' => 0, 'submitted' => 0, 'approved_1' => 0, 'approved_2' => 0, 'finished' => 0],
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
            <h1>Persetujuan Item Code</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Item Code - Persetujuan Item Code</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="card itemcode-section-card">
                <div class="card-header pb-0">
                    <ul class="nav nav-tabs card-header-tabs itemcode-tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'new_product' ? 'active' : '' }}"
                                href="{{ route('item-code.approval', ['tab' => 'new_product']) }}">
                                Produk Baru
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'update_price' ? 'active' : '' }}"
                                href="{{ route('item-code.approval', ['tab' => 'update_price']) }}">
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

                    <div class="{{ $activeTab === 'new_product' ? '' : 'd-none' }}">
                        @php
                            $newStats = $statsByType['new_product'] ?? ['total' => 0, 'submitted' => 0, 'approved_1' => 0, 'approved_2' => 0, 'finished' => 0];
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

                        <div class="itemcode-toolbar">
                            <div class="d-flex flex-wrap gap-2 itemcode-toolbar-stats">
                                <span class="badge rounded-pill text-bg-light border">Total: {{ $newStats['total'] }}</span>
                                <span class="badge rounded-pill text-bg-warning">Submitted: {{ $newStats['submitted'] }}</span>
                                <span class="badge rounded-pill text-bg-info">Approved 1: {{ $newStats['approved_1'] ?? 0 }}</span>
                                <span class="badge rounded-pill text-bg-primary">Approved 2: {{ $newStats['approved_2'] ?? 0 }}</span>
                                <span class="badge rounded-pill text-bg-success">Finished: {{ $newStats['finished'] }}</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center itemcode-toolbar-controls">
                                <form method="GET" action="{{ route('item-code.approval') }}"
                                    class="d-flex flex-wrap gap-2 align-items-center itemcode-filter-form" data-auto-filter-form>
                                    <input type="hidden" name="tab" value="new_product">
                                    <input type="hidden" name="per_page" value="{{ $perPage }}" data-per-page-hidden>

                                    <div class="input-group input-group-sm itemcode-filter-input">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" name="q" class="form-control"
                                            value="{{ $activeTab === 'new_product' ? ($filters['q'] ?? '') : '' }}"
                                            placeholder="Cari Nomor / Supplier / Category / Product Code / Description / Unit / Nama">
                                    </div>

                                    <select class="form-select form-select-sm" name="status">
                                        @php
                                            $newSelectedStatus = $activeTab === 'new_product' ? ($filters['status'] ?? '') : '';
                                        @endphp
                                        <option value="" {{ $newSelectedStatus === '' ? 'selected' : '' }}>Semua Status</option>
                                        @if ($canApprove)
                                            <option value="submitted" {{ $newSelectedStatus === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                        @endif
                                        <option value="approved_1" {{ $newSelectedStatus === 'approved_1' ? 'selected' : '' }}>Approved 1</option>
                                        <option value="approved_2" {{ $newSelectedStatus === 'approved_2' ? 'selected' : '' }}>Approved 2</option>
                                        <option value="finished" {{ $newSelectedStatus === 'finished' ? 'selected' : '' }}>Finished</option>
                                    </select>

                                    <div class="input-group input-group-sm itemcode-date-range">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" name="start_date" class="form-control"
                                            value="{{ $activeTab === 'new_product' ? ($filters['start_date'] ?? '') : '' }}"
                                            aria-label="Tanggal mulai">
                                        <span class="input-group-text range-separator">s/d</span>
                                        <input type="date" name="end_date" class="form-control"
                                            value="{{ $activeTab === 'new_product' ? ($filters['end_date'] ?? '') : '' }}"
                                            aria-label="Tanggal selesai">
                                    </div>

                                    <a href="{{ route('item-code.approval', ['tab' => 'new_product', 'per_page' => $perPage]) }}"
                                        class="btn btn-sm btn-outline-secondary">Reset</a>

                                    <div class="w-100"></div>

                                    <div class="d-flex align-items-center gap-1 small text-muted">
                                        <span>Show</span>
                                        <select class="form-select form-select-sm" data-per-page-select style="width: auto;">
                                            <option value="100" {{ $perPage === 100 ? 'selected' : '' }}>100</option>
                                            <option value="500" {{ $perPage === 500 ? 'selected' : '' }}>500</option>
                                        </select>
                                        <span>entries</span>
                                    </div>
                                </form>

                                <div class="itemcode-toolbar-actions">

                                <a href="{{ route('item-code.exportApproval', $newFilterParams) }}"
                                    class="btn btn-sm btn-outline-success">
                                    Export
                                </a>

                                @if ($canApprove1)
                                    <form action="{{ route('item-code.approveAll') }}" method="POST" class="d-inline"
                                        data-approval-action-form data-action-type="approve_all"
                                        data-confirm-text="Approve 1 semua data Submitted pada tab Produk Baru?">
                                        @csrf
                                        <input type="hidden" name="tab" value="new_product">
                                        <button type="submit" class="btn btn-sm btn-success"
                                            {{ (int) $newStats['submitted'] < 1 ? 'disabled' : '' }}>
                                            Approve 1 All
                                        </button>
                                    </form>
                                @endif

                                @if ($canApprove2)
                                    <form action="{{ route('item-code.approve2All') }}" method="POST" class="d-inline"
                                        data-approval-action-form data-action-type="approve_all"
                                        data-confirm-text="Approve 2 semua data Approved 1 pada tab Produk Baru?">
                                        @csrf
                                        <input type="hidden" name="tab" value="new_product">
                                        <button type="submit" class="btn btn-sm btn-info"
                                            {{ (int) ($newStats['approved_1'] ?? 0) < 1 ? 'disabled' : '' }}>
                                            Approve 2 All
                                        </button>
                                    </form>
                                @endif

                                @if ($canFinish)
                                    <form action="{{ route('item-code.finishAll') }}" method="POST" class="d-inline"
                                        data-approval-action-form data-action-type="finish_all"
                                        data-confirm-text="Finish semua data Approved 2 pada tab Produk Baru?">
                                        @csrf
                                        <input type="hidden" name="tab" value="new_product">
                                        <button type="submit" class="btn btn-sm btn-primary"
                                            {{ (int) ($newStats['approved_2'] ?? 0) < 1 ? 'disabled' : '' }}>
                                            Finished All
                                        </button>
                                    </form>
                                @endif
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive itemcode-table-wrap">
                            <table id="approval-new-table" class="table table-bordered table-striped align-middle itemcode-table">
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
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th style="width:130px; min-width:130px; max-width:130px;">Aksi</th>
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
                                            <td class="text-end">{{ number_format((float) $item->price_per_pcs, 2) }}</td>
                                            <td>{{ $item->reason_new_price ?: '-' }}</td>
                                            <td><span class="badge itemcode-status-badge bg-{{ $badgeClass }}">{!! $statusLabelHtml !!}</span></td>
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
                                                        'harga_baru' => $item->harga_baru !== null ? (float) $item->harga_baru : null,
                                                        'reason_new_price' => $item->reason_new_price,
                                                        'attachment_url' => $item->attachment ? route('item-code.attachment', $item->id) : null,
                                                        'selisih' => $item->selisih !== null ? (float) $item->selisih : null,
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

                                                    @if ($canApprove1 && $item->status === 'submitted')
                                                        <form action="{{ route('item-code.approve', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="approve" data-confirm-text="Approve 1 data ini?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">Approve 1</button>
                                                        </form>

                                                        <form action="{{ route('item-code.reject', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="reject" data-confirm-text="Reject data ini ke Draft?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                        </form>
                                                    @elseif ($canApprove2 && $item->status === 'approved_1')
                                                        <form action="{{ route('item-code.approve2', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="approve" data-confirm-text="Approve 2 data ini?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-info">Approve 2</button>
                                                        </form>

                                                        <form action="{{ route('item-code.reject', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="reject" data-confirm-text="Reject data ini ke Draft?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                        </form>
                                                    @elseif ($canFinish && $item->status === 'approved_2')
                                                        <form action="{{ route('item-code.finish', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="finish" data-confirm-text="Finish data ini?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-primary">Finish</button>
                                                        </form>
                                                        <span></span>
                                                    @else
                                                        <span></span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="js-empty-row">
                                            <td colspan="15" class="text-center text-muted">Belum ada data produk baru.</td>
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
                            $updateStats = $statsByType['update_price'] ?? ['total' => 0, 'submitted' => 0, 'approved_1' => 0, 'approved_2' => 0, 'finished' => 0];
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

                        <div class="itemcode-toolbar">
                            <div class="d-flex flex-wrap gap-2 itemcode-toolbar-stats">
                                <span class="badge rounded-pill text-bg-light border">Total: {{ $updateStats['total'] }}</span>
                                <span class="badge rounded-pill text-bg-warning">Submitted: {{ $updateStats['submitted'] }}</span>
                                <span class="badge rounded-pill text-bg-info">Approved 1: {{ $updateStats['approved_1'] ?? 0 }}</span>
                                <span class="badge rounded-pill text-bg-primary">Approved 2: {{ $updateStats['approved_2'] ?? 0 }}</span>
                                <span class="badge rounded-pill text-bg-success">Finished: {{ $updateStats['finished'] }}</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center itemcode-toolbar-controls">
                                <form method="GET" action="{{ route('item-code.approval') }}"
                                    class="d-flex flex-wrap gap-2 align-items-center itemcode-filter-form" data-auto-filter-form>
                                    <input type="hidden" name="tab" value="update_price">
                                    <input type="hidden" name="per_page" value="{{ $perPage }}" data-per-page-hidden>

                                    <div class="input-group input-group-sm itemcode-filter-input">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" name="q" class="form-control"
                                            value="{{ $activeTab === 'update_price' ? ($filters['q'] ?? '') : '' }}"
                                            placeholder="Cari Nomor / Supplier / Category / Product Code / Description / Unit / Nama">
                                    </div>

                                    <select class="form-select form-select-sm" name="status">
                                        @php
                                            $updateSelectedStatus = $activeTab === 'update_price' ? ($filters['status'] ?? '') : '';
                                        @endphp
                                        <option value="" {{ $updateSelectedStatus === '' ? 'selected' : '' }}>Semua Status</option>
                                        @if ($canApprove)
                                            <option value="submitted" {{ $updateSelectedStatus === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                        @endif
                                        <option value="approved_1" {{ $updateSelectedStatus === 'approved_1' ? 'selected' : '' }}>Approved 1</option>
                                        <option value="approved_2" {{ $updateSelectedStatus === 'approved_2' ? 'selected' : '' }}>Approved 2</option>
                                        <option value="finished" {{ $updateSelectedStatus === 'finished' ? 'selected' : '' }}>Finished</option>
                                    </select>

                                    <div class="input-group input-group-sm itemcode-date-range">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" name="start_date" class="form-control"
                                            value="{{ $activeTab === 'update_price' ? ($filters['start_date'] ?? '') : '' }}"
                                            aria-label="Tanggal mulai">
                                        <span class="input-group-text range-separator">s/d</span>
                                        <input type="date" name="end_date" class="form-control"
                                            value="{{ $activeTab === 'update_price' ? ($filters['end_date'] ?? '') : '' }}"
                                            aria-label="Tanggal selesai">
                                    </div>

                                    <a href="{{ route('item-code.approval', ['tab' => 'update_price', 'per_page' => $perPage]) }}"
                                        class="btn btn-sm btn-outline-secondary">Reset</a>

                                    <div class="w-100"></div>

                                    <div class="d-flex align-items-center gap-1 small text-muted">
                                        <span>Show</span>
                                        <select class="form-select form-select-sm" data-per-page-select style="width: auto;">
                                            <option value="100" {{ $perPage === 100 ? 'selected' : '' }}>100</option>
                                            <option value="500" {{ $perPage === 500 ? 'selected' : '' }}>500</option>
                                        </select>
                                        <span>entries</span>
                                    </div>
                                </form>

                                <div class="itemcode-toolbar-actions">

                                <a href="{{ route('item-code.exportApproval', $updateFilterParams) }}"
                                    class="btn btn-sm btn-outline-success">
                                    Export
                                </a>

                                @if ($canApprove1)
                                    <form action="{{ route('item-code.approveAll') }}" method="POST" class="d-inline"
                                        data-approval-action-form data-action-type="approve_all"
                                        data-confirm-text="Approve 1 semua data Submitted pada tab Update Harga?">
                                        @csrf
                                        <input type="hidden" name="tab" value="update_price">
                                        <button type="submit" class="btn btn-sm btn-success"
                                            {{ (int) $updateStats['submitted'] < 1 ? 'disabled' : '' }}>
                                            Approve 1 All
                                        </button>
                                    </form>
                                @endif

                                @if ($canApprove2)
                                    <form action="{{ route('item-code.approve2All') }}" method="POST" class="d-inline"
                                        data-approval-action-form data-action-type="approve_all"
                                        data-confirm-text="Approve 2 semua data Approved 1 pada tab Update Harga?">
                                        @csrf
                                        <input type="hidden" name="tab" value="update_price">
                                        <button type="submit" class="btn btn-sm btn-info"
                                            {{ (int) ($updateStats['approved_1'] ?? 0) < 1 ? 'disabled' : '' }}>
                                            Approve 2 All
                                        </button>
                                    </form>
                                @endif

                                @if ($canFinish)
                                    <form action="{{ route('item-code.finishAll') }}" method="POST" class="d-inline"
                                        data-approval-action-form data-action-type="finish_all"
                                        data-confirm-text="Finish semua data Approved 2 pada tab Update Harga?">
                                        @csrf
                                        <input type="hidden" name="tab" value="update_price">
                                        <button type="submit" class="btn btn-sm btn-primary"
                                            {{ (int) ($updateStats['approved_2'] ?? 0) < 1 ? 'disabled' : '' }}>
                                            Finished All
                                        </button>
                                    </form>
                                @endif
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive itemcode-table-wrap">
                            <table id="approval-update-table" class="table table-bordered table-striped align-middle itemcode-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nomor Pengajuan</th>
                                        <th>Tanggal</th>
                                        <th>Nama</th>
                                        <th>Category</th>
                                        <th>Supplier</th>
                                        <th>Product<br>Code</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Currency</th>
                                        <th>Eff. Date <br>(Current)</th>
                                        <th>Current <br>Price</th>
                                        <th>Eff. Date<br> (New)</th>
                                        <th>New <br>Price</th>
                                        <th>Reason</th>
                                        <th>Lihat <br>File</th>
                                        <th>Selisih</th>
                                        <th>Status</th>
                                        <th style="width:130px; min-width:130px; max-width:130px;">Aksi</th>
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
                                            <td class="desc-cell">{{ $item->description }}</td>
                                            <td class="text-end">{{ number_format((float) $item->qty, 0, '.', '') }}</td>
                                            <td>{{ $item->unit ?: '-' }}</td>
                                            <td>{{ $item->currency }}</td>
                                            <td>{{ optional($item->tanggal_lama)->format('d-m-Y') ?: '-' }}</td>
                                            <td class="text-end">{{ number_format((float) $item->price_per_pcs, 2) }}</td>
                                            <td>{{ optional($item->tanggal_harga_baru)->format('d-m-Y') ?: '-' }}</td>
                                            <td class="text-end">{{ $hargaBaruValue !== null ? number_format($hargaBaruValue, 2) : '-' }}</td>
                                            <td>{{ $item->reason_new_price ?: '-' }}</td>
                                            <td>
                                                @if (!empty($item->attachment))
                                                    <a href="{{ route('item-code.attachment', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $selisihValue !== null ? number_format($selisihValue, 2) : '-' }}</td>
                                            <td><span class="badge itemcode-status-badge bg-{{ $badgeClass }}">{!! $statusLabelHtml !!}</span></td>
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
                                                        'harga_baru' => $item->harga_baru !== null ? (float) $item->harga_baru : null,
                                                        'reason_new_price' => $item->reason_new_price,
                                                        'attachment_url' => $item->attachment ? route('item-code.attachment', $item->id) : null,
                                                        'selisih' => $item->selisih !== null ? (float) $item->selisih : null,
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

                                                    @if ($canApprove1 && $item->status === 'submitted')
                                                        <form action="{{ route('item-code.approve', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="approve" data-confirm-text="Approve 1 data ini?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">Approve 1</button>
                                                        </form>

                                                        <form action="{{ route('item-code.reject', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="reject" data-confirm-text="Reject data ini ke Draft?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                        </form>
                                                    @elseif ($canApprove2 && $item->status === 'approved_1')
                                                        <form action="{{ route('item-code.approve2', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="approve" data-confirm-text="Approve 2 data ini?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-info">Approve 2</button>
                                                        </form>

                                                        <form action="{{ route('item-code.reject', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="reject" data-confirm-text="Reject data ini ke Draft?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                        </form>
                                                    @elseif ($canFinish && $item->status === 'approved_2')
                                                        <form action="{{ route('item-code.finish', $item->id) }}" method="POST" class="d-contents"
                                                            data-approval-action-form data-action-type="finish" data-confirm-text="Finish data ini?">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-primary">Finish</button>
                                                        </form>
                                                        <span></span>
                                                    @else
                                                        <span></span>
                                                    @endif
                                                </div>

                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="js-empty-row">
                                            <td colspan="20" class="text-center text-muted">Belum ada data update harga.</td>
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

        @include('item_code.partials.modal-view-detail')

        <style>
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
    white-space: normal !important;
    word-break: break-word;
    text-align: center;
}

.d-contents {
    display: contents;
}

.itemcode-table .action-cell {
    width: 130px !important;
    min-width: 130px !important;
    max-width: 130px !important;
    padding: 0.25rem 0.2rem !important;
}
            .itemcode-section-card {
                border: 1px solid #e5e9f2;
                box-shadow: 0 8px 22px rgba(27, 39, 51, 0.06);
            }

            .itemcode-tabs .nav-link {
                color: #52606d;
                font-weight: 600;
                border: none;
                border-bottom: 2px solid transparent;
                border-radius: 0;
                padding-left: 0.9rem;
                padding-right: 0.9rem;
            }

            .itemcode-tabs .nav-link.active {
                color: #0d6efd;
                border-bottom-color: #0d6efd;
                background: transparent;
            }

            .itemcode-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 0.75rem;
                flex-wrap: wrap;
                padding: 0.75rem;
                border: 1px solid #e9edf5;
                border-radius: 0.75rem;
                background: linear-gradient(180deg, #fbfcfe 0%, #f5f8fc 100%);
                margin-bottom: 0.9rem;
            }

            .itemcode-toolbar-stats {
                flex: 1 1 100%;
            }

            .itemcode-toolbar-controls {
                width: 100%;
                justify-content: space-between;
                align-items: flex-start;
                gap: 0.6rem 0.9rem;
            }

            .itemcode-filter-form {
                flex: 1 1 560px;
                max-width: 640px;
                min-width: 300px;
            }

            .itemcode-toolbar-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 0.5rem;
            }

            .itemcode-toolbar-actions .btn,
            .itemcode-toolbar-actions form .btn {
                white-space: nowrap;
            }

            .itemcode-filter-input {
                min-width: 220px;
                max-width: 340px;
                flex: 0 1 340px;
            }

            .itemcode-filter-form select[name="status"] {
                width: 150px;
                min-width: 150px;
                flex: 0 0 150px;
            }

            .itemcode-date-range {
                flex: 0 0 290px;
                max-width: 290px;
                min-width: 260px;
            }

            .itemcode-date-range .form-control {
                min-width: 0;
            }

            .itemcode-date-range .range-separator {
                padding-left: 0.45rem;
                padding-right: 0.45rem;
                font-size: 0.74rem;
            }

            .itemcode-filter-form .input-group-text,
            .itemcode-filter-form .form-control,
            .itemcode-filter-form .form-select {
                border: 1.2px solid #9fb2cc;
                box-shadow: none;
                background-color: #ffffff;
            }

            .itemcode-filter-form .input-group-text {
                color: #5b6f87;
                background-color: #f6f9fd;
                border-right: 0;
            }

            .itemcode-filter-form .itemcode-filter-input .form-control {
                border-left: 0;
            }

            .itemcode-filter-form .form-control:focus,
            .itemcode-filter-form .form-select:focus {
                border-color: #6f95c6;
                box-shadow: 0 0 0 0.16rem rgba(31, 111, 209, 0.12);
            }

            .itemcode-table-wrap {
                border: 1.5px solid #b8c6da;
                border-radius: 0.85rem;
                background: #ffffff;
                box-shadow: 0 10px 20px rgba(21, 33, 54, 0.06);
            }

            .itemcode-table {
                margin-bottom: 0;
                min-width: 1280px;
                border-collapse: separate;
                border-spacing: 0;
                border: 1px solid #b8c6da;
            }

            .itemcode-table th,
            .itemcode-table td {
                border-color: #b8c6da;
                border-style: solid;
                border-width: 1px;
            }

            .itemcode-table th {
                position: sticky;
                top: 0;
                z-index: 2;
                white-space: nowrap;
                font-size: 0.79rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #ffffff;
                background: linear-gradient(180deg, #1f6fd1 0%, #1252ab 100%);
                padding: 0.72rem 0.68rem;
                border-bottom: 2px solid #0f3f84;
            }

            .itemcode-table td {
                font-size: 0.88rem;
                color: #2a3542;
                background: #ffffff;
                padding: 0.64rem 0.68rem;
            }

            .itemcode-table tbody tr:nth-child(even) td {
                background: #fbfdff;
            }

            .itemcode-table tbody tr:hover td {
                background: #eaf3ff !important;
            }

            .itemcode-table tbody td:first-child {
                text-align: center;
                font-weight: 700;
                color: #607086;
                width: 56px;
                background: #f3f7fd;
            }

            .itemcode-table tbody tr:nth-child(even) td:first-child {
                background: #edf3fb;
            }

            .itemcode-table td.text-end {
                font-variant-numeric: tabular-nums;
                font-weight: 600;
            }

            .itemcode-table th:last-child {
                position: sticky;
                right: 0;
                z-index: 4;
                background: linear-gradient(180deg, #1b66c1 0%, #104b9b 100%);
                border-left: 1px solid #b8c6da;
            }

            .itemcode-table td:last-child {
                position: sticky;
                right: 0;
                z-index: 1;
                background: #ffffff;
                border-left: 1px solid #b8c6da;
                box-shadow: -8px 0 10px -10px rgba(35, 53, 77, 0.7);
            }

            .itemcode-table tbody tr:nth-child(even) td:last-child {
                background: #fbfdff;
            }

            .itemcode-table tbody tr:hover td:last-child {
                background: #eaf3ff !important;
            }

            /* Make Status column (second-to-last) sticky to the left of the Action column */
            .itemcode-table th:nth-last-child(2) {
                position: sticky;
                top: 0;
                right: 130px;
                z-index: 3;
                background: linear-gradient(180deg, #1b66c1 0%, #104b9b 100%);
                border-left: 1px solid #b8c6da;
            }

            .itemcode-table td:nth-last-child(2) {
                position: sticky;
                right: 130px;
                z-index: 2;
                background: #ffffff;
                border-left: 1px solid #b8c6da;
                box-shadow: -6px 0 8px -8px rgba(35, 53, 77, 0.45);
            }

            .itemcode-table tbody tr:nth-child(even) td:nth-last-child(2) {
                background: #fbfdff;
            }

            .itemcode-table tbody tr:hover td:nth-last-child(2) {
                background: #eaf3ff !important;
            }

            .itemcode-status-badge {
                min-width: 72px;
                padding: 0.28rem 0.5rem;
                font-size: 0.72rem;
                text-align: center;
                font-weight: 600;
                letter-spacing: 0.01em;
            }

            .itemcode-table .action-cell {
                width: 130px;
                min-width: 130px;
                max-width: 130px;
            }

            .itemcode-table .js-empty-row td {
                background: #fffdf8;
                font-weight: 600;
            }

            .action-cell .btn {
                font-weight: 600;
            }

            @media (max-width: 768px) {
                .itemcode-filter-input {
                    min-width: 100%;
                }

                .itemcode-date-range {
                    min-width: 100%;
                    max-width: 100%;
                    flex-basis: 100%;
                }

                .itemcode-toolbar-controls {
                    flex-direction: column;
                    align-items: stretch;
                }

                .itemcode-filter-form {
                    min-width: 100%;
                    width: 100%;
                }

                .itemcode-toolbar-actions {
                    width: 100%;
                    justify-content: flex-start;
                }

                .itemcode-table {
                    min-width: 1080px;
                }
            }
        </style>

        <script>
            const itemcodeHistoryUrlTemplate = @json(route('item-code.history', ['id' => '__ID__']));
            const itemcodeSearchFocusStorageKey = 'itemcode_approval_search_focus';
            const itemcodeMinSearchKeywordLength = 3;

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
                    itemcodeSearchFocusStorageKey,
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

                const rawState = window.sessionStorage.getItem(itemcodeSearchFocusStorageKey);
                if (!rawState) {
                    return;
                }

                window.sessionStorage.removeItem(itemcodeSearchFocusStorageKey);

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

                        return keywordLength === 0 || keywordLength >= itemcodeMinSearchKeywordLength;
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

            function initApprovalActionAlerts() {
                const actionForms = document.querySelectorAll('[data-approval-action-form]');

                const setHiddenFieldValue = (form, fieldName, fieldValue) => {
                    let field = form.querySelector(`input[name="${fieldName}"]`);

                    if (!field) {
                        field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = fieldName;
                        form.appendChild(field);
                    }

                    field.value = fieldValue;
                };

                actionForms.forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();

                        const actionType = String(form.getAttribute('data-action-type') || '').toLowerCase();
                        const fallbackText = form.getAttribute('data-confirm-text') || 'Lanjutkan aksi ini?';

                        if (typeof Swal === 'undefined') {
                            if (actionType === 'reject') {
                                const promptValue = window.prompt('Masukkan alasan reject (minimal 3 karakter):', '');

                                if (promptValue === null) {
                                    return;
                                }

                                const rejectReason = promptValue.trim();
                                if (rejectReason.length < 3) {
                                    window.alert('Alasan reject wajib diisi minimal 3 karakter.');
                                    return;
                                }

                                setHiddenFieldValue(form, 'reject_reason', rejectReason);
                                form.submit();
                                return;
                            }

                            if (window.confirm(fallbackText)) {
                                form.submit();
                            }
                            return;
                        }

                        const actionConfig = {
                            approve: {
                                title: 'Approve data?',
                                text: fallbackText,
                                icon: 'question',
                                confirmButtonColor: '#198754',
                                confirmButtonText: 'Ya, approve',
                            },
                            approve_all: {
                                title: 'Approve semua data?',
                                text: fallbackText,
                                icon: 'question',
                                confirmButtonColor: '#198754',
                                confirmButtonText: 'Ya, approve semua',
                            },
                            reject: {
                                title: 'Reject data?',
                                text: fallbackText,
                                icon: 'warning',
                                confirmButtonColor: '#dc3545',
                                confirmButtonText: 'Ya, reject',
                            },
                            finish: {
                                title: 'Finish data?',
                                text: fallbackText,
                                icon: 'success',
                                confirmButtonColor: '#0d6efd',
                                confirmButtonText: 'Ya, finish',
                            },
                            finish_all: {
                                title: 'Finish semua data?',
                                text: fallbackText,
                                icon: 'success',
                                confirmButtonColor: '#0d6efd',
                                confirmButtonText: 'Ya, finish semua',
                            },
                        };

                        const selectedConfig = actionConfig[actionType] || {
                            title: 'Konfirmasi aksi',
                            text: fallbackText,
                            icon: 'question',
                            confirmButtonColor: '#0d6efd',
                            confirmButtonText: 'Ya, lanjutkan',
                        };

                        if (actionType === 'reject') {
                            Swal.fire({
                                title: 'Reject data?',
                                text: fallbackText,
                                icon: 'warning',
                                input: 'textarea',
                                inputLabel: 'Catatan Reject',
                                inputPlaceholder: 'Tulis alasan reject...',
                                inputAttributes: {
                                    'aria-label': 'Alasan reject',
                                    'maxlength': '500',
                                },
                                showCancelButton: true,
                                confirmButtonColor: '#dc3545',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Ya, reject',
                                cancelButtonText: 'Batal',
                                inputValidator: (value) => {
                                    const rejectReason = String(value || '').trim();

                                    if (rejectReason.length < 3) {
                                        return 'Alasan reject wajib diisi minimal 3 karakter.';
                                    }

                                    return null;
                                },
                                preConfirm: (value) => String(value || '').trim(),
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    setHiddenFieldValue(form, 'reject_reason', String(result.value || ''));
                                    form.submit();
                                }
                            });

                            return;
                        }

                        Swal.fire({
                            title: selectedConfig.title,
                            text: selectedConfig.text,
                            icon: selectedConfig.icon,
                            showCancelButton: true,
                            confirmButtonColor: selectedConfig.confirmButtonColor,
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: selectedConfig.confirmButtonText,
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

                const hargaLama = Number.parseFloat(item.price_per_pcs);
                const hargaBaru = Number.parseFloat(item.harga_baru);
                const fallbackSelisih = Number.isFinite(hargaLama) && Number.isFinite(hargaBaru)
                    ? hargaLama - hargaBaru
                    : null;
                const selisih = item.selisih !== null && item.selisih !== undefined
                    ? Number(item.selisih)
                    : fallbackSelisih;

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
                        itemcodeHistoryUrlTemplate.replace('__ID__', String(itemId)),
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
            initApprovalActionAlerts();
            attachModalCleanupHandlers();

        </script>
    </main>
@endsection
