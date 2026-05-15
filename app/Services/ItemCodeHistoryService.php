<?php

namespace App\Services;

use App\Models\ItemCode;
use App\Models\TrsItemCodeHistory;
use Illuminate\Support\Facades\Auth;

class ItemCodeHistoryService
{
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_IMPORTED = 'imported';
    public const ACTION_SUBMITTED = 'submitted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_FINISHED = 'finished';

    public function record(
        ItemCode $itemCode,
        string $action,
        string $summary,
        ?string $statusFrom = null,
        ?string $statusTo = null,
        array $changeSet = []
    ): TrsItemCodeHistory {
        $actor = Auth::user();

        return TrsItemCodeHistory::create([
            'item_code_id' => $itemCode->id,
            'action' => $action,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'summary' => $summary,
            'change_set' => array_values($this->filterChangedRows($changeSet)),
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $changeSet
     * @return array<int, array<string, mixed>>
     */
    private function filterChangedRows(array $changeSet): array
    {
        return array_filter($changeSet, function ($row): bool {
            if (!is_array($row)) {
                return false;
            }

            if (!array_key_exists('old', $row) || !array_key_exists('new', $row)) {
                return true;
            }

            return $this->normalizeValue($row['old']) !== $this->normalizeValue($row['new']);
        });
    }

    private function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return trim((string) $value);
    }
}
