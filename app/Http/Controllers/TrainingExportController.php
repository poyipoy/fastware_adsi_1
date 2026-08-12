<?php

namespace App\Http\Controllers;

use App\Exports\TrainingApprovalExport;
use App\Exports\TrainingFollowUpExport;
use App\Exports\TrainingHistoryExport;
use App\Exports\TrainingSubmissionExport;
use App\Services\HR\EmployeeIdentityFormatter;
use App\Services\HR\TrainingExportQueryService;
use App\Services\HR\TrainingHistoryQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TrainingExportController extends Controller
{
    public function __construct(
        private readonly TrainingExportQueryService $queries,
        private readonly TrainingHistoryQueryService $history,
    ) {
    }

    public function submissions(Request $request)
    {
        $query = $this->queries->submissions($request->user(), $this->filters($request));

        return $this->downloadOrEmpty(
            $request,
            $query,
            new TrainingSubmissionExport($query),
            'pengajuan_training',
        );
    }

    public function approvals(Request $request)
    {
        $query = $this->queries->approvals($request->user(), $this->filters($request));

        return $this->downloadOrEmpty(
            $request,
            $query,
            new TrainingApprovalExport($query),
            'persetujuan_training',
        );
    }

    public function followUp(Request $request, int $tahun)
    {
        $filters = $this->filters($request);
        $filters['year'] = $tahun;
        $query = $this->queries->followUp($request->user(), $tahun, $filters);

        return $this->downloadOrEmpty(
            $request,
            $query,
            new TrainingFollowUpExport($query),
            'tindak_lanjut_training',
        );
    }

    public function history(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->queries->history($request->user(), $filters);
        if (! (clone $query)->exists()) {
            return $this->emptyResponse($request);
        }

        return Excel::download(
            new TrainingHistoryExport($this->history, $request->user(), $filters),
            $this->filename('history_training'),
        );
    }

    public function historyCsv(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->queries->history($request->user(), $filters);
        if (! (clone $query)->exists()) {
            return $this->emptyResponse($request);
        }

        return response()->streamDownload(function () use ($request, $filters): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, [
                'tahun', 'department', 'section', 'job_position', 'npk', 'nama_karyawan',
                'jenis', 'program_training', 'program_training_plan', 'status',
            ]);

            foreach ($this->history->flattened($request->user(), $filters) as $row) {
                $training = $row['training'];
                $participant = $row['participant'];
                fputcsv($stream, [
                    $training->tahun_aktual,
                    $training->section?->department?->name ?? $training->jobPosition?->department?->name ?? '-',
                    $training->section?->name ?? '-',
                    $training->jobPosition?->position_name ?? '-',
                    EmployeeIdentityFormatter::npk($participant->npk),
                    $participant->name,
                    $training->is_sharing_knowledge ? 'Sharing Knowledge' : 'Training',
                    $training->program_training,
                    $training->program_training_plan,
                    $training->status_2,
                ]);
            }

            fclose($stream);
        }, str_replace('.xlsx', '.csv', $this->filename('history_training')), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function downloadOrEmpty(Request $request, $query, $export, string $prefix)
    {
        if (! (clone $query)->exists()) {
            return $this->emptyResponse($request);
        }

        return Excel::download($export, $this->filename($prefix));
    }

    private function emptyResponse(Request $request)
    {
        $message = 'Tidak ada data yang sesuai dengan filter export.';

        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->with('warning', $message);
    }

    private function filters(Request $request): array
    {
        return $request->only([
            'year', 'tahun', 'status_1', 'status_2', 'kategori', 'search', 'department_id',
        ]);
    }

    private function filename(string $prefix): string
    {
        return $prefix.'_'.Carbon::now('Asia/Jakarta')->format('Y-m-d_Hi').'.xlsx';
    }
}
