<?php

namespace App\Services;

use App\Enums\ProcurementMenuAccessGroup;
use App\Models\ItemCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ItemCodeCancellationService
{
    public function __construct(
        private readonly ItemCodeHistoryService $historyService
    ) {
    }

    public function cancel(int $itemCodeId, User $actor): ItemCode
    {
        abort_unless(
            ProcurementMenuAccessGroup::ITEM_CODE_CANCELLER->hasAccess((string) $actor->name),
            403,
            'Unauthorized: hanya ILYAS NOOR FIRDAUS yang dapat membatalkan Item Code.'
        );

        return DB::transaction(function () use ($itemCodeId, $actor): ItemCode {
            $itemCode = ItemCode::query()
                ->lockForUpdate()
                ->findOrFail($itemCodeId);

            abort_unless(
                $itemCode->canTransitionTo(ItemCode::STATUS_CANCELLED),
                403,
                'Transisi status tidak valid: hanya Approved 1 atau Approved 2 yang dapat dibatalkan.'
            );

            $statusFrom = (string) $itemCode->status;

            $itemCode->update([
                'status' => ItemCode::STATUS_CANCELLED,
                'cancelled_by' => $actor->getKey(),
                'cancelled_at' => now(),
            ]);

            $itemCode->refresh();

            $this->historyService->record(
                $itemCode,
                ItemCodeHistoryService::ACTION_CANCELLED,
                'Data item code dibatalkan secara permanen.',
                $statusFrom,
                $itemCode->status,
                [[
                    'field' => 'status',
                    'label' => 'Status',
                    'old' => $this->statusLabel($statusFrom),
                    'new' => $this->statusLabel((string) $itemCode->status),
                ]]
            );

            return $itemCode;
        });
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            ItemCode::STATUS_APPROVED_1 => 'Approved 1',
            ItemCode::STATUS_APPROVED_2 => 'Approved 2',
            ItemCode::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
