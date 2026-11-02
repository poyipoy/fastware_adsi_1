<?php

namespace App\Http\Controllers;

use App\Models\MstDepartment;
use App\Models\MstSection;
use App\Services\HR\DepartmentStructureService;
use App\Services\HR\JobPositionAccessService;
use Illuminate\Http\Request;

class MstDepartmentController extends Controller
{
    public function __construct(private DepartmentStructureService $structure, private JobPositionAccessService $access)
    {
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($this->access->canManageMapping($request->user()), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeManager($request);
        $request->validate(['search' => 'nullable|string|max:100', 'is_active' => 'nullable|boolean']);
        $departments = MstDepartment::query()->withCount(['sections', 'jobPositions'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search')->trim().'%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('mst_department.index', compact('departments'));
    }

    public function show(Request $request, MstDepartment $mstDepartment)
    {
        $this->authorizeManager($request);
        $mstDepartment->load(['sections' => fn ($q) => $q->withCount('jobPositions')
            ->with(['jobPositions' => fn ($positions) => $positions->orderBy('id')->select(['id', 'department_id', 'section_id', 'is_active', 'updated_at'])])->orderBy('name')]);
        $positions = $mstDepartment->jobPositions()->with('section')->orderBy('position_name')->paginate(25);
        $destinations = MstDepartment::active()->whereKeyNot($mstDepartment->id)->orderBy('name')->get();
        $tokens = $mstDepartment->sections->mapWithKeys(fn ($section) => [$section->id => $this->structure->sectionToken($section)]);

        return view('mst_department.show', ['department' => $mstDepartment, 'positions' => $positions, 'destinations' => $destinations, 'tokens' => $tokens]);
    }

    public function store(Request $request)
    {
        $this->authorizeManager($request);
        $request->validate(['name' => 'required|string']);
        $this->structure->saveDepartment($request->input('name'));

        return redirect()->route('mst-department.index')->with('success', 'Department berhasil ditambahkan.');
    }

    public function update(Request $request, MstDepartment $mstDepartment)
    {
        $this->authorizeManager($request);
        $request->validate(['name' => 'required|string']);
        $this->structure->saveDepartment($request->input('name'), $mstDepartment->id);

        return redirect()->route('mst-department.show', $mstDepartment)->with('success', 'Nama department berhasil diperbarui.');
    }

    public function status(Request $request, MstDepartment $mstDepartment)
    {
        $this->authorizeManager($request);
        $request->validate(['is_active' => 'required|boolean']);
        $this->structure->setStatus($mstDepartment->id, $request->boolean('is_active'));

        return back()->with('success', 'Status department berhasil diperbarui.');
    }

    public function moveSection(Request $request, MstDepartment $mstDepartment, MstSection $mstSection)
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'department_id' => 'required|integer|exists:mst_departments,id',
            'expected_department_id' => 'required|integer', 'structure_token' => 'required|string|size:64',
        ]);
        abort_unless((int) $data['expected_department_id'] === (int) $mstDepartment->id, 409, 'Department asal berubah. Muat ulang halaman.');
        $this->structure->moveSection($mstDepartment->id, $mstSection->id, (int) $data['department_id'], $data['structure_token']);

        return redirect()->route('mst-department.show', $data['department_id'])->with('success', 'Section beserta seluruh posisinya berhasil dipindahkan. Jalur approval tetap; atur terpisah di Master Job Position.');
    }
}
