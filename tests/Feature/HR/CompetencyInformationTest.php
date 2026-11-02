<?php

namespace Tests\Feature\HR;

use App\Models\MstJobPosition;
use App\Models\PoinKategori;
use App\Models\TrsPenilaianTc;
use Illuminate\Support\Facades\DB;
use Tests\Support\HrIsolatedDatabaseTestCase;

class CompetencyInformationTest extends HrIsolatedDatabaseTestCase
{
    private function definitions(MstJobPosition $position): array
    {
        $category = PoinKategori::create([
            'judul_keterangan' => 'Fixture category', 'deskripsi_1' => 'Guide one',
            'deskripsi_2' => 'Guide two', 'deskripsi_3' => 'Guide three', 'deskripsi_4' => 'Guide four',
        ]);
        foreach (['Fixture second rubric', 'Fixture third rubric'] as $title) {
            PoinKategori::create(['judul_keterangan' => $title, 'deskripsi_1' => 'One', 'deskripsi_2' => 'Two', 'deskripsi_3' => 'Three', 'deskripsi_4' => 'Four']);
        }
        $result = [];
        foreach ([['mst_tcs', 'tc'], ['mst_soft_skills', 'sk'], ['mst_additionals', 'ad']] as [$table, $type]) {
            $result[$type] = DB::table($table)->insertGetId([
                'id_job_position' => $position->id, 'id_poin_kategori' => $category->id,
                'keterangan_'.$type => 'Same competency name',
                'deskripsi_'.$type => $type === 'ad' ? null : 'Specific '.$type.' <img src=x onerror="alert(1)">',
                'nilai' => 3,
            ]);
        }
        $result['second_tc'] = DB::table('mst_tcs')->insertGetId([
            'id_job_position' => $position->id, 'id_poin_kategori' => $category->id,
            'keterangan_tc' => 'Same competency name', 'deskripsi_tc' => 'Different definition', 'nilai' => 4,
        ]);

        return $result;
    }

    public function test_metadata_uses_real_description_columns_and_category_rubric_without_tier_columns(): void
    {
        $position = $this->position();
        $ids = $this->definitions($position);
        $this->actingAs($this->manager())->getJson(route('job.positions.details2', $position->id))->assertOk()
            ->assertJsonCount(2, 'tcs')->assertJsonCount(1, 'softSkills')->assertJsonCount(1, 'additionals')
            ->assertJsonPath('tcs.0.id', $ids['tc'])->assertJsonPath('tcs.1.id', $ids['second_tc'])
            ->assertJsonPath('tcs.1.deskripsi_tc', 'Different definition')
            ->assertJsonPath('softSkills.0.deskripsi_sk', 'Specific sk <img src=x onerror="alert(1)">')
            ->assertJsonPath('additionals.0.deskripsi_ad', null)
            ->assertJsonPath('tcs.0.poin_kategori.deskripsi_1', 'Guide one')
            ->assertJsonPath('softSkills.0.poin_kategori.deskripsi_2', 'Guide two')
            ->assertJsonPath('additionals.0.poin_kategori.deskripsi_3', 'Guide three')
            ->assertJsonPath('additionals.0.poin_kategori.deskripsi_4', 'Guide four')
            ->assertJsonMissingPath('tcs.0.deskripsi_level_1');
        $this->postJson(route('job.positions.details'), ['id' => $position->id])->assertOk()->assertJsonCount(2, 'tcs');
    }

