<?php

namespace Tests\Feature\HR;

use App\Models\MstDepartment;
use App\Models\MstJobPosition;
use App\Models\MstSection;
use App\Models\User;
use App\Services\HR\SalesRegionSplitService;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\HrIsolatedDatabaseTestCase;

class SalesDepartmentPackageSqlTest extends HrIsolatedDatabaseTestCase
{
    private function organization(): array
    {
        foreach (['users' => 9001, 'mst_departments' => 2001, 'mst_sections' => 3001, 'mst_job_positions' => 4001] as $table => $start) {
            DB::statement("ALTER TABLE `$table` AUTO_INCREMENT=$start");
        }
        DB::statement('ALTER TABLE mst_departments ADD UNIQUE department_unique(name)');
        DB::statement('ALTER TABLE mst_sections ADD UNIQUE section_unique(department_id,name), ADD FOREIGN KEY(department_id) REFERENCES mst_departments(id)');
        DB::statement('ALTER TABLE mst_job_positions ADD UNIQUE position_unique(position_name), ADD FOREIGN KEY(department_id) REFERENCES mst_departments(id), ADD FOREIGN KEY(section_id) REFERENCES mst_sections(id)');
        DB::statement('ALTER TABLE mst_position_approvals ADD UNIQUE route_unique(position_id,approval_level), ADD FOREIGN KEY(position_id) REFERENCES mst_job_positions(id), ADD FOREIGN KEY(approver_position_id) REFERENCES mst_job_positions(id)');
        DB::statement('ALTER TABLE user_job_positions ADD FOREIGN KEY(user_id) REFERENCES users(id), ADD FOREIGN KEY(mst_job_position_id) REFERENCES mst_job_positions(id)');
        DB::statement('DROP TABLE km_organization_assignment_audits');
        DB::statement('CREATE TABLE mst_pd_pengajuans (id bigint unsigned primary key auto_increment,id_job_position bigint unsigned,program_training varchar(100)) ENGINE=InnoDB');
        $actor = User::create(['name' => 'SITI MARIA ULFA', 'is_active' => 0]);
        $first = MstDepartment::create(['name' => 'Sales Region 1 & 2', 'is_active' => true]);
        $second = MstDepartment::create(['name' => 'Sales Region 3 & 4', 'is_active' => true]);
        $division = MstJobPosition::create(['position_name' => 'Sales Div Head', 'job_level' => 'div_head', 'is_active' => true]);
        $heads = [];
        foreach (['Sales Dept Head Region 1&2', 'Sales Dept. Head Region 3&4'] as $index => $name) {
            $heads[] = MstJobPosition::create(['position_name' => $name, 'department_id' => $index ? $second->id : $first->id,
                'job_level' => 'dept_head', 'is_active' => true, 'is_key_position' => true]);
        }
        $incumbents = [$this->employee('Fixture incumbent A'), $this->employee('Fixture incumbent B')];
        foreach ($heads as $index => $head) {
            $this->assign($incumbents[$index], $head, ['effective_from' => today()->subYear()]);
            DB::table('mst_position_approvals')->insert(['position_id' => $head->id, 'approval_level' => 3, 'approver_position_id' => $division->id]);
        }
        $children = [];
        foreach (range(1, 4) as $region) {
            $department = $region <= 2 ? $first : $second;
            $section = MstSection::create(['name' => 'Sales Region '.$region, 'department_id' => $department->id, 'is_active' => true]);
            $children[$region] = MstJobPosition::create(['position_name' => 'Fixture staff '.$region, 'department_id' => $department->id,
                'section_id' => $section->id, 'is_active' => true]);
            foreach ([1, 2] as $level) {
                DB::table('mst_position_approvals')->insert(['position_id' => $children[$region]->id, 'approval_level' => $level,
                    'approver_position_id' => $heads[$region <= 2 ? 0 : 1]->id]);
            }
            DB::table('mst_position_approvals')->insert(['position_id' => $children[$region]->id, 'approval_level' => 3, 'approver_position_id' => $division->id]);
        }
        $inactive = MstJobPosition::create(['position_name' => 'Fixture archived staff', 'department_id' => $first->id,
            'section_id' => $children[1]->section_id, 'is_active' => false]);
        foreach (['mst_tcs', 'mst_soft_skills', 'mst_additionals'] as $table) {
            DB::table($table)->insert(['id_job_position' => $heads[0]->id, 'nilai' => 3]);
        }
        DB::table('trs_penilaian_tcs')->insert(['id_user' => $incumbents[0]->id, 'id_job_position' => $heads[0]->id, 'tahun_penilaian' => 2025, 'nilai_tc' => 4]);
        DB::table('mst_pd_pengajuans')->insert(['id_job_position' => $heads[0]->id, 'program_training' => 'Fixture historical training']);
        DB::table('detail_penilaian_tcs')->insert(['id_job_position' => $heads[0]->id, 'catatan' => 'Fixture correction history']);
        DB::table('working_experiences')->insert(['user_id' => $incumbents[0]->id, 'year_start' => 2025, 'job_position' => $heads[0]->position_name]);

        return compact('actor', 'heads', 'children', 'division', 'incumbents', 'inactive');
    }

