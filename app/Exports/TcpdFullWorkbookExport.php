<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TcpdFullWorkbookExport implements WithMultipleSheets
{
    use Exportable;

    protected array $companyRows;
    protected array $competencyRows;
    protected array $topJobs;
    protected array $criticalFocus;
    protected array $meta;

    public function __construct(
        array $companyRows,
        array $competencyRows,
        array $topJobs,
        array $criticalFocus,
        array $meta = []
    ) {
        $this->companyRows    = $companyRows;
        $this->competencyRows = $competencyRows;
        $this->topJobs        = $topJobs;
        $this->criticalFocus  = $criticalFocus;
        $this->meta           = $meta;
    }

    /**
     * @return array<int, mixed>
     */
    public function sheets(): array
    {
        return [
            new TcpdCompanyExport($this->companyRows, $this->meta, 'Departemen & Company'),
            new TcpdCompetencyExport($this->competencyRows, $this->meta, 'Area Development'),
            new TcpdTopJobsExport($this->topJobs, $this->meta, 'Top Jobs'),
            new TcpdCriticalFocusExport($this->criticalFocus, $this->meta, 'Critical Focus Area'),
        ];
    }
}
