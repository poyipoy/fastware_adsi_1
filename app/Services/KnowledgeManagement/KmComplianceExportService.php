<?php

namespace App\Services\KnowledgeManagement;

use App\Exports\KnowledgeManagement\KmHrComplianceExport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class KmComplianceExportService
{
    /** @param array<string, mixed> $filters */
    public function details(User $actor, string $format, array $filters): Response
    {
        $rows = $this->detailRows($filters);
        $writer = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $bytes = Excel::raw(new KmHrComplianceExport($rows), $writer);
        $fileName = 'km-hr-compliance-'.now()->format('Ymd-His').'.'.$extension;
        $this->audit($actor, 'hr_compliance_detail', $extension, $filters, $rows->count(), $fileName, $bytes);

        return response($bytes, 200, [
            'Content-Type' => $format === 'csv'
                ? 'text/csv; charset=UTF-8'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @param array<string, mixed> $filters */
    public function aggregatePdf(User $actor, array $filters): Response
    {
        $base = $this->filteredBase($filters);
        $summary = [
            'recipients' => (clone $base)->count(),
            'completed' => (clone $base)->whereNotNull('assignment_users.completed_at')->count(),
            'exempted' => (clone $base)->whereNotNull('assignment_users.exempted_at')->count(),
            'overdue' => (clone $base)->whereNull('assignment_users.completed_at')
                ->whereNull('assignment_users.exempted_at')->where('assignment_users.due_at', '<', now())->count(),
        ];
        $cohorts = (clone $base)
            ->select('assignment_users.department_snapshot')
            ->selectRaw('COUNT(DISTINCT assignment_users.user_id) AS cohort_size')
            ->selectRaw('SUM(assignment_users.completed_at IS NOT NULL) AS completed_count')
            ->whereNotNull('assignment_users.department_snapshot')
            ->groupBy('assignment_users.department_snapshot')
            ->havingRaw('COUNT(DISTINCT assignment_users.user_id) >= 5')
            ->orderBy('assignment_users.department_snapshot')->get();
        $bytes = Pdf::loadView('knowlege_management.compliance.export-pdf', [
            'summary' => $summary,
            'cohorts' => $cohorts,
            'generatedAt' => now(),
        ])->setPaper('a4')->output();
        $fileName = 'km-compliance-aggregate-'.now()->format('Ymd-His').'.pdf';
        $this->audit($actor, 'compliance_aggregate', 'pdf', $filters, $summary['recipients'], $fileName, $bytes);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function detailRows(array $filters): Collection
    {
        return $this->filteredBase($filters)
            ->join('users', 'users.id', '=', 'assignment_users.user_id')
            ->join('km_document_versions as versions', 'versions.id', '=', 'assignments.document_version_id')
            ->join('km_pengajuans as documents', 'documents.id', '=', 'versions.km_pengajuan_id')
            ->select([
                'users.npk as employee_id', 'users.name', 'assignment_users.department_snapshot',
                'documents.judul as document_title', 'assignments.title as assignment_title',
                'assignment_users.due_at', 'assignment_users.completed_at',
            ])
            ->selectRaw("CONCAT(versions.version_major, '.', versions.version_minor) AS version_number")
            ->selectRaw("CASE WHEN assignment_users.exempted_at IS NULL THEN '' ELSE COALESCE(assignment_users.exemption_reason, 'Exempted') END AS exemption")
            ->orderBy('users.name')->orderBy('assignments.id')->get();
    }

    /** @param array<string, mixed> $filters */
    private function filteredBase(array $filters)
    {
        return DB::table('km_assignment_users as assignment_users')
            ->join('km_assignments as assignments', 'assignments.id', '=', 'assignment_users.assignment_id')
            ->when(! empty($filters['assignment_id']), static fn ($query) => $query
                ->where('assignments.id', $filters['assignment_id']))
            ->when(! empty($filters['date_from']), static fn ($query) => $query
                ->whereDate('assignment_users.due_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), static fn ($query) => $query
                ->whereDate('assignment_users.due_at', '<=', $filters['date_to']));
    }

    /** @param array<string, mixed> $filters */
    private function audit(
        User $actor,
        string $type,
        string $format,
        array $filters,
        int $count,
        string $fileName,
        string $bytes,
    ): void {
        DB::table('km_export_audits')->insert([
            'actor_id' => $actor->getKey(),
            'export_type' => $type,
            'format' => $format,
            'filters' => json_encode($filters, JSON_THROW_ON_ERROR),
            'record_count' => $count,
            'file_name' => $fileName,
            'checksum_sha256' => hash('sha256', $bytes),
            'created_at' => now(),
        ]);
    }
}