    private function sql(string $file): string
    {
        if ($file === 'split_sales_regions_only' && getenv('SALES_PACKAGE_VERIFY_BUNDLE') !== '1') {
            $process = new \Symfony\Component\Process\Process(['python', base_path('scripts/department-sales-release/build_package.py'), '--print-standalone-sql']);
            $process->mustRun();

            return $process->getOutput();
        }
        $directory = getenv('SALES_PACKAGE_VERIFY_BUNDLE') === '1'
            ? 'deploy-department-sales/SALES-DEPARTMENT-20261026/database/sql/sales-region-split'
            : 'scripts/department-sales-release/sql';

        return file_get_contents(base_path($directory.'/'.$file.'.sql'));
    }

    private function execute(string $sql, ?PDO $pdo = null): array
    {
        $pdo ??= DB::connection()->getPdo();
        $delimiter = ';';
        $buffer = '';
        $results = [];
        foreach (preg_split('/\R/', $sql) as $line) {
            if (preg_match('/^\s*--/', $line) || trim($line) === '') {
                continue;
            }
            if (preg_match('/^DELIMITER\s+(\S+)$/i', trim($line), $match)) {
                $this->assertSame('', trim($buffer), 'Incomplete SQL before delimiter');
                $delimiter = $match[1];

                continue;
            }
            $buffer .= $line."\n";
            if (str_ends_with(rtrim($buffer), $delimiter)) {
                $statement = substr(rtrim($buffer), 0, -strlen($delimiter));
                $query = $pdo->query($statement);
                do {
                    if ($query->columnCount() > 0) {
                        $results[] = $query->fetchAll(PDO::FETCH_ASSOC);
                    }
                } while ($query->nextRowset());
                $query->closeCursor();
                $buffer = '';
            }
        }
        $this->assertSame('', trim($buffer), 'Incomplete SQL at end of file');

        return $results;
    }

    private function fingerprints(array $tables): array
    {
        return collect($tables)->mapWithKeys(fn ($table) => [$table => hash('sha256', DB::table($table)->orderBy('id')->get()->toJson())])->all();
    }

    private function ready(): void
    {
        $this->execute($this->sql('00_preflight'));
        $this->execute($this->sql('01_prerequisites'));
    }

    public static function splitImplementations(): array
    {
        return [['sql'], ['artisan']];
    }

