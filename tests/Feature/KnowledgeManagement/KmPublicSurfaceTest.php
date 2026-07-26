<?php

namespace Tests\Feature\KnowledgeManagement;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KmPublicSurfaceTest extends TestCase
{
    public function test_km_trigger_scripts_are_not_web_accessible(): void
    {
        $this->assertSame([], glob(public_path('trigger*.php')) ?: []);
        $this->assertFalse(File::exists(public_path('clean_triggers.php')));
    }

    public function test_public_php_scripts_do_not_expose_km_commands_or_private_paths(): void
    {
        foreach (File::allFiles(public_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = File::get($file->getPathname());

            $this->assertDoesNotMatchRegularExpression(
                '/km:migrate-private-files|private[\\\\\/]km|KmPengajuan/u',
                $source,
                "KM command or private path exposed by {$file->getRelativePathname()}",
            );
        }
    }

    public function test_pending_route_is_absent_and_long_term_routes_are_present(): void
    {
        $this->assertFalse(Route::has('km.progress.save'));
        $this->assertTrue(Route::has('km.approvals.bulk'));
        $this->assertTrue(Route::has('km.analytics.popular'));
        $this->assertTrue(Route::has('km.analytics.popular.export.xlsx'));
        $this->assertTrue(Route::has('km.analytics.popular.export.pdf'));
    }

    public function test_km_assets_do_not_reference_pdfjs_cdn(): void
    {
        foreach ([
            resource_path('js/km/dashboard.js'),
            resource_path('js/km/pdf-viewer.js'),
            resource_path('views/dashboard/dsKnowlege.blade.php'),
        ] as $path) {
            $source = File::get($path);
            $this->assertStringNotContainsString('cdnjs', $source);
            $this->assertStringNotContainsString('mozilla.github.io', $source);
        }
    }

    public function test_active_km_controller_is_orchestration_only(): void
    {
        $source = File::get(app_path('Http/Controllers/KmPengajuanController.php'));

        foreach ([
            'DB::',
            'lockForUpdate(',
            '->create(',
            '->save(',
            '->update(',
            '->delete(',
            '->validate(',
            'KmPengajuan::query(',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }

        $this->assertStringContainsString('KmDocumentAuthoringService', $source);
        $this->assertStringContainsString('KmInteractionService', $source);
        $this->assertStringContainsString('KmDocumentQueryService', $source);
    }
}
