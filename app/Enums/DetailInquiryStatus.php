<?php

namespace App\Enums;

enum DetailInquiryStatus: int
{
    case OPEN = 2;
    case ON_PROGRESS = 5;
    case FINISH = 6;
    case APPROVE_INVENTORY = 8;
    case CONFIRM = 9;

    /**
     * Get status label
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::ON_PROGRESS => 'On Progress',
            self::FINISH => 'Finish',
            self::APPROVE_INVENTORY => 'Approve Inventory',
            self::CONFIRM => 'Confirm',
        };
    }

    /**
     * Get status CSS class (badge)
     * 
     * @return string
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::OPEN => 'badge bg-success text-dark',
            self::ON_PROGRESS => 'badge bg-warning text-dark',
            self::FINISH => 'badge bg-primary',
            self::APPROVE_INVENTORY => 'badge bg-info text-dark',
            self::CONFIRM => 'badge bg-warning text-dark',
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
}

