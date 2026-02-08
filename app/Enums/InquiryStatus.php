<?php

namespace App\Enums;

enum InquiryStatus: int
{
    case DRAFT_0 = 0;
    case DRAFT_1 = 1;
    case OPEN = 2;
    case APPROVE_KA_DEPT = 3;
    case APPROVE_KA_SIE = 4;
    case ON_PROGRESS = 5;
    case FINISHED = 6;
    case REJECTED = 7;
    case APPROVE_INVENTORY = 8;
    case CONFIRM_PURCHASING = 9;

    /**
     * Get status label
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT_0 => 'Draft',
            self::DRAFT_1 => 'Draft',
            self::OPEN => 'Open',
            self::APPROVE_KA_DEPT => 'Approve Ka.Dept',
            self::APPROVE_KA_SIE => 'Approve Ka.Sie',
            self::ON_PROGRESS => 'On Progress',
            self::FINISHED => 'Finished',
            self::REJECTED => 'Rejected',
            self::APPROVE_INVENTORY => 'Approve Inventory',
            self::CONFIRM_PURCHASING => 'Confirm Purchasing',
        };
    }

    /**
     * Get status CSS class
     * 
     * @return string
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::DRAFT_0 => 'btn-secondary btn-custom-draft',
            self::DRAFT_1 => 'btn-secondary btn-custom-draft',
            self::OPEN => 'btn-success btn-custom-open',
            self::APPROVE_KA_DEPT => 'btn-danger btn-custom-approve-dept',
            self::APPROVE_KA_SIE => 'btn-info btn-custom-approve-sie',
            self::ON_PROGRESS => 'btn-warning btn-custom-in-progress',
            self::FINISHED => 'btn-primary btn-custom-finished',
            self::REJECTED => 'btn-danger btn-custom-rejected',
            self::APPROVE_INVENTORY => 'btn-danger btn-custom-inventory',
            self::CONFIRM_PURCHASING => 'btn-warning btn-custom-confirm-purchasing',
        };
    }

    /**
     * Get status metadata (label and class)
     * 
     * @return array{label: string, class: string}
     */
    public function getMeta(): array
    {
        return [
            'label' => $this->getLabel(),
            'class' => $this->getCssClass(),
        ];
    }

    /**
     * Get all status values
     * 
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all active statuses (for filtering)
     * 
     * @return array
     */
    public static function activeStatuses(): array
    {
        return self::values();
    }
}

