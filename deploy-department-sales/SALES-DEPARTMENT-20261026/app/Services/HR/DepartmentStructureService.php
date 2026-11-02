<?php

namespace App\Services\HR;

use App\Models\MstDepartment;
use App\Models\MstJobPosition;
use App\Models\MstSection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentStructureService
{
    /** Organization writers lock this small master table first, in the same order. */
    public function lockOrganization(): void
    {
        MstDepartment::query()->orderBy('id')->lockForUpdate()->get(['id']);
    }

    public function saveDepartment(string $name, ?int $id = null): MstDepartment
    {
        try {
            return DB::transaction(function () use ($name, $id) {
                $this->lockOrganization();
                $attributes = Validator::make(['name' => trim($name)], [
                    'name' => ['required', 'string', 'max:100', Rule::unique('mst_departments', 'name')->ignore($id)],
                ])->validate();
                $department = $id === null ? new MstDepartment(['is_active' => true]) : MstDepartment::findOrFail($id);
                $department->fill($attributes)->save();

                return $department;
            }, 3);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['name' => 'Nama department sudah digunakan.']);
            }
            throw $e;
        }
    }

    public function setStatus(int $id, bool $active): void
    {
        DB::transaction(function () use ($id, $active) {
            $this->lockOrganization();
            $department = MstDepartment::findOrFail($id);
            if (! $active && ($department->sections()->active()->exists() || $department->jobPositions()->active()->exists())) {
                throw ValidationException::withMessages(['is_active' => 'Pindahkan atau nonaktifkan section dan posisi aktif sebelum menonaktifkan department.']);
            }
            $department->update(['is_active' => $active]);
        }, 3);
    }

    /** Call inside the writer transaction after lockOrganization(). */
    public function validatePositionStructure(?int $departmentId, ?int $sectionId): void
    {
        if ($departmentId !== null && ! MstDepartment::active()->whereKey($departmentId)->exists()) {
            throw ValidationException::withMessages(['department_id' => 'Pilih department yang aktif.']);
        }
        if ($sectionId !== null) {
            $section = MstSection::whereKey($sectionId)->lockForUpdate()->first();
            if (! $section || ! $section->is_active || $departmentId === null || (int) $section->department_id !== $departmentId) {
                throw ValidationException::withMessages(['section_id' => 'Section harus aktif dan berada di department yang dipilih. Muat ulang halaman jika struktur berubah.']);
            }
        }
    }

    public function createSection(int $departmentId, string $name): MstSection
    {
        return DB::transaction(function () use ($departmentId, $name) {
            $this->lockOrganization();
            $this->validatePositionStructure($departmentId, null);
            $attributes = Validator::make(['name' => trim($name)], [
                'name' => ['required', 'string', 'max:150', Rule::unique('mst_sections', 'name')->where('department_id', $departmentId)],
            ])->validate();

            return MstSection::create([...$attributes, 'department_id' => $departmentId, 'is_active' => true]);
        }, 3);
    }

    /** Serialize assignment writes with a cutover before accepting an active job. */
    public function lockAssignmentPosition(int $positionId): void
    {
        $this->lockOrganization();
        if (! MstJobPosition::active()->whereKey($positionId)->lockForUpdate()->first()) {
            throw ValidationException::withMessages(['mst_job_position_id' => 'Pilih job position yang masih aktif.']);
        }
    }

    public function sectionToken(MstSection $section): string
    {
        $positions = $section->relationLoaded('jobPositions') ? $section->jobPositions
            : MstJobPosition::where('section_id', $section->id)->orderBy('id')
                ->get(['id', 'department_id', 'section_id', 'is_active', 'updated_at']);

        return hash('sha256', json_encode([
            $section->only(['id', 'name', 'department_id', 'is_active', 'updated_at']),
            $positions->map(fn ($position) => $position->only(['id', 'department_id', 'section_id', 'is_active', 'updated_at']))->values()->all(),
        ], JSON_THROW_ON_ERROR));
    }

    public function moveSection(int $sourceId, int $sectionId, int $destinationId, string $expectedToken): void
    {
        DB::transaction(function () use ($sourceId, $sectionId, $destinationId, $expectedToken) {
            $this->lockOrganization();
            $section = MstSection::whereKey($sectionId)->lockForUpdate()->firstOrFail();
            $positions = MstJobPosition::where('section_id', $sectionId)->orderBy('id')->lockForUpdate()->get();
            abort_unless((int) $section->department_id === $sourceId && hash_equals($this->sectionToken($section), $expectedToken),
                409, 'Struktur section berubah. Muat ulang halaman sebelum memindahkan.');
            if ($sourceId === $destinationId) {
                throw ValidationException::withMessages(['department_id' => 'Department tujuan harus berbeda dari department asal.']);
            }
            $this->validatePositionStructure($destinationId, null);
            if ($positions->contains(fn ($position) => (int) $position->department_id !== $sourceId)) {
                throw ValidationException::withMessages(['section_id' => 'Department posisi dalam section tidak konsisten. Perbaiki data sebelum memindahkan.']);
            }
            if (MstSection::where('department_id', $destinationId)->where('name', $section->name)->exists()) {
                throw ValidationException::withMessages(['department_id' => 'Nama section sudah digunakan di department tujuan.']);
            }
            $section->update(['department_id' => $destinationId]);
            MstJobPosition::where('section_id', $sectionId)->update(['department_id' => $destinationId, 'updated_at' => now()]);
        }, 3);
    }
}
