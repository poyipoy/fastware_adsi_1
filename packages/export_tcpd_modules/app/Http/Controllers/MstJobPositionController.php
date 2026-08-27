<?php

namespace App\Http\Controllers;

use App\Enums\HRMenuAccessGroup;
use App\Models\MstDepartment;
use App\Models\MstJobPosition;
use App\Models\MstPositionApproval;
use App\Models\MstSection;
use App\Models\User;
use App\Models\UserJobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MstJobPositionController extends Controller
{
    private function ensureAccess(): void
    {
        $userName = auth()->user()->name ?? '';
        abort_unless(HRMenuAccessGroup::JOB_POSITION->hasAccess($userName), 403);
    }

    // -------------------------------------------------------------------------
    // CRUD Job Position
    // -------------------------------------------------------------------------

    /**
     * Daftar semua Job Position master.
     */
    public function index(Request $request)
    {
        $this->ensureAccess();

        $query = MstJobPosition::with(['approvalRoutes.approverPosition', 'department', 'section']);

        if ($request->filled('position_name')) {
            $query->where('position_name', 'like', '%' . $request->input('position_name') . '%');
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        $positions = $query->orderBy('position_name')->get();

        $departments = MstDepartment::active()->orderBy('name')->get();

        $sections = collect();
        if ($request->filled('department_id')) {
            $sections = MstSection::active()
                ->where('department_id', $request->input('department_id'))
                ->orderBy('name')
                ->get();
        }

        return view('mst_job_position.index', compact('positions', 'departments', 'sections'));
    }

    /**
     * Form tambah Job Position baru.
     */
    public function create()
    {
        $this->ensureAccess();

        $allPositions = MstJobPosition::active()->orderBy('position_name')->get();
        $departments  = MstDepartment::active()->orderBy('name')->get();
        $sections     = collect(); // kosong; diisi via AJAX setelah dept dipilih

        return view('mst_job_position.form', [
            'position'       => null,
            'allPositions'   => $allPositions,
            'departments'    => $departments,
            'sections'       => $sections,
            'approvalRoutes' => [],
        ]);
    }

    /**
     * Simpan Job Position baru beserta rute approval-nya.
     */
    public function store(Request $request)
    {
        $this->ensureAccess();

        $request->validate([
            'position_name'   => 'required|string|max:255|unique:mst_job_positions,position_name',
            'department_id'   => 'nullable|exists:mst_departments,id',
            'section_id'      => 'nullable|exists:mst_sections,id',
            'approval_levels' => 'nullable|array',
            'approval_levels.*.level'               => 'required|integer|min:0|max:3',
            'approval_levels.*.approver_position_id' => 'nullable|exists:mst_job_positions,id',
        ]);

        DB::beginTransaction();
        try {
            $position = MstJobPosition::create([
                'position_name' => $request->position_name,
                'department_id' => $request->department_id ?: null,
                'section_id'    => $request->section_id ?: null,
                'is_active'     => true,
            ]);

            if ($request->filled('approval_levels')) {
                foreach ($request->approval_levels as $route) {
                    if (!isset($route['level']) || $route['level'] === '') continue;
                    MstPositionApproval::updateOrCreate(
                        ['position_id' => $position->id, 'approval_level' => $route['level']],
                        ['approver_position_id' => $route['approver_position_id'] ?: null]
                    );
                }
            }

            DB::commit();
            return redirect()->route('mst-job-position.index')
                ->with('success', "Job Position '{$position->position_name}' berhasil ditambahkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MstJobPositionController::store error', ['err' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * Form edit Job Position.
     */
    public function edit(MstJobPosition $mstJobPosition)
    {
        $this->ensureAccess();

        $mstJobPosition->load('approvalRoutes.approverPosition', 'department', 'section');
        $allPositions = MstJobPosition::active()
            ->where('id', '!=', $mstJobPosition->id)
            ->orderBy('position_name')
            ->get();
        $departments = MstDepartment::active()->orderBy('name')->get();
        $sections    = $mstJobPosition->department_id
            ? MstSection::active()->where('department_id', $mstJobPosition->department_id)->orderBy('name')->get()
            : collect();

        return view('mst_job_position.form', [
            'position'       => $mstJobPosition,
            'allPositions'   => $allPositions,
            'departments'    => $departments,
            'sections'       => $sections,
            'approvalRoutes' => $mstJobPosition->approvalRoutes,
        ]);
    }

    /**
     * Update Job Position beserta rute approval-nya.
     */
    public function update(Request $request, MstJobPosition $mstJobPosition)
    {
        $this->ensureAccess();

        $request->validate([
            'position_name'   => "required|string|max:255|unique:mst_job_positions,position_name,{$mstJobPosition->id}",
            'department_id'   => 'nullable|exists:mst_departments,id',
            'section_id'      => 'nullable|exists:mst_sections,id',
            'approval_levels' => 'nullable|array',
            'approval_levels.*.level'               => 'required|integer|min:0|max:3',
            'approval_levels.*.approver_position_id' => 'nullable|exists:mst_job_positions,id',
        ]);

        DB::beginTransaction();
        try {
            $mstJobPosition->update([
                'position_name' => $request->position_name,
                'department_id' => $request->department_id ?: null,
                'section_id'    => $request->section_id ?: null,
            ]);

            // Hapus lama, ganti baru
            $mstJobPosition->approvalRoutes()->delete();

            if ($request->filled('approval_levels')) {
                foreach ($request->approval_levels as $route) {
                    if (!isset($route['level']) || $route['level'] === '') continue;
                    MstPositionApproval::create([
                        'position_id'          => $mstJobPosition->id,
                        'approval_level'       => $route['level'],
                        'approver_position_id' => $route['approver_position_id'] ?: null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('mst-job-position.index')
                ->with('success', "Job Position '{$mstJobPosition->position_name}' berhasil diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * Toggle aktif/nonaktif posisi.
     */
    public function toggleActive(MstJobPosition $mstJobPosition)
    {
        $this->ensureAccess();
        $mstJobPosition->update(['is_active' => !$mstJobPosition->is_active]);
        $status = $mstJobPosition->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Posisi '{$mstJobPosition->position_name}' berhasil {$status}.");
    }

    /**
     * Delete posisi (hanya jika tidak ada mapping user).
     */
    public function destroy(MstJobPosition $mstJobPosition)
    {
        $this->ensureAccess();

        if ($mstJobPosition->users()->exists()) {
            return back()->with('error', 'Tidak bisa menghapus posisi yang masih memiliki karyawan terkait.');
        }

        $name = $mstJobPosition->position_name;
        $mstJobPosition->delete();
        return redirect()->route('mst-job-position.index')
            ->with('success', "Posisi '{$name}' berhasil dihapus.");
    }

    // -------------------------------------------------------------------------
    // AJAX – Master Departemen & Section (dari tombol "+" di form)
    // -------------------------------------------------------------------------

    /**
     * Simpan departemen baru dan kembalikan sebagai JSON.
     */
    public function storeDepartment(Request $request)
    {
        $this->ensureAccess();

        $request->validate(['name' => 'required|string|max:100|unique:mst_departments,name']);

        $dept = MstDepartment::create(['name' => trim($request->name), 'is_active' => true]);

        return response()->json(['id' => $dept->id, 'name' => $dept->name]);
    }

    /**
     * Simpan section baru dan kembalikan sebagai JSON.
     */
    public function storeSection(Request $request)
    {
        $this->ensureAccess();

        $request->validate([
            'department_id' => 'required|exists:mst_departments,id',
            'name'          => 'required|string|max:150',
        ]);

        // Cek duplikat per departemen
        $exists = MstSection::where('department_id', $request->department_id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'Section sudah ada di departemen ini.'], 422);
        }

        $sec = MstSection::create([
            'department_id' => $request->department_id,
            'name'          => trim($request->name),
            'is_active'     => true,
        ]);

        return response()->json(['id' => $sec->id, 'name' => $sec->name]);
    }

    /**
     * Ambil daftar section berdasarkan departemen (untuk AJAX filter dropdown).
     */
    public function getSectionsByDepartment(MstDepartment $mstDepartment)
    {
        $sections = MstSection::active()
            ->where('department_id', $mstDepartment->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($sections);
    }

    /**
     * Ambil daftar departemen aktif.
     */
    public function getDepartments()
    {
        $this->ensureAccess();
        $departments = MstDepartment::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($departments);
    }
}
