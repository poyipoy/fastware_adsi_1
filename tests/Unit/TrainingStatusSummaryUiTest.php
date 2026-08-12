<?php

namespace Tests\Unit;

use Tests\TestCase;

class TrainingStatusSummaryUiTest extends TestCase
{
    public function test_active_training_pages_place_status_summary_at_the_top(): void
    {
        $views = [
            'dept_develop_index.blade.php',
            'edit_develop_hrga.blade.php',
            'view_develop_hrga.blade.php',
            'view_develop.blade.php',
        ];

        foreach ($views as $view) {
            $blade = file_get_contents(resource_path('views/people_development/'.$view));

            $this->assertStringContainsString('data-training-status-anchor', $blade, $view);
            $this->assertStringContainsString('data-training-status-summary', $blade, $view);
            $this->assertStringContainsString('training-status-summary.css', $blade, $view);
            $this->assertStringContainsString('training-status-summary.js', $blade, $view);
        }
    }

    public function test_shared_script_moves_summary_after_page_title(): void
    {
        $script = file_get_contents(public_path('js/hr/training-status-summary.js'));

        $this->assertStringContainsString("anchor.insertAdjacentElement('afterend', summary)", $script);
        $this->assertStringContainsString('aria-label', $script);
    }
}
