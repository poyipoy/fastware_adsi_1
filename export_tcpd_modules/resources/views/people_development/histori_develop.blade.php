@extends('layout')

@section('content')
<style>
    .history-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        color: #fff;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    .history-toolbar .form-control,
    .history-toolbar .form-select {
        min-height: 40px;
    }

    .employee-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .employee-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #0d6efd;
        background-color: #e9f2ff;
        border: 1px solid #cfe2ff;
    }

    .table td,
    .table th {
        vertical-align: middle;
        white-space: nowrap;
    }

    .loading-row {
        color: #6c757d;
    }

    .empty-state {
        color: #6c757d;
    }

    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .25rem .5rem;
        border-radius: 999px;
        font-size: 0.8rem;
        background: #eef6ff;
        color: #0b5ed7;
        border: 1px solid #cfe2ff;
        cursor: pointer;
    }

    .filter-chip .close-x {
        font-weight: 700;
        margin-left: .25rem;
        color: #0b5ed7;
    }

        /* Button visual improvements */
    .history-toolbar .btn-action {
        min-height: 44px;
        font-size: 0.95rem;
        padding: 0.45rem 0.9rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 0 rgba(0,0,0,0.03);
    }

    .history-toolbar .btn-action i {
        vertical-align: middle;
        margin-right: 0.45rem;
    }

    .history-toolbar .btn-export {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }
</style>

