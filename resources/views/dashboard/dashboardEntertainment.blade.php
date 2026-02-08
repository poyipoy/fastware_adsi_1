@extends('layout')

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Dashboard Entertainment</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/forumSS') }}">Home</a></li>
                <li class="breadcrumb-item active">Dashboard Entertainment</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <!-- Filter Card -->
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Filter</h5>
                        <form method="GET" action="{{ route('dashboard.entertainment') }}">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="filter_start_date" class="form-label">Tanggal Dari</label>
                                    <input type="date" class="form-control" id="filter_start_date" name="start_date" value="{{ $startDate }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="filter_end_date" class="form-label">Tanggal Sampai</label>
                                    <input type="date" class="form-control" id="filter_end_date" name="end_date" value="{{ $endDate }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-funnel"></i> Filter
                                        </button>
                                        <a href="{{ route('dashboard.entertainment') }}" class="btn btn-secondary mt-2">
                                            <i class="bi bi-arrow-clockwise"></i> Reset ke Bulan Ini
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sales Table -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Sales</h5>
                            <a href="{{ route('dashboard.entertainment.export.sales', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success btn-sm">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="salesTable">
                                <thead>
                                    <tr>
                                        <th>Nama Sales</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salesData as $sale)
                                    <tr>
                                        <td>{{ $sale->nama }}</td>
                                        <td>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th>Total</th>
                                        <th>Rp {{ number_format($salesTotal, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Companies Table -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Perusahaan</h5>
                            <a href="{{ route('dashboard.entertainment.export.companies', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success btn-sm">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="companiesTable">
                                <thead>
                                    <tr>
                                        <th>Nama Perusahaan</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($companiesData as $company)
                                    <tr>
                                        <td>{{ $company->nama_customer }}</td>
                                        <td>Rp {{ number_format($company->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th>Total</th>
                                        <th>Rp {{ number_format($companiesTotal, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

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
@endsection
