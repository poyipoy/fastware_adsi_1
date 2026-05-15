<?php

namespace App\Http\Controllers;

use App\Enums\HRMenuAccessGroup;
use App\Models\Role;
use App\Models\TcJobPosition;
use App\Models\MstTc;
use App\Models\MstSoftSkill;
use App\Models\MstAdditionals;
use App\Models\User;
use App\Models\UserJobAccess;
use App\Models\TrsPenilaianTc;
use App\Models\DetailTcPenilaian;
use App\Models\TcPeopleDevelopment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TcJobController extends Controller
{
    private function ensureJobPositionAccess(): void
    {
        $userName = auth()->user()->name ?? '';
        abort_unless(HRMenuAccessGroup::JOB_POSITION->hasAccess($userName), 403);
    }

    /**
     * Copy master competency data (mst_tcs, mst_soft_skills, mst_additionals)
     * from an existing tc_job_positions record of the same job_position
     * to a newly created tc_job_positions record.
     */
    private function copyMasterCompetencyData(TcJobPosition $newJobPosition): void
    {
        // Cari record lain dengan job_position yang sama yang sudah punya data master
        $existingRecord = TcJobPosition::where('job_position', $newJobPosition->job_position)
            ->where('id', '!=', $newJobPosition->id)
            ->whereHas('mstTcs')
            ->first();

        if (!$existingRecord) {
            Log::info('No existing master data to copy for job_position: ' . $newJobPosition->job_position);
            return;
        }

        // Copy mst_tcs
        foreach ($existingRecord->mstTcs as $tc) {
            MstTc::create([
                'id_job_position' => $newJobPosition->id,
                'id_poin_kategori' => $tc->id_poin_kategori,
                'keterangan_tc' => $tc->keterangan_tc,
                'deskripsi_tc' => $tc->deskripsi_tc,
                'nilai' => $tc->nilai,
            ]);
        }

        // Copy mst_soft_skills
        foreach ($existingRecord->mstSoftSkills as $sk) {
            MstSoftSkill::create([
                'id_job_position' => $newJobPosition->id,
                'id_poin_kategori' => $sk->id_poin_kategori,
                'keterangan_sk' => $sk->keterangan_sk,
                'deskripsi_sk' => $sk->deskripsi_sk,
                'nilai' => $sk->nilai,
            ]);
        }

        // Copy mst_additionals
        foreach ($existingRecord->mstAdditional as $ad) {
            MstAdditionals::create([
                'id_job_position' => $newJobPosition->id,
                'id_poin_kategori' => $ad->id_poin_kategori,
                'keterangan_ad' => $ad->keterangan_ad,
                'deskripsi_ad' => $ad->deskripsi_ad,
                'nilai' => $ad->nilai,
            ]);
        }

        Log::info('Master competency data copied for new job_position record:', [
            'new_id' => $newJobPosition->id,
            'source_id' => $existingRecord->id,
            'job_position' => $newJobPosition->job_position,
        ]);
    }

    public function jobShow()
    {
        $this->ensureJobPositionAccess();

        // Ambil job position yang spesifik untuk edit
        $jobPositions = TcJobPosition::with('user', 'role')
            ->select('job_position', 'status', DB::raw('MIN(id) as id'))
            ->groupBy('job_position', 'status')
            ->get();

        // Ambil semua pengguna dan filter berdasarkan job_position
        $users = User::all();
        $roles = Role::all();

        return view('tc_job.tc_job', compact('jobPositions', 'users', 'roles'));
    }

    public function getUserRole($userId)
    {
        $user = User::with('roles')->find($userId); // Make sure the relationship is called 'roles' as defined in your model
        if ($user && $user->roles) {
            return response()->json([
                'roleName' => $user->roles->role,
                'roleId' => $user->roles->id,
            ]);
        } else {
            return response()->json(null, 404); // Send an error if no role found
        }
    }

    public function store(Request $request)
    {
        $this->ensureJobPositionAccess();

        // Validate the incoming request data
        $request->validate([
            'id_user' => 'required|array', // Validate id_user as an array
            'id_user.*' => 'exists:users,id', // Validate each id_user exists in the users table
            'job_position' => 'required|string|max:255', // Validate job_position field
        ]);

        try {
            // Prepare and save each job position with the corresponding user
            foreach ($request->id_user as $userId) {
                // Fetch the user to get the role ID
                $user = User::findOrFail($userId);
                $idRole = $user->role_id; // Assuming id_role is a column in the users table

                // Prepare the data to be saved
                $data = [
                    'id_user' => $userId,
                    'id_role' => $idRole, // Save id_role along with id_user and job_position
                    'job_position' => $request->job_position,
                    'status' => 1, // Set the status to 1
                ];

                // Create the new job position with the modified data
                $jobPosition = TcJobPosition::create($data);

                // Auto-copy master competency data dari record yang sudah ada
                $this->copyMasterCompetencyData($jobPosition);

                // Simpan akses untuk user target (bukan user login HR)
                UserJobAccess::firstOrCreate([
                    'user_id' => $user->id,
                    'role_id' => $user->role_id,
                    'job_position' => $request->job_position,
                ]);

                // Log the success for each job position created
                Log::info('Job Position added successfully:', [
                    'id_user' => $data['id_user'],
                    'id_role' => $data['id_role'],
                    'job_position' => $data['job_position'],
                    'status' => $data['status'],
                ]);
            }

            // Redirect back with a success message
            return redirect()->back()->with('success', 'Job Position(s) added successfully.');
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error adding Job Position:', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            // Redirect back with an error message
            return redirect()->back()->with('error', 'Failed to add Job Position.');
        }
    }

    public function getJobPositionData($id)
    {
        $this->ensureJobPositionAccess();

        // Fetch the job position with associated user and role
        $jobPosition = TcJobPosition::find($id);
        if (!$jobPosition) {
            return redirect()->route('getJobPosition')->with('error', 'Job Position not found');
        }

        $jobPositions = TcJobPosition::all();

        // Fetch all users
        $allUsers = User::all();

        // Fetch users related to the job position
        $relatedUsers = User::whereHas('jobPositions', function ($query) use ($jobPosition) {
            $query->where('job_position', $jobPosition->job_position);
        })->get();

        // Count job positions for each related user
        $userJobPositionCounts = [];
        foreach ($relatedUsers as $user) {
            $userJobPositionCounts[$user->id] = $user->jobPositions->count();
        }

        // **NEW** Fetch all job positions with the same job_position and get their ids
        $jobPositionIds = TcJobPosition::where('job_position', $jobPosition->job_position)->pluck('id');
        // Return the view with the job position, related users, all users data, and job position counts
        return view('tc_job.edit_job', [
            'jobPosition' => $jobPosition,
            'relatedUsers' => $relatedUsers,
            'allUsers' => $allUsers,
            'userJobPositionCounts' => $userJobPositionCounts,
            'jobPositionIds' => $jobPositionIds,
            'jobPositions' => $jobPositions,
        ]);
    }

    public function updateJob(Request $request, $id)
    {
        $this->ensureJobPositionAccess();

        $jobPosition = TcJobPosition::find($id);

        if (!$jobPosition) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job Position tidak ditemukan',
            ], 404);
        }

        // Simpan nama lama sebelum update
        $oldJobPositionName = $jobPosition->job_position;
        $newJobPositionName = $request->input('job_position');

        // Jika nama job position berubah, update semua tabel terkait
        if ($oldJobPositionName !== $newJobPositionName) {
            // Update semua record di tc_job_positions yang punya nama lama
            TcJobPosition::where('job_position', $oldJobPositionName)
                ->update(['job_position' => $newJobPositionName]);

            // Update id_job_position di trs_penilaian_tcs
            TrsPenilaianTc::where('id_job_position', $oldJobPositionName)
                ->update(['id_job_position' => $newJobPositionName]);

            // Update id_job_position di detail_penilaian_tcs
            DetailTcPenilaian::where('id_job_position', $oldJobPositionName)
                ->update(['id_job_position' => $newJobPositionName]);

            // Update job_position di tc_user_job_accesses
            UserJobAccess::where('job_position', $oldJobPositionName)
                ->update(['job_position' => $newJobPositionName]);

            // Update id_job_position di tc_people_developments
            TcPeopleDevelopment::where('id_job_position', $oldJobPositionName)
                ->update(['id_job_position' => $newJobPositionName]);

            Log::info('Job position name updated across all tables', [
                'old_name' => $oldJobPositionName,
                'new_name' => $newJobPositionName,
            ]);
        }

        // Mendapatkan ID pengguna yang dipilih
        $selectedUserIds = $request->input('id_user', []);

        // Mendapatkan ID pengguna yang saat ini terhubung dengan job position
        $currentUserIds = TcJobPosition::where('job_position', $newJobPositionName)
            ->pluck('id_user')
            ->toArray();

        // Melakukan iterasi pada setiap ID pengguna yang dipilih
        foreach ($selectedUserIds as $userId) {
            $user = User::find($userId);

            if ($user) {
                // Memperbarui entri job position yang sudah ada jika sudah ada
                $existingJobPosition = TcJobPosition::where('job_position', $newJobPositionName)
                    ->where('id_user', $user->id)
                    ->first();

                if ($existingJobPosition) {
                    // Jika ada, hanya memperbarui status dan id_role
                    $existingJobPosition->id_role = $user->role_id;
                    $existingJobPosition->status = 1; // Memastikan status diatur ke 1
                    $existingJobPosition->save();
                } else {
                    // Jika tidak ada, buat entri baru dengan status = 1
                    $newEntry = TcJobPosition::create([
                        'job_position' => $newJobPositionName,
                        'id_user' => $user->id,
                        'id_role' => $user->role_id,
                        'status' => 1, // Mengatur status ke 1
                    ]);

                    // Auto-copy master competency data dari record yang sudah ada
                    $this->copyMasterCompetencyData($newEntry);

                }

                // Pastikan mapping akses selalu ada untuk user yang ditugaskan
                UserJobAccess::firstOrCreate([
                    'user_id' => $user->id,
                    'role_id' => $user->role_id,
                    'job_position' => $newJobPositionName,
                ]);
            }
        }

        // Menangani penghapusan pengguna yang tidak lagi ditugaskan
        foreach ($currentUserIds as $currentUserId) {
            if (!in_array($currentUserId, $selectedUserIds)) {
                // Menghapus atau menandai sebagai tidak aktif
                TcJobPosition::where('job_position', $newJobPositionName)
                    ->where('id_user', $currentUserId)
                    ->delete(); // Baris ini menghapus catatan, ubah jika Anda perlu menyimpannya tetapi mengatur status ke tidak aktif

                UserJobAccess::where('user_id', $currentUserId)
                    ->where('job_position', $newJobPositionName)
                    ->delete();
            }
        }

        // Mengarahkan ke route jobShow dengan pesan sukses
        return redirect()->route('jobShow')->with('success', 'Job Position berhasil diperbarui');
    }

    public function deleteRow(Request $request)
    {
        $this->ensureJobPositionAccess();

        // Ambil job_position dan id_user dari request
        $jobPositionName = $request->input('jobPositionId'); // Menggunakan job_position sebagai nama
        $userId = $request->input('userId'); // Ambil id_user yang dikirim dari front end

        // Cari semua job positions berdasarkan nama dan id_user
        $jobPositions = TcJobPosition::where('job_position', $jobPositionName)
            ->where('id_user', $userId)
            ->get();

        // Cek apakah job positions ditemukan
        if ($jobPositions->isNotEmpty()) {
            // Hapus semua entri yang sesuai
            TcJobPosition::where('job_position', $jobPositionName)
                ->where('id_user', $userId)
                ->delete(); // Hapus entri dari database

            UserJobAccess::where('user_id', $userId)
                ->where('job_position', $jobPositionName)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entri job position berhasil dihapus untuk id_user yang sama',
                'id_user' => $userId
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Job position tidak ditemukan']);
    }
}
