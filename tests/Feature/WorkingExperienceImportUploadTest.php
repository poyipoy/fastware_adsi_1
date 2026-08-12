<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class WorkingExperienceImportUploadTest extends TestCase
{
    public function test_uploaded_workbook_is_staged_and_removed_after_import(): void
    {
        Storage::fake('local');
        config()->set('excel.transactions.handler', 'null');
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $user = new User(['name' => 'SITI MARIA ULFA']);
        $user->id = 91;
        $user->exists = true;

        $temporaryPath = tempnam(sys_get_temp_dir(), 'working-experience-import-');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            'npk',
            'nama_karyawan',
            'tahun_mulai',
            'tahun_selesai',
            'jabatan',
            'section',
            'departemen',
            'keterangan',
        ]);
        (new Xlsx($spreadsheet))->save($temporaryPath);

        try {
            $response = $this->actingAs($user)->post(
                route('user-job-position.api.working-experience.import'),
                [
                    'import_file' => new UploadedFile(
                        $temporaryPath,
                        'working-experience.xlsx',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        null,
                        true,
                    ),
                ],
            );
        } finally {
            $spreadsheet->disconnectWorksheets();

            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        $response
            ->assertRedirect()
            ->assertSessionHas('import_success', 'Import selesai. Tidak ada data untuk ditambahkan.');

        $this->assertSame([], Storage::disk('local')->allFiles('imports/working-experience'));
    }
}
