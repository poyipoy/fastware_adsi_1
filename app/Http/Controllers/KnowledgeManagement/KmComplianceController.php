<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeManagement\OverrideKmCompletionRequest;
use App\Http\Requests\KnowledgeManagement\ExemptKmAssignmentUserRequest;
use App\Http\Requests\KnowledgeManagement\StoreKmAssignmentRequest;
use App\Models\KmAssignment;
use App\Models\KmAssignmentUser;
use App\Models\KmDocumentVersion;
use App\Models\MstDepartment;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Services\KnowledgeManagement\KmAccessService;
use App\Services\KnowledgeManagement\KmAssignmentService;
use App\Services\KnowledgeManagement\KmCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class KmComplianceController extends Controller
{
    public function __construct(private readonly KmAccessService $access) {}

    public function index(): View
    {
        abort_unless($this->access->canManageAssignments(request()->user())
            || $this->access->canViewAnalytics(request()->user()), 403);
        abort_unless(Schema::hasTable('km_assignments'), 503, 'Schema compliance KM belum tersedia.');

        $summary = [
            'assignments' => KmAssignment::query()->count(),
            'recipients' => KmAssignmentUser::query()->count(),
            'completed' => KmAssignmentUser::query()->whereNotNull('completed_at')->count(),
            'overdue' => KmAssignmentUser::query()->whereNull('completed_at')->whereNull('exempted_at')
                ->where('due_at', '<', now())->count(),
            'engaged_users' => DB::table('km_transaksis')->whereNotNull('last_progress_at')->distinct('id_user')->count('id_user'),
            'average_approval_hours' => round((float) DB::table('km_approval_events as submitted')
                ->join('km_approval_events as approved', static function ($join): void {
                    $join->on('approved.km_pengajuan_id', '=', 'submitted.km_pengajuan_id')
                        ->where('approved.action', 'approved');
                })->where('submitted.action', 'submitted')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, submitted.acted_at, approved.acted_at)) / 3600 as hours')
                ->value('hours'), 1),
        ];
        $departmentCohorts = KmAssignmentUser::query()
            ->select('department_snapshot')
            ->whereNotNull('department_snapshot')
            ->selectRaw('COUNT(DISTINCT user_id) as cohort_size')
            ->selectRaw('SUM(completed_at IS NOT NULL) as completed_count')
            ->groupBy('department_snapshot')
            ->havingRaw('COUNT(DISTINCT user_id) >= 5')
            ->orderBy('department_snapshot')->get();

        return view('knowlege_management.compliance.index', [
            'summary' => $summary,
            'departmentCohorts' => $departmentCohorts,
            'assignments' => KmAssignment::query()->withCount(['users', 'users as completed_count' => static fn ($q) => $q->whereNotNull('completed_at')])
                ->latest('id')->paginate(20),
            'versions' => KmDocumentVersion::query()->with('document')
                ->where('version_status', 'published')->where('processing_status', 'ready')
                ->latest('published_at')->get(),
            'users' => User::query()->where('is_active', false)->orderBy('name')->get(['id', 'name', 'npk']),
            'departments' => MstDepartment::query()->active()->orderBy('name')->get(['id', 'name']),
            'positions' => MstJobPosition::query()->active()->orderBy('position_name')->get(['id', 'position_name']),
            'canManageAssignments' => $this->access->canManageAssignments(request()->user()),
            'canOverrideCompletion' => $this->access->canOverrideCompletion(request()->user()),
            'canViewAnalytics' => $this->access->canViewAnalytics(request()->user()),
            'pendingRecipients' => KmAssignmentUser::query()
                ->with(['user', 'assignment.version'])
                ->whereNull('completed_at')->whereNull('exempted_at')
                ->latest('id')->limit(50)->get(),
        ]);
    }

    public function store(StoreKmAssignmentRequest $request, KmAssignmentService $service): RedirectResponse
    {
        $service->create($request->user(), $request->validated());
        return back()->with('success', 'Assignment KM berhasil dibuat dan penerima telah disnapshot.');
    }

    public function override(
        OverrideKmCompletionRequest $request,
        KmDocumentVersion $version,
        User $user,
        KmCompletionService $service,
    ): RedirectResponse {
        $service->manualOverride($request->user(), $user, $version, $request->validated('reason'));
        return back()->with('success', 'Completion aksesibilitas berhasil dicatat dalam audit trail.');
    }

    public function exempt(
        ExemptKmAssignmentUserRequest $request,
        KmAssignmentUser $assignmentUser,
        KmAssignmentService $service,
    ): RedirectResponse {
        $service->exempt($request->user(), $assignmentUser, $request->validated('reason'));
        return back()->with('success', 'Pengecualian assignment dicatat beserta actor dan alasan.');
    }
}
