<?php

namespace App\Enums\Warehouse;

enum WarehouseVerificationStatus: string
{
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
}