    #[DataProvider('splitImplementations')]
    public function test_split_with_different_ids_preserves_history_and_has_same_sql_and_artisan_contract(string $implementation): void
    {
        $fixture = $this->organization();
        $historyTables = ['mst_tcs', 'mst_soft_skills', 'mst_additionals', 'trs_penilaian_tcs', 'detail_penilaian_tcs', 'mst_pd_pengajuans', 'working_experiences'];
        $history = $this->fingerprints($historyTables);
        $before = $this->fingerprints(['mst_departments', 'mst_job_positions', 'user_job_positions']);
        $this->execute($this->sql('00_preflight'));
        $this->assertSame($before, $this->fingerprints(array_keys($before)));
        $this->execute($this->sql('01_prerequisites'));
        if ($implementation === 'sql') {
            $this->execute($this->sql('02_split_sales_regions'));
        } else {
            \Illuminate\Support\Facades\Storage::fake('local');
            app(SalesRegionSplitService::class)->apply($fixture['actor'], app(SalesRegionSplitService::class)->preview()['hash']);
        }
        foreach (range(1, 4) as $region) {
            $head = MstJobPosition::where('position_name', 'Sales Dept Head Region '.$region)->firstOrFail();
            $this->assertSame($head->department_id, $fixture['children'][$region]->fresh()->department_id);
            $this->assertDatabaseHas('user_job_positions', ['mst_job_position_id' => $head->id, 'user_id' => $fixture['incumbents'][in_array($region, [1, 3]) ? 0 : 1]->id,
                'is_active' => 1, 'assignment_source' => 'sales_region_split_v1']);
            foreach ([1, 2] as $level) {
                $this->assertDatabaseHas('mst_position_approvals', ['position_id' => $fixture['children'][$region]->id, 'approval_level' => $level, 'approver_position_id' => $head->id]);
            }
            $this->assertDatabaseHas('mst_position_approvals', ['position_id' => $head->id, 'approval_level' => 3, 'approver_position_id' => $fixture['division']->id]);
            foreach (['mst_tcs', 'mst_soft_skills', 'mst_additionals'] as $table) {
                $this->assertSame(0, DB::table($table)->where('id_job_position', $head->id)->count());
            }
        }
        $this->assertSame($fixture['children'][1]->fresh()->department_id, $fixture['inactive']->fresh()->department_id);
        $this->assertFalse($fixture['inactive']->fresh()->is_active);
        $this->assertSame($history, $this->fingerprints($historyTables));
        $journal = DB::table('km_organization_assignment_audits')->where('action', 'sales_region_split_completed')->first();
        $this->assertSame($fixture['actor']->id, $journal->actor_id);
        if ($implementation === 'sql') {
            $this->assertNotEmpty(json_decode($journal->before_state, true)['assignments']);
        }
        $this->assertDatabaseCount('km_organization_assignment_audits', 7);
        $this->assertSame('completed', app(SalesRegionSplitService::class)->preview()['status']);
        foreach ([[0, [1, 3]], [1, [2, 4]]] as [$index, $regions]) {
            $expectedDepartments = MstDepartment::whereIn('name', array_map(fn ($region) => 'Sales Region '.$region, $regions))->pluck('id')->map('intval')->sort()->values()->all();
            $this->assertSame($expectedDepartments, app(\App\Services\HR\TcpdDashboardAccessService::class)->scope($fixture['incumbents'][$index])['department_ids']);
        }
        $this->execute($this->sql('03_postcheck'));
        $this->assertSame(0, DB::table('information_schema.ROUTINES')->where('ROUTINE_SCHEMA', DB::connection()->getDatabaseName())->where('ROUTINE_NAME', 'like', 'sales_dept_20261026_%')->count());
    }

    public function test_reimport_does_not_reset_later_hr_changes(): void
    {
        $this->organization();
        $this->ready();
        $this->execute($this->sql('02_split_sales_regions'));
        $head = MstJobPosition::where('position_name', 'Sales Dept Head Region 1')->firstOrFail();
        $head->update(['position_name' => 'Fixture HR changed head']);
        DB::table('user_job_positions')->where('mst_job_position_id', $head->id)->update(['is_active' => 0]);
        $before = $this->fingerprints(['mst_departments', 'mst_job_positions', 'user_job_positions', 'mst_position_approvals', 'km_organization_assignment_audits']);
        foreach (['00_preflight', '01_prerequisites', '02_split_sales_regions', '03_postcheck'] as $file) {
            $this->execute($this->sql($file));
        }
        $this->assertSame($before, $this->fingerprints(array_keys($before)));
    }

