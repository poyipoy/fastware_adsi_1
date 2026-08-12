<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmAssignment;
use App\Models\KmCompletionEvent;
use App\Models\KmDocumentVersion;
use App\Models\KmAssignmentUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KmAssignmentService
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmRbacService $rbac,
        private readonly KmTargetingService $targeting,
        private readonly KmNotificationService $notifications,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(User $actor, array $payload): KmAssignment
    {
        return DB::transaction(function () use ($actor, $payload): KmAssignment {
            $version = KmDocumentVersion::query()->with('document')->whereKey($payload['document_version_id'])
                ->lockForUpdate()->firstOrFail();
            if (($version->version_status?->value ?? $version->version_status) !== 'published' || ! $version->isReady()) {
                throw ValidationException::withMessages([
                    'document_version_id' => 'Versi harus published dan selesai diproses.',
                ]);
            }

            $userIds = collect($payload['target_user_ids'] ?? [])->map('intval')->unique();
            $departmentIds = collect($payload['target_department_ids'] ?? [])->map('intval')->unique();
            $positionIds = collect($payload['target_job_position_ids'] ?? [])->map('intval')->unique();
            $recipients = User::query()->where('is_active', false)->orderBy('id')->get()
                ->filter(function (User $user) use ($version, $userIds, $departmentIds, $positionIds): bool {
                    if (! $this->access->isPublishedDocumentEligible($user, $version->document)) {
                        return false;
                    }
                    $explicit = $userIds->contains((int) $user->getKey());
                    $activePositions = collect($this->rbac->activePositionIds($user));
                    $organizationMatch = $positionIds->intersect($activePositions)->isNotEmpty();
                    if (! $organizationMatch && $departmentIds->isNotEmpty() && $activePositions->isNotEmpty()) {
                        $organizationMatch = DB::table('mst_job_positions')
                            ->whereIn('id', $activePositions)->whereIn('department_id', $departmentIds)->exists();
                    }

                    return $explicit || $organizationMatch;
                })->values();
            if ($recipients->isEmpty()) {
                throw ValidationException::withMessages([
                    'target_user_ids' => 'Tidak ada pengguna target yang memenuhi policy visibility materi.',
                ]);
            }

            $assignment = KmAssignment::query()->create([
                'document_version_id' => $version->getKey(),
                'title' => trim((string) $payload['title']),
                'status' => 'active',
                'due_at' => $payload['due_at'],
                'target_snapshot' => [
                    'user_ids' => $userIds->values()->all(),
                    'department_ids' => $departmentIds->values()->all(),
                    'job_position_ids' => $positionIds->values()->all(),
                ],
                'created_by' => $actor->getKey(),
                'reason' => trim((string) $payload['reason']),
            ]);

            foreach ($recipients as $user) {
                $snapshot = $this->targeting->snapshot($user);
                $completion = KmCompletionEvent::query()
                    ->where('user_id', $user->getKey())
                    ->where('document_version_id', $version->getKey())
                    ->oldest('id')->first();
                $assignmentUser = $assignment->users()->create([
                    'user_id' => $user->getKey(),
                    'department_snapshot' => $snapshot['department'],
                    'job_position_snapshot' => $snapshot['position'],
                    'due_at' => $payload['due_at'],
                    'completed_at' => $completion?->completed_at,
                    'completion_event_id' => $completion?->getKey(),
                ]);
                if ($completion === null) {
                    $this->notifications->record(
                        $user,
                        'assignment_created',
                        'assignment_created:'.$assignmentUser->getKey().':u'.$user->getKey(),
                        [
                            'assignment_id' => $assignment->getKey(),
                            'document_id' => $version->km_pengajuan_id,
                            'document_version_id' => $version->getKey(),
                            'title' => $assignment->title,
                            'due_date' => $assignment->due_at->toIso8601String(),
                        ],
                    );
                }
            }

            return $assignment->refresh();
        }, 3);
    }

    /** @return array{h3: int, h1: int, overdue: int} */
    public function sendReminders(): array
    {
        $counts = ['h3' => 0, 'h1' => 0, 'overdue' => 0];
        $rows = \App\Models\KmAssignmentUser::query()
            ->with('assignment.version')
            ->whereNull('completed_at')->whereNull('exempted_at')
            ->whereHas('assignment', static fn ($query) => $query->where('status', 'active'))
            ->whereBetween('due_at', [now()->subDay()->startOfDay(), now()->addDays(3)->endOfDay()])
            ->orderBy('id')->get();
        foreach ($rows as $row) {
            $days = today()->diffInDays($row->due_at->copy()->startOfDay(), false);
            $field = match ($days) { 3 => 'reminded_h3_at', 1 => 'reminded_h1_at', -1 => 'overdue_notified_at', default => null };
            $key = match ($days) { 3 => 'h3', 1 => 'h1', -1 => 'overdue', default => null };
            if ($field === null || $row->{$field} !== null) {
                continue;
            }
            $type = $days < 0 ? 'assignment_overdue' : 'assignment_reminder';
            $this->notifications->record(
                (int) $row->user_id,
                $type,
                $type.':'.$row->getKey().':'.$days,
                [
                    'assignment_id' => $row->assignment_id,
                    'document_id' => $row->assignment->version->km_pengajuan_id,
                    'document_version_id' => $row->assignment->document_version_id,
                    'title' => $row->assignment->title,
                    'due_date' => $row->due_at->toIso8601String(),
                ],
            );
            $row->forceFill([$field => now()])->save();
            $counts[$key]++;
        }

        return $counts;
    }

    public function exempt(User $actor, KmAssignmentUser $recipient, string $reason): KmAssignmentUser
    {
        return DB::transaction(function () use ($actor, $recipient, $reason): KmAssignmentUser {
            $locked = KmAssignmentUser::query()->with('assignment.version')
                ->whereKey($recipient->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->completed_at !== null) {
                throw ValidationException::withMessages(['reason' => 'Penerima yang sudah selesai tidak dapat dikecualikan.']);
            }
            $locked->forceFill([
                'exempted_at' => $locked->exempted_at ?? now(),
                'exempted_by' => $actor->getKey(),
                'exemption_reason' => trim($reason),
            ])->save();
            $this->notifications->record(
                (int) $locked->user_id,
                'assignment_exempted',
                'assignment_exempted:'.$locked->getKey(),
                [
                    'assignment_id' => $locked->assignment_id,
                    'document_id' => $locked->assignment->version->km_pengajuan_id,
                    'document_version_id' => $locked->assignment->document_version_id,
                    'title' => $locked->assignment->title,
                    'reason' => $reason,
                ],
            );

            return $locked->refresh();
        }, 3);
    }
}
