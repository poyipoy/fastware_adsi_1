<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportWorkingExperienceRequest;
use App\Http\Requests\StoreWorkingExperienceRequest;
use App\Http\Requests\UpdateWorkingExperienceRequest;
use App\Imports\WorkingExperienceImport;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Models\WorkingExperience;
use App\Services\HR\DepartmentStructureService;
use App\Services\HR\JobPositionAccessService;
use App\Services\HR\WorkingExperienceValidationService;
use App\Services\KnowledgeManagement\KmOrganizationAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class UserJobPositionController extends Controller
{
    public function __construct(private JobPositionAccessService $mappingAccess)
    {
    }

    private function ensureAccess(): void
    {
        abort_unless($this->mappingAccess->canManageMapping(auth()->user()), 403);
    }

    /**
     * Halaman daftar mapping karyawan ↔ posisi.
     */
    public function index(Request $request)
    {
        abort_unless($this->mappingAccess->canViewMapping($request->user()), 403);
        $canManageMapping = $this->mappingAccess->canManageMapping($request->user());
        $request->validate(['position_id' => 'nullable|integer|min:1', 'search' => 'nullable|string|max:255']);

        $readable = $this->mappingAccess->getReadableMappingScope($request->user());
        $query = (clone $readable)
            ->with(['user:id,npk,name', 'jobPosition.department', 'jobPosition.section'])
            ->orderBy('created_at', 'desc')
            ->when(! $canManageMapping, fn ($rows) => $rows->orderByDesc('id'));

        if ($request->filled('position_id')) {
            $query->where('mst_job_position_id', $request->position_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('npk', 'like', "%{$search}%"));
        }

        $mappings = $query->paginate(25)->appends($request->all());
        $positions = MstJobPosition::active()
            ->when(! $canManageMapping, fn ($positions) => $positions
                ->whereIn('id', (clone $readable)->select('mst_job_position_id')))
            ->with(['department', 'section'])->orderBy('position_name')->get();
        $users = $canManageMapping ? User::orderBy('name')->get(['id', 'name', 'npk']) : collect();
        $departments = $canManageMapping ? \App\Models\MstDepartment::active()->orderBy('name')->get() : collect();
        $sections = $canManageMapping ? \App\Models\MstSection::active()->orderBy('name')->get() : collect();

        return view('user_job_position.index', compact('mappings', 'positions', 'users', 'departments', 'sections', 'canManageMapping'));
    }

    /**
     * Simpan mapping baru: user ↔ posisi.
     */
    public function store(Request $request, KmOrganizationAssignmentService $assignments)
    {
        $this->ensureAccess();

        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'mst_job_position_id' => 'required|exists:mst_job_positions,id',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
            'assignment_source' => 'nullable|string|max:64',
            'change_reason' => 'nullable|string|max:2000',
        ]);

        $positionId = $request->mst_job_position_id;
        $created = 0;
        $assignedUserNames = [];

        DB::beginTransaction();
        try {
            app(DepartmentStructureService::class)->lockAssignmentPosition((int) $positionId);
            foreach ($request->user_ids as $userId) {
                $assignments->create($request->user(), [
                    'user_id' => $userId,
                    'mst_job_position_id' => $positionId,
                    'is_active' => true,
                    'effective_from' => $request->effective_from ?: today(),
                    'effective_until' => $request->effective_until,
                    'assignment_source' => $request->assignment_source ?: 'manual_hr',
                ], $request->change_reason ?: 'Assignment dibuat melalui modul HR.');
                $created++;
                $assignedUserNames[] = User::find($userId)?->name ?? 'Karyawan';
            }
            DB::commit();

            // Flash reminder SweetAlert
            $positionName = MstJobPosition::find($positionId)?->position_name ?? 'posisi terkait';
            if ($created === 1) {
                $reminderMsg = 'Jangan lupa untuk tambahkan penilaian competency untuk '.$assignedUserNames[0].' di job position '.$positionName.'.';
            } else {
                $reminderMsg = 'Jangan lupa untuk tambahkan penilaian competency untuk '.$created.' karyawan terpilih di job position '.$positionName.'.';
            }

            return back()
                ->with('success', "{$created} karyawan berhasil di-assign ke posisi.")
                ->with('reminder', $reminderMsg);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UserJobPositionController::store', ['err' => $e->getMessage()]);

            return back()->with('error', 'Gagal menyimpan: '.$e->getMessage());
        }
    }

    /**
     * Perbarui mapping user-posisi.
     */
    public function update(Request $request, UserJobPosition $userJobPosition, KmOrganizationAssignmentService $assignments)
    {
        $this->ensureAccess();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'mst_job_position_id' => 'required|exists:mst_job_positions,id',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
            'assignment_source' => 'nullable|string|max:64',
            'change_reason' => 'nullable|string|max:2000',
        ]);

        try {
            DB::transaction(function () use ($request, $userJobPosition, $assignments) {
                app(DepartmentStructureService::class)->lockAssignmentPosition((int) $request->mst_job_position_id);
                $assignments->update($request->user(), $userJobPosition, [
                    'user_id' => $request->user_id,
                    'mst_job_position_id' => $request->mst_job_position_id,
                    'is_active' => $userJobPosition->is_active,
                    'effective_from' => $request->effective_from ?: ($userJobPosition->effective_from ?? today()),
                    'effective_until' => $request->effective_until,
                    'assignment_source' => $request->assignment_source ?: ($userJobPosition->assignment_source ?? 'manual_hr'),
                ], $request->change_reason ?: 'Assignment diperbarui melalui modul HR.');
            }, 3);

            // Flash reminder SweetAlert
            $userName = User::find($request->user_id)?->name ?? 'karyawan';
            $positionName = MstJobPosition::find($request->mst_job_position_id)?->position_name ?? 'posisi terkait';
            $reminderMsg = 'Jangan lupa untuk tambahkan penilaian competency untuk '.$userName.' di job position '.$positionName.'.';

            return back()
                ->with('success', 'Mapping karyawan berhasil diperbarui.')
                ->with('reminder', $reminderMsg);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('UserJobPositionController::update', ['err' => $e->getMessage()]);

            return back()->with('error', 'Gagal memperbarui mapping: '.$e->getMessage());
        }
    }

    /**
     * Toggle aktif/nonaktif mapping user-posisi.
     */
    public function toggleActive(Request $request, UserJobPosition $userJobPosition, KmOrganizationAssignmentService $assignments)
    {
        $this->ensureAccess();
        DB::transaction(function () use ($request, $userJobPosition, $assignments) {
            app(DepartmentStructureService::class)->lockOrganization();
            $locked = UserJobPosition::whereKey($userJobPosition->id)->lockForUpdate()->firstOrFail();
            if (! $locked->is_active) {
                app(DepartmentStructureService::class)->lockAssignmentPosition((int) $locked->mst_job_position_id);
            }
            $assignments->toggle($request->user(), $locked,
                $request->string('change_reason')->trim()->toString() ?: 'Status assignment diubah melalui modul HR.');
        }, 3);
        $userJobPosition->refresh();
        $status = $userJobPosition->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Mapping berhasil {$status}.");
    }

    /**
     * Hapus mapping user-posisi.
     */
    public function destroy(
        Request $request,
        UserJobPosition $userJobPosition,
        KmOrganizationAssignmentService $assignments,
    ) {
        $this->ensureAccess();
        if ($userJobPosition->is_active) {
            $assignments->toggle(
                $request->user(),
                $userJobPosition,
                $request->string('change_reason')->trim()->toString()
                    ?: 'Assignment dinonaktifkan melalui aksi hapus legacy.',
            );
        }

        return back()->with('success', 'Mapping dinonaktifkan dan histori tetap dipertahankan.');
    }

    /**
     * API: ambil posisi-posisi yang dipegang user tertentu.
     */
    public function getPositionsByUser(Request $request)
    {
        abort_unless($this->mappingAccess->canViewMapping($request->user()), 403);
        $canManageMapping = $this->mappingAccess->canManageMapping($request->user());
        $employeeId = (int) $request->input('user_id');
        $readable = $this->mappingAccess->getReadableMappingScope($request->user())
            ->where('user_id', $employeeId);
        abort_unless($employeeId > 0 && ($canManageMapping || (clone $readable)->exists()), 403);
        $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $positions = $readable
            ->activeAt()
            ->with($canManageMapping ? 'jobPosition.approvalRoutes.approverPosition' : 'jobPosition')
            ->get()
            ->pluck('jobPosition')
            ->filter();

        if (! $canManageMapping) {
            $positions = $positions->unique('id')->values()->map(fn ($position) => $position->only([
                'id', 'position_name', 'department_id', 'section_id', 'job_level',
            ]));
        }

        return response()->json($positions);
    }

    // ========================
    //  Modul 3.1 — Working Experience CRUD
    // ========================

    /**
     * API: Ambil semua working experiences untuk seorang user.
     * GET /hr/user-job-position/api/working-experience?user_id=X
     */
    public function getWorkingExperiences(Request $request)
    {
        abort_unless($this->mappingAccess->canViewMapping($request->user()), 403);
        abort_unless($this->mappingAccess->canViewEmployeeWorkingExperience($request->user(), (int) $request->input('user_id')), 403);

        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        $data = WorkingExperience::where('user_id', $request->user_id)
            ->chronological()
            ->get()
            ->map(fn ($we) => [
                'id' => $we->id,
                'year_start' => $we->year_start,
                'year_end' => $we->year_end,
                'year_end_label' => $we->year_end_label,
                'job_position' => $we->job_position,
                'section' => $we->section,
                'departemen' => $we->departemen,
                'keterangan' => $we->keterangan,
            ]);

        return response()->json(['data' => $data]);
    }

    /**
     * API: Simpan (create) satu working experience baru.
     * POST /hr/user-job-position/api/working-experience
     */
    public function storeWorkingExperience(
        StoreWorkingExperienceRequest $request,
        WorkingExperienceValidationService $validation,
    ) {
        $this->ensureAccess();

        $validated = $validation->prepare($request->validated());
        $we = WorkingExperience::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Working experience berhasil ditambahkan.',
            'data' => [
                'id' => $we->id,
                'year_start' => $we->year_start,
                'year_end' => $we->year_end,
                'year_end_label' => $we->year_end_label,
                'job_position' => $we->job_position,
                'section' => $we->section,
                'departemen' => $we->departemen,
                'keterangan' => $we->keterangan,
            ],
        ]);
    }

    /**
     * API: Update satu working experience.
     * PUT /hr/user-job-position/api/working-experience/{id}
     */
    public function updateWorkingExperience(
        UpdateWorkingExperienceRequest $request,
        WorkingExperience $workingExperience,
        WorkingExperienceValidationService $validation,
    ) {
        $this->ensureAccess();

        $validated = $validation->prepare($request->validated(), $workingExperience->user);

        $workingExperience->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Working experience berhasil diperbarui.',
            'data' => [
                'id' => $workingExperience->id,
                'year_start' => $workingExperience->year_start,
                'year_end' => $workingExperience->year_end,
                'year_end_label' => $workingExperience->year_end_label,
                'job_position' => $workingExperience->job_position,
                'section' => $workingExperience->section,
                'departemen' => $workingExperience->departemen,
                'keterangan' => $workingExperience->keterangan,
            ],
        ]);
    }

    /**
     * API: Hapus satu working experience.
     * DELETE /hr/user-job-position/api/working-experience/{id}
     */
    public function destroyWorkingExperience(WorkingExperience $workingExperience)
    {
        $this->ensureAccess();
        $workingExperience->delete();

        return response()->json(['success' => true, 'message' => 'Working experience berhasil dihapus.']);
    }

    /**
     * Import bulk Working Experience dari file Excel.
     * POST /hr/user-job-position/api/working-experience/import
     */
    public function importWorkingExperience(
        ImportWorkingExperienceRequest $request,
        WorkingExperienceValidationService $validation,
    ) {
        $this->ensureAccess();

        $stagedPath = null;

        try {
            $file = $request->file('import_file');
            $sourcePath = $file?->getPathname();

            if (
                ! $file
                || ! $file->isValid()
                || ! is_string($sourcePath)
                || trim($sourcePath) === ''
                || ! is_readable($sourcePath)
            ) {
                return back()->withErrors([
                    'import_file' => 'File upload sementara tidak tersedia. Silakan pilih ulang file lalu coba kembali.',
                ]);
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $stagedPath = 'imports/working-experience/'.Str::uuid().'.'.$extension;
            $sourceStream = fopen($sourcePath, 'rb');

            try {
                $stored = Storage::disk('local')->put($stagedPath, $sourceStream);
            } finally {
                if (is_resource($sourceStream)) {
                    fclose($sourceStream);
                }
            }

            if (! $stored) {
                throw new \RuntimeException('File upload tidak dapat disiapkan untuk proses import.');
            }

            $importer = new WorkingExperienceImport($validation);
            Excel::import($importer, Storage::disk('local')->path($stagedPath));
            $importer->persist();

            $successCount = $importer->successCount;
            $skippedCount = $importer->skippedCount;
            $failures = $importer->failures;
            $importSummary = $this->workingExperienceImportSummary($successCount, $skippedCount);

            if (empty($failures)) {
                return back()->with('import_success', $importSummary);
            }

            // Ada beberapa baris gagal
            $failureMessages = array_map(function ($f) {
                $employee = $f['npk'] === '-'
                    ? "{$f['name']} (NPK belum tersedia)"
                    : "{$f['npk']} - {$f['name']}";

                return "Baris {$f['row']} — {$employee}: {$f['reason']}";
            }, $failures);

            return back()
                ->with('import_success', $importSummary)
                ->with('import_failures', $failureMessages)
                ->with('import_failure_details', $failures);
        } catch (Throwable $e) {
            Log::error('WorkingExperienceImport error', [
                'err' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return back()->with(
                'error',
                'Import belum dapat diproses. Pastikan file sesuai dengan template, lalu coba kembali.',
            );
        } finally {
            if ($stagedPath !== null) {
                Storage::disk('local')->delete($stagedPath);
            }
        }
    }

    private function workingExperienceImportSummary(int $successCount, int $skippedCount): string
    {
        if ($successCount === 0 && $skippedCount === 0) {
            return 'Import selesai. Tidak ada data untuk ditambahkan.';
        }

        if ($successCount === 0) {
            return "Tidak ada data baru yang ditambahkan. {$skippedCount} baris yang sama sudah tersedia dan dilewati.";
        }

        $summary = "{$successCount} baris berhasil diimport.";

        if ($skippedCount > 0) {
            $summary .= " {$skippedCount} baris yang sama sudah tersedia dan dilewati.";
        }

        return $summary;
    }

    /**
     * Download template Excel kosong untuk import Working Experience.
     * GET /hr/user-job-position/api/working-experience/import/template
     */
    public function downloadImportTemplate()
    {
        $this->ensureAccess();

        return Excel::download(
            new \App\Exports\WorkingExperienceTemplateExport,
            'template_import_working_experience.xlsx'
        );
    }
}