    public function test_missing_schema_conflicts_and_km_targets_are_rejected_without_data_writes(): void
    {
        $fixture = $this->organization();
        $target = MstDepartment::create(['name' => 'Sales Region 1']);
        $before = $this->fingerprints(['mst_departments', 'mst_job_positions', 'user_job_positions']);
        try {
            $this->execute($this->sql('00_preflight'));
            $this->fail('Accepted conflicting destination');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('tujuan sudah ada', $exception->getMessage());
        }
        $this->assertSame($before, $this->fingerprints(array_keys($before)));
        $target->delete();
        DB::statement('CREATE TABLE km_document_version_departments (department_id bigint unsigned) ENGINE=InnoDB');
        DB::table('km_document_version_departments')->insert(['department_id' => $fixture['heads'][0]->department_id]);
        try {
            $this->execute($this->sql('00_preflight'));
            $this->fail('Accepted existing KM target');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('KM lama', $exception->getMessage());
        }
        DB::statement('DROP TABLE km_document_version_departments');
        DB::statement('ALTER TABLE user_job_positions DROP COLUMN effective_until');
        $this->expectException(PDOException::class);
        $this->execute($this->sql('00_preflight'));
    }

    public function test_late_sql_failure_rolls_back_all_data_and_audits(): void
    {
        $this->organization();
        $this->ready();
        $tables = ['mst_departments', 'mst_sections', 'mst_job_positions', 'user_job_positions', 'mst_position_approvals', 'km_organization_assignment_audits'];
        $before = $this->fingerprints($tables);
        $sql = str_replace('UPDATE mst_job_positions SET is_active=0,updated_at=v_now WHERE id IN (v_h12,v_h34);',
            "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fixture late failure';", $this->sql('02_split_sales_regions'), $count);
        $this->assertSame(1, $count);
        try {
            $this->execute($sql);
            $this->fail('Expected rollback');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('Fixture late failure', $exception->getMessage());
        }
        $this->assertSame($before, $this->fingerprints($tables));
    }

    public function test_incompatible_audit_and_ambiguous_actor_stop_before_cutover(): void
    {
        $this->organization();
        $this->ready();
        DB::statement('ALTER TABLE km_organization_assignment_audits MODIFY action varchar(8) NOT NULL');
        try {
            $this->execute($this->sql('00_preflight'));
            $this->fail('Accepted incompatible audit type');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('kolom audit', $exception->getMessage());
        }
        DB::statement('ALTER TABLE km_organization_assignment_audits MODIFY action varchar(32) NOT NULL');
        User::create(['name' => 'SITI MARIA ULFA']);
        try {
            $this->execute($this->sql('00_preflight'));
            $this->fail('Accepted ambiguous audit actor');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('Actor SITI', $exception->getMessage());
        }
        $this->assertDatabaseCount('mst_departments', 2);
        $this->assertDatabaseCount('km_organization_assignment_audits', 0);
    }

    public function test_sql_waits_for_the_same_department_lock_as_application_writers(): void
    {
        $this->organization();
        $this->ready();
        $config = config('database.connections.mysql');
        $pdo = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'].';charset=utf8mb4', $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('SET SESSION innodb_lock_wait_timeout=1');
        DB::beginTransaction();
        try {
            app(\App\Services\HR\DepartmentStructureService::class)->lockOrganization();
            try {
                $this->execute($this->sql('02_split_sales_regions'), $pdo);
                $this->fail('SQL bypassed the organization writer lock');
            } catch (PDOException $exception) {
                $this->assertSame(1205, $exception->errorInfo[1]);
            }
        } finally {
            DB::rollBack();
        }
        $this->assertDatabaseCount('mst_departments', 2);
        $this->assertDatabaseCount('km_organization_assignment_audits', 0);
    }

