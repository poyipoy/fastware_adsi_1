<?php

namespace App\Http\Controllers;

use App\Enums\HRMenuAccessGroup;
use App\Imports\WorkingExperienceImport;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Models\WorkingExperience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class UserJobPositionController extends Controller
{
    private function ensureAccess(): void
    {
        abort_unless(HRMenuAccessGroup::JOB_POSITION->hasAccessForUser(auth()->user()), 403);
    }

    /**
     * Halaman daftar mapping karyawan ↔ posisi.
     */
    public function index(Request $request)
    {
        $this->ensureAccess();

        $query = UserJobPosition::with(['user', 'jobPosition.department', 'jobPosition.section'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('position_id')) {
            $query->where('mst_job_position_id', $request->position_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $mappings   = $query->paginate(25)->appends($request->all());
        $positions  = MstJobPosition::active()->with('department')->orderBy('position_name')->get();
        $users      = User::orderBy('name')->get();
        $departments = \App\Models\MstDepartment::active()->orderBy('name')->get();
        $sections = \App\Models\MstSection::active()->orderBy('name')->get();

        return view('user_job_position.index', compact('mappings', 'positions', 'users', 'departments', 'sections'));
    }

    /**
     * Simpan mapping baru: user ↔ posisi.
     */
    public function store(Request $request)
    {
        $this->ensureAccess();

        $request->validate([
            'user_ids'              => 'required|array|min:1',
            'user_ids.*'            => 'exists:users,id',
            'mst_job_position_id'   => 'required|exists:mst_job_positions,id',
        ]);

        $positionId = $request->mst_job_position_id;
        $created = 0;
        $assignedUserNames = [];

        DB::beginTransaction();
        try {
            foreach ($request->user_ids as $userId) {
                UserJobPosition::firstOrCreate(
                    ['user_id' => $userId, 'mst_job_position_id' => $positionId],
                    ['is_active' => true]
                );
                $created++;
                $assignedUserNames[] = User::find($userId)?->name ?? 'Karyawan';
            }
            DB::commit();

            // Flash reminder SweetAlert
            $positionName = MstJobPosition::find($positionId)?->position_name ?? 'posisi terkait';
            if ($created === 1) {
                $reminderMsg = 'Jangan lupa untuk tambahkan penilaian competency untuk ' . $assignedUserNames[0] . ' di job position ' . $positionName . '.';
            } else {
                $reminderMsg = 'Jangan lupa untuk tambahkan penilaian competency untuk ' . $created . ' karyawan terpilih di job position ' . $positionName . '.';
            }

            return back()
                ->with('success', "{$created} karyawan berhasil di-assign ke posisi.")
                ->with('reminder', $reminderMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UserJobPositionController::store', ['err' => $e->getMessage()]);
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * Perbarui mapping user-posisi.
     */
    public function update(Request $request, UserJobPosition $userJobPosition)
    {
        $this->ensureAccess();

        $request->validate([
            'user_id'              => 'required|exists:users,id',
            'mst_job_position_id'   => 'required|exists:mst_job_positions,id',
        ]);

        try {
            $exists = UserJobPosition::where('user_id', $request->user_id)
                ->where('mst_job_position_id', $request->mst_job_position_id)
                ->where('id', '!=', $userJobPosition->id)
                ->exists();

            if ($exists) {
                return back()->with('error', 'Mapping untuk karyawan dan posisi tersebut sudah ada.');
            }

            $userJobPosition->update([
                'user_id' => $request->user_id,
                'mst_job_position_id' => $request->mst_job_position_id,
            ]);

            // Flash reminder SweetAlert
            $userName     = User::find($request->user_id)?->name ?? 'karyawan';
            $positionName = MstJobPosition::find($request->mst_job_position_id)?->position_name ?? 'posisi terkait';
            $reminderMsg  = 'Jangan lupa untuk tambahkan penilaian competency untuk ' . $userName . ' di job position ' . $positionName . '.';

            return back()
                ->with('success', 'Mapping karyawan berhasil diperbarui.')
                ->with('reminder', $reminderMsg);
        } catch (\Exception $e) {
            Log::error('UserJobPositionController::update', ['err' => $e->getMessage()]);
            return back()->with('error', 'Gagal memperbarui mapping: ' . $e->getMessage());
        }
    }

    /**
     * Toggle aktif/nonaktif mapping user-posisi.
     */
    public function toggleActive(UserJobPosition $userJobPosition)
    {
        $this->ensureAccess();
        $userJobPosition->update(['is_active' => !$userJobPosition->is_active]);
        $status = $userJobPosition->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Mapping berhasil {$status}.");
    }

    /**
     * Hapus mapping user-posisi.
     */
    public function destroy(UserJobPosition $userJobPosition)
    {
        $this->ensureAccess();
        $userJobPosition->delete();
        return back()->with('success', 'Mapping berhasil dihapus.');
    }

    /**
     * API: ambil posisi-posisi yang dipegang user tertentu.
     */
    public function getPositionsByUser(Request $request)
    {
        $userId = $request->user_id;
        $positions = UserJobPosition::where('user_id', $userId)
            ->where('is_active', true)
            ->with('jobPosition.approvalRoutes.approverPosition')
            ->get()
            ->pluck('jobPosition')
            ->filter();

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
        $this->ensureAccess();

        $request->validate(['user_id' => 'required|exists:users,id']);

        $data = WorkingExperience::where('user_id', $request->user_id)
            ->chronological()
            ->get()
            ->map(fn($we) => [
                'id'            => $we->id,
                'year_start'    => $we->year_start,
                'year_end'      => $we->year_end,
                'year_end_label'=> $we->year_end_label,
                'job_position'  => $we->job_position,
                'section'       => $we->section,
                'departemen'    => $we->departemen,
                'keterangan'    => $we->keterangan,
            ]);

        return response()->json(['data' => $data]);
    }

    /**
     * API: Simpan (create) satu working experience baru.
     * POST /hr/user-job-position/api/working-experience
     */
    public function storeWorkingExperience(Request $request)
    {
        $this->ensureAccess();

        $validated = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'year_start'    => 'required|integer|digits:4|min:1900|max:' . (date('Y') + 5),
            'year_end'      => 'nullable|integer|digits:4|min:1900|max:' . (date('Y') + 5) . '|gte:year_start',
            'job_position'  => 'required|string|max:255',
            'section'       => 'nullable|string|max:255',
            'departemen'    => 'nullable|string|max:255',
            'keterangan'    => 'nullable|string|max:1000',
        ]);

        $we = WorkingExperience::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Working experience berhasil ditambahkan.',
            'data'    => [
                'id'             => $we->id,
                'year_start'     => $we->year_start,
                'year_end'       => $we->year_end,
                'year_end_label' => $we->year_end_label,
                'job_position'   => $we->job_position,
                'section'        => $we->section,
                'departemen'     => $we->departemen,
                'keterangan'     => $we->keterangan,
            ],
        ]);
    }

    /**
     * API: Update satu working experience.
     * PUT /hr/user-job-position/api/working-experience/{id}
     */
    public function updateWorkingExperience(Request $request, WorkingExperience $workingExperience)
    {
        $this->ensureAccess();

        $validated = $request->validate([
            'year_start'   => 'required|integer|digits:4|min:1900|max:' . (date('Y') + 5),
            'year_end'     => 'nullable|integer|digits:4|min:1900|max:' . (date('Y') + 5) . '|gte:year_start',
            'job_position' => 'required|string|max:255',
            'section'      => 'nullable|string|max:255',
            'departemen'   => 'nullable|string|max:255',
            'keterangan'   => 'nullable|string|max:1000',
        ]);

        $workingExperience->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Working experience berhasil diperbarui.',
            'data'    => [
                'id'             => $workingExperience->id,
                'year_start'     => $workingExperience->year_start,
                'year_end'       => $workingExperience->year_end,
                'year_end_label' => $workingExperience->year_end_label,
                'job_position'   => $workingExperience->job_position,
                'section'        => $workingExperience->section,
                'departemen'     => $workingExperience->departemen,
                'keterangan'     => $workingExperience->keterangan,
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
    public function importWorkingExperience(Request $request)
    {
        $this->ensureAccess();

        $request->validate([
            'import_file' => 'required|file|max:5120',
        ], [
            'import_file.required' => 'File Excel wajib dipilih.',
            'import_file.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $extension = strtolower($request->file('import_file')->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return back()->withErrors(['import_file' => 'Format file harus .xlsx, .xls, atau .csv.']);
        }

        try {
            $file = $request->file('import_file');
            
            if (!$file->isValid()) {
                return back()->withErrors(['import_file' => 'File upload gagal (Error ' . $file->getError() . ').']);
            }

            // Gunakan move() alih-alih store() karena terkadang store() gagal di Windows/Laragon
            // jika sys_temp_dir bermasalah.
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = storage_path('app/temp_imports');
            $file->move($destinationPath, $filename);
            
            $absolutePath = $destinationPath . DIRECTORY_SEPARATOR . $filename;

            $importer = new WorkingExperienceImport();
            Excel::import($importer, $absolutePath);

            // Hapus file setelah di-import
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }

            $successCount = $importer->successCount;
            $failures     = $importer->failures;

            if (empty($failures)) {
                return back()->with('import_success', "Import berhasil! {$successCount} baris data working experience berhasil ditambahkan.");
            }

            // Ada beberapa baris gagal
            $failureMessages = array_map(function ($f) {
                $errText = collect($f['errors'])->flatten()->implode('; ');
                return "Baris {$f['row']} ({$f['name']}): {$errText}";
            }, $failures);

            return back()
                ->with('import_success', "{$successCount} baris berhasil diimport.")
                ->with('import_failures', $failureMessages);

        } catch (\Exception $e) {
            Log::error('WorkingExperienceImport error', ['err' => $e->getMessage()]);
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
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
