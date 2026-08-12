<?php

namespace App\Data\Warehouse;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final readonly class WarehouseDashboardFilter
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public ?string $transactionType = null,
        public ?int $categoryId = null,
        public ?int $consumableId = null,
        public ?string $section = null,
        public ?int $verifiedUserId = null,
        public ?string $stockStatus = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $period = self::periodFromRequest($request, 'date_from', 'date_to');

        return new self(
            from: $period->from,
            to: $period->to,
            transactionType: $request->string('transaction_type')->trim()->value() ?: null,
            categoryId: $request->integer('category_id') ?: null,
            consumableId: $request->integer('consumable_id') ?: null,
            section: $request->string('section')->trim()->value() ?: null,
            verifiedUserId: $request->integer('verified_user_id') ?: null,
            stockStatus: $request->string('stock_status')->trim()->value() ?: null,
        );
    }

    public static function fromTrendRequest(Request $request): self
    {
        return self::periodFromRequest($request, 'trend_date_from', 'trend_date_to');
    }

    public static function defaultPeriod(): self
    {
        $to = CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'))->endOfDay();
        $defaultDays = max(1, (int) config('warehouse.dashboard.default_period_days', 30));

        return new self(
            from: $to->subDays($defaultDays - 1)->startOfDay(),
            to: $to,
        );
    }

    public static function currentMonth(): self
    {
        $now = CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'));

        return new self(
            from: $now->startOfMonth()->startOfDay(),
            to: $now->endOfMonth()->endOfDay(),
        );
    }

    public function toQueryString(): array
    {
        return array_filter([
            'date_from' => $this->from->toDateString(),
            'date_to' => $this->to->toDateString(),
            'transaction_type' => $this->transactionType,
            'category_id' => $this->categoryId,
            'consumable_id' => $this->consumableId,
            'section' => $this->section,
            'verified_user_id' => $this->verifiedUserId,
            'stock_status' => $this->stockStatus,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private static function periodFromRequest(Request $request, string $fromKey, string $toKey): self
    {
        $default = self::defaultPeriod();
        $from = $default->from;
        $to = $default->to;
        $fromInput = $request->string($fromKey)->trim()->value();
        $toInput = $request->string($toKey)->trim()->value();

        if ($fromInput !== '') {
            $from = CarbonImmutable::parse($fromInput, config('app.timezone', 'Asia/Jakarta'))->startOfDay();
        }

        if ($toInput !== '') {
            $to = CarbonImmutable::parse($toInput, config('app.timezone', 'Asia/Jakarta'))->endOfDay();
        }

        return new self($from, $to);
    }
}