<main id="main" class="main">
    <section class="section dashboard">
        <div class="card shadow-sm border-0">
            <div class="card-header history-header p-3 p-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <h5 class="mb-1 fw-bold">Histori Development</h5>
                    <div class="history-toolbar mb-3">
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge text-bg-light" id="resultCount">{{ $dataTcPeopleDevelopment->count() }} Data</span>
                    </div>
                </div>

                <div class="mb-3">
                    <div id="activeFilters" class="d-flex gap-2 flex-wrap"></div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="history-toolbar mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="role_id" class="form-label mb-1 fw-semibold">Departemen</label>
                            <select id="role_id" name="role_id" class="form-select">
                                <option value="">Pilih Departemen</option>
                                @if (auth()->user()->role_id == 11 || in_array(auth()->user()->role_id, [1, 3, 14, 15]))
                                    <option value="11">Departemen Finn Acc Hrga IT Proc</option>
                                @endif
                                @if (auth()->user()->role_id == 2 || in_array(auth()->user()->role_id, [1, 3, 14, 15]))
                                    <option value="2">Departemen Sales Marketing</option>
                                @endif
                                @if (auth()->user()->role_id == 5 || in_array(auth()->user()->role_id, [1, 3, 14, 15]))
                                    <option value="5">Departemen Production</option>
                                @endif
                                @if (auth()->user()->role_id == 7 || in_array(auth()->user()->role_id, [1, 3, 14, 15]))
                                    <option value="7">Departemen Logistics</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-12 col-md-3 col-lg-2">
                            <label for="year" class="form-label mb-1 fw-semibold">Tahun</label>
                            <select id="year" name="year" class="form-select">
                                <option value="">Pilih Tahun</option>
                                @for ($year = now()->year; $year >= 2015; $year--)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-12 col-md-5 col-lg-3 d-grid d-md-flex gap-2">
                            <button id="btnFilter" type="button" class="btn btn-success btn-action w-100" title="Filter data berdasarkan pilihan">
                                <i class="bi bi-funnel"></i>
                                <span>Filter</span>
                            </button>
                            <button id="btnReset" type="button" class="btn btn-secondary btn-action w-100 text-light" title="Reset semua filter">
                                <span>Reset</span>
                            </button>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label for="searchInput" class="form-label mb-1 fw-semibold">Cari Data</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="searchInput" class="form-control" placeholder="Cari di semua kolom...">
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="d-flex gap-2 justify-content-end">
                                <a id="exportCsv" href="#" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0" id="historyTable">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Employee</th>
                                <th>Nama Program</th>
                                <th>Kategori Competency</th>
                                <th>Competency</th>
                                <th>Lembaga</th>
                                <th>Periode Actual</th>
                                <th class="text-center">File</th>
                            </tr>
                        </thead>
                        <tbody id="peopleDevTabel">
                            @if ($dataTcPeopleDevelopment->isEmpty())
                                <tr class="empty-state-row">
                                    <td colspan="7" class="text-center py-4 empty-state">
                                        <i class="bi bi-inbox fs-5 d-block mb-2"></i>
                                        Tidak ada data tersedia
                                    </td>
                                </tr>
                            @else
                                @foreach ($dataTcPeopleDevelopment as $data)
                                    <tr>
                                        <td>
                                            <span class="employee-pill">
                                                <span class="employee-avatar">
                                                    {{ strtoupper(substr($data->user->name ?? '-', 0, 1)) }}
                                                </span>
                                                <span>{{ $data->user->name ?? '-' }}</span>
                                            </span>
                                        </td>
                                        <td>{{ $data->program_training_plan ?? '-' }}</td>
                                        <td>{{ $data->kategori_competency ?? '-' }}</td>
                                        <td>{{ $data->competency ?? '-' }}</td>
                                        <td>{{ $data->lembaga_plan ?? '-' }}</td>
                                        <td>{{ $data->due_date_plan ?? '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('download.pdf', ['id' => $data->id]) }}" class="btn btn-sm btn-outline-primary" title="Download file">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <script>
        const filterUrl = "{{ route('people_development.filter') }}";
        const downloadTemplate = "{{ route('download.pdf', ['id' => ':id']) }}";

        const roleInput = document.getElementById('role_id');
        const yearInput = document.getElementById('year');
        const searchInput = document.getElementById('searchInput');
        const btnFilter = document.getElementById('btnFilter');
        const btnReset = document.getElementById('btnReset');
        const tableBody = document.getElementById('peopleDevTabel');
        const resultCount = document.getElementById('resultCount');

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '-';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function makeEmployeeCell(name) {
            const safeName = escapeHtml(name || '-');
            const initial = safeName && safeName !== '-' ? safeName.charAt(0).toUpperCase() : '-';

            return `
                <span class="employee-pill">
                    <span class="employee-avatar">${initial}</span>
                    <span>${safeName}</span>
                </span>
            `;
        }

        function setLoadingState() {
            tableBody.innerHTML = `
                <tr class="loading-row">
                    <td colspan="7" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        Memuat data...
                    </td>
                </tr>
            `;
        }

        function setEmptyState() {
            tableBody.innerHTML = `
                <tr class="empty-state-row">
                    <td colspan="7" class="text-center py-4 empty-state">
                        <i class="bi bi-inbox fs-5 d-block mb-2"></i>
                        Tidak ada data tersedia
                    </td>
                </tr>
            `;
        }

        function renderRows(data) {
            tableBody.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                setEmptyState();
                resultCount.textContent = '0 Data';
                return;
            }

            const rows = data.map((item) => {
                const fileUrl = downloadTemplate.replace(':id', item.id);

                return `
                    <tr>
                        <td>${makeEmployeeCell(item.user ? item.user.name : '-')}</td>
                        <td>${escapeHtml(item.program_training_plan)}</td>
                        <td>${escapeHtml(item.kategori_competency)}</td>
                        <td>${escapeHtml(item.competency)}</td>
                        <td>${escapeHtml(item.lembaga_plan)}</td>
                        <td>${escapeHtml(item.due_date_plan)}</td>
                        <td class="text-center">
                            <a href="${fileUrl}" class="btn btn-sm btn-outline-primary" title="Download file">
                                <i class="bi bi-download"></i>
                            </a>
                        </td>
                    </tr>
                `;
            }).join('');

            tableBody.innerHTML = rows;
            resultCount.textContent = `${data.length} Data`;
            applySearchFilter();
        }

        function applySearchFilter() {
            const keyword = (searchInput.value || '').trim().toLowerCase();
            const rows = tableBody.querySelectorAll('tr');
            let visibleCount = 0;

            rows.forEach((row) => {
                const rowText = row.textContent.toLowerCase();
                const isVisible = rowText.includes(keyword);
                row.style.display = isVisible ? '' : 'none';

                if (isVisible && !row.classList.contains('empty-state-row') && !row.classList.contains('loading-row')) {
                    visibleCount += 1;
                }
            });

            if (keyword && visibleCount === 0 && rows.length > 0) {
                resultCount.textContent = '0 Data';
            }
        }

        async function filterData() {
            const roleId = roleInput.value;
            const year = yearInput.value;

            const query = new URLSearchParams();
            if (roleId) {
                query.append('role_id', roleId);
            }
            if (year) {
                query.append('year', year);
            }

            setLoadingState();

            try {
                const response = await fetch(`${filterUrl}?${query.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Gagal memuat data');
                }

                const data = await response.json();
                renderRows(data);
            } catch (error) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4 text-danger">
                            Terjadi kesalahan saat mengambil data.
                        </td>
                    </tr>
                `;
                resultCount.textContent = '0 Data';
                console.error(error);
            }
        }

        btnFilter.addEventListener('click', filterData);

        btnReset.addEventListener('click', function() {
            roleInput.value = '';
            yearInput.value = '';
            searchInput.value = '';
            filterData();
        });

        yearInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                filterData();
            }
        });

        searchInput.addEventListener('input', applySearchFilter);
        // Export handlers
        document.getElementById('exportCsv').addEventListener('click', function(e) {
            e.preventDefault();
            const params = new URLSearchParams();
            if (roleInput.value) params.append('role_id', roleInput.value);
            if (yearInput.value) params.append('year', yearInput.value);
            window.location = `{{ route('people_development.export.csv') }}?${params.toString()}`;
        });

        // exportPdf removed — PDF export disabled
    </script>
</main>
@endsection
