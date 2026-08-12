<?php

namespace App\Services\KnowledgeManagement;

use App\Models\User;
use App\Models\UserJobPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class KmOrganizationAssignmentService
{
    /** @param array<string, mixed> $attributes */
    public function create(User $actor, array $attributes, string $reason): UserJobPosition
    {
        return DB::transaction(function () use ($actor, $attributes, $reason): UserJobPosition {
            $attributes = $this->normalizeAttributes($attributes);
            $this->assertNoOverlap($attributes);
            $assignment = UserJobPosition::query()->create($attributes);
            $this->audit($assignment, $actor, 'created', null, $assignment->toArray(), $reason);

            return $assignment;
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function update(
        User $actor,
        UserJobPosition $assignment,
        array $attributes,
        string $reason,
    ): UserJobPosition {
        return DB::transaction(function () use ($actor, $assignment, $attributes, $reason): UserJobPosition {
            $locked = UserJobPosition::query()->whereKey($assignment->getKey())->lockForUpdate()->firstOrFail();
            $before = $locked->toArray();
            $attributes = $this->normalizeAttributes([
                ...$locked->only([
                    'user_id', 'mst_job_position_id', 'is_active', 'effective_from',
                    'effective_until', 'assignment_source',
                ]),
                ...$attributes,
            ]);
            $this->assertNoOverlap($attributes, (int) $locked->getKey());
            $locked->fill($attributes)->save();
            $this->audit($locked, $actor, 'updated', $before, $locked->fresh()->toArray(), $reason);

            return $locked->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function normalizeAttributes(array $attributes): array
    {
        if (! Schema::hasColumn('user_job_positions', 'effective_from')) {
            unset($attributes['effective_from'], $attributes['effective_until'], $attributes['assignment_source']);

            return $attributes;
        }

        if ((bool) ($attributes['is_active'] ?? true) && empty($attributes['effective_from'])) {
            $attributes['effective_from'] = today()->toDateString();
        }
        if (Schema::hasColumn('user_job_positions', 'assignment_source')
            && trim((string) ($attributes['assignment_source'] ?? '')) === '') {
            $attributes['assignment_source'] = 'km_module';
        }

        return $attributes;
    }

    public function toggle(User $actor, UserJobPosition $assignment, string $reason): UserJobPosition
    {
        $attributes = $assignment->only([
            'user_id', 'mst_job_position_id', 'effective_from', 'effective_until', 'assignment_source',
        ]);
        $attributes['is_active'] = ! $assignment->is_active;

        return $this->update($actor, $assignment, $attributes, $reason);
    }

    /** @param array<string, mixed> $attributes */
    public function assertNoOverlap(array $attributes, ?int $exceptId = null): void
    {
        if (! (bool) ($attributes['is_active'] ?? true)
            || ! Schema::hasColumn('user_job_positions', 'effective_from')) {
            return;
        }
        $from = $attributes['effective_from'] ?? today()->toDateString();
        $until = $attributes['effective_until'] ?? null;
        $query = UserJobPosition::query()
            ->where('user_id', $attributes['user_id'])
            ->where('mst_job_position_id', $attributes['mst_job_position_id'])
            ->where('is_active', true)
            ->when($exceptId !== null, static fn ($q) => $q->whereKeyNot($exceptId))
            ->where(static function ($q) use ($until): void {
                $q->whereNull('effective_from');
                if ($until === null) {
                    $q->orWhereNotNull('effective_from');
                } else {
                    $q->orWhereDate('effective_from', '<=', $until);
                }
            })
            ->where(static fn ($q) => $q
                ->whereNull('effective_until')->orWhereDate('effective_until', '>=', $from));
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'Periode assignment aktif untuk pengguna dan posisi ini tumpang tindih.',
            ]);
        }
    }

    private function audit(
        UserJobPosition $assignment,
        User $actor,
        string $action,
        ?array $before,
        ?array $after,
        string $reason,
    ): void {
        if (! Schema::hasTable('km_organization_assignment_audits')) {
            return;
        }
        DB::table('km_organization_assignment_audits')->insert([
            'user_job_position_id' => $assignment->getKey(),
            'actor_id' => $actor->getKey(),
            'action' => $action,
            'before_state' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_state' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR),
            'reason' => trim($reason) ?: 'Perubahan melalui modul HR.',
            'created_at' => now(),
        ]);
    }
}
