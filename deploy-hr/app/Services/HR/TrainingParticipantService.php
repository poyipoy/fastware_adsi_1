<?php

namespace App\Services\HR;

use App\Models\TcPeopleDevelopment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TrainingParticipantService
{
    public function __construct(private readonly JobPositionAccessService $jobPositionAccess)
    {
    }

    public function prepareRows(array $rows, User $actor): array
    {
        $prepared = [];

        foreach (array_values($rows) as $index => $row) {
            $isSharing = filter_var($row['is_sharing_knowledge'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $field = "rows.{$index}";

            if ($isSharing) {
                $rawIds = collect($row['participant_user_ids'] ?? [])
                    ->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT))
                    ->filter(fn ($id) => $id !== false && $id > 0)
                    ->values();
                $ids = $rawIds
                    ->uniqueStrict()
                    ->sort()
                    ->values();

                if ($rawIds->count() !== $ids->count()) {
                    throw ValidationException::withMessages([
                        "{$field}.participant_user_ids" => 'Participant Sharing Knowledge tidak boleh duplikat.',
                    ]);
                }

                if ($ids->isEmpty()) {
                    throw ValidationException::withMessages([
                        "{$field}.participant_user_ids" => 'Sharing Knowledge wajib memiliki minimal satu participant.',
                    ]);
                }

                $this->assertEligible($ids, $actor, "{$field}.participant_user_ids");
                $row['participant_user_ids'] = $ids->all();
                $row['id_user'] = $ids->first();
            } else {
                $id = filter_var($row['id_user'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) {
                    throw ValidationException::withMessages([
                        "{$field}.id_user" => 'Training biasa wajib memiliki satu karyawan.',
                    ]);
                }

                $this->assertEligible(collect([$id]), $actor, "{$field}.id_user");
                $row['id_user'] = $id;
                $row['participant_user_ids'] = [];
            }

            $row['is_sharing_knowledge'] = $isSharing;
            $prepared[] = $row;
        }

        return $prepared;
    }

    public function sync(TcPeopleDevelopment $training, array $row): void
    {
        if (! $row['is_sharing_knowledge']) {
            $training->participants()->sync([]);

            return;
        }

        $training->participants()->sync($row['participant_user_ids']);
    }

    public function readableParticipants(TcPeopleDevelopment $training): Collection
    {
        if ($training->participants->isNotEmpty()) {
            return $training->participants->unique('id')->values();
        }

        return $training->is_sharing_knowledge && $training->user
            ? collect([$training->user])
            : collect();
    }

    private function assertEligible(Collection $ids, User $actor, string $field): void
    {
        $allowedPositionIds = $this->jobPositionAccess
            ->getAccessibleJobPositions($actor, false)
            ->pluck('id');

        $eligible = User::query()
            ->whereIn('id', $ids)
            ->where('is_active', 0)
            ->whereHas('userJobPositions', function ($query) use ($allowedPositionIds) {
                $query->where('is_active', true)
                    ->where(fn ($dates) => $dates->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
                    ->where(fn ($dates) => $dates->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()))
                    ->whereIn('mst_job_position_id', $allowedPositionIds)
                    ->whereHas('jobPosition', fn ($position) => $position->where('is_active', true));
            })
            ->pluck('id');

        $invalid = $ids->diff($eligible)->values();
        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                $field => 'Participant tidak aktif, tidak ditemukan, atau berada di luar scope HR: '.$invalid->implode(', ').'.',
            ]);
        }
    }
}
