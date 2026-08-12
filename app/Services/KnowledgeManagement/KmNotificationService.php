<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmNotification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class KmNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(int|User $recipient, string $type, string $eventKey, array $data): void
    {
        $userId = $recipient instanceof User ? (int) $recipient->getKey() : $recipient;
        if ($userId <= 0) {
            return;
        }

        $payload = [
            'user_id' => $userId,
            'type' => mb_substr(trim($type), 0, 48),
            'event_key' => mb_substr(trim($eventKey), 0, 191),
            'data' => json_encode(
                $this->sanitizeData($data),
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE,
            ),
            'read_at' => null,
            'created_at' => now(),
        ];

        $persist = function () use ($payload): void {
            try {
                DB::table('km_notifications')->insertOrIgnore($payload);
            } catch (Throwable $exception) {
                report($exception);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($persist);

            return;
        }

        $persist();
    }

    /**
     * @param  iterable<int>  $userIds
     * @param  array<string, mixed>  $data
     */
    public function recordMany(
        iterable $userIds,
        string $type,
        string $eventKeyPrefix,
        array $data,
    ): void {
        $uniqueIds = collect($userIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        foreach ($uniqueIds as $userId) {
            $this->record(
                $userId,
                $type,
                mb_substr($eventKeyPrefix.':u'.$userId, 0, 191),
                $data,
            );
        }
    }

    public function paginateFor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return KmNotification::query()
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->paginate(max(1, min($perPage, 50)));
    }

    public function unreadCount(User $user): int
    {
        return KmNotification::query()
            ->where('user_id', $user->getKey())
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, int $notificationId): bool
    {
        if ($notificationId <= 0) {
            return false;
        }

        $notification = KmNotification::query()
            ->whereKey($notificationId)
            ->where('user_id', $user->getKey())
            ->first();
        if ($notification === null) {
            return false;
        }

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return true;
    }

    public function markAllRead(User $user): int
    {
        return KmNotification::query()
            ->where('user_id', $user->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, int|string|null>
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        foreach (['document_id', 'document_version_id', 'insight_id', 'assignment_id', 'actor_id'] as $key) {
            if (isset($data[$key]) && (int) $data[$key] > 0) {
                $sanitized[$key] = (int) $data[$key];
            }
        }
        foreach ([
            'title' => 160,
            'reason' => 240,
            'actor_name' => 120,
            'reaction' => 32,
            'due_date' => 32,
        ] as $key => $limit) {
            if (isset($data[$key])) {
                $value = trim((string) $data[$key]);
                if ($value !== '') {
                    $sanitized[$key] = mb_substr($value, 0, $limit);
                }
            }
        }

        return $sanitized;
    }
}
