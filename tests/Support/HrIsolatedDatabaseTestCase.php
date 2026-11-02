<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class HrIsolatedDatabaseTestCase extends TestCase
{
    private string $compiledViews;

    private ?string $fixtureDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compiledViews = sys_get_temp_dir().'/fastware-hr-views-'.bin2hex(random_bytes(12));
        mkdir($this->compiledViews, 0700);
        config(['view.compiled' => $this->compiledViews]);
        config(['logging.default' => 'null']);

        $connection = config('database.connections.mysql');
        if (! app()->environment('testing')
            || config('database.default') !== 'mysql'
            || $connection['database'] !== 'fastware_adsi_1_testing'
            || ! in_array($connection['host'], ['127.0.0.1', 'localhost', '::1'], true)
            || ! empty($connection['url']) || ! empty($connection['unix_socket'])
            || ! empty($connection['password'])) {
            throw new \LogicException('HR fixtures require the verified local testing connection; refusing other credentials or databases.');
        }
        $this->assertSame('fastware_adsi_1_testing', DB::selectOne('SELECT DATABASE() AS db')->db);

        $this->fixtureDatabase = 'fastware_adsi_1_hr_gate2_'.bin2hex(random_bytes(10)).'_testing';
        DB::statement('CREATE DATABASE `'.$this->fixtureDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        config(['database.connections.mysql.database' => $this->fixtureDatabase]);
        DB::purge('mysql');
        $this->assertSame($this->fixtureDatabase, DB::selectOne('SELECT DATABASE() AS db')->db);

        // Disposable random schema: legacy UNION queries cannot reopen MySQL
        // TEMPORARY tables. No existing schema/table is migrated, cleared, or seeded.
        $tables = [
            'users' => 'id bigint unsigned primary key auto_increment, name varchar(255), npk varchar(40), section varchar(255), role_id int null, is_active int default 0, password varchar(255) null, pass varchar(255) null, email varchar(255) null, fcm_token varchar(255) null, created_at timestamp null, updated_at timestamp null',
            'mst_departments' => 'id bigint unsigned primary key auto_increment, name varchar(255), is_active tinyint default 1, created_at timestamp null, updated_at timestamp null',
            'mst_sections' => 'id bigint unsigned primary key auto_increment, name varchar(255), department_id bigint unsigned null, is_active tinyint default 1, created_at timestamp null, updated_at timestamp null',
            'mst_job_positions' => 'id bigint unsigned primary key auto_increment, position_name varchar(255), department_id bigint unsigned null, section_id bigint unsigned null, job_level varchar(50) default "staff", is_active tinyint default 1, is_key_position tinyint default 0, created_at timestamp null, updated_at timestamp null',
            'user_job_positions' => 'id bigint unsigned primary key auto_increment, user_id bigint unsigned, mst_job_position_id bigint unsigned, is_active tinyint default 1, effective_from date null, effective_until date null, assignment_source varchar(64) default "test", created_at timestamp null, updated_at timestamp null',
            'mst_position_approvals' => 'id bigint unsigned primary key auto_increment, position_id bigint unsigned, approver_position_id bigint unsigned null, approval_level int, created_at timestamp null, updated_at timestamp null',
            'working_experiences' => 'id bigint unsigned primary key auto_increment, user_id bigint unsigned, year_start int, year_end int null, job_position varchar(255), section varchar(255) null, departemen varchar(255) null, keterangan text null, created_at timestamp null, updated_at timestamp null',
            'tc_poin_kategoris' => 'id int primary key auto_increment, judul_keterangan varchar(255), deskripsi_1 text null, deskripsi_2 text null, deskripsi_3 text null, deskripsi_4 text null, created_at timestamp null, updated_at timestamp null',
            'mst_tcs' => 'id bigint primary key auto_increment, id_job_position bigint unsigned, id_poin_kategori int null, keterangan_tc varchar(255), deskripsi_tc text null, nilai int null, created_at timestamp null, updated_at timestamp null',
            'mst_soft_skills' => 'id bigint primary key auto_increment, id_job_position bigint unsigned, id_poin_kategori int null, keterangan_sk varchar(255), deskripsi_sk text null, nilai int null, created_at timestamp null, updated_at timestamp null',
            'mst_additionals' => 'id bigint primary key auto_increment, id_job_position bigint unsigned, id_poin_kategori int null, keterangan_ad varchar(255), deskripsi_ad text null, nilai int null, created_at timestamp null, updated_at timestamp null',
            'trs_penilaian_tcs' => 'id bigint unsigned primary key auto_increment, id_user bigint unsigned, id_job_position bigint unsigned, id_tc bigint null, id_sk bigint null, id_ad bigint null, nilai_tc int null, nilai_sk int null, nilai_ad int null, total_nilai int null, status int default 0, tahun_penilaian int, is_locked tinyint default 0, modified_at int null, modified_updated varchar(255) null, created_at timestamp null, updated_at timestamp null',
            'detail_penilaian_tcs' => 'id bigint primary key auto_increment, id_job_position varchar(255), name varchar(255) null, keterangan_detail text null, keterangan_sebelum text null, nilai_sebelum text null, catatan text null, modified_at varchar(255) null, corrected_by_role varchar(50) null, created_at timestamp null, updated_at timestamp null',
            'km_organization_assignment_audits' => 'id bigint primary key auto_increment, user_job_position_id bigint unsigned null, actor_id bigint unsigned null, action varchar(50), before_state text null, after_state text null, reason text null, created_at timestamp null',
        ];
        foreach ($tables as $table => $definition) {
            DB::statement("CREATE TABLE `$table` ($definition) ENGINE=InnoDB");
        }

        // Test the real page template without unrelated global menu/database composers.
        app('events')->forget('composing: layout');
        view()->getFinder()->prependLocation(base_path('tests/Fixtures/hr/views'));
    }

    protected function tearDown(): void
    {
        if ($this->app && $this->app->bound('db')) {
            if ($this->fixtureDatabase !== null
                && preg_match('/\Afastware_adsi_1_hr_gate2_[a-f0-9]{20}_testing\z/', $this->fixtureDatabase)
                && config('database.connections.mysql.database') === $this->fixtureDatabase
                && DB::selectOne('SELECT DATABASE() AS db')->db === $this->fixtureDatabase) {
                DB::statement('DROP DATABASE `'.$this->fixtureDatabase.'`');
            }
            DB::purge('mysql');
        }
        if (isset($this->compiledViews)) {
            foreach (glob($this->compiledViews.'/*.php') ?: [] as $view) {
                unlink($view);
            }
            rmdir($this->compiledViews);
        }
        parent::tearDown();
    }

    protected function employee(string $name = 'Fixture employee'): \App\Models\User
    {
        return \App\Models\User::create(['name' => $name, 'npk' => '9000001', 'section' => 'Fixture section']);
    }

    protected function manager(): \App\Models\User
    {
        $user = new \App\Models\User(['name' => 'Fixture authorized manager']);
        $user->id = 91;
        $user->exists = true;
        $user->setRelation('roles', new \App\Models\Role(['role' => 'Fixture HR role']));

        return $user;
    }

    protected function position(string $level = 'staff'): \App\Models\MstJobPosition
    {
        return \App\Models\MstJobPosition::create(['position_name' => 'Fixture position '.uniqid(), 'job_level' => $level, 'is_active' => true]);
    }

    protected function assign(\App\Models\User $user, \App\Models\MstJobPosition $position, array $overrides = []): \App\Models\UserJobPosition
    {
        return \App\Models\UserJobPosition::create(array_merge([
            'user_id' => $user->id, 'mst_job_position_id' => $position->id,
            'is_active' => true, 'effective_from' => today()->subDay(),
        ], $overrides));
    }

    protected function assertInlineJavascriptParses(string $html, string $context): void
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        foreach ($document->getElementsByTagName('script') as $script) {
            if (! $script->hasAttribute('src') && trim($script->textContent) !== '') {
                $process = new \Symfony\Component\Process\Process(['node', '--check']);
                $process->setInput($script->textContent);
                $process->run();
                $this->assertSame(0, $process->getExitCode(), $context.': '.$process->getErrorOutput());
            }
        }
    }
}
