<?php

namespace App\Services\Warehouse;

use Illuminate\Support\Str;

final class WarehouseTransactionNumberGenerator
{
    public function generate(): string
    {
        return 'WH-'.now(config('app.timezone', 'Asia/Jakarta'))->format('Ymd-His').'-'.strtoupper(Str::random(8));
    }
}
