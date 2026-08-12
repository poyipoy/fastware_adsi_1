<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Services\KnowledgeManagement\KmAccessService;
use App\Services\KnowledgeManagement\KmComplianceExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class KmComplianceExportController extends Controller
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmComplianceExportService $exports,
    ) {}

    public function details(Request $request, string $format): Response
    {
        abort_unless($this->access->canExport($request->user()), 403);
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);
        return $this->exports->details($request->user(), $format, $this->filters($request));
    }

    public function pdf(Request $request): Response
    {
        abort_unless($this->access->canViewAnalytics($request->user()), 403);
        return $this->exports->aggregatePdf($request->user(), $this->filters($request));
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'assignment_id' => ['nullable', 'integer', Rule::exists('km_assignments', 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
    }
}
