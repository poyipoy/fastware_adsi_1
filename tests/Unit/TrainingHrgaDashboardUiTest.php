<?php

namespace Tests\Unit;

use Tests\TestCase;

class TrainingHrgaDashboardUiTest extends TestCase
{
    public function test_hrga_dashboard_shows_total_training_and_progress_statuses(): void
    {
        $blade = file_get_contents(
            resource_path('views/people_development/hrga_develop_index.blade.php'),
        );

        $this->assertStringContainsString('Total Training', $blade);
        $this->assertStringNotContainsString('Departemen Aktif', $blade);
        $this->assertStringContainsString('Ringkasan Status', $blade);
        $this->assertStringContainsString('progress-summary-track', $blade);
        $this->assertStringContainsString('$progressPercentage', $blade);
        $this->assertStringContainsString('$kpiMencariVendor', $blade);
        $this->assertStringContainsString('$kpiProsesPendaftaran', $blade);
        $this->assertStringContainsString('$kpiOnProgress', $blade);
        $this->assertStringContainsString('$kpiDone', $blade);
        $this->assertStringContainsString('$kpiPending', $blade);
        $this->assertStringContainsString('$kpiDitolak', $blade);
    }

    public function test_hrga_metrics_use_the_same_filtered_activity_collection(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PdController.php'));

        $this->assertStringContainsString('$kpiTotalTraining = $allHrgaData->count();', $controller);
        $this->assertStringContainsString(
            '$kpiDone = $allHrgaData->where(\'status_2\', TrainingStatus::DONE)->count();',
            $controller,
        );
    }
}
