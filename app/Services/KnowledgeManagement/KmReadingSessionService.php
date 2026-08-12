<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmPengajuan;
use App\Models\KmReadingSession;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class KmReadingSessionService
{
    /** @param array<string, mixed> $progress */
    public function creditedDelta(
        User $user,
        KmPengajuan $document,
        ?int $versionId,
        array $progress,
    ): int {
        $requestedDelta = max(0, (int) ($progress['active_delta'] ?? 0));
        if ($versionId === null || ! Schema::hasTable('km_reading_sessions')
            || empty($progress['session_token'])) {
            return $requestedDelta;
        }

        $sessionHash = hash('sha256', (string) $progress['session_token']);
        $deviceHash = empty($progress['device_token'])
            ? null
            : hash('sha256', (string) $progress['device_token']);
        $clientTotal = max(0, (int) ($progress['session_active_seconds'] ?? $requestedDelta));
        $current = KmReadingSession::query()
            ->where('user_id', $user->getKey())
            ->where('document_version_id', $versionId)
            ->where('session_hash', $sessionHash)
            ->lockForUpdate()
            ->first();
        $previousClientTotal = (int) ($current?->client_active_seconds ?? 0);
        $uncreditedClientDelta = max(0, $clientTotal - $previousClientTotal);
        $maximumDelta = max(0, (int) config('knowledge_management.reading.maximum_active_delta_seconds', 120));
        $candidate = min($requestedDelta, $uncreditedClientDelta, $maximumDelta);

        $inactiveTimeout = max(
            5,
            (int) config('knowledge_management.reading.inactive_timeout_seconds', 60),
        );
        $otherSessionActive = KmReadingSession::query()
            ->where('user_id', $user->getKey())
            ->where('document_version_id', $versionId)
            ->when($current !== null, static fn ($query) => $query->whereKeyNot($current->getKey()))
            ->where('last_seen_at', '>=', now()->subSeconds($inactiveTimeout))
            ->lockForUpdate()
            ->exists();
        $credited = $otherSessionActive ? 0 : $candidate;

        if ($current === null) {
            $current = KmReadingSession::query()->create([
                'user_id' => $user->getKey(),
                'document_id' => $document->getKey(),
                'document_version_id' => $versionId,
                'session_hash' => $sessionHash,
                'device_hash' => $deviceHash,
                'client_active_seconds' => $clientTotal,
                'credited_active_seconds' => $credited,
                'started_at' => now(),
                'last_seen_at' => now(),
            ]);
        } else {
            $current->forceFill([
                'device_hash' => $deviceHash ?? $current->device_hash,
                'client_active_seconds' => max($previousClientTotal, $clientTotal),
                'credited_active_seconds' => (int) $current->credited_active_seconds + $credited,
                'last_seen_at' => now(),
                'ended_at' => null,
            ])->save();
        }

        return $credited;
    }
}
