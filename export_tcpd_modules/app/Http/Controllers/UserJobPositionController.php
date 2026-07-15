<?php

namespace App\Http\Controllers;

use App\Enums\HRMenuAccessGroup;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        return view('user_job_position.index', compact('mappings', 'positions', 'users'));
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

        DB::beginTransaction();
        try {
            foreach ($request->user_ids as $userId) {
                UserJobPosition::firstOrCreate(
                    ['user_id' => $userId, 'mst_job_position_id' => $positionId],
                    ['is_active' => true]
                );
                $created++;
            }
            DB::commit();
            return back()->with('success', "{$created} karyawan berhasil di-assign ke posisi.");
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

            return back()->with('success', 'Mapping karyawan berhasil diperbarui.');
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
}
