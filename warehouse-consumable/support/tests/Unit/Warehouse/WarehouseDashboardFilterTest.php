<?php

namespace Tests\Unit\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Tests\TestCase;

class WarehouseDashboardFilterTest extends TestCase
{
    public function test_query_string_preserves_explicit_dimensions(): void
    {
        $filter = WarehouseDashboardFilter::fromRequest(Request::create('/warehouse/dashboard', 'GET', [
            'date_from' => '2026-08-01', 'date_to' => '2026-08-07', 'transaction_type' => 'OUT', 'category_id' => 3, 'section' => 'Assembly',
        ]));

        self::assertSame('2026-08-01', $filter->from->toDateString());
        self::assertSame('2026-08-07', $filter->to->toDateString());
        self::assertSame(['date_from' => '2026-08-01', 'date_to' => '2026-08-07', 'transaction_type' => 'OUT', 'category_id' => 3, 'section' => 'Assembly'], $filter->toQueryString());
    }

    public function test_current_month_uses_calendar_bounds_without_dashboard_dimensions(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 14:30:00', 'Asia/Jakarta'));

        try {
            $filter = WarehouseDashboardFilter::currentMonth();

            self::assertSame('2026-08-01 00:00:00', $filter->from->format('Y-m-d H:i:s'));
            self::assertSame('2026-08-31 23:59:59', $filter->to->format('Y-m-d H:i:s'));
            self::assertNull($filter->transactionType);
            self::assertNull($filter->categoryId);
            self::assertNull($filter->consumableId);
            self::assertNull($filter->section);
            self::assertNull($filter->verifiedUserId);
            self::assertNull($filter->stockStatus);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_trend_filter_uses_its_own_date_parameters_without_dashboard_dimensions(): void
    {
        $filter = WarehouseDashboardFilter::fromTrendRequest(Request::create('/warehouse/dashboard', 'GET', [
            'trend_date_from' => '2026-08-01',
            'trend_date_to' => '2026-08-07',
            'transaction_type' => 'OUT',
            'category_id' => 3,
        ]));

        self::assertSame('2026-08-01', $filter->from->toDateString());
        self::assertSame('2026-08-07', $filter->to->toDateString());
        self::assertNull($filter->transactionType);
        self::assertNull($filter->categoryId);
        self::assertNull($filter->consumableId);
        self::assertNull($filter->section);
        self::assertNull($filter->verifiedUserId);
        self::assertNull($filter->stockStatus);
    }

    public function test_default_period_uses_configured_30_day_window_without_dimensions(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 14:30:00', 'Asia/Jakarta'));

        try {
            $filter = WarehouseDashboardFilter::defaultPeriod();

            self::assertSame('2026-07-13 00:00:00', $filter->from->format('Y-m-d H:i:s'));
            self::assertSame('2026-08-11 23:59:59', $filter->to->format('Y-m-d H:i:s'));
            self::assertNull($filter->transactionType);
            self::assertNull($filter->categoryId);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
