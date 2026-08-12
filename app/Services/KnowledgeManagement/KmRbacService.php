<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmAccessRule;
use App\Models\User;
use App\Models\UserJobPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class KmRbacService
{
    public const ABILITIES = [
        'km.oversight' => 'Pengawasan dan deaktivasi materi',
        'km.insight.moderate' => 'Moderasi insight',
        'km.analytics.view' => 'Analitik KM',
        'km.assignment.manage' => 'Kelola assignment wajib',
        'km.completion.override' => 'Override completion aksesibilitas',
        'km.processing.recover_original' => 'Recovery file gagal diproses',
        'km.export' => 'Ekspor data KM',
        'km.access.manage' => 'Kelola akses KM',
    ];

    public function allows(User $user, string $ability, bool $default = false): bool
    {
        if (! Schema::hasTable('km_access_rules')) {
            return $default;
        }

        $base = KmAccessRule::query()
            ->where('ability', $ability)
            ->where(static fn ($query) => $query
                ->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(static fn ($query) => $query
                ->whereNull('valid_until')->orWhere('valid_until', '>=', now()));

        $userRule = (clone $base)
            ->where('subject_type', 'user')
            ->where('subject_id', $user->getKey())
            ->latest('id')
            ->first();
        if ($userRule !== null) {
            return $userRule->effect === 'allow';
        }

        $positionIds = $this->activePositionIds($user);
        if ($positionIds !== []) {
            $effects = (clone $base)
                ->where('subject_type', 'job_position')
                ->whereIn('subject_id', $positionIds)
                ->pluck('effect');
            if ($effects->contains('deny')) {
                return false;
            }
            if ($effects->contains('allow')) {
                return true;
            }
        }

        if ($user->role_id !== null) {
            $roleRule = (clone $base)
                ->where('subject_type', 'role')
                ->where('subject_id', $user->role_id)
                ->latest('id')
                ->first();
            if ($roleRule !== null) {
                return $roleRule->effect === 'allow';
            }
        }

        return $default;
    }

    /** @param array<string, mixed> $attributes */
    public function createRule(User $actor, array $attributes): KmAccessRule
    {
        return DB::transaction(function () use ($actor, $attributes): KmAccessRule {
            $this->assertNoOverlap($attributes);
            $rule = KmAccessRule::query()->create([
                ...$attributes,
                'created_by' => $actor->getKey(),
            ]);
            $this->audit($rule, $actor, 'created', null, $rule->toArray(), (string) $attributes['reason']);

            return $rule;
        });
    }

    public function deleteRule(User $actor, KmAccessRule $rule, string $reason): void
    {
        DB::transaction(function () use ($actor, $rule, $reason): void {
            $before = $rule->toArray();
            $id = $rule->getKey();
            $rule->delete();
            DB::table('km_access_audits')->insert([
                'access_rule_id' => null,
                'actor_id' => $actor->getKey(),
                'action' => 'deleted',
                'before_state' => json_encode($before, JSON_THROW_ON_ERROR),
                'after_state' => null,
                'reason' => trim($reason),
                'created_at' => now(),
            ]);
        });
    }

    /** @return list<int> */
    public function activePositionIds(User $user): array
    {
        if (! Schema::hasTable('user_job_positions')) {
            return [];
        }

        return UserJobPosition::query()
            ->where('user_id', $user->getKey())
            ->activeAt()
            ->pluck('mst_job_position_id')
            ->map('intval')->unique()->values()->all();
    }

    /** @param array<string, mixed> $attributes */
    private function assertNoOverlap(array $attributes): void
    {
        $from = $attributes['valid_from'] ?? null;
        $until = $attributes['valid_until'] ?? null;
        $conflict = KmAccessRule::query()
            ->where('subject_type', $attributes['subject_type'])
            ->where('subject_id', $attributes['subject_id'])
            ->where('ability', $attributes['ability'])
            ->where(static function ($query) use ($until): void {
                $query->whereNull('valid_from');
                if ($until !== null) {
                    $query->orWhere('valid_from', '<=', $until);
                } else {
                    $query->orWhereNotNull('valid_from');
                }
            })
            ->where(static function ($query) use ($from): void {
                $query->whereNull('valid_until');
                if ($from !== null) {
                    $query->orWhere('valid_until', '>=', $from);
                } else {
                    $query->orWhereNotNull('valid_until');
                }
            })
            ->exists();
        if ($conflict) {
            throw ValidationException::withMessages([
                'ability' => 'Rule aktif untuk subject dan ability ini memiliki periode yang tumpang tindih.',
            ]);
        }
    }

    private function audit(
        KmAccessRule $rule,
        User $actor,
        string $action,
        ?array $before,
        ?array $after,
        string $reason,
    ): void {
        DB::table('km_access_audits')->insert([
            'access_rule_id' => $rule->getKey(),
            'actor_id' => $actor->getKey(),
            'action' => $action,
            'before_state' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_state' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR),
            'reason' => trim($reason),
            'created_at' => now(),
        ]);
    }
}