    public function test_staff_cannot_call_metadata_or_assessment_endpoints_directly(): void
    {
        $position = $this->position();
        $staff = new \App\Models\User(['name' => 'Fixture ordinary staff']);
        $staff->id = 2000;
        $this->actingAs($staff);
        foreach ([
            route('job.positions.details2', $position->id),
            route('getJobPositionData', ['id' => $position->id]),
            route('getJobPositionDataEdit', ['id' => $position->id]),
            route('getNilaiDataEdit', ['id' => $position->id]),
            route('getDataTrs', ['id_job_position' => $position->id]),
            route('penilaian.view', $position->id), route('penilaian.preview', $position->id),
            route('penilaian.yearlyHistory', [$position->id, 2025]),
        ] as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->postJson(route('job.positions.details'), ['id' => $position->id])->assertForbidden();
    }

    public function test_head_read_scope_denies_other_branch_and_allows_existing_competency_scope(): void
    {
        $department = DB::table('mst_departments')->insertGetId(['name' => 'Fixture department']);
        $section = DB::table('mst_sections')->insertGetId(['name' => 'Fixture section', 'department_id' => $department]);
        $head = new \App\Models\User(['name' => 'Fixture head']);
        $head->id = 2000;
        $own = $this->position('sec_head');
        $own->update(['section_id' => $section, 'department_id' => $department]);
        $this->assign($head, $own);
        $allowed = $this->position();
        $allowed->update(['section_id' => $section, 'department_id' => $department]);
        $outside = $this->position();
        $this->definitions($allowed);
        $this->actingAs($head)->getJson(route('job.positions.details2', $allowed->id))->assertOk();
        $this->getJson(route('job.positions.details2', $outside->id))->assertForbidden();
        $this->getJson(route('getDataTrs', ['id_job_position' => $outside->id]))->assertForbidden();
        $this->getJson(route('getJobPositionData', ['id' => $outside->id]))->assertForbidden();
    }

    public function test_supporting_reads_are_year_scoped_and_do_not_change_scores_status_or_locks(): void
    {
        $position = $this->position();
        $employee = $this->employee();
        $employee->update(['email' => 'fixture@example.test', 'fcm_token' => 'fixture-private-token']);
        $this->assign($employee, $position);
        $ids = $this->definitions($position);
        foreach ([now()->year - 1, now()->year] as $year) {
            TrsPenilaianTc::create([
                'id_user' => $employee->id, 'id_job_position' => $position->id, 'id_tc' => $ids['tc'],
                'nilai_tc' => $year === now()->year ? 3 : 1, 'status' => $year === now()->year ? 1 : 4,
                'tahun_penilaian' => $year, 'is_locked' => $year < now()->year,
            ]);
        }
        $before = DB::table('trs_penilaian_tcs')->orderBy('id')->get()->toJson();
        $this->actingAs($this->manager())->getJson(route('getDataTrs', ['id_job_position' => $position->id]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.nilai_tc', 3)
            ->assertJsonMissingPath('0.user.email')->assertJsonMissingPath('0.user.fcm_token');
        $this->getJson(route('getDataTrs', ['id_job_position' => $position->id, 'year' => now()->year - 1]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.nilai_tc', 1)->assertJsonPath('0.is_locked', true);
        $this->getJson(route('getDataTrs', ['id_job_position' => $position->id, 'year' => 'invalid']))->assertUnprocessable();
        $this->getJson(route('getJobPositionData', ['id' => $position->id]))->assertOk();
        $this->getJson(route('getJobPositionDataEdit', ['id' => $position->id, 'year' => now()->year - 1]))->assertOk();
        $this->getJson(route('getNilaiDataEdit', ['id' => $position->id, 'year' => now()->year - 1]))->assertOk()->assertJsonCount(1)->assertJsonPath('0.nilai_tc', 1);
        $this->getJson(route('job.positions.details2', $position->id))->assertOk();
        $this->assertSame($before, DB::table('trs_penilaian_tcs')->orderBy('id')->get()->toJson());
    }

    public function test_existing_score_submission_keeps_names_values_year_and_draft_status(): void
    {
        $position = $this->position();
        $employee = $this->employee();
        $ids = $this->definitions($position);
        $this->actingAs($this->manager())->postJson(route('savePenilaian'), [
            'posisi' => $position->id, 'id_user' => [$employee->id],
            'id_tc' => [$employee->id => [$ids['tc']]], 'nilai_tc' => [$employee->id => [3]],
            'id_sk' => [$employee->id => [$ids['sk']]], 'nilai_sk' => [$employee->id => [2]],
            'id_ad' => [$employee->id => [$ids['ad']]], 'nilai_ad' => [$employee->id => [4]],
        ])->assertOk();
        $assessment = TrsPenilaianTc::firstOrFail();
        $this->assertSame([3, 2, 4], [$assessment->nilai_tc, $assessment->nilai_sk, $assessment->nilai_ad]);
        $this->assertSame(0, (int) $assessment->status);
        $this->assertSame(now()->year, $assessment->tahun_penilaian);
        $this->assertFalse($assessment->is_locked);
    }

    public function test_existing_draft_and_non_sales_approval_path_is_preserved(): void
    {
        $position = $this->position();
        $employee = $this->employee();
        $assessment = TrsPenilaianTc::create(['id_user' => $employee->id, 'id_job_position' => $position->id, 'nilai_tc' => 3, 'status' => 0, 'tahun_penilaian' => now()->year]);
        $this->actingAs($this->manager())->postJson(route('penilaian.submitDraft', $position->id))->assertOk();
        $this->assertSame(2, (int) $assessment->fresh()->status);
        $this->postJson(route('update.status2', $position->id))->assertOk();
        $this->assertSame(4, (int) $assessment->fresh()->status);
        $this->assertSame(3, $assessment->fresh()->nilai_tc);
    }

    public function test_existing_sales_division_approval_branch_is_preserved_without_structure_changes(): void
    {
        $position = $this->position();
        $position->update(['position_name' => 'Fixture Sales role']);
        $employee = $this->employee();
        DB::table('mst_position_approvals')->insert(['position_id' => $position->id, 'approver_position_id' => $this->position('div_head')->id, 'approval_level' => 3]);
        $assessment = TrsPenilaianTc::create(['id_user' => $employee->id, 'id_job_position' => $position->id, 'nilai_tc' => 2, 'status' => 2, 'tahun_penilaian' => now()->year]);
        $this->actingAs($this->manager())->postJson(route('update.status2', $position->id))->assertOk();
        $this->assertSame(3, (int) $assessment->fresh()->status);
        $this->postJson(route('update.status3', $position->id))->assertOk();
        $this->assertSame(4, (int) $assessment->fresh()->status);
        $this->assertSame(2, $assessment->fresh()->nilai_tc);
    }

    public function test_yearly_history_opens_existing_guidance_without_writing_historical_rows(): void
    {
        $position = $this->position();
        $employee = $this->employee();
        $this->definitions($position);
        $assessment = TrsPenilaianTc::create(['id_user' => $employee->id, 'id_job_position' => $position->id, 'status' => 4, 'nilai_tc' => 3, 'tahun_penilaian' => 2025, 'is_locked' => true]);
        $before = $assessment->fresh()->getRawOriginal();
        $this->actingAs($this->manager())->get(route('penilaian.yearlyHistory', [$position->id, 2025]))
            ->assertOk()->assertSee('id="collapseKeterangan" class="accordion-collapse collapse show"', false)
            ->assertDontSee('data-competency-information', false);
        $this->assertSame($before, $assessment->fresh()->getRawOriginal());
    }

    public function test_all_active_forms_open_guidance_and_preserve_original_summary_without_extra_information_ui(): void
    {
        $position = $this->position();
        $position->job_position = $position->position_name;
        $employee = $this->employee();
        $ids = $this->definitions($position);
        $assessment = TrsPenilaianTc::create(['id_user' => $employee->id, 'id_job_position' => $position->id, 'id_tc' => $ids['tc'], 'status' => 2, 'tahun_penilaian' => now()->year]);
        $this->actingAs($this->manager());
        $data = [
            'penilaian' => $assessment->fresh(['jobPosition', 'user']), 'users' => collect([$employee]),
            'jobPositions' => collect([$position]), 'trsPenilaian' => collect([$assessment]),
            'id_tc' => $ids['tc'], 'id_sk' => $ids['sk'], 'id_ad' => $ids['ad'], 'idJobPosition' => $position->id,
            'dataTc1' => PoinKategori::find(1), 'dataTc2' => PoinKategori::find(2), 'dataTc3' => PoinKategori::find(3),
            'detailPenilaian' => collect(), 'updateRoute' => route('updateDept', $position->id),
            'backRoute' => route('penilaian.index2'),
        ];
        foreach (['sc_penilaian', 'edit_penilaian', 'dept_penilaian', 'view_penilaian', 'privew_penilaian'] as $name) {
            $html = view('tc_penilaian.'.$name, $data)->render();
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML($html);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new \DOMXPath($document);
            $trigger = $xpath->query('//button[@aria-controls="collapseKeterangan"]')->item(0);
            $panel = $document->getElementById('collapseKeterangan');
            $this->assertSame('true', $trigger->getAttribute('aria-expanded'), $name);
            $this->assertTrue($trigger->hasAttribute('data-assessment-guidance-toggle'), $name);
            $this->assertFalse($trigger->hasAttribute('data-bs-toggle'), $name);
            $this->assertStringNotContainsString('collapsed', $trigger->getAttribute('class'), $name);
            $this->assertStringContainsString('collapse show', $panel->getAttribute('class'), $name);
            $this->assertSame(12, $xpath->query('//*[@id="collapseKeterangan"]//tbody/tr')->length, $name);
            $this->assertStringContainsString('assessment-summary.js', $html);
            $this->assertStringNotContainsString('data-competency', $html);
            $this->assertStringNotContainsString('competency-information.js', $html);
            $this->assertStringNotContainsString('hr-ci-', $html);
            $this->assertSame($name === 'privew_penilaian' ? 0 : 1, $xpath->query('//*[@id="openModalButton"]')->length, $name);
            $this->assertSame($name === 'privew_penilaian' ? 0 : 1, $xpath->query('//*[@id="jobPositionModal"]')->length, $name);
            $this->assertInlineJavascriptParses($html, $name);
        }
    }
}
