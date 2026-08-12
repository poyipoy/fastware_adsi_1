<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeManagement\DeleteKmAccessRuleRequest;
use App\Http\Requests\KnowledgeManagement\StoreKmAccessRuleRequest;
use App\Models\KmAccessRule;
use App\Models\MstJobPosition;
use App\Models\Role;
use App\Models\User;
use App\Services\KnowledgeManagement\KmAccessService;
use App\Services\KnowledgeManagement\KmRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class KmAccessRuleController extends Controller
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmRbacService $rbac,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->access->canManageAccess(request()->user()), 403);
        abort_unless(Schema::hasTable('km_access_rules'), 503, 'Schema RBAC KM belum tersedia.');

        $users = User::query()->where('is_active', false)->orderBy('name')->get(['id', 'name', 'npk']);
        $roles = Role::query()->orderBy('role')->get(['id', 'role']);
        $positions = MstJobPosition::query()->active()->orderBy('position_name')->get(['id', 'position_name']);
        $labels = [
            'user' => $users->pluck('name', 'id'),
            'role' => $roles->pluck('role', 'id'),
            'job_position' => $positions->pluck('position_name', 'id'),
        ];

        return view('knowlege_management.access.index', [
            'rules' => KmAccessRule::query()->latest('id')->paginate(25),
            'users' => $users,
            'roles' => $roles,
            'positions' => $positions,
            'abilities' => KmRbacService::ABILITIES,
            'subjectLabels' => $labels,
        ]);
    }

    public function store(StoreKmAccessRuleRequest $request): RedirectResponse
    {
        $this->rbac->createRule($request->user(), $request->validated());

        return back()->with('success', 'Rule akses KM berhasil disimpan.');
    }

    public function destroy(
        DeleteKmAccessRuleRequest $request,
        KmAccessRule $accessRule,
    ): RedirectResponse {
        $this->rbac->deleteRule($request->user(), $accessRule, $request->validated('reason'));

        return back()->with('success', 'Rule akses KM berhasil dihapus dan dicatat dalam audit trail.');
    }
}
