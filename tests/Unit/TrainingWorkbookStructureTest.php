<?php

namespace Tests\Unit;

use App\Exports\AbstractTrainingQueryExport;
use App\Exports\WorkingExperienceTemplateExport;
use App\Models\TcPeopleDevelopment;
use App\Models\User;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class TrainingWorkbookStructureTest extends TestCase
{
    public function test_working_experience_template_has_the_locked_eight_columns(): void
    {
        $export = new WorkingExperienceTemplateExport();

        $this->assertSame([
            'npk',
            'nama_karyawan',
            'tahun_mulai',
            'tahun_selesai',
            'jabatan',
            'section',
            'departemen',
            'keterangan',
        ], $export->headings());
        $this->assertCount(8, $export->array()[0]);
    }

    public function test_general_training_export_joins_participants_and_uses_native_numeric_values(): void
    {
        $query = TcPeopleDevelopment::query();
        $export = new class($query) extends AbstractTrainingQueryExport
        {
        };

        $first = new User(['npk' => '001', 'name' => 'Budi']);
        $first->id = 1;
        $second = new User(['npk' => 0, 'name' => 'Sari']);
        $second->id = 2;

        $training = new TcPeopleDevelopment([
            'tahun_aktual' => 2026,
            'is_sharing_knowledge' => true,
            'program_training' => 'Sharing Safety',
            'due_date' => '2026-08-01',
            'due_date_plan' => '2026-08-02',
            'biaya' => 'Rp 1.250.000',
            'biaya_plan' => '750000',
        ]);
        $training->setRelation('participants', collect([$first, $second]));
        $training->setRelation('user', $first);
        $training->setRelation('section', null);
        $training->setRelation('jobPosition', null);

        $row = $export->map($training);

        $this->assertSame('001, -', $row[5]);
        $this->assertSame('Budi, Sari', $row[6]);
        $this->assertIsFloat($row[11]);
        $this->assertSame(1250000.0, $row[13]);
        $this->assertSame(750000.0, $row[14]);
    }

    public function test_working_experience_xlsx_renders_with_freeze_pane_and_filter(): void
    {
        $binary = Excel::raw(new WorkingExperienceTemplateExport(), ExcelWriter::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'we-template-');

        try {
            file_put_contents($path, $binary);
            $sheet = IOFactory::load($path)->getActiveSheet();

            $this->assertSame('npk', $sheet->getCell('A1')->getValue());
            $this->assertSame('keterangan', $sheet->getCell('H1')->getValue());
            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertSame('A1:H1', $sheet->getAutoFilter()->getRange());
        } finally {
            @unlink($path);
        }
    }
}
