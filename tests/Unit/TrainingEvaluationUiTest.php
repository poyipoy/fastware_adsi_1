<?php

namespace Tests\Unit;

use Tests\TestCase;

class TrainingEvaluationUiTest extends TestCase
{
    public function test_group_form_has_participant_and_readonly_states(): void
    {
        $blade = file_get_contents(
            resource_path('views/people_development/form_evaluasi.blade.php'),
        );

        $this->assertStringContainsString('Evaluasi Grup Sharing Knowledge', $blade);
        $this->assertStringContainsString('participant-list', $blade);
        $this->assertStringContainsString('Evaluasi Peserta Secara Kelompok', $blade);
        $this->assertStringContainsString('name="metode_evaluasi"', $blade);
        $this->assertStringContainsString('value="Sharing Knowledge"', $blade);
        $this->assertStringContainsString('@disabled(! $canEditEvaluation)', $blade);
        $this->assertStringNotContainsString('roleId === 14 || roleId === 15', $blade);
    }

    public function test_profile_uses_explicit_payload_and_safe_button_ids(): void
    {
        $blade = file_get_contents(resource_path('views/auth/dataDiri.blade.php'));

        $this->assertStringContainsString('trainingEvaluationById', $blade);
        $this->assertStringContainsString('evaluation-view-button', $blade);
        $this->assertStringContainsString('evaluation-print-button', $blade);
        $this->assertStringNotContainsString('onclick="showModal({{', $blade);
    }

    public function test_pdf_generator_supports_long_participant_lists(): void
    {
        $script = file_get_contents(public_path('js/hr/training-evaluation-pdf.js'));

        $this->assertStringContainsString('participantLabels(data)', $script);
        $this->assertStringContainsString('ensureSpace', $script);
        $this->assertStringContainsString('pdf.addPage()', $script);
        $this->assertStringContainsString('EVALUASI PESERTA SECARA KELOMPOK', $script);
    }

    public function test_active_follow_up_views_use_group_completion_status(): void
    {
        $hrga = file_get_contents(
            resource_path('views/people_development/edit_develop_hrga.blade.php'),
        );
        $manager = file_get_contents(
            resource_path('views/people_development/view_develop.blade.php'),
        );

        $this->assertStringContainsString('item.evaluation_completed', $hrga);
        $this->assertStringContainsString('item.evaluation_completed', $manager);
        $this->assertStringContainsString('canEditSharingEvaluation', $manager);
        $this->assertStringContainsString('participantUsers', $manager);
        $this->assertStringContainsString('item.participants', $manager);
    }
}
