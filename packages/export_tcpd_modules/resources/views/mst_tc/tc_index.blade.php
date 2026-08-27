@extends('layout')

@section('content')
    <main id="main" class="main">

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <style>
            .blue-theme-header {
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                color: white;
                padding: 1.5rem;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                margin-bottom: 2rem;
            }
            .blue-theme-header h1 {
                color: white;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }
            .blue-theme-header .breadcrumb-item.active {
                color: #e0e0e0;
            }
            .filter-card {
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                padding: 1.25rem;
                margin-bottom: 2rem;
                border: 1px solid #eef2f5;
            }
        </style>
        <div class="pagetitle blue-theme-header">
            <h1>Halaman Pengajuan Data Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Competency</li>
                </ol>
            </nav>
        </div>

        <div class="filter-card d-flex justify-content-between align-items-center flex-wrap gap-3">
            <a href="{{ route('tcCreate') }}" class="btn btn-success rounded-pill shadow-sm px-4 py-2">
                <i class="fas fa-plus me-1"></i> Tambah Data
            </a>
            
            <form action="{{ route('tcShow') }}" method="GET" class="d-flex align-items-center flex-wrap gap-3">
                <div class="d-flex flex-column" style="width: 200px;">
                    <label for="deptFilter" class="form-label small fw-semibold text-secondary mb-1">Department</label>
                    <select name="department" id="deptFilter" class="form-select form-select-sm rounded-pill shadow-sm border-primary" onchange="this.form.submit()">
                        <option value="">Semua Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ ($selectedDept ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex flex-column" style="width: 200px;">
                    <label for="sectionFilter" class="form-label small fw-semibold text-secondary mb-1">Section</label>
                    <select name="section" id="sectionFilter" class="form-select form-select-sm rounded-pill shadow-sm border-primary" onchange="this.form.submit()">
                        <option value="">Semua Section</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}" {{ ($selectedSection ?? '') == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex flex-column" style="width: 200px;">
                    <label for="jobFilter" class="form-label small fw-semibold text-secondary mb-1">Job Position</label>
                    <select name="job_position" id="jobFilter" class="form-select form-select-sm rounded-pill shadow-sm border-primary" onchange="this.form.submit()">
                        <option value="">Semua Job Position</option>
                        @foreach($jobPositionOptions as $jpOpt)
                            <option value="{{ $jpOpt->id }}" {{ ($selectedJobPosition ?? '') == $jpOpt->id ? 'selected' : '' }}>{{ $jpOpt->position_name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <section class="section">
            <div class="card shadow-sm rounded-4 border-0">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="datatable table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center" style="width: 80px;">No</th>
                                    <th scope="col">Job Position</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Section</th>
                                    <th scope="col" class="text-center">Technical Competency</th>
                                    <th scope="col" class="text-center">Soft Skills</th>
                                    <th scope="col" class="text-center">Additional</th>
                                    <th scope="col" class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @foreach ($jobPositions as $index => $data)
                                    <tr>
                                        <td class="text-center">{{ $i++ }}</td>
                                        <td class="fw-semibold text-secondary">{{ $data->job_position }}</td>
                                        <td>{{ optional($data->department)->name ?? '-' }}</td>
                                        <td>{{ optional($data->section)->name ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $data->tc_count > 0 ? 'bg-primary' : 'bg-secondary' }} rounded-pill px-3 py-2">
                                                {{ $data->tc_count }} Items
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $data->sk_count > 0 ? 'bg-success' : 'bg-secondary' }} rounded-pill px-3 py-2">
                                                {{ $data->sk_count }} Items
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $data->ad_count > 0 ? 'bg-info' : 'bg-secondary' }} rounded-pill px-3 py-2">
                                                {{ $data->ad_count }} Items
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('mst_tc.edit_all', $data->id) }}" class="btn btn-warning btn-sm rounded-pill shadow-sm px-3"> 
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        @push('scripts')
            <!-- jQuery -->
            <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <!-- SimpleDataTables JS -->
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
        @endpush

    </main><!-- End #main -->
@endsection
