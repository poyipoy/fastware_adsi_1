<?php

namespace App\Http\Controllers;

use App\Exports\KnowledgeManagement\KmPopularMaterialExport;
use App\Http\Requests\KnowledgeManagement\KmPopularMaterialFilterRequest;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmPopularMaterialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KmAnalyticsController extends Controller
{
    public function popular(
        KmPopularMaterialFilterRequest $request,
        KmPopularMaterialReportService $reports,
    ): View {
        $this->authorize('viewPopularAnalytics', KmPengajuan::class);
        $filters = $request->filters();
        $options = $reports->filterOptions();
        $materials = $reports->paginate($request->user(), $filters);

        return view('knowlege_management.analytics.popular', [
            'materials' => $materials,
            'generatedAt' => $reports->generatedAt(),
            'filters' => $filters,
            'exportLimitReached' => $materials->total() >= $reports->exportLimit(),
            'exportTruncated' => $materials->total() > $reports->exportLimit(),
            ...$options,
        ]);
    }

    public function exportPopularXlsx(
        KmPopularMaterialFilterRequest $request,
        KmPopularMaterialReportService $reports,
    ): BinaryFileResponse {
        $this->authorize('viewPopularAnalytics', KmPengajuan::class);
        $report = $reports->exportReport($request->user(), $request->filters());
        $filename = 'km-materi-populer-'.$report['generated_at']->format('Ymd_His').'.xlsx';

        return Excel::download(new KmPopularMaterialExport($report), $filename);
    }

    public function exportPopularPdf(
        KmPopularMaterialFilterRequest $request,
        KmPopularMaterialReportService $reports,
    ): Response {
        $this->authorize('viewPopularAnalytics', KmPengajuan::class);
        $report = $reports->exportReport($request->user(), $request->filters());
        $filename = 'km-materi-populer-'.$report['generated_at']->format('Ymd_His').'.pdf';

        return Pdf::loadView('knowlege_management.analytics.popular-pdf', $report)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }
}
