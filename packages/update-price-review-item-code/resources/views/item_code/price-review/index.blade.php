@extends('layout')

@section('content')
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Review Harga Produk Baru Mamik</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('item-code.form') }}">Item Code</a></li>
                    <li class="breadcrumb-item active">Review Harga</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Data belum dapat diproses.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 pt-3">
                    <div>
                        <div class="text-muted small">Antrean aktif</div>
                        <div class="fs-4 fw-semibold">{{ (int) $pendingCount }} pengajuan</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <form method="GET" action="{{ route('item-code.price-review.index') }}"
                            class="d-flex align-items-center gap-2">
                            <label for="price_review_per_page" class="small text-muted">Tampilkan</label>
                            <select id="price_review_per_page" name="per_page" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                                @foreach ([10, 20, 50, 100] as $size)
                                    <option value="{{ $size }}" {{ (int) $perPage === $size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <a href="{{ route('item-code.form', ['tab' => 'new_product']) }}"
                            class="btn btn-sm btn-outline-secondary">
                            Kembali ke Form Item Code
                        </a>
                    </div>
                </div>
            </div>

            @forelse ($items as $item)
                <article class="card mb-3 border-warning">
                    <div class="card-header bg-warning-subtle d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <span class="fw-semibold">{{ $item->nomor_pengajuan ?: 'Nomor belum tersedia' }}</span>
                            <span class="text-muted ms-2">{{ $item->product_code }}</span>
                        </div>
                        <span class="badge text-bg-warning border border-dark">Menunggu Input Harga</span>
                    </div>

                    <div class="card-body pt-3">
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <tbody>
                                            <tr>
                                                <th style="width: 22%">Pengaju</th>
                                                <td>{{ optional($item->creator)->name ?: '-' }}</td>
                                                <th style="width: 18%">Tanggal</th>
                                                <td>{{ optional($item->tanggal)->format('d-m-Y') ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Category</th>
                                                <td>{{ $item->category ?: '-' }}</td>
                                                <th>Supplier</th>
                                                <td>{{ $item->supplier ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Product Code</th>
                                                <td>{{ $item->product_code ?: '-' }}</td>
                                                <th>Qty / Unit</th>
                                                <td>
                                                    {{ $item->qty !== null ? number_format((float) $item->qty, 0, '.', '') : '-' }}
                                                    {{ $item->unit ?: '' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Description</th>
                                                <td colspan="3">{{ $item->description ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Currency</th>
                                                <td>{{ $item->currency ?: '-' }}</td>
                                                <th>Harga Terakhir</th>
                                                <td>
                                                    {{ $item->price_per_pcs !== null ? number_format((float) $item->price_per_pcs, 2) : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Reason</th>
                                                <td colspan="3">{{ $item->reason_new_price ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Attachment</th>
                                                <td>
                                                    @if ($item->attachment)
                                                        <a href="{{ route('item-code.attachment', $item->id) }}"
                                                            class="btn btn-sm btn-outline-primary" target="_blank">
                                                            Lihat Attachment
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <th>Review Sebelumnya</th>
                                                <td>
                                                    {{ optional($item->priceReviewer)->name ?: '-' }}
                                                    @if ($item->price_reviewed_at)
                                                        <span class="d-block small text-muted">
                                                            {{ $item->price_reviewed_at->format('d-m-Y H:i:s') }}
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="fw-semibold">Isi Harga & Kirim ke Approval</h6>
                                    <form action="{{ route('item-code.price-review.confirm', $item->id) }}" method="POST"
                                        data-price-review-confirmation
                                        data-review-action="confirm"
                                        data-product-code="{{ $item->product_code }}"
                                        data-currency="{{ $item->currency }}">
                                        @csrf
                                        <label for="price_per_pcs_{{ $item->id }}" class="form-label">Price ({{ $item->currency }})</label>
                                        <input id="price_per_pcs_{{ $item->id }}" name="price_per_pcs" type="number"
                                            step="0.01" min="0" max="9999999999999.99" class="form-control"
                                            value="{{ old('price_per_pcs', $item->price_per_pcs) }}" required>
                                        <div class="form-text">Maksimal dua angka di belakang koma.</div>
                                        <button type="submit" class="btn btn-primary w-100 mt-3">
                                            Isi Harga & Kirim ke Approval
                                        </button>
                                    </form>
                                </div>

                                <div class="border rounded p-3">
                                    <h6 class="fw-semibold">Kembalikan ke Mamik</h6>
                                    <form action="{{ route('item-code.price-review.return', $item->id) }}" method="POST"
                                        data-price-review-confirmation
                                        data-review-action="return"
                                        data-product-code="{{ $item->product_code }}">
                                        @csrf
                                        <label for="return_reason_{{ $item->id }}" class="form-label">Alasan</label>
                                        <textarea id="return_reason_{{ $item->id }}" name="return_reason" class="form-control"
                                            rows="3" minlength="3" maxlength="500" required>{{ old('return_reason') }}</textarea>
                                        <div class="form-text">3–500 karakter.</div>
                                        <button type="submit" class="btn btn-outline-danger w-100 mt-3">
                                            Kembalikan ke Mamik
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h5 class="mb-2">Tidak ada antrean review harga</h5>
                        <p class="text-muted mb-0">Semua pengajuan Produk Baru Mamik sudah diproses.</p>
                    </div>
                </div>
            @endforelse

            @if (method_exists($items, 'links'))
                <div class="mt-3">
                    {{ $items->onEachSide(1)->links() }}
                </div>
            @endif
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-price-review-confirmation]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    const action = form.dataset.reviewAction;
                    const productCode = form.dataset.productCode || '-';
                    const isReturn = action === 'return';
                    const priceInput = form.querySelector('[name="price_per_pcs"]');
                    const currency = form.dataset.currency || '';
                    const price = priceInput ? priceInput.value.trim() : '';
                    const title = isReturn
                        ? 'Kembalikan ke Mamik?'
                        : 'Konfirmasi Harga?';
                    const message = isReturn
                        ? `Pengajuan Product Code ${productCode} akan dikembalikan ke Mamik sebagai Draft.`
                        : `Harga ${currency} ${price} untuk Product Code ${productCode} akan disimpan dan dikirim ke proses approval.`;

                    if (typeof Swal === 'undefined') {
                        if (window.confirm(message)) {
                            form.submit();
                        }

                        return;
                    }

                    Swal.fire({
                        title: title,
                        text: message,
                        icon: isReturn ? 'warning' : 'question',
                        showCancelButton: true,
                        confirmButtonText: isReturn ? 'Ya, kembalikan' : 'Ya, konfirmasi',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: isReturn ? '#dc3545' : '#0d6efd',
                        cancelButtonColor: '#6c757d',
                        reverseButtons: true,
                        focusCancel: true,
                        allowOutsideClick: false,
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
