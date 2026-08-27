<?php
/**
 * Script Migrasi Otomatis: tc_job_positions → mst_job_positions, mst_position_approvals, user_job_positions
 * 
 * Cara pakai: buka di browser http://localhost/fastware_adsi_1/run_migrate_job_positions.php
 * atau jalankan via CLI: php run_migrate_job_positions.php
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\MstJobPosition;
use App\Models\MstPositionApproval;
use App\Models\UserJobPosition;
use App\Models\User;

echo "<pre>";
echo "=== Migrasi tc_job_positions → Struktur Master Baru ===\n\n";

DB::beginTransaction();
try {
    // ============================================================
    // STEP 1: Jalankan migrations yang belum berjalan
    // ============================================================
    echo "STEP 1: Menjalankan database migrations...\n";
    Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_30_000001_create_mst_job_positions_table.php', '--force' => true]);
    Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_30_000002_create_mst_position_approvals_table.php', '--force' => true]);
    Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_30_000003_create_user_job_positions_table.php', '--force' => true]);
    echo "  ✓ Tables created/verified.\n\n";

    // ============================================================
    // STEP 2: Ekstrak posisi unik dari tc_job_positions
    // ============================================================
    echo "STEP 2: Mengekstrak posisi unik dari tc_job_positions...\n";

    $rawPositions = DB::table('tc_job_positions')
        ->whereNotNull('job_position')
        ->where('job_position', '!=', '')
        ->select('job_position', 'department', 'section')
        ->distinct()
        ->orderBy('job_position')
        ->get();

    $positionIdMap = []; // 'position_name (normalized)' => id di mst_job_positions

    foreach ($rawPositions as $raw) {
        $name = trim($raw->job_position);
        if (empty($name)) continue;

        $existing = MstJobPosition::whereRaw('LOWER(position_name) = ?', [strtolower($name)])->first();
        if ($existing) {
            $positionIdMap[strtolower($name)] = $existing->id;
            echo "  [SKIP] Posisi sudah ada: {$name}\n";
            continue;
        }

        $pos = MstJobPosition::create([
            'position_name' => $name,
            'department'    => $raw->department ? trim($raw->department) : null,
            'section'       => $raw->section ? trim($raw->section) : null,
            'is_active'     => true,
        ]);
        $positionIdMap[strtolower($name)] = $pos->id;
        echo "  [+] Posisi dibuat: {$name}\n";
    }

    echo "\n  Total posisi di mst_job_positions: " . MstJobPosition::count() . "\n\n";

    // ============================================================
    // STEP 3: Bangun approval route dari pola section_head_name & department_head_name
    // ============================================================
    echo "STEP 3: Membangun rute approval...\n";

    // Ambil semua kombinasi unik: job_position → section_head_name, department_head_name
    $approvalPatterns = DB::table('tc_job_positions')
        ->whereNotNull('job_position')
        ->where('job_position', '!=', '')
        ->select('job_position', 'section_head_name', 'department_head_name', 'div_head_name')
        ->distinct()
        ->get();

    $approvalLog = [];

    foreach ($approvalPatterns as $pattern) {
        $posName = strtolower(trim($pattern->job_position));
        $positionId = $positionIdMap[$posName] ?? null;
        if (!$positionId) {
            echo "  [WARN] Posisi tidak ditemukan di map: {$pattern->job_position}\n";
            continue;
        }

        // Approval Level 1: Section Head
        if (!empty($pattern->section_head_name)) {
            $approverName = strtolower(trim($pattern->section_head_name));
            
            // Cari posisi yang dipegang orang ini (cari nama user di users table, lalu lihat posisi mereka)
            $approverUser = User::whereRaw('UPPER(TRIM(name)) = ?', [strtoupper(trim($pattern->section_head_name))])->first();
            $approverPositionId = null;
            
            if ($approverUser) {
                // Cari posisi user ini dari tc_job_positions
                $approverTcPos = DB::table('tc_job_positions')
                    ->where('id_user', $approverUser->id)
                    ->whereNotNull('job_position')
                    ->where('job_position', '!=', '')
                    ->first();
                if ($approverTcPos) {
                    $approverPositionId = $positionIdMap[strtolower(trim($approverTcPos->job_position))] ?? null;
                }
            }
            
            // Fallback: cari posisi yang nama posisinya mirip dengan section_head_name
            if (!$approverPositionId) {
                $guessedPos = MstJobPosition::whereRaw(
                    'LOWER(position_name) LIKE ?', ['%' . strtolower(trim($pattern->section_head_name)) . '%']
                )->first();
                $approverPositionId = $guessedPos?->id;
            }

            if ($approverPositionId && $approverPositionId !== $positionId) {
                MstPositionApproval::firstOrCreate(
                    ['position_id' => $positionId, 'approval_level' => 1],
                    ['approver_position_id' => $approverPositionId]
                );
                echo "  [L1] {$pattern->job_position} → {$pattern->section_head_name} (pos_id: {$approverPositionId})\n";
            } else {
                // Simpan tanpa approver_position_id (approver belum terpetakan ke posisi)
                MstPositionApproval::firstOrCreate(
                    ['position_id' => $positionId, 'approval_level' => 1],
                    ['approver_position_id' => null]
                );
                echo "  [L1-NO_MAP] {$pattern->job_position} → {$pattern->section_head_name} (belum terpetakan)\n";
            }
        }

        // Approval Level 2: Department Head
        if (!empty($pattern->department_head_name)) {
            $approverUser = User::whereRaw('UPPER(TRIM(name)) = ?', [strtoupper(trim($pattern->department_head_name))])->first();
            $approverPositionId = null;

            if ($approverUser) {
                $approverTcPos = DB::table('tc_job_positions')
                    ->where('id_user', $approverUser->id)
                    ->whereNotNull('job_position')
                    ->where('job_position', '!=', '')
                    ->first();
                if ($approverTcPos) {
                    $approverPositionId = $positionIdMap[strtolower(trim($approverTcPos->job_position))] ?? null;
                }
            }

            if ($approverPositionId && $approverPositionId !== $positionId) {
                MstPositionApproval::firstOrCreate(
                    ['position_id' => $positionId, 'approval_level' => 2],
                    ['approver_position_id' => $approverPositionId]
                );
                echo "  [L2] {$pattern->job_position} → {$pattern->department_head_name} (pos_id: {$approverPositionId})\n";
            } else {
                MstPositionApproval::firstOrCreate(
                    ['position_id' => $positionId, 'approval_level' => 2],
                    ['approver_position_id' => null]
                );
                echo "  [L2-NO_MAP] {$pattern->job_position} → {$pattern->department_head_name} (belum terpetakan)\n";
            }
        }

        // Approval Level 3: Div Head (opsional)
        if (!empty($pattern->div_head_name)) {
            $approverUser = User::whereRaw('UPPER(TRIM(name)) = ?', [strtoupper(trim($pattern->div_head_name))])->first();
            $approverPositionId = null;
            if ($approverUser) {
                $approverTcPos = DB::table('tc_job_positions')
                    ->where('id_user', $approverUser->id)
                    ->whereNotNull('job_position')
                    ->where('job_position', '!=', '')
                    ->first();
                if ($approverTcPos) {
                    $approverPositionId = $positionIdMap[strtolower(trim($approverTcPos->job_position))] ?? null;
                }
            }
            MstPositionApproval::firstOrCreate(
                ['position_id' => $positionId, 'approval_level' => 3],
                ['approver_position_id' => $approverPositionId]
            );
            echo "  [L3] {$pattern->job_position} → {$pattern->div_head_name}\n";
        }
    }

    echo "\n  Total rute approval: " . MstPositionApproval::count() . "\n\n";

    // ============================================================
    // STEP 4: Mapping User → user_job_positions
    // ============================================================
    echo "STEP 4: Mapping karyawan ke posisi baru...\n";

    $userMappings = DB::table('tc_job_positions')
        ->whereNotNull('id_user')
        ->whereNotNull('job_position')
        ->where('job_position', '!=', '')
        ->select('id_user', 'job_position', 'status')
        ->distinct()
        ->get();

    // Kumpulkan semua user_id yang valid
    $validUserIds = DB::table('users')->pluck('id')->flip()->all();

    foreach ($userMappings as $map) {
        if (!isset($validUserIds[$map->id_user])) {
            echo "  [SKIP] user_id {$map->id_user} tidak ditemukan di tabel users.\n";
            continue;
        }
        $posName = strtolower(trim($map->job_position));
        $positionId = $positionIdMap[$posName] ?? null;
        if (!$positionId || !$map->id_user) continue;

        UserJobPosition::firstOrCreate(
            ['user_id' => $map->id_user, 'mst_job_position_id' => $positionId],
            ['is_active' => ($map->status == 1)]
        );
    }

    echo "  ✓ Total mapping user-posisi: " . UserJobPosition::count() . "\n\n";

    DB::commit();
    echo "=== SELESAI. Semua data berhasil dimigrasikan! ===\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo "</pre>";
