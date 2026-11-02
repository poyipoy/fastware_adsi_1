<?php

namespace App\Services\HR;

use App\Models\MstDepartment;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Services\KnowledgeManagement\KmOrganizationAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SalesRegionSplitService
{
    private const ACTION = 'sales_region_split_completed';

    private const SOURCE = 'sales_region_split_v1';

    private const DEPARTMENTS = ['Sales Region 1 & 2', 'Sales Region 3 & 4'];

    private const HEADS = ['Sales Dept Head Region 1&2', 'Sales Dept. Head Region 3&4'];

    private const TABLES = ['mst_departments', 'mst_sections', 'mst_job_positions', 'mst_position_approvals', 'user_job_positions'];

    public function __construct(
        private readonly JobPositionAccessService $access,
        private readonly KmOrganizationAssignmentService $assignments,
    ) {
    }

    /** Preview contains aggregate counts and reviewed state hash, never employee identities. */
    public function preview(): array
    {
        if ($completed = $this->completed()) {
            return $completed;
        }

        return $this->inspect()['preview'];
    }

    public function apply(User $actor, string $reviewedHash): array
    {
        if (! User::query()->whereKey($actor->getKey())->exists() || ! $this->access->canManageMapping($actor)) {
            throw new AuthorizationException('Actor tidak memiliki akses mengelola mapping HR.');
        }
        if (! preg_match('/\A[a-f0-9]{64}\z/', $reviewedHash)) {
            throw new RuntimeException('Hash preview yang sudah direview wajib diberikan.');
        }

        return DB::transaction(function () use ($actor, $reviewedHash): array {
            // Same lock order as Master Department: every department, then sections/jobs.
            foreach (['mst_departments', 'mst_sections', 'mst_job_positions', 'mst_position_approvals', 'user_job_positions'] as $table) {
                DB::table($table)->orderBy('id')->lockForUpdate()->get(['id']);
            }
            if ($completed = $this->completed()) {
                return $completed;
            }
            $plan = $this->inspect();
            if (! hash_equals($plan['preview']['hash'], $reviewedHash)) {
                throw new RuntimeException('Data berubah sejak preview; jalankan preview dan review ulang.');
            }
            if ($plan['preview']['blockers'] !== []) {
                throw new RuntimeException('Cutover diblokir: '.implode(' ', $plan['preview']['blockers']));
            }

            $snapshot = $this->snapshot($plan, $actor, $reviewedHash);
            $departmentIds = $headIds = [];
            foreach (range(1, 4) as $region) {
                $department = MstDepartment::create(['name' => 'Sales Region '.$region, 'is_active' => true]);
                $departmentIds[$region] = (int) $department->id;
                $head = MstJobPosition::create([
                    'position_name' => 'Sales Dept Head Region '.$region,
                    'department_id' => $department->id, 'section_id' => null,
                    'job_level' => 'dept_head', 'is_active' => true, 'is_key_position' => true,
                ]);
                $headIds[$region] = (int) $head->id;
                DB::table('mst_sections')->where('id', $plan['sections'][$region]['id'])
                    ->update(['department_id' => $department->id, 'updated_at' => now()]);
                DB::table('mst_job_positions')->where('section_id', $plan['sections'][$region]['id'])
                    ->update(['department_id' => $department->id, 'updated_at' => now()]);
            }
            foreach ($plan['incoming'] as $routeId => $region) {
                DB::table('mst_position_approvals')->where('id', $routeId)
                    ->update(['approver_position_id' => $headIds[$region], 'updated_at' => now()]);
            }
            foreach (range(1, 4) as $region) {
                $sourceIndex = in_array($region, [1, 3], true) ? 0 : 1;
                foreach ($plan['outgoing'][$sourceIndex] as $route) {
                    if ((int) $route['approval_level'] !== 3) {
                        DB::table('mst_position_approvals')->insert([
                            'position_id' => $headIds[$region], 'approval_level' => $route['approval_level'],
                            'approver_position_id' => $route['approver_position_id'], 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
                DB::table('mst_position_approvals')->insert([
                    'position_id' => $headIds[$region], 'approval_level' => 3,
                    'approver_position_id' => $plan['division']['id'], 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($plan['incumbents'][$sourceIndex] as $assignment) {
                    $this->assignments->create($actor, [
                        'user_id' => $assignment['user_id'], 'mst_job_position_id' => $headIds[$region],
                        'is_active' => true, 'effective_from' => today()->toDateString(),
                        'effective_until' => null, 'assignment_source' => self::SOURCE,
                    ], 'Pemisahan department Sales menjadi empat region.');
                }
            }
            foreach ($plan['incumbents'] as $assignments) {
                foreach ($assignments as $assignment) {
                    $this->assignments->update($actor, UserJobPosition::findOrFail($assignment['id']), [
                        'is_active' => false, 'effective_until' => today()->subDay()->toDateString(),
                    ], 'Penutupan posisi kepala department Sales gabungan.');
                }
            }
            MstJobPosition::whereIn('id', array_column($plan['heads'], 'id'))->update(['is_active' => false]);
            MstDepartment::whereIn('id', array_column($plan['departments'], 'id'))->update(['is_active' => false]);
            DB::table('km_organization_assignment_audits')->insert([
                'user_job_position_id' => null, 'actor_id' => $actor->id, 'action' => self::ACTION,
                'before_state' => null,
                'after_state' => json_encode(['version' => self::SOURCE, 'hash' => $reviewedHash, 'snapshot' => $snapshot,
                    'department_ids' => $departmentIds, 'head_ids' => $headIds], JSON_THROW_ON_ERROR),
                'reason' => 'Cutover Sales selesai; eksekusi ulang tidak mengubah perubahan HR berikutnya.', 'created_at' => now(),
            ]);

            return ['status' => 'applied', 'hash' => $reviewedHash, 'snapshot' => $snapshot];
        });
    }

    private function completed(): ?array
    {
        if (! Schema::hasTable('km_organization_assignment_audits')) {
            return null;
        }
        $row = DB::table('km_organization_assignment_audits')->where('action', self::ACTION)->first();
        if (! $row) {
            return null;
        }
        $state = json_decode($row->after_state, true, 512, JSON_THROW_ON_ERROR);
        if (($state['version'] ?? null) !== self::SOURCE || count($state['head_ids'] ?? []) !== 4) {
            throw new RuntimeException('Jurnal cutover tidak valid; perlu pemeriksaan manual.');
        }

        return ['status' => 'completed', 'hash' => $state['hash'], 'blockers' => [], 'summary' => ['departments' => 4]];
    }

    private function inspect(): array
    {
        $state = [];
        foreach (self::TABLES as $table) {
            $state[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }
        $blockers = [];
        $departments = $heads = $sections = $incumbents = $outgoing = $incoming = [];
        $one = static function (array $rows, string $column, string $name) use (&$blockers): array {
            $matches = array_values(array_filter($rows, fn ($row) => $row[$column] === $name));
            if (count($matches) !== 1) {
                $blockers[] = 'Master harus tepat satu: '.$name.'.';
            }

            return $matches[0] ?? [];
        };
        foreach (self::DEPARTMENTS as $index => $name) {
            $departments[$index] = $one($state['mst_departments'], 'name', $name);
            $heads[$index] = $one($state['mst_job_positions'], 'position_name', self::HEADS[$index]);
            if (empty($departments[$index]['is_active']) || empty($heads[$index]['is_active'])
                || ($heads[$index]['job_level'] ?? '') !== 'dept_head'
                || ($heads[$index]['section_id'] ?? null) !== null
                || ($heads[$index]['department_id'] ?? null) !== ($departments[$index]['id'] ?? null)) {
                $blockers[] = 'Department/kepala sumber tidak aktif atau tidak konsisten.';
            }
        }
        $division = $one($state['mst_job_positions'], 'position_name', 'Sales Div Head');
        if (empty($division['is_active']) || ($division['job_level'] ?? '') !== 'div_head') {
            $blockers[] = 'Sales Div Head harus aktif pada level div_head.';
        }
        $headIds = array_column($heads, 'id');
        $departmentIds = array_column($departments, 'id');
        $regionBySection = [];
        foreach (range(1, 4) as $region) {
            $name = 'Sales Region '.$region;
            $sections[$region] = $one($state['mst_sections'], 'name', $name);
            if (($sections[$region]['department_id'] ?? null) !== ($departments[$region <= 2 ? 0 : 1]['id'] ?? null)) {
                $blockers[] = 'Section region berada di luar department sumber: '.$name.'.';
            }
            if (isset($sections[$region]['id'])) {
                $regionBySection[$sections[$region]['id']] = $region;
            }
            if (collect($state['mst_departments'])->contains('name', $name)
                || collect($state['mst_job_positions'])->contains('position_name', 'Sales Dept Head Region '.$region)) {
                $blockers[] = 'Master tujuan sudah ada tanpa jurnal selesai: '.$name.'.';
            }
        }
        foreach ($state['mst_sections'] as $section) {
            if (in_array($section['department_id'], $departmentIds, true) && ! isset($regionBySection[$section['id']])) {
                $blockers[] = 'Terdapat section sumber di luar empat region.';
            }
        }
        $positions = array_column($state['mst_job_positions'], null, 'id');
        foreach ($positions as $position) {
            if (isset($regionBySection[$position['section_id'] ?? 0])) {
                $region = $regionBySection[$position['section_id']];
                if ($position['department_id'] !== ($departments[$region <= 2 ? 0 : 1]['id'] ?? null)) {
                    $blockers[] = 'Department posisi tidak cocok dengan department section sumber.';
                }
            } elseif (in_array($position['department_id'], $departmentIds, true) && ! in_array($position['id'], $headIds, true)) {
                $blockers[] = 'Terdapat posisi sumber tanpa section regional yang jelas.';
            }
        }
        $slots = [];
        foreach ($state['mst_position_approvals'] as $route) {
            if (in_array($route['position_id'], $headIds, true) || in_array($route['approver_position_id'], $headIds, true)) {
                $slot = $route['position_id'].':'.$route['approval_level'];
                if (isset($slots[$slot]) || ! in_array((int) $route['approval_level'], [0, 1, 2, 3], true)) {
                    $blockers[] = 'Approval sumber duplikat atau level tidak dikenal.';
                }
                $slots[$slot] = true;
            }
            if (in_array($route['approver_position_id'], $headIds, true)) {
                $child = $positions[$route['position_id']] ?? [];
                $region = $regionBySection[$child['section_id'] ?? 0] ?? null;
                if ($region === null || in_array($route['position_id'], $headIds, true)) {
                    $blockers[] = 'Incoming approval kepala lama tidak dapat dipetakan ke section regional.';
                } else {
                    $incoming[$route['id']] = $region;
                }
            }
        }
        foreach ($heads as $index => $head) {
            $outgoing[$index] = array_values(array_filter($state['mst_position_approvals'], fn ($route) => $route['position_id'] === ($head['id'] ?? null)));
            foreach ($outgoing[$index] as $route) {
                if ($route['approver_position_id'] !== null && empty($positions[$route['approver_position_id']]['is_active'])) {
                    $blockers[] = 'Approver sumber hilang atau tidak aktif.';
                }
                if ((int) $route['approval_level'] === 3 && $route['approver_position_id'] !== ($division['id'] ?? null)) {
                    $blockers[] = 'Rute divisi kepala sumber berbeda dari Sales Div Head.';
                }
            }
            $incumbents[$index] = array_values(array_filter($state['user_job_positions'], fn ($assignment) => $assignment['mst_job_position_id'] === ($head['id'] ?? null) && $assignment['is_active']));
            if ($incumbents[$index] === []) {
                $blockers[] = 'Kepala sumber tidak memiliki incumbent aktif.';
            }
            $seen = [];
            foreach ($incumbents[$index] as $assignment) {
                if (isset($seen[$assignment['user_id']]) || empty($assignment['effective_from'])
                    || $assignment['effective_from'] >= today()->toDateString()
                    || ($assignment['effective_until'] !== null && $assignment['effective_until'] < today()->toDateString())
                    || ! User::whereKey($assignment['user_id'])->exists()) {
                    $blockers[] = 'Assignment incumbent ambigu, tidak valid, atau belum dapat ditutup pada kemarin.';
                }
                $seen[$assignment['user_id']] = true;
            }
        }
        foreach (['km_document_version_departments' => ['department_id', $departmentIds],
            'km_document_version_job_positions' => ['job_position_id', $headIds]] as $table => [$column, $ids]) {
            if (Schema::hasTable($table)) {
                $state[$table] = DB::table($table)->whereIn($column, $ids)->orderBy('document_version_id')->orderBy($column)
                    ->get()->map(fn ($row) => (array) $row)->all();
                if ($state[$table] !== []) {
                    $blockers[] = 'Target KM sumber memerlukan keputusan sebelum cutover: '.$table.'.';
                }
            }
        }
        if (! Schema::hasColumns('user_job_positions', ['effective_from', 'effective_until', 'assignment_source'])
            || ! Schema::hasTable('km_organization_assignment_audits')) {
            $blockers[] = 'Schema assignment efektif/audit belum tersedia.';
        }
        $blockers = array_values(array_unique($blockers));
        $hash = hash('sha256', json_encode(['version' => self::SOURCE, 'date' => today()->toDateString(), 'state' => $state], JSON_THROW_ON_ERROR));
        $regions = [];
        foreach (range(1, 4) as $region) {
            $sourceIndex = in_array($region, [1, 3], true) ? 0 : 1;
            $regions[] = ['region' => $region, 'section_id' => $sections[$region]['id'] ?? null,
                'section' => 'Sales Region '.$region, 'destination' => 'Sales Region '.$region,
                'new_head' => 'Sales Dept Head Region '.$region, 'incumbent_source' => self::HEADS[$sourceIndex],
                'incumbent_count' => count($incumbents[$sourceIndex]),
                'position_ids' => array_values(array_map(fn ($position) => $position['id'], array_filter($positions,
                    fn ($position) => ($position['section_id'] ?? null) === ($sections[$region]['id'] ?? null))))];
        }
        $approvalChanges = [];
        foreach ($state['mst_position_approvals'] as $route) {
            if (isset($incoming[$route['id']])) {
                $approvalChanges[] = ['id' => $route['id'], 'position' => $positions[$route['position_id']]['position_name'],
                    'level' => $route['approval_level'], 'from' => $positions[$route['approver_position_id']]['position_name'],
                    'to' => 'Sales Dept Head Region '.$incoming[$route['id']]];
            }
        }
        $preview = ['status' => $blockers === [] ? 'ready' : 'blocked', 'hash' => $hash, 'blockers' => $blockers,
            'regions' => $regions, 'approval_changes' => $approvalChanges,
            'summary' => ['departments_to_create' => 4, 'heads_to_create' => 4, 'sections_to_move' => count($regionBySection),
                'approval_references_to_update' => count($incoming), 'source_assignments_to_close' => array_sum(array_map('count', $incumbents)),
                'competency_standards_copied' => 0, 'historical_rows_rewritten' => 0]];

        return compact('state', 'preview', 'departments', 'heads', 'sections', 'incumbents', 'outgoing', 'incoming', 'division');
    }

    private function snapshot(array $plan, User $actor, string $hash): string
    {
        $state = $plan['state'];
        $ids = array_column($plan['heads'], 'id');
        foreach (['mst_tcs', 'mst_soft_skills', 'mst_additionals', 'trs_penilaian_tcs', 'detail_penilaian_tcs', 'mst_pd_pengajuans'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'id_job_position')) {
                $state[$table] = DB::table($table)->whereIn('id_job_position', $ids)->orderBy('id')->get()->toArray();
            }
        }
        $path = 'private/hr/sales-region-split/'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(10)).'.json';
        $payload = json_encode(['version' => self::SOURCE, 'reviewed_hash' => $hash, 'actor_id' => $actor->id,
            'created_at' => now()->toIso8601String(), 'database' => DB::connection()->getDatabaseName(), 'tables' => $state], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        if (! Storage::disk('local')->put($path, $payload)) {
            throw new RuntimeException('Snapshot private gagal disimpan; cutover dibatalkan.');
        }

        return $path;
    }
}
