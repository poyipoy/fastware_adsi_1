<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\Insight;
use App\Models\KmBadge;
use App\Models\KmCompletionEvent;
use App\Models\KmPengajuan;
use App\Models\KmUserBadge;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class KmGamificationService
{
    /** @return array{tier: string|null, points: int, next_tier: string|null, points_to_next: int, badges: mixed} */
    public function profile(User $user): array
    {
        $points = max(0, (int) ($user->km_total_poin ?? 0));
        [$tier, $next, $remaining] = match (true) {
            $points >= 300 => ['Gold', null, 0],
            $points >= 150 => ['Silver', 'Gold', 300 - $points],
            $points >= 50 => ['Bronze', 'Silver', 150 - $points],
            default => [null, 'Bronze', 50 - $points],
        };

        return [
            'tier' => $tier,
            'points' => $points,
            'next_tier' => $next,
            'points_to_next' => $remaining,
            'badges' => Schema::hasTable('km_user_badges')
                ? KmUserBadge::query()->with('badge')->where('user_id', $user->getKey())->oldest('awarded_at')->get()
                : collect(),
        ];
    }

    public function awardEligible(User|int $user): void
    {
        if (! Schema::hasTable('km_badges')) {
            return;
        }
        $model = $user instanceof User ? $user : User::query()->find($user);
        if ($model === null) {
            return;
        }
        $counts = [
            'completion' => Schema::hasTable('km_completion_events')
                ? KmCompletionEvent::query()->where('user_id', $model->getKey())->count() : 0,
            'publication' => KmPengajuan::query()
                ->where('id_user', $model->getKey())
                ->where('status', KmDocumentStatus::PUBLISHED->value)
                ->count(),
            'featured_insight' => Insight::query()->where('id_user', $model->getKey())->whereNotNull('featured_at')->count(),
        ];
        foreach (KmBadge::query()->where('is_active', true)->get() as $badge) {
            $count = (int) ($counts[$badge->event_type] ?? 0);
            if ($count < (int) $badge->threshold) {
                continue;
            }
            KmUserBadge::query()->firstOrCreate([
                'user_id' => $model->getKey(),
                'badge_id' => $badge->getKey(),
            ], [
                'event_key' => 'badge:'.$badge->slug.':u'.$model->getKey(),
                'evidence' => ['event_type' => $badge->event_type, 'count' => $count],
                'awarded_at' => now(),
            ]);
        }
    }
}
