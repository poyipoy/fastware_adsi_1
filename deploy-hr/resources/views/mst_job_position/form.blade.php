@extends('layout')

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $position ? 'Edit' : 'Tambah' }} Job Position</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('mst-job-position.index') }}">Master Job Position</a></li>
                <li class="breadcrumb-item active">{{ $position ? 'Edit' : 'Tambah' }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="container">
            @if($errors->any())
                <div class="alert alert-danger rounded-3 shadow-sm">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ $position ? route('mst-job-position.update', $position->id) : route('mst-job-position.store') }}">
                @csrf
                @if($position) @method('PUT') @endif

                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-primary text-white rounded-top-4 py-3">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-briefcase me-2"></i>Informasi Posisi</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            {{-- Nama Job Position (freetext) --}}
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('position_name') is-invalid @enderror"
                                           id="position_name" name="position_name"
                                           value="{{ old('position_name', $position?->position_name) }}"
                                           placeholder="Nama Posisi" required>
                                    <label for="position_name">Nama Job Position <span class="text-danger">*</span></label>
                                    @error('position_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Departemen dropdown + tombol "+" --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Departemen</label>
                                <div class="input-group">
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">— Kosong —</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}"
                                                {{ old('department_id', $position?->department_id) == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="btnAddDept"
                                            title="Tambah Departemen Baru" data-bs-toggle="modal" data-bs-target="#modalAddDept">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Section dropdown + tombol "+" --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Section</label>
                                <div class="input-group">
                                    <select class="form-select" id="section_id" name="section_id">
                                        <option value="">— Kosong —</option>
                                        @foreach($sections as $sec)
                                            <option value="{{ $sec->id }}"
                                                {{ old('section_id', $position?->section_id) == $sec->id ? 'selected' : '' }}>
                                                {{ $sec->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="btnAddSection"
                                            title="Tambah Section Baru" data-bs-toggle="modal" data-bs-target="#modalAddSection">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-success text-white rounded-top-4 py-3">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-sitemap me-2"></i>Rute Approval</h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">
                            Tentukan siapa approver untuk posisi ini. Pilih posisi (bukan nama orang).
                            Sistem akan mencari siapa yang menjabat posisi tersebut secara otomatis.
                        </p>

                        @foreach([0 => 'Sub Section Head (Opsional)', 1 => 'Section Head', 2 => 'Department Head', 3 => 'Division Head (Opsional)'] as $level => $label)
                            @php
                                $existingRoute = collect($approvalRoutes)->firstWhere('approval_level', $level);
                            @endphp

                            @if($level == 0)
                            <div id="subSecHeadWrapper" style="display: none;">
                            @endif

                            @if($level == 3)
                            <div id="divHeadWrapper" style="display: none;">
                                <hr class="my-2">
                            @endif

                            <div class="row g-3 align-items-center mb-3">
                                <div class="col-md-3">
                                    <label class="fw-semibold">
                                        Approval {{ $level }}
                                        <br><small class="text-muted fw-normal">{{ $label }}</small>
                                    </label>
                                    <input type="hidden" name="approval_levels[{{ $loop->index }}][level]" value="{{ $level }}">
                                </div>
                                <div class="col-md-9">
                                    <select name="approval_levels[{{ $loop->index }}][approver_position_id]"
                                            class="form-select approval-select"
                                            data-selected="{{ old("approval_levels.{$loop->index}.approver_position_id", $existingRoute?->approver_position_id) }}">
                                        <option value="">— Tidak ada (posisi ini tidak memerlukan approval {{ $level }}) —</option>
                                        {{-- Options will be populated by JS based on selected department --}}
                                    </select>
                                </div>
                            </div>

                            @if($level == 3)
                            </div>
                            @endif

                            @if($level == 0 || $level == 1 || $level == 2)
                                <hr class="my-2">
                            @endif

                            @if($level == 0)
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('mst-job-position.index') }}" class="btn btn-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

{{-- ========================================================= --}}
{{-- Modal: Tambah Departemen Baru                             --}}
{{-- ========================================================= --}}
<div class="modal fade" id="modalAddDept" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h6 class="modal-title"><i class="fas fa-building me-2"></i>Tambah Departemen</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="deptAlert" class="alert d-none mb-2 py-2 small"></div>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="newDeptName" placeholder="Nama Departemen">
                    <label for="newDeptName">Nama Departemen <span class="text-danger">*</span></label>
                </div>
                <div>
                    <label class="form-label small fw-semibold text-secondary mb-1">Departemen Terdaftar:</label>
                    <div class="overflow-auto border rounded-3 p-2 bg-light" style="max-height: 150px;">
                        <ul class="list-group list-group-flush small" id="deptList">
                            <li class="list-group-item text-muted py-1 border-0 bg-transparent">Memuat...</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" id="btnSaveDept">
                    <i class="fas fa-save me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================= --}}
{{-- Modal: Tambah Section Baru                                --}}
{{-- ========================================================= --}}
<div class="modal fade" id="modalAddSection" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h6 class="modal-title"><i class="fas fa-layer-group me-2"></i>Tambah Section</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="secAlert" class="alert d-none mb-2 py-2 small"></div>
                <p class="text-muted small mb-2">Departemen yang dipilih: <strong id="secDeptLabel">—</strong></p>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="newSecName" placeholder="Nama Section">
                    <label for="newSecName">Nama Section <span class="text-danger">*</span></label>
                </div>
                <div>
                    <label class="form-label small fw-semibold text-secondary mb-1">Section Terdaftar:</label>
                    <div class="overflow-auto border rounded-3 p-2 bg-light" style="max-height: 150px;">
                        <ul class="list-group list-group-flush small" id="secList">
                            <li class="list-group-item text-muted py-1 border-0 bg-transparent">Memuat...</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" id="btnSaveSection">
                    <i class="fas fa-save me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const deptSelect    = document.getElementById('department_id');
    const sectionSelect = document.getElementById('section_id');

    const urlSections   = "{{ route('mst-job-position.ajax.sections-by-dept', ':id') }}";
    const urlStoreDept  = "{{ route('mst-job-position.ajax.store-department') }}";
    const urlStoreSec   = "{{ route('mst-job-position.ajax.store-section') }}";
    const urlDepts      = "{{ route('mst-job-position.ajax.departments') }}";
    const csrf          = document.querySelector('meta[name="csrf-token"]').content;

    @php
        $mappedPositions = $allPositions->map(function($ap) {
            return [
                'id' => $ap->id,
                'position_name' => $ap->position_name,
                'department_id' => $ap->department_id,
                'department_name' => $ap->department ? $ap->department->name : ''
            ];
        });
    @endphp
    // Data all positions from backend
    const allPositions = {!! json_encode($mappedPositions) !!};

    // ---- AJAX: load departemen list di modal ----
    async function loadDeptList() {
        const deptList = document.getElementById('deptList');
        deptList.innerHTML = '<li class="list-group-item text-muted py-1 border-0 bg-transparent">Memuat...</li>';
        try {
            const res = await fetch(urlDepts);
            const data = await res.json();
            deptList.innerHTML = '';
            if (data.length === 0) {
                deptList.innerHTML = '<li class="list-group-item text-muted py-1 border-0 bg-transparent">Belum ada departemen</li>';
                return;
            }
            data.forEach(dept => {
                const li = document.createElement('li');
                li.className = 'list-group-item py-1 border-0 bg-transparent text-secondary d-flex align-items-center';
                li.innerHTML = `<span><i class="fas fa-building text-primary me-2"></i>${dept.name}</span>`;
                deptList.appendChild(li);
            });
        } catch (e) {
            deptList.innerHTML = '<li class="list-group-item text-danger py-1 border-0 bg-transparent">Gagal memuat</li>';
        }
    }

    // ---- AJAX: load section list di modal ----
    async function loadSecList(deptId) {
        const secList = document.getElementById('secList');
        if (!deptId) {
            secList.innerHTML = '<li class="list-group-item text-muted py-1 border-0 bg-transparent">Silakan pilih departemen di form utama</li>';
            return;
        }
        secList.innerHTML = '<li class="list-group-item text-muted py-1 border-0 bg-transparent">Memuat...</li>';
        try {
            const res = await fetch(urlSections.replace(':id', deptId));
            const data = await res.json();
            secList.innerHTML = '';
            if (data.length === 0) {
                secList.innerHTML = '<li class="list-group-item text-muted py-1 border-0 bg-transparent">Belum ada section di departemen ini</li>';
                return;
            }
            data.forEach(sec => {
                const li = document.createElement('li');
                li.className = 'list-group-item py-1 border-0 bg-transparent text-secondary d-flex align-items-center';
                li.innerHTML = `<span><i class="fas fa-layer-group text-primary me-2"></i>${sec.name}</span>`;
                secList.appendChild(li);
            });
        } catch (e) {
            secList.innerHTML = '<li class="list-group-item text-danger py-1 border-0 bg-transparent">Gagal memuat</li>';
        }
    }

    // ---- AJAX: filter section saat dept berubah ----
    async function loadSections(deptId, selectedId = null) {
        sectionSelect.innerHTML = '<option value="">— Kosong —</option>';
        if (!deptId) return;
        try {
            const res  = await fetch(urlSections.replace(':id', deptId));
            const data = await res.json();
            data.forEach(sec => {
                const opt = new Option(sec.name, sec.id, false, sec.id == selectedId);
                sectionSelect.appendChild(opt);
            });
            if (typeof toggleWrappers === 'function') toggleWrappers();
        } catch (e) { console.error(e); }
    }

    deptSelect.addEventListener('change', () => {
        loadSections(deptSelect.value);
        // Update label di modal section
        document.getElementById('secDeptLabel').textContent =
            deptSelect.options[deptSelect.selectedIndex]?.text || '—';
    });

    // Saat modal dept dibuka, load daftar departemen
    document.getElementById('modalAddDept').addEventListener('show.bs.modal', () => {
        loadDeptList();
    });

    // Saat modal section dibuka, sinkronkan label dept yang sedang dipilih & load section
    document.getElementById('modalAddSection').addEventListener('show.bs.modal', () => {
        const selectedDeptText = deptSelect.options[deptSelect.selectedIndex]?.text || '—';
        document.getElementById('secDeptLabel').textContent = selectedDeptText;
        loadSecList(deptSelect.value);
    });

    // ---- Simpan Departemen Baru ----
    document.getElementById('btnSaveDept').addEventListener('click', async () => {
        const name  = document.getElementById('newDeptName').value.trim();
        const alert = document.getElementById('deptAlert');
        alert.className = 'alert d-none mb-2 py-2 small';

        if (!name) { showAlert(alert, 'danger', 'Nama departemen wajib diisi.'); return; }

        const btn = document.getElementById('btnSaveDept');
        btn.disabled = true;
        try {
            const res  = await fetch(urlStoreDept, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ name })
            });
            const data = await res.json();
            if (!res.ok) { showAlert(alert, 'danger', data.errors?.name?.[0] ?? data.message ?? 'Gagal menyimpan.'); return; }

            // Tambah ke dropdown dept dan pilih otomatis
            const opt = new Option(data.name, data.id, true, true);
            deptSelect.appendChild(opt);
            deptSelect.value = data.id;
            deptSelect.dispatchEvent(new Event('change'));

            document.getElementById('newDeptName').value = '';
            // Reload list departemen
            loadDeptList();
            bootstrap.Modal.getInstance(document.getElementById('modalAddDept')).hide();
        } catch(e) { showAlert(alert, 'danger', 'Terjadi kesalahan.'); }
        finally { btn.disabled = false; }
    });

    // ---- Simpan Section Baru ----
    document.getElementById('btnSaveSection').addEventListener('click', async () => {
        const name     = document.getElementById('newSecName').value.trim();
        const deptId   = deptSelect.value;
        const alert    = document.getElementById('secAlert');
        alert.className = 'alert d-none mb-2 py-2 small';

        if (!deptId) { showAlert(alert, 'warning', 'Pilih departemen terlebih dahulu di form utama.'); return; }
        if (!name)   { showAlert(alert, 'danger',  'Nama section wajib diisi.'); return; }

        const btn = document.getElementById('btnSaveSection');
        btn.disabled = true;
        try {
            const res  = await fetch(urlStoreSec, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ name, department_id: deptId })
            });
            const data = await res.json();
            if (!res.ok) { showAlert(alert, 'danger', data.errors?.name?.[0] ?? data.error ?? 'Gagal menyimpan.'); return; }

            // Tambah ke dropdown section dan pilih otomatis
            const opt = new Option(data.name, data.id, true, true);
            sectionSelect.appendChild(opt);
            sectionSelect.value = data.id;

            document.getElementById('newSecName').value = '';
            // Reload list section
            loadSecList(deptId);
            bootstrap.Modal.getInstance(document.getElementById('modalAddSection')).hide();
        } catch(e) { showAlert(alert, 'danger', 'Terjadi kesalahan.'); }
        finally { btn.disabled = false; }
    });

    function showAlert(el, type, msg) {
        el.className = `alert alert-${type} mb-2 py-2 small`;
        el.textContent = msg;
    }

    // ---- Show/Hide Division Head based on Department "Sales" ----
    const divHeadWrapper = document.getElementById('divHeadWrapper');
    const selectDivHead = document.querySelector('select[name="approval_levels[3][approver_position_id]"]');

    // ---- Show/Hide Sub Section Head based on Department "Finance" ----
    const subSecHeadWrapper = document.getElementById('subSecHeadWrapper');
    const selectSubSecHead = document.querySelector('select[name="approval_levels[0][approver_position_id]"]');

    function toggleWrappers() {
        const selectedDeptText = deptSelect.options[deptSelect.selectedIndex]?.text || '';
        const deptUpper = selectedDeptText.trim().toUpperCase();

        const sectionSelectEl = document.getElementById('section_id');
        const selectedSecText = sectionSelectEl.options[sectionSelectEl.selectedIndex]?.text || '';
        const secUpper = selectedSecText.trim().toUpperCase();

        if (divHeadWrapper) {
            if (deptUpper.includes('SALES')) {
                divHeadWrapper.style.display = 'block';
            } else {
                divHeadWrapper.style.display = 'none';
                if (selectDivHead) {
                    selectDivHead.value = ''; // Reset to empty
                }
            }
        }

        if (subSecHeadWrapper) {
            if (deptUpper.includes('FINANCE') && secUpper.includes('FINANCE')) {
                subSecHeadWrapper.style.display = 'block';
            } else {
                subSecHeadWrapper.style.display = 'none';
                if (selectSubSecHead) {
                    selectSubSecHead.value = ''; // Reset to empty
                }
            }
        }
    }

    // ---- Dynamic Approval Dropdowns based on Department ----
    const approvalSelects = document.querySelectorAll('.approval-select');

    function updateApprovalDropdowns(isInitialLoad = false) {
        const selectedDeptId = deptSelect.value;
        const filteredPositions = allPositions.filter(ap => 
            String(ap.department_id || '') === String(selectedDeptId) || 
            ap.department_id === null || 
            ap.department_id === ''
        );

        approvalSelects.forEach((select, index) => {
            const levelMatch = select.name.match(/approval_levels\[(\d+)\]/);
            const level = levelMatch ? levelMatch[1] : index;
            
            // Keep the previous selection if it's initial load, otherwise clear it
            const currentSelected = isInitialLoad ? select.getAttribute('data-selected') : '';

            // Clear current options
            select.innerHTML = `<option value="">— Tidak ada (posisi ini tidak memerlukan approval ${level}) —</option>`;

            // Populate filtered options
            filteredPositions.forEach(ap => {
                const opt = new Option(ap.position_name + (ap.department_name ? ` (${ap.department_name})` : ''), ap.id);
                if (String(ap.id) === String(currentSelected)) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
            
            // If currentSelected is present but not in the filtered list (e.g. cross-department from old data), 
            // we should also inject it so the form doesn't silently lose data on load.
            if (isInitialLoad && currentSelected && !filteredPositions.find(p => String(p.id) === String(currentSelected))) {
                const missingPos = allPositions.find(p => String(p.id) === String(currentSelected));
                if (missingPos) {
                    const opt = new Option(missingPos.position_name + (missingPos.department_name ? ` (${missingPos.department_name})` : ''), missingPos.id);
                    opt.selected = true;
                    select.appendChild(opt);
                }
            }
        });
    }

    deptSelect.addEventListener('change', () => {
        toggleWrappers();
        updateApprovalDropdowns(false); // Update and clear selection
    });
    document.getElementById('section_id').addEventListener('change', toggleWrappers);
    
    toggleWrappers(); // Initial trigger
    updateApprovalDropdowns(true); // Initial populate on load
})();
</script>
@endpush
@endsection