    public function test_standalone_splits_without_existing_helpers_and_reimport_preserves_hr_changes(): void
    {
        $fixture = $this->organization();
        $this->ready(); // Fixture preparation only: provide the audit table required on the target server.
        foreach (['validate', 'prerequisites', 'apply'] as $name) {
            DB::statement('DROP PROCEDURE IF EXISTS sales_dept_20261026_'.$name);
        }
        $historyTables = ['mst_tcs', 'mst_soft_skills', 'mst_additionals', 'trs_penilaian_tcs', 'detail_penilaian_tcs', 'mst_pd_pengajuans', 'working_experiences'];
        $history = $this->fingerprints($historyTables);
        $tables = DB::select('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME');
        $this->execute($this->sql('split_sales_regions_only'));
        $this->assertSame($history, $this->fingerprints($historyTables));
        $this->assertEquals($tables, DB::select('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME'));
        foreach (range(1, 4) as $region) {
            $head = MstJobPosition::where('position_name', 'Sales Dept Head Region '.$region)->firstOrFail();
            $this->assertSame($head->department_id, $fixture['children'][$region]->fresh()->department_id);
            $this->assertDatabaseHas('user_job_positions', ['mst_job_position_id' => $head->id,
                'user_id' => $fixture['incumbents'][in_array($region, [1, 3]) ? 0 : 1]->id, 'is_active' => 1]);
            foreach ([1, 2] as $level) {
                $this->assertDatabaseHas('mst_position_approvals', ['position_id' => $fixture['children'][$region]->id,
                    'approval_level' => $level, 'approver_position_id' => $head->id]);
            }
        }
        $this->assertSame(0, DB::table('information_schema.ROUTINES')->where('ROUTINE_SCHEMA', DB::connection()->getDatabaseName())->where('ROUTINE_NAME', 'like', 'sales_dept_20261026_%')->count());
        $head->update(['position_name' => 'HR changed after standalone import']);
        DB::table('user_job_positions')->where('mst_job_position_id', $head->id)->update(['is_active' => 0]);
        $protected = $this->fingerprints(['mst_departments', 'mst_sections', 'mst_job_positions', 'user_job_positions', 'mst_position_approvals', 'km_organization_assignment_audits']);
        $this->execute($this->sql('split_sales_regions_only'));
        $this->assertSame($protected, $this->fingerprints(array_keys($protected)));
    }

    public function test_standalone_missing_audit_or_other_prerequisites_never_creates_tables_or_writes_data(): void
    {
        $this->organization(); // Audit table deliberately absent.
        $protected = $this->fingerprints(['mst_departments', 'mst_sections', 'mst_job_positions', 'user_job_positions', 'mst_position_approvals']);
        try {
            $this->execute($this->sql('split_sales_regions_only'));
            $this->fail('Standalone accepted a missing audit table');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('Tabel audit belum tersedia', $exception->getMessage());
        }
        $this->assertSame(0, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())->where('TABLE_NAME', 'km_organization_assignment_audits')->count());
        $this->assertSame($protected, $this->fingerprints(array_keys($protected)));
        DB::statement('ALTER TABLE user_job_positions DROP COLUMN effective_until');
        $before = $this->fingerprints(array_keys($protected));
        try {
            $this->execute($this->sql('split_sales_regions_only'));
            $this->fail('Standalone accepted missing assignment columns');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('Prasyarat HR/kolom', $exception->getMessage());
        }
        $this->assertSame($before, $this->fingerprints(array_keys($before)));
    }

    public function test_standalone_late_failure_rolls_back_data_and_preserves_audit_table(): void
    {
        $this->organization();
        $this->ready();
        $tables = ['mst_departments', 'mst_sections', 'mst_job_positions', 'user_job_positions', 'mst_position_approvals', 'km_organization_assignment_audits'];
        $before = $this->fingerprints($tables);
        $sql = str_replace('UPDATE mst_job_positions SET is_active=0,updated_at=v_now WHERE id IN (v_h12,v_h34);',
            "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fixture standalone late failure';", $this->sql('split_sales_regions_only'), $count);
        $this->assertSame(1, $count);
        try {
            $this->execute($sql);
            $this->fail('Standalone failed to roll back');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('Fixture standalone late failure', $exception->getMessage());
        }
        $this->assertSame($before, $this->fingerprints($tables));
    }
}
